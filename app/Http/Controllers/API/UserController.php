<?php

namespace App\Http\Controllers\API;

use App\User;
use Exception;
use Validator;
use App\Review;
use App\Bookings;
use \DateTimeZone;
use Carbon\Carbon;
use App\Paymentlogs;
use App\Notification;
use App\PasswordReset;
use App\ServiceReview;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Mail\VerificationEmail;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\LoginRequest;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Http\Requests\ResetPasswordRequest;
use App\Notifications\PasswordResetRequest;
use App\Notifications\PasswordResetSuccess;
use App\Http\Requests\ForgotPasswordRequest;
use App\Http\Requests\PasswordChangeRequest;
use App\Http\Requests\UserRegistrationRequest;
use App\Http\Requests\API\GetUserDetailsRequest;
use App\Notifications\RegisterUserWelcomePackage;
use App\Http\Requests\API\UpdateUserProfileRequest;
use App\Notifications\EmailVerificationNotification;
use App\Http\Requests\ResendEmailVerificationRequest;

class UserController extends Controller
{

    public $successStatus = 200;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function searchtaguser(Request $request)
    {

        $data = $request->get('key');
        if ($data != '') {
            $users = User::where('fname', 'like', "{$data}%")
                ->select('id', 'avatar', 'fname', 'lname', 'user_type')
                ->get();
            return response($users, 200);
        } else {
            $users = User::select('id', 'avatar', 'fname', 'lname', 'user_type')
                ->get();
            return response($users, 200);
        }
    }

    /**
     * User Registration Process.
     *
     * @param Request $request
     * @return void
     */
    public function register(UserRegistrationRequest $request)
    {
        $input = $request->validated();
        $input['password'] = bcrypt($input['password']);
        $user = User::where('email', '=', $request->email)
            ->where('user_type', '=', $request->user_type)
            ->first();

        if (!$user) {
            //$input['category'] = explode(',', $request->category);
            $input['category'] = $request->category;
            /** Generating token to verify user  */
            $input['email_verification_token'] = Str::random(32);
            $user = User::create($input);
            // Assign User
            $user->assignRole($request->user_type === 1 ? 'service-provider' : 'customer');

            /** send email to verify account */
            try {
                $user->notify(new RegisterUserWelcomePackage());
                $user->notify(new EmailVerificationNotification());
            } catch (\Exception $exception) {
                Log::error($exception->getMessage());
                return $this->returnResponse(['message' => $exception->getMessage()], 400);
            }
            return $this->returnResponse(['message' => 'User successfully registered', 'user' => $user], 201);
        } else {
            return $this->returnResponse(['message' => 'User Already exist with same role'], 400);
        }
    }

    /**
     * Verifying User
     *
     * @param string $token
     * @param Request $request
     * @return void
     */
    public function verify($token = null, Request $request)
    {
        if ($token == null) return $this->returnResponse(["msg" => "Invalid/Expired url provided."], 401);
        $user = User::where('email_verification_token', $token)->first();
        if ($user == null) return $this->returnResponse(["msg" => "Invalid/Expired url provided."], 401);

        $currentDate = gmdate('Y-m-d H:i:s');
        $items = DB::table('users')->select('created_at')
            ->where('email_verification_token', '=', $token)->first();
        $createDate = $items->created_at;
        $timediff = strtotime($currentDate) - strtotime($createDate);

        if ($timediff > 86400)
            return $this->returnResponse(["msg" => "more than 24 hours/Expired url provided."], 401);
        else {
            $user->update(['email_verified_at' => Carbon::now()]);
            return redirect()->to('linkmeapp://LOGIN');
        }
    }

    /**
     * Resending Email for email verification.
     *
     * @param Request $request
     * @return void
     */
    public function resend(ResendEmailVerificationRequest $request)
    {

        // $validator = Validator::make($request->all(), ['email' => 'required|string|email|max:100', 'user_type' => 'required']);

        // if ($validator->fails()) {
        //     return $this->returnResponse($validator->errors(), 400);
        // }
        $input = $request->validated();
        $user = User::where('email', $input['email'])
            ->where('user_type', $input['user_type'])
            ->first();

        if ($user->email_verified_at != null) {
            return $this->returnResponse(["msg" => "Email already verified."], 400);
        }

        $user->email_verification_token = Str::random(32);
        // User::where('email', '=', $input['email'])
        //     ->update(array(
        //         'email_verification_token' => Str::random(32)
        //     ));
        $user->save();
        // $user = User::where('email', $input['email'])
        //     ->where('user_type', $input['user_type'])
        //     ->first();
        \Mail::to($user->email)->send(new VerificationEmail($user));
        return $this->returnResponse(["msg" => "Email verification link sent on your email id"]);
    }

    /**
     * Login API
     *
     * @param LoginRequest $request
     * @return void
     */
    public function login(LoginRequest $request)
    {
        $device_id = $request->device_id;
        $device_type = $request->device_type;
        $device_token = $request->device_token;
        if ($request->email == 'linkmeguestuser9911@user.com' && $request->password == 'Linkme@12' && $request->user_type == '0') {

            $guest_email = $request->email;
            $guest_password = Hash::make($request->password);
            $user = User::where('email', $guest_email)
                ->first();

            if ($user) {
                $user['is_guest'] = '1';

                $token = $user->createToken('Laravel Password Grant Client')->accessToken;
                $role = $user
                    ->roles
                    ->pluck('name');
                $appConstants = ['cdnUrl' => $this->cdnUrl(), 'cdnStaticUrl' => $this->cdnStaticUrl()];
                return $this->returnResponse(
                    ['token' => $token, 'role' => $role, 'user' => $user, 'constants' => $appConstants],
                    200
                );
            } else {
                $input = array(
                    'fname' => 'Linkmetestfirstuser',
                    'lname' => 'Linkmetestlastuser',
                    'email' => $guest_email,
                    'phone' => '7878789494',
                    'password' => $guest_password,
                    'user_type' => '0',
                    'address' => 'USA',
                    'city' => 'Plantom',
                    'state' => 'Kansas',
                    'zipcode' => '67301',
                    'email_verification_token' => 'mtieI1Mo9LCkF4HKtfC3lDSMH7e2rwoO',
                );
                User::create($input);

                $user = User::where('email', $guest_email)
                    ->first();
                if ($user) {
                    $user['is_guest'] = '1';

                    $token = $user->createToken('Laravel Password Grant Client')->accessToken;
                    $role = $user
                        ->roles
                        ->pluck('name');

                    $appConstants = ['cdnUrl' => $this->cdnUrl(), 'cdnStaticUrl' => $this->cdnStaticUrl()];
                    $response = ['token' => $token, 'role' => $role, 'user' => $user, 'constants' => $appConstants];
                    return response($response, 200);
                } else {
                    $response = ["error" => "Invalid email or password"];
                    return response($response, 400);
                }
            }
        }

        $user = User::where('email', $request->email)
            ->where('user_type', '=', $request->user_type)
            ->first();

        if ($user) {
            if ($user->email_verified_at === NULL) {
                $response = ["error" => "Please verify the link which is sent to your registered email id."];
                return response($response, 422);
            } elseif ($user->active === 0) {
                // Account Is active or not.
                $response = ["error" => "Your account is in-active. Please contact to our support team."];
                return response($response, 422);
            } else {

                if (Hash::check($request->password, $user->password)) {

                    $token = $user->createToken('Laravel Password Grant Client')->accessToken;
                    $role = $user
                        ->roles
                        ->pluck('name');
                    $id = $user->id;

                    DB::table('user_fcm_token')
                        ->updateOrInsert(['user_id' => $id, 'device_id' => $device_id], ['device_type' => $device_type, 'device_token' => $device_token]);

                    /*$totalreviews = Review::join('users', 'users.id', '=', 'review.rated_by')
                        ->where('user_id', $id)
                        ->select('users.id', 'users.fname', 'users.lname', 'users.email', 'review.*')
                        ->get();

                    $user->totalreviews = ['totalreview' => count($totalreviews), 'reaction' => $totalreviews];*/

                    $user['is_guest'] = '0';
                    $appConstants = ['cdnUrl' => $this->cdnUrl(), 'cdnStaticUrl' => $this->cdnStaticUrl()];

                    $response = ['token' => $token, 'role' => $role, 'user' => $user, 'constants' => $appConstants];

                    return response($response, 200);
                } else {
                    $response = ["error" => "Invalid email or password"];
                    return response($response, 400);
                }
            }
        } else {
            $response = ["error" => 'Invalid email or password'];
            return response($response, 400);
        }
    }

    /**
     * Fetch User Details 
     *
     * @param Request $request
     * @return void
     */
    public function userDetails(GetUserDetailsRequest $request)
    {
        $input = $request->validated();
        $user = User::select(
            'id',
            'user_rating',
            'user_reviewcount',
            'socialproviderid',
            'fname',
            'lname',
            'email',
            'user_type',
            'phone',
            'address',
            'city',
            'state',
            'zipcode',
            'category',
            'avatar',
            'bio',
            'latitude',
            'longitude',
            'socialproviderid',
            'preference_location' // This will use for customer("In home": For filtering service location) and providers(service provider business location.)
        )
            ->where('id', $input['user_id'])
            ->where('user_type', '=', $input['user_type'])
            ->first();

        if ($user) return $this->returnResponse(['user' => $user], 200);
        else return $this->returnResponse(["message" => "User does not exist"], 400);
    }

    /**
     * User Rating Listing
     *
     * @param Request $request
     * @return void
     */
    public function userRatingList(Request $request)
    {
        $PageSize = $request->get('PageSize');
        $validator = Validator::make($request->all(), ['user_id' => 'required', 'user_type' => 'required']);
        if ($validator->fails())
            return $this->returnResponse($validator->errors(), 400);

        $user = User::select('user_rating', 'user_reviewcount')
            ->where('id', $request->user_id)
            ->first();

        $totalReviews = ['rating' => $user->user_rating, 'count' => $user->user_reviewcount];

        if ($request->user_type == '0') {
            $totalReviews['summary'] = Review::join('users', 'users.id', '=', 'review.rated_by')
                ->where('review.user_id', $request->user_id)
                ->select(
                    'users.id',
                    'users.fname',
                    'users.lname',
                    'users.user_type',
                    'users.avatar',
                    'review.stars',
                    'review.comments as review_comments',
                    'review.created_at as review_creted_at'
                )
                ->paginate($PageSize);
        } else {
            $totalReviews['summary'] = ServiceReview::join('users', 'users.id', '=', 'customer_id')
                ->where('providerId', $request->user_id)
                ->select(
                    'users.id',
                    'users.fname',
                    'users.lname',
                    'users.user_type',
                    'users.avatar',
                    'stars',
                    'comments as review_comments',
                    'service_reviews.created_at as review_creted_at'
                )
                ->paginate($PageSize);
        }
        return $totalReviews;
    }


    public function user_profile()
    {
        if (Auth::check()) {
            $user = Auth::user();
            $user_id = Auth::user()->id;
            $result = User::select(
                'id',
                'user_rating',
                'user_reviewcount',
                'socialproviderid',
                'fname',
                'lname',
                'email',
                'user_type',
                'phone',
                'address',
                'city',
                'state',
                'zipcode',
                'category',
                'avatar',
                'bio',
                'latitude',
                'longitude',
                'socialproviderid'
            )
                ->where('id', $user_id)
                ->get();
            //            $totalreviews = Review::where('user_id', '=', $user_id)->get();
            //            $result['totalreviews'] = ['totalreview' => count($totalreviews), 'reaction' => $totalreviews];
            return $this->returnResponse(['success' => $result], $this->successStatus);
        } else {
            return $this->returnResponse(['error' => 'Unauthorised']);
        }
    }

    /**
     * Updating User Profile and other details.
     * @bodyParam preference_location string required The preference_location must be value of "IN_HOME", "BUSINESS_LOCATION", "BOTH". Example: IN_HOME
     * @bodyParam is_in_home_service boolean The flag for ON or OFF. Example: true
     * @bodyParam service_location_lat float The latitude for the service location.
     * @bodyParam service_location_long float The latitude for the service location.
     * @bodyParam avatar file User Profile Photo [OPTIONAL].
     * @param Request $request
     * @return void
     */
    public function update_user_profile(UpdateUserProfileRequest $request)
    {
        if (Auth::check()) {
            $input = $request->validated();
            if (!empty($input['password'])) $input['password'] = bcrypt($input['password']);

            // Uploading Image to cloud.
            if ($request->hasFile('avatar')) {
                $image = $request->file('avatar');
                $input['avatar'] = $this->cloudUpload($image->getClientOriginalExtension(), file_get_contents($image));
            }
            $user = auth()->user();
            $user->fill($input);
            $user->save(); // Mass fill details.
            dd($input, $user->toArray());
            return $this->returnResponse(['success' => ['result' => $user->fresh(), 'Status' => 'User Profile has been updated Successfully']], $this->successStatus);
        } else
            return $this->returnResponse(['error' => 'Unauthorized']);
    }

    /**
     * Change password API
     *
     * @param Request $request
     * @return void
     */
    public function password_change(PasswordChangeRequest $request)
    {
        if (Auth::check()) {
            $user = auth()->user();
            $input = $request->all();
            if (!Hash::check($request->old_password, $user->password))
                return $this->returnResponse(['error' => 'Password does not match']);
            User::where('id', $user->id)->update(['password' => Hash::make($input['password'])]);
            return $this->returnResponse(['success' => 'Password changed Successfully']);
        }
    }

    /**
     * Filter based searching service providers
     *
     * @param Request $request
     * @return void
     */
    public function filterSearchResults(Request $request)
    {
        $long1 = $request->get('long');
        $lat1 = $request->get('lat');
        $distance = $request->get('distance');
        $PageSize = $request->get('PageSize');
        $data = $request->get('data');
        $category = $request->get('category');

        /** If Database Postgres Found then, Use different query. */
        if (config('database.default') === 'pgsql')
            return $this->filterSearchResultsForPostgreSQL($request);

        if ($long1 == 0 || $lat1 == 0)
            return $this->returnResponse(['message' => "Invalid location provided."]);
        else {
            $query = "SELECT * FROM ("
                . "SELECT id, email, fname, lname, avatar, user_type, city, state, user_rating, user_reviewcount, category, latitude, longitude,"
                . "( 3959 * acos ( cos ( radians(" . $lat1 . ") ) * cos( radians( Latitude ) ) * cos( radians( Longitude ) - radians(" .  $long1
                . ") ) + sin ( radians(" . $lat1 . ") ) * sin( radians( Latitude ) ) ) ) AS `distance` "
                . "FROM users "
                . "WHERE user_type = 1 ";

            if ($category) $query .= " AND category like '%" . $category . "%' ";
            if ($data) $query .= " AND fname like '%" . $data . "%' ";

            $query .=  " ) as data "
                . "WHERE distance <  " . $distance . " "
                . "ORDER BY distance ASC";
            return json_encode(DB::select($query));
        }
    }

    /**
     * Filtering User to get geo codding results from PostgreSQl
     *
     * @param Request $request
     * @return void
     */
    public function filterSearchResultsForPostgreSQL(Request $request)
    {
        $long1 = $request->get('long');
        $lat1 = $request->get('lat');
        $distance = $request->get('distance');
        $PageSize = $request->get('PageSize');
        $data = $request->get('data');
        $category = $request->get('category');

        if ($long1 == 0 || $lat1 == 0) return $this->returnResponse(['message' => "Invalid location provided."]);

        // SELECT *,( 3959 * acos( cos( radians(6.414478) ) * cos( radians( lat ) ) * cos( radians( lng ) - radians(12.466646) ) 
        // + sin( radians(6.414478) ) * sin( radians( lat ) ) ) ) AS distance 
        // $query = "SELECT * FROM ("
        //     . "SELECT id, email, fname, lname, avatar, user_type, city, state, user_rating, user_reviewcount, category, latitude, longitude,"
        //     . "( 3959 * acos ( cos ( radians(" . $lat1 . ") ) * cos( radians( latitude ) ) * cos( radians( longitude ) - radians(" .  $long1
        //     . ") ) + sin ( radians(" . $lat1 . ") ) * sin( radians( latitude ) ) ) ) AS distance "
        //     . "FROM users "
        //     . "WHERE user_type = 1 ";
        // if ($category)
        //     $query = $query . " AND category like '%" . $category . "%' ";
        // if ($data)
        //     $query = $query . "AND fname like '%" . $data . "%' ";
        // $query = $query . ") as data "
        //     . "WHERE distance < " . $distance . " "
        //     . "ORDER BY distance ASC";
        // $providers = DB::select($query);
        // return json_encode($providers);

        // Followed: https://stackoverflow.com/questions/52363986/laravel-sorting-user-collection-based-by-distance
        $users = User::query();
        $users = $users->where('user_type', 1)
            ->select(['id', 'email', 'fname', 'lname', 'avatar', 'user_type', 'city', 'state', 'user_rating', 'user_reviewcount', 'category', 'latitude', 'longitude']);
        if ($category) $users = $users->where('category', 'LIKE', '%' . $category . '%');
        if ($data) $users = $users->where('fname', 'LIKE', '%' . $data . '%');
        $users = $users->get();
        /** Creating Distance Field */
        if (isset($users) && count($users) > 0) {
            foreach ($users as &$user) {
                $user['distance'] = (($user['latitude']  - $lat1) ** 2)
                    + ((($user['longitude'] - $long1) / cos($user['latitude'] / 57.295)) ** $distance)
                    ** .5 / .009;
            }
            /** Sort by ASC order */
            $users = collect($users)->sortBy('distance');
            $users = array_values(collect($users)->where('distance', '<', $distance)->all());
            $users = $users;
            return json_encode($users);
        } else {
            // IF no users found
            return json_encode([]);
        }
    }

    public function getSearchResults(Request $request)
    {

        $data = $request->get('data');
        $category = $request->get('category');
        $search_drivers = User::where('fname', 'like', "%{$data}%")->whereHas('roles', function ($q) {
            $q->where('name', 'service-provider');
        })
            ->where('category', 'like', "%$category%")->get();

        return $this->returnResponse(['data' => $search_drivers]);
    }

    /**
     * Logout API
     *
     * @param Request $request
     * @return void
     */
    public function logout(Request $request)
    {
        if (Auth::check()) {
            $accessToken = Auth::user()->token();
            $accessToken->revoke();
            DB::table('user_fcm_token')->where('device_id', $request->device_id)->delete();
            return $this->returnResponse(['Success' => 'Successfully logged out']);
        } else return $this->returnResponse(['error' => 'Unauthorized']);
    }

    /**
     * Forgot Password API
     *
     * @param Request $request
     * @return void
     */
    public function forgotPassword(ForgotPasswordRequest $request)
    {
        // $validator = Validator::make($request->all(), ['email' => 'required|string|email', 'user_type' => 'required']);
        // if ($validator->fails()) {
        //     return $this->returnResponse(['error' => $validator->errors()], 401);
        // }
        $input = $request->validated();
        $user = User::where('email', $input['email'])
            ->where('user_type', $input['user_type'])
            ->whereNull('socialprovidertype')
            ->first();

        if ($user && !$user->email_verified_at)
            return $this->returnResponse(['message' => 'Please verify your email address first!']);
        if (!$user)
            return $this->returnResponse(['message' => 'We can not find a user with that e-mail address.'], 404);

        $passwordReset = PasswordReset::updateOrCreate(['email' => $user->email, 'user_type' => $user->user_type], ['email' => $user->email, 'user_type' => $user->user_type, 'token' => str_random(60)]);
        if ($user && $passwordReset) $user->notify(new PasswordResetRequest($passwordReset->token));
        return $this->returnResponse(['message' => 'Password reset link has been sent to your registered email id!']);
    }

    /**
     * Find token password reset
     *
     * @param  [string] $token
     * @return [string] message
     * @return [json] passwordReset object
     */
    public function find($token)
    {
        $passwordReset = PasswordReset::where('token', $token)->first();
        if (!$passwordReset) return $this->returnResponse(['message' => 'This password reset token is invalid.'], 404);
        return view('reset-password', ['passwordReset' => $passwordReset]);
    }

    /**
     * Reset password
     * 
     * @param ResetPasswordRequest $request
     * @return void
     */
    public function reset(ResetPasswordRequest $request)
    {
        // $request->validate(['email' => 'required|string|email', 'password' => 'required|string|confirmed|min:8|max:32', 'token' => 'required|string']);
        $input = $request->validated();
        $passwordReset = PasswordReset::where([
            ['token', $input['token']],
            ['email', $input['email']]
        ])->first();

        if (!$passwordReset) return $this->returnResponse(['message' => 'This password reset token is invalid.'], 404);

        $user = User::where('email', $passwordReset->email)
            ->where('user_type', $passwordReset->user_type)
            ->first();

        if (!$user) return $this->returnResponse(['message' => 'We can not find a user with that e-mail address.'], 404);
        $user->password = bcrypt($input['password']);
        $user->save();
        $passwordReset->delete();
        $user->notify(new PasswordResetSuccess($passwordReset));
        echo "Your password is successfully changed!";
        exit();
        // return $this->returnResponse($user);
    }

    /**************************** Social login functions ***************/

    /**
     * Social Authentication API.
     *
     * @param Request $request
     * @return void
     */
    public function socialLogin(Request $request)
    {
        $validator = Validator::make($request->all(), ['user_type' => 'required', 'socialproviderid' => 'required', 'socialprovidertype' => 'required', 'device_id' => 'required', 'device_type' => 'required', 'device_token' => 'required']);

        if ($validator->fails())
            return $this->returnResponse($validator->errors(), 400);

        $device_id = $request->device_id;
        $device_type = $request->device_type;
        $device_token = $request->device_token;

        $user = User::where('user_type', $request->user_type)
            ->where('socialproviderid', $request->socialproviderid)
            ->where('socialprovidertype', $request->socialprovidertype)
            ->first();
        if (!$user)
            return $this->returnResponse(['message' => 'User coming first time'], 404);
        else if ($user->email_verified_at === NULL)
            return $this->returnResponse(["error" => "Please verify the link which is sent to your registered email id."], 422);
        else {
            $user['is_guest'] = '0';
            $token = $user->createToken('Laravel Password Grant Client')->accessToken;
            $id = $user->id;
            /*                $totalreviews = Review::join('users', 'users.id', '=', 'review.rated_by')
                    ->where('user_id', $id)
                    ->select('users.id', 'users.fname', 'users.lname', 'users.email', 'review.*')
                    ->get();

                $user->totalreviews = ['totalreview' => count($totalreviews), 'reaction' => $totalreviews];*/

            DB::table('user_fcm_token')
                ->updateOrInsert(['user_id' => $id, 'device_id' => $device_id], ['device_type' => $device_type, 'device_token' => $device_token]);

            $appConstants = ['cdnUrl' => $this->cdnUrl(), 'cdnStaticUrl' => $this->cdnStaticUrl()];
            return $this->returnResponse(['message' => 'User logged-in Successfully ', 'token' => $token, 'user' => $user, 'constants' => $appConstants], 200);
        }
    }

    /**
     * Social Sign UP
     *
     * @param Request $request
     * @return void
     */
    public function socialSignup(Request $request)
    {
        $validator = Validator::make($request->all(), ['fname' => 'required', 'lname' => 'required', 'email' => 'required|string|email|max:100', 'user_type' => 'required', 'socialproviderid' => 'required', 'socialprovidertype' => 'required', 'email_verification' => 'required']);

        if ($validator->fails())
            return $this->returnResponse($validator->errors(), 400);

        $device_id = $request->device_id;
        $device_type = $request->device_type;
        $device_token = $request->device_token;
        $request->request->add(['email_verification_token' => Str::random(32)]);

        if (!empty($request->address)) $request->request->add(['address' => $request->address]);
        else $request->request->add(['address' => '']);
        if (!empty($request->state)) $request->request->add(['state' => $request->state]);
        else $request->request->add(['state' => '']);
        if (!empty($request->city)) $request->request->add(['city' => $request->city]);
        else  $request->request->add(['city' => '']);
        if (!empty($request->zipcode)) $request->request->add(['zipcode' => $request->zipcode]);
        else $request->request->add(['zipcode' => '']);

        if (!empty($request->phone)) $request->request->add(['phone' => $request->phone]);
        else $request->request->add(['phone' => '']);

        //$input = $request->all();
        $input = $request->all();
        $input['password'] = '999';

        $user = User::where('email', $request->email)->where('user_type', $request->user_type)->first();
        if (!$user) {
            if ($request->email_verification != 'true') {
                $user = User::create($input);
                if ($request->user_type === 1) $user->assignRole('service-provider');
                else $user->assignRole('customer');
                // $user->sendEmailVerificationNotification();
                \Mail::to($user->email)->send(new VerificationEmail($user));
                $id = $user->id;
                DB::table('user_fcm_token')->updateOrInsert([
                    'user_id' => $id, 'device_id' => $device_id
                ], [
                    'device_type' => $device_type, 'device_token' => $device_token
                ]);
                /*                $totalreviews = Review::join('users', 'users.id', '=', 'review.rated_by')->where('user_id', $id)->select('users.id', 'users.fname', 'users.lname', 'users.email', 'users.avatar', 'review.*')
                    ->get();
                $user->totalreviews = ['totalreview' => count($totalreviews), 'reaction' => $totalreviews];*/
                return $this->returnResponse(['message' => 'User registered Successfully.Please Verify your email', 'user' => $user], 201);
            } else {
                $input['email_verified_at'] = date('Y-m-d H:i:s');
                $user = User::create($input);

                if ($request->user_type === 1) $user->assignRole('service-provider');
                else $user->assignRole('customer');
                $token = $user->createToken('Laravel Password Grant Client')->accessToken;
                $id = $user->id;
                DB::table('user_fcm_token')->updateOrInsert([
                    'user_id' => $id, 'device_id' => $device_id
                ], [
                    'device_type' => $device_type, 'device_token' => $device_token
                ]);
                $avt = User::where('id', $id)->first();
                $user['avatar'] = $avt->avatar;
                /*                $totalreviews = Review::join('users', 'users.id', '=', 'review.rated_by')->where('user_id', $id)->select('users.id', 'users.fname', 'users.lname', 'users.email', 'users.avatar', 'review.*')
                    ->get();
                $user->totalreviews = ['totalreview' => count($totalreviews), 'reaction' => $totalreviews];
*/
                return $this->returnResponse(['message' => 'User logged-in Successfully', 'token' => $token, 'user' => $user], 201);
            }
        } else {
            return $this->returnResponse(['message' => 'User Already exist with same role', 'user' => $user], 400);
        }
    }

    /**
     * Net Payout
     *
     * @param Request $request
     * @return void
     */
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
                'paymentlogs.fees_collected as linkmefee',
                'paymentlogs.paymentstatus',
                'paymentlogs.paid_out as amountpaid',
                DB::raw("paymentlogs.paid_out+paymentlogs.tip as providerprofit")
            )
            ->where('providerId', '=', $id)
            ->where('provider.user_type', '=', '1')
            ->whereIn('status', [2, 3, 4])
            ->whereIn('paymentstatus', ['PAYMENT', 'CANCEL'])
            ->paginate($PageSize);

        return $this->returnResponse(['data' => $searchservice]);
    }


    /**
     * Provider Month Wise Earning.
     *
     * @param Request $request
     * @return void
     */
    public function providerMonthWiseEarning(Request $request)
    {
        $user = Auth::user();
        if ($user->user_type == 0)
            return $this->returnResponse(['failed' => 'Invalid user type']);
        $lastNMonths = 6; //Default last six months

        $report = Paymentlogs::select(
            DB::raw('SUM(IFNULL(paid_out,0) + IFNULL(tip,0)) as amount'),
            DB::raw('YEAR(paymentlogs.created_at) year'),
            DB::raw('MONTH(paymentlogs.created_at) month')
        )
            ->join('bookings', 'bookings.id', '=', 'paymentlogs.bookingId')
            ->where('bookings.providerId', '=', $user->id)
            ->where("paymentlogs.created_at", ">", Carbon::now()->subMonths($lastNMonths))
            ->groupby('year', 'month')
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->get();

        $months = array(
            1 => 'Jan.', 2 => 'Feb.', 3 => 'Mar.', 4 => 'Apr.', 5 => 'May', 6 => 'Jun.',
            7 => 'Jul.', 8 => 'Aug.', 9 => 'Sep.', 10 => 'Oct.', 11 => 'Nov.', 12 => 'Dec.'
        );

        $transposed = array_slice($months, date('n'), 12, true) + array_slice($months, 0, date('n'), true);
        $last6 = array_reverse(array_slice($transposed, -6, 12, true), true);

        $months = array();
        $amounts = array();

        foreach ($last6 as $key => $value) {
            $month = $value;
            $amount = 0;
            foreach ($report as $k => $m) {
                if ($m['month'] == $key) {
                    $amount = $m['amount'];
                    continue;
                }
            }
            array_push($months, $month);
            array_push($amounts, $amount);
        }
        $months = array_reverse($months);
        $amounts  = array_reverse($amounts);
        return $this->returnResponse(['y' => $months, 'values' => $amounts]);
    }


    /**
     * Provider Earnings
     *
     * @param Request $request
     * @return void
     */
    public function ProviderEarnings(Request $request)
    {

        if (Auth::check()) {
            $userid = Auth::user()->id;
            $month = date('m');
            $earnings = ["totalEarnings" => 0, "Monthly" => 0];

            //Total Earning
            $totalEarning = Bookings::where('providerId', $userid)->where('status', '>', 2);
            $price = $totalEarning->sum('price');
            $tips = $totalEarning->sum('providerTip');

            //Monthly Earning
            $today = \Carbon\Carbon::now(); //Current Date and Time
            $startDayofMonth = \Carbon\Carbon::parse($today)->startOfMonth();
            $lastDayofMonth = \Carbon\Carbon::parse($today)->endOfMonth();

            $monthlyEarning = Bookings::where('providerId', $userid)->whereDate('booked_at', '>=', $startDayofMonth)->whereDate('booked_at', '<=', $lastDayofMonth)->where('status', '>', 2);

            $monthlyPrice = $monthlyEarning->sum('price');
            $monthlyTips = $monthlyEarning->sum('providerTip');

            $earnings = ["totalEarnings" => $price + $tips, "Monthly" => $monthlyPrice + $monthlyTips];
            return $this->returnResponse(['earnings' => $earnings]);
        }
    }

    /**
     * Providers Earning List
     *
     * @param Request $request
     * @return void
     */
    public function ProviderEarningsList(Request $request)
    {
        if (Auth::check()) {
            $userid = Auth::user()->id;
            $PageSize = $request->get('PageSize');
            $lists = Bookings::join('users', 'users.id', '=', 'bookings.customerId')
                ->join('linkmeservices', 'linkmeservices.id', '=', 'bookings.serviceId')
                ->join('paymentlogs', 'paymentlogs.bookingId', '=', 'bookings.id')
                ->where('providerId', $userid)
                ->where('status', '>', 1)
                ->select(
                    'bookings.booked_at',
                    'linkmeservices.title',
                    'users.fname',
                    'users.lname',
                    'users.avatar',
                    'paymentlogs.paymentstatus',
                    'paymentlogs.tip',
                    'paymentlogs.paid_out as amountpaid'
                )
                ->orderBy('bookingStartUtc', 'desc')
                ->paginate($PageSize);

            return $this->returnResponse(['earnings' => $lists]);
        }
    }

    /**
     * Provider View List
     *
     * @param Request $request
     * @return void
     */
    public function providerViewList(Request $request)
    {
        if (Auth::check()) {
            $userid = Auth::user()->id;
            // $PageSize = $request->get('PageSize');
            $lists = Bookings::join('users', 'users.id', '=', 'bookings.customerId')->join('linkmeservices', 'linkmeservices.id', '=', 'bookings.serviceId')
                ->where('providerId', $userid)->where('status', 1)
                ->where('bookingStartUtc', '>=', gmdate('Y-m-d H:i:s'))
                ->orderBy('bookingStartUtc')
                ->select('bookings.*', 'users.fname', 'users.lname', 'linkmeservices.title', 'users.avatar')
                ->get();
            return $this->returnResponse(['customerLists' => $lists]);
        }
    }

    /**
     * Provider View Details
     *
     * @param Request $request
     * @return void
     */
    public function providerViewDetails(Request $request)
    {
        $validator = Validator::make($request->all(), ['customerId' => 'required']);
        if ($validator->fails())
            return $this->returnResponse($validator->errors(), 400);
        $PageSize = $request->PageSize;
        $userid = auth('api')->user();
        $lists = Bookings::join('users', 'users.id', '=', 'bookings.customerId')->join('linkmeservices', 'linkmeservices.id', '=', 'bookings.serviceId')
            ->where('providerId', $userid->id)
            ->where('customerId', $request->customerId)
            ->whereIn('status', [2, 3, 4])
            ->where('bookingStartUtc', '<', gmdate('Y-m-d H:i:s'))
            ->select('bookings.*', 'linkmeservices.title', 'linkmeservices.service_img', 'users.fname', 'users.lname', 'users.avatar')
            ->get();

        return $this->returnResponse(['customerLists' => $lists]);
    }

    /**
     * Email Provider Payment Report.
     *
     * @return void
     */
    public function emailProviderPaymentReport()
    {
        $user = Auth::user();
        $userid = $user->id;
        $email = $user->email;
        $viewdata = array('fname' => $user->fname);

        if ($user->user_type == 0)
            return $this->returnResponse(['failed' => 'Invalid user type']);

        $accounts = Bookings::join('users', 'users.id', '=', 'bookings.customerId')
            ->join('linkmeservices', 'linkmeservices.id', '=', 'bookings.serviceId')
            ->join('paymentlogs', 'paymentlogs.bookingId', '=', 'bookings.id')
            ->where('providerId', $userid)
            ->where('status', '>', 1)
            ->select(
                'bookings.booked_at',
                'linkmeservices.title',
                'users.fname',
                'users.lname',
                'users.avatar',
                'paymentlogs.paymentstatus',
                'paymentlogs.tip',
                'paymentlogs.paid_out as amountpaid'
            )->get();

        $csvFile = tmpfile();
        $csvPath = stream_get_meta_data($csvFile)['uri'] . '.csv';
        $fd = fopen($csvPath, 'w');
        $attachment = $csvPath;

        try {
            fputcsv($fd, array('Booking', 'Service', 'Customer', 'Amount', 'Tip', 'Payment Status'));
            foreach ($accounts as $account) {
                fputcsv(
                    $fd,
                    array(
                        $account->booked_at,
                        $account->title,
                        $account->fname . ' ' . $account->lname,
                        $account->amountpaid,
                        $account->tip,
                        $account->paymentstatus
                    )
                );
            }

            \Mail::send(
                config('constant.MAIL.PAYMENT-REPORT.VIEW'),
                $viewdata,
                function ($message) use ($email, $attachment) {
                    $message->to($email)->subject(config('constant.MAIL.PAYMENT-REPORT.SUBJECT'));
                    $message->attach($attachment);
                }
            );

            return $this->returnResponse(['success' => 'Statement is successfully sent to the associated email.'], 200);
        } catch (Exception $e) {
            return $this->returnResponse(['error' => 'We are experiencing some difficulty in processing your request.'], 401);
        } finally {
            fclose($fd);
            unlink($attachment);
        }
    }

    /**
     * User Notifications
     *
     * @param Request $request
     * @return void
     */
    public function userNotifications(Request $request)
    {
        $PageSize = $request->get('PageSize');
        $userTimezone = $request->get('userTimezone');
        $user = Auth::user();
        $user_id = Auth::user()->id;
        $result = Notification::where('userId', $user_id)->orderBy('id', 'desc')->paginate($PageSize);
        foreach ($result as $result_value) {
            $serverdt = $result_value->created_at;
            $serverdt->setTimezone(new DateTimeZone($userTimezone));
            $localDt = $serverdt;
            $UtcCurrentDatetime = $localDt->format('Y-m-d H:i:s');
            $result_value->UtcCurrentDatetime = $UtcCurrentDatetime;
        }
        return $this->returnResponse(['success' => $result], $this->successStatus);
    }
}
