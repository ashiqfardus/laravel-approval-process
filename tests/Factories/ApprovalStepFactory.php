<?php

namespace AshiqFardus\ApprovalProcess\Tests\Factories;

use AshiqFardus\ApprovalProcess\Models\ApprovalStep;
use AshiqFardus\ApprovalProcess\Models\Workflow;
use Illuminate\Database\Eloquent\Factories\Factory;

class ApprovalStepFactory extends Factory
{
    protected $model = ApprovalStep::class;

    public function definition(): array
    {
        return [
            'workflow_id' => Workflow::factory(),
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->sentence(),
            'sequence' => $this->faker->numberBetween(1, 10),
            'approval_type' => $this->faker->randomElement(['serial', 'parallel', 'any-one']),
            'level_alias' => $this->faker->words(2, true),
            'allow_edit' => false,
            'allow_send_back' => true,
            'is_active' => true,
            'sla_hours' => $this->faker->numberBetween(24, 168),
            'allows_delegation' => true,
            'allows_partial_approval' => false,
            'minimum_approval_percentage' => 100,
        ];
    }
}
