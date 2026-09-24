<?php

namespace App\Services;

/**
 * Implement this contract to add Gemini, Azure Vision, or another provider
 * without changing the application controller or SmartVerify decision rules.
 */
interface FarmerCardVisionProvider
{
    public function analyze(string $imagePath): ?array;
}
