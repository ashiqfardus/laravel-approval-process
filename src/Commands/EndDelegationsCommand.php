<?php

namespace AshiqFardus\ApprovalProcess\Commands;

use AshiqFardus\ApprovalProcess\Services\DelegationService;
use Illuminate\Console\Command;

class EndDelegationsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'approval:end-delegations';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'End expired approval delegations';

    /**
     * Execute the console command.
     */
    public function handle(DelegationService $delegationService): int
    {
        $this->info('Checking for expired delegations...');

        $ended = $delegationService->checkAndAutoEnd();

        if ($ended > 0) {
            $this->info("Ended {$ended} expired delegation(s)");
        } else {
            $this->info('No expired delegations found');
        }

        return Command::SUCCESS;
    }
}
