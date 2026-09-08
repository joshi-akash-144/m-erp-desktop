<?php

namespace App\Http\Controllers;

use App\Helpers\AjaxResponse;
use App\Models\UserLoginSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Routing\Controller;

class LoginUserController extends Controller
{

    public function __construct()
    {
        $this->middleware('permission:login_user.list')->only(['index', 'list']);
        $this->middleware('permission:login_user.force_logout')->only('forceLogout');

    }

    public function index()
    {
        return view('company-selector.pages.login-users.index');
    }

    public function list(Request $request): JsonResponse
    {
        $search = $request->get('search', '');
        $page = $request->get('page', 1);
        $size = $request->get('size', 50);

        $query = UserLoginSession::with('user:id,name,email')
            ->where('user_id', '!=', auth()->id())
            ->whereNull('logout_at')
            ->whereExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('sessions')
                    ->whereColumn('sessions.id', 'user_login_sessions.session_id');
            });

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('user', function ($uq) use ($search) {
                    $uq->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                })->orWhere('company_name', 'like', "%{$search}%")
                  ->orWhere('financial_year_name', 'like', "%{$search}%");
            });
        }

        $paginator = $query->orderByDesc('login_at')->paginate($size, ['*'], 'page', $page);

        $data = $paginator->map(function ($session) {
            return [
                'id' => $session->id,
                'name' => $session->user->name ?? '—',
                'email' => $session->user->email ?? '—',
                'login_at' => $session->login_at ? $session->login_at->format('d-m-Y H:i:s') : '—',
                'company_name' => $session->company_name ?? '—',
                'financial_year_name' => $session->financial_year_name ?? '—',
            ];
        });

        return response()->json([
            'data' => $data,
            'last_page' => $paginator->lastPage(),
            'total' => $paginator->total(),
            'permissions' => [
                'force_logout' => auth()->user()->can('login_user.force_logout') ?? false,
            ]
        ]);
    }

    public function forceLogout(Request $request, int $id): JsonResponse
    {
        $session = UserLoginSession::whereNull('logout_at')->findOrFail($id);

        if ($session->user_id === auth()->id()) {
            return AjaxResponse::error('You cannot force logout your own session.');
        }

        // Delete the session from the sessions table
        DB::table('sessions')->where('id', $session->session_id)->delete();

        $session->update(['logout_at' => now()]);

        return AjaxResponse::success('User has been logged out successfully.');
    }
}
