<?php

namespace App\Services\PriceAnalysis;

interface PriceAnalyzerInterface
{
    /**
     * Compare a seller price with a reference market price.
     * A high result means review is recommended, not automatic rejection.
     *
     * @return array{
     *     assessment: string,
     *     price_difference: float|null,
     *     price_deviation_percentage: float|null,
     *     requires_warning: bool,
     *     notify_seller: bool,
     *     confidence: float|null,
     *     analyzer: string
     * }
     */
    public function analyze(?float $sellerPrice, ?float $referencePrice, array $config): array;
}
