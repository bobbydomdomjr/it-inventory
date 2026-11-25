<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use App\Models\SupplierEvaluation;
use App\Support\Audit;
use Illuminate\Http\Request;

class SupplierEvaluationController extends Controller
{
    public function store(Request $request, Supplier $supplier)
    {
        abort_unless($request->user()->hasPermission('suppliers.manage'), 403);

        $data = $request->validate([
            'evaluated_on' => ['required', 'date'],
            'quality_score' => ['required', 'integer', 'min:1', 'max:5'],
            'delivery_score' => ['required', 'integer', 'min:1', 'max:5'],
            'service_score' => ['required', 'integer', 'min:1', 'max:5'],
            'remarks' => ['nullable', 'string', 'max:3000'],
        ]);
        $data['overall_score'] = round(($data['quality_score'] + $data['delivery_score'] + $data['service_score']) / 3, 2);
        $data['evaluated_by'] = $request->user()->id;

        $evaluation = $supplier->evaluations()->create($data);
        Audit::record('supplier_evaluated', 'suppliers', SupplierEvaluation::class, $evaluation->id, [], $data);

        return back()->with('success', 'Supplier performance evaluation recorded.');
    }
}
