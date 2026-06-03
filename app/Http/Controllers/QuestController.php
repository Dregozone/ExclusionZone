<?php

namespace App\Http\Controllers;

use App\Actions\Game\AcceptQuest;
use App\Actions\Game\InteractWithQuestStep;
use App\Models\Quest;
use App\Models\QuestStep;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class QuestController extends Controller
{
    public function accept(Request $request, Quest $quest, AcceptQuest $acceptQuest): RedirectResponse
    {
        try {
            $acceptQuest($request->user(), $quest);
        } catch (AuthorizationException $exception) {
            return back()->with('toast', [
                'heading' => 'Quest unavailable',
                'text' => $exception->getMessage(),
                'variant' => 'danger',
            ]);
        }

        return to_route('dashboard')->with('status', 'Quest accepted: '.$quest->name.'. Check the Jobs tab in your PDA for details.');
    }

    public function interact(Request $request, QuestStep $questStep, InteractWithQuestStep $interactWithQuestStep): RedirectResponse
    {
        try {
            $result = $interactWithQuestStep($request->user(), $questStep);
        } catch (AuthorizationException $exception) {
            return back()->with('toast', [
                'heading' => 'Action unavailable',
                'text' => $exception->getMessage(),
                'variant' => 'danger',
            ]);
        }

        return to_route('dashboard')->with('status', $result['message']);
    }
}
