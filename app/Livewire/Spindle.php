<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;
use App\Models\Game;


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

    public $userTimestamp = 0;

    public $victory = false;

    public $leaderboard;

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

    private function updateSavedGame() {
        Game::where('userOrSessionId', $this->getIdForThisGame())
        ->where('userTimestamp', $this->userTimestamp)
        ->where('target', $this->targetWord)
        ->update([
                'grid' => json_encode($this->grid),
                'turnCount' => $this->turnCount,
                'victory' => $this->victory,
            ]);
    }

    private function getLeaderboard() {
        $rawLeaderboard = DB::select('select turnCount, count(*) as userCount
        from games
        where
          victory is true
          and userTimestamp = :userTimestamp
        group by turnCount
        order by turnCount asc;', ['userTimestamp' => $this->userTimestamp]);

        $maxTurnCount = $rawLeaderboard[count($rawLeaderboard) - 1]->turnCount;
        $maxUserCount = 0;
        for ($i=0; $i < count($rawLeaderboard); $i++) {
            if ($rawLeaderboard[$i]->userCount > $maxUserCount) {
                $maxUserCount = $rawLeaderboard[$i]->userCount;
            }
        }

        $this->leaderboard = [];
        $arrayPos = 0;
        for ($i=1; $i <= $maxTurnCount ; $i++) {
            if ($rawLeaderboard[$arrayPos]->turnCount === $i) {
                array_push($this->leaderboard, (object) [
                    'turnCount' => $i,
                    'userCount' => $rawLeaderboard[$arrayPos]->userCount,
                    'userCountPercent' => $rawLeaderboard[$arrayPos]->userCount / $maxUserCount,
                ]);
                $arrayPos++;
            } else {
                array_push($this->leaderboard, (object) [
                    'turnCount' => $i,
                    'userCount' => 0,
                    'userCountPercent' => 0,
                ]);
            }
        }
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
        $this->currentDate = date('j F Y', $now);
        $userTimestamp = floor($now / 86400);
        $this->userTimestamp = $userTimestamp;

        $game = $this->getSavedGame($userTimestamp);
        if ($game) {
            $this->grid = json_decode($game->grid);
            $this->targetWord = $game->target;
            $this->turnCount = $game->turnCount;
            $this->victory = $game->victory;
            if ($this->victory) {
                $this->getLeaderboard();
            }
            return;
        }

        srand($userTimestamp);
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

        $word = $this->getWord($start, $end);

        if (in_array($word, $allWords) === true) {
            $this->rotate($start, $end, strlen($word));
            $this->turnCount++;
            $this->victory = $this->inVictoryState();
            $this->updateSavedGame();
            if ($this->victory) {
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
                'wordListLength' => count($allWords),
            ];
        }
    }


    public function render()
    {
        return view('livewire.spindle');
    }
}
