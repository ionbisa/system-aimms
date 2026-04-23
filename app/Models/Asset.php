<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class Asset extends Model
{
    public const SPECIFICATION_MAX_LENGTH = 2000;

    public $timestamps = false;

    protected $fillable = [
        'asset_code',
        'name',
        'location',
        'specification',
        'nopol',
        'type',
        'status',
        'pic',
        'photo',
    ];

    public function stock(): HasOne
    {
        return $this->hasOne(Stock::class);
    }

    public static function groupedSummaryKey(string $name, string $type): string
    {
        return Str::lower(Str::squish($name . '|' . $type));
    }

    public static function buildGroupedSummaries(Collection $assets): Collection
    {
        return $assets
            ->groupBy(function (self $asset) {
                return self::groupedSummaryKey($asset->name, $asset->type);
            })
            ->map(function (Collection $group) {
                /** @var self $firstAsset */
                $firstAsset = $group->first();

                $locations = $group
                    ->pluck('location')
                    ->filter(fn (?string $location) => filled($location))
                    ->map(fn (string $location) => Str::squish($location))
                    ->unique()
                    ->values();

                $pics = $group
                    ->pluck('pic')
                    ->filter(fn (?string $pic) => filled($pic))
                    ->map(fn (string $pic) => Str::squish($pic))
                    ->unique()
                    ->values();

                return [
                    'summary_key' => self::groupedSummaryKey($firstAsset->name, $firstAsset->type),
                    'name' => $firstAsset->name,
                    'type' => $firstAsset->type,
                    'total_qty' => $group->count(),
                    'active_qty' => $group->where('status', 'active')->count(),
                    'maintenance_qty' => $group->where('status', 'maintenance')->count(),
                    'disposed_qty' => $group->where('status', 'disposed')->count(),
                    'locations' => $locations,
                    'pics' => $pics,
                    'placements' => $group
                        ->sortBy([
                            fn (self $asset) => Str::lower($asset->location ?? ''),
                            fn (self $asset) => Str::lower($asset->pic ?? ''),
                            fn (self $asset) => Str::lower($asset->asset_code ?? ''),
                        ])
                        ->values()
                        ->map(fn (self $asset) => [
                            'asset_code' => $asset->asset_code,
                            'location' => $asset->location ?: '-',
                            'pic' => $asset->pic ?: '-',
                            'status' => $asset->status,
                        ]),
                ];
            })
            ->sortBy(fn (array $summary) => Str::lower($summary['name'] . '|' . $summary['type']))
            ->values();
    }
}
