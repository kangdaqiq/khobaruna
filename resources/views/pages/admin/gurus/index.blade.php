@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-(--breakpoint-2xl) p-4 md:p-6" x-data="{
        showAddModal: false,
        showEditModal: false,
        activeGuru: {},
        editGuru(guru) {
            this.activeGuru = JSON.parse(JSON.stringify(guru));
            this.showEditModal = true;
        }
    }">
        <!-- Header -->
        <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-800 dark:text-white">Data Pendidik & Tenaga Kependidikan</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">Kelola master data guru mapel, wali kelas, dan integrasi akun sistem.</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.sync.index') }}" class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-800 px-3.5 py-2 text-xs font-semibold text-gray-700 dark:text-gray-300 hover:bg-gray-50">
                    <i class="fa-solid fa-arrows-rotate text-[11px]"></i>
                    <span>Sinkronkan Data</span>
                </a>
                <button @click="showAddModal = true" class="inline-flex items-center gap-2 rounded-lg bg-brand-500 px-4 py-2 text-xs font-semibold text-white hover:bg-brand-600 shadow-theme-xs">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    Tambah Guru Baru
                </button>
            </div>
        </div>

        @if(session('success'))
            <div class="mb-4 rounded-xl bg-emerald-50 border border-emerald-200 p-4 text-sm text-emerald-800 dark:bg-emerald-500/10 dark:border-emerald-500/20 dark:text-emerald-400">
                {{ session('success') }}
            </div>
        @endif

        <!-- Stat Cards -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4 mb-6">
            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-theme-xs dark:border-gray-800 dark:bg-gray-900">
                <span class="text-xs text-gray-500 uppercase font-medium">Total Guru</span>
                <div class="mt-1 text-2xl font-bold text-gray-800 dark:text-white">{{ number_format($stats['total']) }}</div>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-theme-xs dark:border-gray-800 dark:bg-gray-900">
                <span class="text-xs text-gray-500 uppercase font-medium">Wali Kelas</span>
                <div class="mt-1 text-2xl font-bold text-emerald-600">{{ number_format($stats['wali_kelas']) }}</div>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-theme-xs dark:border-gray-800 dark:bg-gray-900">
                <span class="text-xs text-gray-500 uppercase font-medium">Guru Mapel</span>
                <div class="mt-1 text-2xl font-bold text-amber-600">{{ number_format($stats['non_wali']) }}</div>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-theme-xs dark:border-gray-800 dark:bg-gray-900">
                <span class="text-xs text-gray-500 uppercase font-medium">Akun Login Siap</span>
                <div class="mt-1 text-2xl font-bold text-brand-600">{{ number_format($stats['has_account']) }}</div>
            </div>
        </div>

        <!-- Table Card -->
        <div class="rounded-2xl border border-gray-200 bg-white shadow-theme-xs dark:border-gray-800 dark:bg-gray-900">
            <!-- Filter & Search -->
            <div class="p-4 border-b border-gray-100 dark:border-gray-800">
                <form method="GET" action="{{ route('admin.gurus.index') }}" class="flex flex-col sm:flex-row gap-3">
                    <div class="relative flex-1">
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama, NIP, no WA guru..." class="w-full rounded-lg border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-800/50 px-3.5 py-2 text-xs text-gray-800 dark:text-white placeholder-gray-400 focus:outline-hidden focus:border-brand-500">
                    </div>
                    <select name="wali_kelas" onchange="this.form.submit()" class="rounded-lg border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-800/50 px-3 py-2 text-xs text-gray-700 dark:text-gray-300">
                        <option value="">Semua Status Wali</option>
                        <option value="1" {{ request('wali_kelas') === '1' ? 'selected' : '' }}>Hanya Wali Kelas</option>
                        <option value="0" {{ request('wali_kelas') === '0' ? 'selected' : '' }}>Bukan Wali Kelas</option>
                    </select>
                    <button type="submit" class="rounded-lg bg-gray-100 dark:bg-gray-800 px-4 py-2 text-xs font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-200">
                        Cari
                    </button>
                    @if(request('search') || request('wali_kelas') !== null)
                        <a href="{{ route('admin.gurus.index') }}" class="rounded-lg border border-gray-200 dark:border-gray-700 px-3 py-2 text-xs text-gray-500 hover:bg-gray-50 flex items-center">
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
                            <th class="py-3 px-4 text-start">Nama & NIP Guru</th>
                            <th class="py-3 px-4 text-start">Kontak (WA / Email)</th>
                            <th class="py-3 px-4 text-start">Status Penugasan</th>
                            <th class="py-3 px-4 text-start">Akun Login E-Rapor</th>
                            <th class="py-3 px-4 text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse($gurus as $g)
                            <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/30">
                                <td class="py-3 px-4">
                                    <div class="font-semibold text-gray-800 dark:text-white">{{ $g->nama }}</div>
                                    <div class="text-[11px] text-gray-400 font-mono">NIP: {{ $g->nip ?? '-' }}</div>
                                </td>
                                <td class="py-3 px-4">
                                    <div>{{ $g->no_wa ?? '-' }}</div>
                                    <div class="text-[11px] text-gray-400">{{ $g->email ?? '-' }}</div>
                                </td>
                                <td class="py-3 px-4">
                                    @if($g->is_wali_kelas)
                                        <span class="inline-flex rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400">
                                            Wali Kelas {{ $g->kelasWali->nama_kelas ?? '' }}
                                        </span>
                                    @else
                                        <span class="inline-flex rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                                            Guru Mapel
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3 px-4">
                                    @if($g->user)
                                        <div class="font-mono text-gray-700 dark:text-gray-300">user: {{ $g->user->username }}</div>
                                        <span class="inline-flex items-center gap-1 text-[11px] text-emerald-600">
                                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span> Role: {{ $g->user->role_label }}
                                        </span>
                                    @else
                                        <span class="text-rose-500 text-xs">Belum ada akun</span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-end">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <button @click="editGuru({{ $g }})" class="rounded-lg bg-gray-100 dark:bg-gray-800 p-1.5 text-gray-600 hover:text-brand-600 dark:text-gray-300" title="Edit Guru">
                                            <i class="fa-solid fa-pen text-xs"></i>
                                        </button>
                                        <form action="{{ route('admin.gurus.destroy', $g->id) }}" method="POST" onsubmit="return confirm('Hapus data guru {{ $g->nama }}?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="rounded-lg bg-rose-50 dark:bg-rose-500/10 p-1.5 text-rose-600 hover:bg-rose-100" title="Hapus Guru">
                                                <i class="fa-solid fa-trash-can text-xs"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-8 text-center text-gray-500">Tidak ada data guru ditemukan.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="p-4 border-t border-gray-100 dark:border-gray-800">
                {{ $gurus->links() }}
            </div>
        </div>

        <!-- Modal Tambah Guru -->
        <div x-show="showAddModal" x-cloak class="fixed inset-0 z-99999 flex items-center justify-center bg-black/50 p-4">
            <div @click.outside="showAddModal = false" class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-theme-lg dark:bg-gray-900">
                <h3 class="text-lg font-bold text-gray-800 dark:text-white mb-4">Tambah Data Guru Baru</h3>
                <form action="{{ route('admin.gurus.store') }}" method="POST">
                    @csrf
                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Nama Lengkap beserta Gelar *</label>
                            <input type="text" name="nama" required class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3.5 py-2 text-xs text-gray-800 dark:text-white" placeholder="Contoh: Budi Santoso, S.Pd.">
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">NIP (Nomor Induk)</label>
                                <input type="text" name="nip" class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3.5 py-2 text-xs text-gray-800 dark:text-white">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Nomor WhatsApp</label>
                                <input type="text" name="no_wa" class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3.5 py-2 text-xs text-gray-800 dark:text-white" placeholder="08xxxxxxxx">
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Email</label>
                            <input type="email" name="email" class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3.5 py-2 text-xs text-gray-800 dark:text-white">
                        </div>
                        <div class="pt-2">
                            <label class="inline-flex items-center gap-2">
                                <input type="checkbox" name="is_wali_kelas" value="1" class="rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                                <span class="text-xs font-semibold text-gray-700 dark:text-gray-300">Tugaskan Sebagai Wali Kelas</span>
                            </label>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Pilih Kelas Binaan (Jika Wali Kelas)</label>
                            <select name="kelas_id" class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3.5 py-2 text-xs text-gray-800 dark:text-white">
                                <option value="">-- Pilih Kelas --</option>
                                @foreach($kelases as $k)
                                    <option value="{{ $k->id }}">{{ $k->nama_kelas }} ({{ $k->jurusan ?? 'Umum' }})</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="mt-6 flex justify-end gap-2">
                        <button type="button" @click="showAddModal = false" class="rounded-lg border border-gray-300 px-4 py-2 text-xs font-medium text-gray-700 dark:text-gray-300">Batal</button>
                        <button type="submit" class="rounded-lg bg-brand-500 px-4 py-2 text-xs font-semibold text-white hover:bg-brand-600">Simpan Guru & Buat Akun</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal Edit Guru -->
        <div x-show="showEditModal" x-cloak class="fixed inset-0 z-99999 flex items-center justify-center bg-black/50 p-4">
            <div @click.outside="showEditModal = false" class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-theme-lg dark:bg-gray-900">
                <h3 class="text-lg font-bold text-gray-800 dark:text-white mb-4">Edit Data Guru</h3>
                <form :action="'/admin/gurus/' + activeGuru.id" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Nama Lengkap *</label>
                            <input type="text" name="nama" x-model="activeGuru.nama" required class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3.5 py-2 text-xs text-gray-800 dark:text-white">
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">NIP</label>
                                <input type="text" name="nip" x-model="activeGuru.nip" class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3.5 py-2 text-xs text-gray-800 dark:text-white">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">No WhatsApp</label>
                                <input type="text" name="no_wa" x-model="activeGuru.no_wa" class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3.5 py-2 text-xs text-gray-800 dark:text-white">
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Email</label>
                            <input type="email" name="email" x-model="activeGuru.email" class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3.5 py-2 text-xs text-gray-800 dark:text-white">
                        </div>
                        <div class="pt-2">
                            <label class="inline-flex items-center gap-2">
                                <input type="checkbox" name="is_wali_kelas" value="1" :checked="activeGuru.is_wali_kelas" class="rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                                <span class="text-xs font-semibold text-gray-700 dark:text-gray-300">Tugaskan Sebagai Wali Kelas</span>
                            </label>
                        </div>
                    </div>
                    <div class="mt-6 flex justify-end gap-2">
                        <button type="button" @click="showEditModal = false" class="rounded-lg border border-gray-300 px-4 py-2 text-xs font-medium text-gray-700 dark:text-gray-300">Batal</button>
                        <button type="submit" class="rounded-lg bg-brand-500 px-4 py-2 text-xs font-semibold text-white hover:bg-brand-600">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

