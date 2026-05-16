<?php

namespace Tests\Feature;

use App\Models\ItemRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ItemRequestRealizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_distributed_realization_marks_item_request_as_done(): void
    {
        $role = Role::query()->create(['name' => 'Admin GA']);
        $user = User::factory()->create();
        $user->assignRole($role);

        $itemRequest = ItemRequest::query()->create([
            'request_number' => 'PB-001',
            'requested_at' => now(),
            'division' => 'Produksi',
            'requested_by' => $user->id,
            'overall_status' => 'approved',
            'current_step' => 'waiting_ga_realization',
            'final_approved_at' => now(),
        ]);

        $response = $this
            ->actingAs($user)
            ->post(route('item-requests.realize', $itemRequest), [
                'realization_status' => 'distributed',
                'note' => 'Barang sudah diterima user.',
            ]);

        $response
            ->assertRedirect(route('item-requests.show', $itemRequest))
            ->assertSessionHasNoErrors();

        $itemRequest->refresh();

        $this->assertSame('done', $itemRequest->overall_status);
        $this->assertSame('Done', $itemRequest->status_label);
        $this->assertSame('completed', $itemRequest->current_step);
        $this->assertNotNull($itemRequest->completed_at);
    }
}
