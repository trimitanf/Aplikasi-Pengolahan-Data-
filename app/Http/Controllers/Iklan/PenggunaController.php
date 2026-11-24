<?php

namespace App\Http\Controllers\Iklan;

use App\Http\Controllers\Controller;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PenggunaController extends Controller
{
    public function index(Request $request): View
    {
        $query = User::query();

        if ($request->filled('cari')) {
            $keyword = '%'.trim($request->query('cari')).'%';
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'like', $keyword)
                    ->orWhere('username', 'like', $keyword);
            });
        }

        if ($request->filled('role')) {
            $query->where('role', $request->query('role'));
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->query('status') === 'aktif');
        }

        $users = $query->orderBy('role', 'asc')
            ->orderBy('name', 'asc')
            ->paginate(15)
            ->withQueryString();

        $stats = [
            'total_user' => User::count(),
            'total_admin' => User::where('role', 'admin')->count(),
            'total_evaluator' => User::where('role', 'evaluator')->count(),
            'total_evaluator_penandaan' => User::where('role', 'evaluator_penandaan')->count(),
            'total_aktif' => User::where('is_active', true)->count(),
        ];

        return view('pengguna.index', [
            'users' => $users,
            'stats' => $stats,
            'cari' => $request->query('cari'),
            'filterRole' => $request->query('role'),
            'filterStatus' => $request->query('status'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $rawUsername = (string) $request->input('username', '');
        $cleanedUsername = strtolower(trim($rawUsername));
        $cleanedUsername = ltrim($cleanedUsername, '#');
        $cleanedUsername = preg_replace('/\s+/', '_', $cleanedUsername);

        if ($cleanedUsername === '' && $request->filled('name')) {
            $baseUsername = Str::slug($request->input('name'), '_') ?: 'evaluator';
            $candidate = $baseUsername;
            $counter = 1;
            while (User::where('username', $candidate)->exists()) {
                $candidate = $baseUsername.'_'.$counter++;
            }
            $cleanedUsername = $candidate;
        }

        $request->merge([
            'name' => trim((string) $request->input('name')),
            'username' => $cleanedUsername,
        ]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => [
                'required',
                'string',
                'max:100',
                'regex:/^[a-zA-Z0-9._-]+$/',
                'unique:users,username',
            ],
            'password' => ['required', 'string', 'min:6'],
            'role' => ['required', 'string', 'in:admin,evaluator,evaluator_penandaan'],
            'is_active' => ['nullable'],
        ], [
            'name.required' => 'Nama lengkap wajib diisi.',
            'username.required' => 'ID / Username login wajib diisi.',
            'username.regex' => 'ID / Username hanya boleh berisi huruf, angka, titik (.), strip (-), dan garis bawah (_).',
            'username.unique' => 'ID / Username sudah digunakan oleh pengguna lain.',
            'password.required' => 'NIP / Password awal wajib diisi.',
            'password.min' => 'NIP / Password minimal 6 karakter.',
            'role.required' => 'Role pengguna wajib dipilih.',
            'role.in' => 'Role yang dipilih tidak valid.',
        ]);

        User::create([
            'name' => $validated['name'],
            'username' => $validated['username'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('pengguna.index')
            ->with('status', "Pengguna '{$validated['name']}' dengan role ".strtoupper($validated['role'])." berhasil ditambahkan.");
    }

    public function update(Request $request, User $pengguna): RedirectResponse
    {
        $rawUsername = (string) $request->input('username', '');
        $cleanedUsername = strtolower(trim($rawUsername));
        $cleanedUsername = ltrim($cleanedUsername, '#');
        $cleanedUsername = preg_replace('/\s+/', '_', $cleanedUsername);

        if ($cleanedUsername === '') {
            $cleanedUsername = $pengguna->username;
        }

        $request->merge([
            'name' => trim((string) $request->input('name')),
            'username' => $cleanedUsername,
        ]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => [
                'required',
                'string',
                'max:100',
                'regex:/^[a-zA-Z0-9._-]+$/',
                Rule::unique('users', 'username')->ignore($pengguna->id),
            ],
            'password' => ['nullable', 'string', 'min:6'],
            'role' => ['required', 'string', 'in:admin,evaluator,evaluator_penandaan'],
            'is_active' => ['nullable'],
        ], [
            'name.required' => 'Nama lengkap wajib diisi.',
            'username.required' => 'ID / Username login wajib diisi.',
            'username.regex' => 'ID / Username hanya boleh berisi huruf, angka, titik (.), strip (-), dan garis bawah (_).',
            'username.unique' => 'ID / Username sudah digunakan oleh pengguna lain.',
            'password.min' => 'NIP baru minimal 6 karakter jika ingin diperbarui.',
            'role.required' => 'Role pengguna wajib dipilih.',
            'role.in' => 'Role yang dipilih tidak valid.',
        ]);

        // Cegah admin mengubah role dirinya sendiri menjadi non-admin
        if ($pengguna->id === auth()->id() && $validated['role'] !== 'admin') {
            return back()->with('error', 'Anda tidak dapat mengubah role akun Anda sendiri dari Administrator.');
        }

        // Cegah admin menonaktifkan akunnya sendiri (mencegah terkunci dari sistem)
        if ($pengguna->id === auth()->id() && ! $request->boolean('is_active')) {
            return back()->with('error', 'Anda tidak dapat menonaktifkan akun Anda sendiri.');
        }

        $data = [
            'name' => $validated['name'],
            'username' => $validated['username'],
            'role' => $validated['role'],
            'is_active' => $request->boolean('is_active', false),
        ];

        if (! empty($validated['password'])) {
            $data['password'] = Hash::make($validated['password']);
        }

        $pengguna->update($data);

        return redirect()->route('pengguna.index')
            ->with('status', "Data pengguna '{$pengguna->name}' berhasil diperbarui.");
    }

    public function updateFallback(Request $request): RedirectResponse
    {
        $userId = $request->input('user_id');
        if ($userId && $user = User::find($userId)) {
            return $this->update($request, $user);
        }

        return redirect()->route('pengguna.index')->with('error', 'Pengguna yang ingin diperbarui tidak ditemukan.');
    }

    public function destroy(Request $request, User $pengguna): RedirectResponse
    {
        if ($pengguna->id === auth()->id()) {
            return back()->with('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
        }

        if ($pengguna->role === 'admin' && User::where('role', 'admin')->count() <= 1) {
            return back()->with('error', 'Tidak dapat menghapus satu-satunya Administrator di sistem.');
        }

        $nama = $pengguna->name;
        $pengguna->delete();

        return redirect()->route('pengguna.index')
            ->with('status', "Pengguna '{$nama}' berhasil dihapus dari sistem.");
    }

    public function destroyFallback(Request $request): RedirectResponse
    {
        $userId = $request->input('user_id');
        if ($userId && $user = User::find($userId)) {
            return $this->destroy($request, $user);
        }

        return redirect()->route('pengguna.index')->with('error', 'Pengguna yang ingin dihapus tidak ditemukan.');
    }
}
