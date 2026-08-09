<?php

namespace App\Http\Controllers;

use App\Http\Requests\PlayerImportRequest;
use App\Models\Player;
use App\Services\PlayerQuotationsImporter;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PlayerController extends Controller
{
    /**
     * Show the players list.
     */
    public function index(): Response
    {
        return Inertia::render('players/index', [
            'players' => Player::query()->with('team')->orderBy('name')->get(),
        ]);
    }

    /**
     * Import teams and players from an uploaded quotazioni xlsx file.
     */
    public function import(PlayerImportRequest $request, PlayerQuotationsImporter $importer): RedirectResponse
    {
        $result = $importer->import($request->file('file'));

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __(':created created, :updated updated, :deleted removed.', [
                'created' => $result['players_created'],
                'updated' => $result['players_updated'],
                'deleted' => $result['players_deleted'],
            ]),
        ]);

        return to_route('players.index');
    }
}
