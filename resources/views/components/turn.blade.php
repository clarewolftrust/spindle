<div class="mb-3">
  @foreach ($turn->grid as $rowIndex=>$rowOfLetters)<div class="block">
    @foreach ($rowOfLetters as $colIndex=>$letter)<span class="inline-block text-2xl w-11 h-11 pt-1 mb-1 mr-1 text-center {{$bgColour($rowIndex, $colIndex)}}">{{ $letter }}</span>@endforeach
  </div>@endforeach
</div>