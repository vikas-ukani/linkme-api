<?php

namespace App\Http\Controllers\API;

use App\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\DB;
use App\Notification;
use App\Notifications\PasswordResetRequest;
use App\Notifications\PasswordResetSuccess;
use App\PasswordReset;
use App\Review;
use App\Bookings;
use App\Chat;
use Twilio\Jwt\AccessToken;
use Twilio\Jwt\Grants\ChatGrant;
use Twilio\Jwt\Grants\VideoGrant;
use Twilio\Jwt\Grants\VoiceGrant;
use Twilio\Rest\Client;
use Session;
use Validator;
use Image;
use Storage;

class PushNotifyController extends Controller
{

    private $fcm_endpoint = 'https://fcm.googleapis.com/fcm/send';
    private $serverKey = 'AAAAHrXVCfk:APA91bHQY0vbHUyMhSPBf8SMs0K0muqbcc-qnR2AEMYhj6MZfoKqYX_3DVmGlXFfSWiIAR1vzmiG9m-tu_C0ybz54AkoICZ9YFM-2zZKxVGJBaX3pgPjxC5hlfL_YZ_8A9lMZwzisRj8';

    public function send($user_id, $notification_type, $title, $message, $data)
    {
        $userAndroidTokens = DB::table('user_fcm_token')->where('user_id', '=', $user_id)->where('device_type', '=', 'android')
            ->select('device_token')
            ->orderBy('created_at', 'desc')
            ->get();

        $notification = new Notification;
        $notification->userId = $user_id;
        $notification->type = $notification_type;
        $notification->title = $title;
        $notification->message = $message;
        $notification->data = $data;
        $notification->save();

        if (count($userAndroidTokens) > 0) {
            $device_tokens = $userAndroidTokens->pluck('device_token');
            $result = $this->SendtoAndroid($title, $notification_type, $message, $device_tokens, $data);
        }


        $userIOSTokens = DB::table('user_fcm_token')->where('user_id', '=', $user_id)->where('device_type', '=', 'ios')
            ->select('device_token')
            ->orderBy('created_at', 'desc')
            ->get();

        if (count($userIOSTokens) > 0) {
            $device_tokens = $userIOSTokens->pluck('device_token');
            $result = $this->SendtoIos($title, $notification_type, $message, $device_tokens, $data);
        }
    }

    private function SendtoIos($title, $notification_type, $message, $device_token, $data, $type = 'text')
    {
        $customData = array('notification_type' => $notification_type, 'type' => $type, 'data' => $data);

        $notification = array(
            'title' => $title,
            'body' => json_encode($customData),
            'sound' => 1,
            'subtitle' => $message,
            'notification_type' => $notification_type,
            'badge' => '1',


        );

        $arrayToSend = array(
            'registration_ids' => $device_token,
            'notification' => $notification,
            'priority' => 'high'
        );
        $json = json_encode($arrayToSend);
        //print_r($json);
        $headers = array();
        $headers[] = 'Content-Type: application/json';
        $headers[] = 'Authorization: key=' . $this->serverKey;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->fcm_endpoint);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        //Send the request
        $result = curl_exec($ch);
        if ($result === false) {
            die('FCM Send Error: ' . curl_error($ch));
        }

        curl_close($ch);
        return $result;
    }

    private function SendtoAndroid($title, $notification_type, $message, $device_token, $data, $type = 'text')
    {

        $registrationIds = $device_token;
        // prep the bundle
        $msg = array(
            'message' => $message,
            'notification_type' => $notification_type,
            'data' => $data,
            'title' => $title,
            'subtitle' => '',
            'vibrate' => 1,
            'sound' => 1,
            'largeIcon' => 'large_icon',
            'smallIcon' => 'small_icon',
            'type' => $type
        );

        $fields = array(
            'registration_ids' => $registrationIds,
            'data' => $msg
        );
        $headers = array(
            'Authorization: key=' . $this->serverKey,
            'Content-Type: application/json'
        );
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->fcm_endpoint);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
        $result = curl_exec($ch);
        if ($result === false) {
            die('FCM Send Error: ' . curl_error($ch));
        }
        curl_close($ch);
        return $result;
    }

    /*Call Push*/


    public function NotifyCall($receiver, $senderName, $type, $data)
    {

        $title = config('app.name', "We Link") . ' voice call';
        $notification_type = 'CALL_START';
        $message = $senderName . ' is calling';

        $userAndroidTokens = DB::table('user_fcm_token')
            ->where('user_id', '=', $receiver)
            ->where('device_type', '=', 'android')
            ->select('device_token')
            ->orderBy('created_at', 'desc')
            ->get();

        if (count($userAndroidTokens) > 0) {
            $device_tokens = $userAndroidTokens->pluck('device_token');
            $result = $this->SendtoAndroid($title, $notification_type, $message, $device_tokens, $data, $type);
        }


        $userIOSTokens = DB::table('user_fcm_token')->where('user_id', '=', $receiver)
            ->where('device_type', '=', 'ios')
            ->select('device_token')
            ->orderBy('created_at', 'desc')
            ->get();

        if (count($userIOSTokens) > 0) {
            $device_tokens = $userIOSTokens->pluck('device_token');
            $result = $this->SendtoIos($title, $notification_type, $message, $device_tokens, $data, $type);
        }
    }

    private function NotifyCall_IOS($senderName, $deviceToken, $message)
    {
        $passphrase = '1234';
        $pemfile = __DIR__ . '/Halo_ApnsDisP12.pem';

        $ctx = stream_context_create();
        stream_context_set_option($ctx, 'ssl', 'local_cert', $pemfile); // Replace with your pem file
        stream_context_set_option($ctx, 'ssl', 'passphrase', $passphrase);
        $fp = stream_socket_client('ssl://gateway.sandbox.push.apple.com:2195', $err, $errstr, 60, STREAM_CLIENT_CONNECT | STREAM_CLIENT_PERSISTENT, $ctx);

        if (!$fp) exit("Failed to connect: $err $errstr" . PHP_EOL);

        echo 'Connected to APNS' . PHP_EOL;

        $body = [
            'aps' =>
            [
                'content-available' => 1,
                'apns-push-type' => 'background',
                'apns-expiration' => 0
            ],
            'data' => [
                'uuid' => 'UUID HARDCODED',
                'name' => 'Calling hardcoded',
                'type' => 'call',
                'priority' => 'high',
                'handle' => '0000000000', // phone number
                'hasVideo' => 1, // u are trying to audio call. please remove hasVideo key and value
                'handleType' => 'generic'  // options are `generic`, `number` and `email`
            ]
        ];

        // Encode the payload as JSON
        $payload = json_encode($body);
        $msg = chr(0) . pack('n', 32) . pack('H*', $deviceToken) . pack('n', strlen($payload)) . $payload;

        // Send it to the server
        $result = fwrite($fp, $msg, strlen($msg));

        if (!$result) {
            echo 'Message not delivered' . PHP_EOL;
        } else {
            echo 'Message successfully delivered' . PHP_EOL;
        }
        // Close the connection to the server
        fclose($fp);
    }

    private function NotifyCall_ANDROID($deviceToken)
    {

        $message = [
            'body' => 'Hardcoded Body',
            'title' => config('app.name', "We Link") .  ' call',
            'notification_type' => 'Test',
            'priority' => 'high',
            'uuid' => 'Hardcoded uuid',
            'name' => 'Anurag',
            'type' => 'call'
        ];

        $notification = [
            'body' => 'body',
            'title' => 'title',
        ];

        $fields = array(
            'registration_ids' => array(
                $deviceToken
            ),
            'notification' => $notification,
            'data' => $message,
            'priority' => 'high',
        );

        $fields = json_encode($fields);
        $headers = array(
            'Authorization: key=' . $this->serverKey,
            'Content-Type: application/json'
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->fcm_endpoint);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);

        $result = curl_exec($ch);

        curl_close($ch);
        return 'success';
    }
}
