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

<div>
    <div class="mb-6">
        <flux:heading size="xl">High Scores</flux:heading>
        <flux:text class="mt-1">The best player for each skill across the exclusion zone.</flux:text>
    </div>

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
                        <div class="size-8 shrink-0 rounded bg-zinc-700 ring-1 ring-white/10"></div>
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
                            <flux:badge size="sm" color="emerald">{{ $row->level }}</flux:badge>
                        @else
                            <span class="text-zinc-500">—</span>
                        @endif
                    </flux:table.cell>

                    <flux:table.cell class="text-right">
                        @if ($row->xp !== null)
                            <span class="font-medium tabular-nums">{{ number_format($row->xp) }}</span>
                        @else
                            <span class="text-zinc-500">—</span>
                        @endif
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>
</div>
