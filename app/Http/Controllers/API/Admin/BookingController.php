<?php

namespace App\Http\Controllers\API\Admin;

use App\Bookings;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateBookingRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BookingController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
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
                'bookings.price',
                'bookings.providerTip',
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

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $booking = Bookings::where('id', $id)
            ->with([
                'customer' => function ($query) {
                    return $query->select('id', 'fname', 'lname');
                },
                'provider' => function ($query) {
                    return $query->select('id', 'fname', 'lname');
                },
                'service' => function ($query) {
                    return $query->select('id', 'title');
                },
            ])->first();
        return response()->json($booking);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update($id, UpdateBookingRequest $request)
    {
        Bookings::where('id', $id)->update($request->validated());
        return response()->json([
            'status' => 'success',
            'message' => 'Booking has been updated.'
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $booking = Bookings::where('id', $id)->delete();
        return response()->json([
            'status' => $booking ? 'success' : 'error',
            'data' => $booking,
            'message' => $booking ? "Booking has been deleted." : 'Booking not found.'
        ]);
    }
}
