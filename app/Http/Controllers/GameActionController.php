<?php

namespace App\Http\Controllers;

use App\Actions\Game\CancelUserWork;
use App\Actions\Game\CompleteUserWork;
use App\Actions\Game\EquipPremiumCosmetic;
use App\Actions\Game\StartUserWork;
use App\Http\Requests\EquipCosmeticRequest;
use App\Http\Requests\FeedBrainScorcherRequest;
use App\Http\Requests\PerformCityActionRequest;
use App\Http\Requests\TravelRequest;
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
            $work = $startUserWork->forTravel($request->user(), City::query()->findOrFail($request->integer('city_id')));
        } catch (AuthorizationException $exception) {
            return back()->with('toast', $this->dangerToast($exception->getMessage()));
        }

        return to_route('dashboard')->with('status', 'Travel started. Arrive in '.max(10, $work->duration_seconds).' seconds or cancel the route.');
    }

    public function performAction(PerformCityActionRequest $request, StartUserWork $startUserWork): RedirectResponse
    {
        try {
            $this->authorizeTask($request, 'city_action_perform');
            $work = $startUserWork->forCityAction($request->user(), CityAction::query()->findOrFail($request->integer('city_action_id')));
        } catch (AuthorizationException $exception) {
            return back()->with('toast', $this->dangerToast($exception->getMessage()));
        }

        return to_route('dashboard')->with('status', 'Work started. Return when the timer ends in '.max(10, $work->duration_seconds).' seconds.');
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
            $work->isCityAction() => 'Work canceled. No rewards were granted.',
            $work->isBrainScorcher() => 'Offering withdrawn. Your items are still in your pack.',
            default => 'Travel canceled. You stayed in your current city.',
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
