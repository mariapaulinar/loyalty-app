<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 */

namespace App\Http\Controllers\Data;

use App\Http\Controllers\Controller;
use App\Services\Data\DataService;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ExportController extends Controller
{
    /**
     * Export the list of items for the given data definition to a CSV.
     *
     * @param  string  $locale  The current locale.
     * @param  string  $dataDefinitionName  The name of the data definition to retrieve.
     * @param  Request  $request  The incoming HTTP request.
     * @param  DataService  $dataService  The data service to fetch the data definition.
     * @return \Illuminate\View\View|\Illuminate\Contracts\View\Factory
     *
     * @throws \Exception If the data definition is not found.
     */
    public function exportList(string $locale, string $dataDefinitionName, Request $request, DataService $dataService)
    {
        // Find the data definition by name and instantiate it if it exists
        $dataDefinition = $dataService->findDataDefinitionByName($dataDefinitionName);
        if ($dataDefinition === null) {
            throw new \Exception('Data definition "'.$dataDefinitionName.'" not found');
        }
        $dataDefinition = new $dataDefinition;

        // Get settings and data for the data definition
        $settings = $dataDefinition->getSettings([]);
        $tableData = $dataDefinition->getData($dataDefinition->name, 'export');

        // Obtain the user type from the route name (member, staff, partner, or admin)
        // and verify if the Data Definition is permitted for that user type
        $guard = explode('.', $request->route()->getName())[0];
        if ($settings['guard'] !== $guard) {
            Log::notice('app\Http\Controllers\Data\ExportController.php - View not allowed for '.$guard.', '.$settings['guard'].' required ('.auth($settings['guard'])->user()->email.')');
            abort(404);
        }

        $format = strtolower((string) $request->query('format', 'csv'));
        $format = in_array($format, ['csv', 'tsv', 'json'], true) ? $format : 'csv';

        $extension = match ($format) {
            'json' => 'json',
            'tsv' => 'tsv',
            default => 'csv',
        };

        // Generate file name
        $fileName = Str::slug($settings['title'].' '.date('Y-m-d H:i'), '-').'.'.$extension;

        if ($format === 'json') {
            $meta = [
                'exported_at' => now()->toIso8601String(),
                'title' => $settings['title'],
                'total' => $tableData['data']->count(),
            ];

            // Merge DD-provided metadata (e.g., truncation info from admin aggregate exports)
            if (isset($tableData['meta']) && is_array($tableData['meta'])) {
                $meta = array_merge($meta, $tableData['meta']);
            }

            return response()
                ->json([
                    'meta' => $meta,
                    'data' => $tableData['data']->values(),
                ])
                ->withHeaders([
                    'Content-Disposition' => "attachment; filename=$fileName",
                ]);
        }

        // Extract CSV column names
        $columns = $tableData['columns'];
        $csvColumns = array_map(
            fn (string $text): string => trim(strip_tags(html_entity_decode($text))),
            Arr::pluck($columns, 'text')
        );

        $delimiter = $format === 'tsv' ? "\t" : ',';

        // Prepare headers for the file
        $headers = [
            'Content-type' => $format === 'tsv' ? 'text/tab-separated-values' : 'text/csv',
            'Content-Disposition' => "attachment; filename=$fileName",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        // Define CSV content generation callback
        $callback = function () use ($tableData, $csvColumns, $columns, $delimiter) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $csvColumns, $delimiter);

            foreach ($tableData['data'] as $record) {
                $row = [];

                foreach ($columns as $column) {
                    $value = $record[$column['name']] ?? null;
                    $row[] = trim(strip_tags(html_entity_decode((string) $value)));
                }

                fputcsv($file, $row, $delimiter);
            }

            // Surface truncation as a visible footer row (not silent)
            if (isset($tableData['meta']['truncated']) && $tableData['meta']['truncated']) {
                $footerNote = '--- Export limited to '
                    . number_format($tableData['meta']['export_limit'])
                    . ' rows. '
                    . number_format($tableData['meta']['total_available'])
                    . ' total records available. ---';
                fputcsv($file, [$footerNote], $delimiter);
            }

            fclose($file);
        };

        // Stream the response as a CSV file
        return response()->stream($callback, 200, $headers);
    }
}
