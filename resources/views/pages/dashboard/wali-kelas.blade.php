@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-(--breakpoint-2xl) p-4 md:p-6">
        <!-- Role Switcher Banner -->
        <x-common.role-switcher-banner />

        <div class="mb-6 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-800 dark:text-white">Dashboard Wali Kelas</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Kelas Binaan: <strong class="text-brand-600 dark:text-brand-400">{{ $kelas->nama_kelas ?? 'XII RPL 1' }}</strong> | Wali Kelas: <strong>{{ $guru->nama ?? auth()->user()->name }}</strong>
                </p>
            </div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400">
                    <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                    Semester Ganjil 2026/2027
                </span>
            </div>
        </div>

        <!-- Metric Stat Cards -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4 mb-6">
            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-xs dark:border-gray-800 dark:bg-gray-900">
                <span class="text-xs font-semibold text-gray-500 uppercase">Siswa Kelas Binaan</span>
                <div class="mt-2 text-2xl font-bold text-gray-800 dark:text-white">{{ $siswas->count() }} Siswa</div>
                <div class="mt-2 text-xs text-brand-500">Total terdaftar di kelas</div>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-xs dark:border-gray-800 dark:bg-gray-900">
                <span class="text-xs font-semibold text-gray-500 uppercase">Rekap Presensi</span>
                <div class="mt-2 text-2xl font-bold text-emerald-600 dark:text-emerald-400">98.2%</div>
                <div class="mt-2 text-xs text-gray-500">Tingkat kehadiran siswa</div>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-xs dark:border-gray-800 dark:bg-gray-900">
                <span class="text-xs font-semibold text-gray-500 uppercase">Catatan Wali Kelas</span>
                <div class="mt-2 text-2xl font-bold text-amber-600 dark:text-amber-400">Draft</div>
                <div class="mt-2 text-xs text-gray-500">Catatan perkembangan karakter</div>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-xs dark:border-gray-800 dark:bg-gray-900">
                <span class="text-xs font-semibold text-gray-500 uppercase">Status Rapor</span>
                <div class="mt-2 text-2xl font-bold text-indigo-600 dark:text-indigo-400">Siap Verifikasi</div>
                <div class="mt-2 text-xs text-gray-500">Pencetakan rapor semester</div>
            </div>
        </div>

        <!-- Tabel Siswa Kelas Binaan -->
        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-xs dark:border-gray-800 dark:bg-gray-900 mb-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-base font-semibold text-gray-800 dark:text-white">Daftar Siswa Kelas {{ $kelas->nama_kelas ?? 'Binaan' }}</h3>
                    <p class="text-xs text-gray-500">Data siswa yang berada di bawah perwalian Anda.</p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-start text-xs text-gray-600 dark:text-gray-400">
                    <thead>
                        <tr class="border-b border-gray-100 dark:border-gray-800 uppercase text-gray-400">
                            <th class="py-3 text-start font-medium">No</th>
                            <th class="py-3 text-start font-medium">Nama Siswa</th>
                            <th class="py-3 text-start font-medium">NIS / NISN</th>
                            <th class="py-3 text-start font-medium">Kontak Siswa & Ortu</th>
                            <th class="py-3 text-start font-medium">Status Nilai Mapel</th>
                            <th class="py-3 text-end font-medium">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse($siswas as $idx => $s)
                            <tr>
                                <td class="py-3">{{ $idx + 1 }}</td>
                                <td class="py-3">
                                    <div class="font-semibold text-gray-800 dark:text-white">{{ $s->nama }}</div>
                                    <div class="text-[11px] text-gray-400">{{ $s->jenis_kelamin ?? 'Laki-laki' }}</div>
                                </td>
                                <td class="py-3">
                                    <div>NIS: <strong class="text-gray-700 dark:text-gray-300">{{ $s->nis }}</strong></div>
                                    <div class="text-[11px] text-gray-400">NISN: {{ $s->nisn ?? '-' }}</div>
                                </td>
                                <td class="py-3">
                                    <div>WA: {{ $s->no_wa ?? '-' }}</div>
                                    <div class="text-[11px] text-gray-400">Ortu: {{ $s->wa_ortu ?? '-' }}</div>
                                </td>
                                <td class="py-3">
                                    <span class="inline-flex items-center rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-semibold text-emerald-600 dark:bg-emerald-500/10">Lengkap</span>
                                </td>
                                <td class="py-3 text-end">
                                    <button type="button" class="rounded-lg bg-gray-100 dark:bg-gray-800 px-3 py-1 text-xs font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-200">
                                        Rapor Siswa
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-6 text-center text-gray-500">Belum ada siswa di kelas ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

