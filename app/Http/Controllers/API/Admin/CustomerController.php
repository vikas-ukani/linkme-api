<?php

namespace App\Http\Controllers\API\Admin;

use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\Admin\CreateCustomerRequest;
use App\Http\Requests\Admin\UpdateCustomerRequest;
use App\Notifications\UserRegistrationNotification;

class CustomerController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        //$id = Auth::user()->id;
        //$usertype = User::where('id', $id)->first();
        $search_data = $request->get('data');
        $PageSize = $request->get('PageSize');
        $sort = $request->get('sort');

        if ($search_data)
            $search_data = '%' . $search_data . '%';
        if (isset($sort)) {
            $sort = explode(":", $sort);
        }

        $users = User::select('id', 'fname', 'lname', 'email', 'phone', 'address', 'city', 'state', 'zipcode', 'avatar', 'created_at', 'user_rating', 'user_reviewcount', 'active')
            ->where('user_type', '=', 0)
            ->withCount(['bookings'])
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
            ->orderBy(
                isset($sort[0]) ? $sort[0] : 'created_at',
                isset($sort[1]) ? $sort[1] : 'desc'
            )
            ->paginate($PageSize);
        return response()
            ->json(['user' => $users], 201);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(CreateCustomerRequest $request)
    {
        $data = $request->validated();

        // Sample Password
        $data['password'] = Hash::make('password');
        $data['user_type'] = 0; // Customer type is 0. 
        $data['email_verified_at'] = Carbon::now();
        $customer = User::create($data);
        // Send Email Notification to User
        try {
            $customer->notify(new UserRegistrationNotification($customer));
        } catch (\Exception $ex) {
            \Log::error("Customer Registration Notification Error : {$ex->getMessage()}");
        }
        return response()->json([
            'status' => 'success',
            'data' => $customer,
            'message' => "Customer has been created"
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        return response()->json([
            'status' => 'success',
            'data' => User::where('id', $id)->first(),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateCustomerRequest $request, $id)
    {
        User::where('id', $id)->update($request->validated());
        return response()->json([
            'status' => 'success',
            'data' => null,
            'message' => "Customer has been updated"
        ]);
    }


    /**
     * Update Customer status partial data
     *
     * @param Int $id
     * @param Request $request
     * @return mixed
     */
    public function partialUpdates($id, Request $request)
    {
        User::where('id', $id)->update([
            'active' => $request->active == 1
        ]);
        return response()->json([
            'status' => 'success',
            'data' => null,
            'message' => 'Status has been updated.'
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        User::where('id', $id)->delete();
        return response()->json([
            'status' => 'success',
            'data' => null,
            'message' => "Customer has been removed"
        ]);
    }
}
