<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel katalog produk ACICU.
 * ------------------------------------------------------------------
 * Sebelumnya katalog produk ACICU ("CIWENG", "MOLACHEE", "CILUKBA")
 * di-hardcode pada resources/views/acicu.blade.php. Tabel ini menjadi
 * SATU SUMBER data yang dipakai baik oleh halaman pembeli (homepage)
 * maupun panel admin, sehingga keduanya selalu konsisten.
 *
 * Tiga produk awal disisipkan langsung pada migrasi ini agar halaman
 * pembeli tetap tampil utuh setelah deploy (start.sh hanya menjalankan
 * `migrate --force`, bukan `db:seed`).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title', 60);            // nama singkat untuk slider (mis. "CIWENG")
            $table->string('name', 120);            // nama jual (mis. "Ciweng Original Gurih")
            $table->string('category', 60)->default('Aci'); // dipakai filter kategori di admin
            $table->text('description')->nullable();
            $table->unsignedInteger('price')->default(0);
            $table->string('image')->nullable();    // path relatif ke public/ (mis. src-acicu/ciweng.PNG)
            $table->unsignedInteger('stock')->default(0);       // kuota harian
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        $now = now();

        DB::table('products')->insert([
            [
                'slug' => 'ciweng',
                'title' => 'CIWENG',
                'name' => 'Ciweng Original Gurih',
                'category' => 'aci kering',
                'description' => 'Camilan berbahan dasar aci yang gurih dan renyah. Memiliki rasa khas yang cocok dinikmati sebagai teman bersantai ataupun camilan sehari-hari',
                'price' => 10000,
                'image' => 'src-acicu/ciweng.PNG',
                'stock' => 0,
                'is_active' => true,
                'sort_order' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'slug' => 'molachee',
                'title' => 'MOLACHEE',
                'name' => 'Molachee Crispy Keju',
                'category' => 'aci kering',
                'description' => 'Makanan ringan dengan cita rasa khas dan tekstur kenyal yang lezat. Disajikan fresh setiap hari dengan perpaduan rasa yang nikmat.',
                'price' => 10000,
                'image' => 'src-acicu/molachee.PNG',
                'stock' => 0,
                'is_active' => true,
                'sort_order' => 2,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'slug' => 'cilukba',
                'title' => 'CILUKBA',
                'name' => 'Cilukba Pedas Manis',
                'category' => 'aci kering',
                'description' => 'Camilan gurih dengan tekstur renyah di luar dan lembut di dalam. Cocok dinikmati saat santai bersama teman maupun keluarga.',
                'price' => 10000,
                'image' => 'src-acicu/cilukba.PNG',
                'stock' => 0,
                'is_active' => true,
                'sort_order' => 3,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
