<?php

namespace App\Support;

/**
 * Shared by every CSV export/import in the app (ReportController,
 * HistoryController, Admin\UserController) — a plain fputcsv() call with
 * its default comma delimiter and no BOM is what a US/UK Windows install
 * of Excel expects, but this app's actual audience opens these files on
 * Spanish-locale Excel, where "," is the DECIMAL separator and ";" is
 * what a CSV's own column separator is expected to be: double-clicking a
 * comma-delimited file there shows the whole line as one cell instead of
 * splitting into columns, exactly the "sale con comas" complaint. The
 * UTF-8 BOM is the other half of the same problem — without it, Excel
 * assumes the file is Windows-1252 and renders every tilde/ñ as mojibake
 * ("CategorÃ­a" instead of "Categoría").
 */
class Csv
{
    public const DELIMITER = ';';

    /**
     * Call once, right after fopen(), before the header row.
     */
    public static function start($handle): void
    {
        fwrite($handle, "\xEF\xBB\xBF");
    }

    public static function row($handle, array $fields): void
    {
        fputcsv($handle, $fields, self::DELIMITER);
    }

    /**
     * For READING an uploaded roster back: fgetcsv() doesn't strip a
     * UTF-8 BOM itself (it would otherwise sit glued to the first header
     * name, e.g. "\xEF\xBB\xBFnombre", breaking the exact-match lookup
     * for that column), and a file saved from Excel's plain "CSV
     * (delimitado por comas)" format (not "CSV UTF-8") writes tildes/ñ as
     * Windows-1252 instead of UTF-8 — both corrupt the same way the
     * export used to, just in reverse. Run every cell through this
     * before using it.
     */
    public static function normalizeCell(?string $value): string
    {
        $value = trim((string) $value);
        $value = ltrim($value, "\xEF\xBB\xBF");

        if ($value !== '' && ! mb_check_encoding($value, 'UTF-8')) {
            $value = mb_convert_encoding($value, 'UTF-8', 'Windows-1252');
        }

        return $value;
    }

    /**
     * fgetcsv()'s own delimiter detection isn't automatic — a roster
     * someone exported from this app (or built in Excel, which defaults
     * to ";" on a Spanish install) uses ";"; one typed/pasted by hand
     * more often uses ",". Peek at the raw header line and pick whichever
     * delimiter actually splits it into more than one column.
     */
    public static function detectDelimiter(string $headerLine): string
    {
        return substr_count($headerLine, ';') > substr_count($headerLine, ',') ? ';' : ',';
    }
}
