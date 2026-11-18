@php
    $currentRole = auth()->user()?->role ?? 'admin';
    $roles = [
        'admin' => ['label' => 'Super Admin', 'color' => 'bg-brand-500 text-white', 'icon' => 'fa-solid fa-shield-halved'],
        'waka_kurikulum' => ['label' => 'Waka Kurikulum', 'color' => 'bg-indigo-600 text-white', 'icon' => 'fa-solid fa-book-bookmark'],
        'wali_kelas' => ['label' => 'Wali Kelas', 'color' => 'bg-emerald-600 text-white', 'icon' => 'fa-solid fa-chalkboard-user'],
        'guru' => ['label' => 'Guru Mapel', 'color' => 'bg-amber-600 text-white', 'icon' => 'fa-solid fa-pen-to-square'],
        'siswa' => ['label' => 'Siswa', 'color' => 'bg-blue-600 text-white', 'icon' => 'fa-solid fa-graduation-cap'],
    ];
@endphp

<div class="mb-6 rounded-2xl bg-gradient-to-r from-gray-900 via-gray-800 to-gray-900 p-5 text-white shadow-theme-md dark:border dark:border-gray-800">
    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div class="flex items-center gap-3">
            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-white/10 text-xl backdrop-blur-sm text-white">
                <i class="{{ $roles[$currentRole]['icon'] ?? 'fa-solid fa-user' }}"></i>
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <span class="text-xs uppercase tracking-wider text-gray-400">Mode Role Aktif:</span>
                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $roles[$currentRole]['color'] }}">
                        {{ $roles[$currentRole]['label'] ?? ucfirst($currentRole) }}
                    </span>
                </div>
                <h2 class="text-lg font-bold text-white">Halo, {{ auth()->user()->name ?? 'Pengguna' }}!</h2>
            </div>
        </div>

        <!-- Quick Switcher Buttons -->
        <div class="flex flex-wrap items-center gap-1.5">
            <span class="text-xs text-gray-400 ltr:mr-2 rtl:ml-2">Uji Tampilan Role:</span>
            @foreach ($roles as $rKey => $rMeta)
                <a href="{{ route('switch.role', $rKey) }}"
                   class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1 text-xs font-medium transition {{ $currentRole === $rKey ? 'bg-white text-gray-900 font-semibold shadow' : 'bg-white/10 text-gray-200 hover:bg-white/20' }}">
                    <i class="{{ $rMeta['icon'] }} text-[11px]"></i>
                    <span>{{ $rMeta['label'] }}</span>
                </a>
            @endforeach
        </div>
    </div>
</div>

