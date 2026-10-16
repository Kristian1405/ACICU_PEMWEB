<?php

use App\Http\Controllers\Admin\ContentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Teams\TeamInvitationController;
use App\Http\Middleware\EnsureTeamMembership;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::resource('contents', ContentController::class);
    });

Route::get('/', function () {
    return view('acicu');
})->name('acicu');

Route::get('/acicu/pre-order', function () {
    return view('acicu-preorder');
})->name('acicu.preorder');

Route::get('/acicu/pembayaran', function () {
    return view('acicu-payment');
})->name('acicu.payment');

Route::get('/acicu/status', function () {
    return view('acicu-status');
})->name('acicu.status');

/*
|--------------------------------------------------------------------------
| Portal Admin ACICU (fase tampilan/UI)
|--------------------------------------------------------------------------
| Catatan: gate autentikasi admin belum tersedia. Halaman ini belum
| dilindungi middleware dan hanya menampilkan empty state (tanpa data
| server). Integrasi autentikasi Fortify akan dikerjakan pada tahap
| berikutnya setelah disetujui.
*/
Route::prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('login', fn () => view('admin.login'))->name('login');
        Route::get('dashboard', fn () => view('admin.dashboard'))->name('dashboard');
        Route::get('pesanan', fn () => view('admin.orders'))->name('orders');
        Route::get('produk', fn () => view('admin.products'))->name('products');
        Route::get('laporan', fn () => view('admin.reports'))->name('reports');
    });

Route::prefix('{current_team}')
    ->middleware(['auth', 'verified', EnsureTeamMembership::class])
    ->group(function () {
        Route::get('dashboard', DashboardController::class)->name('dashboard');
    });

Route::middleware(['auth'])->group(function () {
    Route::post('invitations/{invitation}/accept', [TeamInvitationController::class, 'accept'])->name('invitations.accept');
    Route::delete('invitations/{invitation}', [TeamInvitationController::class, 'decline'])->name('invitations.decline');
});

require __DIR__.'/settings.php';
