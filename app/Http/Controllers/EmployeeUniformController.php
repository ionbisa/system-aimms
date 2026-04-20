<?php

namespace App\Http\Controllers;

use App\Models\EmployeeUniform;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class EmployeeUniformController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('search'));
        $status = trim((string) $request->query('status'));

        $employeeUniforms = EmployeeUniform::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($nestedQuery) use ($search) {
                    $nestedQuery
                        ->where('employee_name', 'like', '%' . $search . '%')
                        ->orWhere('employee_code', 'like', '%' . $search . '%')
                        ->orWhere('department', 'like', '%' . $search . '%');
                });
            })
            ->when($status === 'Aktif', function ($query) {
                $query->whereDate('expiry_date', '>=', Carbon::today());
            })
            ->when($status === 'Habis', function ($query) {
                $query->whereDate('expiry_date', '<', Carbon::today());
            })
            ->orderByDesc('pickup_date')
            ->orderByDesc('id')
            ->simplePaginate(10)
            ->withQueryString();

        return view('employee-uniforms.index', compact('employeeUniforms', 'search', 'status'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_name' => 'required|string|max:255',
            'employee_code' => 'required|string|max:255',
            'department' => 'required|string|max:255',
            'shirt_size' => 'required|string|max:50',
            'quantity_given' => 'required|integer|min:1',
            'condition' => 'required|in:Baru,Bekas Layak',
            'notes' => 'required|in:Baru,Distribusi Rutin,Pergantian Rusak',
            'photo' => 'nullable|image|max:2048',
        ]);

        $pickupDate = Carbon::today();
        $validated['pickup_date'] = $pickupDate;
        $validated['expiry_date'] = $pickupDate->copy()->addDays(360);

        if ($request->hasFile('photo')) {
            $validated['photo'] = $request->file('photo')->store('employee-uniforms', 'public');
        }

        EmployeeUniform::create($validated);

        return redirect()->route('employee-uniforms.index')
            ->with('success', 'Data APD Seragam Produksi berhasil ditambahkan.');
    }

    public function update(Request $request, EmployeeUniform $employeeUniform)
    {
        $validated = $request->validate([
            'employee_name' => 'required|string|max:255',
            'employee_code' => 'required|string|max:255',
            'department' => 'required|string|max:255',
            'shirt_size' => 'required|string|max:50',
            'quantity_given' => 'required|integer|min:1',
            'condition' => 'required|in:Baru,Bekas Layak',
            'notes' => 'required|in:Baru,Distribusi Rutin,Pergantian Rusak',
            'photo' => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('photo')) {
            if ($employeeUniform->photo) {
                Storage::disk('public')->delete($employeeUniform->photo);
            }

            $validated['photo'] = $request->file('photo')->store('employee-uniforms', 'public');
        }

        $employeeUniform->update($validated);

        return redirect()->route('employee-uniforms.index')
            ->with('success', 'Data APD Seragam Produksi berhasil diupdate.');
    }

    public function destroy(EmployeeUniform $employeeUniform)
    {
        if ($employeeUniform->photo) {
            Storage::disk('public')->delete($employeeUniform->photo);
        }

        $employeeUniform->delete();

        return redirect()->route('employee-uniforms.index')
            ->with('success', 'Data APD Seragam Produksi berhasil dihapus.');
    }
}
