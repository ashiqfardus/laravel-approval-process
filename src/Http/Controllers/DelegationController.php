<?php

namespace AshiqFardus\ApprovalProcess\Http\Controllers;

use AshiqFardus\ApprovalProcess\Models\Delegation;
use AshiqFardus\ApprovalProcess\Http\Requests\DelegationRequest;
use Illuminate\Http\JsonResponse;

class DelegationController extends Controller
{
    /**
     * Display a listing of delegations.
     */
    public function index(): JsonResponse
    {
        $delegations = Delegation::with(['user', 'delegatedToUser', 'approvalStep'])
            ->paginate(config('approval-process.ui.items_per_page'));

        return response()->json($delegations);
    }

    /**
     * Store a newly created delegation.
     */
    public function store(DelegationRequest $request): JsonResponse
    {
        $delegation = Delegation::create($request->validated());

        return response()->json($delegation, 201);
    }

    /**
     * Display the specified delegation.
     */
    public function show(Delegation $delegation): JsonResponse
    {
        $delegation->load(['user', 'delegatedToUser', 'approvalStep']);

        return response()->json($delegation);
    }

    /**
     * Update the specified delegation.
     */
    public function update(DelegationRequest $request, Delegation $delegation): JsonResponse
    {
        $delegation->update($request->validated());

        return response()->json($delegation);
    }

    /**
     * Activate a delegation.
     */
    public function activate(Delegation $delegation): JsonResponse
    {
        $delegation->activate();

        return response()->json($delegation);
    }

    /**
     * Deactivate a delegation.
     */
    public function deactivate(Delegation $delegation): JsonResponse
    {
        $delegation->deactivate();

        return response()->json($delegation);
    }

    /**
     * Delete the specified delegation.
     */
    public function destroy(Delegation $delegation): JsonResponse
    {
        $delegation->delete();

        return response()->json(null, 204);
    }
}
