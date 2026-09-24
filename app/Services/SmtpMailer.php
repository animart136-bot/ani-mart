<?php

namespace App\Services;

class SmtpMailer
{
    private $socket;

    private string $host = '';

    private string $lastStep = 'connecting';

    public function send(
        string $to,
        string $subject,
        string $htmlBody,
        string $textBody
    ): bool {

        $host =
            trim($_ENV['MAIL_HOST'] ?? 'smtp.gmail.com');

        $port =
            (int) ($_ENV['MAIL_PORT'] ?? 587);

        $encryption =
            strtolower(
                trim($_ENV['MAIL_ENCRYPTION'] ?? 'tls')
            );

        $username =
            trim($_ENV['MAIL_USERNAME'] ?? '');

        $password =
            preg_replace('/\s+/', '', $_ENV['MAIL_PASSWORD'] ?? '');

        $fromAddress =
            trim($_ENV['MAIL_FROM_ADDRESS'] ?? $username);

        $fromName =
            trim($_ENV['MAIL_FROM_NAME'] ?? 'Ani-Mart');

        if (
            $host === '' ||
            $username === '' ||
            $password === '' ||
            $fromAddress === ''
        ) {

            throw new \RuntimeException(
                'Missing SMTP email configuration.'
            );
        }

        $this->host =
            $host;

        $this->connect($host, $port, $encryption);
        $this->expect([220], 'server greeting');

        $serverName =
            $_SERVER['SERVER_NAME'] ?? 'localhost';

        $this->command("EHLO {$serverName}", [250], 'EHLO');

        if ($encryption !== 'ssl') {

            $this->command('STARTTLS', [220], 'STARTTLS');

            $this->lastStep =
                'enabling SMTP encryption';

            if (!stream_socket_enable_crypto(
                $this->socket,
                true,
                STREAM_CRYPTO_METHOD_TLS_CLIENT
            )) {

                throw new \RuntimeException(
                    'Could not enable SMTP encryption.'
                );
            }

            $this->command("EHLO {$serverName}", [250], 'EHLO after STARTTLS');
        }

        $this->command('AUTH LOGIN', [334], 'AUTH LOGIN');
        $this->command(base64_encode($username), [334], 'SMTP username');
        $this->command(base64_encode($password), [235], 'SMTP password');
        $this->command("MAIL FROM:<{$fromAddress}>", [250], 'MAIL FROM');
        $this->command("RCPT TO:<{$to}>", [250, 251], 'RCPT TO');
        $this->command('DATA', [354], 'DATA');

        $message =
            $this->buildMessage(
                $fromAddress,
                $fromName,
                $to,
                $subject,
                $htmlBody,
                $textBody
            );

        $this->sendData($message);
        $this->expect([250], 'message body');
        $this->command('QUIT', [221], 'QUIT');

        fclose($this->socket);

        return true;
    }

    private function connect(
        string $host,
        int $port,
        string $encryption
    ): void
    {
        $scheme =
            $encryption === 'ssl'
                ? 'ssl'
                : 'tcp';

        $context =
            stream_context_create([
                'ssl' =>
                [
                    'peer_name' =>
                    $host,

                    'SNI_enabled' =>
                    true
                ]
            ]);

        $this->socket =
            @stream_socket_client(
                "{$scheme}://{$host}:{$port}",
                $errorCode,
                $errorMessage,
                20,
                STREAM_CLIENT_CONNECT,
                $context
            );

        if (!$this->socket) {

            throw new \RuntimeException(
                "Could not connect to SMTP server: {$errorMessage}"
            );
        }

        stream_set_timeout($this->socket, 20);
    }

    private function command(
        string $command,
        array $expectedCodes,
        string $step
    ): string {

        $this->lastStep =
            $step;

        fwrite(
            $this->socket,
            $command . "\r\n"
        );

        return $this->expect($expectedCodes, $step);
    }

    private function sendData(string $message): void
    {
        $this->lastStep =
            'message body';

        fwrite(
            $this->socket,
            $this->escapeData($message) . "\r\n.\r\n"
        );
    }

    private function expect(
        array $expectedCodes,
        string $step
    ): string
    {
        $this->lastStep =
            $step;

        $response = '';

        do {

            $line =
                fgets($this->socket, 515);

            if ($line === false) {

                throw new \RuntimeException(
                    "No response from SMTP server during {$this->lastStep}."
                );
            }

            $response .= $line;

        } while (
            strlen($line) >= 4 &&
            substr($line, 3, 1) === '-'
        );

        $code =
            (int) substr($response, 0, 3);

        if (!in_array($code, $expectedCodes, true)) {

            throw new \RuntimeException(
                "Unexpected SMTP response: {$response}"
            );
        }

        return $response;
    }

    private function buildMessage(
        string $fromAddress,
        string $fromName,
        string $to,
        string $subject,
        string $htmlBody,
        string $textBody
    ): string {

        $boundary =
            'ani_mart_' . bin2hex(random_bytes(12));

        $headers = [
            'From: ' . $this->formatAddress($fromAddress, $fromName),
            "To: <{$to}>",
            "Subject: {$subject}",
            'MIME-Version: 1.0',
            "Content-Type: multipart/alternative; boundary=\"{$boundary}\""
        ];

        return implode("\r\n", $headers) .
            "\r\n\r\n--{$boundary}\r\n" .
            "Content-Type: text/plain; charset=UTF-8\r\n\r\n" .
            $textBody .
            "\r\n\r\n--{$boundary}\r\n" .
            "Content-Type: text/html; charset=UTF-8\r\n\r\n" .
            $htmlBody .
            "\r\n\r\n--{$boundary}--";
    }

    private function formatAddress(
        string $address,
        string $name
    ): string {

        $safeName =
            addcslashes($name, '"\\');

        return "\"{$safeName}\" <{$address}>";
    }

    private function escapeData(string $data): string
    {
        return preg_replace('/^\./m', '..', $data);
    }
}
