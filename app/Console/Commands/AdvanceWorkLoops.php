<?php

namespace App\Console\Commands;

use App\Actions\Game\AdvanceWorkLoop;
use App\Models\UserWork;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('work:advance-loops')]
#[Description('Pay out finished work cycles for every player, including those who are away')]
class AdvanceWorkLoops extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(AdvanceWorkLoop $advanceWorkLoop): int
    {
        $playerCount = 0;

        UserWork::query()
            ->with('user')
            ->where('work_type', 'city_action')
            ->where('available_at', '<=', now())
            ->each(function (UserWork $work) use ($advanceWorkLoop, &$playerCount): void {
                rescue(fn () => $advanceWorkLoop($work->user));
                $playerCount++;
            });

        $this->info("Work loops advanced for {$playerCount} players.");

        return self::SUCCESS;
    }
}
