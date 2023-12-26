<?php

namespace App\Http\Controllers\API;

use App\User;
use App\Admin;
use Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class AdminController extends Controller
{
    /**
     * adminLogin
     *
     * @param  mixed $request
     * @return void
     */
    public  function adminLogin(Request $request)
    {
        $validator = Validator::make($request->all(), ['email' => 'required', 'password' => 'required']);

        if ($validator->fails()) {
            return response()->json($validator->errors()->toJson(), 400);
        }

        if (auth()->guard('admin')->attempt(['email' => request('email'), 'password' => request('password')])) {
            config(['auth.guards.api.provider' => 'admin']);
            $admin = Admin::select('id', 'name', 'email')->find(auth()->guard('admin')->user()->id);
            $success =  $admin;
            $success['token'] =  $admin->createToken('Admin MyApp', ['admin'])->accessToken;

            return $this->returnResponse($success, 200);
        } else {
            return $this->returnResponse(['error' => ['Invalid email or password']], 400);
        }
    }


    /* Dashboard */
    /**
     * activeUsersCount
     *
     * @param  mixed $request
     * @return void
     */
    public function activeUsersCount(Request $request)
    {
        $users = User::select('user_type', DB::raw('count(user_type) as total'))
            ->whereIn('user_type', array(0, 1))
            ->whereNotNull('email_verified_at')
            ->groupBy('user_type')
            ->get();
        return $users;
    }

    /**
     * platformEarning
     *
     * @param  mixed $request
     * @return void
     */
    public function platformEarning(Request $request)
    {
        if (config('database.default') == 'mysql') {
            $earning = DB::table('paymentlogs')
                ->select(
                    DB::raw('SUM(fees_collected) as linkme_earning'),
                    DB::raw('SUM(paid_out) as provider_earning'),
                    DB::raw('SUM(tip) as provider_tips'),
                    DB::raw('SUM(bookingamount) as total_earning')
                )
                ->get();
            return $earning;
        } else {
            $earning = DB::table('paymentlogs')
                ->select(
                    // ::int added, because it will throw error for postgres database
                    DB::raw('SUM(fees_collected::int) as linkme_earning'),
                    DB::raw('SUM(paid_out::int) as provider_earning'),
                    DB::raw('SUM(tip::int) as provider_tips'),
                    DB::raw('SUM(bookingamount::int) as total_earning')
                )
                ->get();
            return $earning;
        }
    }

    /**
     * totalBookings
     *
     * @param  mixed $request
     * @return void
     */
    public function totalBookings(Request $request)
    {
        //$id = Auth::user()->id;
        //        $usertype = User::where('id', $id)->first();

        $booking = DB::table('bookings')
            ->select(DB::raw('count(*) as bookings'))
            ->get();
        return $booking;
    }



    /**
     * topCustomers
     *
     * @param  mixed $request
     * @return void
     */
    public function topCustomers(Request $request)
    {
        if (config('database.default') == 'mysql') {
            $customers = DB::table('paymentlogs')
                ->join('bookings', 'bookings.id', '=', 'paymentlogs.bookingId')
                ->join('users', 'users.id', '=', 'bookings.customerId')
                ->select('fname', 'lname', 'email', DB::raw('SUM(bookingamount) as total_paid'))
                ->groupBy('fname', 'lname', 'email')
                ->orderByRaw('SUM(bookingamount) DESC')
                ->limit(5)
                ->get();
        } else {
            $customers = DB::table('paymentlogs')
                ->join('bookings', 'bookings.id', '=', 'paymentlogs.bookingId')
                ->join('users', 'users.id', '=', 'bookings.customerId')
                // ::int added, because it will throw error for postgres database
                ->select('fname', 'lname', 'email', DB::raw('SUM(bookingamount::int) as total_paid'))
                ->groupBy('fname', 'lname', 'email')
                // ::int added, because it will throw error for postgres database
                ->orderByRaw('SUM(bookingamount::int) DESC')
                ->limit(5)
                ->get();
        }

        return $customers;
    }

    /**
     * topServices
     *
     * @param  mixed $request
     * @return void
     */
    public function topServices(Request $request)
    {
        if (config('database.default') == 'mysql') {
            return DB::table('paymentlogs')
                ->join('bookings', 'bookings.id', '=', 'paymentlogs.bookingId')
                ->join('linkmeservices', 'linkmeservices.provider_id', '=', 'bookings.providerId')
                ->join('users', 'users.id', '=', 'bookings.providerId')
                ->select(
                    'linkmeservices.title',
                    'linkmeservices.category',
                    'linkmeservices.price',
                    'linkmeservices.avg_rating',
                    'users.fname',
                    'users.lname',
                    DB::raw('SUM(bookingamount) as total_paid')
                )
                ->groupBy('title', 'category', 'price', 'avg_rating', 'fname', 'lname')
                ->orderByRaw('SUM(bookingamount) DESC')
                ->limit(5)
                ->get();
        } else {
            return DB::table('paymentlogs')
                ->join('bookings', 'bookings.id', '=', 'paymentlogs.bookingId')
                ->join('linkmeservices', 'linkmeservices.provider_id', '=', 'bookings.providerId')
                ->join('users', 'users.id', '=', 'bookings.providerId')
                ->select(
                    'linkmeservices.title',
                    'linkmeservices.category',
                    'linkmeservices.price',
                    'linkmeservices.avg_rating',
                    'users.fname',
                    'users.lname',
                    DB::raw('SUM(bookingamount) as total_paid')
                )
                ->groupBy('title', 'category', 'price', 'avg_rating', 'fname', 'lname')
                ->orderByRaw('SUM(bookingamount) DESC')
                ->limit(5)
                ->get();
        }
    }


    /**
     * topProviders
     *
     * @param  mixed $request
     * @return void
     */
    public function topProviders(Request $request)
    {
        if (config('database.default') == 'mysql') {
            $providers = DB::table('paymentlogs')
                ->join('bookings', 'bookings.id', '=', 'paymentlogs.bookingId')
                ->join('users', 'users.id', '=', 'bookings.providerId')
                ->select('fname', 'lname', 'email', DB::raw('sum(paid_out+tip) as total_earned'))
                ->groupBy('fname', 'lname', 'email')
                ->orderByRaw('SUM(paid_out+tip) DESC')
                ->limit(5)
                ->get();
        } else {
            $providers = DB::table('paymentlogs')
                ->join('bookings', 'bookings.id', '=', 'paymentlogs.bookingId')
                ->join('users', 'users.id', '=', 'bookings.providerId')
                // ::int added, because it will throw error for postgres database
                ->select('fname', 'lname', 'email', DB::raw('sum(paid_out::int+tip::int) as total_earned'))
                ->groupBy('fname', 'lname', 'email')
                // ::int added, because it will throw error for postgres database
                ->orderByRaw('SUM(paid_out::int+tip::int) DESC')
                ->limit(5)
                ->get();
        }

        return $providers;
    }


    /**
     * getCustomers
     *
     * @param  mixed $request
     * @return void
     */
    public function getCustomers(Request $request)
    {
        //$id = Auth::user()->id;
        //$usertype = User::where('id', $id)->first();
        $search_data = $request->get('data');
        $PageSize = $request->get('PageSize');

        if ($search_data)
            $search_data = '%' . $search_data . '%';

        $users = User::select('id', 'fname', 'lname', 'email', 'phone', 'address', 'city', 'state', 'zipcode', 'avatar', 'created_at', 'user_rating', 'user_reviewcount')
            ->where('user_type', '=', 0)
            ->where(function ($query) use ($search_data) {
                $query->where('fname', 'LIKE', $search_data)
                    ->orWhere('lname', 'LIKE', $search_data)
                    ->orWhere('email', 'LIKE', $search_data)
                    ->orWhere('phone', 'LIKE', $search_data)
                    ->orWhere('address', 'LIKE', $search_data)
                    ->orWhere('city', 'LIKE', $search_data)
                    ->orWhere('state', 'LIKE', $search_data)
                    ->orWhere('zipcode', 'LIKE', $search_data);
            })
            ->orderBy('created_at', 'desc')
            ->paginate($PageSize);
        return response()
            ->json(['user' => $users], 201);
    }

    /**
     * getBookings
     *
     * @param  mixed $request
     * @return void
     */
    public function getBookings(Request $request)
    {
        //$id = Auth::user()->id;
        //        $usertype = User::where('id', $id)->first();

        $search_data = $request->get('data');
        $start_date = $request->get('start_date');
        $end_date = $request->get('end_date');
        $PageSize = $request->get('PageSize');

        if ($search_data)
            $search_data = '%' . $search_data . '%';

        $booking = DB::table('bookings')->join('linkmeservices', 'linkmeservices.id', '=', 'bookings.serviceId')
            ->join('users as provider', 'provider.id', '=', 'bookings.providerId')
            ->join('users as customer', 'customer.id', '=', 'bookings.customerId')
            ->select(
                'bookings.id', // Id for edit booking on admin panel
                'bookings.booked_at',
                'bookings.start_at',
                'bookings.created_at',
                'linkmeservices.title as service_name',
                'linkmeservices.service_img as service_avatar',
                'linkmeservices.duration',
                'provider.avatar as provider_avatar',
                'customer.avatar as customer_avatar',
                DB::RAW("concat(provider.fname,' ',provider.lname) as providerName"),
                DB::RAW("concat(customer.fname,' ',customer.lname) as customerName"),
                DB::RAW("CASE bookings.status WHEN 0 THEN 'NEW' WHEN 1 THEN 'NEW' WHEN 2 THEN 'CANCELED' ELSE 'COMPLETED' END as status")
            )
            ->where(function ($query) use ($search_data) {

                if ($search_data) {
                    $query->Where('title', 'LIKE', $search_data)
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
            ->orderBy('bookingStartUtc', 'desc')
            ->paginate($PageSize);


        return response()
            ->json(['user' => $booking], 201);
    }
}
