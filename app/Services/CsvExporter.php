<?php

namespace App\Services;

use Symfony\Component\HttpFoundation\StreamedResponse;

/** Exporta CSV con BOM UTF-8 para que Excel muestre correctamente los acentos. */
class CsvExporter
{
    /**
     * @param  list<string>  $headers
     * @param  iterable<array>  $rows
     */
    public static function download(string $name, array $headers, iterable $rows): StreamedResponse
    {
        $filename = $name.'-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($headers, $rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, $headers, escape: '');
            foreach ($rows as $row) {
                fputcsv($out, array_map(fn ($v) => $v instanceof \BackedEnum ? $v->value : $v, $row), escape: '');
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** Número sin formato de moneda para que Excel lo trate como número. */
    public static function number(int|float|string|null $value): string
    {
        return $value === null || $value === '' ? '' : number_format((float) $value, 2, '.', '');
    }
}
