<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EmployeeWorkflowController extends Controller
{
    public function offboard(Request $request, Employee $employee)
    {
        abort_unless($request->user()->hasPermission('employees.manage'), 403);
        $data = $request->validate([
            'final_status' => ['required', 'in:transferred,separated'],
            'effective_date' => ['required', 'date'],
            'remarks' => ['nullable', 'string', 'max:3000'],
        ]);

        DB::transaction(function () use ($employee, $data) {
            $lockedEmployee = Employee::whereKey($employee->id)->lockForUpdate()->firstOrFail();
            $assignedAssets = $lockedEmployee->assets()->lockForUpdate()->get(['id', 'asset_tag']);
            $openAssignments = $lockedEmployee->assignments()
                ->where('status', 'issued')->whereNull('returned_at')->lockForUpdate()->get(['id', 'reference_no']);

            if ($assignedAssets->isNotEmpty() || $openAssignments->isNotEmpty()) {
                $tags = $assignedAssets->pluck('asset_tag')->filter()->implode(', ');
                throw ValidationException::withMessages([
                    'offboarding' => 'Return or transfer all accountable IT assets before offboarding this employee.' . ($tags ? ' Outstanding assets: ' . $tags . '.' : ''),
                ]);
            }

            $lockedEmployee->update([
                'status' => $data['final_status'],
                'transferred_at' => $data['final_status'] === 'transferred' ? $data['effective_date'] : null,
                'separated_at' => $data['final_status'] === 'separated' ? $data['effective_date'] : null,
                'offboarding_remarks' => $data['remarks'] ?? null,
            ]);

            if ($data['final_status'] === 'separated' && $lockedEmployee->user) {
                $lockedEmployee->user->update(['is_active' => false]);
            }
        }, 3);

        Audit::record('employee_offboarded', 'employees', Employee::class, $employee->id, [], [
            'status' => $data['final_status'], 'effective_date' => $data['effective_date'],
        ]);

        return back()->with('success', $data['final_status'] === 'separated'
            ? 'Employee separated. The linked system account was deactivated.'
            : 'Employee transfer recorded.');
    }
}
