<?php

namespace App\Livewire\Admin;

use App\Models\Skill;
use App\Models\User;
use App\Models\UserSkill;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Give Skill XP')]
class GiveSkillExperience extends Component
{
    public ?int $selectedUserId = null;

    public ?int $selectedSkillId = null;

    public ?int $experience = null;

    public function boot(): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);
    }

    #[Computed]
    public function users(): Collection
    {
        return User::query()
            ->orderBy('name')
            ->get(['id', 'name', 'email']);
    }

    #[Computed]
    public function skills(): Collection
    {
        return Skill::query()
            ->orderBy('display_name')
            ->get(['id', 'display_name']);
    }

    public function giveExperience(): void
    {
        $this->validate([
            'selectedUserId' => ['required', 'integer', 'exists:users,id'],
            'selectedSkillId' => ['required', 'integer', Rule::exists('skills', 'id')->withoutTrashed()],
            'experience' => ['required', 'integer', 'min:1', 'max:1000000'],
        ]);

        /** @var User $user */
        $user = User::query()->select(['id', 'name'])->find($this->selectedUserId);

        /** @var Skill $skill */
        $skill = Skill::query()->select(['id', 'display_name'])->find($this->selectedSkillId);

        $skillProgress = DB::transaction(function () use ($user, $skill): UserSkill {
            /** @var UserSkill $skillProgress */
            $skillProgress = $user->skills()
                ->lockForUpdate()
                ->firstOrCreate(['skill_id' => $skill->id], ['level' => 1, 'xp' => 0]);

            $skillProgress->gainExperience($this->experience);

            return $skillProgress;
        });

        Flux::toast(
            variant: 'success',
            text: "{$user->name} has been given {$this->experience} XP in {$skill->display_name} (now level {$skillProgress->level}).",
        );

        $this->reset(['selectedUserId', 'selectedSkillId', 'experience']);
    }
}
