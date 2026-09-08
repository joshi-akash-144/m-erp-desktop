<?php

namespace App\Http\Controllers;

use App\Helpers\AjaxResponse;
use App\Models\Company;
use App\Models\User;
use App\Services\DataTables\CompanyUserDataTable;
use Exception;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CompanyUserController extends Controller
{
    protected CompanyUserDataTable $dataTable;

    public function __construct(CompanyUserDataTable $dataTable)
    {
        $this->dataTable = $dataTable;
    }

    /** Index Page */
    public function index(Request $request): View|JsonResponse
    {
        if ($request->ajax()) {
            return $this->dataTable->getData($request);
        }

        $companies = Company::active()->select('id', 'name', 'code')->orderBy('name')->get();
        $users     = User::active()->select('id', 'name')->orderBy('name')->get();

        return view('company-selector.pages.company-user.index', compact('companies', 'users'));
    }

    /** Load Create Form */
    public function create(): View
    {
        $companies = Company::active()->select('id', 'name', 'code')->orderBy('name')->get();
        $users     = User::active()->select('id', 'name')->orderBy('name')->get();
        $mode      = 'create';

        return view('company-selector.pages.company-user.form', compact('mode', 'companies', 'users'));
    }

    /** Store new assignment(s) */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'company_id' => ['required', 'integer', 'exists:companies,id'],
            'user_ids'   => ['required', 'array', 'min:1'],
            'user_ids.*' => ['integer', 'exists:users,id'],
        ]);

        try {
            $authId    = Auth::id();
            $companyId = $validated['company_id'];
            $userIds   = $validated['user_ids'];

            $company = Company::findOrFail($companyId);

            $alreadyAssigned = DB::table('company_users')
                ->where('company_id', $companyId)
                ->whereIn('user_id', $userIds)
                ->pluck('user_id')
                ->toArray();

            $newUserIds = array_diff($userIds, $alreadyAssigned);

            if (empty($newUserIds)) {
                return AjaxResponse::error('All selected users are already assigned to this company.');
            }

            $pivotData = [];
            foreach ($newUserIds as $userId) {
                $pivotData[$userId] = [
                    'status'      => true,
                    'assigned_by' => $authId,
                    'assigned_at' => now(),
                    'created_at'  => now(),
                ];
            }

            $company->users()->attach($pivotData);

            $skipped = count($alreadyAssigned);
            $added   = count($newUserIds);
            $message = "Successfully assigned {$added} user(s) to the company.";
            if ($skipped > 0) {
                $message .= " {$skipped} user(s) were already assigned and skipped.";
            }

            return AjaxResponse::success($message);
        } catch (Exception $e) {
            return AjaxResponse::error('Something went wrong while assigning users.', $e->getMessage());
        }
    }

    /** Toggle status of a company-user assignment */
    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'company_id' => ['required', 'integer', 'exists:companies,id'],
            'user_id'    => ['required', 'integer', 'exists:users,id'],
            'status'     => ['required', 'boolean'],
        ]);

        try {
            $updated = DB::table('company_users')
                ->where('company_id', $validated['company_id'])
                ->where('user_id', $validated['user_id'])
                ->update(['status' => $validated['status']]);

            if (!$updated) {
                return AjaxResponse::error('Assignment not found.');
            }

            $statusLabel = $validated['status'] ? 'activated' : 'deactivated';
            return AjaxResponse::success("Assignment {$statusLabel} successfully.");
        } catch (Exception $e) {
            return AjaxResponse::error('Something went wrong while updating the assignment.', $e->getMessage());
        }
    }

    /** Remove an assignment */
    public function destroy(Request $request): JsonResponse
    {
        if (!$request->ajax()) {
            return AjaxResponse::error('Invalid request type.');
        }

        $validated = $request->validate([
            'company_id' => ['required', 'integer', 'exists:companies,id'],
            'user_id'    => ['required', 'integer', 'exists:users,id'],
        ]);

        try {
            $deleted = DB::table('company_users')
                ->where('company_id', $validated['company_id'])
                ->where('user_id', $validated['user_id'])
                ->delete();

            if (!$deleted) {
                return AjaxResponse::error('Assignment not found.');
            }

            return AjaxResponse::success('User assignment removed successfully.');
        } catch (Exception $e) {
            return AjaxResponse::error('Something went wrong while removing the assignment.', $e->getMessage());
        }
    }
}
