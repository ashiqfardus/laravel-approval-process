<?php

namespace AshiqFardus\ApprovalProcess\Tests\Factories;

use AshiqFardus\ApprovalProcess\Models\Approver;
use AshiqFardus\ApprovalProcess\Models\ApprovalStep;
use Illuminate\Database\Eloquent\Factories\Factory;

class ApproverFactory extends Factory
{
    protected $model = Approver::class;

    public function definition(): array
    {
        return [
            'approval_step_id' => ApprovalStep::factory(),
            'approver_type' => 'user',
            'approver_id' => null,
            'user_id' => 1,
            'is_approved' => false,
            'approval_at' => null,
            'sequence' => 1,
            'weightage' => 100,
        ];
    }

    public function approved(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'is_approved' => true,
                'approval_at' => now(),
            ];
        });
    }
}
