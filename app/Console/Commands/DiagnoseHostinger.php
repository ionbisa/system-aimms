<?php

namespace App\Console\Commands;

use App\Support\PublicMedia;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DiagnoseHostinger extends Command
{
    protected $signature = 'aimms:diagnose-hostinger {media_path? : Optional storage/media path to check}';

    protected $description = 'Check live Hostinger readiness for stock selection and purchase order receipt media.';

    public function handle(): int
    {
        $this->info('AIMMS Hostinger Diagnose');
        $this->line('APP_ENV: ' . config('app.env'));
        $this->line('APP_URL: ' . config('app.url'));
        $this->line('DB_CONNECTION: ' . config('database.default'));
        $this->newLine();

        $this->checkDatabaseConnection();
        $this->checkStockSelection();
        $this->checkSpecificMediaPath();
        $this->checkPurchaseOrderReceipts();

        return self::SUCCESS;
    }

    protected function checkDatabaseConnection(): void
    {
        $this->info('Database');

        try {
            DB::connection()->getPdo();
            $this->line('OK: database connected.');
        } catch (\Throwable $exception) {
            $this->error('ERROR: database connection failed: ' . $exception->getMessage());
        }

        $this->newLine();
    }

    protected function checkStockSelection(): void
    {
        $this->info('Permintaan Barang / On Stock');

        if (! Schema::hasTable('stocks')) {
            $this->error('ERROR: tabel stocks belum ada. Jalankan php artisan migrate --force.');
            $this->newLine();
            return;
        }

        $requiredStockColumns = ['item_code', 'item_name', 'qty', 'unit'];
        $missingStockColumns = collect($requiredStockColumns)
            ->reject(fn (string $column) => Schema::hasColumn('stocks', $column))
            ->values();

        if ($missingStockColumns->isNotEmpty()) {
            $this->error('ERROR: kolom stocks kurang: ' . $missingStockColumns->implode(', '));
        }

        $hasItemRequestItems = Schema::hasTable('item_request_items');
        $supportsStockId = $hasItemRequestItems && Schema::hasColumn('item_request_items', 'stock_id');
        $supportsProcurementType = $hasItemRequestItems && Schema::hasColumn('item_request_items', 'procurement_type');

        $this->line('item_request_items.stock_id: ' . ($supportsStockId ? 'OK' : 'MISSING'));
        $this->line('item_request_items.procurement_type: ' . ($supportsProcurementType ? 'OK' : 'MISSING'));

        $totalStocks = DB::table('stocks')->count();
        $namedStocks = Schema::hasColumn('stocks', 'item_name')
            ? DB::table('stocks')->whereNotNull('item_name')->where('item_name', '<>', '')->count()
            : 0;

        $this->line('Total stocks: ' . $totalStocks);
        $this->line('Stocks tampil di dropdown: ' . $namedStocks);

        if ($namedStocks === 0) {
            $this->warn('WARNING: dropdown On Stock akan kosong karena tidak ada stocks.item_name yang terisi.');
        }

        $this->newLine();
    }

    protected function checkSpecificMediaPath(): void
    {
        $mediaPath = $this->argument('media_path');

        if (! $mediaPath) {
            return;
        }

        $this->info('Cek File Spesifik');
        $this->line('Input: ' . $mediaPath);
        $this->line('Normalized: ' . (PublicMedia::normalizePath($mediaPath) ?? '-'));

        $file = PublicMedia::findFile($mediaPath);

        if ($file) {
            $this->line('OK: file ditemukan: ' . $file);
        } else {
            $this->error('ERROR: file tidak ditemukan oleh Laravel.');
        }

        $this->newLine();
    }

    protected function checkPurchaseOrderReceipts(): void
    {
        $this->info('Purchase Order / Bukti Nota');

        if (! Schema::hasTable('purchase_orders')) {
            $this->error('ERROR: tabel purchase_orders belum ada. Jalankan php artisan migrate --force.');
            $this->newLine();
            return;
        }

        $receiptColumn = Schema::hasColumn('purchase_orders', 'receipt_file') ? 'receipt_file' : null;
        $photoColumn = Schema::hasColumn('purchase_orders', 'photo') ? 'photo' : null;

        if (! $receiptColumn && ! $photoColumn) {
            $this->error('ERROR: kolom receipt_file/photo belum ada.');
            $this->newLine();
            return;
        }

        $fileColumnSql = $receiptColumn && $photoColumn
            ? 'COALESCE(receipt_file, photo)'
            : ($receiptColumn ?: $photoColumn);

        $rows = DB::table('purchase_orders')
            ->select(['id', 'po_number'])
            ->selectRaw($fileColumnSql . ' as media_path')
            ->whereRaw($fileColumnSql . ' IS NOT NULL')
            ->whereRaw($fileColumnSql . " <> ''")
            ->orderByDesc('id')
            ->limit(20)
            ->get();

        $missing = $rows->filter(fn ($row) => ! PublicMedia::exists($row->media_path));

        $this->line('PO dengan path bukti nota dicek: ' . $rows->count());
        $this->line('File bukti nota hilang/tidak terbaca: ' . $missing->count());

        foreach ($missing->take(10) as $row) {
            $this->warn('Missing PO #' . $row->id . ' ' . ($row->po_number ?? '-') . ': ' . $row->media_path);
        }

        if ($rows->isNotEmpty() && $missing->isEmpty()) {
            $this->line('OK: file bukti nota terbaru yang dicek dapat ditemukan.');
        }

        $this->newLine();
    }
}
