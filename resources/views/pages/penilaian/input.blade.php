@extends('layouts.app')

@section('content')
<div x-data="{
    jumlahSumatif: {{ $jumlahSumatif }},
    bobotHarian: {{ $bobotHarian ?? 50 }},
    bobotSts: {{ $bobotSts ?? 20 }},
    bobotSas: {{ $bobotSas ?? 30 }},
    kkmKelas: {{ $kelas->kkm ?? 75 }},
    tpCount: {{ $tpCount ?? $tps->count() }},
    tpsList: {{ json_encode($tps->values()) }},
    modalSyncCbt: false,
    syncTargetType: 'sas',
    syncSumatifIndex: 1,
    syncUjianId: '',
    cbtExams: {{ json_encode($cbtExams ?? []) }},
    scores: {},

    init() {
        @foreach($siswas as $s)
            @php $n = $nilais[$s->id] ?? null; @endphp
            this.scores[{{ $s->id }}] = {
                s1: '{{ $n?->sumatif_1 ?? '' }}',
                s2: '{{ $n?->sumatif_2 ?? '' }}',
                s3: '{{ $n?->sumatif_3 ?? '' }}',
                s4: '{{ $n?->sumatif_4 ?? '' }}',
                s5: '{{ $n?->sumatif_5 ?? '' }}',
                s6: '{{ $n?->sumatif_6 ?? '' }}',
                rataHarian: '{{ $n?->rata_sumatif_harian ?? '' }}',
                sts: '{{ $n?->nilai_uts ?? '' }}',
                uas: '{{ $n?->nilai_uas ?? '' }}',
                akhir: '{{ $n?->nilai_akhir ?? '' }}',
                capaian: '{{ addslashes($n?->capaian_kompetensi ?? '') }}'
            };
            this.recalculate({{ $s->id }});
        @endforeach
    },

    setPresetRatio(harian, sts, sas) {
        this.bobotHarian = harian;
        this.bobotSts = sts;
        this.bobotSas = sas;
        this.recalculateAll();
    },

    setJumlahSumatif(val) {
        this.jumlahSumatif = val;
        this.recalculateAll();
    },

    getKetercapaianStatus(val) {
        let n = parseFloat(val);
        if (isNaN(n) || val === '' || val === null) return 'empty';
        return n >= this.kkmKelas ? 'tercapai' : 'tidak_tercapai';
    },

    recalculate(siswaId) {
        let sc = this.scores[siswaId];
        if (!sc) return;

        // Hitung Rata-rata Sumatif Harian (hanya dari kolom yang aktif dan terisi)
        let total = 0;
        let count = 0;

        for (let i = 1; i <= this.jumlahSumatif; i++) {
            let val = parseFloat(sc['s' + i]);
            if (!isNaN(val)) {
                total += val;
                count++;
            }
        }

        let rata = count > 0 ? (Math.round((total / count) * 10) / 10).toFixed(1) : '';
        sc.rataHarian = rata;

        // Hitung Nilai Akhir berdasarkan Rasio Bobot Komprehensif (Harian + STS + SAS)
        let rHarian = parseFloat(rata);
        let rSts = parseFloat(sc.sts);
        let rSas = parseFloat(sc.uas);

        let bHarian = parseFloat(this.bobotHarian) || 0;
        let bSts = parseFloat(this.bobotSts) || 0;
        let bSas = parseFloat(this.bobotSas) || 0;

        let weightedSum = 0;
        let totalWeight = 0;

        if (!isNaN(rHarian) && bHarian > 0) {
            weightedSum += (rHarian * bHarian);
            totalWeight += bHarian;
        }
        if (!isNaN(rSts) && bSts > 0) {
            weightedSum += (rSts * bSts);
            totalWeight += bSts;
        }
        if (!isNaN(rSas) && bSas > 0) {
            weightedSum += (rSas * bSas);
            totalWeight += bSas;
        }

        if (totalWeight > 0) {
            sc.akhir = (Math.round((weightedSum / totalWeight) * 10) / 10).toFixed(1);
        } else {
            sc.akhir = '';
        }
    },

    recalculateAll() {
        Object.keys(this.scores).forEach(id => {
            this.recalculate(id);
        });
    },

    getPredikat(val) {
        let n = parseFloat(val);
        if (isNaN(n) || val === '') return '-';
        if (n >= 88) return 'A (Sangat Baik)';
        if (n >= 76) return 'B (Baik)';
        if (n >= 65) return 'C (Cukup)';
        return 'D (Perlu Bimbingan)';
    },

    getBadgeClass(val) {
        let n = parseFloat(val);
        if (isNaN(n) || val === '') return 'bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400';
        if (n >= 88) return 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400 font-bold';
        if (n >= 76) return 'bg-blue-50 text-blue-700 dark:bg-blue-500/10 dark:text-blue-400 font-bold';
        if (n >= 65) return 'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400 font-bold';
        return 'bg-rose-50 text-rose-700 dark:bg-rose-500/10 dark:text-rose-400 font-bold';
    },

    generateCapaianForSiswa(siswaId) {
        let sc = this.scores[siswaId];
        if (!sc) return;
        let kkm = parseFloat(this.kkmKelas) || 75;

        // Kumpulkan daftar materi dari TP terdaftar
        let getMateriFromTp = (tp, fallbackIndex) => {
            if (!tp) return 'materi sumatif ' + fallbackIndex;
            let m = (tp.ringkasan_materi && tp.ringkasan_materi.trim() !== '') 
                ? tp.ringkasan_materi.trim() 
                : ((tp.deskripsi && tp.deskripsi.trim() !== '') ? tp.deskripsi.trim() : (tp.kode_tp || ('TP ' + fallbackIndex)));
            return m.replace(/[\.\,\;]+$/, '').trim();
        };

        let tpsTercapai = [];
        let tpsTidakTercapai = [];
        let anySumatifFilled = false;

        let maxCheck = Math.max(this.jumlahSumatif, (this.tpsList ? this.tpsList.length : 0));

        for (let i = 1; i <= maxCheck; i++) {
            let val = parseFloat(sc['s' + i]);
            let tp = (this.tpsList && this.tpsList[i - 1]) ? this.tpsList[i - 1] : null;

            if (!isNaN(val)) {
                anySumatifFilled = true;
                let materi = getMateriFromTp(tp, i);
                let kode = tp ? tp.kode_tp : ('TP ' + i);

                if (val >= kkm) {
                    tpsTercapai.push({ kode: kode, materi: materi, score: val });
                } else {
                    tpsTidakTercapai.push({ kode: kode, materi: materi, score: val });
                }
            }
        }

        if (anySumatifFilled) {
            // Urutkan nilai tertinggi untuk yang tercapai, nilai terendah untuk yang tidak tercapai
            tpsTercapai.sort((a, b) => b.score - a.score);
            tpsTidakTercapai.sort((a, b) => a.score - b.score);

            // Pengelompokan tingkat capaian TP tercapai berdasarkan nilai (A: Sangat Baik, B: Baik, C: Cukup)
            let kelompok = {
                'sangat baik': [],
                'baik': [],
                'cukup': []
            };

            tpsTercapai.forEach(t => {
                let pred = (t.score >= 88) ? 'sangat baik' : ((t.score >= 76) ? 'baik' : 'cukup');
                if (!kelompok[pred].includes(t.materi)) {
                    kelompok[pred].push(t.materi);
                }
            });

            let partsTercapai = [];
            if (kelompok['sangat baik'].length > 0) {
                partsTercapai.push('sangat baik dalam hal ' + kelompok['sangat baik'].join(', '));
            }
            if (kelompok['baik'].length > 0) {
                partsTercapai.push('baik dalam hal ' + kelompok['baik'].join(', '));
            }
            if (kelompok['cukup'].length > 0) {
                partsTercapai.push('cukup dalam hal ' + kelompok['cukup'].join(', '));
            }

            let teksTercapai = '';
            if (partsTercapai.length > 0) {
                teksTercapai = 'Mencapai Kompetensi dengan ' + partsTercapai.join(', serta dengan ') + '.';
            }

            let teksPeningkatan = '';
            if (tpsTidakTercapai.length > 0) {
                let mBelum = tpsTidakTercapai.map(t => t.materi).join(', ');
                teksPeningkatan = 'Perlu peningkatan dalam hal ' + mBelum + '.';
            }

            // Susun kalimat deskripsi capaian kompetensi
            if (teksTercapai && teksPeningkatan) {
                sc.capaian = teksTercapai + ' ' + teksPeningkatan;
            } else if (teksTercapai) {
                sc.capaian = teksTercapai;
            } else if (teksPeningkatan) {
                sc.capaian = teksPeningkatan;
            }
        } else {
            // Jika kolom sumatif belum diisi satu-satu (misal baru ada nilai SAS / Nilai Akhir)
            let na = parseFloat(sc.akhir);
            if (isNaN(na)) na = parseFloat(sc.uas);

            if (!isNaN(na)) {
                let daftarMateri = [];
                if (this.tpsList && this.tpsList.length > 0) {
                    daftarMateri = this.tpsList.map((tp, idx) => getMateriFromTp(tp, idx + 1)).filter(m => m.length > 0);
                }

                let materiStr = daftarMateri.length > 0 
                    ? daftarMateri.join(', ') 
                    : 'materi mata pelajaran {{ addslashes($mapel->nama_mapel) }}';

                if (na >= 88) {
                    sc.capaian = 'Mencapai Kompetensi dengan sangat baik dalam hal ' + materiStr + '.';
                } else if (na >= 76) {
                    sc.capaian = 'Mencapai Kompetensi dengan baik dalam hal ' + materiStr + '.';
                } else if (na >= kkm) {
                    sc.capaian = 'Mencapai Kompetensi dengan cukup dalam hal ' + materiStr + '.';
                } else {
                    sc.capaian = 'Perlu peningkatan dalam hal ' + materiStr + '.';
                }
            }
        }
    },

    generateAllCapaian() {
        Object.keys(this.scores).forEach(id => {
            this.generateCapaianForSiswa(id);
        });
    }
}" class="space-y-6 pb-28">

    <x-common.role-switcher-banner activeRole="guru" />

    <!-- Top Navigation & Information Header -->
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div class="flex items-center gap-3">
            <a href="{{ route('penilaian.index') }}" class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-2.5 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition shadow-theme-xs" title="Kembali ke Daftar Mapel">
                <i class="fa-solid fa-arrow-left text-sm rtl:rotate-180"></i>
            </a>
            <div>
                <h2 class="text-base font-bold text-gray-800 dark:text-white flex items-center gap-2">
                    <span>Lembar Penilaian: {{ $mapel->nama_mapel }}</span>
                </h2>
                <div class="flex flex-wrap items-center gap-2 text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                    <span class="inline-flex items-center gap-1 font-semibold text-brand-600 dark:text-brand-400">
                        <i class="fa-solid fa-school text-[11px]"></i>
                        {{ $kelas->nama_kelas }}
                    </span>
                    <span>•</span>
                    <span class="font-medium text-gray-700 dark:text-gray-300">Tahun Ajaran {{ $activeTahunAjaran ?? '2025/2026' }} (Semester {{ $activeSemester ?? '2' }})</span>
                    <span>•</span>
                    <span>Total: {{ $siswas->count() }} Siswa</span>
                    <span>•</span>
                    <span class="inline-flex items-center gap-1 rounded-md bg-amber-50 dark:bg-amber-500/10 text-amber-700 dark:text-amber-400 px-2 py-0.5 font-bold text-[11px]" title="Batas Ketercapaian Tujuan Pembelajaran kelas ini diatur oleh Admin">
                        <i class="fa-solid fa-gauge-high text-[10px]"></i>
                        KKM Kelas: {{ $kelas->kkm ?? 75 }}
                    </span>
                    @if($assignedGuru)
                        <span>•</span>
                        <span class="text-gray-600 dark:text-gray-300">Pengampu: {{ $assignedGuru->guru?->nama }}</span>
                    @endif
                </div>
            </div>
        </div>

        <!-- Tombol Aksi Cepat: Tarik Nilai CBT Exam & Kelola TP -->
        <div class="flex flex-wrap items-center gap-2.5">
            <a href="{{ route('kurikulum.cp-tp.index', ['mapel_id' => $mapel->id, 'tingkat' => $kelas->tingkat]) }}" target="_blank" class="inline-flex items-center gap-2 rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-3.5 py-2 text-xs font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 shadow-theme-xs transition" title="Kelola Tujuan Pembelajaran (TP) untuk mapel ini">
                <i class="fa-solid fa-bullseye text-brand-500 text-xs"></i>
                <span>Kelola CP & TP</span>
            </a>

            <!-- Tombol Tarik Nilai dari CBT Exam Web (Bisa Pilih SAS, STS, atau Asesmen Harian) -->
            <button type="button" @click="modalSyncCbt = true"
                class="inline-flex items-center gap-2 rounded-xl bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 text-xs font-semibold shadow-theme-xs transition">
                <i class="fa-solid fa-cloud-arrow-down text-sm"></i>
                <span>Tarik Nilai CBT Exam</span>
                @if(!empty($cbtExams) && count($cbtExams) > 0)
                    <span class="rounded-full bg-white/20 px-1.5 py-0.5 text-[10px] font-bold">{{ count($cbtExams) }} Ujian</span>
                @endif
            </button>

            <button type="button" @click="generateAllCapaian()" class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 text-white hover:bg-indigo-700 px-3.5 py-2 text-xs font-semibold shadow-theme-xs transition" title="Otomatis hasilkan narasi capaian seluruh siswa berdasarkan ketercapaian TP & KKM">
                <i class="fa-solid fa-wand-magic-sparkles text-xs"></i>
                <span>Generate Semua Capaian (TP & KKM)</span>
            </button>
        </div>
    </div>

    <!-- Alert Notifikasi -->
    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50/80 p-4 text-xs font-medium text-emerald-800 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-300 flex items-center gap-2.5">
            <i class="fa-solid fa-circle-check text-emerald-600 text-sm"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if(session('error'))
        <div class="rounded-xl border border-rose-200 bg-rose-50/80 p-4 text-xs font-medium text-rose-800 dark:border-rose-500/20 dark:bg-rose-500/10 dark:text-rose-300 flex items-center gap-2.5">
            <i class="fa-solid fa-circle-exclamation text-rose-600 text-sm"></i>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <!-- Control Panel: Pengaturan Jumlah Sumatif & Rasio Perbandingan Custom -->
    <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-xs dark:border-gray-800 dark:bg-gray-900">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
            <!-- 1. Pilihan Jumlah Sumatif Harian (Otomatis Mengikuti Jumlah TP yang Diinput) -->
            <div class="space-y-2">
                <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                    <i class="fa-solid fa-list-ol me-1 text-brand-500"></i>
                    Jumlah Sumatif Harian (Berdasarkan TP)
                </label>
                @if($tps->isNotEmpty())
                    <div class="flex flex-wrap items-center gap-2.5">
                        <div class="inline-flex items-center gap-2 rounded-xl bg-brand-50 dark:bg-brand-500/10 border border-brand-200 dark:border-brand-500/20 px-3.5 py-2 text-xs">
                            <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                            <span class="font-bold text-brand-700 dark:text-brand-300" x-text="jumlahSumatif + ' Kolom Sumatif'"></span>
                            <span class="text-gray-500 dark:text-gray-400 text-[11px]">(Terkoneksi ke {{ $tps->count() }} TP aktif)</span>
                        </div>
                        <a href="{{ route('kurikulum.cp-tp.index', ['mapel_id' => $mapel->id, 'tingkat' => $kelas->tingkat, 'tab' => 'tp']) }}"
                            target="_blank"
                            class="text-xs font-semibold text-brand-600 hover:text-brand-700 dark:text-brand-400 hover:underline inline-flex items-center gap-1.5 px-3 py-2 rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-theme-xs">
                            <i class="fa-solid fa-pen-to-square text-[11px]"></i>
                            <span>Kelola TP / Tambah TP</span>
                        </a>
                    </div>
                @else
                    <div class="flex flex-wrap items-center gap-2">
                        <div class="inline-flex rounded-xl border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-800/40 p-1">
                            <template x-for="n in [1, 2, 3, 4, 5, 6]" :key="n">
                                <button type="button"
                                    @click="setJumlahSumatif(n)"
                                    class="rounded-lg px-2.5 py-1 text-xs font-semibold transition"
                                    :class="jumlahSumatif === n ? 'bg-white dark:bg-gray-800 text-brand-600 dark:text-brand-400 shadow-sm' : 'text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white'">
                                    <span x-text="n + ' Sumatif'"></span>
                                </button>
                            </template>
                        </div>
                        <a href="{{ route('kurikulum.cp-tp.index', ['mapel_id' => $mapel->id, 'tingkat' => $kelas->tingkat, 'tab' => 'tp']) }}"
                            target="_blank"
                            class="text-xs text-amber-600 dark:text-amber-400 font-semibold hover:underline inline-flex items-center gap-1 ms-1">
                            <i class="fa-solid fa-circle-plus text-[11px]"></i>
                            <span>Buat TP Terlebih Dahulu</span>
                        </a>
                    </div>
                @endif
            </div>

            <!-- 2. Rasio Perbandingan Custom Guru (Harian : STS : SAS) -->
            <div class="space-y-2">
                <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                    <i class="fa-solid fa-sliders me-1 text-purple-500"></i>
                    Rasio Bobot Nilai Akhir (Harian : STS : SAS)
                </label>
                <div class="flex flex-wrap items-center gap-2.5">
                    <div class="flex items-center gap-1.5">
                        <span class="text-xs text-gray-600 dark:text-gray-300 font-medium">Harian:</span>
                        <div class="relative w-16">
                            <input type="number" min="0" max="100"
                                x-model.number="bobotHarian"
                                @input="recalculateAll()"
                                class="w-full text-center rounded-lg border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-800/50 py-1.5 pe-4 text-xs font-bold font-mono text-gray-800 dark:text-white focus:border-brand-500 focus:outline-hidden">
                            <span class="absolute end-1.5 top-2 text-[10px] text-gray-400">%</span>
                        </div>
                    </div>

                    <span class="text-gray-400 font-bold">:</span>

                    <div class="flex items-center gap-1.5">
                        <span class="text-xs text-amber-600 dark:text-amber-400 font-medium">STS:</span>
                        <div class="relative w-16">
                            <input type="number" min="0" max="100"
                                x-model.number="bobotSts"
                                @input="recalculateAll()"
                                class="w-full text-center rounded-lg border border-amber-200 dark:border-amber-800/60 bg-amber-50/50 dark:bg-amber-900/20 py-1.5 pe-4 text-xs font-bold font-mono text-amber-800 dark:text-amber-300 focus:border-amber-500 focus:outline-hidden">
                            <span class="absolute end-1.5 top-2 text-[10px] text-gray-400">%</span>
                        </div>
                    </div>

                    <span class="text-gray-400 font-bold">:</span>

                    <div class="flex items-center gap-1.5">
                        <span class="text-xs text-purple-600 dark:text-purple-400 font-medium">SAS:</span>
                        <div class="relative w-16">
                            <input type="number" min="0" max="100"
                                x-model.number="bobotSas"
                                @input="recalculateAll()"
                                class="w-full text-center rounded-lg border border-purple-200 dark:border-purple-800/60 bg-purple-50/50 dark:bg-purple-900/20 py-1.5 pe-4 text-xs font-bold font-mono text-purple-800 dark:text-purple-300 focus:border-purple-500 focus:outline-hidden">
                            <span class="absolute end-1.5 top-2 text-[10px] text-gray-400">%</span>
                        </div>
                    </div>

                    <!-- Preset Cepat -->
                    <div class="flex items-center gap-1 ms-1">
                        <button type="button" @click="setPresetRatio(50, 20, 30)" class="rounded-md border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-2 py-1 text-[11px] font-medium text-gray-600 dark:text-gray-300 hover:bg-gray-100" title="Standar dengan STS">
                            50:20:30
                        </button>
                        <button type="button" @click="setPresetRatio(40, 30, 30)" class="rounded-md border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-2 py-1 text-[11px] font-medium text-gray-600 dark:text-gray-300 hover:bg-gray-100" title="Proporsional STS & SAS">
                            40:30:30
                        </button>
                        <button type="button" @click="setPresetRatio(60, 0, 40)" class="rounded-md border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-2 py-1 text-[11px] font-medium text-gray-600 dark:text-gray-300 hover:bg-gray-100" title="Tanpa STS (Harian:SAS)">
                            60:0:40
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Referensi Tujuan Pembelajaran (TP) Jika Tersedia -->
    @if($tps->isNotEmpty())
        <div class="rounded-2xl border border-blue-100 bg-blue-50/40 p-4 dark:border-blue-900/40 dark:bg-blue-950/20">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-bold uppercase tracking-wider text-blue-700 dark:text-blue-300 flex items-center gap-1.5">
                    <i class="fa-solid fa-bullseye text-xs"></i>
                    <span>Referensi Tujuan Pembelajaran (TP) Terdaftar untuk Sumatif</span>
                </span>
                <span class="text-[11px] text-blue-600 dark:text-blue-400 font-medium">
                    {{ $tps->count() }} TP Aktif
                </span>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-2.5">
                @foreach($tps->take(6) as $tp)
                    <div class="rounded-xl bg-white dark:bg-gray-800 p-2.5 border border-blue-100 dark:border-blue-900/30 text-xs">
                        <div class="flex items-center gap-1.5 font-bold font-mono text-brand-600 dark:text-brand-400 text-[11px]">
                            <span>{{ $tp->kode_tp }}</span>
                            @if($tp->cp)
                                <span class="text-[10px] font-normal text-gray-400">({{ $tp->cp->elemen }})</span>
                            @endif
                        </div>
                        <div class="text-[11px] text-gray-600 dark:text-gray-300 line-clamp-2 mt-1" title="{{ $tp->deskripsi }}">
                            {{ $tp->ringkasan_materi ?: $tp->deskripsi }}
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Main Grade Input Form -->
    <form action="{{ route('penilaian.store', [$kelas->id, $mapel->id]) }}" method="POST">
        @csrf

        <!-- Hidden input untuk menyimpan konfigurasi rasio dan jumlah sumatif -->
        <input type="hidden" name="jumlah_sumatif" :value="jumlahSumatif">
        <input type="hidden" name="bobot_harian" :value="bobotHarian">
        <input type="hidden" name="bobot_sts" :value="bobotSts">
        <input type="hidden" name="bobot_sas" :value="bobotSas">

        <div class="rounded-2xl border border-gray-200 bg-white shadow-theme-xs dark:border-gray-800 dark:bg-gray-900 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-start text-xs text-gray-600 dark:text-gray-400">
                    <thead>
                        <!-- Header Grouping -->
                        <tr class="border-b border-gray-100 dark:border-gray-800 bg-gray-50/70 dark:bg-gray-800/40 text-[11px] uppercase tracking-wider font-semibold text-gray-500 dark:text-gray-400">
                            <th rowspan="2" class="py-3 px-3 text-center w-12 border-e border-gray-100 dark:border-gray-800">No</th>
                            <th rowspan="2" class="py-3 px-4 text-start min-w-[200px] border-e border-gray-100 dark:border-gray-800">Peserta Didik</th>
                            <th :colspan="jumlahSumatif + 1" class="py-2 px-3 text-center bg-blue-50/50 dark:bg-blue-500/5 text-blue-700 dark:text-blue-300 border-e border-gray-100 dark:border-gray-800">
                                Nilai Sumatif Lingkup Materi / Harian (Input Manual Guru)
                            </th>
                            <th rowspan="2" class="py-3 px-3 text-center w-28 bg-amber-50/50 dark:bg-amber-500/5 text-amber-700 dark:text-amber-300 border-e border-gray-100 dark:border-gray-800">
                                STS / UTS
                                <span class="block text-[9px] font-normal text-amber-600 dark:text-amber-400">(Tengah Sem)</span>
                            </th>
                            <th rowspan="2" class="py-3 px-3 text-center w-28 bg-purple-50/50 dark:bg-purple-500/5 text-purple-700 dark:text-purple-300 border-e border-gray-100 dark:border-gray-800">
                                SAS / UAS
                                <span class="block text-[9px] font-normal text-purple-600 dark:text-purple-400">(Exam Web / CBT)</span>
                            </th>
                            <th rowspan="2" class="py-3 px-3 text-center w-28 bg-emerald-50/50 dark:bg-emerald-500/5 text-emerald-800 dark:text-emerald-300 border-e border-gray-100 dark:border-gray-800">
                                Nilai Akhir
                                <span class="block text-[9px] font-normal text-emerald-600 dark:text-emerald-400" x-text="'(' + bobotHarian + ':' + bobotSts + ':' + bobotSas + ')'"></span>
                            </th>
                            <th rowspan="2" class="py-3 px-3 text-center w-32 border-e border-gray-100 dark:border-gray-800">Predikat</th>
                            <th rowspan="2" class="py-3 px-4 text-start min-w-[340px]">Capaian Kompetensi / Catatan Rapor</th>
                        </tr>
                        <!-- Sub Header Sumatif (Terkorelasi ke TP & KKM Kelas) -->
                        <tr class="border-b border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-800/30 text-[10px] uppercase font-semibold text-gray-500">
                            @for($i = 1; $i <= 6; $i++)
                                @php $tp = $tps->get($i - 1); @endphp
                                <th x-show="jumlahSumatif >= {{ $i }}" class="py-2 px-1 text-center min-w-[85px] border-e border-gray-100 dark:border-gray-800">
                                    <div class="font-bold text-gray-700 dark:text-gray-300">Sumatif {{ $i }}</div>
                                    @if($tp)
                                        <div class="mt-0.5">
                                            <span class="inline-block rounded bg-brand-50 text-brand-700 dark:bg-brand-500/10 dark:text-brand-400 px-1 py-0.2 text-[9px] font-mono font-bold" title="{{ $tp->deskripsi }}">
                                                {{ $tp->kode_tp }}
                                            </span>
                                            <div class="text-[9px] text-gray-500 dark:text-gray-400 font-normal truncate max-w-[80px] mx-auto mt-0.5" title="{{ $tp->ringkasan_materi ?: $tp->deskripsi }}">
                                                {{ $tp->ringkasan_materi ?: Str::limit($tp->deskripsi, 14) }}
                                            </div>
                                        </div>
                                    @endif
                                    <div class="text-[9px] font-mono text-amber-600 dark:text-amber-400 font-bold mt-0.5">
                                        KKM: {{ $kelas->kkm ?? 75 }}
                                    </div>
                                </th>
                            @endfor
                            <th class="py-2 px-2 text-center w-24 bg-blue-100/40 dark:bg-blue-500/10 text-blue-700 dark:text-blue-300 border-e border-gray-100 dark:border-gray-800">
                                Rata Harian
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse($siswas as $idx => $s)
                            <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/30 transition">
                                <td class="py-3 px-3 text-center font-medium text-gray-400 border-e border-gray-100 dark:border-gray-800">
                                    {{ $idx + 1 }}
                                </td>
                                <td class="py-3 px-4 border-e border-gray-100 dark:border-gray-800">
                                    <div class="font-semibold text-gray-800 dark:text-white">{{ $s->nama }}</div>
                                    <div class="text-[11px] font-mono text-gray-400">NIS: {{ $s->nis }}</div>
                                </td>

                                <!-- Kolom Sumatif 1 s/d 6 (Terkorelasi ke TP & KKM Kelas) -->
                                @for($i = 1; $i <= 6; $i++)
                                    <td x-show="jumlahSumatif >= {{ $i }}" class="py-2 px-1 text-center border-e border-gray-100 dark:border-gray-800">
                                        <div class="flex flex-col items-center">
                                            <input type="number" step="0.1" min="0" max="100"
                                                name="nilai[{{ $s->id }}][sumatif_{{ $i }}]"
                                                x-model="scores[{{ $s->id }}].s{{ $i }}"
                                                @input="recalculate({{ $s->id }})"
                                                placeholder="0-100"
                                                class="w-16 text-center rounded-lg border py-1.5 px-1 text-xs font-mono font-bold focus:outline-hidden transition"
                                                :class="{
                                                    'border-emerald-400 bg-emerald-50/60 text-emerald-800 dark:bg-emerald-500/10 dark:text-emerald-300 dark:border-emerald-600': getKetercapaianStatus(scores[{{ $s->id }}].s{{ $i }}) === 'tercapai',
                                                    'border-rose-400 bg-rose-50/60 text-rose-800 dark:bg-rose-500/10 dark:text-rose-300 dark:border-rose-600': getKetercapaianStatus(scores[{{ $s->id }}].s{{ $i }}) === 'tidak_tercapai',
                                                    'border-gray-200 bg-gray-50 dark:border-gray-800 dark:bg-gray-800/50 text-gray-800 dark:text-white': getKetercapaianStatus(scores[{{ $s->id }}].s{{ $i }}) === 'empty'
                                                }">

                                            <!-- Indikator Batas Ketercapaian Realtime -->
                                            <template x-if="getKetercapaianStatus(scores[{{ $s->id }}].s{{ $i }}) === 'tercapai'">
                                                <span class="text-[9px] text-emerald-600 dark:text-emerald-400 font-bold flex items-center gap-0.5 mt-0.5">
                                                    <i class="fa-solid fa-check text-[8px]"></i> Tercapai
                                                </span>
                                            </template>
                                            <template x-if="getKetercapaianStatus(scores[{{ $s->id }}].s{{ $i }}) === 'tidak_tercapai'">
                                                <span class="text-[9px] text-rose-600 dark:text-rose-400 font-bold flex items-center gap-0.5 mt-0.5" title="Di bawah batas ketercapaian KKM ({{ $kelas->kkm ?? 75 }})">
                                                    <i class="fa-solid fa-triangle-exclamation text-[8px]"></i> Tidak Tercapai
                                                </span>
                                            </template>
                                        </div>
                                    </td>
                                @endfor

                                <!-- Rata-rata Harian (Auto Calculate) -->
                                <td class="py-2 px-2 text-center bg-blue-50/30 dark:bg-blue-500/5 border-e border-gray-100 dark:border-gray-800">
                                    <span class="font-mono font-bold text-xs text-blue-700 dark:text-blue-300"
                                        x-text="scores[{{ $s->id }}].rataHarian || '-'">
                                    </span>
                                </td>

                                <!-- Nilai STS / UTS (Sumatif Tengah Semester) -->
                                <td class="py-2 px-2 text-center bg-amber-50/20 dark:bg-amber-500/5 border-e border-gray-100 dark:border-gray-800">
                                    <input type="number" step="0.1" min="0" max="100"
                                        name="nilai[{{ $s->id }}][sts]"
                                        x-model="scores[{{ $s->id }}].sts"
                                        @input="recalculate({{ $s->id }})"
                                        placeholder="0-100"
                                        class="w-20 text-center rounded-lg border border-amber-200 dark:border-amber-800/60 bg-amber-50/50 dark:bg-amber-900/20 px-1.5 py-1.5 text-xs text-amber-900 dark:text-amber-200 font-mono font-bold focus:border-amber-500 focus:outline-hidden">
                                </td>

                                <!-- Nilai SAS / UAS (Bisa ditarik dari CBT atau diedit manual) -->
                                <td class="py-2 px-2 text-center bg-purple-50/20 dark:bg-purple-500/5 border-e border-gray-100 dark:border-gray-800">
                                    <input type="number" step="0.1" min="0" max="100"
                                        name="nilai[{{ $s->id }}][uas]"
                                        x-model="scores[{{ $s->id }}].uas"
                                        @input="recalculate({{ $s->id }})"
                                        placeholder="0-100"
                                        class="w-20 text-center rounded-lg border border-purple-200 dark:border-purple-800/60 bg-purple-50/50 dark:bg-purple-900/20 px-1.5 py-1.5 text-xs text-purple-900 dark:text-purple-200 font-mono font-bold focus:border-purple-500 focus:outline-hidden">
                                </td>

                                <!-- Nilai Akhir (Kalkulasi Otomatis dengan Rasio Custom) -->
                                <td class="py-2 px-2 text-center bg-emerald-50/20 dark:bg-emerald-500/5 border-e border-gray-100 dark:border-gray-800">
                                    <span class="inline-block w-16 py-1 rounded text-center font-mono font-bold text-xs"
                                        :class="getBadgeClass(scores[{{ $s->id }}].akhir)"
                                        x-text="scores[{{ $s->id }}].akhir || '-'">
                                    </span>
                                </td>

                                <!-- Predikat -->
                                <td class="py-2 px-3 text-center border-e border-gray-100 dark:border-gray-800">
                                    <span class="text-[11px] font-medium"
                                        x-text="getPredikat(scores[{{ $s->id }}].akhir)">
                                        -
                                    </span>
                                </td>

                                <!-- Capaian Kompetensi / Catatan Guru -->
                                <td class="py-2 px-3">
                                    <div class="flex items-start gap-1.5">
                                        <textarea
                                            name="nilai[{{ $s->id }}][capaian]"
                                            x-model="scores[{{ $s->id }}].capaian"
                                            rows="2"
                                            placeholder="Narasi capaian kompetensi rapor..."
                                            class="w-full rounded-lg border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-800/50 px-2.5 py-1.5 text-xs text-gray-800 dark:text-white placeholder-gray-400 focus:border-brand-500 focus:outline-hidden leading-relaxed resize-y"></textarea>

                                        <button type="button"
                                            @click="generateCapaianForSiswa({{ $s->id }})"
                                            class="p-2 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-brand-600 hover:bg-brand-50 dark:hover:bg-brand-500/10 transition shrink-0 shadow-theme-xs mt-0.5"
                                            title="Generate Otomatis Capaian Siswa Ini (Sesuai Standar Rapor Resmi)">
                                            <i class="fa-solid fa-wand-magic-sparkles text-xs"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="12" class="py-8 text-center text-gray-500">
                                    Tidak ada siswa terdaftar di rombel ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Sticky Floating Save Bar -->
        <div class="fixed bottom-0 start-0 end-0 z-99 border-t border-gray-200 dark:border-gray-800 bg-white/95 dark:bg-gray-900/95 backdrop-blur-md px-6 py-3.5 shadow-theme-xl">
            <div class="mx-auto flex max-w-7xl items-center justify-between">
                <div class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                    <i class="fa-solid fa-circle-info text-brand-500 text-xs"></i>
                    <span>Nilai akhir dihitung secara real-time dengan rasio <strong class="text-gray-700 dark:text-gray-200" x-text="bobotHarian + '% Harian : ' + bobotSts + '% STS : ' + bobotSas + '% SAS'"></strong>.</span>
                </div>
                <div class="flex items-center gap-3">
                    <a href="{{ route('penilaian.index') }}" class="rounded-xl border border-gray-200 dark:border-gray-700 px-4 py-2.5 text-xs font-semibold text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                        Batal
                    </a>
                    <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-brand-500 px-6 py-2.5 text-xs font-semibold text-white shadow-theme-xs hover:bg-brand-600 transition">
                        <i class="fa-solid fa-floppy-disk text-xs"></i>
                        <span>Simpan Penilaian</span>
                    </button>
                </div>
            </div>
        </div>
    </form>

    <!-- MODAL: Tarik Nilai dari CBT Exam Web (SAS, STS, atau Asesmen Harian) -->
    <div x-show="modalSyncCbt" x-cloak class="fixed inset-0 z-999 flex items-center justify-center bg-gray-900/60 p-4 backdrop-blur-sm"
        @keydown.escape.window="modalSyncCbt = false">
        <div class="w-full max-w-lg rounded-2xl bg-white dark:bg-gray-900 shadow-theme-xl border border-gray-200 dark:border-gray-800 p-6 space-y-5 max-h-[90vh] overflow-y-auto"
            @click.outside="modalSyncCbt = false">
            <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-800 pb-3">
                <div class="flex items-center gap-2.5">
                    <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-purple-50 text-purple-600 dark:bg-purple-500/10 dark:text-purple-400">
                        <i class="fa-solid fa-cloud-arrow-down text-base"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-gray-900 dark:text-white">
                            Tarik Nilai dari CBT Exam Web
                        </h3>
                        <p class="text-[11px] text-gray-400">Pilih jenis asesmen dan tujuan kolom nilai di E-Rapor</p>
                    </div>
                </div>
                <button type="button" @click="modalSyncCbt = false" class="text-gray-400 hover:text-gray-600 text-sm">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <form action="{{ route('penilaian.sync-cbt', [$kelas->id, $mapel->id]) }}" method="POST" class="space-y-4">
                @csrf

                <!-- 1. Pilihan Target Kolom Penilaian -->
                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        Pilih Target Penilaian di E-Rapor:
                    </label>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 text-xs">
                        <!-- Target SAS -->
                        <label class="flex flex-col p-3 rounded-xl border cursor-pointer transition"
                            :class="syncTargetType === 'sas' ? 'border-purple-500 bg-purple-50/50 dark:bg-purple-950/20 text-purple-900 dark:text-purple-200' : 'border-gray-200 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-800/50 text-gray-700 dark:text-gray-300'">
                            <div class="flex items-center justify-between mb-1">
                                <span class="font-bold">SAS / UAS</span>
                                <input type="radio" name="target_type" value="sas" x-model="syncTargetType" class="text-purple-600 focus:ring-purple-500">
                            </div>
                            <span class="text-[10px] text-gray-500 dark:text-gray-400 leading-tight">Asesmen Akhir Semester</span>
                        </label>

                        <!-- Target STS -->
                        <label class="flex flex-col p-3 rounded-xl border cursor-pointer transition"
                            :class="syncTargetType === 'sts' ? 'border-amber-500 bg-amber-50/50 dark:bg-amber-950/20 text-amber-900 dark:text-amber-200' : 'border-gray-200 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-800/50 text-gray-700 dark:text-gray-300'">
                            <div class="flex items-center justify-between mb-1">
                                <span class="font-bold">STS / UTS</span>
                                <input type="radio" name="target_type" value="sts" x-model="syncTargetType" class="text-amber-600 focus:ring-amber-500">
                            </div>
                            <span class="text-[10px] text-gray-500 dark:text-gray-400 leading-tight">Sumatif Tengah Semester</span>
                        </label>

                        <!-- Target Harian -->
                        <label class="flex flex-col p-3 rounded-xl border cursor-pointer transition"
                            :class="syncTargetType === 'harian' ? 'border-blue-500 bg-blue-50/50 dark:bg-blue-950/20 text-blue-900 dark:text-blue-200' : 'border-gray-200 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-800/50 text-gray-700 dark:text-gray-300'">
                            <div class="flex items-center justify-between mb-1">
                                <span class="font-bold">Sumatif Harian</span>
                                <input type="radio" name="target_type" value="harian" x-model="syncTargetType" class="text-blue-600 focus:ring-blue-500">
                            </div>
                            <span class="text-[10px] text-gray-500 dark:text-gray-400 leading-tight">Kuis / Ulangan Harian (TP)</span>
                        </label>
                    </div>
                </div>

                <!-- 2. Opsi Pemilihan Kolom Sumatif (Jika Target Harian) -->
                <div x-show="syncTargetType === 'harian'" class="p-3 rounded-xl bg-blue-50/60 dark:bg-blue-950/20 border border-blue-200 dark:border-blue-800 space-y-2">
                    <label class="block text-xs font-semibold text-blue-900 dark:text-blue-200">
                        Masukkan ke Kolom Sumatif Ke:
                    </label>
                    <select name="sumatif_index" x-model.number="syncSumatifIndex"
                        class="w-full rounded-xl border border-blue-200 dark:border-blue-700 bg-white dark:bg-gray-800 px-3 py-2 text-xs font-semibold text-gray-800 dark:text-white focus:outline-hidden">
                        <template x-for="i in jumlahSumatif" :key="i">
                            <option :value="i" x-text="'Sumatif ' + i + (tpsList[i-1] ? ' (' + (tpsList[i-1].ringkasan_materi || tpsList[i-1].kode_tp) + ')' : '')"></option>
                        </template>
                    </select>
                    <p class="text-[10px] text-blue-700 dark:text-blue-300">
                        Nilai dari CBT akan langsung mengisi kolom sumatif terpilih dan memperbarui Rata-rata Harian & Nilai Akhir secara otomatis.
                    </p>
                </div>

                <!-- 3. Pilihan Ujian Spesifik di CBT (Optional / Auto) -->
                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1.5 flex items-center justify-between">
                        <span>Pilih Pelaksanaan Ujian di CBT:</span>
                        <span class="text-[10px] text-gray-400 font-normal">Opsional (Default: Otomatis)</span>
                    </label>

                    @if(!empty($cbtExams) && count($cbtExams) > 0)
                        <div class="space-y-1.5 max-h-48 overflow-y-auto rounded-xl border border-gray-200 dark:border-gray-800 p-2 bg-gray-50/50 dark:bg-gray-800/30">
                            <!-- Opsi Otomatis -->
                            <label class="flex items-center gap-2 p-2 rounded-lg hover:bg-white dark:hover:bg-gray-800 cursor-pointer text-xs border border-transparent transition"
                                :class="syncUjianId === '' ? 'bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700 shadow-xs' : ''">
                                <input type="radio" name="ujian_id" value="" x-model="syncUjianId" class="text-purple-600 focus:ring-purple-500">
                                <div>
                                    <div class="font-semibold text-gray-800 dark:text-white">Otomatis Deteksi Ujian Paling Relevan</div>
                                    <div class="text-[10px] text-gray-400">Sistem otomatis mencocokkan tipe ujian dan kode mapel</div>
                                </div>
                            </label>

                            @foreach($cbtExams as $exam)
                                <label class="flex items-center gap-2 p-2 rounded-lg hover:bg-white dark:hover:bg-gray-800 cursor-pointer text-xs border border-transparent transition"
                                    :class="syncUjianId == '{{ $exam['id'] }}' ? 'bg-white dark:bg-gray-800 border-purple-300 dark:border-purple-700 shadow-xs' : ''">
                                    <input type="radio" name="ujian_id" value="{{ $exam['id'] }}" x-model="syncUjianId" class="text-purple-600 focus:ring-purple-500">
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center justify-between">
                                            <span class="font-semibold text-gray-800 dark:text-white truncate">{{ $exam['nama_ujian'] }}</span>
                                            <span class="rounded px-1.5 py-0.5 text-[9px] font-mono font-bold uppercase
                                                {{ $exam['tipe_ujian'] === 'asesmen_akhir_semester' ? 'bg-purple-100 text-purple-700 dark:bg-purple-950/40 dark:text-purple-300' : ($exam['tipe_ujian'] === 'asesmen_harian' ? 'bg-blue-100 text-blue-700 dark:bg-blue-950/40 dark:text-blue-300' : 'bg-amber-100 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300') }}">
                                                {{ $exam['tipe_ujian'] }}
                                            </span>
                                        </div>
                                        <div class="flex items-center gap-2 text-[10px] text-gray-400 mt-0.5 font-mono">
                                            <span>ID: {{ $exam['exam_id'] }}</span>
                                            <span>•</span>
                                            <span>{{ $exam['tanggal'] ?: 'Tanggal belum diisi' }}</span>
                                            <span>•</span>
                                            <span class="text-emerald-600 dark:text-emerald-400 font-semibold">{{ $exam['jumlah_peserta_selesai'] }} Siswa Selesai</span>
                                        </div>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    @else
                        <div class="rounded-xl border border-dashed border-gray-200 dark:border-gray-700 p-3 text-center text-xs text-gray-400">
                            <i class="fa-solid fa-circle-info text-gray-300 mb-1 block text-sm"></i>
                            Sistem akan secara otomatis mendeteksi ujian yang cocok di database CBT untuk mata pelajaran ini.
                        </div>
                    @endif
                </div>

                <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-gray-100 dark:border-gray-800">
                    <button type="button" @click="modalSyncCbt = false" class="rounded-xl border border-gray-200 dark:border-gray-700 px-4 py-2 text-xs font-semibold text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                        Batal
                    </button>
                    <button type="submit" class="rounded-xl bg-purple-600 hover:bg-purple-700 px-5 py-2 text-xs font-semibold text-white shadow-theme-xs transition flex items-center gap-1.5">
                        <i class="fa-solid fa-cloud-arrow-down text-xs"></i>
                        <span>Mulai Tarik Nilai CBT</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
