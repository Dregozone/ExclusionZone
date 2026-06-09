<?php

namespace App\Livewire\Admin;

use App\Models\City;
use App\Models\Item;
use App\Models\Quest;
use App\Models\QuestStep;
use App\Models\Skill;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Manage Quests')]
class ManageQuests extends Component
{
    public bool $showForm = false;

    public bool $showTrashed = false;

    public ?int $editingId = null;

    // Quest form fields
    public string $name = '';

    public string $description = '';

    public ?int $reward_item_id = null;

    public int $reward_item_quantity = 1;

    public ?int $reward_skill_id = null;

    public ?int $reward_xp_amount = null;

    public bool $is_active = true;

    public string $quest_type = 'job';

    public bool $is_repeatable = false;

    public ?int $sequence_order = null;

    public ?int $prerequisite_quest_id = null;

    /**
     * @var array<int, array{id: ?int, step_order: int, city_id: ?int, person_of_interest: string, action_label: string, interaction_text: string, required_item_id: ?int, required_item_quantity: int, consumes_item: bool, requirement_variants_json: string}>
     */
    public array $steps = [];

    public function boot(): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);
    }

    #[Computed]
    public function quests(): Collection
    {
        $query = Quest::query()
            ->withCount('steps')
            ->orderBy('quest_type')
            ->orderBy('sequence_order')
            ->orderBy('name');

        if ($this->showTrashed) {
            $query->withTrashed();
        }

        return $query->get(['id', 'name', 'description', 'quest_type', 'is_repeatable', 'sequence_order', 'is_active', 'deleted_at']);
    }

    #[Computed]
    public function cities(): Collection
    {
        return City::query()->orderBy('city')->get(['id', 'city']);
    }

    #[Computed]
    public function skills(): Collection
    {
        return Skill::query()->orderBy('display_name')->get(['id', 'display_name']);
    }

    #[Computed]
    public function items(): Collection
    {
        return Item::query()->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function storyQuests(): Collection
    {
        return Quest::query()
            ->where('quest_type', 'story')
            ->orderBy('sequence_order')
            ->get(['id', 'name']);
    }

    public function create(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $quest = Quest::query()->with('steps')->findOrFail($id);

        $this->editingId = $id;
        $this->name = $quest->name;
        $this->description = $quest->description;
        $this->reward_item_id = $quest->reward_item_id;
        $this->reward_item_quantity = $quest->reward_item_quantity;
        $this->reward_skill_id = $quest->reward_skill_id;
        $this->reward_xp_amount = $quest->reward_xp_amount;
        $this->is_active = $quest->is_active;
        $this->quest_type = $quest->quest_type ?? 'job';
        $this->is_repeatable = $quest->is_repeatable;
        $this->sequence_order = $quest->sequence_order;
        $this->prerequisite_quest_id = $quest->prerequisite_quest_id;

        $this->steps = $quest->steps->map(fn (QuestStep $step): array => [
            'id' => $step->id,
            'step_order' => $step->step_order,
            'city_id' => $step->city_id,
            'person_of_interest' => $step->person_of_interest,
            'action_label' => $step->action_label,
            'interaction_text' => $step->interaction_text,
            'required_item_id' => $step->required_item_id,
            'required_item_quantity' => $step->required_item_quantity,
            'consumes_item' => $step->consumes_item,
            'requirement_variants_json' => $step->requirement_variants !== null
                ? json_encode($step->requirement_variants, JSON_PRETTY_PRINT)
                : '',
        ])->values()->all();

        $this->showForm = true;
    }

    public function addStep(): void
    {
        $this->steps[] = [
            'id' => null,
            'step_order' => count($this->steps),
            'city_id' => null,
            'person_of_interest' => '',
            'action_label' => '',
            'interaction_text' => '',
            'required_item_id' => null,
            'required_item_quantity' => 1,
            'consumes_item' => false,
            'requirement_variants_json' => '',
        ];
    }

    public function removeStep(int $index): void
    {
        array_splice($this->steps, $index, 1);

        foreach ($this->steps as $i => &$step) {
            $step['step_order'] = $i;
        }
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:2000'],
            'reward_item_id' => ['nullable', 'integer', 'exists:items,id'],
            'reward_item_quantity' => ['required', 'integer', 'min:1'],
            'reward_skill_id' => ['nullable', 'integer', 'exists:skills,id'],
            'reward_xp_amount' => ['nullable', 'integer', 'min:1'],
            'is_active' => ['required', 'boolean'],
            'quest_type' => ['required', 'string', 'in:story,job'],
            'is_repeatable' => ['required', 'boolean'],
            'sequence_order' => ['nullable', 'integer', 'min:1'],
            'prerequisite_quest_id' => ['nullable', 'integer', 'exists:quests,id'],
            'steps' => ['array'],
            'steps.*.city_id' => ['required', 'integer', 'exists:cities,id'],
            'steps.*.person_of_interest' => ['required', 'string', 'max:255'],
            'steps.*.action_label' => ['required', 'string', 'max:255'],
            'steps.*.interaction_text' => ['required', 'string', 'max:2000'],
            'steps.*.required_item_id' => ['nullable', 'integer', 'exists:items,id'],
            'steps.*.required_item_quantity' => ['required', 'integer', 'min:1'],
            'steps.*.consumes_item' => ['required', 'boolean'],
            'steps.*.requirement_variants_json' => ['nullable', 'string'],
        ]);

        DB::transaction(function () use ($validated): void {
            $questData = [
                'name' => $validated['name'],
                'description' => $validated['description'],
                'reward_item_id' => $validated['reward_item_id'],
                'reward_item_quantity' => $validated['reward_item_quantity'],
                'reward_skill_id' => $validated['reward_skill_id'],
                'reward_xp_amount' => $validated['reward_xp_amount'],
                'is_active' => $validated['is_active'],
                'quest_type' => $validated['quest_type'],
                'is_repeatable' => $validated['is_repeatable'],
                'sequence_order' => $validated['sequence_order'],
                'prerequisite_quest_id' => $validated['prerequisite_quest_id'],
            ];

            if ($this->editingId !== null) {
                $quest = Quest::query()->findOrFail($this->editingId);
                $quest->update($questData);
                Flux::toast(variant: 'success', text: __('Quest updated.'));
            } else {
                $quest = Quest::query()->create($questData);
                Flux::toast(variant: 'success', text: __('Quest created.'));
            }

            $incomingIds = collect($this->steps)->pluck('id')->filter()->values();
            $quest->steps()->whereNotIn('id', $incomingIds)->delete();

            foreach ($this->steps as $index => $stepData) {
                $variants = null;
                $variantsJson = trim($stepData['requirement_variants_json'] ?? '');
                if ($variantsJson !== '') {
                    $decoded = json_decode($variantsJson, true);
                    if (is_array($decoded)) {
                        $variants = $decoded;
                    }
                }

                $stepPayload = [
                    'step_order' => $index,
                    'city_id' => $stepData['city_id'],
                    'person_of_interest' => $stepData['person_of_interest'],
                    'action_label' => $stepData['action_label'],
                    'interaction_text' => $stepData['interaction_text'],
                    'required_item_id' => $stepData['required_item_id'] ?: null,
                    'required_item_quantity' => $stepData['required_item_quantity'],
                    'consumes_item' => $stepData['consumes_item'],
                    'requirement_variants' => $variants,
                ];

                if ($stepData['id'] !== null) {
                    QuestStep::query()->where('id', $stepData['id'])->update($stepPayload);
                } else {
                    $quest->steps()->create($stepPayload);
                }
            }
        });

        $this->showForm = false;
        $this->resetForm();
        unset($this->quests);
    }

    public function delete(int $id): void
    {
        Quest::query()->findOrFail($id)->delete();
        Flux::toast(variant: 'success', text: __('Quest deleted.'));
        unset($this->quests);
    }

    public function restore(int $id): void
    {
        Quest::withTrashed()->findOrFail($id)->restore();
        Flux::toast(variant: 'success', text: __('Quest restored.'));
        unset($this->quests);
    }

    public function cancel(): void
    {
        $this->showForm = false;
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->name = '';
        $this->description = '';
        $this->reward_item_id = null;
        $this->reward_item_quantity = 1;
        $this->reward_skill_id = null;
        $this->reward_xp_amount = null;
        $this->is_active = true;
        $this->quest_type = 'job';
        $this->is_repeatable = false;
        $this->sequence_order = null;
        $this->prerequisite_quest_id = null;
        $this->steps = [];
    }
}
