<?php

namespace AshiqFardus\ApprovalProcess\Tests\Unit\Services;

use AshiqFardus\ApprovalProcess\Models\ApprovalStep;
use AshiqFardus\ApprovalProcess\Models\Approver;
use AshiqFardus\ApprovalProcess\Services\WeightageCalculator;
use AshiqFardus\ApprovalProcess\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class WeightageCalculatorTest extends TestCase
{
    use RefreshDatabase;

    protected WeightageCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new WeightageCalculator();
    }

    /** @test */
    public function it_calculates_current_percentage_correctly()
    {
        $step = $this->createStepWithApprovers([
            ['weightage' => 50, 'is_approved' => true],
            ['weightage' => 30, 'is_approved' => false],
            ['weightage' => 20, 'is_approved' => false],
        ]);

        $percentage = $this->calculator->calculateCurrentPercentage($step);

        $this->assertEquals(50.0, $percentage);
    }

    /** @test */
    public function it_calculates_percentage_with_multiple_approvals()
    {
        $step = $this->createStepWithApprovers([
            ['weightage' => 50, 'is_approved' => true],
            ['weightage' => 30, 'is_approved' => true],
            ['weightage' => 20, 'is_approved' => false],
        ]);

        $percentage = $this->calculator->calculateCurrentPercentage($step);

        $this->assertEquals(80.0, $percentage);
    }

    /** @test */
    public function it_returns_zero_for_no_approvers()
    {
        $step = ApprovalStep::factory()->create();

        $percentage = $this->calculator->calculateCurrentPercentage($step);

        $this->assertEquals(0, $percentage);
    }

    /** @test */
    public function it_checks_if_minimum_percentage_reached()
    {
        $step = $this->createStepWithApprovers([
            ['weightage' => 50, 'is_approved' => true],
            ['weightage' => 30, 'is_approved' => true],
            ['weightage' => 20, 'is_approved' => false],
        ], ['minimum_approval_percentage' => 75]);

        $hasReached = $this->calculator->hasReachedMinimumPercentage($step);

        $this->assertTrue($hasReached); // 80% >= 75%
    }

    /** @test */
    public function it_checks_if_minimum_percentage_not_reached()
    {
        $step = $this->createStepWithApprovers([
            ['weightage' => 50, 'is_approved' => true],
            ['weightage' => 30, 'is_approved' => false],
            ['weightage' => 20, 'is_approved' => false],
        ], ['minimum_approval_percentage' => 75]);

        $hasReached = $this->calculator->hasReachedMinimumPercentage($step);

        $this->assertFalse($hasReached); // 50% < 75%
    }

    /** @test */
    public function it_provides_detailed_approval_breakdown()
    {
        $step = $this->createStepWithApprovers([
            ['weightage' => 50, 'is_approved' => true],
            ['weightage' => 30, 'is_approved' => false],
            ['weightage' => 20, 'is_approved' => false],
        ], ['minimum_approval_percentage' => 75]);

        $breakdown = $this->calculator->getApprovalBreakdown($step);

        $this->assertEquals(100, $breakdown['total_weightage']);
        $this->assertEquals(50, $breakdown['approved_weightage']);
        $this->assertEquals(50, $breakdown['pending_weightage']);
        $this->assertEquals(50.0, $breakdown['current_percentage']);
        $this->assertEquals(75, $breakdown['minimum_percentage']);
        $this->assertFalse($breakdown['is_complete']);
        $this->assertEquals(25, $breakdown['remaining_percentage']);
        $this->assertCount(3, $breakdown['approvers']);
    }

    /** @test */
    public function it_validates_weightage_distribution()
    {
        $approvers = [
            ['weightage' => 50],
            ['weightage' => 30],
            ['weightage' => 20],
        ];

        $validation = $this->calculator->validateWeightageDistribution($approvers);

        $this->assertTrue($validation['is_valid']);
        $this->assertEquals(100, $validation['total_weightage']);
        $this->assertEmpty($validation['errors']);
    }

    /** @test */
    public function it_detects_zero_total_weightage_error()
    {
        $approvers = [
            ['weightage' => 0],
            ['weightage' => 0],
        ];

        $validation = $this->calculator->validateWeightageDistribution($approvers);

        $this->assertFalse($validation['is_valid']);
        $this->assertNotEmpty($validation['errors']);
        $this->assertStringContainsString('cannot be 0', $validation['errors'][0]);
    }

    /** @test */
    public function it_detects_negative_weightage_error()
    {
        $approvers = [
            ['weightage' => 50],
            ['weightage' => -10],
        ];

        $validation = $this->calculator->validateWeightageDistribution($approvers);

        $this->assertFalse($validation['is_valid']);
        $this->assertNotEmpty($validation['errors']);
    }

    /** @test */
    public function it_warns_about_non_100_total()
    {
        $approvers = [
            ['weightage' => 60],
            ['weightage' => 30],
        ];

        $validation = $this->calculator->validateWeightageDistribution($approvers);

        $this->assertTrue($validation['is_valid']);
        $this->assertEquals(90, $validation['total_weightage']);
        $this->assertNotEmpty($validation['warnings']);
    }

    /** @test */
    public function it_suggests_equal_distribution()
    {
        $distribution = $this->calculator->suggestWeightageDistribution(3, 'equal');

        $this->assertCount(3, $distribution);
        $this->assertEquals(100, array_sum($distribution));
        $this->assertEquals([34, 33, 33], $distribution);
    }

    /** @test */
    public function it_suggests_hierarchical_distribution()
    {
        $distribution = $this->calculator->suggestWeightageDistribution(3, 'hierarchical');

        $this->assertCount(3, $distribution);
        $this->assertEquals(100, array_sum($distribution));
        $this->assertTrue($distribution[0] > $distribution[1]);
        $this->assertTrue($distribution[1] > $distribution[2]);
    }

    /** @test */
    public function it_suggests_majority_one_distribution()
    {
        $distribution = $this->calculator->suggestWeightageDistribution(3, 'majority-one');

        $this->assertCount(3, $distribution);
        $this->assertEquals(100, array_sum($distribution));
        $this->assertEquals(51, $distribution[0]); // First has majority
    }

    /** @test */
    public function it_calculates_approver_percentages()
    {
        $step = $this->createStepWithApprovers([
            ['weightage' => 50, 'is_approved' => true],
            ['weightage' => 30, 'is_approved' => false],
            ['weightage' => 20, 'is_approved' => false],
        ]);

        $percentages = $this->calculator->getApproverPercentages($step);

        $this->assertCount(3, $percentages);
        $this->assertEquals(50.0, $percentages[0]['percentage']);
        $this->assertEquals(30.0, $percentages[1]['percentage']);
        $this->assertEquals(20.0, $percentages[2]['percentage']);
    }

    /** @test */
    public function it_calculates_remaining_approvals_needed()
    {
        $step = $this->createStepWithApprovers([
            ['weightage' => 50, 'is_approved' => true],
            ['weightage' => 30, 'is_approved' => false],
            ['weightage' => 20, 'is_approved' => false],
        ], ['minimum_approval_percentage' => 75]);

        $remaining = $this->calculator->getRemainingApprovalsNeeded($step);

        $this->assertFalse($remaining['is_complete']);
        $this->assertEquals(25, $remaining['remaining_percentage']);
        $this->assertCount(2, $remaining['possible_approvers']);
        $this->assertEquals(1, $remaining['minimum_approvers_needed']); // Just need the 30% approver
    }

    /** @test */
    public function it_shows_complete_when_minimum_reached()
    {
        $step = $this->createStepWithApprovers([
            ['weightage' => 50, 'is_approved' => true],
            ['weightage' => 30, 'is_approved' => true],
            ['weightage' => 20, 'is_approved' => false],
        ], ['minimum_approval_percentage' => 75]);

        $remaining = $this->calculator->getRemainingApprovalsNeeded($step);

        $this->assertTrue($remaining['is_complete']);
        $this->assertEquals(0, $remaining['remaining_percentage']);
    }

    /** @test */
    public function it_handles_100_percent_requirement()
    {
        $step = $this->createStepWithApprovers([
            ['weightage' => 50, 'is_approved' => true],
            ['weightage' => 30, 'is_approved' => true],
            ['weightage' => 20, 'is_approved' => false],
        ], ['minimum_approval_percentage' => 100]);

        $hasReached = $this->calculator->hasReachedMinimumPercentage($step);

        $this->assertFalse($hasReached); // 80% < 100%
    }

    /** @test */
    public function it_handles_51_percent_requirement()
    {
        $step = $this->createStepWithApprovers([
            ['weightage' => 51, 'is_approved' => true],
            ['weightage' => 25, 'is_approved' => false],
            ['weightage' => 24, 'is_approved' => false],
        ], ['minimum_approval_percentage' => 51]);

        $hasReached = $this->calculator->hasReachedMinimumPercentage($step);

        $this->assertTrue($hasReached); // 51% >= 51%
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
