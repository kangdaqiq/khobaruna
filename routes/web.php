<?php

use App\Http\Controllers\AbsensiController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CpTpController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GuruController;
use App\Http\Controllers\KkmController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\MapelController;
use App\Http\Controllers\PembagianTugasController;
use App\Http\Controllers\PenilaianController;
use App\Http\Controllers\RaporController;
use App\Http\Controllers\RaporSettingController;
use App\Http\Controllers\SiswaController;
use App\Http\Controllers\SyncController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// Locale Switch Route
Route::get('/locale/{locale}', [LocaleController::class, 'switch'])->name('locale.switch');

// Authentication Routes
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Quick Role Switcher (Simulasi 5 Role)
Route::get('/switch-role/{role}', [AuthController::class, 'switchRole'])->name('switch.role');

// Main Root & Role Dashboards
Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
Route::get('/dashboard', [DashboardController::class, 'index']);

Route::prefix('dashboard')->name('dashboard.')->group(function () {
    Route::get('/admin', [DashboardController::class, 'admin'])->name('admin');
    Route::get('/waka-kurikulum', [DashboardController::class, 'wakaKurikulum'])->name('waka-kurikulum');
    Route::get('/wali-kelas', [DashboardController::class, 'waliKelas'])->name('wali-kelas');
    Route::get('/guru', [DashboardController::class, 'guru'])->name('guru');
    Route::get('/siswa', [DashboardController::class, 'siswa'])->name('siswa');
});

// Admin Management Routes
Route::prefix('admin')->name('admin.')->group(function () {
    // Manajemen Akun (User Management)
    Route::resource('users', UserController::class);
    Route::post('users/{id}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset-password');

    // Data Guru
    Route::resource('gurus', GuruController::class);

    // Data Siswa
    Route::resource('siswas', SiswaController::class);

    // Mata Pelajaran (Mapel)
    Route::resource('mapels', MapelController::class);

    // Pembagian Tugas Mengajar (Mapping Guru Pengampu Mapel & Kelas)
    Route::get('pembagian-tugas', [PembagianTugasController::class, 'index'])->name('pembagian-tugas.index');
    Route::post('pembagian-tugas', [PembagianTugasController::class, 'store'])->name('pembagian-tugas.store');
    Route::delete('pembagian-tugas/{id}', [PembagianTugasController::class, 'destroy'])->name('pembagian-tugas.destroy');

    // Pengaturan Batas Ketercapaian / KKM Tiap Kelas
    Route::get('kkm', [KkmController::class, 'index'])->name('kkm.index');
    Route::post('kkm', [KkmController::class, 'update'])->name('kkm.update');

    // Rekapitulasi Absensi Siswa (Sinkronisasi Backend Web Absen)
    Route::get('absensi', [AbsensiController::class, 'index'])->name('absensi.index');
    Route::post('absensi/sync', [AbsensiController::class, 'sync'])->name('absensi.sync');
    Route::put('absensi/{id}', [AbsensiController::class, 'update'])->name('absensi.update');

    // Pengaturan Cetak Rapor & Tanda Tangan Digital
    Route::get('rapor-setting', [RaporSettingController::class, 'index'])->name('rapor-setting.index');
    Route::post('rapor-setting', [RaporSettingController::class, 'update'])->name('rapor-setting.update');
    Route::post('rapor-setting/walas/{guruId}', [RaporSettingController::class, 'uploadWalas'])->name('rapor-setting.upload-walas');
    Route::delete('rapor-setting/walas/{guruId}', [RaporSettingController::class, 'deleteWalasSignature'])->name('rapor-setting.delete-walas-signature');
    Route::delete('rapor-setting/remove/{type}/{id?}', [RaporSettingController::class, 'removeSignature'])->name('rapor-setting.remove-signature');

    // Pusat Sinkronisasi Data
    Route::get('sync', [SyncController::class, 'index'])->name('sync.index');
    Route::post('sync', [SyncController::class, 'run'])->name('sync.run');
    Route::post('sync-exam', [SyncController::class, 'runExamSync'])->name('sync.exam');
});

// Cetak Laporan Hasil Belajar (Rapor Siswa)
Route::prefix('rapor')->name('rapor.')->group(function () {
    Route::get('/', [RaporController::class, 'index'])->name('index');
    Route::get('/print', [RaporController::class, 'print'])->name('print');
    Route::post('/catatan/{siswaId}', [RaporController::class, 'updateCatatan'])->name('catatan');
    Route::post('/kokurikuler/{kelasId}', [RaporController::class, 'updateKokurikulerMassal'])->name('kokurikuler');
});

// Penilaian Siswa (Otorisasi: Guru Hanya Bisa Menilai Kelas & Mapel yang Diampunya)
Route::prefix('penilaian')->name('penilaian.')->group(function () {
    Route::get('/', [PenilaianController::class, 'index'])->name('index');
    Route::get('/kelas/{kelas}/mapel/{mapel}', [PenilaianController::class, 'input'])->name('input');
    Route::post('/kelas/{kelas}/mapel/{mapel}', [PenilaianController::class, 'store'])->name('store');
    Route::post('/kelas/{kelas}/mapel/{mapel}/sync-sas', [PenilaianController::class, 'syncSas'])->name('sync-sas');
    Route::post('/kelas/{kelas}/mapel/{mapel}/sync-cbt', [PenilaianController::class, 'syncCbt'])->name('sync-cbt');
    Route::get('/kelas/{kelas}/mapel/{mapel}/cbt-exams', [PenilaianController::class, 'getCbtExams'])->name('cbt-exams');
});

// Capaian Pembelajaran (CP) & Tujuan Pembelajaran (TP) Kurikulum Merdeka
Route::prefix('kurikulum/cp-tp')->name('kurikulum.cp-tp.')->group(function () {
    Route::get('/', [CpTpController::class, 'index'])->name('index');
    Route::post('/cp', [CpTpController::class, 'storeCp'])->name('cp.store');
    Route::put('/cp/{id}', [CpTpController::class, 'updateCp'])->name('cp.update');
    Route::delete('/cp/{id}', [CpTpController::class, 'destroyCp'])->name('cp.destroy');
    Route::post('/tp', [CpTpController::class, 'storeTp'])->name('tp.store');
    Route::put('/tp/{id}', [CpTpController::class, 'updateTp'])->name('tp.update');
    Route::delete('/tp/{id}', [CpTpController::class, 'destroyTp'])->name('tp.destroy');
});

// Utility & Legacy Template Pages
Route::get('/calendar', function () {
    return view('pages.calender', ['title' => 'Calendar']);
})->name('calendar');

Route::get('/profile', function () {
    return view('pages.profile', ['title' => 'Profile']);
})->name('profile');

Route::get('/form-elements', function () {
    return view('pages.form.form-elements', ['title' => 'Form Elements']);
})->name('form-elements');

Route::get('/basic-tables', function () {
    return view('pages.tables.basic-tables', ['title' => 'Basic Tables']);
})->name('basic-tables');

Route::get('/blank', function () {
    return view('pages.blank', ['title' => 'Blank']);
})->name('blank');

Route::get('/error-404', function () {
    return view('pages.errors.error-404', ['title' => 'Error 404']);
})->name('error-404');
