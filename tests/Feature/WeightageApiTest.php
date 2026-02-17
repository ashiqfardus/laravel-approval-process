<?php

namespace AshiqFardus\ApprovalProcess\Tests\Feature;

use AshiqFardus\ApprovalProcess\Models\ApprovalStep;
use AshiqFardus\ApprovalProcess\Models\ApprovalRequest;
use AshiqFardus\ApprovalProcess\Models\Approver;
use AshiqFardus\ApprovalProcess\Models\Workflow;
use AshiqFardus\ApprovalProcess\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class WeightageApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware();
    }

    /** @test */
    public function it_gets_step_weightage_breakdown()
    {
        $step = $this->createStepWithApprovers([
            ['weightage' => 50, 'is_approved' => true],
            ['weightage' => 30, 'is_approved' => false],
            ['weightage' => 20, 'is_approved' => false],
        ], ['minimum_approval_percentage' => 75]);

        $response = $this->getJson("/api/approval-process/steps/{$step->id}/weightage/breakdown");

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'total_weightage' => 100,
                    'approved_weightage' => 50,
                    'pending_weightage' => 50,
                    'current_percentage' => 50.0,
                    'minimum_percentage' => 75,
                    'is_complete' => false,
                    'remaining_percentage' => 25,
                ],
            ]);
    }

    /** @test */
    public function it_gets_request_weightage_breakdown()
    {
        $workflow = Workflow::factory()->create();
        $step = ApprovalStep::factory()->create([
            'workflow_id' => $workflow->id,
            'minimum_approval_percentage' => 60,
        ]);
        
        Approver::factory()->create([
            'approval_step_id' => $step->id,
            'weightage' => 70,
            'is_approved' => true,
        ]);

        $request = ApprovalRequest::factory()->create([
            'workflow_id' => $workflow->id,
            'current_step_id' => $step->id,
        ]);

        $response = $this->getJson("/api/approval-process/requests/{$request->id}/weightage/breakdown");

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'current_percentage' => 100.0,
                    'is_complete' => true,
                ],
            ]);
    }

    /** @test */
    public function it_gets_remaining_approvals_needed()
    {
        $step = $this->createStepWithApprovers([
            ['weightage' => 50, 'is_approved' => true],
            ['weightage' => 30, 'is_approved' => false],
            ['weightage' => 20, 'is_approved' => false],
        ], ['minimum_approval_percentage' => 75]);

        $response = $this->getJson("/api/approval-process/steps/{$step->id}/weightage/remaining");

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'is_complete' => false,
                    'remaining_percentage' => 25,
                    'minimum_approvers_needed' => 1,
                ],
            ]);
    }

    /** @test */
    public function it_updates_minimum_approval_percentage()
    {
        $step = ApprovalStep::factory()->create([
            'minimum_approval_percentage' => 100,
        ]);

        $response = $this->putJson("/api/approval-process/steps/{$step->id}/weightage/minimum-percentage", [
            'minimum_percentage' => 75,
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Minimum approval percentage updated successfully',
                'data' => [
                    'minimum_approval_percentage' => 75,
                ],
            ]);

        $this->assertDatabaseHas('approval_steps', [
            'id' => $step->id,
            'minimum_approval_percentage' => 75,
        ]);
    }

    /** @test */
    public function it_validates_minimum_percentage_range()
    {
        $step = ApprovalStep::factory()->create();

        $response = $this->putJson("/api/approval-process/steps/{$step->id}/weightage/minimum-percentage", [
            'minimum_percentage' => 150, // Invalid
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Validation failed',
            ]);
    }

    /** @test */
    public function it_updates_approver_weightage()
    {
        $approver = Approver::factory()->create(['weightage' => 50]);

        $response = $this->putJson("/api/approval-process/approvers/{$approver->id}/weightage", [
            'weightage' => 70,
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Approver weightage updated successfully',
                'data' => [
                    'weightage' => 70,
                ],
            ]);

        $this->assertDatabaseHas('approval_approvers', [
            'id' => $approver->id,
            'weightage' => 70,
        ]);
    }

    /** @test */
    public function it_bulk_updates_weightages()
    {
        $step = ApprovalStep::factory()->create();
        $approver1 = Approver::factory()->create(['approval_step_id' => $step->id, 'weightage' => 33]);
        $approver2 = Approver::factory()->create(['approval_step_id' => $step->id, 'weightage' => 33]);
        $approver3 = Approver::factory()->create(['approval_step_id' => $step->id, 'weightage' => 34]);

        $response = $this->putJson("/api/approval-process/steps/{$step->id}/weightage/bulk-update", [
            'approvers' => [
                ['id' => $approver1->id, 'weightage' => 50],
                ['id' => $approver2->id, 'weightage' => 30],
                ['id' => $approver3->id, 'weightage' => 20],
            ],
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Weightages updated successfully',
            ]);

        $this->assertDatabaseHas('approval_approvers', ['id' => $approver1->id, 'weightage' => 50]);
        $this->assertDatabaseHas('approval_approvers', ['id' => $approver2->id, 'weightage' => 30]);
        $this->assertDatabaseHas('approval_approvers', ['id' => $approver3->id, 'weightage' => 20]);
    }

    /** @test */
    public function it_validates_weightage_distribution()
    {
        $step = $this->createStepWithApprovers([
            ['weightage' => 50],
            ['weightage' => 30],
            ['weightage' => 20],
        ]);

        $response = $this->postJson("/api/approval-process/steps/{$step->id}/weightage/validate");

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'is_valid' => true,
                    'total_weightage' => 100,
                ],
            ]);
    }

    /** @test */
    public function it_suggests_equal_distribution()
    {
        $response = $this->postJson('/api/approval-process/weightage/suggest', [
            'approver_count' => 3,
            'strategy' => 'equal',
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'strategy' => 'equal',
                    'approver_count' => 3,
                    'total' => 100,
                ],
            ]);

        $distribution = $response->json('data.distribution');
        $this->assertCount(3, $distribution);
        $this->assertEquals(100, array_sum($distribution));
    }

    /** @test */
    public function it_suggests_hierarchical_distribution()
    {
        $response = $this->postJson('/api/approval-process/weightage/suggest', [
            'approver_count' => 3,
            'strategy' => 'hierarchical',
        ]);

        $response->assertOk();
        
        $distribution = $response->json('data.distribution');
        $this->assertCount(3, $distribution);
        $this->assertEquals(100, array_sum($distribution));
        $this->assertTrue($distribution[0] > $distribution[1]);
    }

    /** @test */
    public function it_suggests_majority_one_distribution()
    {
        $response = $this->postJson('/api/approval-process/weightage/suggest', [
            'approver_count' => 3,
            'strategy' => 'majority-one',
        ]);

        $response->assertOk();
        
        $distribution = $response->json('data.distribution');
        $this->assertEquals(51, $distribution[0]); // First has majority
    }

    /** @test */
    public function it_gets_approver_percentages()
    {
        $step = $this->createStepWithApprovers([
            ['weightage' => 50],
            ['weightage' => 30],
            ['weightage' => 20],
        ]);

        $response = $this->getJson("/api/approval-process/steps/{$step->id}/weightage/percentages");

        $response->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => ['id', 'user_id', 'weightage', 'percentage', 'is_approved'],
                ],
            ]);

        $percentages = $response->json('data');
        $this->assertEquals(50.0, $percentages[0]['percentage']);
        $this->assertEquals(30.0, $percentages[1]['percentage']);
        $this->assertEquals(20.0, $percentages[2]['percentage']);
    }

    /**
     * Helper method to create a step with approvers.
     */
    protected function createStepWithApprovers(array $approvers, array $stepAttributes = []): ApprovalStep
    {
        $step = ApprovalStep::factory()->create(array_merge([
            'minimum_approval_percentage' => 100,
        ], $stepAttributes));

        foreach ($approvers as $approverData) {
            Approver::factory()->create(array_merge([
                'approval_step_id' => $step->id,
            ], $approverData));
        }

        return $step->fresh(['approvers']);
    }
}
