<?php

namespace App\Filament\Support;

use Symfony\Component\HttpFoundation\StreamedResponse;

class Csv
{
    /** Streamt rijen als CSV (puntkomma, met BOM zodat Excel de tekens goed toont). */
    public static function download(string $filename, array $header, iterable $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($header, $rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, $header, ';', '"', '');
            foreach ($rows as $row) {
                fputcsv($out, $row, ';', '"', '');
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
