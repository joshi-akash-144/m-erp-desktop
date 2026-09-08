<?php

namespace App\Http\Controllers;

use App\Helpers\AjaxResponse;
use App\Http\Requests\StoreRoleRequest;
use App\Http\Requests\UpdateRoleRequest;
use App\Models\User;
use App\Repositories\RoleRepository;
use App\Repositories\CommonRepository;
use App\Services\DataTables\RoleDataTable;
use Exception;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Auth;


class RoleController extends Controller
{
    protected RoleRepository $repository;
    protected CommonRepository $commonRepository;
    protected RoleDataTable $dataTable;
    protected int $companyId;

    public function __construct(RoleRepository $repository, RoleDataTable $dataTable,  CommonRepository $commonRepository)
    {
        // Apply middleware for permissions
        $this->middleware('permission:role.list')->only(['index', 'list']);
        $this->middleware('permission:role.create')->only(['create', 'store']);
        $this->middleware('permission:role.update')->only(['edit', 'update']);
        $this->middleware('permission:role.delete')->only('destroy');
        $this->middleware('permission:role.restore')->only('restore');

        $this->repository = $repository;
        $this->commonRepository = $commonRepository;
        $this->dataTable = $dataTable;

        // Get company ID from session
        $this->middleware(function ($request, $next) {
            $this->companyId = (int) session('company_id');
            return $next($request);
        });
    }

    /** Index Page */
    public function index(Request $request): View|JsonResponse
    {
           if ($request->ajax()) {
            $query = $this->repository->query();
            return $this->dataTable->getData($query);
        }
        $roles = Role::select('name', 'id')->get();
        // dd($roles);
        // $pageConfigs = ['myLayout' => 'blank'];
        return view('company-selector.pages.setups.roles.index',compact('roles'));
    }

    /** Load Create Modal */
    public function create(Request $request)
    {        

        $roles = Role::select('id', 'name')
            ->orderBy('name', 'asc')
            ->get();
                    
        $permissions = Permission::select('id', 'name')
            ->orderBy('name', 'asc')
            ->get();
                
        // Group permissions by module
        $groupedPermissions = [];       
        foreach ($permissions as $permission) {
            [$module, $action] = explode('.', $permission->name);
            $groupedPermissions[$module][$action] = $permission->name;                   
        }
        
        $modalData = [
            'title'     => __('titles.role.add'),            
            'data'      => null,
            'form_mode' => 'create',
            'roles'     => $roles,
            'permissions' => $groupedPermissions,                         
        ];
        return view('company-selector.pages.setups.roles.form', compact('modalData'));
        
        // return AjaxResponse::success(message: __('messages.modal.load_success'), data: [
        //     'html' => view('_partials._modals.master.modal-role', compact('modalData'))->render(),
        // ]);
    }

    /** Store */
    public function store(StoreRoleRequest $request): JsonResponse
    {
        try {            
            $validated = $request->validated();            
            
            $roles = $this->repository->create($validated);

            // Retrieve the 'new' permissions or default to an empty array
            $newPermissions = $request->permissions['new'] ?? [];            
            $roles->syncPermissions($newPermissions);

            return AjaxResponse::success(message: __('messages.role.created'), data: $roles, code: 201);
        } catch (Exception $e) {
            return AjaxResponse::error(message: __('messages.common.unexpected_error'), errors: $e->getMessage());
        }
    }



    /** Load View Modal */
    public function show(Request $request, Role $roles)
    {
        $roleId = $roles->id;

        $allRoles = Role::select('id', 'name')
            ->orderBy('name', 'asc')
            ->get();

        $permissions = Permission::select('id', 'name')
            ->orderBy('name', 'asc')
            ->get();

        // Group permissions by module
        $groupedPermissions = [];

        foreach ($permissions as $permission) {
            [$module, $action] = explode('.', $permission->name);
            $groupedPermissions[$module][$action] = $permission->name;
        }

        $modalData = [
            'title'     => __('titles.role.view'),            
            'data'      => $roles,
            'form_mode' => 'view',
            'roles' => $allRoles,
            'permissions' => $groupedPermissions,
        ];        
             $mode = 'view';
          return view('company-selector.pages.setups.roles.form', compact( 'mode','modalData'));
    }

    /** Load Edit Modal */
    public function edit(Request $request, Role $roles)
    {       
       
        $roleId = $roles->id;                
        $allRoles = Role::find($roleId);

        $permissions = Permission::select('id', 'name')
            ->orderBy('name', 'asc')
            ->get();

        // Group permissions by module
        $groupedPermissions = [];
        foreach ($permissions as $permission) {
            [$module, $action] = explode('.', $permission->name);
            $groupedPermissions[$module][$action] = $permission->name;
        }

        // Get permissions assigned to this role
        $rolePermissions = $roles->permissions->pluck('name')->toArray();

        $modalData = [
            'title'        => __('titles.role.edit'),
            'id'           => $roleId,
            'data'         => $roles,
            'form_mode'    => 'edit',
            'roles'        => $allRoles,
            'permissions'  => $groupedPermissions,
            'rolePermissions' => $rolePermissions, // Pass assigned permissions
        ];
            $mode = 'edit';     
        return view('company-selector.pages.setups.roles.form', compact( 'allRoles','mode','modalData'));
    }


    /** Update */
    public function update(UpdateRoleRequest $request, Role $roles): JsonResponse
    {
        try {
            $validated = $request->validated();
            $updated = $this->repository->update($roles, $validated);
            $roles->syncPermissions($request->permissions);
            return AjaxResponse::success(message: __('messages.role.updated'), data: $updated);
        } catch (Exception $e) {
            return AjaxResponse::error(message: __('messages.common.unexpected_error'), errors: $e->getMessage());
        }
    }   

    /** Delete */
    public function destroy(Request $request, Role $roles): JsonResponse
    {
        if (!$request->ajax()) {
            return AjaxResponse::error(message: __('messages.request.type'));
        }

        // Check if the role has any permissions assigned
        if ($roles->permissions()->count() > 0) {
            return AjaxResponse::error(message: __('messages.role.has_permissions'));
        }
        try {
            $this->repository->delete($roles);
            return AjaxResponse::success(message: __('messages.role.deleted'));
        } catch (Exception $e) {
            return AjaxResponse::error(message: __('messages.common.unexpected_error'), errors: $e->getMessage());
        }
    }

    /** Restore */
    // public function restore(Request $request, string $id): JsonResponse
    // {
    //     if (!$request->ajax()) {
    //         return AjaxResponse::error(message: __('messages.request.type'));
    //     }

    //     try {
    //         $this->repository->restoreByUuid($id);
    //         return AjaxResponse::success(__('messages.role.restored'));
    //     } catch (Exception $e) {
    //         return AjaxResponse::error(__('messages.common.unexpected_error'), $e->getMessage());
    //     }
    // }

    /** List (DataTable) */
    // public function list(): JsonResponse
    // {
    //     $data = $this->repository->all(
    //         columns: ['*'],            
    //         orderBy: 'name'
    //     );
    //     $user = Auth::user();
    //     // Add permission info for each row
    //     $data = $data->map(function ($item) use ($user) {
    //         $item->can = [
    //             'update' => $user->can('role.update'),
    //             'view'   => $user->can('role.view'),
    //             'delete' => $user->can('role.delete'),
    //         ];
    //         return $item;
    //     });
    //     return $this->dataTable->getData($data);
    // }
}
