<?php

namespace App\Http\Controllers;

use App\Actions\Game\EquipPremiumCosmetic;
use App\Actions\Game\PerformCityAction;
use App\Actions\Game\TravelToCity;
use App\Http\Requests\EquipCosmeticRequest;
use App\Http\Requests\PerformCityActionRequest;
use App\Http\Requests\TravelRequest;
use App\Models\City;
use App\Models\CityAction;
use App\Models\PremiumCosmetic;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class GameActionController extends Controller
{
    public function travel(TravelRequest $request, TravelToCity $travelToCity): RedirectResponse
    {
        try {
            $this->authorizeTask($request, 'city_action_perform');
            $travelToCity($request->user(), City::query()->findOrFail($request->integer('city_id')));
        } catch (AuthorizationException $exception) {
            return back()->with('toast', $this->dangerToast($exception->getMessage()));
        }

        return to_route('dashboard')->with('status', 'You moved to a new city and refreshed your options.');
    }

    public function performAction(PerformCityActionRequest $request, PerformCityAction $performCityAction): RedirectResponse
    {
        try {
            $this->authorizeTask($request, 'city_action_perform');
            $reward = $performCityAction($request->user(), CityAction::query()->findOrFail($request->integer('city_action_id')));
        } catch (AuthorizationException $exception) {
            return back()->with('toast', $this->dangerToast($exception->getMessage()));
        }

        $summary = "Action completed: +{$reward['xp']} XP";

        if ($reward['item_name'] !== null) {
            $summary .= ", +{$reward['quantity']} {$reward['item_name']}";
        }

        if ($reward['levels_gained'] > 0) {
            $summary .= ", {$reward['levels_gained']} level gained";
        }

        return to_route('dashboard')->with('status', $summary.'.');
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
            'chat' => 'The radio crackles with survivor chatter. Full live chat is the next milestone.',
            'trade' => 'Merchants are posting prices. The trade board hook is online for the next iteration.',
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
