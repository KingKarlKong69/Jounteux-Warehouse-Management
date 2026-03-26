<?php

namespace App\Http\Controllers;

use App\Enums\AuditAction;
use App\Enums\BlockReason;
use App\Enums\UserRole;
use App\Events\AuditLogEvent;
use App\Http\Requests\DeleteUserRequest;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Traits\HasIndexFilters;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class UserController extends Controller
{
    use HasIndexFilters;
    /**
     * Display paginated user listing with search, role tabs, date filters.
     */
    public function index(Request $request)
    {
        Gate::authorize('viewAny', User::class);

        $query = User::query();

        // ── Search ──
        $this->applySearch($query, $request->input('search'), ['name', 'email']);

        // ── Role tab filter ──
        if ($role = $request->input('role')) {
            if (in_array($role, UserRole::values())) {
                $query->ofRole($role);
            }
        }

        // ── Date preset filter ──
        if ($preset = $request->input('date_preset')) {
            $query->datePreset($preset);
        }

        // ── Custom date range ──
        $query->dateBetween(
            $request->input('date_from'),
            $request->input('date_to')
        );

        // ── Pagination ──
        $perPage = min($request->input('per_page', 15), 100);
        $users = $query->orderByDesc('created_at')->paginate($perPage)->withQueryString();

        return Inertia::render('Admin/Users/Index', [
            'users' => [
                'data' => UserResource::collection($users->items())->toArray(request()),
                'meta' => [
                    'current_page' => $users->currentPage(),
                    'last_page' => $users->lastPage(),
                    'per_page' => $users->perPage(),
                    'total' => $users->total(),
                    'from' => $users->firstItem(),
                    'to' => $users->lastItem(),
                ],
            ],
            'filters' => [
                'search' => $request->input('search', ''),
                'role' => $request->input('role', ''),
                'date_preset' => $request->input('date_preset', ''),
                'date_from' => $request->input('date_from', ''),
                'date_to' => $request->input('date_to', ''),
                'per_page' => $perPage,
            ],
            'roleTabs' => ['all', ...UserRole::values()],
            'stats' => [
                'total' => User::count(),
                'admins' => User::where('role', 'admin')->count(),
                'staff' => User::where('role', 'staff')->count(),
                'blocked' => User::where('is_blocked', true)->count(),
            ],
        ]);
    }

    /**
     * Show create user form.
     */
    public function create()
    {
        Gate::authorize('create', User::class);

        return Inertia::render('Admin/Users/Create', [
            'roles' => UserRole::values(),
        ]);
    }

    /**
     * Store a new user.
     */
    public function store(StoreUserRequest $request)
    {
        Gate::authorize('create', User::class);

        $data = $request->validated();
        $data['password'] = $data['password']; // will be hashed by cast
        $data['is_blocked'] = false;
        $data['failed_login_attempts'] = 0;

        $user = User::create($data);

        return redirect()
            ->route('admin.users.index')
            ->with('success', "User \"{$user->name}\" created successfully.");
    }

    /**
     * Show edit user form.
     */
    public function edit(User $user)
    {
        Gate::authorize('update', $user);

        return Inertia::render('Admin/Users/Edit', [
            'targetUser' => (new UserResource($user))->toArray(request()),
            'roles' => UserRole::values(),
        ]);
    }

    /**
     * Update user.
     */
    public function update(UpdateUserRequest $request, User $user)
    {
        Gate::authorize('update', $user);

        $data = $request->validated();

        // Only update password if provided
        if (empty($data['password'])) {
            unset($data['password']);
        }

        $user->update($data);

        return redirect()
            ->route('admin.users.index')
            ->with('success', "User \"{$user->name}\" updated successfully.");
    }

    /**
     * Block a user.
     */
    public function block(Request $request, User $user)
    {
        Gate::authorize('block', $user);

        // Update without triggering Auditable trait (we log explicitly below)
        User::withoutEvents(function () use ($user) {
            $user->update([
                'is_blocked' => true,
                'blocked_at' => now(),
                'block_reason' => BlockReason::MANUAL_ADMIN_BLOCK->value,
            ]);
        });

        // Audit log
        event(new AuditLogEvent(
            user: Auth::user(),
            action: AuditAction::BLOCKED->value,
            auditableType: User::class,
            auditableId: $user->id,
            oldValues: ['is_blocked' => false],
            newValues: [
                'is_blocked' => true,
                'block_reason' => BlockReason::MANUAL_ADMIN_BLOCK->value,
                'blocked_by' => Auth::user()->name,
            ],
            ipAddress: $request->ip(),
            metadata: [
                'triggered_by' => 'user',
                'model_identifier' => $user->name,
                'block_reason' => BlockReason::MANUAL_ADMIN_BLOCK->value,
            ],
            eventLabel: 'Account Blocked',
        ));

        return back()->with('success', "User \"{$user->name}\" has been blocked.");
    }

    /**
     * Unblock a user.
     */
    public function unblock(Request $request, User $user)
    {
        Gate::authorize('unblock', $user);

        $oldBlockReason = $user->getRawOriginal('block_reason');

        // Update without triggering Auditable trait (we log explicitly below)
        User::withoutEvents(function () use ($user) {
            $user->update([
                'is_blocked' => false,
                'blocked_at' => null,
                'block_reason' => null,
                'failed_login_attempts' => 0,
            ]);
        });

        // Audit log
        event(new AuditLogEvent(
            user: Auth::user(),
            action: AuditAction::UPDATED->value,
            auditableType: User::class,
            auditableId: $user->id,
            oldValues: [
                'is_blocked' => true,
                'block_reason' => $oldBlockReason,
            ],
            newValues: [
                'is_blocked' => false,
                'block_reason' => null,
                'unblocked_by' => Auth::user()->name,
            ],
            ipAddress: $request->ip(),
            metadata: [
                'triggered_by' => 'user',
                'model_identifier' => $user->name,
                'previous_block_reason' => $oldBlockReason,
            ],
            eventLabel: 'Account Unblocked',
        ));

        return back()->with('success', "User \"{$user->name}\" has been unblocked.");
    }

    /**
     * Secure account deletion.
     * Requires the admin to type the target user's name and own password.
     */
    public function destroy(DeleteUserRequest $request, User $user)
    {
        Gate::authorize('delete', $user);

        $data = $request->validated();

        // Validate name match
        if (trim(strtolower($data['confirm_name'])) !== trim(strtolower($user->name))) {
            return back()->withErrors(['confirm_name' => 'The name you entered does not match the user\'s full name.']);
        }

        // Validate admin password
        if (!Hash::check($data['admin_password'], Auth::user()->password)) {
            return back()->withErrors(['admin_password' => 'Your admin password is incorrect.']);
        }

        // Cannot delete self
        if (Auth::id() === $user->id) {
            return back()->withErrors(['general' => 'You cannot delete your own account.']);
        }

        // Cannot delete last admin
        if ($user->isAdmin() && User::where('role', 'admin')->count() <= 1) {
            return back()->withErrors(['general' => 'Cannot delete the last admin account.']);
        }

        $userName = $user->name;

        // Audit log before deletion
        event(new AuditLogEvent(
            user: Auth::user(),
            action: AuditAction::DELETED->value,
            auditableType: User::class,
            auditableId: $user->id,
            oldValues: [
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ],
            newValues: [
                'deleted_by' => Auth::user()->name,
                'deletion_type' => 'permanent',
            ],
            ipAddress: $request->ip(),
            metadata: [
                'triggered_by' => 'user',
                'model_identifier' => $user->name,
                'deletion_type' => 'permanent',
                'deleted_user_email' => $user->email,
                'deleted_user_role' => $user->role,
            ],
            eventLabel: 'Account Permanently Deleted',
        ));

        // Permanent delete without triggering Auditable trait (already logged above)
        User::withoutEvents(function () use ($user) {
            $user->forceDelete();
        });

        return redirect()
            ->route('admin.users.index')
            ->with('success', "User \"{$userName}\" has been permanently deleted.");
    }
}
