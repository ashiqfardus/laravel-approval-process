<?php

namespace AshiqFardus\ApprovalProcess\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ApprovalRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $method = $this->method();
        
        // For updates, only validate fields that can be changed
        if ($method === 'PUT' || $method === 'PATCH') {
            return [
                'data_snapshot' => 'nullable|array',
                'metadata' => 'nullable|array',
                'status' => 'nullable|string|in:draft,submitted,in-review,pending,approved,rejected,cancelled,archived',
            ];
        }
        
        // For creation, validate all required fields
        return [
            'workflow_id' => 'required|exists:approval_workflows,id',
            'requestable_type' => 'required|string',
            'requestable_id' => 'required',
            'requested_by_user_id' => 'required|exists:users,id',
            'status' => 'nullable|string|in:draft,submitted,in-review,pending,approved,rejected,cancelled,archived',
            'data_snapshot' => 'nullable|array',
            'metadata' => 'nullable|array',
        ];
    }
}
