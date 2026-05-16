<?php

namespace Tests\Feature;

use App\Models\PurchaseOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PurchaseOrderReceiptLinkTest extends TestCase
{
    use RefreshDatabase;

    public function test_receipt_file_url_supports_public_storage_paths(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('purchase-orders/receipts/nota.pdf', 'nota');

        $purchaseOrder = PurchaseOrder::query()->create([
            'po_number' => '001/BBP/GA/V/2026',
            'total_price' => 5000,
            'status' => 'Approved',
            'receipt_file' => 'public/storage/purchase-orders/receipts/nota.pdf',
        ]);

        $this->assertSame(
            route('media.show', ['path' => 'purchase-orders/receipts/nota.pdf']),
            $purchaseOrder->receipt_file_url
        );
    }
}
