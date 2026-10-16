<?php

namespace Tests\Feature\Admin;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminProductTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_admin_login(): void
    {
        $this->get('/admin/produk')->assertRedirect('/admin/login');
    }

    public function test_admin_can_view_product_catalog(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/admin/produk')
            ->assertOk()
            ->assertSee('Ciweng Original Gurih')
            ->assertSee('Tambah Produk');
    }

    public function test_homepage_shows_products_from_database(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('CIWENG')
            ->assertSee('Ciweng Original Gurih');
    }

    public function test_admin_can_create_product(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/admin/produk', [
                'title' => 'SEBLAK',
                'name' => 'Seblak Aci Pedas',
                'category' => 'aci kuah',
                'description' => 'Seblak aci kuah pedas.',
                'price' => 12000,
                'stock' => 20,
                'sort_order' => 4,
                'is_active' => '1',
            ])
            ->assertRedirect('/admin/produk');

        $this->assertDatabaseHas('products', [
            'slug' => 'seblak',
            'name' => 'Seblak Aci Pedas',
            'price' => 12000,
            'is_active' => 1,
        ]);
    }

    public function test_product_requires_title_name_and_price(): void
    {
        $this->actingAs(User::factory()->create())
            ->from('/admin/produk/create')
            ->post('/admin/produk', [])
            ->assertRedirect('/admin/produk/create')
            ->assertSessionHasErrors(['title', 'name', 'price']);
    }

    public function test_admin_can_update_product(): void
    {
        $product = Product::where('slug', 'ciweng')->firstOrFail();

        $this->actingAs(User::factory()->create())
            ->put('/admin/produk/'.$product->id, [
                'title' => $product->title,
                'name' => 'Ciweng Super Gurih',
                'category' => 'aci kering',
                'price' => 11000,
                'is_active' => '1',
            ])
            ->assertRedirect('/admin/produk');

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Ciweng Super Gurih',
            'price' => 11000,
        ]);
    }

    public function test_admin_can_delete_product(): void
    {
        $product = Product::where('slug', 'cilukba')->firstOrFail();

        $this->actingAs(User::factory()->create())
            ->delete('/admin/produk/'.$product->id)
            ->assertRedirect('/admin/produk');

        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }
}
