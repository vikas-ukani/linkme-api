<?php

namespace App\Http\Controllers\API\Admin;

use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\Admin\ProviderCreateRequest;
use App\Http\Requests\Admin\ProviderUpdateRequest;
use App\Notifications\UserRegistrationNotification;

class ProviderController extends Controller
{

    /**
     * getProviders
     * Display a listing of the resource.
     * @param  mixed $request
     * @return void
     */
    public function index(Request $request)
    {
        //        $id = Auth::user()->id;
        //        $usertype = User::where('id', $id)->first();

        $search_data = $request->get('data');
        $PageSize = $request->get('PageSize');
        $sort = $request->get('sort');

        if ($search_data)
            $search_data = '%' . $search_data . '%';
        if (isset($sort))
            $sort = explode(":", $sort);

        $users = User::where('user_type', '=', 1)
            ->withCount(['services'])
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
     * @param ProviderCreateRequest $request
     * @return void
     */
    public function store(ProviderCreateRequest $request)
    {
        $data = $request->validated();

        // Sample Password
        $data['password'] = Hash::make('password');
        $data['user_type'] = 1; // Provider type is 1. 
        $data['email_verified_at'] = Carbon::now();
        $provider = User::create($data);
        // Send Email Notification to User
        try {
            $provider->notify(new UserRegistrationNotification($provider));
        } catch (\Exception $ex) {
            \Log::error("Provider Registration Notification Error : {$ex->getMessage()}");
        }
        return response()->json([
            'status' => 'success',
            'data' => $provider,
            'message' => "Provider has been created"
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
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(ProviderUpdateRequest $request, $id)
    {
        $data = $request->validated();
        if (isset($data['category']) && is_string($data['category'])) {
            $data['category'] = [$data['category']];
        }
        User::where('id', $id)->update($data);
        return response()->json([
            'status' => 'success',
            'data' => null,
            'message' => "Provider has been updated"
        ]);
    }

    /**
     * Update Provider status partial data
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
            'message' => "Provider has been removed"
        ]);
    }
}
