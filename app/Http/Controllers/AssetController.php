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

        $assetQuery = Asset::query()
            ->when($selectedFilter, function ($query) use ($selectedFilter) {
                $query->whereIn('type', $selectedFilter['types']);
            })
            ->when($search !== '', function ($query) use ($search) {
                $query->where('name', 'like', '%' . $search . '%');
            });

        $assets = (clone $assetQuery)
            ->orderByDesc('id')
            ->simplePaginate(10)
            ->withQueryString();

        $pageTitle = $selectedFilter['label'] ?? 'Asset Management';

        $assetSummaryMap = collect();

        if ($selectedCategory === 'company') {
            $assetSummaryMap = Asset::buildGroupedSummaries(
                (clone $assetQuery)
                    ->orderBy('name')
                    ->orderBy('location')
                    ->get()
            )->keyBy('summary_key');
        }

        return view('assets.index', compact(
            'assets',
            'assetSummaryMap',
            'pageTitle',
            'selectedCategory',
            'search'
        ));
    }

    public function create()
    {
        return view('assets.create');
    }

    public function store(Request $request)
    {
        $validated = $this->validateAsset($request);

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
        $validated = $this->validateAsset($request, $asset);

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

    protected function validateAsset(Request $request, ?Asset $asset = null): array
    {
        $assetCodeRule = 'required|string|max:255|unique:assets,asset_code';

        if ($asset) {
            $assetCodeRule .= ',' . $asset->id;
        }

        return $request->validate([
            'asset_code' => $assetCodeRule,
            'name' => 'required|string|max:255',
            'location' => 'nullable|string|max:255',
            'specification' => 'nullable|string|max:' . Asset::SPECIFICATION_MAX_LENGTH,
            'nopol' => 'nullable|string|max:255',
            'type' => 'required|in:Delivery Cars,Personal Cars,Motorcycles,Office Assets',
            'status' => 'required|in:active,maintenance,disposed',
            'pic' => 'nullable|string|max:255',
            'photo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:10240',
        ], [
            'specification.max' => 'Spesifikasi maksimal ' . Asset::SPECIFICATION_MAX_LENGTH . ' karakter.',
        ]);
    }

}
