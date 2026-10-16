<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\ContentController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PaymentController;
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
| Pembayaran QRIS (Midtrans Snap) + Webhook
|--------------------------------------------------------------------------
| snapToken : browser meminta Snap token untuk ditampilkan sebagai QRIS.
| notification: webhook Midtrans (dikecualikan dari CSRF, lihat bootstrap/app.php).
| check     : (opsional) cek status pesanan dari Firestore.
*/
Route::post('/acicu/pembayaran/snap', [PaymentController::class, 'snapToken'])->name('acicu.payment.snap');
Route::post('/acicu/pembayaran/verify', [PaymentController::class, 'verify'])->name('acicu.payment.verify');
Route::get('/acicu/status/{order}', [PaymentController::class, 'status'])->name('acicu.status.check');
Route::post('/midtrans/notification', [PaymentController::class, 'notification'])->name('midtrans.notification');

/*
|--------------------------------------------------------------------------
| Portal Admin ACICU
|--------------------------------------------------------------------------
| Login memakai guard session Laravel (model User). Halaman admin
| dilindungi middleware "auth" dan tamu diarahkan ke /admin/login
| (lihat bootstrap/app.php -> redirectGuestsTo()).
*/
Route::prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('login', [AuthController::class, 'showLogin'])->name('login');
        Route::post('login', [AuthController::class, 'login'])
            ->middleware('throttle:login')
            ->name('login.attempt');

        Route::middleware('auth')->group(function () {
            Route::post('logout', [AuthController::class, 'logout'])->name('logout');
            Route::get('dashboard', fn () => view('admin.dashboard'))->name('dashboard');
            Route::get('pesanan', [OrderController::class, 'index'])->name('orders');
            Route::get('produk', fn () => view('admin.products'))->name('products');
            Route::get('laporan', fn () => view('admin.reports'))->name('reports');
        });
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
