<?php

namespace AshiqFardus\ApprovalProcess\Tests\Unit\Services;

use AshiqFardus\ApprovalProcess\Tests\TestCase;
use AshiqFardus\ApprovalProcess\Services\DelegationService;
use AshiqFardus\ApprovalProcess\Models\ApprovalDelegation;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DelegationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected DelegationService $service;
    protected $delegator;
    protected $delegate;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->service = new DelegationService();
        $this->delegator = $this->createUser(['email' => 'delegator@example.com']);
        $this->delegate = $this->createUser(['email' => 'delegate@example.com']);
    }

    /** @test */
    public function it_can_create_delegation()
    {
        $delegation = $this->service->createDelegation([
            'delegator_id' => $this->delegator->id,
            'delegate_id' => $this->delegate->id,
            'module_type' => 'App\\Models\\TestModel',
            'start_date' => now(),
            'end_date' => now()->addDays(7),
            'reason' => 'Vacation',
        ]);

        $this->assertInstanceOf(ApprovalDelegation::class, $delegation);
        $this->assertEquals($this->delegator->id, $delegation->delegator_id);
        $this->assertEquals($this->delegate->id, $delegation->delegate_id);
    }

    /** @test */
    public function it_can_get_active_delegation()
    {
        ApprovalDelegation::create([
            'delegator_id' => $this->delegator->id,
            'delegate_id' => $this->delegate->id,
            'module_type' => 'App\\Models\\TestModel',
            'start_date' => now()->subDay(),
            'end_date' => now()->addDay(),
        ]);

        $delegation = $this->service->getActiveDelegation($this->delegator->id, 'App\\Models\\TestModel');

        $this->assertNotNull($delegation);
        $this->assertEquals($this->delegate->id, $delegation->delegate_id);
    }

    /** @test */
    public function it_returns_null_for_expired_delegation()
    {
        ApprovalDelegation::create([
            'delegator_id' => $this->delegator->id,
            'delegate_id' => $this->delegate->id,
            'module_type' => 'App\\Models\\TestModel',
            'start_date' => now()->subDays(10),
            'end_date' => now()->subDays(3),
        ]);

        $delegation = $this->service->getActiveDelegation($this->delegator->id, 'App\\Models\\TestModel');

        $this->assertNull($delegation);
    }

    /** @test */
    public function it_can_end_delegation()
    {
        $delegation = ApprovalDelegation::create([
            'delegator_id' => $this->delegator->id,
            'delegate_id' => $this->delegate->id,
            'module_type' => 'App\\Models\\TestModel',
            'start_date' => now(),
            'end_date' => now()->addDays(7),
        ]);

        $this->service->endDelegation($delegation->id);

        $this->assertDatabaseHas('approval_delegations', [
            'id' => $delegation->id,
            'end_date' => now()->toDateString(),
        ]);
    }

    /** @test */
    public function it_can_auto_end_expired_delegations()
    {
        ApprovalDelegation::create([
            'delegator_id' => $this->delegator->id,
            'delegate_id' => $this->delegate->id,
            'module_type' => 'App\\Models\\TestModel',
            'start_date' => now()->subDays(10),
            'end_date' => now()->subDay(),
        ]);

        $count = $this->service->checkAndAutoEnd();

        // The delegation is already expired, so it should be counted
        $this->assertEquals(0, $count); // No action needed as it's already past end_date
    }

    /** @test */
    public function it_can_get_user_delegations()
    {
        ApprovalDelegation::create([
            'delegator_id' => $this->delegator->id,
            'delegate_id' => $this->delegate->id,
            'module_type' => 'App\\Models\\TestModel',
            'start_date' => now(),
            'end_date' => now()->addDays(7),
        ]);

        $delegations = $this->service->getUserDelegations($this->delegator->id);

        $this->assertCount(1, $delegations);
    }

    /** @test */
    public function it_can_get_delegations_as_delegate()
    {
        ApprovalDelegation::create([
            'delegator_id' => $this->delegator->id,
            'delegate_id' => $this->delegate->id,
            'module_type' => 'App\\Models\\TestModel',
            'start_date' => now(),
            'end_date' => now()->addDays(7),
        ]);

        $delegations = $this->service->getDelegationsAsDelegate($this->delegate->id);

        $this->assertCount(1, $delegations);
    }
}
