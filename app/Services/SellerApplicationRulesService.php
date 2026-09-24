<?php

namespace App\Services;

class SellerApplicationRulesService
{
    /**
     * Compare the applicant identity with the name OCR'd from an RSBSA card.
     * A middle-name difference is deliberately reviewable, not a rejection.
     */
    public static function rsbsaNameMatchStatus(
        ?string $firstName,
        ?string $middleName,
        ?string $lastName,
        ?string $extractedName
    ): string {
        $first = self::normalizeName($firstName);
        $middle = self::normalizeName($middleName);
        $last = self::normalizeName($lastName);
        $extracted = self::normalizeName($extractedName);

        if ($extracted === '' || $first === '' || $last === '') {
            return 'unavailable';
        }

        $submittedFullName = trim(implode(' ', array_filter([$first, $middle, $last])));
        if ($submittedFullName === $extracted) {
            return 'match';
        }

        // Both legal first and last names are present, but the middle name,
        // name order, or OCR formatting differs. An administrator must decide.
        if (self::containsNamePart($extracted, $first) && self::containsNamePart($extracted, $last)) {
            return 'review';
        }

        return 'mismatch';
    }

    private static function normalizeName(?string $value): string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }

        $transliterated = function_exists('iconv') ? iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) : $value;
        $value = is_string($transliterated) ? $transliterated : $value;
        $value = preg_replace('/[^a-z0-9]+/i', ' ', $value) ?? '';
        return strtolower(trim(preg_replace('/\s+/', ' ', $value) ?? ''));
    }

    private static function containsNamePart(string $fullName, string $part): bool
    {
        return str_contains(' ' . $fullName . ' ', ' ' . $part . ' ');
    }

    public static function verificationTrack(?string $rsbsaCardUrl, ?string $rsbsaNumber): array
    {
        $hasRsbsaInformation = trim((string) $rsbsaCardUrl) !== '' || trim((string) $rsbsaNumber) !== '';

        return [
            'has_rsbsa_information' => $hasRsbsaInformation,
            'application_status' => 'pending',
            'rsbsa_verification_status' => $hasRsbsaInformation ? 'submitted' : 'not_submitted',
        ];
    }
}
