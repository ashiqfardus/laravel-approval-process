# Weightage-Based Approval System

**Version:** 1.0  
**Last Updated:** 2026-02-17

---

## 📋 Overview

The Weightage-Based Approval System allows you to assign voting power to different approvers at the same approval level. Instead of requiring **all** approvers to approve (100%), you can set a **dynamic minimum percentage threshold** (e.g., 51%, 75%, etc.) that must be reached for the step to proceed.

### Key Features

- ✅ **Dynamic Approval Thresholds** - Set any percentage from 0-100%
- ✅ **Weighted Voting** - Each approver has a customizable weightage (voting power)
- ✅ **Real-time Progress Tracking** - Visual progress bars show current approval percentage
- ✅ **Flexible Strategies** - Equal, hierarchical, or majority-one distributions
- ✅ **Smart Calculations** - Automatic calculation of remaining approvals needed
- ✅ **Validation** - Built-in validation for weightage distributions

---

## 🎯 Use Cases

### 1. Majority Approval (51%)
```
Board of Directors (5 members):
- Director A: 20% weightage
- Director B: 20% weightage
- Director C: 20% weightage
- Director D: 20% weightage
- Director E: 20% weightage

Minimum: 51% → Need 3 out of 5 directors to approve
```

### 2. Hierarchical Approval (75%)
```
Management Team:
- CEO: 50% weightage
- CFO: 30% weightage
- COO: 20% weightage

Minimum: 75% → CEO + CFO OR CEO + COO + CFO
```

### 3. Expert Panel (60%)
```
Technical Review:
- Senior Architect: 40% weightage
- Lead Developer: 30% weightage
- QA Lead: 20% weightage
- DevOps Lead: 10% weightage

Minimum: 60% → Senior Architect + Lead Developer
```

### 4. Unanimous (100%)
```
Legal Compliance:
- Legal Officer: 50% weightage
- Compliance Officer: 50% weightage

Minimum: 100% → Both must approve
```

---

## 🗄️ Database Schema

### Migration

```php
// database/migrations/2024_01_01_000006_add_weightage_to_approval_system.php

// approval_approvers table
Schema::table('approval_approvers', function (Blueprint $table) {
    $table->unsignedInteger('weightage')->default(100)
        ->comment('Approval weightage/voting power (0-100)');
});

// approval_steps table
Schema::table('approval_steps', function (Blueprint $table) {
    $table->unsignedInteger('minimum_approval_percentage')->default(100)
        ->comment('Minimum percentage of weightage required to proceed (0-100)');
});

// approval_requests table
Schema::table('approval_requests', function (Blueprint $table) {
    $table->decimal('current_approval_percentage', 5, 2)->default(0)
        ->comment('Current approval percentage at current step');
});
```

---

## 💻 API Reference

### 1. Get Step Weightage Breakdown

```http
GET /api/approval-process/steps/{step}/weightage/breakdown
```

**Response:**
```json
{
  "success": true,
  "data": {
    "total_weightage": 100,
    "approved_weightage": 50,
    "pending_weightage": 50,
    "current_percentage": 50.0,
    "minimum_percentage": 75,
    "is_complete": false,
    "remaining_percentage": 25,
    "approvers": [
      {
        "id": 1,
        "user_id": 10,
        "weightage": 50,
        "is_approved": true,
        "approval_at": "2026-02-17T10:30:00Z"
      },
      {
        "id": 2,
        "user_id": 11,
        "weightage": 30,
        "is_approved": false,
        "approval_at": null
      },
      {
        "id": 3,
        "user_id": 12,
        "weightage": 20,
        "is_approved": false,
        "approval_at": null
      }
    ]
  }
}
```

### 2. Get Request Weightage Breakdown

```http
GET /api/approval-process/requests/{request}/weightage/breakdown
```

Returns the same structure as above for the request's current step.

### 3. Update Minimum Approval Percentage

```http
PUT /api/approval-process/steps/{step}/weightage/minimum-percentage
Content-Type: application/json

{
  "minimum_percentage": 75
}
```

**Response:**
```json
{
  "success": true,
  "message": "Minimum approval percentage updated successfully",
  "data": {
    "step_id": 5,
    "minimum_approval_percentage": 75
  }
}
```

### 4. Update Approver Weightage

```http
PUT /api/approval-process/approvers/{approver}/weightage
Content-Type: application/json

{
  "weightage": 70
}
```

### 5. Bulk Update Weightages

```http
PUT /api/approval-process/steps/{step}/weightage/bulk-update
Content-Type: application/json

{
  "approvers": [
    {"id": 1, "weightage": 50},
    {"id": 2, "weightage": 30},
    {"id": 3, "weightage": 20}
  ]
}
```

### 6. Suggest Weightage Distribution

```http
POST /api/approval-process/weightage/suggest
Content-Type: application/json

{
  "approver_count": 3,
  "strategy": "hierarchical"
}
```

**Strategies:**
- `equal` - Equal distribution (33%, 33%, 34%)
- `hierarchical` - Decreasing (50%, 30%, 20%)
- `majority-one` - One has majority (51%, 25%, 24%)

**Response:**
```json
{
  "success": true,
  "data": {
    "strategy": "hierarchical",
    "approver_count": 3,
    "distribution": [50, 30, 20],
    "total": 100
  }
}
```

### 7. Validate Weightage Distribution

```http
POST /api/approval-process/steps/{step}/weightage/validate
```

**Response:**
```json
{
  "success": true,
  "data": {
    "is_valid": true,
    "total_weightage": 100,
    "errors": [],
    "warnings": []
  }
}
```

### 8. Get Remaining Approvals Needed

```http
GET /api/approval-process/steps/{step}/weightage/remaining
```

**Response:**
```json
{
  "success": true,
  "data": {
    "is_complete": false,
    "remaining_percentage": 25,
    "possible_approvers": [
      {
        "id": 2,
        "user_id": 11,
        "weightage": 30,
        "percentage": 30.0
      },
      {
        "id": 3,
        "user_id": 12,
        "weightage": 20,
        "percentage": 20.0
      }
    ],
    "minimum_approvers_needed": 1
  }
}
```

---

## 🎨 UI Components

### Workflow Designer

The workflow designer now includes weightage configuration:

```blade
<!-- Approver with Weightage Input -->
<div class="bg-gray-50 p-3 rounded">
    <div class="flex items-center justify-between mb-2">
        <span class="text-sm font-medium">User: John Doe</span>
        <button class="text-red-600">×</button>
    </div>
    <div class="flex items-center gap-2">
        <label class="text-xs text-gray-600">Weightage:</label>
        <input type="number" min="0" max="100" value="50" class="w-20 px-2 py-1">
        <span class="text-xs">%</span>
    </div>
</div>

<!-- Minimum Approval Percentage -->
<div class="mt-4 p-3 bg-blue-50 rounded">
    <label class="block text-sm font-medium mb-2">
        Minimum Approval Percentage
    </label>
    <input type="number" min="0" max="100" value="75" class="w-24 px-3 py-2">
    <span class="text-sm">% required to proceed</span>
    <p class="text-xs text-gray-500 mt-1">
        Set the minimum weightage percentage needed for this step to complete
    </p>
</div>
```

### Request Detail View

Shows real-time approval progress:

```blade
<!-- Approval Progress Bar -->
<div class="mt-3 p-3 bg-blue-50 rounded">
    <div class="flex items-center justify-between mb-2">
        <span class="text-sm font-medium">Approval Progress</span>
        <span class="text-sm font-semibold text-blue-600">
            50.0% / 75%
        </span>
    </div>
    <div class="w-full bg-gray-200 rounded-full h-3">
        <div class="h-full bg-blue-500" style="width: 50%"></div>
    </div>
    <p class="text-xs text-gray-600 mt-1">
        Need 25% more to proceed
    </p>
</div>

<!-- Approver List with Weightage -->
<div class="mt-2 space-y-1">
    <div class="flex items-center justify-between text-xs">
        <span class="px-2 py-1 bg-green-100 text-green-800 rounded">
            ✓ CEO: John Doe
        </span>
        <span class="font-medium">50%</span>
    </div>
    <div class="flex items-center justify-between text-xs">
        <span class="px-2 py-1 bg-gray-100 text-gray-700 rounded">
            CFO: Jane Smith
        </span>
        <span class="font-medium">30%</span>
    </div>
</div>
```

---

## 🧪 Testing

### Unit Tests

```php
use AshiqFardus\ApprovalProcess\Services\WeightageCalculator;

$calculator = new WeightageCalculator();

// Test percentage calculation
$percentage = $calculator->calculateCurrentPercentage($step);
// Returns: 50.0 (if 50% of weightage has approved)

// Test minimum reached
$hasReached = $calculator->hasReachedMinimumPercentage($step);
// Returns: true/false

// Test approval breakdown
$breakdown = $calculator->getApprovalBreakdown($step);
// Returns: detailed breakdown array

// Suggest distribution
$distribution = $calculator->suggestWeightageDistribution(3, 'equal');
// Returns: [34, 33, 33]
```

### Feature Tests

```php
// Test API endpoint
$response = $this->getJson("/api/approval-process/steps/{$step->id}/weightage/breakdown");

$response->assertOk()
    ->assertJson([
        'success' => true,
        'data' => [
            'current_percentage' => 50.0,
            'minimum_percentage' => 75,
            'is_complete' => false,
        ],
    ]);
```

---

## 📊 Examples

### Example 1: Simple Majority (51%)

```php
use AshiqFardus\ApprovalProcess\Models\ApprovalStep;
use AshiqFardus\ApprovalProcess\Models\Approver;

// Create step with 51% minimum
$step = ApprovalStep::create([
    'workflow_id' => $workflow->id,
    'name' => 'Board Approval',
    'sequence' => 1,
    'minimum_approval_percentage' => 51,
]);

// Add 5 equal approvers
foreach ($boardMembers as $member) {
    Approver::create([
        'approval_step_id' => $step->id,
        'user_id' => $member->id,
        'weightage' => 20, // 20% each
    ]);
}

// Result: Need 3 out of 5 to approve (60% >= 51%)
```

### Example 2: Hierarchical (75%)

```php
$step = ApprovalStep::create([
    'workflow_id' => $workflow->id,
    'name' => 'Executive Approval',
    'minimum_approval_percentage' => 75,
]);

Approver::create([
    'approval_step_id' => $step->id,
    'user_id' => $ceo->id,
    'weightage' => 50, // CEO has 50%
]);

Approver::create([
    'approval_step_id' => $step->id,
    'user_id' => $cfo->id,
    'weightage' => 30, // CFO has 30%
]);

Approver::create([
    'approval_step_id' => $step->id,
    'user_id' => $coo->id,
    'weightage' => 20, // COO has 20%
]);

// Result: CEO + CFO (80%) OR CEO + COO + CFO (100%)
```

### Example 3: Using WeightageCalculator

```php
use AshiqFardus\ApprovalProcess\Services\WeightageCalculator;

$calculator = new WeightageCalculator();

// Get current status
$breakdown = $calculator->getApprovalBreakdown($step);

echo "Current: {$breakdown['current_percentage']}%\n";
echo "Minimum: {$breakdown['minimum_percentage']}%\n";
echo "Complete: " . ($breakdown['is_complete'] ? 'Yes' : 'No') . "\n";

// Get remaining approvals needed
$remaining = $calculator->getRemainingApprovalsNeeded($step);

echo "Need {$remaining['remaining_percentage']}% more\n";
echo "Minimum approvers needed: {$remaining['minimum_approvers_needed']}\n";
```

---

## ⚙️ Configuration

### Setting Default Minimum Percentage

In `config/approval-process.php`:

```php
return [
    'defaults' => [
        'minimum_approval_percentage' => 100, // Default to unanimous
    ],
];
```

### Per-Step Configuration

```php
$step->update([
    'minimum_approval_percentage' => 51, // Majority
]);
```

---

## 🔧 Troubleshooting

### Issue: Total weightage doesn't equal 100

**Solution:** The system works with any total weightage. Percentages are calculated proportionally.

```php
// This is valid:
Approver 1: 60 weightage
Approver 2: 30 weightage
Total: 90

// Percentages: 66.67% and 33.33%
```

### Issue: Step not completing despite enough approvals

**Check:**
1. Verify `minimum_approval_percentage` is set correctly
2. Check if weightages are properly assigned
3. Use `getApprovalBreakdown()` to debug

```php
$breakdown = app(WeightageCalculator::class)->getApprovalBreakdown($step);
dd($breakdown);
```

### Issue: Validation errors when updating weightage

**Common causes:**
- Negative weightage values
- Weightage > 100 (allowed but unusual)
- Total weightage = 0

---

## 📚 Best Practices

1. **Use 100 as Total Weightage** - Makes percentage calculations intuitive
2. **Set Realistic Minimums** - 51% for majority, 75% for supermajority, 100% for unanimous
3. **Document Weightage Rationale** - Explain why certain approvers have more weight
4. **Test Distributions** - Use `suggestWeightageDistribution()` for common patterns
5. **Monitor Progress** - Display progress bars to users
6. **Validate Before Saving** - Use `validateWeightageDistribution()` API

---

## 🚀 Migration Guide

### Upgrading from Simple Approval

If you have existing workflows without weightage:

```php
use Illuminate\Support\Facades\DB;

// Run migration
php artisan migrate

// All existing approvers will have weightage = 100
// All existing steps will have minimum_approval_percentage = 100
// Behavior remains unchanged (requires all approvers)

// To enable weightage for a step:
DB::table('approval_steps')
    ->where('id', $stepId)
    ->update(['minimum_approval_percentage' => 51]); // Or your desired %

// Update approver weightages as needed
DB::table('approval_approvers')
    ->where('approval_step_id', $stepId)
    ->update(['weightage' => 20]); // Equal distribution for 5 approvers
```

---

## 📖 Related Documentation

- [API Reference](./openapi.yaml)
- [Workflow Designer Guide](./PHASE_5_SUMMARY.md)
- [Testing Guide](../tests/README.md)
- [Configuration Guide](./CONFIGURATION_GUIDE.md)

---

## 🎓 Advanced Topics

### Custom Weightage Strategies

Implement custom distribution logic:

```php
class CustomWeightageStrategy
{
    public function calculate(array $approvers): array
    {
        // Your custom logic
        return $weightages;
    }
}
```

### Dynamic Weightage Adjustment

Adjust weightage based on context:

```php
if ($request->amount > 100000) {
    // High-value requests require CEO approval
    $ceoApprover->update(['weightage' => 60]);
}
```

### Audit Trail

Track weightage changes:

```php
ApprovalAction::recordAction(
    $request,
    $step,
    $userId,
    'weightage_updated',
    "Weightage changed from {$old} to {$new}"
);
```

---

**Questions?** Check the [FAQ](./FAQ.md) or open an issue on GitHub.
