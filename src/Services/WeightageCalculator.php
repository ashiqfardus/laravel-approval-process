<?php

namespace AshiqFardus\ApprovalProcess\Services;

use AshiqFardus\ApprovalProcess\Models\ApprovalStep;
use AshiqFardus\ApprovalProcess\Models\ApprovalRequest;
use Illuminate\Support\Collection;

class WeightageCalculator
{
    /**
     * Calculate the current approval percentage for a step.
     *
     * @param ApprovalStep $step
     * @return float Current approval percentage (0-100)
     */
    public function calculateCurrentPercentage(ApprovalStep $step): float
    {
        $approvers = $step->approvers;

        if ($approvers->isEmpty()) {
            return 0;
        }

        $totalWeightage = $approvers->sum('weightage');

        if ($totalWeightage === 0) {
            return 0;
        }

        $approvedWeightage = $approvers
            ->where('is_approved', true)
            ->sum('weightage');

        return round(($approvedWeightage / $totalWeightage) * 100, 2);
    }

    /**
     * Check if a step has reached the minimum approval percentage.
     *
     * @param ApprovalStep $step
     * @return bool
     */
    public function hasReachedMinimumPercentage(ApprovalStep $step): bool
    {
        $currentPercentage = $this->calculateCurrentPercentage($step);
        $minimumPercentage = $step->minimum_approval_percentage ?? 100;

        return $currentPercentage >= $minimumPercentage;
    }

    /**
     * Get detailed approval breakdown for a step.
     *
     * @param ApprovalStep $step
     * @return array
     */
    public function getApprovalBreakdown(ApprovalStep $step): array
    {
        $approvers = $step->approvers;
        $totalWeightage = $approvers->sum('weightage');
        $approvedWeightage = $approvers->where('is_approved', true)->sum('weightage');
        $pendingWeightage = $totalWeightage - $approvedWeightage;
        $currentPercentage = $this->calculateCurrentPercentage($step);
        $minimumPercentage = $step->minimum_approval_percentage ?? 100;

        return [
            'total_weightage' => $totalWeightage,
            'approved_weightage' => $approvedWeightage,
            'pending_weightage' => $pendingWeightage,
            'current_percentage' => $currentPercentage,
            'minimum_percentage' => $minimumPercentage,
            'is_complete' => $currentPercentage >= $minimumPercentage,
            'remaining_percentage' => max(0, $minimumPercentage - $currentPercentage),
            'approvers' => $approvers->map(function ($approver) {
                return [
                    'id' => $approver->id,
                    'user_id' => $approver->user_id,
                    'weightage' => $approver->weightage,
                    'is_approved' => $approver->is_approved,
                    'approval_at' => $approver->approval_at,
                ];
            })->toArray(),
        ];
    }

    /**
     * Validate weightage distribution for a step.
     *
     * @param array $approvers Array of approvers with weightage
     * @return array Validation result
     */
    public function validateWeightageDistribution(array $approvers): array
    {
        $totalWeightage = collect($approvers)->sum('weightage');

        $errors = [];
        $warnings = [];

        // Check if total weightage is 0
        if ($totalWeightage === 0) {
            $errors[] = 'Total weightage cannot be 0. At least one approver must have weightage > 0.';
        }

        // Check for negative weightage
        foreach ($approvers as $index => $approver) {
            if (isset($approver['weightage']) && $approver['weightage'] < 0) {
                $errors[] = "Approver #{$index}: Weightage cannot be negative.";
            }
            if (isset($approver['weightage']) && $approver['weightage'] > 100) {
                $warnings[] = "Approver #{$index}: Weightage exceeds 100. This is allowed but unusual.";
            }
        }

        // Warn if total weightage doesn't equal 100
        if ($totalWeightage !== 100 && $totalWeightage > 0) {
            $warnings[] = "Total weightage is {$totalWeightage}. Consider using 100 for easier percentage calculation.";
        }

        return [
            'is_valid' => empty($errors),
            'total_weightage' => $totalWeightage,
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }

    /**
     * Calculate what percentage each approver represents.
     *
     * @param ApprovalStep $step
     * @return array
     */
    public function getApproverPercentages(ApprovalStep $step): array
    {
        $approvers = $step->approvers;
        $totalWeightage = $approvers->sum('weightage');

        if ($totalWeightage === 0) {
            return [];
        }

        return $approvers->map(function ($approver) use ($totalWeightage) {
            return [
                'id' => $approver->id,
                'user_id' => $approver->user_id,
                'weightage' => $approver->weightage,
                'percentage' => round(($approver->weightage / $totalWeightage) * 100, 2),
                'is_approved' => $approver->is_approved,
            ];
        })->toArray();
    }

    /**
     * Suggest optimal weightage distribution.
     *
     * @param int $approverCount
     * @param string $strategy 'equal', 'hierarchical', 'majority-one'
     * @return array
     */
    public function suggestWeightageDistribution(int $approverCount, string $strategy = 'equal'): array
    {
        if ($approverCount <= 0) {
            return [];
        }

        switch ($strategy) {
            case 'equal':
                // Equal distribution
                $weightage = floor(100 / $approverCount);
                $remainder = 100 - ($weightage * $approverCount);
                
                $distribution = array_fill(0, $approverCount, $weightage);
                if ($remainder > 0) {
                    $distribution[0] += $remainder; // Give remainder to first approver
                }
                return $distribution;

            case 'hierarchical':
                // Decreasing weightage (50%, 30%, 20% for 3 approvers)
                $weights = [];
                $remaining = 100;
                for ($i = 0; $i < $approverCount; $i++) {
                    if ($i === $approverCount - 1) {
                        $weights[] = $remaining;
                    } else {
                        // Use a ratio that ensures decreasing values
                        // First gets ~50%, second gets ~30%, third gets ~20%
                        $ratio = ($approverCount - $i) / array_sum(range(1, $approverCount - $i));
                        $weight = (int) floor($remaining * $ratio);
                        $weights[] = $weight;
                        $remaining -= $weight;
                    }
                }
                return $weights;

            case 'majority-one':
                // One approver has 51%, others share 49%
                if ($approverCount === 1) {
                    return [100];
                }
                
                $majorityWeight = 51;
                $othersWeight = floor(49 / ($approverCount - 1));
                $remainder = 49 - ($othersWeight * ($approverCount - 1));
                
                $distribution = [$majorityWeight];
                for ($i = 1; $i < $approverCount; $i++) {
                    $distribution[] = $othersWeight + ($i === 1 ? $remainder : 0);
                }
                return $distribution;

            default:
                return $this->suggestWeightageDistribution($approverCount, 'equal');
        }
    }

    /**
     * Calculate remaining approvals needed to reach minimum percentage.
     *
     * @param ApprovalStep $step
     * @return array
     */
    public function getRemainingApprovalsNeeded(ApprovalStep $step): array
    {
        $currentPercentage = $this->calculateCurrentPercentage($step);
        $minimumPercentage = $step->minimum_approval_percentage ?? 100;
        $remainingPercentage = max(0, $minimumPercentage - $currentPercentage);

        if ($remainingPercentage <= 0) {
            return [
                'is_complete' => true,
                'remaining_percentage' => 0,
                'possible_approvers' => [],
            ];
        }

        $approvers = $step->approvers;
        $totalWeightage = $approvers->sum('weightage');
        $pendingApprovers = $approvers->where('is_approved', false);

        // Calculate which combinations of pending approvers can complete the step
        $possibleApprovers = $pendingApprovers->map(function ($approver) use ($totalWeightage) {
            return [
                'id' => $approver->id,
                'user_id' => $approver->user_id,
                'weightage' => $approver->weightage,
                'percentage' => round(($approver->weightage / $totalWeightage) * 100, 2),
            ];
        })->sortByDesc('percentage')->values()->toArray();

        return [
            'is_complete' => false,
            'remaining_percentage' => $remainingPercentage,
            'possible_approvers' => $possibleApprovers,
            'minimum_approvers_needed' => $this->calculateMinimumApproversNeeded(
                $pendingApprovers,
                $remainingPercentage,
                $totalWeightage
            ),
        ];
    }

    /**
     * Calculate minimum number of approvers needed to reach percentage.
     *
     * @param Collection $pendingApprovers
     * @param float $remainingPercentage
     * @param int $totalWeightage
     * @return int
     */
    protected function calculateMinimumApproversNeeded(
        Collection $pendingApprovers,
        float $remainingPercentage,
        int $totalWeightage
    ): int {
        if ($totalWeightage === 0) {
            return 0;
        }

        $sorted = $pendingApprovers->sortByDesc('weightage');
        $cumulativePercentage = 0;
        $count = 0;

        foreach ($sorted as $approver) {
            $count++;
            $cumulativePercentage += ($approver->weightage / $totalWeightage) * 100;
            
            if ($cumulativePercentage >= $remainingPercentage) {
                return $count;
            }
        }

        return $count; // All remaining approvers needed
    }
}
