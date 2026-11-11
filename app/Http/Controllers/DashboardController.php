<?php

namespace App\Http\Controllers;

use App\Actions\Game\AdvanceTravel;
use App\Actions\Game\AdvanceWorkLoop;
use App\Actions\Game\BuildCityMenuData;
use App\Actions\Game\TrackSurvivalNeeds;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function show(Request $request, BuildCityMenuData $buildCityMenuData, AdvanceWorkLoop $advanceWorkLoop, AdvanceTravel $advanceTravel, TrackSurvivalNeeds $trackSurvivalNeeds): View
    {
        $advanceWorkLoop($request->user());
        $travel = $advanceTravel($request->user());

        if ($travel['last_status'] !== null) {
            $request->session()->now('status', $travel['last_status']);
        }

        $trackSurvivalNeeds->rest($request->user());

        return view('dashboard', $buildCityMenuData($request->user()));
    }
}
