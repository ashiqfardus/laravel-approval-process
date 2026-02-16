<?php

namespace AshiqFardus\ApprovalProcess\Commands;

use AshiqFardus\ApprovalProcess\Models\Workflow;
use Illuminate\Console\Command;

class ListWorkflowsCommand extends Command
{
    protected $signature = 'approval:list-workflows {--inactive : Show inactive workflows}';
    protected $description = 'List all approval workflows';

    public function handle()
    {
        $showInactive = $this->option('inactive');

        $query = Workflow::with('steps.approvers');
        
        if (!$showInactive) {
            $query->where('is_active', true);
        }

        $workflows = $query->get();

        if ($workflows->isEmpty()) {
            $this->warn('No workflows found.');
            return Command::SUCCESS;
        }

        $this->info('📋 Approval Workflows:');
        $this->newLine();

        foreach ($workflows as $workflow) {
            $status = $workflow->is_active ? '✅ Active' : '❌ Inactive';
            
            $this->line("<fg=cyan>ID:</> {$workflow->id}");
            $this->line("<fg=cyan>Name:</> {$workflow->name}");
            $this->line("<fg=cyan>Model:</> {$workflow->model_type}");
            $this->line("<fg=cyan>Status:</> {$status}");
            $this->line("<fg=cyan>Steps:</> {$workflow->steps->count()}");

            if ($workflow->steps->isNotEmpty()) {
                $this->newLine();
                $this->line('  <fg=yellow>Steps:</>');
                
                foreach ($workflow->steps as $step) {
                    $approvers = $step->approvers->count();
                    $this->line("    {$step->sequence}. {$step->name} ({$step->approval_type}) - {$approvers} approver(s)");
                }
            }

            $this->newLine();
            $this->line(str_repeat('-', 60));
            $this->newLine();
        }

        return Command::SUCCESS;
    }
}
