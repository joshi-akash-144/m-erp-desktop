<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Illuminate\Routing\Controller;
use Exception;
use App\Helpers\AjaxResponse;
use App\Services\DataTables\PermissionDataTable;
use Illuminate\Http\JsonResponse;

class PermissionController extends Controller
{
    protected $dataTable;

    public function __construct(PermissionDataTable $dataTable)
    {
        // $this->middleware('permission:permission.list');
        // $this->middleware('permission:permission.create')->only(['create', 'store']);
        // $this->middleware('permission:permission.update')->only(['edit', 'update']);
        // $this->middleware('permission:permission.delete')->only('destroy');
        // $this->middleware('permission:permission.restore')->only('restore');
        $this->dataTable = $dataTable;
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = Permission::query();
            return $this->dataTable->getData($query);
        }
        return view('company-selector.pages.setups.permissions.index');
    }

    public function create(Request $request)
    {
        $modalData = [
            'title'     => 'Add Permissions',
            'form_mode' => 'create',
            'data'      => null
        ];
        $html = view('company-selector.pages.setups.permissions.form', compact('modalData'))->render();
        return response()->json(['html' => $html]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'module_name' => 'required|string',
            'permissions' => 'required|array',
            'permissions.*' => 'required|string'
        ]);

        try {
            $module = strtolower(str_replace(' ', '_', trim($request->module_name)));
            $permissionsArray = $request->permissions;
            
            $allPermissions = [];
            foreach ($permissionsArray as $action) {
                if (empty($action)) continue;
                $action = strtolower(str_replace(' ', '_', $action));
                $permission = Permission::updateOrCreate(
                    ['name' => "{$module}.{$action}"],
                    ['module' => $module]
                );
                $allPermissions[] = $permission->id;
            }

            // Assign to Root role automatically
            $rootUser = Role::where('name', 'Root')->first();
            $superAdminUser = Role::where('name', 'Super Admin')->first();

            if ($rootUser && !empty($allPermissions)) {
                $rootUser->givePermissionTo($allPermissions);
            }
            if ($superAdminUser && !empty($allPermissions)) {
                $superAdminUser->givePermissionTo($allPermissions);
            }

            return AjaxResponse::success(message: 'Permissions created successfully!');
        } catch (Exception $e) {
            return AjaxResponse::error(message: $e->getMessage());
        }
    }

    public function edit(Request $request, $id)
    {
        $permission = Permission::findOrFail($id);
        if ($permission->roles()->exists()) {
            return AjaxResponse::error(message: 'This permission is assigned to one or more roles and cannot be edited.');
        }
        $modalData = [
            'title'     => 'Edit Permission',
            'form_mode' => 'edit',
            'data'      => $permission
        ];
        $html = view('company-selector.pages.setups.permissions.form', compact('modalData'))->render();
        return response()->json(['html' => $html]);
    }

    public function show(Request $request, $id)
    {
        $permission = Permission::findOrFail($id);
        $modalData = [
            'title'     => 'View Permission',
            'form_mode' => 'view',
            'data'      => $permission
        ];
        $html = view('company-selector.pages.setups.permissions.form', compact('modalData'))->render();
        return response()->json(['html' => $html]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'module' => 'required|string',
            'name' => 'required|string',
        ]);

        try {
            $module = strtolower(str_replace(' ', '_', trim($request->module)));
            $action = strtolower(str_replace(' ', '', trim($request->name)));
            $fullName = "{$module}.{$action}";

            if (Permission::where('name', $fullName)->where('id', '!=', $id)->exists()) {
                return AjaxResponse::error(message: 'The permission name has already been taken.');
            }

            $permission = Permission::findOrFail($id);
            // if ($permission->roles()->exists()) {
            //     return AjaxResponse::error(message: 'This permission is assigned to one or more roles and cannot be updated.');
            // }
            $permission->module = $module;
            $permission->name = $fullName;
            $permission->save();

            return AjaxResponse::success(message: 'Permission updated successfully!');
        } catch (Exception $e) {
            return AjaxResponse::error(message: $e->getMessage());
        }
    }

    public function destroy(Request $request, $id)
    {
        if (!$request->ajax()) {
            return AjaxResponse::error(message: __('messages.request.type'));
        }

        try {
            $permission = Permission::findOrFail($id);
            if ($permission->roles()->exists()) {
                return AjaxResponse::error(message: 'This permission is assigned to one or more roles and cannot be deleted.');
            }
            $permission->delete();
            return AjaxResponse::success(message: 'Permission deleted successfully!');
        } catch (Exception $e) {
            return AjaxResponse::error(message: $e->getMessage());
        }
    }
}
