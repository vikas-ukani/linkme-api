<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Controllers\API\PushNotifyController;
use App\Http\Controllers\API\BookinController;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Support\Facades\Auth;
use App\User;
use App\Paymentlogs;
use App\Bookings;
use Illuminate\Support\Facades\DB;
use Validator;
use Stripe;

class PaymentController extends Controller
{

    public function Paymenthold(Request $request)
    {

        if (Auth::check()) {
            $id = Auth::user()->id;
            $user = User::where('id', $id)->first();
            $name = str_pad($user->fname, 8) . $user->lname;
            $email = $user->email;
            $city = $user->city;
            $address = $user->address;
            $zipCode = $user->zipcode;
            $state = $user->state;
            $validator = Validator::make($request->all(), ['bookingid' => 'required', 'bookingamount' => 'required', 'currency' => 'required', 'card_number' => 'required', 'exp_month' => 'required', 'exp_year' => 'required', 'cvc' => 'required']);

            if ($validator->fails()) {
                return response()
                    ->json(['error' => $validator->errors()], 401);
            }

            try {

                $stripe = new \Stripe\StripeClient('sk_test_51IG1QdBMbRuPGTUJ9PvSMd6aNBscozSFhunfNPHtMxKEPG1ShzcNkeyma4L0MMsRpfl7SCSo25tbeTl1SEyNKThm00e9ZfpHaK');

                $source = $stripe
                    ->tokens
                    ->create(['card' => ['number' => $request->card_number, 'exp_month' => $request->exp_month, 'exp_year' => $request->exp_year, 'cvc' => $request->cvc,],]);

                Stripe\Stripe::setApiKey('sk_test_51IG1QdBMbRuPGTUJ9PvSMd6aNBscozSFhunfNPHtMxKEPG1ShzcNkeyma4L0MMsRpfl7SCSo25tbeTl1SEyNKThm00e9ZfpHaK');

                $customer = \Stripe\Customer::create(array(
                    'name' => $name,
                    'description' => 'Payment test for Linkme',
                    'email' => $email,
                    'source' => $source->id,
                    "address" => [
                        "city" => $city,
                        "country" => "US",
                        "line1" => $address,
                        "line2" => "",
                        "postal_code" => $zipCode,
                        "state" => $state
                    ]
                ));

                User::where('id', $id)->update(['stripe_customerId' => $customer->id]);

                $charge = Stripe\Charge::create(["customer" => $customer->id, "amount" => $request->bookingamount, "currency" => $request->currency, "description" => "Payment test for Linkme", "capture" => false,]);

                $paymentdetails = new Paymentlogs;

                $paymentdetails->chargeId = $charge->id;
                $paymentdetails->bookingId = $request->bookingid;
                $paymentdetails->CustomerId = $id;
                $paymentdetails->bookingamount = $charge->amount;
                $paymentdetails->save();

                Bookings::where('id', $request->bookingid)
                    ->update(['status' => 1]);

                $booking = new BookinController();

                $booking->notifyBookingConfimation($request->bookingid);

                return response()
                    ->json(['status' => true, 'data' => $charge, 'paymentdetails' => $paymentdetails]);
            } catch (\Stripe\Exception\CardException $e) {
                return response()->json(['status' => false, 'data' => "charges stripe fail!", 'status code' => $e->getHttpStatus(), 'Type' => $e->getError()->type, 'Code' => $e->getError()->code, 'Param' => $e->getError()->param, 'Message' => $e->getError()->message], $e->getHttpStatus());
            }

            // get all error message
            foreach ($validator->messages()
                ->toArray() as $key => $msg) {
                $messages[$key] = reset($msg);
            }

            return response()->json(['status' => false, 'data' => $messages], 412);
        }
    }

    public function captureCharge(Request $request)
    {

        $validator = Validator::make($request->all(), ['bookingid' => 'required']);

        if ($validator->fails()) {
            return response()
                ->json(['error' => $validator->errors()], 401);
        }

        $chargedetails = Paymentlogs::where('bookingId', $request->bookingid)
            ->first();

        if ($chargedetails) {
            try {

                $stripe = new \Stripe\StripeClient('sk_test_51IG1QdBMbRuPGTUJ9PvSMd6aNBscozSFhunfNPHtMxKEPG1ShzcNkeyma4L0MMsRpfl7SCSo25tbeTl1SEyNKThm00e9ZfpHaK');

                $capture = $stripe
                    ->charges
                    ->capture($chargedetails->chargeId);

                Paymentlogs::where('chargeId', $chargedetails->chargeId)
                    ->update(['paymentstatus' => "captured"]);

                Bookings::where('id', $request->bookingid)
                    ->update(['status' => "3"]);

                if ($request->tip) {
                    $user = auth('api')->user();
                    $customer = User::where('id', $user->id)
                        ->first();

                    Stripe\Stripe::setApiKey('sk_test_51IG1QdBMbRuPGTUJ9PvSMd6aNBscozSFhunfNPHtMxKEPG1ShzcNkeyma4L0MMsRpfl7SCSo25tbeTl1SEyNKThm00e9ZfpHaK');
                    $charge = Stripe\Charge::create(["customer" => $customer->stripe_customerId, "amount" => $request->tip, "currency" => $request->currency, "description" => "Tip for the Provider"]);

                    Bookings::where('id', $request->bookingid)
                        ->update(['providerTip' => $request->tip]);
                }

                return response()
                    ->json(['status' => true, 'data' => $capture], 200);
            } catch (Exception $e) {

                Paymentlogs::where('chargeId', $chargedetails->chargeId)
                    ->update(['paymentstatus' => "error"]);

                return response()
                    ->json(['status' => false, 'data' => "charges stripe fail!"]);
            }
        } else {
            return response()
                ->json(['data' => "No Booking found for capture charge"], 404);
        }
    }

    public function Savedcard()
    {
        if (Auth::check()) {
            $id = Auth::user()->id;
            $user = User::where('id', $id)->first();
            $stripe = new \Stripe\StripeClient('sk_test_51IG1QdBMbRuPGTUJ9PvSMd6aNBscozSFhunfNPHtMxKEPG1ShzcNkeyma4L0MMsRpfl7SCSo25tbeTl1SEyNKThm00e9ZfpHaK');
            $card = $stripe
                ->customers
                ->allSources($user->stripe_customerId, ['object' => 'card', 'limit' => 3]);
        }
        return response()
            ->json(['data' => $card], 200);
    }

    public function addcard(Request $request)
    {
        if (Auth::check()) {
            $id = Auth::user()->id;
            $user = User::where('id', $id)->first();

            $name = str_pad($user->fname, 8) . $user->lname;
            $email = $user->email;
            $city = $user->city;
            $address = $user->address;
            $zipCode = $user->zipcode;
            $state = $user->state;
            $validator = Validator::make($request->all(), ['card_number' => 'required', 'exp_month' => 'required', 'exp_year' => 'required', 'cvc' => 'required']);

            if ($validator->fails()) {
                return response()
                    ->json(['error' => $validator->errors()], 401);
            }

            try {

                $stripe = new \Stripe\StripeClient('sk_test_51IG1QdBMbRuPGTUJ9PvSMd6aNBscozSFhunfNPHtMxKEPG1ShzcNkeyma4L0MMsRpfl7SCSo25tbeTl1SEyNKThm00e9ZfpHaK');

                $source = $stripe
                    ->tokens
                    ->create(['card' => ['number' => $request->card_number, 'exp_month' => $request->exp_month, 'exp_year' => $request->exp_year, 'cvc' => $request->cvc,],]);
                if ($user->stripe_customerId) {
                    $stripe
                        ->customers
                        ->createSource($user->stripe_customerId, ['source' => $source->id]);
                } else {

                    Stripe\Stripe::setApiKey('sk_test_51IG1QdBMbRuPGTUJ9PvSMd6aNBscozSFhunfNPHtMxKEPG1ShzcNkeyma4L0MMsRpfl7SCSo25tbeTl1SEyNKThm00e9ZfpHaK');
                    $customer = \Stripe\Customer::create(array(
                        'name' => $name,
                        'description' => 'Payment test for Linkme',
                        'email' => $email,
                        'source' => $source->id,
                        "address" => [
                            "city" => $city,
                            "country" => "US",
                            "line1" => $address,
                            "line2" => "",
                            "postal_code" => $zipCode,
                            "state" => $state
                        ]
                    ));
                    User::where('id', $id)->update(['stripe_customerId' => $customer->id]);
                }
            } catch (Exception $e) {
                return response()->json(['status' => false, 'data' => "add card to stripe fail!"]);
            }

            $stripe = new \Stripe\StripeClient('sk_test_51IG1QdBMbRuPGTUJ9PvSMd6aNBscozSFhunfNPHtMxKEPG1ShzcNkeyma4L0MMsRpfl7SCSo25tbeTl1SEyNKThm00e9ZfpHaK');
            $card = $stripe
                ->customers
                ->allSources($user->stripe_customerId, ['object' => 'card', 'limit' => 3]);

            return response()
                ->json(['data' => $card], 200);
        }
    }
}
