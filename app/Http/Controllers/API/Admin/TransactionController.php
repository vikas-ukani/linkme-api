<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Stripe\StripeClient;

class TransactionController extends Controller
{
    protected $stripeClient;

    /**
     * Initialization Instances.
     */
    public function __construct()
    {
        // Create Stripe client
        // $this->stripeClient = new StripeClient(config('services.stripe.key'));
        $this->stripeClient = new StripeClient(config('services.stripe.secret'));
    }


    public function index(Request $request)
    {
        try {
            $limit = $request->get('limit') ?? 10;
            // $transactions = $this->stripeClient->payouts->all(['limit' => 100]);
            $transactions = $this->stripeClient->paymentIntents->all(['limit' => $limit]);
            $transactions = $this->stripeClient->balanceTransactions->all(['limit' => $limit]);
            // $transactions = $this->stripeClient->charges->all(['limit' => 100]);
            // $transactions = $this->stripeClient->issuing->transactions->all([
            //     'limit' => 3,
            // ]);
            // dd($transactions->toArray());
            return response()->json($transactions, 200);
        } catch (Exception $ex) {
            return response()->json([
                'data' => null,
                'message' => $ex->getMessage()
            ]);
        }
    }
}
