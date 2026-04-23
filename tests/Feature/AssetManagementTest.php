<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssetManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_store_company_asset_with_long_specification(): void
    {
        $user = User::factory()->create();

        $specification = implode(' ', array_fill(
            0,
            5,
            'Merk MULTIPRO tipe VBC200-1/110 kapasitas 2HP tekanan 8 bar air delivery 320 liter per menit.'
        ));

        $response = $this
            ->actingAs($user)
            ->post(route('assets.store'), [
                'asset_code' => '001/AST/MKN-GA/III/2026',
                'name' => 'Compressor Angin 2 hp Multipro VBC 200-1',
                'location' => 'Workshop Kendaraan',
                'specification' => $specification,
                'nopol' => '1',
                'type' => 'Office Assets',
                'status' => 'active',
                'pic' => 'Prasetyo',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('assets.index'));

        $asset = Asset::query()->where('asset_code', '001/AST/MKN-GA/III/2026')->first();

        $this->assertNotNull($asset);
        $this->assertSame($specification, $asset->specification);
    }
}
