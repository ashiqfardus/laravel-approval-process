<?php

namespace AshiqFardus\ApprovalProcess\Tests\Factories;

use AshiqFardus\ApprovalProcess\Models\ApprovalRequest;
use AshiqFardus\ApprovalProcess\Models\Workflow;
use Illuminate\Database\Eloquent\Factories\Factory;

class ApprovalRequestFactory extends Factory
{
    protected $model = ApprovalRequest::class;

    public function definition(): array
    {
        return [
            'workflow_id' => Workflow::factory(),
            'current_step_id' => null,
            'current_approval_percentage' => 0,
            'requestable_type' => 'App\\Models\\Document',
            'requestable_id' => 1,
            'requested_by_user_id' => 1,
            'status' => ApprovalRequest::STATUS_SUBMITTED,
            'data_snapshot' => ['test' => 'data'],
            'submitted_at' => now(),
            'metadata' => [],
        ];
    }
}
