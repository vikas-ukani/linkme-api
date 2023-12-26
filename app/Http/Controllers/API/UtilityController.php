<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;


class UtilityController extends Controller
{
    public function getStatus()
    {
        if (empty(env('DB_DATABASE')) || empty(env('DB_USERNAME')) || empty(env('DB_PASSWORD')))
            return $this->returnResponse(['error' => 'Database not configure'], 400);
        elseif (empty(env('MAIL_MAILER')) || empty(env('MAIL_HOST')) || empty(env('MAIL_PORT')) || empty(env('MAIL_USERNAME')) || empty(env('MAIL_PASSWORD')) || empty(env('MAIL_ENCRYPTION')) || empty(env('MAIL_FROM_ADDRESS')))
            return $this->returnResponse(['error' => 'SMTP not configure'], 400);
        elseif (empty(env('STRIPE_KEY')) || empty(env('STRIPE_SECRET')) || empty(env('STRIPE_CONNECT')))
            return $this->returnResponse(['error' => 'Stripe not configure'], 400);
        elseif (empty(env('TWILIO_ACCOUNT_SID')) || empty(env('TWILIO_AUTH_TOKEN')) || empty(env('TWILIO_SERVICE_SID')) || empty(env('TWILIO_API_KEY_SID')) || empty(env('TWILIO_API_KEY_SECRET')))
            return $this->returnResponse(['error' => 'TWILIO not configure'], 400);
        elseif (empty(env('AWS_ACCESS_KEY_ID')) || empty(env('AWS_SECRET_ACCESS_KEY')) || empty(env('AWS_DEFAULT_REGION')) || empty(env('AWS_BUCKET')) || empty(env('AWS_URL')))
            return $this->returnResponse(['error' => 'AWS not configure'], 400);
        else return $this->returnResponse(['Success' => 'All setup done'], 200);
    }
}
