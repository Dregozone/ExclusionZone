<?php

namespace App\Http\Controllers;

use App\Actions\Game\AdvanceTravel;
use App\Actions\Game\AdvanceWorkLoop;
use App\Actions\Game\BuildCityMenuData;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function show(Request $request, BuildCityMenuData $buildCityMenuData, AdvanceWorkLoop $advanceWorkLoop, AdvanceTravel $advanceTravel): View
    {
        $advanceWorkLoop($request->user());
        $travel = $advanceTravel($request->user());

        if ($travel['last_status'] !== null) {
            $request->session()->now('status', $travel['last_status']);
        }

        return view('dashboard', $buildCityMenuData($request->user()));
    }
}
