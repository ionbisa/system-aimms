<?php

namespace App\Console\Commands;

use App\Models\PurchaseOrder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class NormalizePurchaseOrderAmounts extends Command
{
    protected $signature = 'purchase-orders:normalize-amounts {--dry-run : Tampilkan perubahan tanpa menyimpan}';

    protected $description = 'Normalisasi nominal Purchase Order lama agar konsisten ke rupiah utuh.';

    protected int $updatedOrders = 0;

    protected int $updatedItems = 0;

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $this->info($dryRun
            ? 'Mode dry-run: menghitung perubahan tanpa menyimpan.'
            : 'Memproses normalisasi nominal Purchase Order...');

        $process = function (): void {
            PurchaseOrder::query()
                ->with('items')
                ->orderBy('id')
                ->chunkById(100, function ($purchaseOrders) {
                    foreach ($purchaseOrders as $purchaseOrder) {
                        $this->normalizePurchaseOrder($purchaseOrder);
                    }
                });
        };

        if ($dryRun) {
            $process();
        } else {
            DB::transaction($process);
        }

        $this->newLine();
        $this->line('Baris item diperbarui: ' . $this->updatedItems);
        $this->line('Purchase order diperbarui: ' . $this->updatedOrders);

        return self::SUCCESS;
    }

    protected function normalizePurchaseOrder(PurchaseOrder $purchaseOrder): void
    {
        $orderChanged = false;
        $recalculatedTotal = 0.0;

        foreach ($purchaseOrder->items as $item) {
            $normalizedQty = round((float) $item->qty, 2);
            $normalizedUnitPrice = round((float) $item->estimated_unit_price, 0);
            $normalizedLineTotal = round($normalizedQty * $normalizedUnitPrice, 0);

            $itemChanged =
                (float) $item->qty !== $normalizedQty ||
                (float) $item->estimated_unit_price !== $normalizedUnitPrice ||
                (float) $item->estimated_total_price !== $normalizedLineTotal;

            if ($itemChanged) {
                $this->updatedItems++;

                if (! $this->option('dry-run')) {
                    $item->update([
                        'qty' => $normalizedQty,
                        'estimated_unit_price' => $normalizedUnitPrice,
                        'estimated_total_price' => $normalizedLineTotal,
                    ]);
                }
            }

            $recalculatedTotal += $normalizedLineTotal;
        }

        $normalizedOrderQty = is_null($purchaseOrder->qty) ? null : round((float) $purchaseOrder->qty, 2);
        $normalizedOrderUnitPrice = is_null($purchaseOrder->unit_price) ? null : round((float) $purchaseOrder->unit_price, 0);
        $normalizedOrderTotal = $purchaseOrder->items->isNotEmpty()
            ? round($recalculatedTotal, 0)
            : round((float) $purchaseOrder->total_price, 0);

        if (! is_null($purchaseOrder->qty) && (float) $purchaseOrder->qty !== $normalizedOrderQty) {
            $orderChanged = true;
        }

        if (! is_null($purchaseOrder->unit_price) && (float) $purchaseOrder->unit_price !== $normalizedOrderUnitPrice) {
            $orderChanged = true;
        }

        if ((float) $purchaseOrder->total_price !== $normalizedOrderTotal) {
            $orderChanged = true;
        }

        if (! $orderChanged) {
            return;
        }

        $this->updatedOrders++;

        if ($this->option('dry-run')) {
            return;
        }

        $payload = [
            'total_price' => $normalizedOrderTotal,
        ];

        if (! is_null($purchaseOrder->qty)) {
            $payload['qty'] = $normalizedOrderQty;
        }

        if (! is_null($purchaseOrder->unit_price)) {
            $payload['unit_price'] = $normalizedOrderUnitPrice;
        }

        $purchaseOrder->update($payload);
    }
}
