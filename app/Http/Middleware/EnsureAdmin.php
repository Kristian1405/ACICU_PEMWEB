<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * OPSIONAL: bila ACICU_ADMIN_EMAILS diisi, hanya email itu yang boleh masuk
 * /admin. Bila kosong, perilaku tetap seperti semula.
 */
class EnsureAdmin
{
    public static function isAdmin(?object $user): bool
    {
        $email = strtolower((string) ($user->email ?? ''));
        $allowed = (array) config('acicu.admin_emails');

        // Daftar kosong = perilaku lama (semua akun yang login boleh masuk).
        if ($allowed === []) {
            return $user !== null;
        }

        return $email !== '' && in_array($email, array_map('strtolower', $allowed), true);
    }

    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(self::isAdmin($request->user()), 403, 'Khusus admin.');

        return $next($request);
    }
}
