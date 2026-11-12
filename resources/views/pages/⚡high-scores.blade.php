<?php

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Player High Scores')] class extends Component
{
    #[Computed]
    public function leaderboard(): Collection
    {
        return DB::table('skills')
            ->select([
                'skills.id',
                'skills.key',
                'skills.display_name',
                'skills.description',
                'top.level',
                'top.xp',
                'users.name as player_name',
            ])
            ->leftJoinSub(
                DB::table('user_skills as us')
                    ->select(['us.skill_id', 'us.user_id', 'us.level', 'us.xp'])
                    ->whereRaw('us.id = (SELECT id FROM user_skills WHERE skill_id = us.skill_id ORDER BY level DESC, xp DESC LIMIT 1)'),
                'top',
                'skills.id',
                'top.skill_id'
            )
            ->leftJoin('users', 'users.id', '=', 'top.user_id')
            ->whereNull('skills.deleted_at')
            ->orderBy('skills.display_name')
            ->get();
    }
};
?>

<div class="mx-auto w-full max-w-6xl">
    @php($ranked = $this->leaderboard->whereNotNull('level'))
    <div class="mb-8 grid gap-6 lg:grid-cols-[1.2fr_0.8fr] lg:items-end">
        <div>
            <p class="ez-label !text-ez-signal">Leaderboard · every skill in the zone</p>
            <flux:heading size="xl" class="ez-display mt-2 !text-5xl !font-normal sm:!text-7xl">High Scores</flux:heading>
            <flux:text class="mt-3 max-w-xl">The best player for each skill across the exclusion zone.</flux:text>
        </div>
        <dl class="grid grid-cols-2 border-y border-zinc-700">
            <div class="border-r border-zinc-700 py-4 pr-4">
                <dd class="ez-numeral text-7xl text-ez-ink">{{ $this->leaderboard->count() }}</dd>
                <dt class="ez-label mt-2">Skills tracked</dt>
            </div>
            <div class="py-4 pl-4">
                <dd class="ez-numeral text-7xl text-ez-signal">{{ $ranked->max('level') ?? '—' }}</dd>
                <dt class="ez-label mt-2">Highest level</dt>
            </div>
        </dl>
    </div>

    <div class="ez-panel overflow-hidden px-2 sm:px-4">
    <flux:table>
        <flux:table.columns>
            <flux:table.column>Skill</flux:table.column>
            <flux:table.column>Top Player</flux:table.column>
            <flux:table.column>Level</flux:table.column>
            <flux:table.column align="end">XP</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($this->leaderboard as $row)
                <flux:table.row :key="$row->id">
                    <flux:table.cell class="flex items-center gap-3">
                        <div>
                            <p class="font-medium">{{ $row->display_name }}</p>
                            <p class="max-w-xs truncate text-xs text-zinc-400">{{ $row->description }}</p>
                        </div>
                    </flux:table.cell>

                    <flux:table.cell>
                        @if ($row->player_name)
                            {{ $row->player_name }}
                        @else
                            <span class="italic text-zinc-500">—</span>
                        @endif
                    </flux:table.cell>

                    <flux:table.cell>
                        @if ($row->level !== null)
                            <span class="ez-numeral text-3xl text-ez-signal">{{ $row->level }}</span>
                        @else
                            <span class="text-zinc-500">—</span>
                        @endif
                    </flux:table.cell>

                    <flux:table.cell class="text-right">
                        @if ($row->xp !== null)
                            <span class="font-mono tabular-nums text-ez-ink">{{ number_format($row->xp) }}</span>
                        @else
                            <span class="text-zinc-500">—</span>
                        @endif
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>
    </div>
</div>
