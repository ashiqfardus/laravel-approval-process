<?php

namespace AshiqFardus\ApprovalProcess\Commands;

use AshiqFardus\ApprovalProcess\Services\EscalationService;
use Illuminate\Console\Command;

class SendRemindersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'approval:send-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send reminder notifications for pending approvals';

    /**
     * Execute the console command.
     */
    public function handle(EscalationService $escalationService): int
    {
        $this->info('Sending approval reminders...');

        $sent = $escalationService->sendReminders();

        if ($sent > 0) {
            $this->info("Sent {$sent} reminder(s)");
        } else {
            $this->info('No reminders to send');
        }

        return Command::SUCCESS;
    }
}
