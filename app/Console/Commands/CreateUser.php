<?php

namespace App\Console\Commands;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

#[Signature('user:create {name} {email} {--color=#6b7280 : Hex colour used to identify the user}')]
#[Description('Create a verified user')]
class CreateUser extends Command
{
    use PasswordValidationRules;
    use ProfileValidationRules;

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $plainPassword = $this->secret('Password');
        $passwordConfirmation = $this->secret('Confirm password');

        $validator = Validator::make([
            'name' => $this->argument('name'),
            'email' => $this->argument('email'),
            'color' => $this->option('color'),
            'password' => $plainPassword,
            'password_confirmation' => $passwordConfirmation,
        ], [
            ...$this->profileRules(),
            'password' => $this->passwordRules(),
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->error($message);
            }

            return self::FAILURE;
        }

        $user = User::create([
            'name' => $this->argument('name'),
            'email' => $this->argument('email'),
            'color' => $this->option('color'),
            'password' => $plainPassword,
        ]);

        $user->forceFill(['email_verified_at' => now()])->save();

        $this->info("User created with id {$user->id}.");

        return self::SUCCESS;
    }
}
