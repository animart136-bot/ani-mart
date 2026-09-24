<?php

namespace App\Services;

class OrderHistoryReportService
{
    public static function csv(array $orders, array $filters, string $customerName): string
    {
        $stream = fopen('php://temp', 'w+b');
        fwrite($stream, "\xEF\xBB\xBF");
        self::putCsv($stream, ['Ani-Mart Order History']);
        self::putCsv($stream, ['Customer', self::safeCsv($customerName)]);
        self::putCsv($stream, ['Filters', self::filterSummary($filters)]);
        self::putCsv($stream, []);
        self::putCsv($stream, ['Date', 'Order', 'Seller', 'Products', 'Status', 'Payment method', 'Product subtotal', 'Delivery fee', 'Order total']);
        foreach ($orders as $order) {
            $row = self::orderRow($order);
            self::putCsv($stream, [
                $row['date'], self::safeCsv($row['order']), self::safeCsv($row['seller']),
                self::safeCsv($row['products']), $row['status'], $row['payment'],
                number_format($row['subtotal'], 2, '.', ''), number_format($row['delivery'], 2, '.', ''),
                number_format($row['total'], 2, '.', ''),
            ]);
        }
        rewind($stream);
        $csv = stream_get_contents($stream);
        fclose($stream);
        return $csv;
    }

    public static function pdf(array $orders, array $filters, string $customerName): string
    {
        $pages = [];
        $page = self::pageHeader($customerName, $filters);
        $y = 584;
        $page .= self::tableHeader($y);
        $y -= 27;
        $totalValue = 0.0;

        foreach ($orders as $order) {
            $row = self::orderRow($order);
            $totalValue += $row['total'];
            $sellerLines = self::wrap($row['seller'], 18);
            $productLines = self::wrap($row['products'], 26);
            $lineCount = max(1, count($sellerLines), count($productLines));
            $height = max(38, 18 + ($lineCount * 10));
            if ($y - $height < 92) {
                $pages[] = $page;
                $page = self::continuationHeader();
                $y = 660;
                $page .= self::tableHeader($y);
                $y -= 27;
            }

            $baseline = $y - 11;
            $page .= self::text(44, $baseline, substr($row['date'], 0, 10), 8, '0.41 0.46 0.43 rg');
            $page .= self::text(108, $baseline, self::truncate($row['order'], 18), 8, '0.11 0.17 0.14 rg', 'F2');
            foreach ($sellerLines as $index => $line) $page .= self::text(211, $baseline - ($index * 10), $line, 8, '0.11 0.17 0.14 rg');
            foreach ($productLines as $index => $line) $page .= self::text(315, $baseline - ($index * 10), $line, 8, '0.11 0.17 0.14 rg');
            $page .= self::text(454, $baseline, self::truncate(ucwords(str_replace('_', ' ', $row['status'])), 14), 8, '0.06 0.32 0.20 rg', 'F2');
            $page .= self::textRight(568, $baseline, 'PHP ' . number_format($row['total'], 2), 8, '0.11 0.17 0.14 rg', 'F2');
            $page .= self::line(42, $y - $height + 5, 570, $y - $height + 5, '0.86 0.91 0.88 RG');
            $y -= $height;
        }

        if ($orders === []) {
            $page .= self::text(44, $y - 18, 'No orders match the selected filters.', 10, '0.41 0.46 0.43 rg');
            $y -= 48;
        }
        if ($y < 145) {
            $pages[] = $page;
            $page = self::continuationHeader();
            $y = 635;
        }
        $page .= self::summary($y - 5, count($orders), $totalValue);
        $pages[] = $page;

        $count = count($pages);
        foreach ($pages as $index => &$content) {
            $content .= self::text(42, 34, 'This report was generated electronically by Ani-Mart.', 8, '0.41 0.46 0.43 rg');
            $content .= self::textRight(570, 34, 'Page ' . ($index + 1) . ' of ' . $count, 8, '0.41 0.46 0.43 rg');
        }
        unset($content);
        return self::buildPdf($pages);
    }

    private static function orderRow(object $order): array
    {
        $sellers = [];
        $products = [];
        $subtotal = 0.0;
        foreach ($order->items ?? [] as $item) {
            $seller = trim((string) ($item->shop_name ?? ''));
            if ($seller !== '') $sellers[$seller] = true;
            $name = trim((string) ($item->name ?? 'Product'));
            $products[] = $name . ' x' . (int) ($item->quantity ?? 0);
            $subtotal += (float) ($item->price ?? 0) * (int) ($item->quantity ?? 0);
        }
        return [
            'date' => (string) ($order->created_at ?? ''),
            'order' => (string) ($order->order_number ?? ('#' . ($order->id ?? ''))),
            'seller' => implode(', ', array_keys($sellers)) ?: 'Seller unavailable',
            'products' => implode('; ', $products) ?: 'No products',
            'status' => (string) ($order->status ?? ''),
            'payment' => ucwords(str_replace('_', ' ', (string) ($order->payment_method ?? ''))),
            'subtotal' => $subtotal,
            'delivery' => (float) ($order->delivery_fee ?? 0),
            'total' => (float) ($order->total_amount ?? ($subtotal + (float) ($order->delivery_fee ?? 0))),
        ];
    }

    private static function filterSummary(array $filters): string
    {
        $parts = [];
        foreach (['date_from' => 'From', 'date_to' => 'To', 'seller' => 'Seller', 'product' => 'Product', 'status' => 'Status'] as $key => $label) {
            if (!empty($filters[$key])) $parts[] = $label . ': ' . str_replace('_', ' ', (string) $filters[$key]);
        }
        return $parts ? implode(' | ', $parts) : 'All orders';
    }

    private static function pageHeader(string $customerName, array $filters): string
    {
        $content = self::rect(0, 784, 612, 8, '0.09 0.45 0.28 rg');
        $content .= self::text(42, 744, 'ANI-MART', 21, '0.09 0.45 0.28 rg', 'F2');
        $content .= self::text(42, 726, 'Fresh produce from local farmers', 9, '0.41 0.46 0.43 rg');
        $content .= self::textRight(570, 744, 'Order History Report', 22, '0.11 0.17 0.14 rg', 'F2');
        $content .= self::textRight(570, 726, 'Customer purchase record', 9, '0.41 0.46 0.43 rg');
        $content .= self::line(42, 700, 570, 700, '0.86 0.91 0.88 RG');
        $content .= self::text(42, 675, 'CUSTOMER', 7, '0.41 0.46 0.43 rg', 'F2');
        $content .= self::text(42, 658, self::truncate($customerName, 42), 11, '0.11 0.17 0.14 rg', 'F2');
        $content .= self::text(316, 675, 'REPORT FILTERS', 7, '0.41 0.46 0.43 rg', 'F2');
        foreach (self::wrap(self::filterSummary($filters), 50) as $index => $line) {
            $content .= self::text(316, 658 - ($index * 12), $line, 9, '0.41 0.46 0.43 rg');
        }
        $content .= self::line(42, 620, 570, 620, '0.86 0.91 0.88 RG');
        return $content;
    }

    private static function continuationHeader(): string
    {
        $content = self::rect(0, 784, 612, 8, '0.09 0.45 0.28 rg');
        $content .= self::text(42, 744, 'ANI-MART', 18, '0.09 0.45 0.28 rg', 'F2');
        $content .= self::textRight(570, 744, 'Order History Report', 18, '0.11 0.17 0.14 rg', 'F2');
        $content .= self::textRight(570, 726, 'Continued', 9, '0.41 0.46 0.43 rg');
        $content .= self::line(42, 700, 570, 700, '0.86 0.91 0.88 RG');
        return $content;
    }

    private static function tableHeader(float $y): string
    {
        $content = self::line(42, $y - 19, 570, $y - 19, '0.09 0.45 0.28 RG', 1.4);
        foreach ([[44, 'DATE'], [108, 'ORDER'], [211, 'SELLER'], [315, 'PRODUCTS'], [454, 'STATUS']] as [$x, $label]) {
            $content .= self::text($x, $y - 12, $label, 7, '0.41 0.46 0.43 rg', 'F2');
        }
        $content .= self::textRight(568, $y - 12, 'TOTAL', 7, '0.41 0.46 0.43 rg', 'F2');
        return $content;
    }

    private static function summary(float $y, int $orderCount, float $totalValue): string
    {
        $content = self::rect(42, $y - 70, 528, 70, '0.92 0.97 0.94 rg');
        $content .= self::text(58, $y - 24, 'Thank you for supporting local farmers.', 10, '0.06 0.32 0.20 rg', 'F2');
        $content .= self::text(58, $y - 42, 'Keep this report for your order records.', 8, '0.20 0.40 0.30 rg');
        $content .= self::text(390, $y - 22, 'ORDERS', 7, '0.20 0.40 0.30 rg', 'F2');
        $content .= self::textRight(554, $y - 22, (string) $orderCount, 10, '0.06 0.32 0.20 rg', 'F2');
        $content .= self::text(390, $y - 45, 'TOTAL SPENT', 7, '0.20 0.40 0.30 rg', 'F2');
        $content .= self::textRight(554, $y - 45, 'PHP ' . number_format($totalValue, 2), 11, '0.06 0.32 0.20 rg', 'F2');
        return $content;
    }

    private static function text(float $x, float $y, string $text, int $size, string $color, string $font = 'F1'): string
    {
        $text = self::pdfText($text);
        return $color . " BT /{$font} {$size} Tf {$x} {$y} Td ({$text}) Tj ET\n";
    }

    private static function textRight(float $right, float $y, string $text, int $size, string $color, string $font = 'F1'): string
    {
        $plain = self::plainText($text);
        $factor = $font === 'F2' ? 0.56 : 0.52;
        $x = max(42, $right - (strlen($plain) * $size * $factor));
        return self::text($x, $y, $plain, $size, $color, $font);
    }

    private static function rect(float $x, float $y, float $width, float $height, string $color): string
    {
        return "{$color} {$x} {$y} {$width} {$height} re f\n";
    }

    private static function line(float $x1, float $y1, float $x2, float $y2, string $color, float $width = 0.5): string
    {
        return "{$color} {$width} w {$x1} {$y1} m {$x2} {$y2} l S\n";
    }

    private static function wrap(string $text, int $width): array
    {
        $wrapped = wordwrap(self::plainText($text), $width, "\n", true);
        return array_slice(explode("\n", $wrapped), 0, 4);
    }

    private static function truncate(string $text, int $length): string
    {
        $text = self::plainText($text);
        return strlen($text) > $length ? substr($text, 0, max(0, $length - 3)) . '...' : $text;
    }

    private static function plainText(string $text): string
    {
        $text = str_replace(['₱', '–', '—', '“', '”'], ['PHP ', '-', '-', '"', '"'], $text);
        $converted = iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $text);
        return preg_replace('/[\x00-\x1F\x7F]/', ' ', $converted !== false ? $converted : $text);
    }

    private static function pdfText(string $text): string
    {
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], self::plainText($text));
    }

    private static function safeCsv(string $value): string
    {
        $value = trim($value);
        return preg_match('/^[=+\-@]/', $value) ? "'" . $value : $value;
    }

    private static function putCsv($stream, array $row): void
    {
        fputcsv($stream, $row, ',', '"', '');
    }

    private static function buildPdf(array $pages): string
    {
        $objects = [];
        $objects[1] = '<< /Type /Catalog /Pages 2 0 R >>';
        $pageObjectNumbers = [];
        $next = 5;
        foreach ($pages as $content) {
            $pageNumber = $next++;
            $contentNumber = $next++;
            $pageObjectNumbers[] = $pageNumber;
            $objects[$pageNumber] = "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Resources << /Font << /F1 3 0 R /F2 4 0 R >> >> /Contents {$contentNumber} 0 R >>";
            $objects[$contentNumber] = "<< /Length " . strlen($content) . " >>\nstream\n{$content}endstream";
        }
        $objects[2] = '<< /Type /Pages /Count ' . count($pageObjectNumbers) . ' /Kids [' . implode(' ', array_map(static fn($number) => $number . ' 0 R', $pageObjectNumbers)) . '] >>';
        $objects[3] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>';
        $objects[4] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>';
        ksort($objects);
        $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $offsets = [0];
        foreach ($objects as $number => $object) {
            $offsets[$number] = strlen($pdf);
            $pdf .= "{$number} 0 obj\n{$object}\nendobj\n";
        }
        $xref = strlen($pdf);
        $max = max(array_keys($objects));
        $pdf .= "xref\n0 " . ($max + 1) . "\n0000000000 65535 f \n";
        for ($i = 1; $i <= $max; $i++) $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        $pdf .= "trailer\n<< /Size " . ($max + 1) . " /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF";
        return $pdf;
    }
}
