<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentLogController extends Controller
{

    /**
     * getPaymentlogs converted to index
     * Display a listing of the resource.
     * @param  mixed $request
     * @return void
     */
    public function index(Request $request)
    {
        // $id = Auth::user()->id;
        // $usertype = User::where('id', $id)->first();
        $search_data = $request->get('data');
        $start_date = $request->get('start_date');
        $end_date = $request->get('end_date');
        $PageSize = $request->get('PageSize');

        if ($search_data)
            $search_data = '%' . $search_data . '%';
        $booking = DB::table('bookings')->join('linkmeservices', 'linkmeservices.id', '=', 'bookings.serviceId')
            ->join('paymentlogs as payment', 'payment.bookingId', '=', 'bookings.id')
            ->join('users as provider', 'provider.id', '=', 'bookings.providerId')
            ->join('users as customer', 'customer.id', '=', 'bookings.customerId')
            ->select(
                'bookings.price',
                'payment.id',
                'payment.bookingamount',
                'payment.paymentstatus',
                'bookings.booked_at',
                'bookings.start_at',
                'bookings.created_at',
                'payment.created_at as paymentdate',
                'payment.tip',
                'payment.fees_collected as fee',
                'payment.gateway_fees',
                'payment.paid_out as netpayment',
                'payment.stripe_fixed',
                'linkmeservices.title as service_name',
                'linkmeservices.service_img as service_avatar',
                'provider.avatar as provider_avatar',
                'customer.avatar as customer_avatar',
                DB::RAW("concat(provider.fname,' ',provider.lname) as providerName"),
                DB::RAW("concat(customer.fname,' ',customer.lname) as customerName"),
                DB::RAW("CASE bookings.status WHEN 0 THEN 'NEW' WHEN 1 THEN 'NEW' WHEN 2 THEN 'CANCELED' ELSE 'COMPLETED' END as status")
            )
            ->where(function ($query) use ($search_data) {
                if ($search_data) {
                    $query->where('bookingamount', 'LIKE', $search_data)
                        ->orWhere('paymentstatus', 'LIKE', $search_data)
                        ->orWhere('title', 'LIKE', $search_data)
                        ->orWhere('customer.fname', 'LIKE', $search_data)
                        ->orWhere('customer.lname', 'LIKE', $search_data)
                        ->orWhere('provider.fname', 'LIKE', $search_data)
                        ->orWhere('provider.lname', 'LIKE', $search_data);
                }
            })
            ->where(function ($query) use ($start_date, $end_date) {
                if ($start_date  && $end_date) {
                    $query->whereBetween('booked_at', [$start_date, $end_date]);
                }
            })
            ->orderBy('payment.created_at', 'desc')
            ->paginate($PageSize);
        return response()->json(['user' => $booking], 200);
    }

    public function generateReport(Request $request)
    {
        
    }
}
