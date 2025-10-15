<?php

namespace App\Console\Commands;

use App\Services\LoginAttemptService;
use Illuminate\Console\Command;

class UnlockAccount extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'auth:unlock {email : Email адреса корисника}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Откључава налог корисника брисањем неуспелих покушаја пријављивања';

    /**
     * Execute the console command.
     */
    public function handle(LoginAttemptService $loginAttemptService): int
    {
        $email = $this->argument('email');

        // Check if account is locked
        $attempts = $loginAttemptService->getRecentAttempts($email);

        if ($attempts === 0) {
            $this->info("✓ Налог '{$email}' није закључан.");
            return self::SUCCESS;
        }

        // Clear failed attempts
        $loginAttemptService->clearAttempts($email);

        $this->info("✓ Налог '{$email}' је успешно откључан!");
        $this->line("  Број обрисаних неуспелих покушаја: {$attempts}");

        return self::SUCCESS;
    }
}
