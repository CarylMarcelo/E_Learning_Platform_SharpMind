<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class CreateAdministrator extends Command
{
    protected $signature = 'app:create-admin';

    protected $description = 'Create a workspace administrator without changing any existing account';

    public function handle(): int
    {
        $name = $this->ask('Administrator name');
        $email = $this->ask('Administrator email');
        $password = $this->secret('Password (at least 12 characters)');
        $confirmation = $this->secret('Confirm password');
        $values = [
            'name' => is_string($name) ? trim($name) : $name,
            'email' => is_string($email) ? Str::lower(trim($email)) : $email,
            'password' => $password,
            'password_confirmation' => $confirmation,
        ];
        $validator = Validator::make($values, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:12', 'confirmed'],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->error($message);
            }

            return self::INVALID;
        }

        User::create($validator->safe()->only(['name', 'email', 'password']));
        $this->info('Administrator created. Sign in using the email and password you entered.');

        return self::SUCCESS;
    }
}
