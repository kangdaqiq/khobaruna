@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-(--breakpoint-2xl) p-4 md:p-6" x-data="{
        showAddModal: false,
        showEditModal: false,
        showResetModal: false,
        activeUser: {},
        editUser(user) {
            this.activeUser = JSON.parse(JSON.stringify(user));
            this.showEditModal = true;
        },
        resetPassword(user) {
            this.activeUser = JSON.parse(JSON.stringify(user));
            this.showResetModal = true;
        }
    }">
        <!-- Breadcrumb / Header -->
        <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-800 dark:text-white">Manajemen Akun Pengguna</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">Kelola akun dan hak akses pengguna (Admin, Waka Kurikulum, Wali Kelas, Guru, Siswa).</p>
            </div>
            <div class="flex items-center gap-2">
                <button @click="showAddModal = true" class="inline-flex items-center gap-2 rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-600 shadow-theme-xs">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    Tambah Akun Baru
                </button>
            </div>
        </div>

        @if(session('success'))
            <div class="mb-4 rounded-xl bg-emerald-50 border border-emerald-200 p-4 text-sm text-emerald-800 dark:bg-emerald-500/10 dark:border-emerald-500/20 dark:text-emerald-400">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="mb-4 rounded-xl bg-rose-50 border border-rose-200 p-4 text-sm text-rose-800 dark:bg-rose-500/10 dark:border-rose-500/20 dark:text-rose-400">
                {{ session('error') }}
            </div>
        @endif

        <!-- Role Filter Pills -->
        <div class="mb-6 flex flex-wrap gap-2">
            <a href="{{ route('admin.users.index') }}" class="rounded-lg px-3 py-1.5 text-xs font-medium {{ !request('role') ? 'bg-brand-500 text-white font-semibold' : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-400 hover:bg-gray-100' }} border border-gray-200 dark:border-gray-800 shadow-theme-xs">
                Semua ({{ number_format($roleCounts['total']) }})
            </a>
            <a href="{{ route('admin.users.index', ['role' => 'admin']) }}" class="rounded-lg px-3 py-1.5 text-xs font-medium {{ request('role') === 'admin' ? 'bg-brand-500 text-white font-semibold' : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-400 hover:bg-gray-100' }} border border-gray-200 dark:border-gray-800 shadow-theme-xs">
                Admin ({{ number_format($roleCounts['admin']) }})
            </a>
            <a href="{{ route('admin.users.index', ['role' => 'waka_kurikulum']) }}" class="rounded-lg px-3 py-1.5 text-xs font-medium {{ request('role') === 'waka_kurikulum' ? 'bg-brand-500 text-white font-semibold' : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-400 hover:bg-gray-100' }} border border-gray-200 dark:border-gray-800 shadow-theme-xs">
                Waka Kurikulum ({{ number_format($roleCounts['waka_kurikulum']) }})
            </a>
            <a href="{{ route('admin.users.index', ['role' => 'wali_kelas']) }}" class="rounded-lg px-3 py-1.5 text-xs font-medium {{ request('role') === 'wali_kelas' ? 'bg-brand-500 text-white font-semibold' : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-400 hover:bg-gray-100' }} border border-gray-200 dark:border-gray-800 shadow-theme-xs">
                Wali Kelas ({{ number_format($roleCounts['wali_kelas']) }})
            </a>
            <a href="{{ route('admin.users.index', ['role' => 'guru']) }}" class="rounded-lg px-3 py-1.5 text-xs font-medium {{ request('role') === 'guru' ? 'bg-brand-500 text-white font-semibold' : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-400 hover:bg-gray-100' }} border border-gray-200 dark:border-gray-800 shadow-theme-xs">
                Guru Mapel ({{ number_format($roleCounts['guru']) }})
            </a>
            <a href="{{ route('admin.users.index', ['role' => 'siswa']) }}" class="rounded-lg px-3 py-1.5 text-xs font-medium {{ request('role') === 'siswa' ? 'bg-brand-500 text-white font-semibold' : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-400 hover:bg-gray-100' }} border border-gray-200 dark:border-gray-800 shadow-theme-xs">
                Siswa ({{ number_format($roleCounts['siswa']) }})
            </a>
        </div>

        <!-- Table Card -->
        <div class="rounded-2xl border border-gray-200 bg-white shadow-theme-xs dark:border-gray-800 dark:bg-gray-900">
            <!-- Search Header -->
            <div class="p-4 border-b border-gray-100 dark:border-gray-800">
                <form method="GET" action="{{ route('admin.users.index') }}" class="flex flex-col sm:flex-row gap-3">
                    @if(request('role'))
                        <input type="hidden" name="role" value="{{ request('role') }}">
                    @endif
                    <div class="relative flex-1">
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama, username, atau email..." class="w-full rounded-lg border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-800/50 px-3.5 py-2 text-xs text-gray-800 dark:text-white placeholder-gray-400 focus:outline-hidden focus:border-brand-500">
                    </div>
                    <button type="submit" class="rounded-lg bg-gray-100 dark:bg-gray-800 px-4 py-2 text-xs font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-200">
                        Cari
                    </button>
                    @if(request('search'))
                        <a href="{{ route('admin.users.index', request()->only('role')) }}" class="rounded-lg border border-gray-200 dark:border-gray-700 px-3 py-2 text-xs text-gray-500 hover:bg-gray-50 flex items-center">
                            Reset
                        </a>
                    @endif
                </form>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto">
                <table class="w-full text-start text-xs text-gray-600 dark:text-gray-400">
                    <thead>
                        <tr class="border-b border-gray-100 dark:border-gray-800 uppercase text-gray-400 font-medium">
                            <th class="py-3 px-4 text-start">Pengguna</th>
                            <th class="py-3 px-4 text-start">Username / Email</th>
                            <th class="py-3 px-4 text-start">Role Sistem</th>
                            <th class="py-3 px-4 text-start">Status</th>
                            <th class="py-3 px-4 text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse($users as $u)
                            <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/30">
                                <td class="py-3 px-4">
                                    <div class="font-semibold text-gray-800 dark:text-white">{{ $u->name }}</div>
                                    <div class="text-[11px] text-gray-400">Terdaftar: {{ $u->created_at?->format('d/m/Y') }}</div>
                                </td>
                                <td class="py-3 px-4">
                                    <div class="flex items-center gap-1.5 flex-wrap">
                                        <span class="font-mono font-medium text-gray-800 dark:text-gray-200">{{ $u->username ?? '-' }}</span>
                                        @if($u->plain_password)
                                            <span class="inline-flex items-center rounded bg-gray-100 dark:bg-gray-800 px-1.5 py-0.5 text-[10px] font-mono text-gray-600 dark:text-gray-300" title="Password tersimpan / tersinkron">
                                                pwd: <span class="font-bold text-brand-600 dark:text-brand-400 ms-1">{{ $u->plain_password }}</span>
                                            </span>
                                        @endif
                                    </div>
                                    <div class="text-[11px] text-gray-400">{{ $u->email ?? '-' }}</div>
                                </td>
                                <td class="py-3 px-4">
                                    @php
                                        $badgeClasses = match($u->role) {
                                            'admin' => 'bg-brand-50 text-brand-700 dark:bg-brand-500/10 dark:text-brand-400',
                                            'waka_kurikulum' => 'bg-indigo-50 text-indigo-700 dark:bg-indigo-500/10 dark:text-indigo-400',
                                            'wali_kelas' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400',
                                            'guru' => 'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400',
                                            'siswa' => 'bg-blue-50 text-blue-700 dark:bg-blue-500/10 dark:text-blue-400',
                                            default => 'bg-gray-50 text-gray-700'
                                        };
                                    @endphp
                                    <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $badgeClasses }}">
                                        {{ $u->role_label }}
                                    </span>
                                </td>
                                <td class="py-3 px-4">
                                    @if($u->is_active)
                                        <span class="inline-flex items-center gap-1 text-emerald-600 text-xs font-medium">
                                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span> Aktif
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 text-gray-400 text-xs font-medium">
                                            <span class="h-1.5 w-1.5 rounded-full bg-gray-400"></span> Nonaktif
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-end">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <button @click="resetPassword({{ $u }})" class="rounded-lg bg-gray-100 dark:bg-gray-800 p-1.5 text-gray-600 hover:text-brand-600 dark:text-gray-300" title="Reset Password">
                                            <i class="fa-solid fa-key text-xs"></i>
                                        </button>
                                        <button @click="editUser({{ $u }})" class="rounded-lg bg-gray-100 dark:bg-gray-800 p-1.5 text-gray-600 hover:text-brand-600 dark:text-gray-300" title="Edit Akun">
                                            <i class="fa-solid fa-pen text-xs"></i>
                                        </button>
                                        @if($u->id !== auth()->id())
                                            <form action="{{ route('admin.users.destroy', $u->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus akun {{ $u->name }}?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="rounded-lg bg-rose-50 dark:bg-rose-500/10 p-1.5 text-rose-600 hover:bg-rose-100" title="Hapus Akun">
                                                <i class="fa-solid fa-trash-can text-xs"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-8 text-center text-gray-500">Tidak ada akun ditemukan.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="p-4 border-t border-gray-100 dark:border-gray-800">
                {{ $users->links() }}
            </div>
        </div>

        <!-- Modal Tambah Akun -->
        <div x-show="showAddModal" x-cloak class="fixed inset-0 z-99999 flex items-center justify-center bg-black/50 p-4">
            <div @click.outside="showAddModal = false" class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-theme-lg dark:bg-gray-900">
                <h3 class="text-lg font-bold text-gray-800 dark:text-white mb-4">Tambah Akun Pengguna Baru</h3>
                <form action="{{ route('admin.users.store') }}" method="POST">
                    @csrf
                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Nama Lengkap *</label>
                            <input type="text" name="name" required class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3.5 py-2 text-xs text-gray-800 dark:text-white">
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Username *</label>
                                <input type="text" name="username" required class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3.5 py-2 text-xs text-gray-800 dark:text-white" placeholder="Contoh: 1001 atau NIP">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Role Akun *</label>
                                <select name="role" required class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3.5 py-2 text-xs text-gray-800 dark:text-white">
                                    <option value="admin">Super Admin</option>
                                    <option value="waka_kurikulum">Waka Kurikulum</option>
                                    <option value="wali_kelas">Wali Kelas</option>
                                    <option value="guru">Guru Mapel</option>
                                    <option value="siswa">Siswa</option>
                                </select>
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Email (Opsional)</label>
                            <input type="email" name="email" class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3.5 py-2 text-xs text-gray-800 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Password *</label>
                            <input type="password" name="password" required minlength="6" class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3.5 py-2 text-xs text-gray-800 dark:text-white" placeholder="Minimal 6 karakter">
                        </div>
                    </div>
                    <div class="mt-6 flex justify-end gap-2">
                        <button type="button" @click="showAddModal = false" class="rounded-lg border border-gray-300 px-4 py-2 text-xs font-medium text-gray-700 dark:text-gray-300">Batal</button>
                        <button type="submit" class="rounded-lg bg-brand-500 px-4 py-2 text-xs font-semibold text-white hover:bg-brand-600">Simpan Akun</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal Edit Akun -->
        <div x-show="showEditModal" x-cloak class="fixed inset-0 z-99999 flex items-center justify-center bg-black/50 p-4">
            <div @click.outside="showEditModal = false" class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-theme-lg dark:bg-gray-900">
                <h3 class="text-lg font-bold text-gray-800 dark:text-white mb-4">Edit Akun Pengguna</h3>
                <form :action="'/admin/users/' + activeUser.id" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Nama Lengkap *</label>
                            <input type="text" name="name" x-model="activeUser.name" required class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3.5 py-2 text-xs text-gray-800 dark:text-white">
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Username *</label>
                                <input type="text" name="username" x-model="activeUser.username" required class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3.5 py-2 text-xs text-gray-800 dark:text-white">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Role Akun *</label>
                                <select name="role" x-model="activeUser.role" required class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3.5 py-2 text-xs text-gray-800 dark:text-white">
                                    <option value="admin">Super Admin</option>
                                    <option value="waka_kurikulum">Waka Kurikulum</option>
                                    <option value="wali_kelas">Wali Kelas</option>
                                    <option value="guru">Guru Mapel</option>
                                    <option value="siswa">Siswa</option>
                                </select>
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Email</label>
                            <input type="email" name="email" x-model="activeUser.email" class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3.5 py-2 text-xs text-gray-800 dark:text-white">
                        </div>
                        <div class="flex items-center gap-2 pt-2">
                            <input type="checkbox" id="edit_is_active" name="is_active" value="1" :checked="activeUser.is_active" class="rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                            <label for="edit_is_active" class="text-xs text-gray-700 dark:text-gray-300 font-medium">Status Akun Aktif</label>
                        </div>
                    </div>
                    <div class="mt-6 flex justify-end gap-2">
                        <button type="button" @click="showEditModal = false" class="rounded-lg border border-gray-300 px-4 py-2 text-xs font-medium text-gray-700 dark:text-gray-300">Batal</button>
                        <button type="submit" class="rounded-lg bg-brand-500 px-4 py-2 text-xs font-semibold text-white hover:bg-brand-600">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal Reset Password -->
        <div x-show="showResetModal" x-cloak class="fixed inset-0 z-99999 flex items-center justify-center bg-black/50 p-4">
            <div @click.outside="showResetModal = false" class="w-full max-w-sm rounded-2xl bg-white p-6 shadow-theme-lg dark:bg-gray-900">
                <h3 class="text-base font-bold text-gray-800 dark:text-white mb-2">Reset Password</h3>
                <p class="text-xs text-gray-500 mb-4">Set password baru untuk akun <strong x-text="activeUser.name"></strong>.</p>
                <form :action="'/admin/users/' + activeUser.id + '/reset-password'" method="POST">
                    @csrf
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Password Baru *</label>
                        <input type="password" name="password" required minlength="6" class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3.5 py-2 text-xs text-gray-800 dark:text-white" placeholder="Minimal 6 karakter">
                    </div>
                    <div class="mt-5 flex justify-end gap-2">
                        <button type="button" @click="showResetModal = false" class="rounded-lg border border-gray-300 px-4 py-2 text-xs font-medium text-gray-700 dark:text-gray-300">Batal</button>
                        <button type="submit" class="rounded-lg bg-amber-600 px-4 py-2 text-xs font-semibold text-white hover:bg-amber-700">Simpan Password</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

