<?php

namespace AshiqFardus\ApprovalProcess\Http\Controllers;

use AshiqFardus\ApprovalProcess\Models\ApprovalRequest;
use Illuminate\Http\JsonResponse;

class ApprovalActionController extends Controller
{
    /**
     * Get all actions for a request.
     */
    public function index(ApprovalRequest $request): JsonResponse
    {
        $actions = $request->actions()->with('user', 'step')->get();

        return response()->json($actions);
    }

    /**
     * Get approval history for a request.
     */
    public function history(ApprovalRequest $request): JsonResponse
    {
        $history = $request->actions()
            ->with(['user:id,name,email', 'step:id,name'])
            ->orderByDesc('created_at')
            ->get()
            ->groupBy('approval_step_id');

        return response()->json($history);
    }
}
