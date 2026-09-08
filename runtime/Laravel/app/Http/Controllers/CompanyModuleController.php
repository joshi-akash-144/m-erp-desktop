<?php

namespace App\Http\Controllers;

use App\Helpers\AjaxResponse;
use App\Models\Company;
use App\Models\Module;
use Exception;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CompanyModuleController extends Controller
{
    /** Index — list all companies with their assigned modules */
    public function index(Request $request): View|JsonResponse
    {
        if ($request->ajax()) {
            return $this->getData($request);
        }

        $companies = Company::active()->select('id', 'name', 'code')->orderBy('name')->get();
        $modules   = Module::active()->orderBy('order')->get();

        return view('company-selector.pages.company-module.index', compact('companies', 'modules'));
    }

    /** Create form */
    public function create(): View
    {
        $companies = Company::active()->select('id', 'name', 'code')->orderBy('name')->get();
        $modules   = Module::active()->orderBy('order')->get();

        return view('company-selector.pages.company-module.form', compact('companies', 'modules'));
    }

    /** Store / bulk assign modules to a company */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'company_id'  => ['required', 'integer', 'exists:companies,id'],
            'module_ids'  => ['required', 'array', 'min:1'],
            'module_ids.*'=> ['integer', 'exists:modules,id'],
        ]);

        try {
            $authId    = Auth::id();
            $companyId = $validated['company_id'];
            $moduleIds = $validated['module_ids'];

            $pivotData = [];
            foreach ($moduleIds as $moduleId) {
                $pivotData[$moduleId] = [
                    'is_active'   => true,
                    'assigned_by' => $authId,
                    'assigned_at' => now(),
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ];
            }

            $company = Company::findOrFail($companyId);
            $company->modules()->sync($pivotData);

            return AjaxResponse::success('Modules assigned to company successfully.');
        } catch (Exception $e) {
            return AjaxResponse::error('Something went wrong while assigning modules.', $e->getMessage());
        }
    }

    /** Toggle active status of a company-module assignment */
    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'company_id' => ['required', 'integer', 'exists:companies,id'],
            'module_id'  => ['required', 'integer', 'exists:modules,id'],
            'is_active'  => ['required', 'boolean'],
        ]);

        try {
            $updated = DB::table('company_modules')
                ->where('company_id', $validated['company_id'])
                ->where('module_id', $validated['module_id'])
                ->update(['is_active' => $validated['is_active'], 'updated_at' => now()]);

            if (!$updated) {
                return AjaxResponse::error('Assignment not found.');
            }

            $label = $validated['is_active'] ? 'activated' : 'deactivated';
            return AjaxResponse::success("Module {$label} successfully.");
        } catch (Exception $e) {
            return AjaxResponse::error('Something went wrong.', $e->getMessage());
        }
    }

    /** Remove a single module assignment */
    public function destroy(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'company_id' => ['required', 'integer', 'exists:companies,id'],
            'module_id'  => ['required', 'integer', 'exists:modules,id'],
        ]);

        try {
            DB::table('company_modules')
                ->where('company_id', $validated['company_id'])
                ->where('module_id', $validated['module_id'])
                ->delete();

            return AjaxResponse::success('Module assignment removed successfully.');
        } catch (Exception $e) {
            return AjaxResponse::error('Something went wrong.', $e->getMessage());
        }
    }

    /** Ajax data for index table */
    private function getData(Request $request): JsonResponse
    {
        $page   = (int) $request->get('page', 1);
        $size   = (int) $request->get('size', 50);
        $search = $request->get('search', '');
        $filterCompany = $request->get('filter_company_id');
        $filterModule  = $request->get('filter_module_id');
        $filterStatus  = $request->get('filter_status');

        $query = DB::table('company_modules as cm')
            ->join('companies as c', 'c.id', '=', 'cm.company_id')
            ->join('modules as m', 'm.id', '=', 'cm.module_id')
            ->select([
                'cm.company_id',
                'cm.module_id',
                'c.name as company_name',
                'c.code as company_code',
                'm.title as module_title',
                'm.icon as module_icon',
                'm.color as module_color',
                'cm.is_active',
                'cm.assigned_at',
            ])
            ->where('c.status', true)
            ->where('m.is_active', true);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('c.name', 'like', "%{$search}%")
                  ->orWhere('m.title', 'like', "%{$search}%");
            });
        }

        if ($filterCompany) {
            $query->where('cm.company_id', $filterCompany);
        }

        if ($filterModule) {
            $query->where('cm.module_id', $filterModule);
        }

        if ($filterStatus !== null && $filterStatus !== '') {
            $query->where('cm.is_active', (bool) $filterStatus);
        }

        $total     = $query->count();
        $lastPage  = (int) ceil($total / $size);
        $data      = $query->orderBy('c.name')->orderBy('m.order')
                           ->skip(($page - 1) * $size)->take($size)->get();

        return response()->json([
            'data'      => $data,
            'total'     => $total,
            'last_page' => max($lastPage, 1),
        ]);
    }
}
