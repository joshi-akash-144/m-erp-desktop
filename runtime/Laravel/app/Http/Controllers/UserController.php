<?php

namespace App\Http\Controllers;

use App\Helpers\AjaxResponse;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdatePasswordRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use Spatie\Permission\Models\Role;
use App\Repositories\UserRepository;
use App\Services\DataTables\UserDataTable;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    protected UserDataTable $dataTable;
    protected UserRepository $repository;
    // protected int $companyId;
    public function __construct(UserRepository $repository, UserDataTable $dataTable)
    {
        // Apply middleware for permissions
        $this->middleware('permission:user.list')->only(['index', 'list']);
        $this->middleware('permission:user.create')->only(['create', 'store']);
        $this->middleware('permission:user.update')->only(['edit', 'update']);
        $this->middleware('permission:user.delete')->only('destroy');
        $this->middleware('permission:user.restore')->only('restore');

        $this->repository = $repository;
        $this->dataTable = $dataTable;
    }

    /** Index Page */
    public function index(Request $request): View|JsonResponse
    {
        if ($request->ajax()) {
            $query = $this->repository->query();
            return $this->dataTable->getData($query);
        }
        $users = User::select('name', 'id')->get();
        return view('company-selector.pages.user.index', compact('users'));
    }

    /** Load Create Modal */
    public function create()
    {
        $roles = Role::select('id', 'name')->orderBy('name', 'asc')->get();
        $modalData = [
            'title' => __('titles.user.add'),
            'uuid' => Str::uuid(),
            'data' => null,
            'form_mode' => 'create',
        ];
        $mode = 'create';
        return view('company-selector.pages.user.form', compact('mode', 'modalData', 'roles'));
    }

    /** Load View Modal */
    public function show(User $user)
    {
        $mode = 'view';
        $roles = Role::select('id', 'name')->orderBy('name', 'asc')->get();
        return view('company-selector.pages.user.form', compact('user', 'mode', 'roles'));
    }


    /** Store */
    public function store(StoreUserRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            $validated['is_approved'] = 1;
            $validated['uuid'] = (string) Str::uuid();
            
            $user = $this->repository->create(collect($validated)->except('role_id')->toArray());

            if (!empty($validated['role_id'])) {
                $role = Role::find($validated['role_id']);
                if ($role) {
                    $user->assignRole($role);
                }
            }

            return AjaxResponse::success(message: __('messages.user.created'), data: $user);
        } catch (Exception $e) {
            return AjaxResponse::error(message: __('messages.common.unexpected_error'), errors: $e->getMessage());
        }
    }

    /** Load Edit Modal */

    public function edit(User $user)
    {
        $mode = 'edit';
        $roles = Role::select('id', 'name')->orderBy('name', 'asc')->get();
        return view('company-selector.pages.user.form', compact('user', 'mode', 'roles'));
    }


    /** Update */
    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {

        try {
            $validated = $request->validated();
            
            if (isset($validated['role_id'])) {
                $role = Role::find($validated['role_id']);
                if ($role && !$user->hasRole($role)) {
                    $user->syncRoles([$role]);
                }
            }

            $updated = $this->repository->update($user, collect($validated)->except('role_id')->toArray());

            return AjaxResponse::success(message: __('messages.user.updated'), data: $updated);
        } catch (Exception $e) {
            return AjaxResponse::error(message: __('messages.common.unexpected_error'), errors: $e->getMessage());
        }
    }

    /** Delete */
    public function destroy(Request $request, User $user): JsonResponse
    {
        if (!$request->ajax()) {
            return AjaxResponse::error(message: __('messages.request.type'));
        }


        try {
            $this->repository->delete($user);
            return AjaxResponse::success(message: __('messages.user.deleted'));
        } catch (Exception $e) {
            return AjaxResponse::error(message: __('messages.common.unexpected_error'), errors: $e->getMessage());
        }
    }

    /** Restore */
    public function restore(Request $request, string $uuid): JsonResponse
    {
        if (!$request->ajax()) {
            return AjaxResponse::error(message: __('messages.request.type'));
        }

        try {
            $this->repository->restoreByUuid($uuid);
            return AjaxResponse::success(__('messages.user.restored'));
        } catch (Exception $e) {
            return AjaxResponse::error(__('messages.common.unexpected_error'), $e->getMessage());
        }
    }

    /** List (DataTable) */
    public function list(): JsonResponse
    {
        $data = $this->repository->all(
            columns: ['*'],
            orderBy: 'name'
        );

        return $this->dataTable->getData($data);
    }


    /** Profile  */
    public function profile()
    {
        $user = Auth::user(); // get logged-in user
        return view('company-selector.pages.user.profile', compact('user'));
    }

    /** change-password page */

    public function changepassword()
    {
        $user = Auth::user();
        return view('company-selector.pages.user.change-password', compact('user'));
    }

    public function updateprofile(Request $request){
        $user = Auth::user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users,username,' . $user->id,
        ]);

        $user->update($validated);

        return redirect()->back()->with('success', 'Profile updated successfully!');
    }
    public function updatePassword(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->back()->with('error', 'No authenticated user found.');
        }

        //  Track attempts
        $cacheKey = 'password_attempts_' . $user->id;
        $attemptData = Cache::get($cacheKey, [
            'count' => 1,
            'timestamp' => Carbon::now()
        ]);

        $timeDiff = Carbon::now()->diffInSeconds($attemptData['timestamp']);

        //  If user exceeded 3 attempts within 30s, block
        if ($timeDiff < 30 && $attemptData['count'] > 3) {
            $remaining = 30 - $timeDiff;
            return redirect()
                ->back()
                ->with('error', "Too many attempts. Please wait {$remaining} seconds before trying again.");
        }

        //  Reset count after 30s or increment if still inside 30s
        if ($timeDiff >= 30) {
            $attemptData = ['count' => 1, 'timestamp' => Carbon::now()];
        } else {
            $attemptData['count']++;
        }

        Cache::put($cacheKey, $attemptData, now()->addSeconds(30));

        //  Validate password (backend validation)
        $request->validate([
            'password' => 'required|string|min:8|confirmed',
        ], [
            'password.required' => 'Please enter a new password.',
            'password.min' => 'Password must be at least 8 characters.',
            'password.confirmed' => 'Password confirmation does not match.',
        ]);

        //  Update password
        $user->password = Hash::make($request->password);
        $user->save();

        //  Reset attempt count after success
        Cache::forget($cacheKey);

        return redirect()
            ->back()
            ->with('success', 'Password updated successfully!');
    }

}
