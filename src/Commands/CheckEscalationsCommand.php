<?php

namespace AshiqFardus\ApprovalProcess\Commands;

use AshiqFardus\ApprovalProcess\Services\EscalationService;
use Illuminate\Console\Command;

class CheckEscalationsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'approval:check-escalations';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for overdue approvals and escalate them';

    /**
     * Execute the console command.
     */
    public function handle(EscalationService $escalationService): int
    {
        $this->info('Checking for overdue approvals...');

        $escalated = $escalationService->checkOverdueApprovals();

        if ($escalated > 0) {
            $this->info("Escalated {$escalated} approval request(s)");
        } else {
            $this->info('No overdue approvals found');
        }

        return Command::SUCCESS;
    }
}
