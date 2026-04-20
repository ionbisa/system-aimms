<aside class="app-sidebar offcanvas-md offcanvas-start border-0" tabindex="-1" id="sidebarMenu" aria-labelledby="sidebarMenuLabel">
    <div class="offcanvas-header d-md-none">
        <h5 class="offcanvas-title" id="sidebarMenuLabel">Menu AIMMS</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body p-0">
        <div class="sidebar-inner pt-3">

            <ul class="nav flex-column px-2 pb-4">

            {{-- DASHBOARD --}}
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                    <i class="bi bi-speedometer2 me-2"></i> Dashboard
                </a>
            </li>

            {{-- ASSET MANAGEMENT --}}
            @role('Master Admin|Admin GA|Admin Produksi|Kepala Produksi|SPV Operasional|Manager Operasional|Manager Finance|Direktur Operasional')
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="collapse" href="#assetMenu">
                    <i class="bi bi-building me-2"></i> Asset Management
                    <i class="bi bi-chevron-down float-end"></i>
                </a>
                <div class="collapse ps-3" id="assetMenu">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="{{ url('/asset-management?category=delivery') }}"><i class="bi bi-truck me-2"></i> Delivery Cars</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="{{ url('/asset-management?category=personal') }}"><i class="bi bi-car-front me-2"></i> Personal Cars</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="{{ url('/asset-management?category=motor') }}"><i class="bi bi-bicycle me-2"></i> Motorcycles</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="{{ url('/asset-management?category=company') }}"><i class="bi bi-buildings me-2"></i> Company Assets</a>
                        </li>
                    </ul>
                </div>
            </li>
            @endrole

            {{-- PURCHASE ORDER --}}
            @role('Master Admin|Admin GA|Manager Operasional|Manager Finance|Direktur Operasional')
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('purchase-orders.*') ? 'active' : '' }}" href="{{ route('purchase-orders.index') }}">
                    <i class="bi bi-cart4 me-2"></i> Purchase Order (PO)
                </a>
            </li>
            @endrole

            {{-- ON STOCK --}}
            @role('Master Admin|Admin GA|Admin Produksi|Kepala Produksi|SPV Operasional|Manager Operasional|Manager Finance|Direktur Operasional')
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('stock.index') ? 'active' : '' }}" href="{{ route('stock.index') }}">
                    <i class="bi bi-box-seam me-2"></i> On Stock
                </a>
            </li>
            @endrole

            {{-- INBOUND FOR GA --}}
            @role('Master Admin|Admin GA')
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('stock.inbound') ? 'active' : '' }}" href="{{ route('stock.inbound') }}">
                    <i class="bi bi-arrow-down-square me-2"></i> Inbound
                </a>
            </li>
            @endrole

            {{-- OUTBOUND FOR GA --}}
            @role('Master Admin|Admin GA')
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('stock.outbound') ? 'active' : '' }}" href="{{ route('stock.outbound') }}">
                    <i class="bi bi-arrow-up-square me-2"></i> Outbound
                </a>
            </li>
            @endrole

            {{-- OUTBOUND / REQUEST SYSTEM --}}
            @role('Admin Produksi')
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('item-requests.*') ? 'active' : '' }}" href="{{ route('item-requests.index') }}">
                    <i class="bi bi-send-check me-2"></i> Outbound / Permintaan Barang
                </a>
            </li>
            @endrole

            {{-- DAFTAR PERMINTAAN BARANG --}}
            @role('Master Admin|Admin GA|Kepala Produksi|SPV Operasional|Manager Operasional|Manager Finance|Direktur Operasional')
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('item-requests.*') ? 'active' : '' }}" href="{{ route('item-requests.index') }}">
                    <i class="bi bi-card-checklist me-2"></i> Daftar Permintaan Barang
                </a>
            </li>
            @endrole

            {{-- APD --}}
            @role('Master Admin|Admin GA|Admin Produksi|Kepala Produksi|SPV Operasional|Manager Operasional|Manager Finance|Direktur Operasional')
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('employee-boots.*') || request()->routeIs('employee-uniforms.*') ? 'active' : '' }}" data-bs-toggle="collapse" href="#apdMenu" aria-expanded="{{ request()->routeIs('employee-boots.*') || request()->routeIs('employee-uniforms.*') ? 'true' : 'false' }}">
                    <i class="bi bi-shield-check me-2"></i> APD Karyawan
                    <i class="bi bi-chevron-down float-end"></i>
                </a>
                <div class="collapse ps-3 {{ request()->routeIs('employee-boots.*') || request()->routeIs('employee-uniforms.*') ? 'show' : '' }}" id="apdMenu">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('employee-boots.*') ? 'active' : '' }}" href="{{ route('employee-boots.index') }}"><i class="bi bi-headset-vr me-2"></i> Sepatu</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('employee-uniforms.*') ? 'active' : '' }}" href="{{ route('employee-uniforms.index') }}"><i class="bi bi-person-badge me-2"></i> Seragam Kerja</a>
                        </li>
                    </ul>
                </div>
            </li>
            @endrole

            {{-- REPORTING --}}
            @role('Master Admin|Admin GA|Admin Produksi|Kepala Produksi|SPV Operasional|Manager Operasional|Manager Finance|Direktur Operasional')
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('reports.*') ? 'active' : '' }}" data-bs-toggle="collapse" href="#reportMenu">
                    <i class="bi bi-clipboard-data me-2"></i> Reporting
                    <i class="bi bi-chevron-down float-end"></i>
                </a>
                <div class="collapse ps-3 {{ request()->routeIs('reports.*') ? 'show' : '' }}" id="reportMenu">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('reports.daily') ? 'active' : '' }}" href="{{ route('reports.daily') }}"><i class="bi bi-calendar-day me-2"></i> Daily Report</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('reports.weekly') ? 'active' : '' }}" href="{{ route('reports.weekly') }}"><i class="bi bi-calendar-week me-2"></i> Weekly Report</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('reports.monthly') ? 'active' : '' }}" href="{{ route('reports.monthly') }}"><i class="bi bi-calendar-month me-2"></i> Monthly Report</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('reports.yearly') ? 'active' : '' }}" href="{{ route('reports.yearly') }}"><i class="bi bi-calendar3 me-2"></i> Year Report</a>
                        </li>
                    </ul>
                </div>
            </li>
            @endrole

            {{-- USER --}}
            @role('Master Admin')
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}" href="{{ route('users.index') }}">
                    <i class="bi bi-person-bounding-box me-2"></i> Setting User
                </a>
            </li>
            @endrole

            {{-- LOGOUT --}}
            <li class="nav-item mt-4">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="btn btn-sm btn-warning w-100">
                        <i class="bi bi-box-arrow-right me-1"></i> Logout
                    </button>
                </form>
            </li>

        </ul>

        </div>
    </div>
</aside>
