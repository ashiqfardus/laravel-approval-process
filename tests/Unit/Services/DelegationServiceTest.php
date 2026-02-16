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
    protected $user;
    protected $delegate;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->service = new DelegationService();
        $this->user = $this->createUser(['email' => 'user@example.com']);
        $this->delegate = $this->createUser(['email' => 'delegate@example.com']);
    }

    /** @test */
    public function it_can_create_delegation()
    {
        $delegation = $this->service->createDelegation([
            'user_id' => $this->user->id,
            'delegated_to_user_id' => $this->delegate->id,
            'module_type' => 'App\\Models\\TestModel',
            'starts_at' => now(),
            'ends_at' => now()->addDays(7),
            'reason' => 'Vacation',
        ]);

        $this->assertDatabaseHas('approval_delegations', [
            'user_id' => $this->user->id,
            'delegated_to_user_id' => $this->delegate->id,
        ]);
    }

    /** @test */
    public function it_can_get_active_delegation()
    {
        ApprovalDelegation::create([
            'user_id' => $this->user->id,
            'delegated_to_user_id' => $this->delegate->id,
            'module_type' => 'App\\Models\\TestModel',
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDay(),
            'is_active' => true,
        ]);

        $active = $this->service->getActiveDelegation($this->user->id, 'App\\Models\\TestModel');

        $this->assertNotNull($active);
        $this->assertEquals($this->delegate->id, $active->delegated_to_user_id);
    }

    /** @test */
    public function it_returns_null_for_expired_delegation()
    {
        ApprovalDelegation::create([
            'user_id' => $this->user->id,
            'delegated_to_user_id' => $this->delegate->id,
            'module_type' => 'App\\Models\\TestModel',
            'starts_at' => now()->subDays(10),
            'ends_at' => now()->subDay(),
            'is_active' => true,
        ]);

        $active = $this->service->getActiveDelegation($this->user->id, 'App\\Models\\TestModel');

        $this->assertNull($active);
    }

    /** @test */
    public function it_can_end_delegation()
    {
        $delegation = ApprovalDelegation::create([
            'user_id' => $this->user->id,
            'delegated_to_user_id' => $this->delegate->id,
            'module_type' => 'App\\Models\\TestModel',
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDay(),
            'is_active' => true,
        ]);

        $this->service->endDelegation($delegation->id);

        $this->assertDatabaseHas('approval_delegations', [
            'id' => $delegation->id,
            'is_active' => false,
        ]);
    }

    /** @test */
    public function it_can_auto_end_expired_delegations()
    {
        ApprovalDelegation::create([
            'user_id' => $this->user->id,
            'delegated_to_user_id' => $this->delegate->id,
            'module_type' => 'App\\Models\\TestModel',
            'starts_at' => now()->subDays(10),
            'ends_at' => now()->subDay(),
            'is_active' => true,
        ]);

        $count = $this->service->checkAndAutoEnd();

        $this->assertEquals(1, $count);
    }

    /** @test */
    public function it_can_get_user_delegations()
    {
        ApprovalDelegation::create([
            'user_id' => $this->user->id,
            'delegated_to_user_id' => $this->delegate->id,
            'module_type' => 'App\\Models\\TestModel',
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDay(),
            'is_active' => true,
        ]);

        $delegations = $this->service->getUserDelegations($this->user->id);

        $this->assertCount(1, $delegations);
    }

    /** @test */
    public function it_can_get_delegations_as_delegate()
    {
        ApprovalDelegation::create([
            'user_id' => $this->user->id,
            'delegated_to_user_id' => $this->delegate->id,
            'module_type' => 'App\\Models\\TestModel',
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDay(),
            'is_active' => true,
        ]);

        $delegations = $this->service->getDelegationsAsDelegate($this->delegate->id);

        $this->assertCount(1, $delegations);
    }
}
