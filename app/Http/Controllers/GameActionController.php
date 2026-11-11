<?php

namespace App\Http\Controllers;

use App\Actions\Game\CancelUserWork;
use App\Actions\Game\CompleteUserWork;
use App\Actions\Game\DealWithTrouble;
use App\Actions\Game\EatFood;
use App\Actions\Game\EquipPremiumCosmetic;
use App\Actions\Game\StartUserWork;
use App\Actions\Game\UseMedicalItem;
use App\Http\Requests\BuyWayPastTroubleRequest;
use App\Http\Requests\EatFoodRequest;
use App\Http\Requests\EquipCosmeticRequest;
use App\Http\Requests\FeedBrainScorcherRequest;
use App\Http\Requests\PerformCityActionRequest;
use App\Http\Requests\TravelRequest;
use App\Http\Requests\UseMedicalItemRequest;
use App\Models\City;
use App\Models\CityAction;
use App\Models\Item;
use App\Models\PremiumCosmetic;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class GameActionController extends Controller
{
    public function travel(TravelRequest $request, StartUserWork $startUserWork): RedirectResponse
    {
        try {
            $this->authorizeTask($request, 'city_action_perform');
            $destination = City::query()->findOrFail($request->integer('city_id'));
            $work = $startUserWork->forTravel(
                $request->user(),
                $destination,
                array_map('intval', $request->array('via')),
            );
        } catch (AuthorizationException $exception) {
            return back()->with('toast', $this->dangerToast($exception->getMessage()));
        }

        $arrivalSeconds = max(10, $work->duration_seconds);

        return to_route('dashboard')->with('status', $work->queuedCityIds() === []
            ? "Travel started. Arrive in {$arrivalSeconds} seconds or cancel the route."
            : "Travel started for {$destination->city}. Reach {$work->destinationCity?->city} in {$arrivalSeconds} seconds, then the next leg starts on its own. Cancel at any time to stay where you are.");
    }

    public function performAction(PerformCityActionRequest $request, StartUserWork $startUserWork): RedirectResponse
    {
        try {
            $this->authorizeTask($request, 'city_action_perform');
            $work = $startUserWork->forCityAction($request->user(), CityAction::query()->findOrFail($request->integer('city_action_id')));
        } catch (AuthorizationException $exception) {
            return back()->with('toast', $this->dangerToast($exception->getMessage()));
        }

        return to_route('dashboard')->with('status', 'Work started. It pays out every '.max(10, $work->duration_seconds).' seconds and repeats until you stop it.');
    }

    public function feedBrainScorcher(FeedBrainScorcherRequest $request, StartUserWork $startUserWork): RedirectResponse
    {
        try {
            $this->authorizeTask($request, 'city_action_perform');
            $work = $startUserWork->forBrainScorcher(
                $request->user(),
                Item::query()->findOrFail($request->integer('item_id')),
                $request->integer('quantity'),
            );
        } catch (AuthorizationException $exception) {
            return back()->with('toast', $this->dangerToast($exception->getMessage()));
        }

        return to_route('dashboard')->with('status', 'The Brain Scorcher accepted your offering. See what it gives back in '.max(10, $work->duration_seconds).' seconds, or cancel to take your items back.');
    }

    public function eat(EatFoodRequest $request, EatFood $eatFood): RedirectResponse
    {
        $item = Item::query()->findOrFail($request->integer('item_id'));

        try {
            $relieved = $eatFood($request->user(), $item);
        } catch (AuthorizationException $exception) {
            return back()->with('toast', $this->dangerToast($exception->getMessage()));
        }

        $effects = collect([
            'health +'.$relieved['health_restored'] => $relieved['health_restored'],
            'hunger -'.$relieved['hunger_relieved'] => $relieved['hunger_relieved'],
            'fatigue -'.$relieved['fatigue_relieved'] => $relieved['fatigue_relieved'],
        ])->filter()->keys()->implode(', ');

        return to_route('dashboard')->with('status', "You ate 1x {$item->name}".($effects === '' ? '.' : " ({$effects})."));
    }

    public function useMedicalItem(UseMedicalItemRequest $request, UseMedicalItem $useMedicalItem): RedirectResponse
    {
        $item = Item::query()->findOrFail($request->integer('item_id'));

        try {
            $healthRestored = $useMedicalItem($request->user(), $item);
        } catch (AuthorizationException $exception) {
            return back()->with('toast', $this->dangerToast($exception->getMessage()));
        }

        return to_route('dashboard')->with('status', "You used 1x {$item->name} (health +{$healthRestored}).");
    }

    public function attackTrouble(Request $request, DealWithTrouble $dealWithTrouble): RedirectResponse
    {
        try {
            $fight = $dealWithTrouble->attack($request->user());
        } catch (AuthorizationException $exception) {
            return back()->with('toast', $this->dangerToast($exception->getMessage()));
        }

        return to_route('dashboard')->with('status', $fight['status']);
    }

    public function buyWayPastTrouble(BuyWayPastTroubleRequest $request, DealWithTrouble $dealWithTrouble): RedirectResponse
    {
        try {
            $status = $dealWithTrouble->buyWayIn($request->user(), $request->array('items'));
        } catch (AuthorizationException $exception) {
            return back()->with('toast', $this->dangerToast($exception->getMessage()));
        }

        return to_route('dashboard')->with('status', $status);
    }

    public function runFromTrouble(Request $request, DealWithTrouble $dealWithTrouble): RedirectResponse
    {
        try {
            $origin = $dealWithTrouble->runAway($request->user());
        } catch (AuthorizationException $exception) {
            return back()->with('toast', $this->dangerToast($exception->getMessage()));
        }

        return to_route('dashboard')->with('status', "You turned and ran for {$origin->city}. There is no turning back until you get there.");
    }

    /**
     * The survivor has read how they were rescued after dying.
     */
    public function dismissDeathReport(Request $request): RedirectResponse
    {
        $request->user()->forceFill(['death_report' => null])->save();

        return to_route('dashboard');
    }

    public function completeWork(Request $request, CompleteUserWork $completeUserWork): RedirectResponse
    {
        try {
            $result = $completeUserWork($request->user());
        } catch (AuthorizationException $exception) {
            return back()->with('toast', $this->dangerToast($exception->getMessage()));
        }

        return to_route('dashboard')->with('status', $result['status']);
    }

    public function cancelWork(Request $request, CancelUserWork $cancelUserWork): RedirectResponse
    {
        try {
            $work = $cancelUserWork($request->user());
        } catch (AuthorizationException $exception) {
            return back()->with('toast', $this->dangerToast($exception->getMessage()));
        }

        $message = match (true) {
            $work->isCityAction() && $work->cycles_completed > 0 => "Work stopped after {$work->cycles_completed} ".str('cycle')->plural($work->cycles_completed)." (+{$work->xp_earned} XP). The unfinished cycle earned nothing.",
            $work->isCityAction() => 'Work canceled. No rewards were granted.',
            $work->isBrainScorcher() => 'Offering withdrawn. Your items are still in your pack.',
            default => 'Travel canceled. You stayed in '.($work->originCity?->city ?? 'your current city').'.',
        };

        return to_route('dashboard')->with('status', $message);
    }

    public function equipCosmetic(EquipCosmeticRequest $request, EquipPremiumCosmetic $equipPremiumCosmetic): RedirectResponse
    {
        try {
            $equipPremiumCosmetic($request->user(), PremiumCosmetic::query()->findOrFail($request->integer('premium_cosmetic_id')));
        } catch (AuthorizationException $exception) {
            return back()->with('toast', $this->dangerToast($exception->getMessage()));
        }

        return to_route('dashboard')->with('status', 'Cosmetic equipped. Style updated with no gameplay advantage.');
    }

    public function visitHook(Request $request, string $feature): RedirectResponse
    {
        $taskKey = match ($feature) {
            'chat' => 'chat_send',
            'trade' => 'trade_create',
            'combat' => 'combat_initiate',
            default => abort(404),
        };

        try {
            $this->authorizeTask($request, $taskKey);
        } catch (AuthorizationException $exception) {
            return back()->with('toast', $this->dangerToast($exception->getMessage()));
        }

        $message = match ($feature) {
            'chat' => 'The radio crackles with survivor chatter. Open Radio Comms on your PDA to join channel 7.',
            'trade' => 'Merchants are posting prices. Open the Trade tab on your PDA at a trade post to barter with other survivors.',
            'combat' => 'Scouts marked combat hotspots. Contracted fights are wired in as the next gameplay layer.',
        };

        return to_route('dashboard')->with('status', $message);
    }

    /**
     * @return array{heading:string,text:string,variant:string}
     */
    private function dangerToast(string $message): array
    {
        return [
            'heading' => 'Action unavailable',
            'text' => $message,
            'variant' => 'danger',
        ];
    }

    private function authorizeTask(Request $request, string $taskKey): void
    {
        $message = $request->user()?->denialReasonForTask($taskKey);

        if ($message !== null) {
            throw new AuthorizationException($message);
        }
    }
}
