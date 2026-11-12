<?php

namespace App\Http\Controllers;

use App\Http\Requests\ListMatchesRequest;
use App\Models\ProjectMatch;
use App\Services\Matches\MatchCsvExport;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The matches list, with its current filters, as a CSV download.
 */
class MatchExportController extends Controller
{
    public function __invoke(ListMatchesRequest $request, MatchCsvExport $export): StreamedResponse
    {
        $matches = ProjectMatch::query()->filteredBy($request->filters());

        return response()->streamDownload(
            function () use ($export, $matches): void {
                $output = fopen('php://output', 'w');
                $export->write($matches, $output);
                fclose($output);
            },
            'matches-'.now()->format('Ymd-His').'.csv',
            ['Content-Type' => 'text/csv; charset=UTF-8'],
        );
    }
}
