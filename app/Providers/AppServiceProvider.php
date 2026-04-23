<?php

namespace App\Providers;

use App\Models\ItemRequest;
use App\Models\ItemRequestApproval;
use App\Models\ItemRequestNote;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderApproval;
use App\Models\PurchaseOrderNote;
use App\Models\User;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('layouts.partials.header', function ($view) {
            $approvalNotifications = collect();
            $realizationNotifications = collect();
            $noteNotifications = collect();

            if (! Auth::check() || ! Schema::hasTable('item_requests') || ! Schema::hasTable('item_request_approvals')) {
                $view->with('headerNotifications', collect());
                $view->with('headerNotificationGroups', collect());
                $view->with('headerNotificationCount', 0);

                return;
            }

            /** @var User $user */
            $user = Auth::user();

            if ($user->hasAnyRole(['Kepala Produksi', 'Master Admin'])) {
                $productionHeadNotifications = ItemRequestApproval::query()
                    ->with('itemRequest')
                    ->where('role_name', 'Kepala Produksi')
                    ->where('status', 'pending')
                    ->whereNull('seen_at')
                    ->whereHas('itemRequest', function ($query) {
                        $query->where('current_step', 'waiting_production_head')
                            ->where('overall_status', 'pending');
                    })
                    ->get()
                    ->map(function ($approval) {
                        return [
                            'key' => 'approval-' . $approval->id,
                            'category' => 'Approval',
                            'title' => 'Approval Kepala Produksi',
                            'message' => 'Permintaan ' . $approval->itemRequest?->request_number . ' menunggu dibuka.',
                            'route' => route('item-requests.show', $approval->itemRequest),
                            'time' => optional($approval->itemRequest?->requested_at)->format('d-m-Y H:i'),
                        ];
                    });

                $approvalNotifications = $approvalNotifications->concat($productionHeadNotifications);
            }

            if ($user->hasAnyRole(['Manager Operasional', 'Master Admin'])) {
                $managerNotifications = ItemRequestApproval::query()
                    ->with('itemRequest')
                    ->where('role_name', 'Manager Operasional')
                    ->where('status', 'pending')
                    ->whereNull('seen_at')
                    ->whereHas('itemRequest', function ($query) {
                        $query->where('current_step', 'waiting_operational_manager')
                            ->where('overall_status', 'pending');
                    })
                    ->get()
                    ->map(function ($approval) {
                        return [
                            'key' => 'approval-' . $approval->id,
                            'category' => 'Approval',
                            'title' => 'Approval Manager Operasional',
                            'message' => 'Permintaan ' . $approval->itemRequest?->request_number . ' menunggu dibuka.',
                            'route' => route('item-requests.show', $approval->itemRequest),
                            'time' => optional($approval->itemRequest?->requested_at)->format('d-m-Y H:i'),
                        ];
                    });

                $approvalNotifications = $approvalNotifications->concat($managerNotifications);

                if (Schema::hasTable('purchase_orders') && Schema::hasTable('purchase_order_approvals')) {
                    $poManagerNotifications = PurchaseOrderApproval::query()
                        ->with('purchaseOrder')
                        ->where('role_name', 'Manager Operasional')
                        ->where('status', 'pending')
                        ->whereNull('seen_at')
                        ->whereHas('purchaseOrder', function ($query) {
                            $query->where('current_step', 'waiting_operational_manager')
                                ->where('overall_status', 'pending');
                        })
                        ->get()
                        ->map(function ($approval) {
                            return [
                                'key' => 'po-approval-' . $approval->id,
                                'category' => 'Approval',
                                'title' => 'Approval PO Manager Operasional',
                                'message' => 'PO ' . $approval->purchaseOrder?->po_number . ' menunggu dibuka.',
                                'route' => route('purchase-orders.show', $approval->purchaseOrder),
                                'time' => optional($approval->purchaseOrder?->transaction_date)->format('d-m-Y'),
                            ];
                        });

                    $approvalNotifications = $approvalNotifications->concat($poManagerNotifications);
                }
            }

            if ($user->hasAnyRole(['Direktur Operasional', 'Master Admin']) && Schema::hasTable('purchase_orders') && Schema::hasTable('purchase_order_approvals')) {
                $directorNotifications = PurchaseOrderApproval::query()
                    ->with('purchaseOrder')
                    ->where('role_name', 'Direktur Operasional')
                    ->where('status', 'pending')
                    ->whereNull('seen_at')
                    ->whereHas('purchaseOrder', function ($query) {
                        $query->where('current_step', 'waiting_director')
                            ->where('overall_status', 'pending');
                    })
                    ->get()
                    ->map(function ($approval) {
                        return [
                            'key' => 'po-approval-' . $approval->id,
                            'category' => 'Approval',
                            'title' => 'Approval PO Direktur Operasional',
                            'message' => 'PO ' . $approval->purchaseOrder?->po_number . ' menunggu dibuka.',
                            'route' => route('purchase-orders.show', $approval->purchaseOrder),
                            'time' => optional($approval->purchaseOrder?->transaction_date)->format('d-m-Y'),
                        ];
                    });

                $approvalNotifications = $approvalNotifications->concat($directorNotifications);
            }

            if ($user->hasAnyRole(['Admin GA', 'Master Admin'])) {
                $gaNotifications = ItemRequest::query()
                    ->where('overall_status', 'approved')
                    ->where('current_step', 'waiting_ga_realization')
                    ->whereNull('ga_seen_at')
                    ->get()
                    ->map(function ($itemRequest) {
                        return [
                            'key' => 'ga-' . $itemRequest->id,
                            'category' => 'Realisasi',
                            'title' => 'Realisasi Admin GA',
                            'message' => 'Permintaan ' . $itemRequest->request_number . ' siap diproses untuk distribusi.',
                            'route' => route('item-requests.show', $itemRequest),
                            'time' => optional($itemRequest->final_approved_at ?? $itemRequest->requested_at)->format('d-m-Y H:i'),
                        ];
                    });

                $realizationNotifications = $realizationNotifications->concat($gaNotifications);

                if (
                    Schema::hasTable('purchase_orders')
                    && Schema::hasColumn('purchase_orders', 'ga_seen_at')
                ) {
                    $poGaNotifications = PurchaseOrder::query()
                        ->whereIn('overall_status', ['approved', 'pending'])
                        ->where('current_step', 'waiting_ga_completion')
                        ->whereNull('ga_seen_at')
                        ->get()
                        ->map(function ($purchaseOrder) {
                            return [
                                'key' => 'po-ga-' . $purchaseOrder->id,
                                'category' => 'Realisasi',
                                'title' => 'Penyelesaian PO Admin GA',
                                'message' => 'PO ' . $purchaseOrder->po_number . ' menunggu upload bukti nota dan status Done.',
                                'route' => route('purchase-orders.show', $purchaseOrder),
                                'time' => optional($purchaseOrder->realized_at ?? $purchaseOrder->final_approved_at)->format('d-m-Y H:i'),
                            ];
                        });

                    $realizationNotifications = $realizationNotifications->concat($poGaNotifications);
                }
            }

            if ($user->hasAnyRole(['Manager Finance', 'Master Admin']) && Schema::hasTable('purchase_orders')) {
                $financeNotifications = PurchaseOrder::query()
                    ->where('overall_status', 'approved')
                    ->where('current_step', 'waiting_finance_realization')
                    ->whereNull('finance_seen_at')
                    ->get()
                    ->map(function ($purchaseOrder) {
                        return [
                            'key' => 'po-finance-' . $purchaseOrder->id,
                            'category' => 'Realisasi',
                            'title' => 'Realisasi PO Manager Finance',
                            'message' => 'PO ' . $purchaseOrder->po_number . ' siap diproses untuk pengeluaran dana.',
                            'route' => route('purchase-orders.show', $purchaseOrder),
                            'time' => optional($purchaseOrder->final_approved_at ?? $purchaseOrder->created_at)->format('d-m-Y H:i'),
                        ];
                    });

                $realizationNotifications = $realizationNotifications->concat($financeNotifications);
            }

            if (Schema::hasTable('item_request_notes') && Schema::hasTable('item_request_note_reads')) {
                $noteNotifications = ItemRequestNote::query()
                    ->with('itemRequest')
                    ->where(function ($query) use ($user) {
                        $query->whereNull('user_id')
                            ->orWhere('user_id', '!=', $user->id);
                    })
                    ->whereNotExists(function ($query) use ($user) {
                        $query->select(DB::raw(1))
                            ->from('item_request_note_reads')
                            ->whereColumn('item_request_note_reads.item_request_note_id', 'item_request_notes.id')
                            ->where('item_request_note_reads.user_id', $user->id);
                    })
                    ->whereHas('itemRequest', function ($query) use ($user) {
                        $query->where(function ($accessQuery) use ($user) {
                            $accessQuery->where('requested_by', $user->id);

                            if ($user->hasRole('Kepala Produksi') || $user->hasRole('Master Admin')) {
                                $accessQuery->orWhereHas('approvals', fn ($approvalQuery) => $approvalQuery->where('role_name', 'Kepala Produksi'));
                            }

                            if ($user->hasRole('Manager Operasional') || $user->hasRole('Master Admin')) {
                                $accessQuery->orWhereHas('approvals', fn ($approvalQuery) => $approvalQuery->where('role_name', 'Manager Operasional'));
                            }

                            if ($user->hasRole('Admin GA') || $user->hasRole('Master Admin')) {
                                $accessQuery->orWhereNotNull('id');
                            }
                        });
                    })
                    ->latest()
                    ->limit(20)
                    ->get()
                    ->map(function ($note) {
                        return [
                            'key' => 'note-' . $note->id,
                            'category' => 'Catatan Baru',
                            'title' => 'Catatan Baru',
                            'message' => ($note->actor_name ?: 'System') . ': ' . str($note->note)->limit(80),
                            'route' => route('item-requests.show', $note->itemRequest),
                            'time' => optional($note->created_at)->format('d-m-Y H:i'),
                        ];
                    });
            }

            if (Schema::hasTable('purchase_order_notes') && Schema::hasTable('purchase_order_note_reads') && Schema::hasTable('purchase_orders')) {
                $purchaseOrderNoteNotifications = PurchaseOrderNote::query()
                    ->with('purchaseOrder')
                    ->where(function ($query) use ($user) {
                        $query->whereNull('user_id')
                            ->orWhere('user_id', '!=', $user->id);
                    })
                    ->whereNotExists(function ($query) use ($user) {
                        $query->select(DB::raw(1))
                            ->from('purchase_order_note_reads')
                            ->whereColumn('purchase_order_note_reads.purchase_order_note_id', 'purchase_order_notes.id')
                            ->where('purchase_order_note_reads.user_id', $user->id);
                    })
                    ->whereHas('purchaseOrder', function ($query) use ($user) {
                        $query->where(function ($accessQuery) use ($user) {
                            $accessQuery->where('requested_by', $user->id);

                            if ($user->hasRole('Manager Operasional') || $user->hasRole('Master Admin')) {
                                $accessQuery->orWhereHas('approvals', fn ($approvalQuery) => $approvalQuery->where('role_name', 'Manager Operasional'));
                            }

                            if ($user->hasRole('Direktur Operasional') || $user->hasRole('Master Admin')) {
                                $accessQuery->orWhereHas('approvals', fn ($approvalQuery) => $approvalQuery->where('role_name', 'Direktur Operasional'));
                            }

                            if ($user->hasRole('Manager Finance') || $user->hasRole('Master Admin')) {
                                $accessQuery->orWhereNotNull('id');
                            }
                        });
                    })
                    ->latest()
                    ->limit(20)
                    ->get()
                    ->map(function ($note) {
                        return [
                            'key' => 'po-note-' . $note->id,
                            'category' => 'Catatan Baru',
                            'title' => 'Catatan Baru PO',
                            'message' => ($note->actor_name ?: 'System') . ': ' . str($note->note)->limit(80),
                            'route' => route('purchase-orders.show', $note->purchaseOrder),
                            'time' => optional($note->created_at)->format('d-m-Y H:i'),
                        ];
                    });

                $noteNotifications = $noteNotifications->concat($purchaseOrderNoteNotifications);
            }

            $notificationGroups = collect([
                [
                    'key' => 'approval',
                    'label' => 'Approval',
                    'icon' => 'bi-check2-square',
                    'items' => $approvalNotifications->unique('key')->sortByDesc('time')->values(),
                ],
                [
                    'key' => 'realization',
                    'label' => 'Realisasi',
                    'icon' => 'bi-box-seam',
                    'items' => $realizationNotifications->unique('key')->sortByDesc('time')->values(),
                ],
                [
                    'key' => 'notes',
                    'label' => 'Catatan Baru',
                    'icon' => 'bi-chat-left-text',
                    'items' => $noteNotifications->unique('key')->sortByDesc('time')->values(),
                ],
            ]);

            $notifications = $notificationGroups
                ->flatMap(fn ($group) => $group['items'])
                ->unique('key')
                ->sortByDesc('time')
                ->values();

            $view->with('headerNotifications', $notifications->take(8));
            $view->with('headerNotificationGroups', $notificationGroups);
            $view->with('headerNotificationCount', $notifications->count());
        });
    }
}
