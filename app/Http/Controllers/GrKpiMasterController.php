<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\GrKpiIndustry;
use App\Models\GrKpiCompanySize;
use App\Models\GrKpiSeniorityLevel;
use App\Models\GrKpiWorkModel;
use App\Models\GrKpiCompanyType;
use App\Models\GrKpiTimeframe;

class GrKpiMasterController extends Controller
{
    // Maps URL slug -> [Model class, human label]
    private array $masters = [
        'industries'       => [GrKpiIndustry::class,       'Industry'],
        'company-sizes'    => [GrKpiCompanySize::class,    'Company Size'],
        'seniority-levels' => [GrKpiSeniorityLevel::class, 'Seniority Level'],
        'work-models'      => [GrKpiWorkModel::class,      'Work Model'],
        'company-types'    => [GrKpiCompanyType::class,    'Company Type'],
        'timeframes'       => [GrKpiTimeframe::class,      'Target Timeframe'],
    ];

    private function resolve(string $slug): array
    {
        abort_unless(isset($this->masters[$slug]), 404, 'Master not found');
        return $this->masters[$slug];
    }

    private function creatorId(): int
    {
        return Auth::user()->creatorId();
    }

    public function index(string $master)
    {
        [$model, $label] = $this->resolve($master);
        $items = $model::where('created_by', $this->creatorId())
            ->orderBy('sort_order')->orderBy('name')->get();

        return view('growth_review.kpi_masters.index', [
            'items'    => $items,
            'master'   => $master,
            'label'    => $label,
            'masters'  => $this->masters,
        ]);
    }

    public function store(Request $request, string $master)
    {
        [$model] = $this->resolve($master);
        $data = $request->validate([
            'name'       => 'required|string|max:150',
            'sort_order' => 'nullable|integer',
            'is_active'  => 'nullable|boolean',
        ]);
        $data['is_active']  = (bool)($data['is_active'] ?? true);
        $data['sort_order'] = $data['sort_order'] ?? 0;
        $data['created_by'] = $this->creatorId();
        $model::create($data);

        return redirect()->route('growth-review.masters.index', $master)
            ->with('success', $this->masters[$master][1] . ' added.');
    }

    public function update(Request $request, string $master, int $id)
    {
        [$model] = $this->resolve($master);
        $row = $model::where('created_by', $this->creatorId())->findOrFail($id);
        $data = $request->validate([
            'name'       => 'required|string|max:150',
            'sort_order' => 'nullable|integer',
            'is_active'  => 'nullable|boolean',
        ]);
        $data['is_active']  = (bool)($data['is_active'] ?? false);
        $data['sort_order'] = $data['sort_order'] ?? 0;
        $row->update($data);

        return redirect()->route('growth-review.masters.index', $master)
            ->with('success', $this->masters[$master][1] . ' updated.');
    }

    public function destroy(string $master, int $id)
    {
        [$model] = $this->resolve($master);
        $row = $model::where('created_by', $this->creatorId())->findOrFail($id);
        $row->delete();

        return redirect()->route('growth-review.masters.index', $master)
            ->with('success', $this->masters[$master][1] . ' deleted.');
    }
}
