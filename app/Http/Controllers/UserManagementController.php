<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Role;

class UserManagementController extends Controller
{
    protected string $defaultPassword = 'password123';

    protected array $allowedRoles = [
        'Master Admin',
        'Admin GA',
        'Admin Produksi',
        'Kepala Produksi',
        'SPV Operasional',
        'Supervisor Operasional',
        'Manager Operasional',
        'Manager Finance',
        'Direktur Operasional',
    ];

    protected function authorizeMasterAdmin(Request $request): void
    {
        abort_unless($request->user()?->hasRole('Master Admin'), 403);
    }

    public function index(Request $request)
    {
        $this->authorizeMasterAdmin($request);

        $search = trim((string) $request->query('search'));

        $users = User::query()
            ->with('roles')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($nestedQuery) use ($search) {
                    $nestedQuery
                        ->where('name', 'like', '%' . $search . '%')
                        ->orWhere('email', 'like', '%' . $search . '%');
                });
            })
            ->orderBy('name')
            ->simplePaginate(10)
            ->withQueryString();

        return view('users.index', [
            'users' => $users,
            'search' => $search,
            'roles' => $this->allowedRoles,
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizeMasterAdmin($request);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'role' => 'required|string|in:' . implode(',', $this->allowedRoles),
            'password' => ['required', 'confirmed', Password::min(8)],
            'profile_photo' => 'nullable|file|mimetypes:image/jpeg,image/png,image/webp,image/gif,image/avif|max:10240',
        ]);

        Role::findOrCreate($validated['role']);

        if ($request->hasFile('profile_photo')) {
            $validated['profile_photo_path'] = $request->file('profile_photo')->store('users', 'public');
        }

        $user = User::create([
            'name' => $validated['name'],
            'email' => strtolower($validated['email']),
            'password' => Hash::make($validated['password']),
            'profile_photo_path' => $validated['profile_photo_path'] ?? null,
        ]);

        $user->syncRoles([$validated['role']]);

        return redirect()->route('users.index')
            ->with('success', 'Akun baru berhasil ditambahkan.')
            ->with('generated_password', $validated['password']);
    }

    public function update(Request $request, User $user)
    {
        $this->authorizeMasterAdmin($request);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'role' => 'required|string|in:' . implode(',', $this->allowedRoles),
            'profile_photo' => 'nullable|file|mimetypes:image/jpeg,image/png,image/webp,image/gif,image/avif|max:10240',
        ]);

        Role::findOrCreate($validated['role']);

        if ($request->hasFile('profile_photo')) {
            if ($user->profile_photo_path) {
                Storage::disk('public')->delete($user->profile_photo_path);
            }

            $validated['profile_photo_path'] = $request->file('profile_photo')->store('users', 'public');
        }

        $user->update([
            'name' => $validated['name'],
            'email' => strtolower($validated['email']),
            'profile_photo_path' => $validated['profile_photo_path'] ?? $user->profile_photo_path,
        ]);

        $user->syncRoles([$validated['role']]);

        return redirect()->route('users.index')
            ->with('success', 'Data akun berhasil diupdate.');
    }

    public function resetPassword(Request $request, User $user)
    {
        $this->authorizeMasterAdmin($request);

        $validated = $request->validate([
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        return redirect()->route('users.index')
            ->with('success', 'Password user berhasil diperbarui.')
            ->with('generated_password', $validated['password']);
    }

    public function resetToDefaultPassword(Request $request, User $user)
    {
        $this->authorizeMasterAdmin($request);

        $user->update([
            'password' => Hash::make($this->defaultPassword),
        ]);

        return redirect()->route('users.index')
            ->with('success', 'Password user berhasil direset ke password default.')
            ->with('generated_password', $this->defaultPassword);
    }

    public function destroy(Request $request, User $user)
    {
        $this->authorizeMasterAdmin($request);

        if ((int) $request->user()->id === (int) $user->id) {
            return redirect()->route('users.index')
                ->with('error', 'Akun Master Admin yang sedang login tidak bisa dihapus.');
        }

        if ($user->profile_photo_path) {
            Storage::disk('public')->delete($user->profile_photo_path);
        }

        $user->delete();

        return redirect()->route('users.index')
            ->with('success', 'Akun user berhasil dihapus.');
    }
}
