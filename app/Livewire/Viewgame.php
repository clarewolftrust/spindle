<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\Url;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Turn;
use App\Models\Game;
use App\Models\User;

class Viewgame extends Component
{
    public function render()
    {
        return view('livewire.viewgame');
    }

    private function getFriends() {
      if (!Auth::id()) {
          return [];
      }
      return DB::select('select id, name, friendshipCode from users where id = ? or id in (
          select from_user_id from friendships where to_user_id = ?
          union all
          select to_user_id from friendships where from_user_id = ?
      );', [Auth::id(), Auth::id(), Auth::id()]);
    }

    public $unauthorised = false;
    public $allTurns = [];
    public $target = '';
    public $username = '';

    private function swap(&$x, &$y) {
      $tmp=$x;
      $x=$y;
      $y=$tmp;
    }

    private function rotate($grid, $start, $end) {
      $length = abs($start[0] - $end[0]) + abs($start[1] - $end[1]);
      for ($i = 0; $i < ($length/2); $i++) {
        if ($start[0] < $end[0]) {
          $this->swap($grid[$end[0] - $i][$start[1]], $grid[$start[0] + $i][$start[1]]);
        } else if ($start[0] > $end[0]) {
          $this->swap($grid[$end[0] + $i][$start[1]], $grid[$start[0] - $i][$start[1]]);
        } else if ($start[1] < $end[1]) {
          $this->swap($grid[$start[0]][$end[1] - $i], $grid[$start[0]][$start[1] + $i]);
        } else if ($start[1] > $end[1]) {
          $this->swap($grid[$start[0]][$end[1] + $i], $grid[$start[0]][$start[1] - $i]);
        }
      }
      return $grid;
    }

    public function mount() {
      // you can only view today's game if you yourself have completed it
      $todayTimestamp = floor(time() / 86400);
      if ($this->userTimestamp >= $todayTimestamp - 1) {
        $ourGame = Game::where('userOrSessionId', Auth::id())->where('userTimestamp', $this->userTimestamp)->first();
        if (!$ourGame || !$ourGame->victory) {
          $this->unauthorised = true;
          return;
        }
      }

      // retrieve all turns for this game, as long as the logged in user
      // is the same as the specified user, or friends with that user
      $friends = $this->getFriends();
      $friendIds = array_column($friends, 'id');

      if (array_search($this->userId, $friendIds, true) !== false) {
        // we are allowed
        $game = Game::where('userOrSessionId', $this->userId)->where('userTimestamp', $this->userTimestamp)->first();
        if (!$game || !$game->victory) {
          // game doesn't exist - user mustn't have won that day
          $this->unauthorised = true;
        } else {
          $user = User::find($this->userId);
          $this->username = $user->name;
          $this->target = $game->target;
          $this->allTurns = Turn::where('game_id', $game->id)->get();
          for ($i=0; $i < count($this->allTurns); $i++) {
            $this->allTurns[$i]->grid = json_decode(($this->allTurns[$i]->grid));
            $this->allTurns[$i]->grid = $this->rotate($this->allTurns[$i]->grid, [$this->allTurns[$i]->startRow, $this->allTurns[$i]->startCol], [$this->allTurns[$i]->endRow, $this->allTurns[$i]->endCol]);
          }
        }
      } else {
        // we are not allowed
        $this->unauthorised = true;
      }
    }

    #[Url(as: 'date')]
    public $userTimestamp;

    #[Url(as: 'user')]
    public $userId;
}
