<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_snap_rejects_malformed_order_id(): void
    {
        $this->postJson('/acicu/pembayaran/snap', ['order_id' => '../../x'])
            ->assertStatus(422);
    }

    public function test_snap_ignores_client_amounts_and_needs_server_side_order(): void
    {
        // Tanpa Firebase terkonfigurasi, server menolak (tidak mempercayai harga dari klien).
        $this->postJson('/acicu/pembayaran/snap', ['order_id' => 'PO-1234', 'gross_amount' => 1])
            ->assertStatus(503);
    }

    public function test_webhook_rejects_invalid_signature(): void
    {
        $this->postJson('/midtrans/notification', [
            'order_id' => 'PO-1234',
            'status_code' => '200',
            'gross_amount' => '10000.00',
            'signature_key' => 'palsu',
            'transaction_status' => 'settlement',
        ])->assertStatus(403);
    }

    public function test_status_endpoint_hides_unknown_orders(): void
    {
        $this->getJson('/acicu/status/PO-9999')->assertStatus(404);
    }

    public function test_listed_admin_only_when_allowlist_is_set(): void
    {
        config(['acicu.admin_emails' => ['pemilik@acicu.test']]);

        $this->actingAs(User::factory()->create())
            ->get('/admin/dashboard')
            ->assertForbidden();
    }

    public function test_non_admin_cannot_log_in_to_admin_panel(): void
    {
        config(['acicu.admin_emails' => ['pemilik@acicu.test']]);
        $user = User::factory()->create();

        $this->from('/admin/login')
            ->post('/admin/login', ['username' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors('username');

        $this->assertGuest();
    }
}
