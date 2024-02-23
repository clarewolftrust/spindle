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

    public function mount() {
      // retrieve all turns for this game, as long as the logged in user
      // is the same as the specified user, or friends with that user
      $ourId = Auth::id();
      $friends = $this->getFriends();
      $friendIds = array_column($friends, 'id');

      if ($ourId == $this->userId || array_search($this->userId, $friendIds, true)) {
        // we are allowed
        $game = Game::where('userOrSessionId', $this->userId)->where('userTimestamp', $this->userTimestamp)->first();
        if (!$game) {
          // game doesn't exist - user mustn't have played that day
          $this->unauthorised = true;
        } else {
          $user = User::find($this->userId);
          $this->username = $user->name;
          $this->target = $game->target;
          $this->allTurns = Turn::where('game_id', $game->id)->get();
          for ($i=0; $i < count($this->allTurns); $i++) {
            $this->allTurns[$i]->grid = json_decode(($this->allTurns[$i]->grid));
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
