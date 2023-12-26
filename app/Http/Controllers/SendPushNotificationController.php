<?php

use App\User;
use App\FCMToken;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class SendPushNotificationController extends Controller
{
    /**
     * 
     * @var user
     * @var FCMToken
     */
    protected $user;
    protected $fcmToken;

    /**
     * Constructor
     * 
     * @param 
     */
    public function __construct(User $user, FCMToken $fcmToken)
    {
        $this->user = $user;
        $this->fcmToken = $fcmToken;
    }

    /**
     * Functionality to send notification.
     * 
     */
    public function sendNotification(Request $request)
    {
        $tokens = [];
        $apns_ids = [];
        $responseData = [];
        $data = $request->all();
        $users = $data['user_ids'];

        // for Android
        if ($FCMTokenData = $this->fcmToken->whereIn('user_id', $users)->where('token', '!=', null)->select('token')->get()) {
            foreach ($FCMTokenData as $key => $value) {
                $tokens[] = $value->token;
            }
            define('YOUR_SERVER_KEY', 'YOUR_SERVER_KEY');
            $msg = array(
                'body'  => 'This is body of notification',
                'title' => 'Notification',
                'subtitle' => 'This is a subtitle',
            );
            $fields = ['registration_ids'  => $tokens, 'notification'  => $msg];
            $headers = ['Authorization: key=' . YOUR_SERVER_KEY, 'Content-Type: application/json'];

            // Sending Push Notification to device.
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send');
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
            $result = curl_exec($ch);

            if ($result === FALSE) die('FCM Send Error: ' . curl_error($ch));
            $result = json_decode($result, true);
            $responseData['android'] = ["result" => $result];
            curl_close($ch);
        }

        // for IOS
        if ($FCMTokenData = $this->fcmToken->whereIn('user_id', $users)->where('apns_id', '!=', null)->select('apns_id')->get()) {
            foreach ($FCMTokenData as $key => $value) {
                $apns_ids[] = $value->apns_id;
            }

            $url = "https://fcm.googleapis.com/fcm/send";
            $serverKey = 'YOUR_SERVER_KEY';
            $title = "Thsi is title";
            $body = 'This is body';
            $notification = array('title' => $title, 'text' => $body, 'sound' => 'default', 'badge' => '1');
            $arrayToSend = array('registration_ids' => $apns_ids, 'notification' => $notification, 'priority' => 'high');
            $json = json_encode($arrayToSend);
            $headers = array();
            $headers[] = 'Content-Type: application/json';
            $headers[] = 'Authorization: key=' . $serverKey;

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            //Send the request
            $result = curl_exec($ch);

            if ($result === FALSE) die('FCM Send Error: ' . curl_error($ch));
            $result = json_decode($result, true);
            $responseData['ios'] = ["result" => $result];
            curl_close($ch);
        }
        return $responseData;
    }
}
