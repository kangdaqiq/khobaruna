@extends('layouts.app')

@section('content')
<div x-data="{
    activeTab: '{{ $activeTab }}', // Default 'tp' (Bisa langsung input TP)
    isAddCpOpen: false,
    isEditCpOpen: false,
    editCpData: {},
    isAddTpOpen: false,
    isEditTpOpen: false,
    editTpData: {},
    selectedCpIdForTp: '',

    openEditCp(cp) {
        this.editCpData = { ...cp };
        this.isEditCpOpen = true;
    },

    openAddTpForCp(cpId) {
        this.selectedCpIdForTp = cpId;
        this.isAddTpOpen = true;
    },

    openEditTp(tp) {
        this.editTpData = { ...tp };
        this.isEditTpOpen = true;
    }
}" class="space-y-6 pb-20">

    <!-- Header Section -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 dark:text-white flex items-center gap-2.5">
                <i class="fa-solid fa-award text-indigo-600"></i>
                <span>Capaian & Tujuan Pembelajaran (CP / TP)</span>
            </h1>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                Kelola <strong>Capaian Pembelajaran (CP)</strong> sebagai acuan capaian dan <strong>Tujuan Pembelajaran (TP)</strong> untuk penilaian sumatif & deskripsi rapor. CP di awal, dilanjutkan TP (CP bersifat opsional, TP dapat langsung diinput).
            </p>
        </div>

        <div class="flex items-center gap-2">
            <!-- Tombol Tambah CP (Di Awal) -->
            <template x-if="activeTab === 'cp'">
                <button type="button" @click="isAddCpOpen = true" class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-xs font-semibold text-white shadow-theme-xs hover:bg-indigo-700 transition">
                    <i class="fa-solid fa-plus text-xs"></i>
                    <span>Tambah CP Baru</span>
                </button>
            </template>

            <!-- Tombol Tambah TP (Setelah CP) -->
            <template x-if="activeTab === 'tp'">
                <button type="button" @click="selectedCpIdForTp = ''; isAddTpOpen = true" class="inline-flex items-center gap-2 rounded-xl bg-brand-500 px-4 py-2.5 text-xs font-semibold text-white shadow-theme-xs hover:bg-brand-600 transition">
                    <i class="fa-solid fa-plus text-xs"></i>
                    <span>Tambah TP Baru</span>
                </button>
            </template>
        </div>
    </div>

    <!-- Alert Notifikasi -->
    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50/80 p-4 text-xs font-medium text-emerald-800 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-300 flex items-center gap-2.5">
            <i class="fa-solid fa-circle-check text-emerald-600 text-sm"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if($errors->any())
        <div class="rounded-xl border border-rose-200 bg-rose-50/80 p-4 text-xs font-medium text-rose-800 dark:border-rose-500/20 dark:bg-rose-500/10 dark:text-rose-300">
            <div class="font-bold mb-1 flex items-center gap-2">
                <i class="fa-solid fa-circle-exclamation text-rose-600"></i>
                <span>Terdapat kesalahan validasi:</span>
            </div>
            <ul class="list-disc ps-5 space-y-0.5">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Mode Toggle untuk Admin / Waka Kurikulum yang memiliki jadwal mengajar -->
    @if(Auth::user() && in_array(Auth::user()->role, [\App\Models\User::ROLE_ADMIN, \App\Models\User::ROLE_WAKA_KURIKULUM]) && $hasAssignments)
        <div class="flex flex-wrap items-center justify-between text-xs px-1 gap-2">
            <span class="text-gray-600 dark:text-gray-400 flex items-center gap-1.5">
                <i class="fa-solid fa-filter text-brand-500"></i>
                @if(!empty($isSupervisi))
                    <span>Mode Tampilan: <strong class="text-indigo-600 dark:text-indigo-400">Semua Mapel Sekolah (Supervisi Kurikulum)</strong></span>
                @else
                    <span>Mode Tampilan: <strong class="text-brand-600 dark:text-brand-400">Khusus Mapel & Rombel yang Diampu</strong> ({{ $guru?->nama }})</span>
                @endif
            </span>
            @if(!empty($isSupervisi))
                <a href="{{ route('kurikulum.cp-tp.index') }}" class="font-semibold text-brand-600 hover:underline inline-flex items-center gap-1.5">
                    <i class="fa-solid fa-chalkboard-user"></i>
                    <span>Filter Mapel yang Saya Ampu Saja</span>
                </a>
            @else
                <a href="{{ route('kurikulum.cp-tp.index', ['supervisi' => 1]) }}" class="font-semibold text-indigo-600 hover:underline inline-flex items-center gap-1.5">
                    <i class="fa-solid fa-layer-group"></i>
                    <span>Lihat Semua Mapel Sekolah (Supervisi)</span>
                </a>
            @endif
        </div>
    @endif

    <!-- Alert Jika Guru Belum Memiliki Penugasan Mengajar -->
    @if($mapels->isEmpty())
        <div class="rounded-2xl border border-amber-200 bg-amber-50/70 p-5 text-xs text-amber-800 dark:border-amber-500/20 dark:bg-amber-500/10 dark:text-amber-300 flex items-start gap-3">
            <i class="fa-solid fa-triangle-exclamation text-amber-600 text-lg mt-0.5"></i>
            <div>
                <span class="font-bold block text-sm">Tidak Ada Mata Pelajaran yang Diampu</span>
                <span class="text-xs block mt-1">Akun Anda saat ini belum memiliki penugasan mata pelajaran di SK Pembagian Tugas Mengajar. Silakan hubungi Administrator atau Waka Kurikulum untuk mengatur pembagian tugas mengajar Anda.</span>
            </div>
        </div>
    @endif

    <!-- Filter Bar: Mapel & Tingkat / Fase yang Diampu -->
    <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-xs dark:border-gray-800 dark:bg-gray-900">
        <form method="GET" action="{{ route('kurikulum.cp-tp.index') }}" class="grid grid-cols-1 sm:grid-cols-3 gap-4 items-end">
            <input type="hidden" name="tab" :value="activeTab">
            @if(!empty($isSupervisi))
                <input type="hidden" name="supervisi" value="1">
            @endif

            <!-- Pilih Mapel yang Diampu -->
            <div>
                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1.5">
                    <i class="fa-solid fa-book-open text-brand-500 me-1"></i>
                    <span>Mata Pelajaran yang Diampu:</span>
                </label>
                <select name="mapel_id" onchange="this.form.submit()" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-3.5 py-2 text-xs font-medium text-gray-800 dark:text-white focus:border-brand-500 focus:outline-hidden">
                    @forelse($mapels as $m)
                        <option value="{{ $m->id }}" {{ $selectedMapelId == $m->id ? 'selected' : '' }}>
                            {{ $m->nama_mapel }}
                        </option>
                    @empty
                        <option value="">-- Belum ada mapel diampu --</option>
                    @endforelse
                </select>
            </div>

            <!-- Pilih Tingkat & Rombel yang Diampu -->
            <div>
                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1.5">
                    <i class="fa-solid fa-layer-group text-indigo-500 me-1"></i>
                    <span>Tingkat & Rombel yang Diampu:</span>
                </label>
                <select name="tingkat" onchange="this.form.submit()" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-3.5 py-2 text-xs font-medium text-gray-800 dark:text-white focus:border-brand-500 focus:outline-hidden">
                    @forelse($availableTingkats as $t)
                        <option value="{{ $t['tingkat'] }}" {{ $selectedTingkat == $t['tingkat'] ? 'selected' : '' }}>
                            {{ $t['label'] }}
                        </option>
                    @empty
                        <option value="10">Kelas 10 (Fase E)</option>
                    @endforelse
                </select>
            </div>

            <!-- Info Aktif -->
            <div class="flex items-center gap-2">
                <div class="rounded-xl bg-brand-50 dark:bg-brand-500/10 px-4 py-2 border border-brand-100 dark:border-brand-500/20 w-full">
                    <span class="text-[10px] font-bold uppercase text-brand-600 dark:text-brand-400">Mapel & Tingkat Aktif</span>
                    <div class="text-xs font-bold text-gray-800 dark:text-white truncate">
                        {{ $selectedMapel?->nama_mapel ?? 'Semua Mapel' }}
                    </div>
                    <div class="text-[11px] text-gray-500 dark:text-gray-400 truncate">
                        Fase {{ $selectedFase }} (Tingkat {{ $selectedTingkat }})
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- Tab Navigation: Capaian Pembelajaran (CP) & Tujuan Pembelajaran (TP) -->
    <div class="border-b border-gray-200 dark:border-gray-800">
        <nav class="flex space-x-6 rtl:space-x-reverse" aria-label="Tabs">
            <!-- TAB 1: CP (Capaian Pembelajaran - Di Awal) -->
            <button type="button"
                @click="activeTab = 'cp'"
                class="pb-3 text-xs font-bold transition flex items-center gap-2 border-b-2"
                :class="activeTab === 'cp' ? 'border-brand-500 text-brand-600 dark:text-brand-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'">
                <i class="fa-solid fa-award"></i>
                <span>Capaian Pembelajaran (CP)</span>
                <span class="rounded-full bg-indigo-100 dark:bg-indigo-500/20 px-2 py-0.5 text-[10px] text-indigo-600 dark:text-indigo-400" x-text="'{{ $cps->count() }}'"></span>
            </button>

            <!-- TAB 2: TP (Tujuan Pembelajaran - Setelah CP) -->
            <button type="button"
                @click="activeTab = 'tp'"
                class="pb-3 text-xs font-bold transition flex items-center gap-2 border-b-2"
                :class="activeTab === 'tp' ? 'border-brand-500 text-brand-600 dark:text-brand-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'">
                <i class="fa-solid fa-list-check"></i>
                <span>Tujuan Pembelajaran (TP)</span>
                <span class="rounded-full bg-brand-100 dark:bg-brand-500/20 px-2 py-0.5 text-[10px] text-brand-600 dark:text-brand-400" x-text="'{{ $tps->count() }}'"></span>
            </button>
        </nav>
    </div>

    <!-- TAB 1: CAPAIAN PEMBELAJARAN (CP) — DI AWAL -->
    <div x-show="activeTab === 'cp'" class="space-y-4">
        <div class="rounded-2xl border border-gray-200 bg-white shadow-theme-xs dark:border-gray-800 dark:bg-gray-900 overflow-hidden">
            <div class="p-4 border-b border-gray-100 dark:border-gray-800 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <h3 class="text-sm font-bold text-gray-800 dark:text-white flex items-center gap-2">
                        <span>Capaian Pembelajaran (CP) — {{ $selectedMapel?->nama_mapel }}</span>
                    </h3>
                    <p class="text-xs text-gray-400 mt-0.5">
                        Elemen dan Capaian Pembelajaran resmi per fase (opsional, jika ingin mengelompokkan TP berdasarkan elemen CP).
                    </p>
                </div>
                <button type="button" @click="isAddCpOpen = true" class="inline-flex items-center gap-1.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white px-3.5 py-1.5 text-xs font-semibold shadow-theme-xs transition self-start sm:self-auto">
                    <i class="fa-solid fa-plus text-xs"></i>
                    <span>Tambah Elemen CP</span>
                </button>
            </div>

            <div class="p-5 space-y-4">
                @forelse($cps as $cp)
                    <div class="rounded-xl border border-gray-200 dark:border-gray-800 p-4 bg-gray-50/50 dark:bg-gray-800/30 hover:border-indigo-300 dark:hover:border-indigo-700 transition">
                        <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4">
                            <div class="space-y-2 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="rounded-md bg-indigo-50 dark:bg-indigo-500/10 text-indigo-700 dark:text-indigo-400 px-2 py-0.5 text-xs font-bold font-mono">
                                        Fase {{ $cp->fase }} {{ $cp->tingkat ? '(Kelas ' . $cp->tingkat . ')' : '' }}
                                    </span>
                                    <h4 class="text-xs font-bold text-gray-800 dark:text-white uppercase tracking-wider">
                                        Elemen: {{ $cp->elemen }}
                                    </h4>
                                    <!-- Hitung TP terkait -->
                                    <span class="rounded-full bg-brand-50 text-brand-700 dark:bg-brand-500/10 dark:text-brand-400 px-2 py-0.5 text-[10px] font-semibold">
                                        <i class="fa-solid fa-diagram-project me-1"></i>
                                        {{ $cp->tujuanPembelajarans->count() }} TP Ditautkan
                                    </span>
                                </div>

                                <p class="text-xs text-gray-600 dark:text-gray-300 leading-relaxed bg-white dark:bg-gray-900/60 p-3 rounded-lg border border-gray-100 dark:border-gray-800">
                                    {{ $cp->deskripsi }}
                                </p>
                            </div>

                            <div class="flex items-center gap-1.5 self-end sm:self-start">
                                <button type="button" @click="openAddTpForCp('{{ $cp->id }}')" class="inline-flex items-center gap-1.5 rounded-lg bg-brand-50 text-brand-700 dark:bg-brand-500/10 dark:text-brand-400 hover:bg-brand-100 dark:hover:bg-brand-500/20 px-2.5 py-1.5 text-xs font-semibold transition" title="Tambah TP yang bertaut ke CP ini">
                                    <i class="fa-solid fa-plus text-[10px]"></i>
                                    <span>Tambah TP</span>
                                </button>
                                <button type="button" @click="openEditCp({{ json_encode($cp) }})" class="p-1.5 rounded-lg text-gray-400 hover:text-indigo-600 hover:bg-white dark:hover:bg-gray-700 transition" title="Edit CP">
                                    <i class="fa-solid fa-pen-to-square text-xs"></i>
                                </button>
                                <form action="{{ route('kurikulum.cp-tp.cp.destroy', $cp->id) }}" method="POST" onsubmit="return confirm('Hapus Capaian Pembelajaran elemen {{ $cp->elemen }}?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-1.5 rounded-lg text-gray-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-500/10 transition" title="Hapus CP">
                                        <i class="fa-solid fa-trash-can text-xs"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="py-10 text-center text-gray-400">
                        <i class="fa-solid fa-award text-3xl mb-2 text-indigo-300 dark:text-indigo-800 block"></i>
                        <span class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1">Belum ada Capaian Pembelajaran (CP) untuk mapel ini.</span>
                        <span class="block text-[11px] text-gray-400 mb-3">Pengisian CP bersifat opsional. Anda tetap dapat langsung mengisi Tujuan Pembelajaran (TP) pada tab TP.</span>
                        <button type="button" @click="isAddCpOpen = true" class="inline-flex items-center gap-1.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 text-xs font-semibold shadow-theme-xs transition">
                            <i class="fa-solid fa-plus text-xs"></i>
                            <span>Tambah CP Baru</span>
                        </button>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- TAB 2: TUJUAN PEMBELAJARAN (TP) — BISA LANGSUNG DIISI TANPA SYARAT CP -->
    <div x-show="activeTab === 'tp'" class="space-y-4">
        <div class="rounded-2xl border border-gray-200 bg-white shadow-theme-xs dark:border-gray-800 dark:bg-gray-900 overflow-hidden">
            <div class="p-4 border-b border-gray-100 dark:border-gray-800 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <h3 class="text-sm font-bold text-gray-800 dark:text-white flex items-center gap-2">
                        <span>Daftar Tujuan Pembelajaran (TP) — {{ $selectedMapel?->nama_mapel }} (Kelas {{ $selectedTingkat }})</span>
                    </h3>
                    <p class="text-xs text-gray-400 mt-0.5">
                        TP ini berkorelasi langsung dengan ulangan sumatif harian (TP 1 = Sumatif 1, TP 2 = Sumatif 2, dst) dan pembentukan narasi capaian rapor.
                    </p>
                </div>
                <button type="button" @click="selectedCpIdForTp = ''; isAddTpOpen = true" class="inline-flex items-center gap-1.5 rounded-xl bg-brand-500 hover:bg-brand-600 text-white px-3.5 py-1.5 text-xs font-semibold shadow-theme-xs transition self-start sm:self-auto">
                    <i class="fa-solid fa-plus text-xs"></i>
                    <span>Tambah TP Baru</span>
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-start text-xs text-gray-600 dark:text-gray-400">
                    <thead>
                        <tr class="border-b border-gray-100 dark:border-gray-800 bg-gray-50/70 dark:bg-gray-800/40 text-[11px] uppercase font-semibold text-gray-400">
                            <th class="py-3 px-3 text-center w-12">No</th>
                            <th class="py-3 px-3 text-center w-24">Kode TP</th>
                            <th class="py-3 px-4 text-start">Uraian Tujuan Pembelajaran (TP)</th>
                            <th class="py-3 px-4 text-start w-56">Ringkasan Materi (Frasa Rapor)</th>
                            <th class="py-3 px-3 text-center w-40">Elemen CP (Opsional)</th>
                            <th class="py-3 px-3 text-center w-20">Semester</th>
                            <th class="py-3 px-4 text-end w-24">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse($tps as $idx => $tp)
                            <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/30 transition">
                                <td class="py-3 px-3 text-center font-medium text-gray-400">{{ $idx + 1 }}</td>
                                <td class="py-3 px-3 text-center">
                                    <span class="inline-block rounded-lg bg-brand-50 text-brand-700 dark:bg-brand-500/10 dark:text-brand-400 px-2.5 py-0.5 font-bold font-mono text-[11px]">
                                        {{ $tp->kode_tp }}
                                    </span>
                                </td>
                                <td class="py-3 px-4">
                                    <div class="font-medium text-gray-800 dark:text-white leading-relaxed">
                                        {{ $tp->deskripsi }}
                                    </div>
                                </td>
                                <td class="py-3 px-4">
                                    <span class="text-xs text-gray-600 dark:text-gray-300 font-medium">
                                        {{ $tp->ringkasan_materi ?: '-' }}
                                    </span>
                                </td>
                                <!-- Elemen CP Opsional -->
                                <td class="py-3 px-3 text-center">
                                    @if($tp->cp)
                                        <span class="inline-flex items-center gap-1 rounded-md bg-indigo-50 dark:bg-indigo-500/10 text-indigo-700 dark:text-indigo-400 px-2 py-0.5 text-[11px] font-semibold">
                                            <i class="fa-solid fa-link text-[9px]"></i>
                                            {{ $tp->cp->elemen }}
                                        </span>
                                    @else
                                        <span class="text-gray-400 text-[11px]">-</span>
                                    @endif
                                </td>
                                <td class="py-3 px-3 text-center">
                                    <span class="rounded-md bg-gray-100 dark:bg-gray-800 px-2 py-0.5 text-[10px] font-medium text-gray-600 dark:text-gray-300">
                                        {{ $tp->semester }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-end">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <button type="button" @click="openEditTp({{ json_encode($tp) }})" class="p-1.5 rounded-lg text-gray-400 hover:text-brand-600 hover:bg-gray-50 dark:hover:bg-gray-800 transition" title="Edit TP">
                                            <i class="fa-solid fa-pen-to-square text-xs"></i>
                                        </button>
                                        <form action="{{ route('kurikulum.cp-tp.tp.destroy', $tp->id) }}" method="POST" onsubmit="return confirm('Hapus Tujuan Pembelajaran {{ $tp->kode_tp }}?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="p-1.5 rounded-lg text-gray-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-500/10 transition" title="Hapus TP">
                                                <i class="fa-solid fa-trash-can text-xs"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-12 text-center text-gray-400">
                                    <i class="fa-solid fa-list-check text-3xl mb-2 text-gray-300 dark:text-gray-600 block"></i>
                                    <span class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1">Belum ada Tujuan Pembelajaran (TP) untuk mapel ini.</span>
                                    <span class="block text-[11px] text-gray-400 mb-3">Anda bisa langsung menambahkan TP tanpa harus mengisi CP terlebih dahulu.</span>
                                    <button type="button" @click="selectedCpIdForTp = ''; isAddTpOpen = true" class="inline-flex items-center gap-1.5 rounded-xl bg-brand-500 hover:bg-brand-600 text-white px-4 py-2 text-xs font-semibold shadow-theme-xs transition">
                                        <i class="fa-solid fa-plus text-xs"></i>
                                        <span>Tambah TP Sekarang</span>
                                    </button>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- MODAL 1: Tambah Tujuan Pembelajaran (TP) — LANGSUNG BISA INPUT TP -->
    <div x-cloak x-show="isAddTpOpen" class="fixed inset-0 z-999 flex items-center justify-center bg-gray-900/60 backdrop-blur-xs p-4">
        <div @click.away="isAddTpOpen = false" class="w-full max-w-xl rounded-2xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 shadow-theme-xl overflow-hidden">
            <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-800 p-5">
                <h3 class="text-sm font-bold text-gray-800 dark:text-white flex items-center gap-2">
                    <i class="fa-solid fa-plus-circle text-brand-500"></i>
                    <span>Tambah Tujuan Pembelajaran (TP)</span>
                </h3>
                <button type="button" @click="isAddTpOpen = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                    <i class="fa-solid fa-xmark text-base"></i>
                </button>
            </div>

            <form action="{{ route('kurikulum.cp-tp.tp.store') }}" method="POST" class="p-5 space-y-4">
                @csrf
                <input type="hidden" name="mapel_id" value="{{ $selectedMapelId }}">
                <input type="hidden" name="tingkat" value="{{ $selectedTingkat }}">

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Kode TP (e.g. TP 1, TP 2): *</label>
                        <input type="text" name="kode_tp" required placeholder="TP 1" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-3.5 py-2 text-xs font-mono font-bold text-gray-800 dark:text-white focus:border-brand-500 focus:outline-hidden">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Semester: *</label>
                        <select name="semester" required class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-3.5 py-2 text-xs font-medium text-gray-800 dark:text-white focus:border-brand-500 focus:outline-hidden">
                            <option value="Ganjil">Semester Ganjil</option>
                            <option value="Genap">Semester Genap</option>
                        </select>
                    </div>
                </div>

                <!-- CP Terkait (Opsional) -->
                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                        Capaian Pembelajaran (CP) Terkait (Opsional):
                    </label>
                    <select name="cp_id" x-model="selectedCpIdForTp" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-3.5 py-2 text-xs font-medium text-gray-800 dark:text-white focus:border-brand-500 focus:outline-hidden">
                        <option value="">-- Tanpa Elemen CP (Langsung Input TP) --</option>
                        @foreach($cps as $cp)
                            <option value="{{ $cp->id }}">
                                [Elemen: {{ $cp->elemen }}] — Fase {{ $cp->fase }} {{ $cp->tingkat ? '(Kls ' . $cp->tingkat . ')' : '' }}
                            </option>
                        @endforeach
                    </select>
                    <span class="text-[10px] text-gray-400 mt-0.5 block">Jika belum membuat CP, Anda dapat langsung menyimpan TP tanpa memilih elemen CP.</span>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Uraian Lengkap Tujuan Pembelajaran (TP): *</label>
                    <textarea name="deskripsi" rows="3" required placeholder="Contoh: Peserta didik mampu menganalisis struktur dan kaidah kebahasaan teks argumentasi..." class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-3.5 py-2 text-xs text-gray-800 dark:text-white focus:border-brand-500 focus:outline-hidden"></textarea>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Ringkasan Materi (Frasa Rapor):</label>
                    <input type="text" name="ringkasan_materi" placeholder="Contoh: Teks Argumentasi & Kaidah Kebahasaan" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-3.5 py-2 text-xs text-gray-800 dark:text-white focus:border-brand-500 focus:outline-hidden">
                    <span class="text-[10px] text-gray-400 mt-0.5 block">Digunakan sistem untuk menghasilkan kalimat deskripsi capaian rapor secara ringkas dan rapi.</span>
                </div>

                <div class="flex items-center justify-end gap-2 pt-3 border-t border-gray-100 dark:border-gray-800">
                    <button type="button" @click="isAddTpOpen = false" class="rounded-xl border border-gray-200 dark:border-gray-700 px-4 py-2 text-xs font-semibold text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800">
                        Batal
                    </button>
                    <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-brand-500 px-5 py-2 text-xs font-semibold text-white shadow-theme-xs hover:bg-brand-600 transition">
                        <i class="fa-solid fa-floppy-disk text-xs"></i>
                        <span>Simpan TP</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 2: Edit Tujuan Pembelajaran (TP) -->
    <div x-cloak x-show="isEditTpOpen" class="fixed inset-0 z-999 flex items-center justify-center bg-gray-900/60 backdrop-blur-xs p-4">
        <div @click.away="isEditTpOpen = false" class="w-full max-w-xl rounded-2xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 shadow-theme-xl overflow-hidden">
            <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-800 p-5">
                <h3 class="text-sm font-bold text-gray-800 dark:text-white flex items-center gap-2">
                    <i class="fa-solid fa-pen-to-square text-brand-500"></i>
                    <span>Edit Tujuan Pembelajaran (TP)</span>
                </h3>
                <button type="button" @click="isEditTpOpen = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                    <i class="fa-solid fa-xmark text-base"></i>
                </button>
            </div>

            <form :action="'{{ url('kurikulum/cp-tp/tp') }}/' + editTpData.id" method="POST" class="p-5 space-y-4">
                @csrf
                @method('PUT')

                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                        Capaian Pembelajaran (CP) Terkait (Opsional):
                    </label>
                    <select name="cp_id" x-model="editTpData.cp_id" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-3.5 py-2 text-xs font-medium text-gray-800 dark:text-white focus:border-brand-500 focus:outline-hidden">
                        <option value="">-- Tanpa Elemen CP --</option>
                        @foreach($cps as $cp)
                            <option value="{{ $cp->id }}">
                                [Elemen: {{ $cp->elemen }}] — Fase {{ $cp->fase }} {{ $cp->tingkat ? '(Kls ' . $cp->tingkat . ')' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Kode TP: *</label>
                    <input type="text" name="kode_tp" x-model="editTpData.kode_tp" required class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-3.5 py-2 text-xs font-mono font-bold text-gray-800 dark:text-white focus:border-brand-500 focus:outline-hidden">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Uraian Lengkap Tujuan Pembelajaran (TP): *</label>
                    <textarea name="deskripsi" x-model="editTpData.deskripsi" rows="3" required class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-3.5 py-2 text-xs text-gray-800 dark:text-white focus:border-brand-500 focus:outline-hidden"></textarea>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Ringkasan Materi (Frasa Rapor):</label>
                    <input type="text" name="ringkasan_materi" x-model="editTpData.ringkasan_materi" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-3.5 py-2 text-xs text-gray-800 dark:text-white focus:border-brand-500 focus:outline-hidden">
                </div>

                <div class="flex items-center justify-end gap-2 pt-3 border-t border-gray-100 dark:border-gray-800">
                    <button type="button" @click="isEditTpOpen = false" class="rounded-xl border border-gray-200 dark:border-gray-700 px-4 py-2 text-xs font-semibold text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800">
                        Batal
                    </button>
                    <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-brand-500 px-5 py-2 text-xs font-semibold text-white shadow-theme-xs hover:bg-brand-600 transition">
                        <i class="fa-solid fa-floppy-disk text-xs"></i>
                        <span>Perbarui TP</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 3: Tambah Capaian Pembelajaran (CP) — SPESIFIK TINGKAT KELAS -->
    <div x-cloak x-show="isAddCpOpen" class="fixed inset-0 z-999 flex items-center justify-center bg-gray-900/60 backdrop-blur-xs p-4">
        <div @click.away="isAddCpOpen = false" class="w-full max-w-xl rounded-2xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 shadow-theme-xl overflow-hidden">
            <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-800 p-5">
                <h3 class="text-sm font-bold text-gray-800 dark:text-white flex items-center gap-2">
                    <i class="fa-solid fa-award text-indigo-600"></i>
                    <span>Tambah Capaian Pembelajaran (CP) — Kelas {{ $selectedTingkat }}</span>
                </h3>
                <button type="button" @click="isAddCpOpen = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                    <i class="fa-solid fa-xmark text-base"></i>
                </button>
            </div>

            <form action="{{ route('kurikulum.cp-tp.cp.store') }}" method="POST" class="p-5 space-y-4">
                @csrf
                <input type="hidden" name="mapel_id" value="{{ $selectedMapelId }}">

                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Tingkatan Kelas yang Diampu: *</label>
                    <select name="tingkat" required class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-3.5 py-2 text-xs font-bold text-gray-800 dark:text-white focus:border-indigo-500 focus:outline-hidden">
                        @foreach($availableTingkats as $t)
                            <option value="{{ $t['tingkat'] }}" {{ $selectedTingkat == $t['tingkat'] ? 'selected' : '' }}>
                                {{ $t['label'] }}
                            </option>
                        @endforeach
                    </select>
                    <span class="text-[10px] text-gray-400 mt-0.5 block">CP akan tersimpan khusus untuk tingkatan kelas ini dan tidak bercampur dengan tingkatan lain.</span>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Nama Elemen CP: *</label>
                    <input type="text" name="elemen" required placeholder="Contoh: Menyimak / Membaca dan Memirsa / Pemrograman Berorientasi Objek" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-3.5 py-2 text-xs text-gray-800 dark:text-white focus:border-indigo-500 focus:outline-hidden">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Deskripsi Capaian Pembelajaran: *</label>
                    <textarea name="deskripsi" rows="4" required placeholder="Tuliskan rumusan capaian pembelajaran resmi untuk elemen ini..." class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-3.5 py-2 text-xs text-gray-800 dark:text-white focus:border-indigo-500 focus:outline-hidden"></textarea>
                </div>

                <div class="flex items-center justify-end gap-2 pt-3 border-t border-gray-100 dark:border-gray-800">
                    <button type="button" @click="isAddCpOpen = false" class="rounded-xl border border-gray-200 dark:border-gray-700 px-4 py-2 text-xs font-semibold text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800">
                        Batal
                    </button>
                    <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-5 py-2 text-xs font-semibold text-white shadow-theme-xs hover:bg-indigo-700 transition">
                        <i class="fa-solid fa-floppy-disk text-xs"></i>
                        <span>Simpan CP</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 4: Edit Capaian Pembelajaran (CP) -->
    <div x-cloak x-show="isEditCpOpen" class="fixed inset-0 z-999 flex items-center justify-center bg-gray-900/60 backdrop-blur-xs p-4">
        <div @click.away="isEditCpOpen = false" class="w-full max-w-xl rounded-2xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 shadow-theme-xl overflow-hidden">
            <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-800 p-5">
                <h3 class="text-sm font-bold text-gray-800 dark:text-white flex items-center gap-2">
                    <i class="fa-solid fa-pen-to-square text-indigo-600"></i>
                    <span>Edit Capaian Pembelajaran (CP)</span>
                </h3>
                <button type="button" @click="isEditCpOpen = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                    <i class="fa-solid fa-xmark text-base"></i>
                </button>
            </div>

            <form :action="'{{ url('kurikulum/cp-tp/cp') }}/' + editCpData.id" method="POST" class="p-5 space-y-4">
                @csrf
                @method('PUT')

                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Tingkatan Kelas: *</label>
                    <select name="tingkat" x-model="editCpData.tingkat" required class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-3.5 py-2 text-xs font-bold text-gray-800 dark:text-white focus:border-indigo-500 focus:outline-hidden">
                        <option value="10">Kelas 10 (Fase E)</option>
                        <option value="11">Kelas 11 (Fase F)</option>
                        <option value="12">Kelas 12 (Fase F)</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Nama Elemen CP: *</label>
                    <input type="text" name="elemen" x-model="editCpData.elemen" required class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-3.5 py-2 text-xs text-gray-800 dark:text-white focus:border-indigo-500 focus:outline-hidden">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Deskripsi Capaian Pembelajaran: *</label>
                    <textarea name="deskripsi" x-model="editCpData.deskripsi" rows="4" required class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-3.5 py-2 text-xs text-gray-800 dark:text-white focus:border-indigo-500 focus:outline-hidden"></textarea>
                </div>

                <div class="flex items-center justify-end gap-2 pt-3 border-t border-gray-100 dark:border-gray-800">
                    <button type="button" @click="isEditCpOpen = false" class="rounded-xl border border-gray-200 dark:border-gray-700 px-4 py-2 text-xs font-semibold text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800">
                        Batal
                    </button>
                    <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-5 py-2 text-xs font-semibold text-white shadow-theme-xs hover:bg-indigo-700 transition">
                        <i class="fa-solid fa-floppy-disk text-xs"></i>
                        <span>Perbarui CP</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
