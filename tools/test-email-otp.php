<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Services\SmtpMailer;
use App\Services\VerificationEmailTemplate;
use Dotenv\Dotenv;

$dotenv =
    Dotenv::createImmutable(
        dirname(__DIR__)
    );

$dotenv->load();

$recipient =
    $argv[1] ?? ($_ENV['MAIL_USERNAME'] ?? '');

if (trim($recipient) === '') {

    fwrite(
        STDERR,
        "Missing recipient. Set MAIL_USERNAME in .env or pass an email address.\n"
    );

    exit(1);
}

$otp =
    (string) random_int(100000, 999999);

$htmlBody =
    VerificationEmailTemplate::html(
        'Ani-Mart tester',
        $otp,
        true
    );

$textBody =
    VerificationEmailTemplate::text(
        'Ani-Mart tester',
        $otp,
        true
    );

try {

    (new SmtpMailer())->send(
        $recipient,
        'Ani-Mart email OTP test',
        $htmlBody,
        $textBody
    );

    echo "Test OTP email sent to {$recipient}\n";

} catch (Throwable $exception) {

    fwrite(
        STDERR,
        "Email test failed: {$exception->getMessage()}\n"
    );

    exit(1);
}
