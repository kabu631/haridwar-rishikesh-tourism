<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

#[Signature('app:create-admin {email : Login email} {--name=Administrator : Display name} {--password= : Password (generated when omitted)}')]
#[Description('Create or update an admin panel user')]
class CreateAdminUser extends Command
{
    public function handle(): int
    {
        $email = (string) $this->argument('email');

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('Please provide a valid email address.');

            return self::FAILURE;
        }

        $password = $this->option('password') ?: Str::password(16, symbols: false);

        $user = User::query()->updateOrCreate(
            ['email' => $email],
            ['name' => $this->option('name'), 'password' => $password, 'is_admin' => true],
        );

        $this->info("Admin user ready: {$user->email}");

        if (! $this->option('password')) {
            $this->line("Generated password: {$password}");
            $this->line('Change it after signing in (top-right menu → Profile).');
        }

        return self::SUCCESS;
    }
}
