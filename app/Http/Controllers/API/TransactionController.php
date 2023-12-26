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
use Illuminate\Support\Str;
use App\User;
use App\Paymentlogs;
use App\Bookings;
use App\Linkmeservices;
use Illuminate\Support\Facades\DB;
use Validator;
use Stripe;
use App\Services\Stripe\Seller;
use App\Services\Stripe\Transaction;
use Stripe\Charge;
use Stripe\Transfer;
use Stripe\Account;
use DateTime;
use \TimeZone;
use \DateTimeZone;
use \DateInterval;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Storage;
use Stripe\Payout;

class TransactionController extends Controller
{

    public function payaccountStatus()
    {

        $users = Auth::user();
        if (is_null($users->stripe_connect_id)) {
            $customer_base64 = base64_encode($users->id);
            $stripe_connect = config('services.stripe.connect');
            //     $stripe_callback = config('services.stripe.callback');
            $stripe_client_id = config('services.stripe.client_id');

            // $url = $stripe_connect.'?redirect_uri='.$stripe_callback.'&client_id='.$stripe_client_id.'&state='.$customer_base64.'&key='.Str::random(10);

            $url = "https://connect.stripe.com/express/oauth/v2/authorize?redirect_uri=" . $stripe_connect . "&client_id=" . $stripe_client_id . "&state=" . $customer_base64 . '&key=' . Str::random(10);

            return response()->json(['error' => "Your Stripe account not setup! Please setup stripe account using stripe connect url.", 'strip_connect_url' => $url], 401);
        } else {
            return response()->json(['success' => "Found a valid stripe account."], 200);
        }
    }

    public function saveStripeConnectId(Request $request)
    {
        if ($request->has('state') && $request->has('code')) {
            if (is_null($request->query('code'))) {
                return response()->json(['code' => 'stripe connect id cannot be empty!'], 400);
            }
            if (is_null($request->query('state'))) {
                return response()->json(['state' => 'stripe connect user id cannot be empty!'], 400);
            }

            $id = base64_decode($request->query('state'));
            $stripeconnectid = $request->query('code');
            $data = Seller::create($stripeconnectid);
            $user = User::find($id);
            $user->stripe_connect_id = $data->stripe_user_id;
            $user->save();
            dd($data);
            return response()->json(['success' => 'Your stripe account setup has been completed successfully', 'stripe_connect_id' => $data->stripe_user_id], 200);
        } else {
            return response()->json(['error' => 'There has been some issue in setting up your stripe account, kindly try again!.'], 401);
        }
    }


    public function cardsave(Request $request)
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
            $validator = Validator::make($request->all(), [
                'card_number' => 'required',
                'exp_month' => 'required',
                'exp_year' => 'required',
                'cvc' => 'required'
            ]);

            if ($validator->fails()) {
                return response()->json(['error' => $validator->errors()], 401);
            }
            try {
                if (is_null($user->stripe_customerId) || empty($user->stripe_customerId)) {
                    Stripe\Stripe::setApiKey(config('services.stripe.secret'));
                    $customer = \Stripe\Customer::create(array(
                        'name' => $name,
                        'description' => 'Payment test for Linkme',
                        'email' => $email,
                        "address" => ["city" => $city, "country" => "US", "line1" => $address, "line2" => "", "postal_code" => $zipCode, "state" => $state]
                    ));
                    $user->stripe_customerId = $customer->id;
                    User::where('id', $id)->update(['stripe_customerId' => $customer->id]);
                }

                $stripe = new \Stripe\StripeClient(config('services.stripe.secret'));
                $cards = $stripe->customers->allSources($user->stripe_customerId, ['object' => 'card', 'limit' => 5]);

                $source = $stripe->tokens->create([
                    'card' => [
                        'number' => $request->card_number,
                        'exp_month' => $request->exp_month,
                        'exp_year' => $request->exp_year,
                        'cvc' => $request->cvc
                    ],
                ]);

                $cardAlreadyUsed = false;

                if ($cards) {
                    foreach ($cards as $card) {
                        if ($card->fingerprint == $source->fingerprint) {
                            $cardAlreadyUsed = true;
                            break;
                        }
                    }
                }

                if (!$cardAlreadyUsed) {
                    $stripe->customers->createSource($user->stripe_customerId, ['source' => $source->id]);
                }
            } catch (Exception $e) {
                return response()->json([
                    'status' => false,
                    'data' => "add card to stripe failed!"
                ]);
            }
            $stripe = new \Stripe\StripeClient(config('services.stripe.secret'));
            return response()->json(['success' => 'Card added successfully.'], 200);
        }
    }

    public function cardlist()
    {
        if (Auth::check()) {
            try {
                $id = Auth::user()->id;
                $user = User::where('id', $id)->first();

                if (is_null($user->stripe_customerId)) {
                    return response()->json([
                        'status' => false,
                        'data' => "customer not created !! First Add card detail then fetch card list."
                    ]);
                }

                $stripe = new \Stripe\StripeClient(config('services.stripe.secret'));
                $card = $stripe->customers->allSources($user->stripe_customerId, ['object' => 'card', 'limit' => 10]);
            } catch (Exception $e) {
                return response()->json([
                    'status' => false,
                    'data' => "no card found"
                ]);
            }
        }
        return response()->json(['data' => $card], 200);
    }


    public function  setCardAsDefault(Request $request)
    {
        if (Auth::check()) {

            $validator = Validator::make($request->all(), [
                'cardId' => 'required'
            ]);

            if ($validator->fails()) {
                return response()->json(['error' => $validator->errors()], 401);
            }


            try {
                $id = Auth::user()->id;
                $user = User::where('id', $id)->first();

                if (is_null($user->stripe_customerId)) {
                    return response()->json([
                        'status' => false,
                        'data' => "customer not created !! First Add card detail then fetch card list."
                    ]);
                }

                $stripe = new \Stripe\StripeClient(config('services.stripe.secret'));
                $cards = $stripe->customers->allSources($user->stripe_customerId, ['object' => 'card', 'limit' => 10]);

                foreach ($cards as $card) {
                    if ($card->id == $request->get("cardId")) {
                        $customer = $stripe->customers->retrieve($user->stripe_customerId);
                        $customer->default_source = $card;
                        $customer->save();
                        break;
                    }
                }
            } catch (Exception $e) {
                return response()->json([
                    'status' => false,
                    'data' => "no card found"
                ]);
            }
        }
        return response()->json(['success' => 'Selected card is set as default'], 200);
    }

    public function holdpurchase(Request $request)
    {
        if (Auth::check()) {
            $validator = Validator::make($request->all(), [
                'bookingid' => 'required'
            ]);

            if ($validator->fails()) {
                return response()->json(['error' => $validator->errors()], 401);
            }

            try {

                $booking = Bookings::where('id', $request->bookingid)->first();
                if (is_null($booking)) {
                    return response()->json([
                        'status' => false,
                        'data' => "No booking found."
                    ]);
                }
                $amount = $booking->price;

                $users = Auth::user();
                if ($booking->customerId != $users->id) {
                    return response()->json([
                        'status' => false,
                        'data' => "Booking does not belongs to the user."
                    ]);
                }

                $customer = User::where('id', $booking->customerId)->first();
                if (is_null($customer->stripe_customerId) || empty($customer->stripe_customerId)) {
                    return response()->json([
                        'status' => false,
                        'data' => "Invalid card."
                    ]);
                }


                if (!is_null($customer->stripe_customerId)) {

                    /*Stripe\Stripe::setApiKey(config('services.stripe.secret'));
                    $charge = Charge::create([
                        'amount' => self::toStripeFormat($amount),
                        'currency' => config('services.stripe.currency'),
                        'customer' => $customer->stripe_customerId,
                        'description' => strtr("Linkme:{BookingId:@bookingId,CustomerId:@customerId,Provider:@providerId,Service:serviceId}.", ["@bookingId" => $booking->id, "@customerId" => $booking->customerId, "@providerId" => $booking->providerId, "@serviceId" => $booking->serviceId]),
                        "capture" => false,
                    ]);
                    $paymentdetails = new Paymentlogs;
                    $paymentdetails->chargeId = $charge->id;
                    $paymentdetails->bookingId = $request->bookingid;
                    $paymentdetails->CustomerId = $booking->customerId;
                    $paymentdetails->bookingamount = $amount;
	 	    $paymentdetails->fees_collected = 0;
	 	    $paymentdetails->paid_out = 0;
	 	    $paymentdetails->tip = 0;
                    $paymentdetails->save();*/
                    Bookings::where('id', $request->bookingid)->update(['status' => 1]);
                    $booking = new BookinController();
                    $booking->notifyBookingConfimation($request->bookingid);

                    return response()->json([
                        'status' => true
                        //                        'data' => $charge,
                        //                        'paymentdetails' => $paymentdetails
                    ]);
                } else {
                    return response()->json([
                        'status' => false,
                        'data' => "Not found any card of customer!!"
                    ]);
                }
            } catch (Exception $e) {
                return response()->json([
                    'status' => false,
                    'data' => "charges stripe fail!"
                ]);
            }
        }
    }

    public function captureCharges(Request $request)
    {
        $validator = Validator::make($request->all(), ['bookingid' => 'required']);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 401);
        }
        $chargedetails = Paymentlogs::where('bookingId', $request->bookingid)->first();
        if ($chargedetails) {
            try {
                $booking = Bookings::where('id', $request->bookingid)->first();
                $provider = User::where('id', $booking->providerId)->first();

                if (is_null($provider->stripe_connect_id)) {
                    return response()->json([
                        'status' => false,
                        'data' => "Service provider account not setup on stripe!!"
                    ]);
                }
                $customer = User::where('id', $booking->customerId)->first();
                if (is_null($customer->stripe_customerId)) {
                    return response()->json([
                        'status' => false,
                        'data' => "Not found any card of customer to payment!!"
                    ]);
                }
                Stripe\Stripe::setApiKey(config('services.stripe.secret'));
                $amount = $chargedetails->bookingamount;
                $payout = $amount * 0.80; // commission of admin 20 | by Rene Jacques %
                // $payout = $amount * 0.90; //comision of admin 10 %
                $tip = 0;
                $fees_collected = $amount - $payout;
                Transfer::create([
                    'amount' => self::toStripeFormat($payout),
                    "currency" => config('services.stripe.currency'),
                    "source_transaction" => $chargedetails->chargeId,
                    'destination' => $provider->stripe_connect_id
                ]);
                if (!is_null($request->tip) && $request->tip != 0) {
                    $tip = $request->tip;

                    Stripe\Stripe::setApiKey(config('services.stripe.secret'));
                    $charge = Stripe\Charge::create([
                        "customer" => $customer->stripe_customerId,
                        "amount" => self::toStripeFormat($tip),
                        "currency" => config('services.stripe.currency'),
                        "description" => "Tip for the Provider"
                    ]);
                    Transfer::create([
                        'amount' => self::toStripeFormat($tip),
                        "currency" => config('services.stripe.currency'),
                        "source_transaction" => $charge->id,
                        'destination' => $provider->stripe_connect_id
                    ]);
                    Bookings::where('id', $request->bookingid)->update(['providerTip' => $tip]);
                }
                Paymentlogs::where('chargeId', $chargedetails->chargeId)
                    ->update(['paymentstatus' => "PAYMENT", 'paid_out' => $payout, 'fees_collected' => $fees_collected, 'tip' => $tip]);

                Bookings::where('id', $request->bookingid)->update(['status' => "3"]);
                return response()->json(['status' => true, 'data' => 'Transfer successfully'], 200);
            } catch (Exception $e) {

                Paymentlogs::where('chargeId', $chargedetails->chargeId)->update(['paymentstatus' => "ERROR"]);
                return response()->json([
                    'status' => false,
                    'data' => "stripe payment transfer failed!"
                ]);
            }
        } else {
            return response()->json(['data' => "No Booking found for capture charge"], 404);
        }
    }

    public function payment($bookingid, $tip)
    {
        $user = Auth::user();
        $booking = Bookings::where('id', $bookingid)->first();

        if (empty($booking))
            return ['status' => false, 'message' => 'Booking not found.'];

        if ($booking->customerId != $user->id)
            return ['status' => false, 'message' => 'Invalid booking access.'];

        if ($booking->status == 2)
            return ['status' => false, 'message' => 'Booking is cancelled.'];

        if ($booking->status == 3 || $booking->status == 4)
            return ['status' => false, 'message' => 'Payment for the booking is already done.'];


        // Applying Charges

        $provider = User::where('id', $booking->providerId)->first();

        if (is_null($provider->stripe_connect_id)) {
            return response()->json([
                'status' => false,
                'data' => "Service provider account not setup on stripe!!"
            ]);
        }

        $customer = User::where('id', $booking->customerId)->first();
        if (is_null($customer->stripe_customerId)) {
            return response()->json([
                'status' => false,
                'data' => "Not found any card of customer to payment!!"
            ]);
        }

        Stripe\Stripe::setApiKey(config('services.stripe.secret'));
        $amount = $booking->price;
        $payout = $amount * 0.80; // commission of admin 20 | by Rene Jacques %
        // $payout = $amount * 0.90; //comision of admin 10 %
        $fees_collected = $amount - $payout;

        $charge = Charge::create([
            'amount' => (!is_null($tip) && $tip != 0) ? self::toStripeFormat($amount + $tip) : self::toStripeFormat($amount),
            'currency' => config('services.stripe.currency'),
            'customer' => $customer->stripe_customerId,
            'description' => strtr(
                config('app.name', "We Link") . ":{BookingId:@bookingId,CustomerId:@customerId,Provider:@providerId,Service:@serviceId}.",
                [
                    "@bookingId" => $booking->id,
                    "@customerId" => $booking->customerId,
                    "@providerId" => $booking->providerId,
                    "@serviceId" => $booking->serviceId
                ]
            ) . ((!is_null($tip) && $tip != 0) ? " And Tip for the Provider also included." : ""),
            'application_fee_amount' => self::toStripeFormat($amount - $payout),
            'transfer_data' => [
                'destination' => $provider->stripe_connect_id,
            ],
            'capture' => true,
        ]);

        // $charge = Charge::create([
        //     'amount' => (!is_null($tip) && $tip != 0) ? self::toStripeFormat($amount + $tip) : self::toStripeFormat($amount),
        //     'currency' => config('services.stripe.currency'),
        //     'customer' => $customer->stripe_customerId,
        //     'description' => strtr("Linkme:{BookingId:@bookingId,CustomerId:@customerId,Provider:@providerId,Service:@serviceId}.", ["@bookingId" => $booking->id, "@customerId" => $booking->customerId, "@providerId" => $booking->providerId, "@serviceId" => $booking->serviceId]) . ((!is_null($tip) && $tip != 0) ? " And Tip for the Provider also included." : ""),
        //     // 'application_fee_amount' => 1000,
        //     "capture" => true,
        // ]);

        // Transfer::create([
        //     'amount' => self::toStripeFormat($payout + $tip),
        //     "currency" => config('services.stripe.currency'),
        //     "source_transaction" => $charge->id,
        //     'destination' => $provider->stripe_connect_id
        // ]);

        // $charge = Charge::create([
        //     'amount' => self::toStripeFormat($amount),
        //     'currency' => config('services.stripe.currency'),
        //     'customer' => $customer->stripe_customerId,
        //     'description' => strtr("Linkme:{BookingId:@bookingId,CustomerId:@customerId,Provider:@providerId,Service:serviceId}.", ["@bookingId" => $booking->id, "@customerId" => $booking->customerId, "@providerId" => $booking->providerId, "@serviceId" => $booking->serviceId]),
        //     "capture" => true,
        // ]);

        // Transfer::create([
        //     'amount' => self::toStripeFormat($payout),
        //     "currency" => config('services.stripe.currency'),
        //     "source_transaction" => $charge->id,
        //     'destination' => $provider->stripe_connect_id
        // ]);


        // // Applying Tip

        // if (!is_null($tip) && $tip != 0) {

        //     Stripe\Stripe::setApiKey(config('services.stripe.secret'));
        //     $chargetip = Stripe\Charge::create([
        //         "customer" => $customer->stripe_customerId,
        //         "amount" => self::toStripeFormat($tip),
        //         "currency" => config('services.stripe.currency'),
        //         "description" => "Tip for the Provider"
        //     ]);
        //     Transfer::create([
        //         'amount' => self::toStripeFormat($tip),
        //         "currency" => config('services.stripe.currency'),
        //         "source_transaction" => $chargetip->id,
        //         'destination' => $provider->stripe_connect_id
        //     ]);
        // }

        // Updating Booking

        Bookings::where('id', $bookingid)->update(['providerTip' => $tip, 'status' => "3"]);

        // Creating Payment Log

        $paymentdetails = new Paymentlogs;
        $paymentdetails->chargeId = $charge->id;
        $paymentdetails->bookingId = $bookingid;
        $paymentdetails->CustomerId = $booking->customerId;
        $paymentdetails->bookingamount = $amount;
        $paymentdetails->fees_collected = $fees_collected;
        $paymentdetails->paid_out = $payout;
        $paymentdetails->tip = $tip;
        $paymentdetails->paymentstatus = "PAYMENT";
        $paymentdetails->save();

        return ['status' => true, 'message' => 'Booking in completed successfully.'];
    }

    public function getbalance()
    {
        $user = Auth::user();
        if (is_null($user->stripe_connect_id)) {
            return response()->json([
                'status' => false,
                'data' => "stripe account not connected!!"
            ]);
        }
        \Stripe\Stripe::setApiKey(config('services.stripe.secret'));
        $account_link = Account::createLoginLink($user->stripe_connect_id);

        //Total
        $totalFilter = ["destination" => $user->stripe_connect_id];
        $totalEarning = $this->fetchTransfer($totalFilter);

        //Monthly
        $totalMonthlyFilter = ["destination" => $user->stripe_connect_id,  "created" => ["gt" => strtotime(date('Y-m-01')), "lt" => strtotime(date('Y-m-t'))]];
        $totalMonthlyEarning = $this->fetchTransfer($totalMonthlyFilter);

        return response()->json(['status' => true, 'balance' => self::InDollar($totalEarning), 'monthBalance' => self::InDollar($totalMonthlyEarning), 'view_account' => $account_link->url], 200);
    }

    public function fetchTransfer($filter)
    {
        $totalTransfers = [];
        $hasMore = true;
        $filter['limit'] = 0;
        while ($hasMore) {
            $filter['limit'] += 10;
            $totalTransfers = \Stripe\Transfer::all($filter);
            $hasMore = $totalTransfers->has_more;
        }

        return array_sum(array_column($totalTransfers->data, 'amount'));
    }


    //Not in use
    public function refundamout(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'bookingid' => 'required',
            'charge_id' => 'required',
            'amount' => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 401);
        }

        $users = Auth::user();
        $booking = Bookings::where('id', $request->bookingid)->first();

        if (in_array($users->id, array($booking->customerId, $booking->providerId)) == false) {
            return response()->json(['error' => 'User does not have required persmission.'], 401);
        }

        try {
            $stripe = new \Stripe\StripeClient(config('services.stripe.secret'));
            $result = $stripe->refunds->create([
                'charge' => $request->charge_id,
                'amount' => self::toStripeFormat($request->amount)
            ]);

            return response()->json(['status' => true, 'data' => $result], 200);
        } catch (Exception $e) {

            print_r($e);
            return response()->json([
                'status' => false,
                'data' => "stripe refund failed!"
            ]);
        }
    }

    public static function toStripeFormat(float $amount)
    {
        return $amount * 100;
    }

    public static function InDollar(float $amount)
    {
        return number_format($amount / 100, 2, '.', ' ');
    }


    public function captureCancellationCharge($bookingid)
    {
        if (empty($bookingid))
            return ['status' => false, 'message' => 'Booking not found.'];

        $booking = Bookings::where('id', $bookingid)->first();

        if ($booking->status == 2)
            return ['status' => false, 'message' => 'Booking is already cancelled.'];

        $utcNow = new DateTime("now", new DateTimeZone("UTC"));

        $bookingUtc = new DateTime($booking->bookingStartUtc);

        $diff = $utcNow->diff($bookingUtc);
        $bookingWithin24Hours = $diff->days * 24 * 60 * 60 + $diff->h * 60 * 60 + $diff->i * 60 + $diff->s <= 24 * 60 * 60;
        $bookingDatePassed = $utcNow > $bookingUtc;

        $captureChargesInPercent = 0;
        if ($bookingWithin24Hours || $bookingDatePassed) {
            $service = Linkmeservices::where('id', $booking->serviceId)->first();
            $captureChargesInPercent = $service->after_24_cancellation;
        }


        if ($captureChargesInPercent == 0)
            return ['status' => true, 'message' => 'Provider has not configured any cancellation charges.'];


        $provider = User::where('id', $booking->providerId)->first();

        if (is_null($provider->stripe_connect_id)) {
            return response()->json([
                'status' => false,
                'data' => "Service provider account not setup on stripe!!"
            ]);
        }

        $customer = User::where('id', $booking->customerId)->first();
        if (is_null($customer->stripe_customerId)) {
            return response()->json([
                'status' => false,
                'data' => "Not found any card of customer to payment!!"
            ]);
        }

        $bookingPricePercentage = $booking->price * ($captureChargesInPercent / 100);

        Stripe\Stripe::setApiKey(config('services.stripe.secret'));
        $amount = $bookingPricePercentage;
        $payout = $amount * 0.80; // commission of admin 20 | by Rene Jacques %
        // $payout = $amount * 0.90; //comision of admin 10 %
        $fees_collected = $amount - $payout;


        $charge = Charge::create([
            'amount' => self::toStripeFormat($amount),
            'currency' => config('services.stripe.currency'),
            'customer' => $customer->stripe_customerId,
            'description' => "Cancellation Booking-" . $bookingid,
            "capture" => true,
        ]);

        $transfer = Transfer::create([
            'amount' => self::toStripeFormat($payout),
            "currency" => config('services.stripe.currency'),
            "source_transaction" => $charge->id,
            'destination' => $provider->stripe_connect_id
        ]);

        if ($transfer) {

            $paymentdetails = new Paymentlogs;
            $paymentdetails->chargeId = $charge->id;
            $paymentdetails->bookingId = $bookingid;
            $paymentdetails->CustomerId = $booking->customerId;

            $paymentdetails->bookingamount = $amount;
            $paymentdetails->fees_collected = $fees_collected;
            $paymentdetails->paid_out = $payout;
            $paymentdetails->tip = 0;
            $paymentdetails->paymentstatus = "CANCEL";
            $paymentdetails->save();

            return ['status' => true, 'message' => 'Booking cancellation charges captured successfully.'];
        } else

            return ['status' => false, 'message' => 'Booking cancellation is failed, unable to capture charges.'];
    }


    public function netPayout(Request $request)
    {
        $id = Auth::user()->id;
        $PageSize = $request->get('PageSize');

        $searchservice = Bookings::join('linkmeservices', 'linkmeservices.id', '=', 'bookings.serviceId')
            ->join('paymentlogs', 'bookingId', '=', 'bookings.Id')
            ->join('users as provider', 'provider.id', '=', 'bookings.providerId')
            ->join('users as customer', 'customer.id', '=', 'bookings.customerId')
            ->select(
                'customer.fname',
                'customer.lname',
                'customer.avatar',
                'customer.address',
                'linkmeservices.title',
                'bookings.price',
                'bookings.providerTip',
                'bookings.booked_at',
                'paymentlogs.fees_collected as linkmefee',
                'paymentlogs.paymentstatus',
                'paymentlogs.bookingamount as amountpaid',
                DB::raw("paymentlogs.paid_out+paymentlogs.tip as providerprofit")
            )
            ->where('providerId', '=', $id)
            ->where('provider.user_type', '=', '1')
            ->whereIn('status', [2, 3, 4])
            ->whereIn('paymentstatus', ['PAYMENT', 'CANCEL'])
            ->orderBy('paymentlogs.created_at', 'DESC')
            ->paginate($PageSize);

        return response()->json(['data' => $searchservice]);
    }

    public function captureChargesV2(Request $request)
    {
        $validator = Validator::make($request->all(), ['bookingid' => 'required']);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 401);
        }
        $chargedetails = Paymentlogs::where('bookingId', $request->bookingid)->first();
        if ($chargedetails) {
            try {
                $booking = Bookings::where('id', $request->bookingid)->first();
                $provider = User::where('id', $booking->providerId)->first();

                if (is_null($provider->stripe_connect_id)) {
                    return response()->json([
                        'status' => false,
                        'data' => "Service provider account not setup on stripe!!"
                    ]);
                }
                $customer = User::where('id', $booking->customerId)->first();
                if (is_null($customer->stripe_customerId)) {
                    return response()->json([
                        'status' => false,
                        'data' => "Not found any card of customer to payment!!"
                    ]);
                }
                Stripe\Stripe::setApiKey(config('services.stripe.secret'));
                $amount = $chargedetails->bookingamount;
                $payout = $amount * 0.80; // commission of admin 20 | by Rene Jacques %
                // $payout = $amount * 0.90; //comision of admin 10 %
                $tip = 0;
                $fees_collected = $amount - $payout;
                $tip = $request->tip;
                Transfer::create([
                    'amount' => (!is_null($request->tip) && $request->tip != 0) ? (self::toStripeFormat($payout) + self::toStripeFormat($tip)) : self::toStripeFormat($payout),
                    "currency" => config('services.stripe.currency'),
                    "source_transaction" => $chargedetails->chargeId,
                    'destination' => $provider->stripe_connect_id
                ]);
                Bookings::where('id', $request->bookingid)->update(['providerTip' => $tip]);
                // if (!is_null($request->tip) && $request->tip != 0) {
                //     $tip = $request->tip;

                //     Stripe\Stripe::setApiKey(config('services.stripe.secret'));
                //     $charge = Stripe\Charge::create([
                //         "customer" => $customer->stripe_customerId,
                //         "amount" => self::toStripeFormat($tip),
                //         "currency" => config('services.stripe.currency'),
                //         "description" => "Tip for the Provider"
                //     ]);
                //     Transfer::create([
                //         'amount' => self::toStripeFormat($tip),
                //         "currency" => config('services.stripe.currency'),
                //         "source_transaction" => $charge->id,
                //         'destination' => $provider->stripe_connect_id
                //     ]);
                //     Bookings::where('id', $request->bookingid)->update(['providerTip' => $tip]);
                // }
                Paymentlogs::where('chargeId', $chargedetails->chargeId)
                    ->update(['paymentstatus' => "PAYMENT", 'paid_out' => $payout, 'fees_collected' => $fees_collected, 'tip' => $tip]);

                Bookings::where('id', $request->bookingid)->update(['status' => "3"]);
                return response()->json(['status' => true, 'data' => 'Transfer successfully'], 200);
            } catch (Exception $e) {

                Paymentlogs::where('chargeId', $chargedetails->chargeId)->update(['paymentstatus' => "ERROR"]);
                return response()->json([
                    'status' => false,
                    'data' => "stripe payment transfer failed!"
                ]);
            }
        } else {
            return response()->json(['data' => "No Booking found for capture charge"], 404);
        }
    }
}
