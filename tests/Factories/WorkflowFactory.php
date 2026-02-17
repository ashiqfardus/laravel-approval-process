<?php

namespace AshiqFardus\ApprovalProcess\Tests\Factories;

use AshiqFardus\ApprovalProcess\Models\Workflow;
use Illuminate\Database\Eloquent\Factories\Factory;

class WorkflowFactory extends Factory
{
    protected $model = Workflow::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->sentence(),
            'model_type' => 'App\\Models\\Document',
            'is_active' => true,
        ];
    }
}
