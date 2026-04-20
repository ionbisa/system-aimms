<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AssetController extends Controller
{
    public function index(Request $request)
    {
        $filters = [
            'delivery' => [
                'label' => 'Delivery Cars',
                'types' => ['Delivery Cars', 'Car'],
            ],
            'personal' => [
                'label' => 'Personal Cars',
                'types' => ['Personal Cars'],
            ],
            'motor' => [
                'label' => 'Motorcycles',
                'types' => ['Motorcycles', 'Motorcycle', 'Motor'],
            ],
            'company' => [
                'label' => 'Company Assets',
                'types' => ['Office Assets', 'Office'],
            ],
        ];

        $selectedCategory = $request->query('category');
        $selectedFilter = $filters[$selectedCategory] ?? null;
        $search = trim((string) $request->query('search'));

        $assets = Asset::query()
            ->when($selectedFilter, function ($query) use ($selectedFilter) {
                $query->whereIn('type', $selectedFilter['types']);
            })
            ->when($search !== '', function ($query) use ($search) {
                $query->where('name', 'like', '%' . $search . '%');
            })
            ->orderByDesc('id')
            ->simplePaginate(10)
            ->withQueryString();

        $pageTitle = $selectedFilter['label'] ?? 'Asset Management';

        return view('assets.index', compact('assets', 'pageTitle', 'selectedCategory', 'search'));
    }

    public function create()
    {
        return view('assets.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'asset_code' => 'required|string|max:255|unique:assets,asset_code',
            'name' => 'required|string|max:255',
            'location' => 'nullable|string|max:255',
            'specification' => 'nullable|string',
            'nopol' => 'nullable|string|max:255',
            'type' => 'required|in:Delivery Cars,Personal Cars,Motorcycles,Office Assets',
            'status' => 'required|in:active,maintenance,disposed',
            'pic' => 'nullable|string|max:255',
            'photo' => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('photo')) {
            $validated['photo'] = $request->file('photo')->store('assets', 'public');
        }

        Asset::create($validated);

        return redirect()->route('assets.index')
            ->with('success', 'Asset berhasil ditambahkan');
    }

    public function edit(Asset $asset)
    {
        return view('assets.edit', compact('asset'));
    }

    public function update(Request $request, Asset $asset)
    {
        $validated = $request->validate([
            'asset_code' => 'required|string|max:255|unique:assets,asset_code,' . $asset->id,
            'name' => 'required|string|max:255',
            'location' => 'nullable|string|max:255',
            'specification' => 'nullable|string',
            'nopol' => 'nullable|string|max:255',
            'type' => 'required|in:Delivery Cars,Personal Cars,Motorcycles,Office Assets',
            'status' => 'required|in:active,maintenance,disposed',
            'pic' => 'nullable|string|max:255',
            'photo' => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('photo')) {

            if ($asset->photo) {
                Storage::disk('public')->delete($asset->photo);
            }

            $validated['photo'] = $request->file('photo')->store('assets', 'public');
        }

        $asset->update($validated);

        return redirect()->route('assets.index')
            ->with('success', 'Asset berhasil diupdate');
    }

    public function destroy(Asset $asset)
    {
        if ($asset->photo) {
            Storage::disk('public')->delete($asset->photo);
        }

        $asset->delete();

        return redirect()->route('assets.index')
            ->with('success', 'Asset berhasil dihapus');
    }
}
