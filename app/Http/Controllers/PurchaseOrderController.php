<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderApproval;
use App\Models\PurchaseOrderNote;
use App\Models\PurchaseOrderNoteRead;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PurchaseOrderController extends Controller
{
    protected array $transactionTypes = [
        'Pembelian Barang',
        'Pembayaran Jasa',
        'Perbaikan Barang',
        'Pembelian BBM',
    ];

    protected array $divisions = [
        'Bakso (SMS)',
        'Basreng',
        'Kwetiau',
        'Otak-otak',
        'MDM',
        'PBB',
        'Boiler',
        'Meswin Produksi',
        'HRD',
        'Finance',
        'Marketing',
        'GA',
        'Kendaraan',
        'Produksi',
        'Operasional',
        'Lain-lain',
    ];

    protected array $creatorRoles = [
        'Admin GA',
        'Master Admin',
    ];

    protected array $viewerRoles = [
        'Master Admin',
        'Admin GA',
        'Manager Operasional',
        'Manager Finance',
        'Direktur Operasional',
    ];

    protected array $approvalStages = [
        [
            'step_order' => 1,
            'stage_key' => 'operational_manager',
            'stage_label' => 'Diketahui Oleh',
            'role_name' => 'Manager Operasional',
        ],
        [
            'step_order' => 2,
            'stage_key' => 'director_operational',
            'stage_label' => 'Disetujui Oleh',
            'role_name' => 'Direktur Operasional',
        ],
    ];

    protected function approvedMonthlyExpenseQuery(Carbon $selectedMonth)
    {
        return PurchaseOrder::query()
            ->whereYear('transaction_date', $selectedMonth->year)
            ->whereMonth('transaction_date', $selectedMonth->month)
            ->where('overall_status', 'done');
    }

    protected function effectiveTotalPriceSql(string $table = 'purchase_orders'): string
    {
        if (Schema::hasColumn($table, 'actual_total_price')) {
            return "COALESCE(actual_total_price, total_price, 0)";
        }

        return "COALESCE(total_price, 0)";
    }

    protected function normalizeQuantity(float|int|string $value): float
    {
        return round((float) $value, 2);
    }

    protected function normalizeCurrency(float|int|string $value): float
    {
        return round((float) $value, 0);
    }

    protected function calculatePurchaseOrderLineTotal(float|int|string $qty, float|int|string $unitPrice): float
    {
        return $this->normalizeCurrency(
            $this->normalizeQuantity($qty) * $this->normalizeCurrency($unitPrice)
        );
    }

    public function index(Request $request)
    {
        $this->ensureViewer($request);

        if (! $this->workflowSchemaReady()) {
            return view('purchase-orders.index', [
                'purchaseOrders' => collect(),
                'summary' => null,
                'selectedMonth' => $this->monthFilter($request->query('month'))->format('Y-m'),
                'search' => trim((string) $request->query('search')),
                'status' => trim((string) $request->query('status')),
                'transactionTypes' => $this->transactionTypes,
                'divisions' => $this->divisions,
                'schemaReady' => false,
                'canCreate' => $request->user()->hasAnyRole($this->creatorRoles),
            ]);
        }

        $selectedMonth = $this->monthFilter($request->query('month'));
        $search = trim((string) $request->query('search'));
        $status = trim((string) $request->query('status'));

        $purchaseOrders = PurchaseOrder::query()
            ->with(['requester', 'items', 'approvals'])
            ->whereYear('transaction_date', $selectedMonth->year)
            ->whereMonth('transaction_date', $selectedMonth->month)
            ->when($status !== '', fn ($query) => $this->applyStatusFilter($query, $status))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($nestedQuery) use ($search) {
                    $nestedQuery->where('po_number', 'like', '%' . $search . '%')
                        ->orWhere('transaction_type', 'like', '%' . $search . '%')
                        ->orWhere('division', 'like', '%' . $search . '%')
                        ->orWhere('category', 'like', '%' . $search . '%')
                        ->orWhere('vendor', 'like', '%' . $search . '%')
                        ->orWhere('description', 'like', '%' . $search . '%')
                        ->orWhereHas('requester', fn ($requesterQuery) => $requesterQuery->where('name', 'like', '%' . $search . '%'))
                        ->orWhereHas('items', function ($itemQuery) use ($search) {
                            $itemQuery->where('item_name', 'like', '%' . $search . '%')
                                ->orWhere('description', 'like', '%' . $search . '%');
                        });
                });
            })
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->simplePaginate(10)
            ->withQueryString();

        $summary = PurchaseOrder::query()
            ->whereYear('transaction_date', $selectedMonth->year)
            ->whereMonth('transaction_date', $selectedMonth->month)
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(CASE WHEN overall_status IN ('pending', 'expired') THEN 1 ELSE 0 END) as pending_total")
            ->selectRaw("SUM(CASE WHEN overall_status = 'approved' THEN 1 ELSE 0 END) as approved_total")
            ->selectRaw("SUM(CASE WHEN overall_status = 'rejected' THEN 1 ELSE 0 END) as rejected_total")
            ->selectRaw("SUM(CASE WHEN overall_status = 'done' THEN 1 ELSE 0 END) as done_total")
            ->first();

        $summary->monthly_total = (float) $this->approvedMonthlyExpenseQuery($selectedMonth)
            ->sum(DB::raw($this->effectiveTotalPriceSql()));

        return view('purchase-orders.index', [
            'purchaseOrders' => $purchaseOrders,
            'summary' => $summary,
            'selectedMonth' => $selectedMonth->format('Y-m'),
            'search' => $search,
            'status' => $status,
            'transactionTypes' => $this->transactionTypes,
            'divisions' => $this->divisions,
            'schemaReady' => true,
            'canCreate' => $request->user()->hasAnyRole($this->creatorRoles),
        ]);
    }

    public function create(Request $request)
    {
        $this->ensureCreator($request);

        abort_unless($this->workflowSchemaReady(), 503);

        return view('purchase-orders.create', [
            'transactionTypes' => $this->transactionTypes,
            'divisions' => $this->divisions,
        ]);
    }

    public function store(Request $request)
    {
        $this->ensureCreator($request);
        $this->abortUnlessWorkflowReady();

        $validated = $request->validate($this->storeRules());
        $transactionDate = Carbon::now();
        $user = $request->user();

        DB::transaction(function () use ($validated, $transactionDate, $user) {
            $purchaseOrder = PurchaseOrder::create([
                'po_number' => $this->generatePoNumber($transactionDate),
                'transaction_date' => $transactionDate->toDateString(),
                'transaction_type' => $validated['transaction_type'],
                'division' => $validated['division'],
                'category' => $validated['category'],
                'description' => $validated['description'],
                'vendor' => $validated['vendor'],
                'requested_by' => $user->id,
                'requested_role' => $this->resolveRequesterRole($user),
                'overall_status' => 'pending',
                'current_step' => 'waiting_operational_manager',
                'initial_note' => $validated['initial_note'] ?? null,
                'status_label' => 'Pending',
                'total_price' => 0,
                'last_action_at' => now(),
            ]);

            $grandTotal = 0;

            foreach (array_values($validated['items']) as $index => $item) {
                $normalizedQty = $this->normalizeQuantity($item['qty']);
                $normalizedUnitPrice = $this->normalizeCurrency($item['estimated_unit_price']);
                $lineTotal = $this->calculatePurchaseOrderLineTotal($normalizedQty, $normalizedUnitPrice);
                $grandTotal = $this->normalizeCurrency($grandTotal + $lineTotal);

                $purchaseOrder->items()->create([
                    'line_number' => $index + 1,
                    'item_name' => $item['item_name'],
                    'qty' => $normalizedQty,
                    'unit' => $item['unit'],
                    'estimated_unit_price' => $normalizedUnitPrice,
                    'estimated_total_price' => $lineTotal,
                    'description' => $item['description'] ?? null,
                ]);
            }

            $purchaseOrder->update([
                'total_price' => $grandTotal,
            ]);

            foreach ($this->approvalStages as $stage) {
                $purchaseOrder->approvals()->create($stage);
            }

            $this->addNote(
                $purchaseOrder,
                'system',
                'Purchase Order dibuat dan menunggu approval Manager Operasional.',
                $user->name,
                $this->resolveRequesterRole($user),
                $user->id
            );

            if (! empty($validated['initial_note'])) {
                $this->addNote(
                    $purchaseOrder,
                    'comment',
                    $validated['initial_note'],
                    $user->name,
                    $this->resolveRequesterRole($user),
                    $user->id
                );
            }
        });

        return redirect()->route('purchase-orders.index', ['month' => $transactionDate->format('Y-m')])
            ->with('success', 'Purchase Order berhasil dibuat.');
    }

    public function show(Request $request, PurchaseOrder $purchaseOrder)
    {
        $this->ensureCanViewPurchaseOrder($request, $purchaseOrder);
        $this->abortUnlessWorkflowReady();

        $purchaseOrder->load(['requester', 'realizer', 'completer', 'items', 'approvals.actor', 'notes']);
        $activeApproval = $this->activeApproval($purchaseOrder);
        $this->markSeen($request, $purchaseOrder, $activeApproval);
        $this->markNotesAsRead($request, $purchaseOrder);

        return view('purchase-orders.show', [
            'purchaseOrder' => $purchaseOrder->fresh(['requester', 'realizer', 'completer', 'items', 'approvals.actor', 'notes']),
            'activeApproval' => $activeApproval ? $activeApproval->fresh(['actor']) : null,
            'canApprove' => $activeApproval && $this->canActOnApproval($request->user(), $purchaseOrder, $activeApproval),
            'canRealize' => $this->canRealize($request->user(), $purchaseOrder),
            'canComplete' => $this->canComplete($request->user(), $purchaseOrder),
            'gaCompletionReady' => $this->gaCompletionSchemaReady(),
            'showGaCompletionHint' => $request->user()->hasAnyRole(['Admin GA', 'Master Admin']),
            'canExpire' => $this->canExpire($request->user(), $purchaseOrder),
        ]);
    }

    public function approvalAction(Request $request, PurchaseOrder $purchaseOrder, PurchaseOrderApproval $approval)
    {
        $this->ensureCanViewPurchaseOrder($request, $purchaseOrder);
        $this->abortUnlessWorkflowReady();
        abort_unless($approval->purchase_order_id === $purchaseOrder->id, 404);

        $request->validate([
            'action' => 'required|in:approve,pending,reject',
            'note' => 'required|string|max:2000',
        ]);

        abort_unless($this->canActOnApproval($request->user(), $purchaseOrder, $approval), 403);

        DB::transaction(function () use ($request, $purchaseOrder, $approval) {
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

            if ($action === 'approve' && $approval->stage_key === 'operational_manager') {
                $purchaseOrder->update([
                    'overall_status' => 'pending',
                    'current_step' => 'waiting_director',
                    'status_label' => 'Pending',
                    'last_action_at' => now(),
                ]);
            }

            if ($action === 'approve' && $approval->stage_key === 'director_operational') {
                $purchaseOrder->update([
                    'overall_status' => 'approved',
                    'current_step' => 'waiting_finance_realization',
                    'status_label' => 'Approved',
                    'final_approved_at' => now(),
                    'last_action_at' => now(),
                ]);
            }

            if ($action === 'pending') {
                $purchaseOrder->update([
                    'overall_status' => 'pending',
                    'status_label' => 'Pending',
                    'last_action_at' => now(),
                ]);
            }

            if ($action === 'reject') {
                $purchaseOrder->update([
                    'overall_status' => 'rejected',
                    'current_step' => 'rejected',
                    'status_label' => 'Rejected',
                    'rejected_at' => now(),
                    'last_action_at' => now(),
                ]);
            }

            $this->addNote(
                $purchaseOrder,
                'approval',
                $approval->role_name . ' memberikan status ' . ucfirst($action) . '. Catatan: ' . $request->input('note'),
                $request->user()->name,
                $approval->role_name,
                $request->user()->id
            );
        });

        return redirect()->route('purchase-orders.show', $purchaseOrder)
            ->with('success', 'Approval Purchase Order berhasil diperbarui.');
    }

    public function addComment(Request $request, PurchaseOrder $purchaseOrder)
    {
        $this->ensureCanViewPurchaseOrder($request, $purchaseOrder);
        $this->abortUnlessWorkflowReady();

        $validated = $request->validate([
            'note' => 'required|string|max:2000',
        ]);

        $user = $request->user();

        $this->addNote(
            $purchaseOrder,
            'comment',
            $validated['note'],
            $user->name,
            $user->roles->pluck('name')->implode(', '),
            $user->id
        );

        $purchaseOrder->update([
            'last_action_at' => now(),
        ]);

        return redirect()->route('purchase-orders.show', $purchaseOrder)
            ->with('success', 'Catatan Purchase Order berhasil ditambahkan.');
    }

    public function realize(Request $request, PurchaseOrder $purchaseOrder)
    {
        $this->ensureCanViewPurchaseOrder($request, $purchaseOrder);
        $this->abortUnlessWorkflowReady();
        abort_unless($this->canRealize($request->user(), $purchaseOrder), 403);

        $validated = $request->validate([
            'finance_action' => 'required|in:approve,pending,reject',
            'realization_status' => 'nullable|in:fund_ready,fund_disbursed',
            'note' => 'required|string|max:2000',
        ]);

        if ($validated['finance_action'] === 'approve' && empty($validated['realization_status'])) {
            return redirect()->route('purchase-orders.show', $purchaseOrder)
                ->withErrors(['realization_status' => 'Status pencairan dana wajib dipilih saat Manager Finance menyetujui.'])
                ->withInput();
        }

        DB::transaction(function () use ($request, $purchaseOrder, $validated) {
            $financeAction = $validated['finance_action'];
            $realizationStatus = match ($financeAction) {
                'approve' => $validated['realization_status'],
                'pending' => 'pending',
                'reject' => 'rejected',
            };

            $payload = [
                'overall_status' => match ($financeAction) {
                    'pending' => 'pending',
                    'reject' => 'rejected',
                    default => 'approved',
                },
                'status_label' => match ($financeAction) {
                    'pending' => 'Pending',
                    'reject' => 'Rejected',
                    default => 'Approved',
                },
                'realization_status' => $realizationStatus,
                'realization_note' => $validated['note'],
                'realized_by' => $request->user()->id,
                'realized_at' => now(),
                'completed_at' => null,
                'current_step' => match ($financeAction) {
                    'reject' => 'rejected',
                    'pending' => 'waiting_finance_realization',
                    default => 'waiting_ga_completion',
                },
                'rejected_at' => $financeAction === 'reject' ? now() : $purchaseOrder->rejected_at,
                'last_action_at' => now(),
            ];

            if ($this->gaCompletionSchemaReady()) {
                $payload['receipt_note'] = null;
                $payload['receipt_file'] = null;
                $payload['completed_by'] = null;
                $payload['ga_seen_at'] = null;
            }

            $purchaseOrder->update($payload);

            $this->addNote(
                $purchaseOrder,
                'realization',
                'Manager Finance memberikan status ' . ucfirst($financeAction) . ' dengan hasil ' . $purchaseOrder->fresh()->realization_label . '. Catatan: ' . $validated['note'],
                $request->user()->name,
                'Manager Finance',
                $request->user()->id
            );
        });

        return redirect()->route('purchase-orders.show', $purchaseOrder)
            ->with('success', 'Realisasi Purchase Order berhasil diperbarui.');
    }

    public function complete(Request $request, PurchaseOrder $purchaseOrder)
    {
        $this->ensureCanViewPurchaseOrder($request, $purchaseOrder);
        $this->abortUnlessWorkflowReady();
        abort_unless($this->canComplete($request->user(), $purchaseOrder), 403);

        $validated = $request->validate([
            'note' => 'required|string|max:2000',
            'receipt_file' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'actual_total_price' => 'required|numeric|min:0',
        ]);

        DB::transaction(function () use ($request, $purchaseOrder, $validated) {
            $receiptPath = $request->file('receipt_file')->store('purchase-orders/receipts', 'public');

            if ($purchaseOrder->receipt_file) {
                Storage::disk('public')->delete($purchaseOrder->receipt_file);
            }

            if (! $purchaseOrder->receipt_file && $purchaseOrder->photo) {
                Storage::disk('public')->delete($purchaseOrder->photo);
            }

            $payload = [
                'overall_status' => 'done',
                'status_label' => 'Done',
                'realization_status' => 'done',
                'completed_at' => now(),
                'current_step' => 'completed',
                'last_action_at' => now(),
            ];

            if (Schema::hasColumn('purchase_orders', 'actual_total_price')) {
                $payload['actual_total_price'] = $this->normalizeCurrency($validated['actual_total_price']);
            }

            if ($this->gaCompletionSchemaReady()) {
                $payload['receipt_note'] = $validated['note'];
                $payload['receipt_file'] = $receiptPath;
                $payload['completed_by'] = $request->user()->id;
            } else {
                $payload['photo'] = $receiptPath;
            }

            $purchaseOrder->update($payload);

            $this->addNote(
                $purchaseOrder,
                'completion',
                'Admin GA menyelesaikan PO dengan status Done, mengunggah bukti nota, dan mengisi nominal real Rp ' . number_format((float) ($payload['actual_total_price'] ?? $purchaseOrder->total_price), 0, ',', '.') . '. Catatan: ' . $validated['note'],
                $request->user()->name,
                'Admin GA',
                $request->user()->id
            );
        });

        return redirect()->route('purchase-orders.show', $purchaseOrder)
            ->with('success', 'Purchase Order berhasil diselesaikan oleh Admin GA.');
    }

    public function expire(Request $request, PurchaseOrder $purchaseOrder)
    {
        $this->ensureCanViewPurchaseOrder($request, $purchaseOrder);
        $this->abortUnlessWorkflowReady();
        abort_unless($this->canExpire($request->user(), $purchaseOrder), 403);

        $validated = $request->validate([
            'note' => 'required|string|max:2000',
        ]);

        $purchaseOrder->update([
            'overall_status' => 'pending',
            'status_label' => 'Pending',
            'expired_at' => now(),
            'last_action_at' => now(),
        ]);

        $this->addNote(
            $purchaseOrder,
            'system',
            'Purchase Order dikembalikan ke status pending untuk follow up ulang. Catatan: ' . $validated['note'],
            $request->user()->name,
            $request->user()->roles->pluck('name')->implode(', '),
            $request->user()->id
        );

        return redirect()->route('purchase-orders.show', $purchaseOrder)
            ->with('success', 'Status Purchase Order dikembalikan menjadi pending.');
    }

    public function export(Request $request): StreamedResponse
    {
        $this->ensureViewer($request);
        $this->abortUnlessWorkflowReady();

        $selectedMonth = $this->monthFilter($request->query('month'));
        $status = trim((string) $request->query('status'));
        $search = trim((string) $request->query('search'));

        $rows = PurchaseOrder::query()
            ->with(['requester', 'items'])
            ->whereYear('transaction_date', $selectedMonth->year)
            ->whereMonth('transaction_date', $selectedMonth->month)
            ->when($status !== '', fn ($query) => $this->applyStatusFilter($query, $status))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($nestedQuery) use ($search) {
                    $nestedQuery->where('po_number', 'like', '%' . $search . '%')
                        ->orWhere('division', 'like', '%' . $search . '%')
                        ->orWhere('transaction_type', 'like', '%' . $search . '%')
                        ->orWhere('category', 'like', '%' . $search . '%')
                        ->orWhere('vendor', 'like', '%' . $search . '%')
                        ->orWhere('description', 'like', '%' . $search . '%')
                        ->orWhereHas('items', fn ($itemQuery) => $itemQuery->where('item_name', 'like', '%' . $search . '%'));
                });
            })
            ->orderByDesc('transaction_date')
            ->get();

        $filename = 'purchase-order-' . $selectedMonth->format('Y-m') . '-' . now()->format('His') . '.csv';
        $columns = ['No', 'No PO', 'Tanggal', 'Jenis', 'Divisi', 'Kategori', 'Vendor', 'Uraian', 'Dibuat Oleh', 'Status', 'Tahap', 'Realisasi', 'Total Harga', 'Ringkasan'];

        return response()->streamDownload(function () use ($rows, $columns) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $columns);

            foreach ($rows as $index => $row) {
                fputcsv($handle, [
                    $index + 1,
                    $row->po_number,
                    optional($row->transaction_date)->format('d-m-Y'),
                    $row->transaction_type,
                    $row->division,
                    $row->category,
                    $row->vendor,
                    $row->description,
                    $row->requester?->name,
                    $row->display_status,
                    $row->current_step_label,
                    $row->realization_label,
                    number_format((float) $row->effective_total_price, 0, ',', '.'),
                    $row->items->map(fn ($item) => $item->item_name . ' (' . rtrim(rtrim(number_format((float) $item->qty, 2, '.', ''), '0'), '.') . ' ' . $item->unit . ')')->implode('; '),
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function print(Request $request, PurchaseOrder $purchaseOrder)
    {
        $this->ensureCanViewPurchaseOrder($request, $purchaseOrder);
        $this->abortUnlessWorkflowReady();

        $purchaseOrder->load(['requester', 'realizer', 'completer', 'items', 'approvals.actor']);

        return view('purchase-orders.print', compact('purchaseOrder'));
    }

    public function printMonthly(Request $request)
    {
        $this->ensureViewer($request);
        $this->abortUnlessWorkflowReady();

        $selectedMonth = $this->monthFilter($request->query('month'));
        $status = trim((string) $request->query('status'));
        $search = trim((string) $request->query('search'));

        $purchaseOrders = PurchaseOrder::query()
            ->with(['requester', 'items', 'approvals.actor', 'realizer'])
            ->whereYear('transaction_date', $selectedMonth->year)
            ->whereMonth('transaction_date', $selectedMonth->month)
            ->when($status !== '', fn ($query) => $this->applyStatusFilter($query, $status))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($nestedQuery) use ($search) {
                    $nestedQuery->where('po_number', 'like', '%' . $search . '%')
                        ->orWhere('division', 'like', '%' . $search . '%')
                        ->orWhere('transaction_type', 'like', '%' . $search . '%')
                        ->orWhere('category', 'like', '%' . $search . '%')
                        ->orWhere('vendor', 'like', '%' . $search . '%')
                        ->orWhere('description', 'like', '%' . $search . '%')
                        ->orWhereHas('items', fn ($itemQuery) => $itemQuery->where('item_name', 'like', '%' . $search . '%'));
                });
            })
            ->orderByDesc('transaction_date')
            ->get();

        return view('purchase-orders.monthly-print', [
            'purchaseOrders' => $purchaseOrders,
            'selectedMonth' => $selectedMonth,
            'status' => $status,
            'search' => $search,
        ]);
    }

    protected function storeRules(): array
    {
        return [
            'transaction_type' => 'required|string|in:' . implode(',', $this->transactionTypes),
            'division' => 'required|string|in:' . implode(',', $this->divisions),
            'category' => 'required|string|max:255',
            'description' => 'required|string|max:2000',
            'vendor' => 'required|string|max:255',
            'initial_note' => 'nullable|string|max:2000',
            'items' => 'required|array|min:1',
            'items.*.item_name' => 'required|string|max:255',
            'items.*.qty' => 'required|numeric|min:0.01',
            'items.*.unit' => 'required|string|max:50',
            'items.*.estimated_unit_price' => 'required|numeric|min:0',
            'items.*.description' => 'nullable|string|max:1000',
        ];
    }

    protected function workflowSchemaReady(): bool
    {
        if (! Schema::hasTable('purchase_orders')) {
            return false;
        }

        foreach ([
            'category',
            'description',
            'vendor',
            'requested_by',
            'requested_role',
            'overall_status',
            'current_step',
            'initial_note',
            'finance_seen_at',
            'realization_status',
            'realization_note',
            'realized_by',
            'realized_at',
            'completed_at',
            'last_action_at',
        ] as $column) {
            if (! Schema::hasColumn('purchase_orders', $column)) {
                return false;
            }
        }

        return Schema::hasTable('purchase_order_items')
            && Schema::hasTable('purchase_order_approvals')
            && Schema::hasTable('purchase_order_notes')
            && Schema::hasTable('purchase_order_note_reads');
    }

    protected function gaCompletionSchemaReady(): bool
    {
        if (! Schema::hasTable('purchase_orders')) {
            return false;
        }

        foreach ([
            'ga_seen_at',
            'receipt_note',
            'receipt_file',
            'completed_by',
        ] as $column) {
            if (! Schema::hasColumn('purchase_orders', $column)) {
                return false;
            }
        }

        return true;
    }

    protected function abortUnlessWorkflowReady(): void
    {
        abort_unless($this->workflowSchemaReady(), 503, 'Modul Purchase Order belum siap. Jalankan migrasi terbaru terlebih dahulu.');
    }

    protected function monthFilter(?string $month): Carbon
    {
        if (is_string($month) && preg_match('/^\d{4}-\d{2}$/', $month) === 1) {
            return Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        }

        return Carbon::today()->startOfMonth();
    }

    protected function generatePoNumber(Carbon $transactionDate): string
    {
        $romanMonths = [
            1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V', 6 => 'VI',
            7 => 'VII', 8 => 'VIII', 9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII',
        ];

        $suffix = '/BBP/GA/' . $romanMonths[$transactionDate->month] . '/' . $transactionDate->year;

        $lastSequence = PurchaseOrder::query()
            ->whereYear('transaction_date', $transactionDate->year)
            ->whereMonth('transaction_date', $transactionDate->month)
            ->orderByDesc('id')
            ->get(['po_number'])
            ->map(function ($purchaseOrder) use ($suffix) {
                if (preg_match('/^(\d+)' . preg_quote($suffix, '/') . '$/', (string) $purchaseOrder->po_number, $matches) === 1) {
                    return (int) $matches[1];
                }

                return 0;
            })
            ->max() ?? 0;

        return str_pad((string) ($lastSequence + 1), 3, '0', STR_PAD_LEFT) . $suffix;
    }

    protected function activeApproval(PurchaseOrder $purchaseOrder): ?PurchaseOrderApproval
    {
        $purchaseOrder->loadMissing('approvals');
        $approvedStages = $purchaseOrder->approvals->where('status', 'approved')->pluck('stage_key')->all();

        return $purchaseOrder->approvals->first(function ($approval) use ($approvedStages) {
            if ($approval->status === 'approved') {
                return false;
            }

            if ($approval->step_order === 1) {
                return true;
            }

            return in_array('operational_manager', $approvedStages, true);
        });
    }

    protected function canActOnApproval($user, PurchaseOrder $purchaseOrder, PurchaseOrderApproval $approval): bool
    {
        if ($purchaseOrder->overall_status === 'rejected' || $purchaseOrder->current_step === 'completed') {
            return false;
        }

        $activeApproval = $this->activeApproval($purchaseOrder);

        return $activeApproval?->is($approval) && ($user->hasRole($approval->role_name) || $user->hasRole('Master Admin'));
    }

    protected function canRealize($user, PurchaseOrder $purchaseOrder): bool
    {
        return ($user->hasRole('Manager Finance') || $user->hasRole('Master Admin'))
            && $purchaseOrder->current_step === 'waiting_finance_realization'
            && in_array($purchaseOrder->overall_status, ['approved', 'pending', 'expired'], true);
    }

    protected function canComplete($user, PurchaseOrder $purchaseOrder): bool
    {
        return ($user->hasRole('Admin GA') || $user->hasRole('Master Admin'))
            && $purchaseOrder->current_step === 'waiting_ga_completion'
            && in_array($purchaseOrder->overall_status, ['approved', 'pending'], true);
    }

    protected function canExpire($user, PurchaseOrder $purchaseOrder): bool
    {
        if (! ($user->hasAnyRole(['Master Admin', 'Admin GA']) || $user->id === $purchaseOrder->requested_by)) {
            return false;
        }

        return in_array($purchaseOrder->overall_status, ['pending', 'approved'], true)
            && $purchaseOrder->current_step !== 'completed';
    }

    protected function applyStatusFilter($query, string $status)
    {
        if ($status === 'pending') {
            return $query->whereIn('overall_status', ['pending', 'expired']);
        }

        if ($status === 'done') {
            return $query->where('overall_status', 'done');
        }

        return $query->where('overall_status', $status);
    }

    protected function markSeen(Request $request, PurchaseOrder $purchaseOrder, ?PurchaseOrderApproval $activeApproval): void
    {
        if ($activeApproval && $this->canActOnApproval($request->user(), $purchaseOrder, $activeApproval) && ! $activeApproval->seen_at) {
            $activeApproval->update(['seen_at' => now()]);
        }

        if ($this->canRealize($request->user(), $purchaseOrder) && ! $purchaseOrder->finance_seen_at) {
            $purchaseOrder->update(['finance_seen_at' => now()]);
        }

        if ($this->gaCompletionSchemaReady() && $this->canComplete($request->user(), $purchaseOrder) && ! $purchaseOrder->ga_seen_at) {
            $purchaseOrder->update(['ga_seen_at' => now()]);
        }
    }

    protected function markNotesAsRead(Request $request, PurchaseOrder $purchaseOrder): void
    {
        $userId = $request->user()->id;
        $purchaseOrder->loadMissing('notes');

        $noteIds = $purchaseOrder->notes
            ->filter(fn ($note) => (int) $note->user_id !== $userId)
            ->pluck('id')
            ->all();

        if ($noteIds === []) {
            return;
        }

        $existingReadIds = PurchaseOrderNoteRead::query()
            ->where('user_id', $userId)
            ->whereIn('purchase_order_note_id', $noteIds)
            ->pluck('purchase_order_note_id')
            ->all();

        $payload = collect($noteIds)
            ->reject(fn ($noteId) => in_array($noteId, $existingReadIds, true))
            ->map(fn ($noteId) => [
                'purchase_order_note_id' => $noteId,
                'user_id' => $userId,
                'read_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ])
            ->values()
            ->all();

        if ($payload !== []) {
            PurchaseOrderNoteRead::insert($payload);
        }
    }

    protected function addNote(PurchaseOrder $purchaseOrder, string $type, string $note, ?string $actorName = null, ?string $actorRole = null, ?int $userId = null): void
    {
        $purchaseOrder->notes()->create([
            'user_id' => $userId,
            'type' => $type,
            'actor_name' => $actorName,
            'actor_role' => $actorRole,
            'note' => $note,
        ]);
    }

    protected function resolveRequesterRole($user): string
    {
        return $user->roles->pluck('name')->first() ?? 'User';
    }

    protected function ensureViewer(Request $request): void
    {
        abort_unless($request->user()->hasAnyRole($this->viewerRoles), 403);
    }

    protected function ensureCreator(Request $request): void
    {
        abort_unless($request->user()->hasAnyRole($this->creatorRoles), 403);
    }

    protected function ensureCanViewPurchaseOrder(Request $request, PurchaseOrder $purchaseOrder): void
    {
        $this->ensureViewer($request);

        abort_unless(
            $request->user()->hasRole('Master Admin')
            || $request->user()->hasAnyRole($this->viewerRoles)
            || $request->user()->id === $purchaseOrder->requested_by,
            403
        );
    }
}
