<?php

namespace App\Services;

use Symfony\Component\HttpFoundation\StreamedResponse;

class ExcelExportService
{
    /**
     * Stream an HTML table formatted for Excel (.xls) with gridlines, styled headers, and borders.
     *
     * @param string $filename File name for download (e.g. export-idle-alarms.xls)
     * @param string $reportTitle Main title at top of excel sheet
     * @param array $headers List of headers, e.g. ['NO', 'DEVICE ID', ...] or [['label' => 'NO', 'align' => 'center'], ...]
     * @param \Closure $dataCallback Function receiving resource $output to write <tr>...</tr> rows
     * @param array $metadata Key-value pair of filter details to display at top
     * @return StreamedResponse
     */
    public static function streamXls(
        string $filename,
        string $reportTitle,
        array $headers,
        \Closure $dataCallback,
        array $metadata = []
    ): StreamedResponse {
        // Ensure .xls extension
        if (!str_ends_with(strtolower($filename), '.xls')) {
            $filename = pathinfo($filename, PATHINFO_FILENAME) . '.xls';
        }

        $responseHeaders = [
            'Content-Type' => 'application/vnd.ms-excel; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control' => 'max-age=0, no-cache, must-revalidate',
            'Pragma' => 'public',
        ];

        return response()->streamDownload(function () use ($reportTitle, $headers, $dataCallback, $metadata) {
            $out = fopen('php://output', 'w');
            self::writeExcelDocument($out, $reportTitle, $headers, $dataCallback, $metadata);
            fclose($out);
        }, $filename, $responseHeaders);
    }

    /**
     * Write full Excel HTML document to a file resource stream.
     */
    public static function writeExcelDocument(
        $out,
        string $reportTitle,
        array $headers,
        \Closure $dataCallback,
        array $metadata = []
    ): void {
        // UTF-8 BOM for Excel encoding
        fwrite($out, "\xEF\xBB\xBF");

        // HTML & Excel Schema Header
        fwrite($out, '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">' . "\n");
        fwrite($out, '<head>' . "\n");
        fwrite($out, '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">' . "\n");
        fwrite($out, '<!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet><x:Name>Sheet1</x:Name><x:WorksheetOptions><x:DisplayGridlines/><x:DoNotDisplayGridlines>False</x:DoNotDisplayGridlines></x:WorksheetOptions></x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]-->' . "\n");
        fwrite($out, '<style>' . "\n");
        fwrite($out, '  body, table { font-family: Arial, sans-serif; font-size: 10pt; color: #1e293b; }' . "\n");
        fwrite($out, '  .title { font-size: 15pt; font-weight: bold; color: #0f172a; margin-bottom: 4px; }' . "\n");
        fwrite($out, '  .meta { font-size: 9.5pt; color: #475569; margin-bottom: 12px; }' . "\n");
        fwrite($out, '  table { border-collapse: collapse; width: 100%; border: 1px solid #94a3b8; }' . "\n");
        fwrite($out, '  th { background-color: #1e293b; color: #ffffff; font-weight: bold; text-align: center; vertical-align: middle; border: 1px solid #0f172a; padding: 8px 10px; font-size: 10pt; white-space: nowrap; }' . "\n");
        fwrite($out, '  td { border: 1px solid #cbd5e1; padding: 6px 8px; vertical-align: middle; font-size: 9.5pt; mso-number-format: "\\@"; }' . "\n");
        fwrite($out, '  .text-left { text-align: left; }' . "\n");
        fwrite($out, '  .text-center { text-align: center; }' . "\n");
        fwrite($out, '  .text-right { text-align: right; }' . "\n");
        fwrite($out, '  .row-even { background-color: #f8fafc; }' . "\n");
        fwrite($out, '  .row-odd { background-color: #ffffff; }' . "\n");
        fwrite($out, '  .badge-success { background-color: #dcfce7; color: #15803d; font-weight: bold; text-align: center; }' . "\n");
        fwrite($out, '  .badge-warning { background-color: #fef9c3; color: #a16207; font-weight: bold; text-align: center; }' . "\n");
        fwrite($out, '  .badge-orange { background-color: #ffedd5; color: #c2410c; font-weight: bold; text-align: center; }' . "\n");
        fwrite($out, '  .badge-danger { background-color: #fee2e2; color: #b91c1c; font-weight: bold; text-align: center; }' . "\n");
        fwrite($out, '</style>' . "\n");
        fwrite($out, '</head>' . "\n");
        fwrite($out, '<body>' . "\n");

        // Report Title
        fwrite($out, '<div class="title">' . htmlspecialchars($reportTitle) . '</div>' . "\n");

        // Metadata Header
        $metaParts = [];
        $metaParts[] = 'Export Date: ' . date('Y-m-d H:i:s');
        foreach ($metadata as $key => $val) {
            if ($val !== null && $val !== '') {
                $metaParts[] = htmlspecialchars($key) . ': ' . htmlspecialchars($val);
            }
        }
        fwrite($out, '<div class="meta">' . implode(' | ', $metaParts) . '</div>' . "\n");

        // Excel Table
        fwrite($out, '<table border="1" cellspacing="0" cellpadding="5">' . "\n");
        fwrite($out, '  <thead>' . "\n");
        fwrite($out, '    <tr>' . "\n");
        foreach ($headers as $header) {
            $align = is_array($header) ? ($header['align'] ?? 'center') : 'center';
            $label = is_array($header) ? ($header['label'] ?? '') : $header;
            fwrite($out, '      <th class="text-' . $align . '">' . htmlspecialchars($label) . '</th>' . "\n");
        }
        fwrite($out, '    </tr>' . "\n");
        fwrite($out, '  </thead>' . "\n");
        fwrite($out, '  <tbody>' . "\n");

        // Render Data Rows
        $dataCallback($out);

        fwrite($out, '  </tbody>' . "\n");
        fwrite($out, '</table>' . "\n");
        fwrite($out, '</body>' . "\n");
        fwrite($out, '</html>' . "\n");
    }
}
