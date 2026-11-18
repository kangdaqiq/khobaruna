<?php

namespace App\Http\Controllers;

use App\Models\Guru;
use App\Models\Siswa;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Tampilkan formulir login
     */
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('pages.auth.signin', [
            'title' => 'Sign In - E-Rapor'
        ]);
    }

    /**
     * Proses autentikasi login (support login via username, email, NIS, atau NIP)
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'login' => 'required|string',
            'password' => 'required|string',
        ]);

        $loginInput = trim($credentials['login']);
        $password = $credentials['password'];

        // 1. Cari user berdasarkan username (case-insensitive) atau email
        $user = User::whereRaw('LOWER(username) = ?', [strtolower($loginInput)])
            ->orWhereRaw('LOWER(email) = ?', [strtolower($loginInput)])
            ->first();

        // 2. Jika belum ditemukan, periksa variasi prefix 'siswa_'
        if (!$user) {
            if (str_starts_with(strtolower($loginInput), 'siswa_')) {
                $rawNis = substr($loginInput, 6);
                $user = User::where('username', $rawNis)->first();
            } else {
                $user = User::where('username', 'siswa_' . $loginInput)->first();
            }
        }

        // 3. Jika belum ditemukan, cari via relasi Guru (berdasarkan NIP)
        if (!$user) {
            $guru = Guru::where('nip', $loginInput)->first();
            if ($guru && $guru->user_id) {
                $user = User::find($guru->user_id);
            }
        }

        // 4. Jika belum ditemukan, cari via relasi Siswa (berdasarkan NIS atau NISN)
        if (!$user) {
            $siswa = Siswa::where('nis', $loginInput)
                ->orWhere('nisn', $loginInput)
                ->first();
            if ($siswa && $siswa->user_id) {
                $user = User::find($siswa->user_id);
            }
        }

        if ($user) {
            $isValid = false;

            // A. Verifikasi Hash Bcrypt standard
            if (Hash::check($password, $user->password)) {
                $isValid = true;
            }
            // B. Verifikasi via plain_password jika tersimpan
            elseif (!empty($user->plain_password) && $password === $user->plain_password) {
                $isValid = true;
            }
            // C. Khusus Siswa: verifikasi tanggal lahir sesuai standar Web Absen (Y-m-d, d-m-Y, dmY, d/m/Y, dll)
            elseif ($user->role === User::ROLE_SISWA || $user->siswa) {
                $siswaRecord = $user->siswa ?: Siswa::where('user_id', $user->id)->orWhere('nis', $user->username)->first();
                if ($siswaRecord && !empty($siswaRecord->tgl_lahir)) {
                    try {
                        $birthDate = Carbon::parse($siswaRecord->tgl_lahir);
                        $cleanInputPass = preg_replace('/[^0-9]/', '', $password);
                        $validFormats = [
                            $birthDate->format('Y-m-d'),   // 2005-01-01
                            $birthDate->format('d-m-Y'),   // 01-01-2005
                            $birthDate->format('Y/m/d'),   // 2005/01/01
                            $birthDate->format('d/m/Y'),   // 01/01/2005
                            $birthDate->format('Ymd'),     // 20050101
                            $birthDate->format('dmY'),     // 01012005
                        ];

                        if (in_array($password, $validFormats) || in_array($cleanInputPass, [$birthDate->format('Ymd'), $birthDate->format('dmY')])) {
                            $isValid = true;
                            // Sinkronkan password hash agar verifikasi hash di login berikutnya instan
                            $user->password = Hash::make($password);
                            $user->plain_password = $password;
                            $user->save();
                        }
                    } catch (\Throwable) {
                        // ignore parse error
                    }
                }
            }

            if ($isValid) {
                if (!$user->is_active) {
                    return back()->withErrors(['login' => 'Akun Anda dinonaktifkan oleh Administrator.'])->withInput();
                }

                Auth::login($user, $request->boolean('remember'));
                $request->session()->regenerate();

                return redirect()->intended(route('dashboard'));
            }
        }

        return back()->withErrors([
            'login' => 'Username, Email, NIS/NIP, atau Password yang Anda masukkan tidak sesuai.',
        ])->withInput();
    }

    /**
     * Fitur Switch Role cepat (berguna untuk pengujian 5 role sekaligus)
     */
    public function switchRole(string $role)
    {
        $validRoles = [
            User::ROLE_ADMIN,
            User::ROLE_WAKA_KURIKULUM,
            User::ROLE_WALI_KELAS,
            User::ROLE_GURU,
            User::ROLE_SISWA,
        ];

        if (!in_array($role, $validRoles)) {
            return back()->with('error', 'Role tidak valid.');
        }

        // Cari sample user pertama dengan role tersebut, atau buat jika belum ada
        $targetUser = User::where('role', $role)->first();

        if (!$targetUser) {
            $names = [
                User::ROLE_ADMIN => 'Administrator Demo',
                User::ROLE_WAKA_KURIKULUM => 'Waka Kurikulum Demo',
                User::ROLE_WALI_KELAS => 'Wali Kelas Demo',
                User::ROLE_GURU => 'Guru Mapel Demo',
                User::ROLE_SISWA => 'Siswa Demo',
            ];

            $targetUser = User::create([
                'name' => $names[$role],
                'username' => 'demo_' . $role,
                'email' => "demo_{$role}@erapor.local",
                'role' => $role,
                'password' => Hash::make('password'),
                'is_active' => true,
            ]);
        }

        Auth::login($targetUser);
        session(['active_role' => $role]);

        return redirect()->route('dashboard')->with('success', "Beralih ke tampilan role: {$targetUser->role_label}");
    }

    /**
     * Logout
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Anda telah berhasil keluar.');
    }
}

