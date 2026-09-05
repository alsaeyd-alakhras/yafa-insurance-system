<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\MedicalDepartment;
use App\Models\RoleUser;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    private const RECEPTIONIST_DEFAULT_ABILITIES = [
        'visits.view',
        'visits.create',
        'visits.update',
        'employees.view',
        'dependents.view',
    ];

    private const DEPARTMENT_USER_DEFAULT_ABILITIES = [
        'visits.view',
        'visits.create',
        'visits.update',
        'employees.view',
        'dependents.view',
    ];

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $this->authorize('view', User::class);

        $users = User::orderBy('name')->paginate(20);

        return view('dashboard.users.index', compact('users'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $this->authorize('create', User::class);

        $user = new User(['role' => 'receptionist']);
        $medicalDepartments = MedicalDepartment::where('is_active', true)->orderBy('name')->get();

        return view('dashboard.users.create', compact('user', 'medicalDepartments'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->authorize('create', User::class);

        $validated = $request->validate([
            'name' => 'required',
            'username' => 'required|string|unique:users,username',
            'email' => 'nullable|email',
            'password' => 'required|same:confirm_password',
            'confirm_password' => 'required|same:password',
            'role' => 'required|in:admin,receptionist,department_user',
            'medical_department_id' => 'required_if:role,department_user|nullable|exists:medical_departments,id',
        ], [
            'password.same' => 'كلمة المرور غير متطابقة',
            'confirm_password.same' => 'كلمة المرور غير متطابقة',
        ]);

        $abilities = $this->normalizeAbilitiesForRole(
            $validated['role'],
            $request->input('abilities', [])
        );

        DB::beginTransaction();
        try {
            $path = null;
            if ($request->hasFile('avatarUpload')) {
                $path = $request->file('avatarUpload')->store('avatars', 'public');
            }

            $user = User::create([
                'name' => $validated['name'],
                'username' => $validated['username'],
                'email' => $validated['email'] ?? null,
                'password' => $validated['password'],
                'role' => $validated['role'],
                'medical_department_id' => $validated['role'] === 'department_user'
                    ? $validated['medical_department_id']
                    : null,
                'avatar' => $path,
            ]);

            foreach ($abilities as $ability) {
                RoleUser::create([
                    'role_name' => $ability,
                    'user_id' => $user->id,
                    'ability' => 'allow',
                ]);
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->route('dashboard.users.index')->with('success', 'تم اضافة مستخدم جديد');
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        if (Auth::user()->id != $user->id && ! Auth::user()->can('view', User::class)) {
            abort(403);
        }
        $profile = Auth::user()->id == $user->id;
        $logs = ActivityLog::where('user_id', $user->id)->orderBy('created_at', 'DESC')->paginate(20);

        return view('dashboard.users.show', compact('user', 'logs', 'profile'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function settings(Request $request)
    {
        $user = Auth::user();

        return view('dashboard.users.settings', compact('user'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, User $user)
    {
        $this->authorize('update', User::class);
        $medicalDepartments = MedicalDepartment::where('is_active', true)->orderBy('name')->get();

        return view('dashboard.users.edit', compact('user', 'medicalDepartments'));
    }

    /**
     * Update the authenticated user's profile settings.
     */
    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        $rules = [
            'name' => 'required',
            'username' => 'required|string|unique:users,username,'.$user->id,
            'email' => 'nullable|email',
        ];

        if ($request->filled('password')) {
            $rules['password'] = 'required|same:confirm_password';
            $rules['confirm_password'] = 'required|same:password';
        }

        $validated = $request->validate($rules, [
            'password.same' => 'كلمة المرور غير متطابقة',
            'confirm_password.same' => 'كلمة المرور غير متطابقة',
        ]);

        DB::beginTransaction();
        try {
            $oldAvatar = $user->avatar;

            if ($request->hasFile('avatarUpload')) {
                if ($oldAvatar !== null) {
                    Storage::disk('public')->delete($oldAvatar);
                }
                $path = $request->file('avatarUpload')->store('avatars', 'public');
            } else {
                $path = $user->avatar;
            }

            $updateData = [
                'name' => $validated['name'],
                'username' => $validated['username'],
                'email' => $request->email,
                'avatar' => $path,
            ];

            if ($request->filled('password')) {
                $updateData['password'] = $request->password;
            }

            $user->update($updateData);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

        return redirect()->route('dashboard.profile.settings')->with('success', 'تم تحديث الملف الشخصي');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user)
    {
        $this->authorize('update', User::class);

        $rules = [
            'name' => 'required',
            'username' => 'required|string|unique:users,username,'.$user->id,
            'email' => 'nullable|email',
            'role' => 'required|in:admin,receptionist,department_user',
            'medical_department_id' => 'required_if:role,department_user|nullable|exists:medical_departments,id',
        ];

        if ($request->filled('password')) {
            $rules['password'] = 'required|same:confirm_password';
            $rules['confirm_password'] = 'required|same:password';
        }

        $validated = $request->validate($rules, [
            'password.same' => 'كلمة المرور غير متطابقة',
            'confirm_password.same' => 'كلمة المرور غير متطابقة',
        ]);

        DB::beginTransaction();
        try {
            $oldAvatar = $user->avatar;

            $path = $oldAvatar;
            if ($request->hasFile('avatarUpload')) {
                if ($oldAvatar !== null) {
                    Storage::disk('public')->delete($oldAvatar);
                }
                $path = $request->file('avatarUpload')->store('avatars', 'public');
            }

            $updateData = [
                'name' => $validated['name'],
                'username' => $validated['username'],
                'email' => $validated['email'] ?? null,
                'avatar' => $path,
                'role' => $validated['role'],
                'medical_department_id' => $validated['role'] === 'department_user'
                    ? $validated['medical_department_id']
                    : null,
            ];

            if ($request->filled('password')) {
                $updateData['password'] = $request->password;
            }

            $user->update($updateData);

            $abilities = $this->normalizeAbilitiesForRole(
                $validated['role'],
                $request->input('abilities', [])
            );
            $this->syncUserAbilities($user, $abilities);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

        return redirect()->route('dashboard.users.index')->with('success', 'تم تعديل المستخدم');
    }

    private function normalizeAbilitiesForRole(string $role, array $abilities): array
    {
        $defaultAbilities = match ($role) {
            'receptionist' => self::RECEPTIONIST_DEFAULT_ABILITIES,
            'department_user' => self::DEPARTMENT_USER_DEFAULT_ABILITIES,
            default => [],
        };

        if ($defaultAbilities !== []) {
            return array_values(array_unique(array_merge($defaultAbilities, $abilities)));
        }

        return array_values(array_unique($abilities));
    }

    private function syncUserAbilities(User $user, array $abilities): void
    {
        $roleOld = RoleUser::where('user_id', $user->id)->pluck('role_name')->toArray();

        foreach ($roleOld as $role) {
            if (! in_array($role, $abilities, true)) {
                RoleUser::where('user_id', $user->id)->where('role_name', $role)->delete();
            }
        }

        foreach ($abilities as $role) {
            $roleRecord = RoleUser::where('user_id', $user->id)->where('role_name', $role)->first();
            if ($roleRecord === null) {
                RoleUser::create([
                    'role_name' => $role,
                    'user_id' => $user->id,
                    'ability' => 'allow',
                ]);
            } else {
                $roleRecord->update(['ability' => 'allow']);
            }
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, User $user)
    {
        $this->authorize('delete', User::class);
        abort_if($user->id === auth()->id(), 403, 'لا يمكنك حذف حسابك الخاص.');
        abort_if($user->role === 'admin' && ! auth()->user()->super_admin, 403, 'فقط المدير العام (super admin) يقدر يحذف حساب أدمن آخر.');
        if ($user->avatar != null) {
            Storage::disk('public')->delete($user->avatar);
        }
        $user->delete();

        return redirect()->route('dashboard.users.index')->with('success', 'تم حذف المستخدم');
    }
}
