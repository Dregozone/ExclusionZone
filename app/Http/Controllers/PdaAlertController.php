<?php

namespace App\Http\Controllers;

use App\Actions\Game\TrackPdaAlerts;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * The PDA bell asks here every so often when live broadcasting is off, so new radio messages,
 * trade changes and jobs still show without reloading the page.
 */
class PdaAlertController extends Controller
{
    public function index(Request $request, TrackPdaAlerts $trackPdaAlerts): JsonResponse
    {
        return response()->json($trackPdaAlerts->counts($request->user()));
    }

    /**
     * The survivor opened a PDA tab, so everything on it so far is seen.
     */
    public function markSeen(Request $request, string $tab, TrackPdaAlerts $trackPdaAlerts): Response
    {
        $trackPdaAlerts->markSeen($request->user(), $tab);

        return response()->noContent();
    }
}
