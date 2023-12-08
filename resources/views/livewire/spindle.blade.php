<div
  x-data="{ 
    start: null, 
    end: null,
    invalidWordStart: null,
    invalidWordEnd: null,
    validWordStart: null,
    validWordEnd: null,
    loading: false,
    spinning: false,

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
        if (this.spinning || this.loading || $wire.victory) {
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
                console.log('result', this.lastResult);

                if (this.lastResult.valid) {
                    // it was a valid move, do an animation
                    // it was invalid - flash the letters red
                    this.validWordStart = (this.start[0] < this.end[0] || this.start[1] < this.end[1]) ? this.start : this.end;
                    this.validWordEnd = this.validWordStart === this.start ? this.end : this.start;
                    this.spinning = true;
                    setTimeout(() => {
                        this.validWordStart = null;
                        this.validWordEnd = null;
                        this.spinning = false;
                        this.clearStartEnd();
                    }, 1000);
                } else {
                    // it was invalid - flash the letters red
                    this.invalidWordStart = (this.start[0] < this.end[0] || this.start[1] < this.end[1]) ? this.start : this.end;
                    this.invalidWordEnd = this.invalidWordStart === this.start ? this.end : this.start;
                    setTimeout(() => {
                        this.invalidWordStart = null;
                        this.invalidWordEnd = null;
                    }, 500);
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
    }
  }"
  @click.away="clearStartEnd" 
  x-init="$wire.initialise(new Date().getTimezoneOffset())"
  class="inline-block dark:text-white max-w-xl"
> 
    @if ($grid)
    <?php /*
        <p>
            Target word: {{ $targetWord }}
        </p>
        <p>
            Game ID: {{ $gameId }}
        </p>
        <p>
            User timestamp: {{ $userTimestamp }}
        </p>
        */ ?>
        <p>{{ $currentDate }}</p>
        <p>
            @if ($turnCount == 0)
              No turns taken
            @elseif ($turnCount == 1)
              1 turn taken
            @else
              {{$turnCount}} turns taken
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
                        ($wire.victory ? ' cursor-default ' : '')
                    "
                    ><span class="inline-block" x-bind:class="validWordAnimationText({{$rowIndex}}, {{$colIndex}})">
                    {{ $letter }}</span></button>@endforeach
            </div>
        @endforeach
        @if ($victory)
            <h1 class="font-bold text-4xl m-4">🎉 YOU WON!</h1>
            <p class="mb-2">How did you compare to the rest of the world? The graph shows the number of people on the Y axis, and the number of turns they took on the X axis. Your result is highlighted.</p>
            <div class="h-[210px] m-auto  bg-slate-50 dark:bg-slate-900 p-2 rounded-md">
                <div class="h-[150px] flex min-w-0 overflow-hidden justify-between items-end gap-0">
                    @foreach ($leaderboard as $bar)<div class="flex-grow flex-shrink"
                        style="height: {{$bar->userCountPercent * 100}}%; min-height: 1px; min-width: 1px; overflow: hidden;"
                        x-bind:class="{{$bar->turnCount}} === {{$turnCount}} ? 'bg-sky-300 animate-pulse' : 'bg-sky-800'"
                        title="{{$bar->turnCount}}"
                        ></div>@endforeach
                </div>
                <div class="flex justify-between items-end">
                    <div>0</div>
                    <div>{{$leaderboard[count($leaderboard) - 1]->turnCount}}</div>
                </div>
                <div class="flex justify-between items-end">
                    <div class="text-sm">turns</div>
                    <div class="text-sm">turns</div>
                </div>
            </div>
        @endif
    @endif
</div>