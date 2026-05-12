<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * BrevoMailService
 *
 * Sends transactional emails via the Brevo (Sendinblue) HTTP API.
 * https://developers.brevo.com/reference/sendtransacemail
 */
class BrevoMailService
{
    private string $apiKey;
    private string $apiUrl = 'https://api.brevo.com/v3/smtp/email';
    private string $fromEmail;
    private string $fromName;

    public function __construct()
    {
        $this->apiKey = env('BREVO_API_KEY', '');
        $this->fromEmail = env('MAIL_FROM_ADDRESS', 'noreply@prism-ai.app');
        $this->fromName = env('MAIL_FROM_NAME', 'Prism AI');
    }

    /**
     * Send a transactional email.
     *
     * @param  string $toEmail   Recipient email
     * @param  string $toName    Recipient name (optional)
     * @param  string $subject   Email subject
     * @param  string $htmlBody  HTML body of the email
     * @param  string|null $textBody Optional plain text fallback
     * @return bool  True on success
     */
    public function send(string $toEmail, string $toName, string $subject, string $htmlBody, ?string $textBody = null): bool
    {
        if (empty($this->apiKey)) {
            Log::error('BrevoMailService: BREVO_API_KEY is not configured');
            return false;
        }

        $payload = [
            'sender' => [
                'name' => $this->fromName,
                'email' => $this->fromEmail,
            ],
            'to' => [[
                'email' => $toEmail,
                'name' => $toName ?: $toEmail,
            ]],
            'subject' => $subject,
            'htmlContent' => $htmlBody,
        ];

        if ($textBody) {
            $payload['textContent'] = $textBody;
        }

        try {
            $response = Http::withHeaders([
                'api-key' => $this->apiKey,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->timeout(30)->post($this->apiUrl, $payload);

            if ($response->successful()) {
                Log::info("Brevo email sent to {$toEmail}: {$subject}");
                return true;
            }

            Log::error("Brevo API error ({$response->status()}): " . $response->body());
            return false;
        } catch (\Throwable $e) {
            Log::error("Brevo API exception: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send a verification code email.
     */
    public function sendVerificationCode(string $toEmail, string $toName, string $code): bool
    {
        $subject = 'Verify Your Email - Prism AI';
        $html = view('emails.verification-code', [
            'user' => (object) ['name' => $toName, 'email' => $toEmail],
            'code' => $code,
        ])->render();

        return $this->send($toEmail, $toName, $subject, $html);
    }
}
