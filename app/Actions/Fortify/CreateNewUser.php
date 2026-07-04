<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\Business;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    /**
     * Validate and create a newly registered user together with their business.
     *
     * Each registration provisions a new tenant: a Business row holding the
     * account's own HikBridge API key, plus the User that belongs to it.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            ...$this->profileRules(),
            'password' => $this->passwordRules(),
            'business_name' => ['required', 'string', 'max:255'],
            'hik_api_key' => ['required', 'string', 'max:255', 'starts_with:hbk_'],
        ], [
            'hik_api_key.starts_with' => 'The HikBridge API key must start with "hbk_".',
        ])->validate();

        return DB::transaction(function () use ($input): User {
            $business = Business::create([
                'name' => $input['business_name'],
                'hik_api_key' => $input['hik_api_key'],
            ]);

            return User::create([
                'business_id' => $business->id,
                'name' => $input['name'],
                'email' => $input['email'],
                'password' => $input['password'],
            ]);
        });
    }
}
