<?php

use App\Http\Controllers\Admin\ContentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Teams\TeamInvitationController;
use App\Http\Middleware\EnsureTeamMembership;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware('auth')
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::resource('contents', ContentController::class);
    });

Route::get('/acicu', function () {
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
