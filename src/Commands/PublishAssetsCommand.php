<?php

namespace AshiqFardus\ApprovalProcess\Commands;

use Illuminate\Console\Command;
use ApprovalWorkflow\ApprovalProcess\Models\ApprovalRequest;

class PublishAssetsCommand extends Command
{
    protected $signature = 'approval:publish-assets';
    protected $description = 'Publish approval process assets to public directory';

    public function handle(): int
    {
        $this->call('vendor:publish', [
            '--provider' => 'ApprovalWorkflow\ApprovalProcess\ApprovalProcessServiceProvider',
            '--tag' => 'approval-process-assets',
            '--force' => true,
        ]);

        $this->info('Approval process assets published successfully!');
        return self::SUCCESS;
    }
}
