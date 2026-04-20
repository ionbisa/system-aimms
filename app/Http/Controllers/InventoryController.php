<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\InventoryTransaction;
use App\Models\AuditLog;

class InventoryController extends Controller
{
    public function index()
    {
        $transactions = InventoryTransaction::latest()->get();
        return view('inventory.index', compact('transactions'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'item_name' => 'required|string',
            'qty'       => 'required|integer|min:1',
            'type'      => 'required|in:IN,OUT',
        ]);

        InventoryTransaction::create($validated);

        AuditLog::record(
            'Inventory '.$validated['type'],
            $validated['item_name']
        );

        return back()->with('success', 'Inventory berhasil dicatat');
    }
}
