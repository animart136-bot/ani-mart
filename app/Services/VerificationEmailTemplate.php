<?php

namespace App\Services;

class VerificationEmailTemplate
{
    public static function html(
        string $firstName,
        string $otp,
        bool $isTest = false
    ): string {
        $safeName =
            htmlspecialchars(
                $firstName,
                ENT_QUOTES,
                'UTF-8'
            );

        $safeOtp =
            htmlspecialchars(
                $otp,
                ENT_QUOTES,
                'UTF-8'
            );

        $digits =
            str_split($safeOtp);

        $codeBlocks =
            '';

        foreach ($digits as $digit) {

            $codeBlocks .=
                '<td style="padding:0 4px;">' .
                '<span style="display:block;width:44px;height:52px;line-height:52px;text-align:center;background:#f8faf7;border:1px solid #dbe7d5;border-radius:12px;color:#173d2f;font-family:Arial,Helvetica,sans-serif;font-size:24px;font-weight:800;letter-spacing:0;">' .
                $digit .
                '</span>' .
                '</td>';
        }

        $supportingCopy =
            $isTest
                ? 'This is a test email for the registration OTP setup.'
                : 'Enter this code on Ani-Mart to finish creating your account.';

        return
            '<!doctype html>' .
            '<html lang="en">' .
            '<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>' .
            '<body style="margin:0;padding:0;background:#eef5ed;font-family:Arial,Helvetica,sans-serif;color:#1f2a24;">' .
            '<span style="display:none!important;visibility:hidden;opacity:0;color:transparent;height:0;width:0;overflow:hidden;">Your Ani-Mart verification code expires in 10 minutes.</span>' .
            '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#eef5ed;margin:0;padding:32px 12px;">' .
            '<tr><td align="center">' .
            '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:640px;background:#ffffff;border-radius:22px;overflow:hidden;border:1px solid #dfeade;box-shadow:0 18px 48px rgba(23,61,47,.12);">' .
            '<tr><td style="background:#173d2f;padding:30px 32px;">' .
            '<table role="presentation" width="100%" cellspacing="0" cellpadding="0"><tr>' .
            '<td style="vertical-align:middle;">' .
            '<div style="display:inline-block;background:#f4b942;color:#173d2f;border-radius:999px;padding:7px 12px;font-size:12px;font-weight:800;letter-spacing:.08em;text-transform:uppercase;">Ani-Mart</div>' .
            '<h1 style="margin:18px 0 0;color:#ffffff;font-size:28px;line-height:1.2;font-weight:800;">Verify your email</h1>' .
            '<p style="margin:10px 0 0;color:#d6ead5;font-size:15px;line-height:1.6;">Fresh access code for your marketplace account.</p>' .
            '</td>' .
            '<td align="right" style="vertical-align:top;width:76px;">' .
            '<div style="width:58px;height:58px;border-radius:18px;background:#ffffff;text-align:center;line-height:58px;color:#173d2f;font-size:28px;font-weight:800;">A</div>' .
            '</td>' .
            '</tr></table>' .
            '</td></tr>' .
            '<tr><td style="padding:34px 32px 10px;">' .
            '<p style="margin:0;color:#233129;font-size:17px;line-height:1.6;">Hi ' . $safeName . ',</p>' .
            '<p style="margin:10px 0 0;color:#4c5a52;font-size:15px;line-height:1.7;">' . $supportingCopy . '</p>' .
            '</td></tr>' .
            '<tr><td align="center" style="padding:16px 24px 8px;">' .
            '<table role="presentation" cellspacing="0" cellpadding="0" style="margin:0 auto;"><tr>' . $codeBlocks . '</tr></table>' .
            '<p style="margin:18px 0 0;color:#6f7b73;font-size:13px;line-height:1.5;">This code expires in <strong style="color:#173d2f;">10 minutes</strong>.</p>' .
            '</td></tr>' .
            '<tr><td style="padding:18px 32px 34px;">' .
            '<div style="background:#f8faf7;border:1px solid #e1eadf;border-radius:16px;padding:16px 18px;">' .
            '<p style="margin:0;color:#4a5a50;font-size:14px;line-height:1.6;"><strong style="color:#173d2f;">Security note:</strong> If you did not request this code, you can safely ignore this email. Do not share this code with anyone.</p>' .
            '</div>' .
            '</td></tr>' .
            '</table>' .
            '<p style="max-width:640px;margin:18px auto 0;color:#7a877e;font-size:12px;line-height:1.6;text-align:center;">Ani-Mart keeps your account protected while you buy and sell farm products online.</p>' .
            '</td></tr>' .
            '</table>' .
            '</body>' .
            '</html>';
    }

    public static function text(
        string $firstName,
        string $otp,
        bool $isTest = false
    ): string {
        $purpose =
            $isTest
                ? 'This is a test email for the registration OTP setup.'
                : 'Enter this code on Ani-Mart to finish creating your account.';

        return
            "Hi {$firstName},\n\n" .
            "{$purpose}\n\n" .
            "Your Ani-Mart verification code is: {$otp}\n\n" .
            "This code expires in 10 minutes. If you did not request this, you can ignore this email. Do not share this code with anyone.\n";
    }
}
