<?php

namespace App\Services;

/** Provider-neutral SmartVerify facade; it never approves an application. */
class FarmerIdAiVerificationService
{
    private FarmerCardVisionProvider $provider;

    public function __construct(?FarmerCardVisionProvider $provider = null)
    {
        $this->provider = $provider ?? $this->configuredProvider();
    }

    /** Stable integration contract for the application workflow. */
    public function verifyFarmerCard(string $imagePath): array
    {
        $assessment = $this->provider->analyze($imagePath);
        if ($assessment === null) {
            return $this->pendingResult('AI verification is temporarily unavailable or awaiting configuration. An administrator should review the document.');
        }

        $confidence = round(max(0, min(100, (float) ($assessment['confidence_score'] ?? 0))), 2);
        $aiStatus = $confidence >= 95 ? 'verified' : ($confidence >= 80 ? 'review' : 'rejected');
        $features = [
            'authentic_rsbsa_card' => $this->bool($assessment['authentic_rsbsa_card'] ?? false),
            'department_of_agriculture_logo' => $this->bool($assessment['department_of_agriculture_logo'] ?? false),
            'nrsff_text' => $this->bool($assessment['nrsff_text'] ?? false),
            'farmer_photo' => $this->bool($assessment['farmer_photo'] ?? false),
            'full_name_visible' => $this->bool($assessment['full_name_visible'] ?? false),
            'rsbsa_number_visible' => $this->bool($assessment['rsbsa_number_visible'] ?? false),
            'qr_code_detected' => $this->bool($assessment['qr_code_detected'] ?? false),
            'exactly_one_face' => $this->bool($assessment['exactly_one_face'] ?? false),
            'image_quality' => $this->choice($assessment['image_quality'] ?? '', ['excellent', 'good', 'fair', 'poor'], 'poor'),
            'quality_issues' => array_values(array_intersect((array) ($assessment['quality_issues'] ?? []), ['blurry', 'cropped', 'overexposed', 'underexposed', 'edited', 'screenshot', 'fake_document'])),
        ];
        $reason = trim((string) ($assessment['verification_reason'] ?? ''));
        if ($reason === '') {
            $reason = $aiStatus === 'verified'
                ? 'Strong RSBSA card signals were detected. Final approval still requires an administrator.'
                : 'One or more required RSBSA card signals could not be confirmed.';
        }

        return [
            'confidence_score' => $confidence,
            'ai_status' => $aiStatus,
            'verification_reason' => mb_substr($reason, 0, 2000),
            'ocr_data' => [
                'full_name' => mb_substr(trim((string) ($assessment['full_name'] ?? '')), 0, 255),
                'rsbsa_number' => mb_substr(trim((string) ($assessment['rsbsa_number'] ?? '')), 0, 60),
                'card_type' => mb_substr(trim((string) ($assessment['card_type'] ?? '')), 0, 120),
                'agency' => mb_substr(trim((string) ($assessment['agency'] ?? '')), 0, 160),
            ],
            'detected_features' => $features,
            'recommendation' => $aiStatus === 'verified' ? 'Auto Approve' : ($aiStatus === 'review' ? 'Needs Admin Review' : 'Likely Invalid Document'),
        ];
    }

    private function configuredProvider(): FarmerCardVisionProvider
    {
        $provider = strtolower(trim((string) ($_ENV['AI_FARMER_VERIFICATION_PROVIDER'] ?? getenv('AI_FARMER_VERIFICATION_PROVIDER') ?: 'gemini')));
        return match ($provider) {
            'gemini' => new GeminiFarmerCardVisionProvider(),
            'openai' => new OpenAiFarmerCardVisionProvider(),
            default => new UnavailableFarmerCardVisionProvider(),
        };
    }

    private function bool($value): bool { return filter_var($value, FILTER_VALIDATE_BOOLEAN); }
    private function choice(string $value, array $allowed, string $default): string { $value = strtolower(trim($value)); return in_array($value, $allowed, true) ? $value : $default; }

    private function pendingResult(string $reason): array
    {
        return ['confidence_score' => null, 'ai_status' => 'pending', 'verification_reason' => $reason, 'ocr_data' => ['full_name' => '', 'rsbsa_number' => '', 'card_type' => '', 'agency' => ''], 'detected_features' => [], 'recommendation' => 'Pending AI analysis'];
    }
}

class UnavailableFarmerCardVisionProvider implements FarmerCardVisionProvider { public function analyze(string $imagePath): ?array { return null; } }

class OpenAiFarmerCardVisionProvider implements FarmerCardVisionProvider
{
    public function analyze(string $imagePath): ?array
    {
        $apiKey = trim((string) ($_ENV['OPENAI_API_KEY'] ?? getenv('OPENAI_API_KEY') ?: ''));
        if ($apiKey === '' || !function_exists('curl_init') || !filter_var($imagePath, FILTER_VALIDATE_URL)) return null;
        $payload = ['model' => $_ENV['OPENAI_FARMER_VERIFICATION_MODEL'] ?? 'gpt-5-mini', 'store' => false, 'input' => [[
            'role' => 'user',
            'content' => [['type' => 'input_text', 'text' => $this->prompt()], ['type' => 'input_image', 'image_url' => $imagePath, 'detail' => 'high']],
        ]]];
        $curl = curl_init('https://api.openai.com/v1/responses');
        curl_setopt_array($curl, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => json_encode($payload), CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $apiKey, 'Content-Type: application/json'], CURLOPT_RETURNTRANSFER => true, CURLOPT_CONNECTTIMEOUT => 10, CURLOPT_TIMEOUT => 45]);
        $raw = curl_exec($curl);
        $httpCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        return is_string($raw) && $httpCode >= 200 && $httpCode < 300 ? $this->decode(json_decode($raw, true) ?: []) : null;
    }

    private function prompt(): string
    {
        return 'Inspect this Philippine farmer application image as an RSBSA card pre-verification assistant. This is not government-record validation and must not claim legal authenticity. OCR only visible text. Return exactly one JSON object (no markdown) with confidence_score (number 0-100), verification_reason, authentic_rsbsa_card, department_of_agriculture_logo, nrsff_text, farmer_photo, full_name_visible, rsbsa_number_visible, qr_code_detected, exactly_one_face (booleans), image_quality (excellent|good|fair|poor), quality_issues (array using blurry,cropped,overexposed,underexposed,edited,screenshot,fake_document), full_name, rsbsa_number, card_type, agency. Check for Department of Agriculture, NRSFF, farmer photo, full name, RSBSA number, and QR code. Do not invent unreadable text.';
    }

    private function decode(array $response): ?array
    {
        foreach (($response['output'] ?? []) as $item) foreach (($item['content'] ?? []) as $content) {
            if (($content['type'] ?? '') === 'output_text' && isset($content['text'])) {
                $text = preg_replace('/^```(?:json)?\s*|\s*```$/i', '', trim($content['text']));
                $decoded = json_decode($text, true);
                if (is_array($decoded)) return $decoded;
            }
        }
        return null;
    }
}

/** Google Gemini implementation for the same SmartVerify provider contract. */
class GeminiFarmerCardVisionProvider implements FarmerCardVisionProvider
{
    public function analyze(string $imagePath): ?array
    {
        $apiKey = trim((string) ($_ENV['GEMINI_API_KEY'] ?? getenv('GEMINI_API_KEY') ?: ''));
        if ($apiKey === '' || !function_exists('curl_init')) return null;

        $image = $this->downloadImage($imagePath);
        if ($image === null) return null;

        $model = trim((string) ($_ENV['GEMINI_FARMER_VERIFICATION_MODEL'] ?? getenv('GEMINI_FARMER_VERIFICATION_MODEL') ?: 'gemini-3.6-flash'));
        $payload = [
            'contents' => [[
                'parts' => [
                    ['text' => $this->prompt()],
                    ['inline_data' => ['mime_type' => $image['mime_type'], 'data' => base64_encode($image['bytes'])]],
                ],
            ]],
            'generationConfig' => [
                'responseMimeType' => 'application/json',
                'temperature' => 0.1,
            ],
        ];

        $curl = curl_init('https://generativelanguage.googleapis.com/v1beta/models/' . rawurlencode($model) . ':generateContent?key=' . rawurlencode($apiKey));
        curl_setopt_array($curl, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 60,
        ]);
        $raw = curl_exec($curl);
        $httpCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        if (!is_string($raw) || $httpCode < 200 || $httpCode >= 300) return null;

        $response = json_decode($raw, true) ?: [];
        $text = $response['candidates'][0]['content']['parts'][0]['text'] ?? '';
        $text = preg_replace('/^```(?:json)?\s*|\s*```$/i', '', trim((string) $text));
        $assessment = json_decode($text, true);
        return is_array($assessment) ? $assessment : null;
    }

    private function downloadImage(string $url): ?array
    {
        if (!filter_var($url, FILTER_VALIDATE_URL)) return null;
        $curl = curl_init($url);
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3,
        ]);
        $bytes = curl_exec($curl);
        $httpCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $contentType = strtolower((string) curl_getinfo($curl, CURLINFO_CONTENT_TYPE));
        curl_close($curl);
        if (!is_string($bytes) || $bytes === '' || strlen($bytes) > 10 * 1024 * 1024 || $httpCode < 200 || $httpCode >= 300) return null;

        $mimeType = trim(explode(';', $contentType)[0]);
        if (!in_array($mimeType, ['image/jpeg', 'image/png', 'image/webp'], true)) return null;
        return ['bytes' => $bytes, 'mime_type' => $mimeType];
    }

    private function prompt(): string
    {
        return 'Inspect this Philippine farmer application image as an RSBSA card pre-verification assistant. This is not government-record validation and must not claim legal authenticity. OCR only visible text. Return exactly one JSON object (no markdown) with confidence_score (number 0-100), verification_reason, authentic_rsbsa_card, department_of_agriculture_logo, nrsff_text, farmer_photo, full_name_visible, rsbsa_number_visible, qr_code_detected, exactly_one_face (booleans), image_quality (excellent|good|fair|poor), quality_issues (array using blurry,cropped,overexposed,underexposed,edited,screenshot,fake_document), full_name, rsbsa_number, card_type, agency. Check for Department of Agriculture, NRSFF, farmer photo, full name, RSBSA number, and QR code. Do not invent unreadable text.';
    }
}
