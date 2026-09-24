<?php

namespace App\Services;

class AccountSecurityEmailTemplate
{
    public static function passwordReset(string $firstName, string $resetUrl): array
    {
        $name = htmlspecialchars($firstName ?: 'there', ENT_QUOTES, 'UTF-8');
        $url = htmlspecialchars($resetUrl, ENT_QUOTES, 'UTF-8');
        return [
            'subject' => 'Reset your Ani-Mart password',
            'html' => self::shell('Reset your password', '<p>Hi ' . $name . ',</p><p>We received a request to reset your Ani-Mart password.</p><p style="margin:28px 0"><a href="' . $url . '" style="background:#1f7a4d;color:#fff;text-decoration:none;padding:13px 22px;border-radius:10px;font-weight:700">Reset password</a></p><p>This secure link expires in one hour and can be used only once.</p><p>If you did not request this, you can safely ignore this email.</p>'),
            'text' => "Hi {$firstName},\n\nReset your Ani-Mart password using this link:\n{$resetUrl}\n\nThe link expires in one hour and can be used only once. If you did not request this, ignore this email.\n",
        ];
    }

    public static function adminLoginCode(string $firstName, string $code): array
    {
        $name = htmlspecialchars($firstName ?: 'Administrator', ENT_QUOTES, 'UTF-8');
        $safeCode = htmlspecialchars($code, ENT_QUOTES, 'UTF-8');
        return [
            'subject' => 'Your Ani-Mart administrator login code',
            'html' => self::shell('Confirm administrator login', '<p>Hi ' . $name . ',</p><p>Enter this code to finish signing in to the Ani-Mart administration account:</p><p style="font-size:32px;font-weight:800;letter-spacing:8px;color:#1f7a4d">' . $safeCode . '</p><p>The code expires in 10 minutes. Never share it with anyone.</p><p>If this was not you, change your password immediately.</p>'),
            'text' => "Hi {$firstName},\n\nYour Ani-Mart administrator login code is: {$code}\n\nIt expires in 10 minutes. Never share it. If this was not you, change your password immediately.\n",
        ];
    }

    private static function shell(string $title, string $body): string
    {
        return '<!doctype html><html><body style="margin:0;background:#eef5ed;font-family:Arial,sans-serif;color:#233129"><table role="presentation" width="100%" cellpadding="0" cellspacing="0"><tr><td style="padding:32px 12px" align="center"><table role="presentation" width="100%" style="max-width:620px;background:#fff;border:1px solid #dfeade;border-radius:20px"><tr><td style="padding:28px 32px;background:#173d2f;color:#fff;border-radius:20px 20px 0 0"><strong style="color:#f4b942">ANI-MART</strong><h1 style="margin:12px 0 0;font-size:26px">' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</h1></td></tr><tr><td style="padding:32px;line-height:1.7">' . $body . '<div style="margin-top:26px;padding:14px;background:#f6faf6;border-radius:12px;color:#637168;font-size:13px">Ani-Mart will never ask for your password or verification code by email.</div></td></tr></table></td></tr></table></body></html>';
    }
}
