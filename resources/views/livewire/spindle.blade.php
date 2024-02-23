<div
  x-data="{
    start: null,
    end: null,
    invalidWordStart: null,
    invalidWordEnd: null,
    validWordStart: null,
    validWordEnd: null,
    invalidWordText: null,
    showInvalidWordText: false,
    showInvalidWordTextTimout: null,
    loading: false,
    spinning: false,
    showHelp: false,

    invalidWord(row, col) {
        if (!this.invalidWordStart || !this.invalidWordEnd) {
            return false;
        }
        if (this.invalidWordStart[0] === this.invalidWordEnd[0]) {
            // horizontal word
            return row === this.invalidWordStart[0] &&
              col >= this.invalidWordStart[1] &&
              col <= this.invalidWordEnd[1];
        }
        return col === this.invalidWordStart[1] &&
          row >= this.invalidWordStart[0] &&
          row <= this.invalidWordEnd[0];
    },

    validWordAnimation(row, col) {
        if (!this.validWordStart || !this.validWordEnd) {
            return '';
        }
        if (this.validWordStart[0] === this.validWordEnd[0]) {
            // horizontal word
            if (row !== this.validWordStart[0] ||
              col < this.validWordStart[1] ||
              col > this.validWordEnd[1]) {
                return '';
            }
            // check how far left or right of 'center' we are, and return
            // the appropriate animation
            const center = this.validWordStart[1] + (this.validWordEnd[1] - this.validWordStart[1])/2;
            const offset = col - center;
            return `z-20 animate-rotate-x-${offset.toString().replace(/\.5.*/, '-5')}`;
        }
        // vertical word
        if (col !== this.validWordStart[1] ||
          row < this.validWordStart[0] ||
          row > this.validWordEnd[0]) {
            return '';
        }
        // check how far above or below of 'center' we are, and return
        // the appropriate animation
        const center = this.validWordStart[0] + (this.validWordEnd[0] - this.validWordStart[0])/2;
        const offset = row - center;
        return `z-20 animate-rotate-y-${offset.toString().replace(/\.5.*/, '-5')}`;
    },

    validWordAnimationText(row, col) {
        const outer = this.validWordAnimation(row, col);
        return outer ? `animate-rotate-text-counter` : '';
    },

    startOrEnd(row, col) {
        return (
            this.start?.[0] === row && this.start?.[1] === col ||
            this.end?.[0] === row && this.end?.[1] === col
        );
    },

    letterBackground(row, col) {
        const inTarget = $wire.targetWord.includes($wire.grid[row][col]);
        if (this.startOrEnd(row, col)) {
            return `bg-green-500 dark:bg-green-600 ${this.loading ? 'animate-pulse' : ''}`;
        }
        if (this.invalidWord(row, col)) {
            return `animate-pulse-bg-once from-red-500 ${inTarget ? 'to-sky-50 dark:to-slate-600' : 'dark:to-slate-800 to-sky-200'}`;
        }
        if (inTarget) {
            return 'bg-sky-300 dark:bg-slate-600';
        }

        return 'bg-sky-100 dark:bg-slate-800';
    },

    async clickOnLetter(row, col) {
        if (this.spinning || this.loading || $wire.victory || $wire.inactive) {
            return;
        }

        console.log('clickOnLetter', row, col);

        if (!this.start || this.end) {
            this.start = [row, col];
            this.end = null;
        } else {
            if (row === this.start[0] && col === this.start[1]) {
                this.clearStartEnd();
            } else if (row !== this.start[0] && col != this.start[1]) {
                this.start = [row, col];
                this.end = null;
            } else {
                this.end = [ row, col ];

                // ready to submit!
                console.log('Submitting move');
                this.loading = true;
                this.lastResult = await $wire.submit(this.start, this.end);
                this.loading = false;
                if (this.showInvalidWordTextTimout) {
                    clearTimeout(this.showInvalidWordTextTimout);
                    this.showInvalidWordTextTimout = null;
                }
                console.log('result', this.lastResult);

                if (this.lastResult.valid) {
                    // it was a valid move, do an animation
                    // it was invalid - flash the letters red
                    this.validWordStart = (this.start[0] < this.end[0] || this.start[1] < this.end[1]) ? this.start : this.end;
                    this.validWordEnd = this.validWordStart === this.start ? this.end : this.start;
                    this.spinning = true;
                    this.showInvalidWordText = false;
                    if ($wire.victory) {
                        this.makeConfetti();
                    }
                    setTimeout(() => {
                        this.validWordStart = null;
                        this.validWordEnd = null;
                        this.spinning = false;
                        this.clearStartEnd();
                    }, 1000);
                } else {
                    // it was invalid - flash the letters red
                    this.invalidWordText = this.lastResult.word;
                    this.showInvalidWordText = true;
                    this.invalidWordStart = (this.start[0] < this.end[0] || this.start[1] < this.end[1]) ? this.start : this.end;
                    this.invalidWordEnd = this.invalidWordStart === this.start ? this.end : this.start;
                    setTimeout(() => {
                        this.invalidWordStart = null;
                        this.invalidWordEnd = null;
                    }, 500);
                    this.showInvalidWordTextTimout = setTimeout(() => {
                        this.showInvalidWordText = false;
                    }, 5000);
                    this.clearStartEnd();
                }
            }
        }
    },

    clearStartEnd() {
        if (this.spinning || this.loading || $wire.victory) {
            return;
        }
        console.log('clearStartEnd');
        this.start = null;
        this.end = null;
    },

    makeConfetti() {
        confetti({
            disableForReducedMotion: true,
            gravity: 1.5,
            scalar: 1.25,
            decay: 0.9,
            particleCount: 100,
        });
    },

    dismissHelp() {
        this.showHelp = false;
    },
    requestHelp() {
        this.showHelp = true;
    },

    helpAnimationPhases: ['blank', 'pause', 'tapStart', 'tapEnd', 'rotating', 'resting', 'flashing', 'disappearing'],
    helpAnimationIndex: 0,
    reachedPhase(p) {
        return this.helpAnimationPhases.indexOf(p) <= this.helpAnimationIndex;
    },
    cycleHelpAnimation() {
        this.helpAnimationIndex++;
        if (this.helpAnimationIndex >= this.helpAnimationPhases.length) {
            this.helpAnimationIndex = 0;
        }
    },
  }"
  @click.away="clearStartEnd"
  x-init="$wire.initialise(new Date().getTimezoneOffset()); setInterval(() => cycleHelpAnimation(), 1000)"
  class="inline-block dark:text-white max-w-xl"
>
    <div x-transition x-cloak x-show="showHelp" class="flex flex-col items-center justify-center
     bg-sky-200/75 dark:bg-sky-700/75 min-h-[75vh] p-3 rounded-md absolute left-[1rem] top-[9rem] z-50
     md:text-lg
     "
     style="width: calc(100vw - 2rem);"
     @click.outside="dismissHelp"
     >
        <h1 class="text-3xl font-bold">How to play</h1>
        <p class="mb-3">
            The aim is to rearrange the grid so that the <strong>target word</strong>
            is spelled out (either horizontally or vertically).
        </p>
        <p class="mb-3">
            The grid is rearranged by spinning words &mdash; again, either horizontally
            or vertically.
        </p>
        <p class="mb-3">
            To spin a word, tap the first letter, then tap the last letter. If the word is
            a valid English word, it will flip.
        </p>
        <p class="mb-3">
            In the example below, you could tap E then T to spin the word EAT:
        </p>
        <p class="mb-3 transition-opacity delay-700" x-bind:class="reachedPhase('disappearing') ? 'opacity-0' : 'opacity-100' ">
            <span class="inline-block font-mono transition-all text-2xl w-12 h-12 py-2 px-2 mr-2 bg-sky-400 dark:bg-sky-600">G</span>
            <span class="inline-block font-mono transition-all text-2xl w-12 h-12 py-2 px-2 mr-2 bg-sky-400 dark:bg-sky-600">R</span>
            <span class="inline-block font-mono transition-all text-2xl w-12 h-12 py-2 px-2 mr-2" x-bind:class="(reachedPhase('tapEnd') && !reachedPhase('flashing') ? 'bg-green-500 dark:bg-green-600' : 'bg-sky-400 dark:bg-sky-600') + ' ' + (reachedPhase('rotating') ? 'z-20 animate-rotate-x--1' : '')"
              ><span class="inline-block" x-bind:class="(reachedPhase('rotating') ? 'animate-rotate-text-counter' : '')" x-text="reachedPhase('rotating') ? 'E' : 'T'"></span></span>
            <span class="inline-block font-mono transition-all text-2xl w-12 h-12 py-2 px-2 mr-2 bg-sky-400 dark:bg-sky-600"  x-bind:class="(reachedPhase('rotating') ? 'z-20 animate-rotate-x-0' : '')">
            <span class="inline-block" x-bind:class="(reachedPhase('rotating') ? 'animate-rotate-text-counter' : '')">A</span>
            </span>
            <span class="inline-block font-mono transition-all text-2xl w-12 h-12 py-2 px-2" x-bind:class="(reachedPhase('tapStart') && !reachedPhase('flashing') ? 'bg-green-500 dark:bg-green-600' : 'bg-sky-400 dark:bg-sky-600') + ' ' + (reachedPhase('rotating') ? 'z-20 animate-rotate-x-1' : '')">
            <span class="inline-block" x-bind:class="(reachedPhase('rotating') ? 'animate-rotate-text-counter' : '')" x-text="reachedPhase('rotating') ? 'T' : 'E'"></span>
            </span>
        </p>
        <p class="mb-3">
            Try to assemble the target word using the fewest spins possible.
        </p>
        <p class="mb-3">
            <button type="button" @click="dismissHelp" class="dark:bg-sky-500 bg-sky-300 py-2 px-4 rounded-md font-bold text-xl">Let's play!</button>
        </p>
    </div>
    @if ($grid)
        <div class="mt-3" x-bind:class="showHelp ? 'blur-md overflow-hidden' : ''"
        x-init="() => {showHelp = (@auth false @else {{$turnCount}} === 0 @endauth)}">
            <?php /*

            <p>
                Game ID: {{ $gameId }}
            </p>
            <p>
                User timestamp: {{ $userTimestamp }}
            </p>
            */ ?>
            <p>
                <a href="?date={{ $userTimestamp - 1 }}" >&laquo;</a>
                {{ $currentDate }}
                @if ($userTimestamp < $nowTimestamp)
                    <a href="?date={{ $userTimestamp + 1 }}" >&raquo;</a>
                    <br/>
                    <a href="/" class="underline text-sky-700 dark:text-sky-500 decoration-dotted" >View today's puzzle</a>
                @endif
            </p>
            <p>
                Target word: <b>{{ $targetWord }}</b>
                <button type="button" @click="requestHelp" class="bg-gray-400 dark:bg-gray-700 text-white rounded-full w-6">?</button>
            </p>
            <p class="mb-3">
                @if ($turnCount == 0)
                  No turns taken
                @elseif ($turnCount == 1)
                  1 turn taken
                @else
                  {{$turnCount}} turns taken
                @endif
                @if (!$victory)
                  so far
                @endif
            </p>
            @foreach ($grid as $rowIndex=>$rowOfLetters)
                <div wire:key="{{ $rowIndex }}" class="block">
                    @foreach ($rowOfLetters as $colIndex=>$letter)<button
                        wire:key="{{ $rowIndex }}, {{ $colIndex }}"
                        @click="clickOnLetter({{ $rowIndex }}, {{ $colIndex }})"
                        class="inline-block text-2xl w-12 h-12 mb-3 mr-3 text-center transition-colors "
                        x-bind:class="
                            `${validWordAnimation({{$rowIndex}}, {{$colIndex}})} ` +
                            letterBackground({{$rowIndex}}, {{$colIndex}}) +
                            ($wire.victory || $wire.inactive ? ' cursor-default ' : '')
                        "
                        ><span class="inline-block" x-bind:class="validWordAnimationText({{$rowIndex}}, {{$colIndex}})">
                        {{ $letter }}</span></button>@endforeach
                </div>
            @endforeach
            <p class="bg-red-500 text-white px-4 py-2 inline-block m-auto rounded-md" x-show="showInvalidWordText" x-transition x-transition:enter.duration.200ms
            x-transition:leave.duration.800ms x-cloak x-text="`${invalidWordText} is not an English word`" />
            @if ($victory || $inactive)
                <div wire:transition>
                    @if ($victory)
                        <h1 class="font-bold text-4xl m-4">🎉 Victory!</h1>
                    @elseif ($turnCount)
                        <p class="mb-2">
                            Looks like you didn’t manage to solve this one.
                        </p>
                    @else
                        <p class="mb-2">Looks like you didn’t attempt this one.</p>
                    @endif
                    @if ($histogram && count($histogram) > 0)
                        <p class="mb-2">
                            @if ($victory)
                                How did you compare to the rest of the world?
                            @endif
                            The graph shows the
                            number of people on the Y axis, and the number of turns they took
                            on the X axis.
                            @if ($victory)
                                Your result is highlighted.
                            @endif
                        </p>
                        @if ($userTimestamp == $nowTimestamp)
                            <p class="mb-2">
                                Don't forget to play again tomorrow &mdash; there’s a new target word every day!
                            </p>
                        @endif
                        <div class="h-[210px] m-auto  bg-slate-50 dark:bg-slate-900 p-2 rounded-md mb-2">
                            <div class="h-[150px] flex min-w-0 overflow-hidden justify-between items-end gap-0">
                                @foreach ($histogram as $bar)<div class="flex-grow flex-shrink"
                                    style="height: {{$bar->userCountPercent * 100}}%; min-height: 1px; min-width: 1px; overflow: hidden;"
                                    x-bind:class="{{$bar->turnCount}} === {{$turnCount}} ? 'bg-sky-300 animate-pulse' : 'bg-sky-800'"
                                    title="{{$bar->turnCount}}"
                                    ></div>@endforeach
                            </div>
                            <div class="flex justify-between items-end">
                                <div>1</div>
                                <div>{{$histogram[count($histogram) - 1]->turnCount}}</div>
                            </div>
                            <div class="flex justify-between items-end">
                                <div class="text-sm">turn</div>
                                <div class="text-sm">turns</div>
                            </div>
                        </div>
                    @endif
                    <h1 class="text-xl font-bold">You and your friends</h1>
                    @auth
                        <ol class="list-decimal list-inside mb-2">
                            @foreach ($leaderboard as $player)
                                <li class="{{$player->isMe ? 'font-bold' : ''}}">
                                    {{$player->name}}
                                    @if ($player->playing)
                                        @if ($player->victory)
                                        (<a href="/view?date={{$userTimestamp}}&user={{$player->id}}" class="underline text-sky-700 dark:text-sky-500 decoration-dotted">{{$player->turnCount}} turn{{$player->turnCount == 1 ? '' : 's'}}</a>)
                                        @else ($player->playing)
                                            (in progress)
                                        @endif
                                    @else
                                        (hasn’t started today)
                                    @endif
                                </li>
                            @endforeach
                        </ol>
                        <p class="mb-2">
                            To add friends to your personalised leaderboard, enter their code in the box below,
                            or give them your code: <code class="font-mono text-slate-600 dark:text-slate-300">{{$myFriendCode}}</code>
                        </p>
                        <p x-data="{ code: '', submit() {  $wire.addFriend(this.code) } }" class="mb-2">
                            <input type="text" x-model="code" @change="submit" />
                            <button @click="submit" x-bind:disabled="!code"
                                class="bg-sky-500 disabled:bg-slate-300 text-white inline-block leading-10 px-2"
                            >Add friend</button>
                            <span x-show="$wire.badCode" x-transition class="text-red-500 font-bold">
                                Code not recognised.
                            </span>
                        </p>
                    @else
                        <p class="mb-2">
                            <a class="text-sky-600" href="/register">Create an account</a> in order to add your friends to your own personal leaderboard.
                        </p>
                    @endauth
                </div>
            @endif
        </div>
    @endif
</div>


<script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.9.2/dist/confetti.browser.min.js"></script>
