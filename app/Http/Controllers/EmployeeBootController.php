<?php

namespace App\Http\Controllers;

use App\Models\EmployeeBoot;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class EmployeeBootController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('search'));
        $status = trim((string) $request->query('status'));

        $employeeBoots = EmployeeBoot::query()
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
            ->orderByDesc('return_date')
            ->orderByDesc('id')
            ->simplePaginate(10)
            ->withQueryString();

        return view('employee-boots.index', compact('employeeBoots', 'search', 'status'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_name' => 'required|string|max:255',
            'employee_code' => 'required|string|max:255',
            'department' => 'required|string|max:255',
            'boot_size' => 'required|string|max:50',
            'quantity_given' => 'required|integer|min:1',
            'condition' => 'required|in:Baru,Bekas Layak',
            'notes' => 'required|in:Baru,Distribusi Rutin,Pergantian Rusak',
            'photo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:10240',
        ]);

        $returnDate = Carbon::today();
        $validated['return_date'] = $returnDate;
        $validated['expiry_date'] = $returnDate->copy()->addDays(180);

        if ($request->hasFile('photo')) {
            $validated['photo'] = $request->file('photo')->store('employee-boots', 'public');
        }

        EmployeeBoot::create($validated);

        return redirect()->route('employee-boots.index')
            ->with('success', 'Data ABP Sepatu Boots berhasil ditambahkan.');
    }

    public function update(Request $request, EmployeeBoot $employeeBoot)
    {
        $validated = $request->validate([
            'employee_name' => 'required|string|max:255',
            'employee_code' => 'required|string|max:255',
            'department' => 'required|string|max:255',
            'boot_size' => 'required|string|max:50',
            'quantity_given' => 'required|integer|min:1',
            'condition' => 'required|in:Baru,Bekas Layak',
            'notes' => 'required|in:Baru,Distribusi Rutin,Pergantian Rusak',
            'photo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:10240',
        ]);

        if ($request->hasFile('photo')) {
            if ($employeeBoot->photo) {
                Storage::disk('public')->delete($employeeBoot->photo);
            }

            $validated['photo'] = $request->file('photo')->store('employee-boots', 'public');
        }

        $employeeBoot->update($validated);

        return redirect()->route('employee-boots.index')
            ->with('success', 'Data ABP Sepatu Boots berhasil diupdate.');
    }

    public function destroy(EmployeeBoot $employeeBoot)
    {
        if ($employeeBoot->photo) {
            Storage::disk('public')->delete($employeeBoot->photo);
        }

        $employeeBoot->delete();

        return redirect()->route('employee-boots.index')
            ->with('success', 'Data ABP Sepatu Boots berhasil dihapus.');
    }
}
