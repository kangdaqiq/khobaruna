@extends('layouts.app')

@section('content')
<div x-data="{
    showModal: false,
    selectedGuru: '',
    selectedMapel: '',
    selectedKelases: [],
    selectAllKelases(status) {
        if (status) {
            this.selectedKelases = {{ $kelases->pluck('id')->toJson() }};
        } else {
            this.selectedKelases = [];
        }
    }
}" class="space-y-6">

    <x-common.role-switcher-banner activeRole="admin" />

    <!-- Breadcrumb & Header -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <x-common.page-breadcrumb :pageTitle="'Pembagian Tugas Mengajar'" />

        <div class="flex items-center gap-3">
            <button @click="showModal = true" class="inline-flex items-center gap-2 rounded-xl bg-brand-500 px-4 py-2.5 text-xs font-semibold text-white shadow-theme-xs hover:bg-brand-600 transition">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Tambah Pembagian Tugas
            </button>
        </div>
    </div>

    <!-- Alert Success -->
    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50/70 p-4 text-xs font-medium text-emerald-800 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-300 flex items-center gap-2">
            <i class="fa-solid fa-circle-check text-emerald-600"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    <!-- Metric Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-theme-xs dark:border-gray-800 dark:bg-gray-900">
            <div class="text-xs text-gray-500 dark:text-gray-400">Total Penugasan Mengajar</div>
            <div class="mt-1 text-2xl font-bold text-gray-800 dark:text-white">{{ number_format($stats['total_assignments']) }}</div>
            <div class="mt-1 text-[11px] text-brand-600 font-medium">Kombinasi Guru - Mapel - Kelas</div>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-theme-xs dark:border-gray-800 dark:bg-gray-900">
            <div class="text-xs text-gray-500 dark:text-gray-400">Guru Aktif Mengampu</div>
            <div class="mt-1 text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ number_format($stats['total_gurus_active']) }}</div>
            <div class="mt-1 text-[11px] text-gray-400">Dari total {{ $gurus->count() }} Guru</div>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-theme-xs dark:border-gray-800 dark:bg-gray-900">
            <div class="text-xs text-gray-500 dark:text-gray-400">Kelas Terlingkup Penugasan</div>
            <div class="mt-1 text-2xl font-bold text-indigo-600 dark:text-indigo-400">{{ number_format($stats['total_kelases_covered']) }}</div>
            <div class="mt-1 text-[11px] text-gray-400">Dari total {{ $kelases->count() }} Rombel</div>
        </div>
    </div>

    <!-- Table Card -->
    <div class="rounded-2xl border border-gray-200 bg-white shadow-theme-xs dark:border-gray-800 dark:bg-gray-900">
        <!-- Search and Filter Header -->
        <div class="p-4 border-b border-gray-100 dark:border-gray-800">
            <form method="GET" action="{{ route('admin.pembagian-tugas.index') }}" class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                <div>
                    <select name="guru_id" class="w-full rounded-lg border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-800/50 px-3 py-2 text-xs text-gray-800 dark:text-white focus:outline-hidden focus:border-brand-500">
                        <option value="">Semua Guru ({{ $gurus->count() }})</option>
                        @foreach($gurus as $g)
                            <option value="{{ $g->id }}" {{ request('guru_id') == $g->id ? 'selected' : '' }}>{{ $g->nama }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <select name="mapel_id" class="w-full rounded-lg border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-800/50 px-3 py-2 text-xs text-gray-800 dark:text-white focus:outline-hidden focus:border-brand-500">
                        <option value="">Semua Mata Pelajaran ({{ $mapels->count() }})</option>
                        @foreach($mapels as $m)
                            <option value="{{ $m->id }}" {{ request('mapel_id') == $m->id ? 'selected' : '' }}>[{{ $m->kode_mapel }}] {{ $m->nama_mapel }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <select name="kelas_id" class="w-full rounded-lg border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-800/50 px-3 py-2 text-xs text-gray-800 dark:text-white focus:outline-hidden focus:border-brand-500">
                        <option value="">Semua Kelas ({{ $kelases->count() }})</option>
                        @foreach($kelases as $k)
                            <option value="{{ $k->id }}" {{ request('kelas_id') == $k->id ? 'selected' : '' }}>{{ $k->nama_kelas }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="flex-1 rounded-lg bg-gray-100 dark:bg-gray-800 px-4 py-2 text-xs font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-200">
                        Filter
                    </button>
                    @if(request('guru_id') || request('mapel_id') || request('kelas_id'))
                        <a href="{{ route('admin.pembagian-tugas.index') }}" class="rounded-lg border border-gray-200 dark:border-gray-700 px-3 py-2 text-xs text-gray-500 hover:bg-gray-50 flex items-center">
                            Reset
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-start text-xs text-gray-600 dark:text-gray-400">
                <thead>
                    <tr class="border-b border-gray-100 dark:border-gray-800 uppercase text-gray-400 font-medium">
                        <th class="py-3 px-4 text-start">Guru Pengampu</th>
                        <th class="py-3 px-4 text-start">Mata Pelajaran</th>
                        <th class="py-3 px-4 text-start">Kelas / Rombel</th>
                        <th class="py-3 px-4 text-start">Tahun / Semester</th>
                        <th class="py-3 px-4 text-start">Jumlah Siswa</th>
                        <th class="py-3 px-4 text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse($assignments as $a)
                        <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/30">
                            <td class="py-3 px-4">
                                <div class="font-semibold text-gray-800 dark:text-white">{{ $a->guru?->nama ?? '-' }}</div>
                                <div class="text-[11px] text-gray-400 font-mono">NIP: {{ $a->guru?->nip ?? '-' }}</div>
                            </td>
                            <td class="py-3 px-4">
                                <div class="font-medium text-gray-800 dark:text-white">{{ $a->mapel?->nama_mapel ?? '-' }}</div>
                                <div class="text-[11px] text-gray-400">Kode: {{ $a->mapel?->kode_mapel ?? '-' }} ({{ $a->mapel?->kelompok }})</div>
                            </td>
                            <td class="py-3 px-4">
                                <span class="rounded bg-brand-50 text-brand-700 dark:bg-brand-500/10 dark:text-brand-400 px-2.5 py-1 font-semibold text-xs">
                                    {{ $a->kelas?->nama_kelas ?? '-' }}
                                </span>
                            </td>
                            <td class="py-3 px-4">
                                <span class="text-xs text-gray-700 dark:text-gray-300">{{ $a->tahun_ajaran }} - {{ $a->semester }}</span>
                            </td>
                            <td class="py-3 px-4">
                                <span class="text-xs font-semibold text-gray-800 dark:text-white">{{ $a->kelas?->siswas->count() ?? 0 }} Siswa</span>
                            </td>
                            <td class="py-3 px-4 text-end">
                                <form action="{{ route('admin.pembagian-tugas.destroy', $a->id) }}" method="POST" onsubmit="return confirm('Hapus penugasan mengajar ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="rounded-lg bg-rose-50 dark:bg-rose-500/10 p-1.5 text-rose-600 hover:bg-rose-100" title="Hapus Penugasan">
                                        <i class="fa-solid fa-trash-can text-xs"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-8 text-center text-gray-500">Tidak ada penugasan mengajar ditemukan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="p-4 border-t border-gray-100 dark:border-gray-800">
            {{ $assignments->links() }}
        </div>
    </div>

    <!-- Modal Form (Tambah Pembagian Tugas) -->
    <div x-show="showModal" x-cloak class="fixed inset-0 z-999 flex items-center justify-center p-4 bg-gray-900/50 backdrop-blur-xs">
        <div @click.away="showModal = false" class="w-full max-w-lg rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-xl dark:border-gray-800 dark:bg-gray-900 max-h-[90vh] overflow-y-auto">
            <h3 class="text-sm font-bold text-gray-800 dark:text-white">Tambah Pembagian Tugas Mengajar</h3>

            <form action="{{ route('admin.pembagian-tugas.store') }}" method="POST" class="mt-4 space-y-4">
                @csrf

                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Guru Pengampu</label>
                    <select name="guru_id" required class="w-full rounded-lg border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-800/50 px-3 py-2 text-xs text-gray-800 dark:text-white focus:outline-hidden focus:border-brand-500">
                        <option value="">-- Pilih Guru --</option>
                        @foreach($gurus as $g)
                            <option value="{{ $g->id }}">{{ $g->nama }} (NIP: {{ $g->nip ?? '-' }})</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Mata Pelajaran</label>
                    <select name="mapel_id" required class="w-full rounded-lg border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-800/50 px-3 py-2 text-xs text-gray-800 dark:text-white focus:outline-hidden focus:border-brand-500">
                        <option value="">-- Pilih Mata Pelajaran --</option>
                        @foreach($mapels as $m)
                            <option value="{{ $m->id }}">[{{ $m->kode_mapel }}] {{ $m->nama_mapel }} ({{ $m->kelompok }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Tahun Ajaran</label>
                        <input type="text" name="tahun_ajaran" value="2024/2025" class="w-full rounded-lg border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-800/50 px-3 py-2 text-xs text-gray-800 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Semester</label>
                        <select name="semester" class="w-full rounded-lg border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-800/50 px-3 py-2 text-xs text-gray-800 dark:text-white">
                            <option value="Ganjil">Ganjil</option>
                            <option value="Genap">Genap</option>
                        </select>
                    </div>
                </div>

                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label class="text-xs font-medium text-gray-700 dark:text-gray-300">Pilih Kelas yang Diampu</label>
                        <div class="flex gap-2">
                            <button type="button" @click="selectAllKelases(true)" class="text-[11px] text-brand-600 hover:underline">Pilih Semua</button>
                            <span class="text-gray-300">|</span>
                            <button type="button" @click="selectAllKelases(false)" class="text-[11px] text-gray-500 hover:underline">Batal</button>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 p-3 rounded-lg border border-gray-200 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-800/30 max-h-48 overflow-y-auto">
                        @foreach($kelases as $k)
                            <label class="flex items-center gap-2 text-xs text-gray-700 dark:text-gray-300 cursor-pointer">
                                <input type="checkbox" name="kelas_ids[]" value="{{ $k->id }}" x-model="selectedKelases" class="rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                                <span>{{ $k->nama_kelas }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-2 pt-2 border-t border-gray-100 dark:border-gray-800">
                    <button type="button" @click="showModal = false" class="rounded-lg border border-gray-200 dark:border-gray-700 px-4 py-2 text-xs font-medium text-gray-600 dark:text-gray-400 hover:bg-gray-50">
                        Batal
                    </button>
                    <button type="submit" class="rounded-lg bg-brand-500 px-4 py-2 text-xs font-medium text-white hover:bg-brand-600">
                        Simpan Penugasan
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection

