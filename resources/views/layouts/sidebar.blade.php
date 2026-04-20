<aside class="sidebar bg-primary text-white">
    <div class="p-3">
        <h5 class="text-white fw-bold">
            AIMMS
            <span class="text-warning">Bangga Group</span>
        </h5>
        <hr class="border-light">

        <ul class="nav flex-column">

@role('Master Admin')
<li class="nav-item"><a href="/dashboard" class="nav-link">Dashboard</a></li>
<li class="nav-item"><a href="{{ url('/asset-management') }}" class="nav-link">Asset</a></li>
<li class="nav-item"><a href="/inventory" class="nav-link">Inventory</a>
        
</li>
<li class="nav-item"><a href="/maintenance" class="nav-link">Maintenance</a></li>
<li class="nav-item"><a href="/audit-log" class="nav-link">Audit Log</a></li>
@endrole

@role('Admin GA')
<li class="nav-item"><a href="{{ url('/asset-management') }}" class="nav-link">Asset</a></li>
<li class="nav-item"><a href="/inventory" class="nav-link">Inventory</a></li>
@endrole

@role('Supervisor Operasional')
<li class="nav-item"><a href="/maintenance" class="nav-link">Maintenance</a></li>
@endrole

</ul>

        
    </div>
</aside>
