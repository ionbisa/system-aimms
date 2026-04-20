<nav class="app-header navbar navbar-expand-lg navbar-dark px-3 px-lg-4">
    <div class="container-fluid">
        <div class="d-flex align-items-center gap-2">
            <button
                class="btn btn-outline-light d-md-none"
                type="button"
                data-bs-toggle="offcanvas"
                data-bs-target="#sidebarMenu"
                aria-controls="sidebarMenu"
                aria-label="Buka menu samping"
            >
                <i class="bi bi-list fs-5"></i>
            </button>

            {{-- LOGO --}}
            <a class="navbar-brand fw-bold text-white d-flex align-items-center gap-2 mb-0" href="{{ route('dashboard') }}">
                <img
                src="{{ asset('assets/img/logo.png') }}"
                alt="Bangga Group"
                height="32"
                class="d-inline-block align-middle"
            >
                <span class="brand-text">AIMMS <span class="text-warning">| Bangga Group</span></span>
            </a>
        </div>

        {{-- TOGGLER --}}
        <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse"
            data-bs-target="#navbarAIMMS">
            <span class="navbar-toggler-icon"></span>
        </button>

        {{-- RIGHT MENU --}}
        <div class="collapse navbar-collapse justify-content-end" id="navbarAIMMS">
            <ul class="navbar-nav align-items-lg-center gap-lg-3 ms-auto">

                {{-- NOTIFICATION --}}
                <li class="nav-item">
                    <a class="nav-link text-warning position-relative d-inline-flex align-items-center"
                       href="#"
                       role="button"
                       data-bs-toggle="dropdown"
                       aria-expanded="false">
                        <i class="bi {{ ($headerNotificationCount ?? 0) > 0 ? 'bi-bell-fill' : 'bi-bell' }} fs-5" id="headerNotificationBellIcon"></i>
                        @if(($headerNotificationCount ?? 0) > 0)
                        <span id="headerNotificationBadge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                            {{ $headerNotificationCount }}
                        </span>
                        @endif
                    </a>
                    <div id="headerNotificationDropdown" class="dropdown-menu dropdown-menu-end shadow border-0 p-0 overflow-hidden" style="min-width: 340px;">
                        <div class="px-3 py-3 bg-light border-bottom">
                            <div class="fw-bold text-dark">Notifikasi</div>
                            <div id="headerNotificationSummary" class="small text-muted">
                                {{ ($headerNotificationCount ?? 0) > 0 ? $headerNotificationCount . ' notifikasi belum dibuka' : 'Tidak ada notifikasi baru' }}
                            </div>
                        </div>

                        @php
                            $notificationGroups = $headerNotificationGroups ?? collect();
                            $hasAnyNotification = $notificationGroups->contains(fn ($group) => $group['items']->isNotEmpty());
                        @endphp

                        @if($hasAnyNotification)
                        @foreach($notificationGroups as $group)
                        @if($group['items']->isNotEmpty())
                        <div class="px-3 py-2 bg-white border-bottom notification-group-header" data-notification-group="{{ $group['key'] }}">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="fw-semibold text-dark">
                                    <i class="bi {{ $group['icon'] }} me-2"></i>{{ $group['label'] }}
                                </div>
                                <span class="badge bg-secondary notification-group-count">{{ $group['items']->count() }}</span>
                            </div>
                        </div>

                        @foreach($group['items']->take(4) as $notification)
                        <a
                            class="dropdown-item py-3 border-bottom notification-item"
                            href="{{ $notification['route'] }}"
                            data-notification-group="{{ $group['key'] }}"
                        >
                            <div class="fw-semibold text-dark">{{ $notification['title'] }}</div>
                            <div class="small text-muted">{{ $notification['message'] }}</div>
                            <div class="small text-secondary mt-1">{{ $notification['time'] ?: '-' }}</div>
                        </a>
                        @endforeach
                        @endif
                        @endforeach
                        @else
                        <div class="px-3 py-3 small text-muted">
                            Belum ada notifikasi atau pesan masuk yang perlu ditindaklanjuti.
                        </div>
                        @endif
                    </div>
                </li>

                {{-- USER MENU --}}
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle text-warning fw-semibold px-0 px-lg-2 d-flex align-items-center gap-2"
                       href="#"
                       role="button"
                       data-bs-toggle="dropdown"
                       aria-expanded="false">
                        @if(auth()->user()->profile_photo_url)
                        <img
                            src="{{ auth()->user()->profile_photo_url }}"
                            alt="{{ auth()->user()->name }}"
                            class="rounded-circle border border-warning"
                            width="34"
                            height="34"
                            style="object-fit: cover;"
                        >
                        @else
                        <span class="rounded-circle d-inline-flex align-items-center justify-content-center border border-warning text-warning bg-white bg-opacity-10"
                              style="width: 34px; height: 34px;">
                            <i class="bi bi-person-fill"></i>
                        </span>
                        @endif
                        <span>{{ auth()->user()->name }}</span>
                    </a>
                    <div class="dropdown-menu dropdown-menu-end shadow border-0 p-0 overflow-hidden" style="min-width: 280px;">
                        <div class="px-3 py-3 bg-light border-bottom">
                            <div class="d-flex align-items-center gap-3">
                                @if(auth()->user()->profile_photo_url)
                                <img
                                    src="{{ auth()->user()->profile_photo_url }}"
                                    alt="{{ auth()->user()->name }}"
                                    class="rounded-circle border"
                                    width="52"
                                    height="52"
                                    style="object-fit: cover;"
                                >
                                @else
                                <span class="rounded-circle d-inline-flex align-items-center justify-content-center border bg-white text-secondary"
                                      style="width: 52px; height: 52px;">
                                    <i class="bi bi-person-fill fs-4"></i>
                                </span>
                                @endif
                                <div>
                                    <div class="fw-bold text-dark">{{ auth()->user()->name }}</div>
                                    <div class="small text-muted">{{ auth()->user()->email }}</div>
                                    <div class="small text-primary fw-semibold">
                                        {{ auth()->user()->roles->pluck('name')->implode(', ') ?: 'Belum ada role' }}
                                    </div>
                                </div>
                            </div>
                        </div>
                        <a class="dropdown-item py-2" href="{{ route('profile.edit') }}">
                            <i class="bi bi-person-circle me-2"></i> Profil Saya
                        </a>
                        <a class="dropdown-item py-2" href="{{ route('profile.edit') }}">
                            <i class="bi bi-image me-2"></i> Edit Foto Akun
                        </a>
                    </div>
                </li>

                {{-- LOGOUT --}}
                <li class="nav-item">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-warning fw-bold px-3">
                            Logout
                        </button>
                    </form>
                </li>

            </ul>
        </div>

    </div>
</nav>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const dropdown = document.getElementById('headerNotificationDropdown');
        const badge = document.getElementById('headerNotificationBadge');
        const summary = document.getElementById('headerNotificationSummary');
        const bellIcon = document.getElementById('headerNotificationBellIcon');
        const notificationCount = Number(@json($headerNotificationCount ?? 0));
        const notificationStorageKey = 'aimms_header_notification_count_' + @json((string) (auth()->id() ?? 'guest'));

        if (!dropdown || !summary || !bellIcon) {
            return;
        }

        const playNotificationSound = () => {
            try {
                const AudioContextClass = window.AudioContext || window.webkitAudioContext;

                if (!AudioContextClass) {
                    return;
                }

                const audioContext = new AudioContextClass();
                const oscillator = audioContext.createOscillator();
                const gainNode = audioContext.createGain();

                oscillator.type = 'sine';
                oscillator.frequency.setValueAtTime(880, audioContext.currentTime);
                oscillator.frequency.exponentialRampToValueAtTime(660, audioContext.currentTime + 0.14);

                gainNode.gain.setValueAtTime(0.0001, audioContext.currentTime);
                gainNode.gain.exponentialRampToValueAtTime(0.035, audioContext.currentTime + 0.02);
                gainNode.gain.exponentialRampToValueAtTime(0.0001, audioContext.currentTime + 0.18);

                oscillator.connect(gainNode);
                gainNode.connect(audioContext.destination);

                oscillator.start(audioContext.currentTime);
                oscillator.stop(audioContext.currentTime + 0.18);

                oscillator.addEventListener('ended', function () {
                    audioContext.close().catch(function () {});
                });
            } catch (error) {
                // Abaikan jika browser memblokir audio otomatis.
            }
        };

        const triggerNotificationAlert = () => {
            bellIcon.classList.add('notification-bell-alert');

            if (badge) {
                badge.classList.add('notification-badge-alert');
            }

            window.setTimeout(function () {
                bellIcon.classList.remove('notification-bell-alert');

                if (badge) {
                    badge.classList.remove('notification-badge-alert');
                }
            }, 4500);

            playNotificationSound();
        };

        const updateSummary = () => {
            const items = dropdown.querySelectorAll('.notification-item');
            const total = items.length;

            if (badge) {
                if (total > 0) {
                    badge.textContent = total;
                    badge.classList.remove('d-none');
                } else {
                    badge.classList.add('d-none');
                }
            }

            bellIcon.classList.toggle('bi-bell-fill', total > 0);
            bellIcon.classList.toggle('bi-bell', total === 0);

            summary.textContent = total > 0
                ? total + ' notifikasi belum dibuka'
                : 'Tidak ada notifikasi baru';

            const emptyState = dropdown.querySelector('.notification-empty-state');

            if (total === 0) {
                if (!emptyState) {
                    const empty = document.createElement('div');
                    empty.className = 'px-3 py-3 small text-muted notification-empty-state';
                    empty.textContent = 'Belum ada notifikasi atau pesan masuk yang perlu ditindaklanjuti.';
                    dropdown.appendChild(empty);
                }
            } else if (emptyState) {
                emptyState.remove();
            }
        };

        const updateGroup = (groupKey) => {
            const groupItems = dropdown.querySelectorAll('.notification-item[data-notification-group="' + groupKey + '"]');
            const groupHeader = dropdown.querySelector('.notification-group-header[data-notification-group="' + groupKey + '"]');

            if (!groupHeader) {
                return;
            }

            const countBadge = groupHeader.querySelector('.notification-group-count');
            const count = groupItems.length;

            if (countBadge) {
                countBadge.textContent = count;
            }

            if (count === 0) {
                groupHeader.remove();
            }
        };

        dropdown.querySelectorAll('.notification-item').forEach((item) => {
            item.addEventListener('click', function () {
                const groupKey = item.dataset.notificationGroup;
                item.remove();
                updateGroup(groupKey);
                updateSummary();
                window.localStorage.setItem(notificationStorageKey, String(dropdown.querySelectorAll('.notification-item').length));
            });
        });

        const previousCount = Number(window.localStorage.getItem(notificationStorageKey) || 0);

        if (notificationCount > previousCount && previousCount > 0) {
            triggerNotificationAlert();
        }

        if (notificationCount > 0 && previousCount === 0) {
            triggerNotificationAlert();
        }

        window.localStorage.setItem(notificationStorageKey, String(notificationCount));
    });
</script>
