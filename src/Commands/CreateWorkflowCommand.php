<?php

namespace AshiqFardus\ApprovalProcess\Commands;

use AshiqFardus\ApprovalProcess\Models\Workflow;
use AshiqFardus\ApprovalProcess\Models\ApprovalStep;
use AshiqFardus\ApprovalProcess\Models\Approver;
use Illuminate\Console\Command;

class CreateWorkflowCommand extends Command
{
    protected $signature = 'approval:create-workflow';
    protected $description = 'Create a new approval workflow interactively';

    public function handle()
    {
        $this->info('🚀 Creating a new approval workflow...');
        $this->newLine();

        // Get workflow details
        $name = $this->ask('Workflow name');
        $description = $this->ask('Description (optional)');
        $modelType = $this->ask('Model class (e.g., App\\Models\\Offer)');

        // Create workflow
        $workflow = Workflow::create([
            'name' => $name,
            'description' => $description,
            'model_type' => $modelType,
            'is_active' => true,
        ]);

        $this->info("✅ Workflow created: {$workflow->name}");
        $this->newLine();

        // Add steps
        $addSteps = $this->confirm('Add approval steps?', true);
        $sequence = 1;

        while ($addSteps) {
            $this->info("Adding Step {$sequence}:");
            
            $stepName = $this->ask('Step name (e.g., Manager Approval)');
            $approvalType = $this->choice(
                'Approval type',
                ['serial', 'parallel', 'any-one'],
                0
            );
            $slaHours = $this->ask('SLA hours (optional)', 24);

            $step = ApprovalStep::create([
                'workflow_id' => $workflow->id,
                'name' => $stepName,
                'sequence' => $sequence,
                'approval_type' => $approvalType,
                'sla_hours' => $slaHours,
                'is_active' => true,
            ]);

            // Add approvers
            $addApprovers = $this->confirm('Add approvers to this step?', true);
            
            while ($addApprovers) {
                $approverType = $this->choice(
                    'Approver type',
                    ['user', 'role', 'manager', 'department_head', 'position'],
                    1
                );

                $approverId = $this->ask("Approver ID/value (e.g., 'manager' for role)");
                $weightage = $this->ask('Weightage (1-100)', 100);

                Approver::create([
                    'approval_step_id' => $step->id,
                    'approver_type' => $approverType,
                    'approver_id' => $approverId,
                    'weightage' => $weightage,
                ]);

                $this->info("✅ Approver added");
                $addApprovers = $this->confirm('Add another approver to this step?', false);
            }

            $sequence++;
            $addSteps = $this->confirm('Add another step?', false);
        }

        $this->newLine();
        $this->info('🎉 Workflow created successfully!');
        $this->table(
            ['ID', 'Name', 'Model', 'Steps'],
            [[
                $workflow->id,
                $workflow->name,
                $workflow->model_type,
                $workflow->steps()->count()
            ]]
        );

        return Command::SUCCESS;
    }
}
