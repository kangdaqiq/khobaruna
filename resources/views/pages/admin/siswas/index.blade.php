@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-(--breakpoint-2xl) p-4 md:p-6" x-data="{
        showAddModal: false,
        showEditModal: false,
        activeSiswa: {},
        editSiswa(siswa) {
            this.activeSiswa = JSON.parse(JSON.stringify(siswa));
            this.showEditModal = true;
        }
    }">
        <!-- Header -->
        <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-800 dark:text-white">Data Peserta Didik (Siswa)</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">Kelola biodata siswa, penempatan rombel kelas, dan akun login siswa.</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.sync.index') }}" class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-800 px-3.5 py-2 text-xs font-semibold text-gray-700 dark:text-gray-300 hover:bg-gray-50">
                    <i class="fa-solid fa-arrows-rotate text-[11px]"></i>
                    <span>Sinkronkan Data</span>
                </a>
                <button @click="showAddModal = true" class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-xs font-semibold text-white hover:bg-emerald-700 shadow-theme-xs">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    Tambah Siswa Baru
                </button>
            </div>
        </div>

        @if(session('success'))
            <div class="mb-4 rounded-xl bg-emerald-50 border border-emerald-200 p-4 text-sm text-emerald-800 dark:bg-emerald-500/10 dark:border-emerald-500/20 dark:text-emerald-400">
                {{ session('success') }}
            </div>
        @endif

        <!-- Stat Cards -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3 mb-6">
            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-theme-xs dark:border-gray-800 dark:bg-gray-900">
                <span class="text-xs text-gray-500 uppercase font-medium">Total Seluruh Siswa</span>
                <div class="mt-1 text-2xl font-bold text-gray-800 dark:text-white">{{ number_format($stats['total']) }}</div>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-theme-xs dark:border-gray-800 dark:bg-gray-900">
                <span class="text-xs text-gray-500 uppercase font-medium">Terplotting di Rombel</span>
                <div class="mt-1 text-2xl font-bold text-emerald-600">{{ number_format($stats['has_class']) }}</div>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-theme-xs dark:border-gray-800 dark:bg-gray-900">
                <span class="text-xs text-gray-500 uppercase font-medium">Akun Siswa Aktif</span>
                <div class="mt-1 text-2xl font-bold text-brand-600">{{ number_format($stats['has_account']) }}</div>
            </div>
        </div>

        <!-- Table Card -->
        <div class="rounded-2xl border border-gray-200 bg-white shadow-theme-xs dark:border-gray-800 dark:bg-gray-900">
            <!-- Filter & Search -->
            <div class="p-4 border-b border-gray-100 dark:border-gray-800">
                <form method="GET" action="{{ route('admin.siswas.index') }}" class="flex flex-col sm:flex-row gap-3">
                    <div class="relative flex-1">
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama, NIS, NISN, atau kelas siswa..." class="w-full rounded-lg border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-800/50 px-3.5 py-2 text-xs text-gray-800 dark:text-white placeholder-gray-400 focus:outline-hidden focus:border-brand-500">
                    </div>
                    <select name="kelas_id" onchange="this.form.submit()" class="rounded-lg border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-800/50 px-3 py-2 text-xs text-gray-700 dark:text-gray-300">
                        <option value="">Semua Rombel / Kelas</option>
                        @foreach($kelases as $k)
                            <option value="{{ $k->id }}" {{ request('kelas_id') == $k->id ? 'selected' : '' }}>{{ $k->nama_kelas }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="rounded-lg bg-gray-100 dark:bg-gray-800 px-4 py-2 text-xs font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-200">
                        Cari
                    </button>
                    @if(request('search') || request('kelas_id'))
                        <a href="{{ route('admin.siswas.index') }}" class="rounded-lg border border-gray-200 dark:border-gray-700 px-3 py-2 text-xs text-gray-500 hover:bg-gray-50 flex items-center">
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
                            <th class="py-3 px-4 text-start">Nama Siswa</th>
                            <th class="py-3 px-4 text-start">NIS & NISN</th>
                            <th class="py-3 px-4 text-start">Rombel / Kelas</th>
                            <th class="py-3 px-4 text-start">Kontak Siswa & Ortu</th>
                            <th class="py-3 px-4 text-start">Akun Login</th>
                            <th class="py-3 px-4 text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse($siswas as $s)
                            <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/30">
                                <td class="py-3 px-4">
                                    <div class="font-semibold text-gray-800 dark:text-white">{{ $s->nama }}</div>
                                    <div class="text-[11px] text-gray-400">{{ $s->jenis_kelamin ?? 'Laki-laki' }}</div>
                                </td>
                                <td class="py-3 px-4">
                                    <div class="font-mono text-gray-800 dark:text-gray-200 font-medium">NIS: {{ $s->nis ?? '-' }}</div>
                                    <div class="text-[11px] text-gray-400 font-mono">NISN: {{ $s->nisn ?? '-' }}</div>
                                </td>
                                <td class="py-3 px-4">
                                    <span class="inline-flex rounded-lg bg-gray-100 dark:bg-gray-800 px-2.5 py-1 text-xs font-semibold text-gray-800 dark:text-gray-200">
                                        {{ $s->kelas->nama_kelas ?? ($s->kelas_nama ?? 'Belum Ada Kelas') }}
                                    </span>
                                </td>
                                <td class="py-3 px-4">
                                    <div>WA: {{ $s->no_wa ?? '-' }}</div>
                                    <div class="text-[11px] text-gray-400">Ortu: {{ $s->wa_ortu ?? '-' }}</div>
                                </td>
                                <td class="py-3 px-4">
                                    @if($s->user)
                                        <div class="font-mono text-gray-700 dark:text-gray-300">{{ $s->user->username }}</div>
                                        <span class="inline-flex items-center gap-1 text-[11px] text-emerald-600">
                                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span> Siap Login
                                        </span>
                                    @else
                                        <span class="text-rose-500 text-xs">Belum ada akun</span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-end">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <button @click="editSiswa({{ $s }})" class="rounded-lg bg-gray-100 dark:bg-gray-800 p-1.5 text-gray-600 hover:text-brand-600 dark:text-gray-300" title="Edit Siswa">
                                            <i class="fa-solid fa-pen text-xs"></i>
                                        </button>
                                        <form action="{{ route('admin.siswas.destroy', $s->id) }}" method="POST" onsubmit="return confirm('Hapus data siswa {{ $s->nama }}?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="rounded-lg bg-rose-50 dark:bg-rose-500/10 p-1.5 text-rose-600 hover:bg-rose-100" title="Hapus Siswa">
                                                <i class="fa-solid fa-trash-can text-xs"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-8 text-center text-gray-500">Tidak ada data siswa ditemukan.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="p-4 border-t border-gray-100 dark:border-gray-800">
                {{ $siswas->links() }}
            </div>
        </div>

        <!-- Modal Tambah Siswa -->
        <div x-show="showAddModal" x-cloak class="fixed inset-0 z-99999 flex items-center justify-center bg-black/50 p-4">
            <div @click.outside="showAddModal = false" class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-theme-lg dark:bg-gray-900">
                <h3 class="text-lg font-bold text-gray-800 dark:text-white mb-4">Tambah Siswa Baru</h3>
                <form action="{{ route('admin.siswas.store') }}" method="POST">
                    @csrf
                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Nama Lengkap Siswa *</label>
                            <input type="text" name="nama" required class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3.5 py-2 text-xs text-gray-800 dark:text-white">
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">NIS (Nomor Induk Siswa) *</label>
                                <input type="text" name="nis" required class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3.5 py-2 text-xs text-gray-800 dark:text-white" placeholder="Digunakan untuk login">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">NISN</label>
                                <input type="text" name="nisn" class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3.5 py-2 text-xs text-gray-800 dark:text-white">
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Pilih Kelas (Rombel)</label>
                                <select name="kelas_id" class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3.5 py-2 text-xs text-gray-800 dark:text-white">
                                    <option value="">-- Pilih Kelas --</option>
                                    @foreach($kelases as $k)
                                        <option value="{{ $k->id }}">{{ $k->nama_kelas }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Jenis Kelamin</label>
                                <select name="jenis_kelamin" class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3.5 py-2 text-xs text-gray-800 dark:text-white">
                                    <option value="Laki-laki">Laki-laki</option>
                                    <option value="Perempuan">Perempuan</option>
                                </select>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">No WhatsApp Siswa</label>
                                <input type="text" name="no_wa" class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3.5 py-2 text-xs text-gray-800 dark:text-white">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">No WhatsApp Ortu</label>
                                <input type="text" name="wa_ortu" class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3.5 py-2 text-xs text-gray-800 dark:text-white">
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Alamat Tempat Tinggal</label>
                            <textarea name="alamat" rows="2" class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3.5 py-2 text-xs text-gray-800 dark:text-white"></textarea>
                        </div>
                    </div>
                    <div class="mt-6 flex justify-end gap-2">
                        <button type="button" @click="showAddModal = false" class="rounded-lg border border-gray-300 px-4 py-2 text-xs font-medium text-gray-700 dark:text-gray-300">Batal</button>
                        <button type="submit" class="rounded-lg bg-emerald-600 px-4 py-2 text-xs font-semibold text-white hover:bg-emerald-700">Simpan Siswa & Buat Akun</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal Edit Siswa -->
        <div x-show="showEditModal" x-cloak class="fixed inset-0 z-99999 flex items-center justify-center bg-black/50 p-4">
            <div @click.outside="showEditModal = false" class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-theme-lg dark:bg-gray-900">
                <h3 class="text-lg font-bold text-gray-800 dark:text-white mb-4">Edit Data Siswa</h3>
                <form :action="'/admin/siswas/' + activeSiswa.id" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Nama Lengkap Siswa *</label>
                            <input type="text" name="nama" x-model="activeSiswa.nama" required class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3.5 py-2 text-xs text-gray-800 dark:text-white">
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">NIS *</label>
                                <input type="text" name="nis" x-model="activeSiswa.nis" required class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3.5 py-2 text-xs text-gray-800 dark:text-white">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">NISN</label>
                                <input type="text" name="nisn" x-model="activeSiswa.nisn" class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3.5 py-2 text-xs text-gray-800 dark:text-white">
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Pilih Kelas</label>
                                <select name="kelas_id" x-model="activeSiswa.kelas_id" class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3.5 py-2 text-xs text-gray-800 dark:text-white">
                                    <option value="">-- Pilih Kelas --</option>
                                    @foreach($kelases as $k)
                                        <option value="{{ $k->id }}">{{ $k->nama_kelas }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Jenis Kelamin</label>
                                <select name="jenis_kelamin" x-model="activeSiswa.jenis_kelamin" class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3.5 py-2 text-xs text-gray-800 dark:text-white">
                                    <option value="Laki-laki">Laki-laki</option>
                                    <option value="Perempuan">Perempuan</option>
                                </select>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">No WA Siswa</label>
                                <input type="text" name="no_wa" x-model="activeSiswa.no_wa" class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3.5 py-2 text-xs text-gray-800 dark:text-white">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">No WA Ortu</label>
                                <input type="text" name="wa_ortu" x-model="activeSiswa.wa_ortu" class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3.5 py-2 text-xs text-gray-800 dark:text-white">
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Alamat</label>
                            <textarea name="alamat" x-model="activeSiswa.alamat" rows="2" class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3.5 py-2 text-xs text-gray-800 dark:text-white"></textarea>
                        </div>
                    </div>
                    <div class="mt-6 flex justify-end gap-2">
                        <button type="button" @click="showEditModal = false" class="rounded-lg border border-gray-300 px-4 py-2 text-xs font-medium text-gray-700 dark:text-gray-300">Batal</button>
                        <button type="submit" class="rounded-lg bg-emerald-600 px-4 py-2 text-xs font-semibold text-white hover:bg-emerald-700">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

