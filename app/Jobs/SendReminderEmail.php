<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendReminderEmail implements ShouldQueue
{
    use Queueable;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var int
     */
    public $backoff = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public User $user,
        public string $reminderType,
        public string $subject,
        public string $message
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Configure mail settings from .env
            $this->configureMailSettings();

            Log::info("Queue: Sending {$this->reminderType} reminder to: {$this->user->Email}");

            Mail::raw($this->message, function ($mail) {
                $mail->to($this->user->Email)
                    ->subject($this->subject);
            });

            Log::info("Queue: Successfully sent {$this->reminderType} reminder to: {$this->user->Email}");

        } catch (\Exception $e) {
            Log::error("Queue: Failed to send {$this->reminderType} reminder to {$this->user->Email}: " . $e->getMessage());
            throw $e; // Re-throw to trigger retry
        }
    }

    /**
     * Configure mail settings from .env
     */
    private function configureMailSettings()
    {
        $emailFromAddress = env('MAIL_USERNAME', env('MAIL_FROM_ADDRESS', ''));
        $emailPassword = env('MAIL_PASSWORD', '');
        $smtpHost = env('MAIL_HOST', 'smtp.gmail.com');
        $smtpPort = (int) env('MAIL_PORT', 587);
        $enableSsl = env('MAIL_ENCRYPTION', 'tls') === 'tls';

        if (empty($emailFromAddress) || empty($emailPassword)) {
            throw new \Exception('Email settings are not configured properly in .env file.');
        }

        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.transport' => 'smtp',
            'mail.mailers.smtp.host' => $smtpHost,
            'mail.mailers.smtp.port' => $smtpPort,
            'mail.mailers.smtp.encryption' => $enableSsl ? 'tls' : null,
            'mail.mailers.smtp.username' => $emailFromAddress,
            'mail.mailers.smtp.password' => $emailPassword,
            'mail.mailers.smtp.timeout' => 30,
            'mail.from.address' => $emailFromAddress,
            'mail.from.name' => 'Employee Attendance System',
        ]);

        // Re-create mail manager to use new config
        app()->forgetInstance('mail.manager');
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("Queue: Job permanently failed for {$this->user->Email} after {$this->tries} attempts: " . $exception->getMessage());
    }
}
