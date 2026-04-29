<?php

namespace App\Http\Controllers;

use App\Models\ItemRequest;
use App\Models\ItemRequestApproval;
use App\Models\ItemRequestNoteRead;
use App\Models\Stock;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ItemRequestController extends Controller
{
    protected array $assetManagementTypes = [
        'Delivery Cars',
        'Personal Cars',
        'Motorcycles',
        'Office Assets',
        'Car',
        'Motor',
        'Office',
    ];

    protected array $creatorRoles = [
        'Admin Produksi',
        'SPV Operasional',
        'Supervisor Operasional',
    ];

    protected array $viewerRoles = [
        'Master Admin',
        'Admin GA',
        'Admin Produksi',
        'Kepala Produksi',
        'SPV Operasional',
        'Supervisor Operasional',
        'Manager Operasional',
        'Manager Finance',
        'Direktur Operasional',
    ];

    protected array $approvalStages = [
        [
            'step_order' => 1,
            'stage_key' => 'production_head',
            'stage_label' => 'Diketahui Oleh',
            'role_name' => 'Kepala Produksi',
        ],
        [
            'step_order' => 2,
            'stage_key' => 'operational_manager',
            'stage_label' => 'Disetujui Oleh',
            'role_name' => 'Manager Operasional',
        ],
    ];

    public function index(Request $request)
    {
        $this->ensureViewer($request);

        $selectedMonth = $this->monthFilter($request->query('month'));
        $status = trim((string) $request->query('status'));
        $search = trim((string) $request->query('search'));

        $itemRequests = ItemRequest::query()
            ->with(['requester', 'approvals', 'items'])
            ->whereYear('requested_at', $selectedMonth->year)
            ->whereMonth('requested_at', $selectedMonth->month)
            ->when($status !== '', function ($query) use ($status) {
                $query->where('overall_status', $status);
            })
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($nestedQuery) use ($search) {
                    $nestedQuery->where('request_number', 'like', '%' . $search . '%')
                        ->orWhere('division', 'like', '%' . $search . '%')
                        ->orWhereHas('requester', function ($requesterQuery) use ($search) {
                            $requesterQuery->where('name', 'like', '%' . $search . '%');
                        })
                        ->orWhereHas('items', function ($itemQuery) use ($search) {
                            $itemQuery->where('item_name', 'like', '%' . $search . '%')
                                ->orWhere('description', 'like', '%' . $search . '%');
                        });
                });
            })
            ->orderByDesc('requested_at')
            ->orderByDesc('id')
            ->simplePaginate(10)
            ->withQueryString();

        $summary = ItemRequest::query()
            ->whereYear('requested_at', $selectedMonth->year)
            ->whereMonth('requested_at', $selectedMonth->month)
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(CASE WHEN overall_status = 'pending' THEN 1 ELSE 0 END) as pending_total")
            ->selectRaw("SUM(CASE WHEN overall_status = 'approved' THEN 1 ELSE 0 END) as approved_total")
            ->selectRaw("SUM(CASE WHEN overall_status = 'rejected' THEN 1 ELSE 0 END) as rejected_total")
            ->selectRaw("SUM(CASE WHEN overall_status = 'expired' THEN 1 ELSE 0 END) as expired_total")
            ->first();

        return view('item-requests.index', [
            'itemRequests' => $itemRequests,
            'selectedMonth' => $selectedMonth->format('Y-m'),
            'status' => $status,
            'search' => $search,
            'summary' => $summary,
            'canCreate' => $request->user()->hasAnyRole($this->creatorRoles) || $request->user()->hasRole('Master Admin'),
        ]);
    }

    public function create(Request $request)
    {
        $this->ensureCreator($request);

        return view('item-requests.create', [
            'stocks' => $this->availableStocks(),
            'supportsStockSelection' => $this->hasItemRequestItemColumn('stock_id'),
            'supportsProcurementType' => $this->hasItemRequestItemColumn('procurement_type'),
        ]);
    }

    public function store(Request $request)
    {
        $this->ensureCreator($request);

        $rules = [
            'division' => 'required|string|max:255',
            'initial_note' => 'nullable|string|max:2000',
            'items' => 'required|array|min:1',
            'items.*.item_name' => 'required|string|max:255',
            'items.*.qty' => 'required|integer|min:1',
            'items.*.unit' => 'required|string|max:50',
            'items.*.description' => 'nullable|string|max:1000',
        ];

        if ($this->hasItemRequestItemColumn('stock_id')) {
            $rules['items.*.stock_id'] = 'nullable|exists:stocks,id';
        }

        if ($this->hasItemRequestItemColumn('procurement_type')) {
            $rules['items.*.procurement_type'] = 'required|in:stock_distribution,purchase_request';
        }

        $validated = $request->validate($rules);

        $user = $request->user();
        $requestedAt = now();

        DB::transaction(function () use ($validated, $user, $requestedAt) {
            $itemRequest = ItemRequest::create([
                'request_number' => $this->generateRequestNumber($requestedAt),
                'requested_at' => $requestedAt,
                'division' => $validated['division'],
                'requested_role' => $this->resolveRequesterRole($user),
                'overall_status' => 'pending',
                'current_step' => 'waiting_production_head',
                'initial_note' => $validated['initial_note'] ?? null,
                'requested_by' => $user->id,
                'last_action_at' => $requestedAt,
            ]);

            foreach (array_values($validated['items']) as $index => $item) {
                $payload = [
                    'line_number' => $index + 1,
                    'item_name' => $item['item_name'],
                    'qty' => $item['qty'],
                    'unit' => $item['unit'],
                    'description' => $item['description'] ?? null,
                ];

                if ($this->hasItemRequestItemColumn('stock_id')) {
                    $payload['stock_id'] = $item['stock_id'] ?? null;
                }

                if ($this->hasItemRequestItemColumn('procurement_type')) {
                    $payload['procurement_type'] = $item['procurement_type'] ?? 'stock_distribution';
                }

                $itemRequest->items()->create($payload);
            }

            foreach ($this->approvalStages as $stage) {
                $itemRequest->approvals()->create($stage);
            }

            $this->addNote(
                $itemRequest,
                'system',
                'Permintaan barang dibuat dan menunggu approval Kepala Produksi.',
                $user->name,
                $this->resolveRequesterRole($user),
                $user->id
            );

            if (! empty($validated['initial_note'])) {
                $this->addNote(
                    $itemRequest,
                    'comment',
                    $validated['initial_note'],
                    $user->name,
                    $this->resolveRequesterRole($user),
                    $user->id
                );
            }
        });

        return redirect()->route('item-requests.index', ['month' => $requestedAt->format('Y-m')])
            ->with('success', 'Permintaan barang berhasil dibuat.');
    }

    public function show(Request $request, ItemRequest $itemRequest)
    {
        $this->ensureCanViewRequest($request, $itemRequest);

        $itemRequest->load([
            'requester',
            'realizer',
            ...$this->itemRequestItemRelations(),
            'approvals.actor',
            'notes',
        ]);

        $activeApproval = $this->activeApproval($itemRequest);
        $this->markSeen($request, $itemRequest, $activeApproval);
        $this->markNotesAsRead($request, $itemRequest);

        return view('item-requests.show', [
            'itemRequest' => $itemRequest->fresh(['requester', 'realizer', ...$this->itemRequestItemRelations(), 'approvals.actor', 'notes']),
            'activeApproval' => $activeApproval ? $activeApproval->fresh(['actor']) : null,
            'canApprove' => $activeApproval && $this->canActOnApproval($request->user(), $itemRequest, $activeApproval),
            'canRealize' => $this->canRealize($request->user(), $itemRequest),
            'canExpire' => $this->canExpire($request->user(), $itemRequest),
            'stocks' => $this->availableStocks(),
            'supportsStockSelection' => $this->hasItemRequestItemColumn('stock_id'),
            'supportsProcurementType' => $this->hasItemRequestItemColumn('procurement_type'),
        ]);
    }

    public function approvalAction(Request $request, ItemRequest $itemRequest, ItemRequestApproval $approval)
    {
        $this->ensureCanViewRequest($request, $itemRequest);
        abort_unless($approval->item_request_id === $itemRequest->id, 404);

        $request->validate([
            'action' => 'required|in:approve,pending,reject',
            'note' => 'required|string|max:2000',
        ]);

        abort_unless($this->canActOnApproval($request->user(), $itemRequest, $approval), 403);

        DB::transaction(function () use ($request, $itemRequest, $approval) {
            $action = (string) $request->input('action');

            $approval->update([
                'status' => match ($action) {
                    'approve' => 'approved',
                    'reject' => 'rejected',
                    default => 'pending',
                },
                'note' => $request->input('note'),
                'acted_by' => $request->user()->id,
                'acted_at' => now(),
                'seen_at' => $approval->seen_at ?? now(),
            ]);

            if ($action === 'approve' && $approval->stage_key === 'production_head') {
                $itemRequest->update([
                    'overall_status' => 'pending',
                    'current_step' => 'waiting_operational_manager',
                    'last_action_at' => now(),
                ]);
            }

            if ($action === 'approve' && $approval->stage_key === 'operational_manager') {
                $itemRequest->update([
                    'overall_status' => 'approved',
                    'current_step' => 'waiting_ga_realization',
                    'final_approved_at' => now(),
                    'last_action_at' => now(),
                ]);
            }

            if ($action === 'pending') {
                $itemRequest->update([
                    'overall_status' => 'pending',
                    'last_action_at' => now(),
                ]);
            }

            if ($action === 'reject') {
                $itemRequest->update([
                    'overall_status' => 'rejected',
                    'current_step' => 'rejected',
                    'rejected_at' => now(),
                    'last_action_at' => now(),
                ]);
            }

            $roleName = $approval->role_name;
            $actionLabel = match ($action) {
                'approve' => 'Approve',
                'reject' => 'Reject',
                default => 'Pending',
            };

            $this->addNote(
                $itemRequest,
                'approval',
                $roleName . ' memberikan status ' . $actionLabel . '. Catatan: ' . $request->input('note'),
                $request->user()->name,
                $roleName,
                $request->user()->id
            );
        });

        return redirect()->route('item-requests.show', $itemRequest)
            ->with('success', 'Approval permintaan berhasil diperbarui.');
    }

    public function addComment(Request $request, ItemRequest $itemRequest)
    {
        $this->ensureCanViewRequest($request, $itemRequest);

        $validated = $request->validate([
            'note' => 'required|string|max:2000',
        ]);

        $user = $request->user();

        $this->addNote(
            $itemRequest,
            'comment',
            $validated['note'],
            $user->name,
            $user->roles->pluck('name')->implode(', '),
            $user->id
        );

        $itemRequest->update([
            'last_action_at' => now(),
        ]);

        return redirect()->route('item-requests.show', $itemRequest)
            ->with('success', 'Catatan berhasil ditambahkan.');
    }

    public function realize(Request $request, ItemRequest $itemRequest)
    {
        $this->ensureCanViewRequest($request, $itemRequest);
        abort_unless($this->canRealize($request->user(), $itemRequest), 403);

        $validated = $request->validate([
            'realization_status' => 'required|in:ready_for_distribution,distributed,purchase_required',
            'note' => 'required|string|max:2000',
            'items' => 'nullable|array',
            'items.*.stock_id' => 'nullable|exists:stocks,id',
        ]);

        DB::transaction(function () use ($request, $itemRequest, $validated) {
            $realizationStatus = $validated['realization_status'];
            $itemRequest->loadMissing('items');

            if ($realizationStatus === 'distributed') {
                $this->applyStockDistribution($itemRequest, $validated['items'] ?? []);
            }

            $itemRequest->update([
                'realization_status' => $realizationStatus,
                'realization_note' => $validated['note'],
                'realized_by' => $request->user()->id,
                'realized_at' => now(),
                'completed_at' => $realizationStatus === 'distributed' ? now() : null,
                'current_step' => $realizationStatus === 'distributed' ? 'completed' : 'waiting_ga_realization',
                'last_action_at' => now(),
            ]);

            $this->addNote(
                $itemRequest,
                'realization',
                'Admin GA memperbarui realisasi menjadi ' . $itemRequest->fresh()->realization_label . '. Catatan: ' . $validated['note'],
                $request->user()->name,
                'Admin GA',
                $request->user()->id
            );
        });

        return redirect()->route('item-requests.show', $itemRequest)
            ->with('success', 'Realisasi permintaan berhasil diperbarui.');
    }

    public function expire(Request $request, ItemRequest $itemRequest)
    {
        $this->ensureCanViewRequest($request, $itemRequest);
        abort_unless($this->canExpire($request->user(), $itemRequest), 403);

        $validated = $request->validate([
            'note' => 'required|string|max:2000',
        ]);

        $itemRequest->update([
            'overall_status' => 'expired',
            'current_step' => 'expired',
            'expired_at' => now(),
            'last_action_at' => now(),
        ]);

        $this->addNote(
            $itemRequest,
            'system',
            'Permintaan diubah menjadi expired. Catatan: ' . $validated['note'],
            $request->user()->name,
            $request->user()->roles->pluck('name')->implode(', '),
            $request->user()->id
        );

        return redirect()->route('item-requests.show', $itemRequest)
            ->with('success', 'Status permintaan diubah menjadi expired.');
    }

    public function export(Request $request): StreamedResponse
    {
        $this->ensureViewer($request);

        $selectedMonth = $this->monthFilter($request->query('month'));
        $status = trim((string) $request->query('status'));
        $search = trim((string) $request->query('search'));

        $rows = ItemRequest::query()
            ->with(['requester', 'items'])
            ->whereYear('requested_at', $selectedMonth->year)
            ->whereMonth('requested_at', $selectedMonth->month)
            ->when($status !== '', fn ($query) => $query->where('overall_status', $status))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($nestedQuery) use ($search) {
                    $nestedQuery->where('request_number', 'like', '%' . $search . '%')
                        ->orWhere('division', 'like', '%' . $search . '%')
                        ->orWhereHas('items', fn ($itemQuery) => $itemQuery->where('item_name', 'like', '%' . $search . '%'));
                });
            })
            ->orderByDesc('requested_at')
            ->get();

        $filename = 'laporan-permintaan-barang-' . $selectedMonth->format('Y-m') . '-' . now()->format('His') . '.csv';
        $columns = ['No', 'No Permintaan', 'Tanggal', 'Divisi', 'Dibuat Oleh', 'Status Approval', 'Tahap Proses', 'Status Realisasi', 'Total Item', 'Ringkasan Barang', 'Distribusi Selesai'];

        return response()->streamDownload(function () use ($rows, $columns) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $columns);

            foreach ($rows as $index => $row) {
                fputcsv($handle, [
                    $index + 1,
                    $row->request_number,
                    optional($row->requested_at)->format('d-m-Y H:i'),
                    $row->division,
                    $row->requester?->name,
                    $row->status_label,
                    $row->current_step_label,
                    $row->realization_label,
                    $row->items->count(),
                    $row->items->map(fn ($item) => $item->item_name . ' (' . rtrim(rtrim(number_format((float) $item->qty, 2, '.', ''), '0'), '.') . ' ' . $item->unit . ')')->implode('; '),
                    $row->completed_at?->format('d-m-Y H:i') ?? '-',
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function print(Request $request, ItemRequest $itemRequest)
    {
        $this->ensureCanViewRequest($request, $itemRequest);

        $itemRequest->load(['requester', 'realizer', ...$this->itemRequestItemRelations(), 'approvals.actor']);

        return view('item-requests.print', compact('itemRequest'));
    }

    public function printMonthly(Request $request)
    {
        $this->ensureViewer($request);

        $selectedMonth = $this->monthFilter($request->query('month'));
        $status = trim((string) $request->query('status'));
        $search = trim((string) $request->query('search'));

        $itemRequests = ItemRequest::query()
            ->with(['requester', 'realizer', ...$this->itemRequestItemRelations(), 'approvals.actor'])
            ->whereYear('requested_at', $selectedMonth->year)
            ->whereMonth('requested_at', $selectedMonth->month)
            ->when($status !== '', fn ($query) => $query->where('overall_status', $status))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($nestedQuery) use ($search) {
                    $nestedQuery->where('request_number', 'like', '%' . $search . '%')
                        ->orWhere('division', 'like', '%' . $search . '%')
                        ->orWhereHas('requester', fn ($requesterQuery) => $requesterQuery->where('name', 'like', '%' . $search . '%'))
                        ->orWhereHas('items', fn ($itemQuery) => $itemQuery->where('item_name', 'like', '%' . $search . '%'));
                });
            })
            ->orderByDesc('requested_at')
            ->get();

        return view('item-requests.monthly-print', [
            'itemRequests' => $itemRequests,
            'selectedMonth' => $selectedMonth,
            'status' => $status,
            'search' => $search,
        ]);
    }

    protected function addNote(ItemRequest $itemRequest, string $type, string $note, ?string $actorName = null, ?string $actorRole = null, ?int $userId = null): void
    {
        $itemRequest->notes()->create([
            'user_id' => $userId,
            'type' => $type,
            'actor_name' => $actorName,
            'actor_role' => $actorRole,
            'note' => $note,
        ]);
    }

    protected function activeApproval(ItemRequest $itemRequest): ?ItemRequestApproval
    {
        $itemRequest->loadMissing('approvals');

        $approvedStages = $itemRequest->approvals
            ->where('status', 'approved')
            ->pluck('stage_key')
            ->all();

        return $itemRequest->approvals->first(function ($approval) use ($approvedStages) {
            if ($approval->status === 'approved') {
                return false;
            }

            if ($approval->step_order === 1) {
                return true;
            }

            return in_array('production_head', $approvedStages, true);
        });
    }

    protected function canActOnApproval($user, ItemRequest $itemRequest, ItemRequestApproval $approval): bool
    {
        if ($itemRequest->overall_status === 'rejected' || $itemRequest->overall_status === 'expired' || $itemRequest->current_step === 'completed') {
            return false;
        }

        $activeApproval = $this->activeApproval($itemRequest);

        return $activeApproval?->is($approval) && ($user->hasRole($approval->role_name) || $user->hasRole('Master Admin'));
    }

    protected function canRealize($user, ItemRequest $itemRequest): bool
    {
        return ($user->hasRole('Admin GA') || $user->hasRole('Master Admin'))
            && $itemRequest->overall_status === 'approved';
    }

    protected function canExpire($user, ItemRequest $itemRequest): bool
    {
        if (! ($user->hasAnyRole(['Master Admin', 'Admin GA']) || $user->id === $itemRequest->requested_by)) {
            return false;
        }

        return in_array($itemRequest->overall_status, ['pending', 'approved'], true)
            && $itemRequest->current_step !== 'completed';
    }

    protected function markSeen(Request $request, ItemRequest $itemRequest, ?ItemRequestApproval $activeApproval): void
    {
        if ($activeApproval && $this->canActOnApproval($request->user(), $itemRequest, $activeApproval) && ! $activeApproval->seen_at) {
            $activeApproval->update(['seen_at' => now()]);
        }

        if ($this->canRealize($request->user(), $itemRequest) && ! $itemRequest->ga_seen_at) {
            $itemRequest->update(['ga_seen_at' => now()]);
        }
    }

    protected function markNotesAsRead(Request $request, ItemRequest $itemRequest): void
    {
        if (! Schema::hasTable('item_request_note_reads')) {
            return;
        }

        $userId = $request->user()->id;
        $itemRequest->loadMissing('notes');

        $noteIds = $itemRequest->notes
            ->filter(fn ($note) => (int) $note->user_id !== $userId)
            ->pluck('id')
            ->all();

        if ($noteIds === []) {
            return;
        }

        $existingReadIds = ItemRequestNoteRead::query()
            ->where('user_id', $userId)
            ->whereIn('item_request_note_id', $noteIds)
            ->pluck('item_request_note_id')
            ->all();

        $payload = collect($noteIds)
            ->reject(fn ($noteId) => in_array($noteId, $existingReadIds, true))
            ->map(fn ($noteId) => [
                'item_request_note_id' => $noteId,
                'user_id' => $userId,
                'read_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ])
            ->values()
            ->all();

        if ($payload !== []) {
            ItemRequestNoteRead::insert($payload);
        }
    }

    protected function monthFilter(?string $month): Carbon
    {
        if (is_string($month) && preg_match('/^\d{4}-\d{2}$/', $month) === 1) {
            return Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        }

        return Carbon::today()->startOfMonth();
    }

    protected function generateRequestNumber(Carbon $requestedAt): string
    {
        $prefix = 'PRM/' . $requestedAt->format('Ym');

        $lastSequence = ItemRequest::query()
            ->whereYear('requested_at', $requestedAt->year)
            ->whereMonth('requested_at', $requestedAt->month)
            ->orderByDesc('id')
            ->get(['request_number'])
            ->map(function ($itemRequest) use ($prefix) {
                if (preg_match('/^' . preg_quote($prefix, '/') . '\/(\d{3})$/', $itemRequest->request_number, $matches) === 1) {
                    return (int) $matches[1];
                }

                return 0;
            })
            ->max() ?? 0;

        return $prefix . '/' . str_pad((string) ($lastSequence + 1), 3, '0', STR_PAD_LEFT);
    }

    protected function resolveRequesterRole($user): string
    {
        foreach ($this->creatorRoles as $role) {
            if ($user->hasRole($role)) {
                return $role;
            }
        }

        return $user->roles->pluck('name')->first() ?? 'User';
    }

    protected function ensureViewer(Request $request): void
    {
        abort_unless($request->user()->hasAnyRole($this->viewerRoles), 403);
    }

    protected function ensureCreator(Request $request): void
    {
        abort_unless($request->user()->hasAnyRole(array_merge($this->creatorRoles, ['Master Admin'])), 403);
    }

    protected function ensureCanViewRequest(Request $request, ItemRequest $itemRequest): void
    {
        $this->ensureViewer($request);
        abort_unless($request->user()->hasAnyRole($this->viewerRoles), 403);
        abort_unless(
            $request->user()->hasRole('Master Admin')
            || $request->user()->hasAnyRole($this->viewerRoles)
            || $request->user()->id === $itemRequest->requested_by,
            403
        );
    }

    protected function availableStocks()
    {
        if (! Schema::hasTable('stocks')) {
            return collect();
        }

        return Stock::query()
            ->where(function ($query) {
                $query->whereNull('asset_id')
                    ->orWhereDoesntHave('asset')
                    ->orWhereHas('asset', function ($assetQuery) {
                        $assetQuery->whereNotIn('type', $this->assetManagementTypes);
                    });
            })
            ->whereNotNull('item_code')
            ->where('item_code', 'like', 'BRG-%')
            ->orderBy('item_name')
            ->get();
    }

    protected function hasItemRequestItemColumn(string $column): bool
    {
        return Schema::hasTable('item_request_items') && Schema::hasColumn('item_request_items', $column);
    }

    protected function itemRequestItemRelations(): array
    {
        return $this->hasItemRequestItemColumn('stock_id')
            ? ['items.stock']
            : ['items'];
    }

    protected function applyStockDistribution(ItemRequest $itemRequest, array $itemStockSelections): void
    {
        if ($itemRequest->stock_deducted_at) {
            return;
        }

        $selectionMap = collect($itemStockSelections)
            ->mapWithKeys(fn ($item) => [(int) ($item['id'] ?? 0) => (int) ($item['stock_id'] ?? 0)]);

        foreach ($itemRequest->items as $requestItem) {
            if (($requestItem->procurement_type ?? 'stock_distribution') === 'purchase_request') {
                $requestItem->update([
                    'distributed_qty' => null,
                ]);

                continue;
            }

            $stockId = $selectionMap[(int) $requestItem->id] ?? (int) ($requestItem->stock_id ?? 0);

            abort_if($stockId <= 0, 422, 'Semua item harus dipetakan ke stok terlebih dahulu sebelum distribusi.');

            $stock = Stock::query()->lockForUpdate()->find($stockId);
            abort_if(! $stock, 422, 'Barang stok untuk distribusi tidak ditemukan.');
            abort_if((float) $stock->qty < (float) $requestItem->qty, 422, 'Qty stok ' . $stock->item_name . ' tidak mencukupi untuk distribusi.');

            $requestItem->update([
                'stock_id' => $stock->id,
                'distributed_qty' => $requestItem->qty,
            ]);

            $stock->decrement('qty', (int) $requestItem->qty);

            $payload = [
                'item_name' => $stock->item_name,
                'qty' => $requestItem->qty,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if (Schema::hasColumn('stock_outbounds', 'unit')) {
                $payload['unit'] = $stock->unit;
            }

            if (Schema::hasColumn('stock_outbounds', 'description')) {
                $payload['description'] = 'Distribusi permintaan barang ' . $itemRequest->request_number . ' untuk divisi ' . $itemRequest->division . '. Keterangan: ' . ($requestItem->description ?: '-');
            }

            DB::table('stock_outbounds')->insert($payload);
        }

        $itemRequest->update([
            'stock_deducted_at' => now(),
        ]);
    }
}
