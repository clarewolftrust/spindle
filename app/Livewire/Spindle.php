<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\Url;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;
use App\Models\Game;
use App\Models\Turn;
use App\Models\User;


class Spindle extends Component
{

    // stacked probabilities of each letter, starting with A
    private $probabilities = [
        8.4966, // A
        10.5686, // B
        15.1074, // C
        18.4918,
        29.652500000000003,
        31.464600000000004,
        33.935100000000006,
        36.938500000000005,
        44.48330000000001,
        44.67980000000001,
        45.781400000000005,
        51.270700000000005,
        54.28360000000001,
        60.93800000000001,
        68.10150000000002,
        71.26860000000002,
        71.46480000000003,
        79.04570000000002,
        84.78080000000003,
        91.73170000000003,
        95.36250000000003,
        96.36990000000003,
        97.65980000000003,
        97.95000000000003, // X
        99.72790000000003, // Y
        100.0000000000000 // Z
    ];

    public function randomLetter() {
        $random = 100 * (rand() / getrandmax());
        for ($i = 0; $i < count($this->probabilities); $i++) {
            if ($random < $this->probabilities[$i]) {
                return chr(65 + $i);
            }
        }
        return 'Z';
    }
    public $grid = null;

    public $targetWord = 'DEFAULT';

    public $currentDate = '';

    public $turnCount = 0;
    
    #[Url(as: 'date')]
    public $userTimestamp = 0;

    public $nowTimestamp = 0;

    public $victory = false;

    public $inactive = false;

    public $histogram;

    public $leaderboard = [];

    private function either($a, $b) {
        return $a ? $a : $b;
    }

    public function generateGrid() {
        $this->grid = [
            [$this->randomLetter(), $this->randomLetter(), $this->randomLetter(), $this->randomLetter(), $this->randomLetter()],
            [$this->randomLetter(), $this->randomLetter(), $this->randomLetter(), $this->randomLetter(), $this->randomLetter()],
            [$this->randomLetter(), $this->randomLetter(), $this->randomLetter(), $this->randomLetter(), $this->randomLetter()],
            [$this->randomLetter(), $this->randomLetter(), $this->randomLetter(), $this->randomLetter(), $this->randomLetter()],
            [$this->randomLetter(), $this->randomLetter(), $this->randomLetter(), $this->randomLetter(), $this->randomLetter()],
        ];
    }

    private function getIdForThisGame() {
        return strval($this->either(Auth::id(), Session::getId()));
    }

    private function getSavedGame($userTimestamp) {
        return Game::where('userOrSessionId', $this->getIdForThisGame())->where('userTimestamp', $userTimestamp)->first();
    }

    public $gameId = 'not found';

    public function mount() {
        $this->gameId = $this->getIdForThisGame();
    }

    private function createSavedGame() {
        $game = new Game;
        $game->userOrSessionId = $this->getIdForThisGame();
        $game->target = $this->targetWord;
        $game->grid = json_encode($this->grid);
        $game->turnCount = 0;
        $game->userTimestamp = $this->userTimestamp;
        $game->save();
    }

    private function updateSavedGame($start, $end) {
        if ($this->inactive) {
            return;
        }
        // save the updated game state
        Game::where('userOrSessionId', $this->getIdForThisGame())
        ->where('userTimestamp', $this->userTimestamp)
        ->where('target', $this->targetWord)
        ->update([
                'grid' => json_encode($this->grid),
                'turnCount' => $this->turnCount,
                'victory' => $this->victory,
            ]);

        $game = Game::where('userOrSessionId', $this->getIdForThisGame())
            ->where('userTimestamp', $this->userTimestamp)
            ->where('target', $this->targetWord)
            ->first();

        // now save the specific turn
        $turn = new Turn;
        $turn->game_id = $game->id;
        $turn->grid = json_encode($this->grid);
        $turn->startRow = $start[0];
        $turn->startCol = $start[1];
        $turn->endRow = $end[0];
        $turn->endCol = $end[1];
        $turn->save();
    }

    private function getHistogram() {
        $rawHistogram = DB::select('select turnCount, count(*) as userCount
        from games
        where
          victory is true
          and userTimestamp = :userTimestamp
        group by turnCount
        order by turnCount asc;', ['userTimestamp' => $this->userTimestamp]);

        $maxTurnCount = 0;
        $maxUserCount = 0;

        if (count($rawHistogram) > 0) {
            $maxTurnCount = $rawHistogram[count($rawHistogram) - 1]->turnCount;
            for ($i=0; $i < count($rawHistogram); $i++) {
                if ($rawHistogram[$i]->userCount > $maxUserCount) {
                    $maxUserCount = $rawHistogram[$i]->userCount;
                }
            }
        }

        $this->histogram = [];
        if ($maxUserCount > 0) {
            $arrayPos = 0;
            for ($i=1; $i <= $maxTurnCount ; $i++) {
                if ($rawHistogram[$arrayPos]->turnCount === $i) {
                    array_push($this->histogram, (object) [
                        'turnCount' => $i,
                        'userCount' => $rawHistogram[$arrayPos]->userCount,
                        'userCountPercent' => $rawHistogram[$arrayPos]->userCount / $maxUserCount,
                    ]);
                    $arrayPos++;
                } else {
                    array_push($this->histogram, (object) [
                        'turnCount' => $i,
                        'userCount' => 0,
                        'userCountPercent' => 0,
                    ]);
                }
            }
        }
    }

    public $myFriendCode = '';

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

    private function getLeaderboard() {
        $friends = $this->getFriends();
        foreach ($friends as $f) {
            // convert our ID to a string, because if we supply a number MySQL provides results
            // that are not what we want. Eg, 1 matches '1pr012i03912i3' as well as '1'.
            $g = Game::where('userOrSessionId', strval($f->id))
                ->where('userTimestamp', $this->userTimestamp)
                ->where('target', $this->targetWord)
                ->first();
            if ($g) {
                $f->playing = true;
                $f->victory = $g->victory;
                $f->turnCount = $g->turnCount;
            } else {
                $f->playing = false;
            }
            $f->isMe = $f->id == Auth::id();
            if ($f->isMe) {
                $this->myFriendCode = $this->encodeFriendCode($f);
            }
        }

        usort($friends, function($a, $b) {
            if (!$a->playing) {
                return 1;
            }
            if (!$b->playing) {
                return -1;
            }
            if (!$a->victory) {
                return 1;
            }
            if (!$b->victory) {
                return -1;
            }
            return $a->turnCount - $b->turnCount;
        });
        $this->leaderboard = $friends;
    }

    private function encodeFriendCode($user) {
        return $user->id . '_' . $user->friendshipCode;
    }
    private function decodeFriendCode($code) {
        if (preg_match('/^\d+_[A-Z0-9]{8}$/', $code) != 1) {
            return [-1, 'badcode'];
        }
        [$id, $code] = explode('_', $code);
        return [$id, $code];
    }
    public $badCode = false;
    public function addFriend($code) {
        $myId = Auth::id();
        if (!$myId) {
            return;
        }
        // find friend based on the unique code
        [$friendId, $friendCode] = $this->decodeFriendCode($code);
        $friend = User::where('id', $friendId)->where('friendshipCode', $friendCode)->first();

        if (!$friend) {
            $this->badCode = true;
            return;
        }
        $this->badCode = false;
        if ($friend->id == $myId) {
            return;
        }

        // add them to the friendship database (always do lowest ID first, to avoid duplicates)
        DB::table('friendships')->upsert([
            'from_user_id' => $myId < $friendId ? $myId : $friendId,
            'to_user_id' => $myId < $friendId ? $friendId : $myId
        ], ['from_user_id', 'to_user_id'], ['from_user_id', 'to_user_id']);

        // rebuild the leaderboard
        $this->getLeaderboard();
    }

    // we use custom initialise, instead of mount, because we want the client to
    // send us the timezone offset first
    public function initialise($timezoneOffsetMinutes) {
        include 'WordList.php';

        // one random seed per day ensures everyone's playing the same game
        // comment it out for a different grid every time you reload
        if (abs($timezoneOffsetMinutes) > (24 * 60)) {
            $timezoneOffsetMinutes = 0;
        }
        $now = time() - ($timezoneOffsetMinutes * 60);
        $this->nowTimestamp = floor($now / 86400);
        if ($this->userTimestamp == 0 || $this->userTimestamp > $this->nowTimestamp) {
            $this->userTimestamp = $this->nowTimestamp;
        }
        if ($this->userTimestamp != $this->nowTimestamp) {
            $this->inactive = true;
        }

        $this->currentDate = date('j F Y', $this->userTimestamp * 86400 + ($timezoneOffsetMinutes * 60));

        $game = $this->getSavedGame($this->userTimestamp);
        if ($game) {
            $this->grid = json_decode($game->grid);
            $this->targetWord = $game->target;
            $this->turnCount = $game->turnCount;
            $this->victory = $game->victory;
            if ($this->victory || $this->inactive) {
                $this->getHistogram();
                $this->getLeaderboard();
            }
            return;
        }

        srand($this->userTimestamp);
        $this->generateGrid();
        $this->targetWord = "";
        $attempts = 0;
        while (strlen($this->targetWord) == 0 && $attempts <= 100) {
            $attempts++;
            $this->generateGrid();
            $gridLetters = array_merge(...$this->grid);
            $sortedGridLetters = join('', $gridLetters);
            $numberOfFiveLetterWords = count($fiveLetterWords);
            $offset = rand(0, $numberOfFiveLetterWords - 1);
            for ($i = 0; $i < $numberOfFiveLetterWords; $i++) {
                $candidate = $fiveLetterWords[($i + $offset) % $numberOfFiveLetterWords];
                if (substr_count($sortedGridLetters, $candidate[0]) >= substr_count($candidate, $candidate[0]) &&
                    substr_count($sortedGridLetters, $candidate[1]) >= substr_count($candidate, $candidate[1]) &&
                    substr_count($sortedGridLetters, $candidate[2]) >= substr_count($candidate, $candidate[2]) &&
                    substr_count($sortedGridLetters, $candidate[3]) >= substr_count($candidate, $candidate[3]) &&
                    substr_count($sortedGridLetters, $candidate[4]) >= substr_count($candidate, $candidate[4])
                ) {
                    $this->targetWord = strtoupper($candidate);
                    break;
                }
            }
        }
        if (strlen($this->targetWord) == 0) {
            $this->targetWord = "FAILED";
        }

        // save the current grid and target to the database
        $this->createSavedGame();
    }

    public function swap(&$x, &$y) {
        $tmp=$x;
        $x=$y;
        $y=$tmp;
    }

    public function rotate($start, $end, $length) {
        for ($i = 0; $i < ($length/2); $i++) {
            if ($start[0] < $end[0]) {
                $this->swap($this->grid[$end[0] - $i][$start[1]], $this->grid[$start[0] + $i][$start[1]]);
            } else if ($start[0] > $end[0]) {
                $this->swap($this->grid[$end[0] + $i][$start[1]], $this->grid[$start[0] - $i][$start[1]]);
            } else if ($start[1] < $end[1]) {
                $this->swap($this->grid[$start[0]][$end[1] - $i], $this->grid[$start[0]][$start[1] + $i]);
            } else if ($start[1] > $end[1]) {
                $this->swap($this->grid[$start[0]][$end[1] + $i], $this->grid[$start[0]][$start[1] - $i]);
            }
        }
    }

    public function getWord($start, $end) {
        $word = '';
        $r = $start[0];
        $c = $start[1];
        $length = max(abs($start[0] - $end[0]), abs($start[1] - $end[1])) + 1;
        for ($i = 0; $i < $length; $i++) {
            $word = $word . $this->grid[$r][$c];
            if ($r !== $end[0]) {
                if ($r < $end[0]) {
                    $r++;
                } else {
                    $r--;
                }
            }
            if ($c !== $end[1]) {
                if ($c < $end[1]) {
                    $c++;
                } else {
                    $c--;
                }
            }
        }
        return $word;
    }

    private function inVictoryState() {
        $f = function($a) { return implode($a); };
        $rows = array_map($f, $this->grid);
        $cols = array_map($f, array_map(null, ...$this->grid));
        return in_array($this->targetWord, [...$rows, ...$cols]);
    }

    public function submit($start, $end) {
        include 'WordList.php';

        if ($this->inactive || $this->victory) {
            return;
        }

        $word = $this->getWord($start, $end);

        if (in_array($word, $allWords) === true) {
            $this->rotate($start, $end, strlen($word));
            $this->turnCount++;
            $this->victory = $this->inVictoryState();
            $this->updateSavedGame($start, $end);
            if ($this->victory) {
                $this->getHistogram();
                $this->getLeaderboard();
            }
            // TODO: store the histogram of 5-letter, 4-letter, 3-letter, and 2-letter words, at least for local display
            return (object) [
                'valid' => true,
            ];
        } else {
            return (object) [
                'valid' => false,
                'word' => $word,
            ];
        }
    }


    public function render()
    {
        return view('livewire.spindle');
    }
}
