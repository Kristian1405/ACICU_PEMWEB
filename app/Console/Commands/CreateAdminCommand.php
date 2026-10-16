<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

/**
 * Membuat (atau menyetel ulang kata sandi) akun admin ACICU.
 *
 * Contoh:
 *   php artisan acicu:admin admin@acicu.test --name="Admin ACICU"
 *   docker compose exec app php artisan acicu:admin admin@acicu.test
 *
 * Bila --password tidak diisi, kata sandi diminta secara interaktif
 * sehingga tidak tersimpan di riwayat perintah shell.
 */
class CreateAdminCommand extends Command
{
    protected $signature = 'acicu:admin
        {email? : Email akun admin}
        {--name= : Nama admin}
        {--password= : Kata sandi (kosongkan agar diminta interaktif)}';

    protected $description = 'Membuat atau memperbarui akun admin ACICU';

    public function handle(): int
    {
        $email = $this->argument('email') ?: $this->ask('Email admin');
        $name = $this->option('name') ?: $this->ask('Nama admin', 'Admin ACICU');
        $password = $this->option('password') ?: $this->secret('Kata sandi admin (min. 8 karakter)');

        $validator = Validator::make(
            ['email' => $email, 'name' => $name, 'password' => $password],
            [
                'email' => ['required', 'string', 'email', 'max:255'],
                'name' => ['required', 'string', 'max:255'],
                'password' => ['required', 'string', 'min:8'],
            ],
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        // Cast "hashed" pada model User akan meng-hash kata sandi otomatis.
        $user = User::updateOrCreate(
            ['email' => (string) $email],
            ['name' => (string) $name, 'password' => (string) $password],
        );

        $user->forceFill(['email_verified_at' => now()])->save();

        $this->info("Akun admin siap: {$user->email}");

        return self::SUCCESS;
    }
}
