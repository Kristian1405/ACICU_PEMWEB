<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_renders_without_firebase(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/admin/dashboard')
            ->assertOk()
            ->assertSee('Total Pesanan');
    }

    public function test_reports_renders_without_firebase(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/admin/laporan')
            ->assertOk()
            ->assertSee('Laporan Penjualan');
    }

    public function test_reports_accepts_date_filter(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/admin/laporan?from=2026-01-01&to=2026-12-31')
            ->assertOk();
    }
}
