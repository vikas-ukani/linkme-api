<?php

use App\Http\Controllers\Controller;
use App\FCMToken as UserFcmTokenModel;
use App\Http\Requests\FCMTokenRequest;

class UserFcmTokenController extends Controller
{
	/**
	 * Instance of FCMToken class
	 *
	 * @var incident
	 */
	protected $userFcmToken;


	/**
	 * Constructor
	 *
	 * @param App\FCMToken $userFcmToken
	 */
	public function __construct(UserFcmTokenModel $userFcmToken)
	{
		return $this->userFcmToken = $userFcmToken;
	}

	/**
	 * Store a newly created resource in storage.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @return \Illuminate\Http\Response
	 */
	public function store(FCMTokenRequest $request)
	{
		$data = $request->all();
		$userId = auth()->id();

		if (array_key_exists('device_type', $data)) {
			// Add the IOS device token to dbs
			$FCMTokenData = $this->userFcmToken->create(['user_id' => $userId, 'apns_id' => $data['token']]);
			if ($FCMTokenData) return $this->returnResponse(['message' => 'Token added successfully', 'data' => [$FCMTokenData]], 200);
			else return $this->returnResponse(['error' => 'Something went wrong!!'], 400);
		} else {
			$FCMTokenData = ['user_id' => $userId, 'token' => $data['token']];
			// Add the android device token to dbs
			$FCMTokenData = $this->userFcmToken->create($FCMTokenData);
			if ($FCMTokenData) {
				return $this->returnResponse(['message' => 'Token added successfully', 'data' => [$FCMTokenData]], 200);
			} else {
				return $this->returnResponse(['error' => 'Something went wrong!!'], 400);;
			}
		}
	}
}
