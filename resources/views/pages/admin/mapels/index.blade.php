@extends('layouts.app')

@section('content')
<div x-data="{
    showModal: false,
    editMode: false,
    modalTitle: 'Tambah Mata Pelajaran',
    formAction: '{{ route('admin.mapels.store') }}',
    formMethod: 'POST',
    formData: {
        id: null,
        nama_mapel: '',
        kode_mapel: '',
        kelompok: 'Umum'
    },
    openCreateModal() {
        this.editMode = false;
        this.modalTitle = 'Tambah Mata Pelajaran Baru';
        this.formAction = '{{ route('admin.mapels.store') }}';
        this.formMethod = 'POST';
        this.formData = { id: null, nama_mapel: '', kode_mapel: '', kelompok: 'Umum' };
        this.showModal = true;
    },
    openEditModal(mapel) {
        this.editMode = true;
        this.modalTitle = 'Edit Mata Pelajaran';
        this.formAction = '/admin/mapels/' + mapel.id;
        this.formMethod = 'POST';
        this.formData = {
            id: mapel.id,
            nama_mapel: mapel.nama_mapel,
            kode_mapel: mapel.kode_mapel ?? '',
            kelompok: mapel.kelompok ?? 'Umum'
        };
        this.showModal = true;
    }
}" class="space-y-6">

    <x-common.role-switcher-banner activeRole="admin" />

    <!-- Breadcrumb & Header -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <x-common.page-breadcrumb :pageTitle="'Mata Pelajaran'" />

        <div class="flex items-center gap-3">
            <button @click="openCreateModal()" class="inline-flex items-center gap-2 rounded-xl bg-brand-500 px-4 py-2.5 text-xs font-semibold text-white shadow-theme-xs hover:bg-brand-600 transition">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Tambah Mapel
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
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <a href="{{ route('admin.mapels.index') }}" class="rounded-2xl border border-gray-200 bg-white p-4 shadow-theme-xs dark:border-gray-800 dark:bg-gray-900 transition hover:border-brand-500">
            <div class="text-xs text-gray-500 dark:text-gray-400">Total Mata Pelajaran</div>
            <div class="mt-1 text-2xl font-bold text-gray-800 dark:text-white">{{ number_format($kelompokCounts['all']) }}</div>
            <div class="mt-1 text-[11px] text-brand-600 font-medium">Semua Kelompok</div>
        </a>
        <a href="{{ route('admin.mapels.index', ['kelompok' => 'Umum']) }}" class="rounded-2xl border border-gray-200 bg-white p-4 shadow-theme-xs dark:border-gray-800 dark:bg-gray-900 transition hover:border-brand-500">
            <div class="text-xs text-gray-500 dark:text-gray-400">Kelompok Umum</div>
            <div class="mt-1 text-2xl font-bold text-indigo-600 dark:text-indigo-400">{{ number_format($kelompokCounts['Umum']) }}</div>
            <div class="mt-1 text-[11px] text-gray-400">Normatif & Adaptif</div>
        </a>
        <a href="{{ route('admin.mapels.index', ['kelompok' => 'Kejuruan']) }}" class="rounded-2xl border border-gray-200 bg-white p-4 shadow-theme-xs dark:border-gray-800 dark:bg-gray-900 transition hover:border-brand-500">
            <div class="text-xs text-gray-500 dark:text-gray-400">Kelompok Kejuruan</div>
            <div class="mt-1 text-2xl font-bold text-amber-600 dark:text-amber-400">{{ number_format($kelompokCounts['Kejuruan']) }}</div>
            <div class="mt-1 text-[11px] text-gray-400">Produktif SMK</div>
        </a>
        <a href="{{ route('admin.mapels.index', ['kelompok' => 'Muatan Lokal']) }}" class="rounded-2xl border border-gray-200 bg-white p-4 shadow-theme-xs dark:border-gray-800 dark:bg-gray-900 transition hover:border-brand-500">
            <div class="text-xs text-gray-500 dark:text-gray-400">Muatan Lokal</div>
            <div class="mt-1 text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ number_format($kelompokCounts['Muatan Lokal']) }}</div>
            <div class="mt-1 text-[11px] text-gray-400">Bahasa & Budaya Daerah</div>
        </a>
    </div>

    <!-- Table Card -->
    <div class="rounded-2xl border border-gray-200 bg-white shadow-theme-xs dark:border-gray-800 dark:bg-gray-900">
        <!-- Search and Filter Header -->
        <div class="p-4 border-b border-gray-100 dark:border-gray-800">
            <form method="GET" action="{{ route('admin.mapels.index') }}" class="flex flex-col sm:flex-row gap-3">
                @if(request('kelompok'))
                    <input type="hidden" name="kelompok" value="{{ request('kelompok') }}">
                @endif
                <div class="relative flex-1">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama mapel atau kode..." class="w-full rounded-lg border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-800/50 px-3.5 py-2 text-xs text-gray-800 dark:text-white placeholder-gray-400 focus:outline-hidden focus:border-brand-500">
                </div>
                <button type="submit" class="rounded-lg bg-gray-100 dark:bg-gray-800 px-4 py-2 text-xs font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-200">
                    Cari
                </button>
                @if(request('search') || request('kelompok'))
                    <a href="{{ route('admin.mapels.index') }}" class="rounded-lg border border-gray-200 dark:border-gray-700 px-3 py-2 text-xs text-gray-500 hover:bg-gray-50 flex items-center">
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
                        <th class="py-3 px-4 text-start">Kode</th>
                        <th class="py-3 px-4 text-start">Mata Pelajaran</th>
                        <th class="py-3 px-4 text-start">Kelompok Kurikulum</th>
                        <th class="py-3 px-4 text-start">Rombel Tercover</th>
                        <th class="py-3 px-4 text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse($mapels as $m)
                        <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/30">
                            <td class="py-3 px-4">
                                <span class="rounded bg-gray-100 dark:bg-gray-800 px-2 py-0.5 font-mono text-[11px] font-bold text-gray-700 dark:text-gray-300">
                                    {{ $m->kode_mapel ?? '-' }}
                                </span>
                            </td>
                            <td class="py-3 px-4">
                                <div class="font-semibold text-gray-800 dark:text-white">{{ $m->nama_mapel }}</div>
                            </td>
                            <td class="py-3 px-4">
                                @php
                                    $badge = match($m->kelompok) {
                                        'Umum' => 'bg-indigo-50 text-indigo-700 dark:bg-indigo-500/10 dark:text-indigo-400',
                                        'Kejuruan' => 'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400',
                                        'Muatan Lokal' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400',
                                        default => 'bg-gray-100 text-gray-700'
                                    };
                                @endphp
                                <span class="inline-flex rounded-full px-2.5 py-0.5 text-[11px] font-semibold {{ $badge }}">
                                    {{ $m->kelompok }}
                                </span>
                            </td>
                            <td class="py-3 px-4">
                                <span class="text-xs font-semibold text-gray-800 dark:text-white">{{ $m->total_kelas }} Kelas</span>
                            </td>
                            <td class="py-3 px-4 text-end">
                                <div class="flex items-center justify-end gap-1.5">
                                    <button @click="openEditModal({{ $m }})" class="rounded-lg bg-gray-100 dark:bg-gray-800 p-1.5 text-gray-600 hover:text-brand-600 dark:text-gray-300" title="Edit Mapel">
                                        <i class="fa-solid fa-pen text-xs"></i>
                                    </button>
                                    <form action="{{ route('admin.mapels.destroy', $m->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus mapel {{ $m->nama_mapel }}?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="rounded-lg bg-rose-50 dark:bg-rose-500/10 p-1.5 text-rose-600 hover:bg-rose-100" title="Hapus Mapel">
                                            <i class="fa-solid fa-trash-can text-xs"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-8 text-center text-gray-500">Tidak ada mata pelajaran ditemukan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="p-4 border-t border-gray-100 dark:border-gray-800">
            {{ $mapels->links() }}
        </div>
    </div>

    <!-- Modal Form (Create / Edit) -->
    <div x-show="showModal" x-cloak class="fixed inset-0 z-999 flex items-center justify-center p-4 bg-gray-900/50 backdrop-blur-xs">
        <div @click.away="showModal = false" class="w-full max-w-md rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-xl dark:border-gray-800 dark:bg-gray-900">
            <h3 class="text-sm font-bold text-gray-800 dark:text-white" x-text="modalTitle"></h3>

            <form :action="formAction" method="POST" class="mt-4 space-y-4">
                @csrf
                <template x-if="editMode">
                    <input type="hidden" name="_method" value="PUT">
                </template>

                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Nama Mata Pelajaran</label>
                    <input type="text" name="nama_mapel" x-model="formData.nama_mapel" required placeholder="Contoh: Matematika" class="w-full rounded-lg border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-800/50 px-3 py-2 text-xs text-gray-800 dark:text-white focus:outline-hidden focus:border-brand-500">
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Kode Mapel (Singkatan)</label>
                    <input type="text" name="kode_mapel" x-model="formData.kode_mapel" placeholder="Contoh: MTK" class="w-full rounded-lg border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-800/50 px-3 py-2 text-xs text-gray-800 dark:text-white focus:outline-hidden focus:border-brand-500">
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Kelompok Kurikulum</label>
                    <select name="kelompok" x-model="formData.kelompok" class="w-full rounded-lg border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-800/50 px-3 py-2 text-xs text-gray-800 dark:text-white focus:outline-hidden focus:border-brand-500">
                        <option value="Umum">Umum (Normatif & Adaptif)</option>
                        <option value="Kejuruan">Kejuruan (Produktif SMK)</option>
                        <option value="Muatan Lokal">Muatan Lokal (Bahasa Daerah/Adat)</option>
                        <option value="Bimbingan">Bimbingan & Konseling (BK)</option>
                    </select>
                </div>

                <div class="mt-6 flex justify-end gap-2 pt-2 border-t border-gray-100 dark:border-gray-800">
                    <button type="button" @click="showModal = false" class="rounded-lg border border-gray-200 dark:border-gray-700 px-4 py-2 text-xs font-medium text-gray-600 dark:text-gray-400 hover:bg-gray-50">
                        Batal
                    </button>
                    <button type="submit" class="rounded-lg bg-brand-500 px-4 py-2 text-xs font-medium text-white hover:bg-brand-600">
                        Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection

