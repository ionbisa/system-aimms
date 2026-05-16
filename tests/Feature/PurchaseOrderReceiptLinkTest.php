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

    public function test_media_route_serves_receipt_files_without_public_storage_symlink(): void
    {
        Storage::disk('public')->put('purchase-orders/receipts/nota-test.txt', 'nota');

        $response = $this->get(route('media.show', ['path' => 'purchase-orders/receipts/nota-test.txt']));

        $response->assertOk();
        $this->assertSame('nota', file_get_contents($response->baseResponse->getFile()->getPathname()));

        Storage::disk('public')->delete('purchase-orders/receipts/nota-test.txt');
    }

    public function test_receipt_file_url_supports_full_media_urls(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('purchase-orders/receipts/nota-url.pdf', 'nota');

        $purchaseOrder = PurchaseOrder::query()->create([
            'po_number' => '002/BBP/GA/V/2026',
            'total_price' => 5000,
            'status' => 'Approved',
            'receipt_file' => 'https://example.test/storage/purchase-orders/receipts/nota-url.pdf',
        ]);

        $this->assertSame(
            route('media.show', ['path' => 'purchase-orders/receipts/nota-url.pdf']),
            $purchaseOrder->receipt_file_url
        );
    }
}
