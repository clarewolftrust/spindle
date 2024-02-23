<?php

namespace App\View\Components;

use App\Models\Turn as TurnModel;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Turn extends Component
{
  /**
   * Create a new component instance.
   */
  public function __construct(public TurnModel $turn, public string $target)
  {
  }

  /**
   * Get the view / contents that represent the component.
   */
  public function render(): View|Closure|string
  {
    return view('components.turn');
  }

  public function bgColour($rowIndex, $colIndex) {
    $inTarget = strpos($this->target, $this->turn->grid[$rowIndex][$colIndex]) !== false;
    $startRow = min($this->turn->startRow, $this->turn->endRow);
    $endRow = max($this->turn->startRow, $this->turn->endRow);
    $startCol = min($this->turn->startCol, $this->turn->endCol);
    $endCol = max($this->turn->startCol, $this->turn->endCol);
    $inSelectedWord = $startRow == $endRow 
      ? $colIndex >= $startCol && $colIndex <= $endCol && $rowIndex == $startRow
      : $rowIndex >= $startRow && $rowIndex <= $endRow && $colIndex == $startCol;
    if ($inSelectedWord) {
      return 'bg-green-500 dark:bg-green-600 animate-pulse';
    }
    if ($inTarget) {
      return 'bg-sky-300 dark:bg-slate-600';
    }
    return 'bg-sky-100 dark:bg-slate-800';
  }
}
