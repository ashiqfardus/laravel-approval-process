<!-- Approval Status Badge Component -->
<span @class([
    'px-3 py-1 text-xs font-medium rounded-full',
    'bg-green-100 text-green-800' => $status === 'approved',
    'bg-red-100 text-red-800' => $status === 'rejected',
    'bg-yellow-100 text-yellow-800' => $status === 'pending',
    'bg-blue-100 text-blue-800' => $status === 'in-review',
    'bg-gray-100 text-gray-800' => $status === 'draft',
])>
    @switch($status)
        @case('approved')
            ✓ Approved
            @break
        @case('rejected')
            ✗ Rejected
            @break
        @case('pending')
            ⏳ Pending
            @break
        @case('in-review')
            👁 In Review
            @break
        @case('draft')
            📝 Draft
            @break
        @default
            {{ ucfirst($status) }}
    @endswitch
</span>
