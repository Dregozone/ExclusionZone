<?php

namespace App\Http\Controllers;

use App\Actions\Game\BuildCityMenuData;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function show(Request $request, BuildCityMenuData $buildCityMenuData): View
    {
        return view('dashboard', $buildCityMenuData($request->user()));
    }
}
