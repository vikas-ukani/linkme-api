<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\User;
use App\Models\LinkedSocialAccount;
use Laravel\Socialite\Two\User as ProviderUser;

class SocialAccountsService extends Controller
{
    public function findOrCreate(ProviderUser $providerUser, string $provider): User
    {
        $linkedSocialAccount = \App\LinkedSocialAccount::where('provider_name', $provider)->where('provider_id', $providerUser->getId())
            ->first();

        if ($linkedSocialAccount) {
            return $linkedSocialAccount->user;
        } else {
            $user = null;

            if ($email = $providerUser->getEmail()) {
                $user = User::where('email', $email)->first();
            }

            if (!$user) {
                $user = User::create(['fname' => $providerUser->getName(), 'lname' => $providerUser->getName(), 'email' => $providerUser->getEmail(),]);
            }

            $user->linkedSocialAccounts()
                ->create(['provider_id' => $providerUser->getId(), 'provider_name' => $provider,]);

            return $user;
        }
    }
}
