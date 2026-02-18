<?php

namespace AshiqFardus\ApprovalProcess\View\Components;

use Illuminate\View\Component;
use AshiqFardus\ApprovalProcess\Models\Workflow;
use AshiqFardus\ApprovalProcess\Models\ApprovableEntity;
use AshiqFardus\ApprovalProcess\Models\ApprovalRequest;

class AdminPanel extends Component
{
    public $stats;
    public $workflows;
    public $entities;
    public $recentRequests;

    public function __construct()
    {
        $this->loadStats();
        $this->workflows = Workflow::with('steps')->latest()->take(5)->get();
        $this->entities = ApprovableEntity::where('is_active', true)->latest()->take(5)->get();
        $this->recentRequests = ApprovalRequest::with(['workflow', 'requester'])->latest()->take(10)->get();
    }

    private function loadStats()
    {
        $this->stats = [
            'total_workflows' => Workflow::count(),
            'active_workflows' => Workflow::where('is_active', true)->count(),
            'total_entities' => ApprovableEntity::where('is_active', true)->count(),
            'pending_requests' => ApprovalRequest::where('status', 'pending')->count(),
            'total_requests' => ApprovalRequest::count(),
            'approved_today' => ApprovalRequest::where('status', 'approved')
                ->whereDate('updated_at', today())
                ->count(),
        ];
    }

    public function render()
    {
        return view('approval-process::components.admin-panel');
    }
}
