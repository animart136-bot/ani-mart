<?php

namespace App\Services\PriceAnalysis;

class RuleBasedPriceAnalyzer implements PriceAnalyzerInterface
{
    public function analyze(?float $sellerPrice, ?float $referencePrice, array $config): array
    {
        if ($sellerPrice === null || $sellerPrice <= 0 || $referencePrice === null || $referencePrice <= 0) {
            return [
                'assessment' => 'REFERENCE_UNAVAILABLE',
                'price_difference' => null,
                'price_deviation_percentage' => null,
                'requires_warning' => false,
                'notify_seller' => false,
                'confidence' => null,
                'analyzer' => 'rule_based',
            ];
        }

        $difference = round($sellerPrice - $referencePrice, 2);
        $deviation = round((($sellerPrice - $referencePrice) / $referencePrice) * 100, 2);
        $absoluteDeviation = abs($deviation);

        $thresholds = $config['thresholds'] ?? [];
        $normalMax = (float) ($thresholds['normal_max'] ?? 15);
        $reviewMax = (float) ($thresholds['price_review_max'] ?? 30);
        $highMax = (float) ($thresholds['high_deviation_max'] ?? 50);

        if ($absoluteDeviation <= $normalMax) {
            $assessment = 'NORMAL';
        } elseif ($absoluteDeviation <= $reviewMax) {
            $assessment = 'PRICE_REVIEW';
        } elseif ($absoluteDeviation <= $highMax) {
            $assessment = 'HIGH_DEVIATION';
        } else {
            $assessment = 'SIGNIFICANT_DEVIATION';
        }

        $warningAssessments = $config['warning_assessments'] ?? ['HIGH_DEVIATION', 'SIGNIFICANT_DEVIATION'];
        $notifyAssessments = $config['notify_assessments'] ?? ['SIGNIFICANT_DEVIATION'];

        return [
            'assessment' => $assessment,
            'price_difference' => $difference,
            'price_deviation_percentage' => $deviation,
            'requires_warning' => in_array($assessment, $warningAssessments, true),
            'notify_seller' => in_array($assessment, $notifyAssessments, true),
            'confidence' => null,
            'analyzer' => 'rule_based',
        ];
    }
}
