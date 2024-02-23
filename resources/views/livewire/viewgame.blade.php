<div>
    @if ($unauthorised)
      <p>You are not allowed to view this game</p>
    @else
      <p>Viewing {{$username}}'s solution</p>
      <p>Target word: {{$target}}</p>
      @foreach ($allTurns as $turn)
        Turn {{ $loop->index + 1 }}
        <x-turn :turn=$turn :target=$target />
      @endforeach
    @endif
    <p>
      <a class="text-sky-600" href="/?date={{$userTimestamp}}">Return to puzzle results</a>
    </p>
</div>
