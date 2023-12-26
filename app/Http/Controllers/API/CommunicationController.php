<?php

namespace App\Http\Controllers\API;

use App\Chat;
use App\User;
use Validator;
use Twilio\Rest\Client;
use Twilio\Jwt\AccessToken;
use Illuminate\Http\Request;
use Twilio\Jwt\Grants\ChatGrant;
use Twilio\Jwt\Grants\VideoGrant;
use Twilio\Jwt\Grants\VoiceGrant;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Http\Requests\API\SendNotificationRequest;
use App\Http\Requests\API\StartCallRequest;
use Illuminate\Support\Facades\Auth;

class CommunicationController extends Controller
{

    /**
     * Sending Notification
     *
     * @param SendNotificationRequest $request
     * @return void
     */
    public function sendNotification(SendNotificationRequest $request)
    {
        $type = $request->type;
        $user_id = $request->sender;
        $receiver = $request->receiver;

        $senderName = 'Test User';

        $roomId = 'linkme_room_' . $user_id . '_' . $receiver;

        $accountSid = env('TWILIO_ACCOUNT_SID');
        $apiKeySid = env('TWILIO_API_KEY_SID');
        $apiKeySecret = env('TWILIO_API_KEY_SECRET');

        $identity = $user_id;

        $token = new AccessToken(
            $accountSid,
            $apiKeySid,
            $apiKeySecret,
            3600,
            $identity
        );

        $grant = new VideoGrant();
        $grant->setRoom($roomId);
        $token->addGrant($grant);
        $call_token = $token->toJWT();

        $pushnotify = new PushNotifyController();
        $data = ['room' => $roomId, 'token' => $call_token];
        $pushnotify->notifyCall($receiver, $senderName, $type, $data);
        return $this->returnResponse(['token' => $call_token, 'room' => $roomId, 'type' => $type]);
    }



    /**
     * Start Call API
     *
     * @param StartCallRequest $request
     * @return void
     */
    public function startCall(StartCallRequest $request)
    {

        $type = $request->type;
        $receiver = $request->receiver;

        $user = Auth::user();
        $user_id = Auth::user()->id;
        $senderName = $user->fname;

        $roomId = 'linkme_room_' . $user_id . '_' . $receiver;

        $accountSid = env('TWILIO_ACCOUNT_SID');
        $apiKeySid = env('TWILIO_API_KEY_SID');
        $apiKeySecret = env('TWILIO_API_KEY_SECRET');

        $identity = $user_id;

        $token = new AccessToken(
            $accountSid,
            $apiKeySid,
            $apiKeySecret,
            3600,
            $identity
        );

        $grant = new VideoGrant();
        $grant->setRoom($roomId);
        $token->addGrant($grant);
        $call_token = $token->toJWT();

        $pushnotify = new PushNotifyController();
        $data = ['room' => $roomId, 'token' => $call_token];
        $pushnotify->notifyCall($receiver, $senderName, $type, $data);
        return $this->returnResponse(['token' => $call_token, 'room' => $roomId, 'type' => $type]);
    }

    /**
     * Generating Token for Video Call
     *
     * @param Request $request
     * @return void
     */
    public function generateTokenVideoCall(Request $request)
    {
        $validator = Validator::make($request->all(), ['userId' => 'required']);

        if ($validator->fails())
            return $this->returnResponse($validator->errors(), 400);
        $token = new AccessToken('AC609145942d54e3c535cae5de8f5ea53a', 'SK2bd6258fff1efc11d818a4e055e806b2', 'mIkRx1WGebCIj67aud7UIZuEdUMqaKaq', 3600, $request->userId);
        $videoGrant = new VideoGrant();
        $token->addGrant($videoGrant);
        return $this->returnResponse(['token' => $token->toJWT()]);
    }

    /**
     * Generate Token for Voice
     *
     * @param Request $request
     * @return void
     */
    public function generateTokenvoice(Request $request)
    {
        $userId = $request->get('userId');
        $token = new AccessToken('AC609145942d54e3c535cae5de8f5ea53a', 'SK92cfcba96c413345369119a4b5cbd217', '4AOnZNIZcJNecSywIs97JvNAmIKFuyCA', 3600, $userId);
        $VoiceGrant = new VoiceGrant();
        $token->addGrant($VoiceGrant);
        return $this->returnResponse(['token' => $token->toJWT()]);
    }

    /**
     * Get Chat List
     *
     * @param Request $request
     * @return void
     */
    public function chatlist(Request $request)
    {

        if (Auth::check()) {
            $id = Auth::user()->id;
            $searchData = $request->get('searchData');
            $pagesize = $request->get('PageSize');

            $query = Chat::join('users as initiator', 'chat.createdBy', '=', 'initiator.id')
                ->join('users as recepient', 'chat.chatWith', '=', 'recepient.id')
                ->select(
                    'chat.createdBy',
                    'chat.chatWith',
                    'chat.friendlyName',
                    'chat.uniqueName',
                    "chat.channelId",
                    'recepient.category as recepient_category',
                    'initiator.category as initiator_category',
                    'chat.updated_at',
                    DB::raw("CONCAT(initiator.fname,' ',initiator.lname) AS Initiator"),
                    'initiator.avatar AS InitiatorAvatar',
                    DB::raw("CONCAT(recepient.fname,' ',recepient.lname) AS Recepient"),
                    'recepient.avatar AS RecepientAvatar'
                );

            $query->where(function ($query) use ($id) {
                $query->where('chatWith', $id)->orWhere('createdBy', $id);
            });
            $query->where(function ($query) use ($searchData) {
                if ($searchData) {
                    $query->orWhere('recepient.fname', 'LIKE', $searchData . '%')
                        ->orWhere('recepient.lname', 'LIKE', $searchData . '%')
                        ->orWhere('initiator.fname', 'LIKE', $searchData . '%')
                        ->orWhere('initiator.lname', 'LIKE', $searchData . '%');
                }
            });
            //->where('chatWith', $id)->orWhere('createdBy', $id)
            $chatwith = $query->orderBy('chat.updated_at', 'desc')->paginate($pagesize);
            return $this->returnResponse(['user' => $chatwith], 201);
        } else return $this->returnResponse(['error' => 'Unauthorised'], 401);
    }

    /*Chatting */

    public function generateToken(Request $request)
    {
        $validator = Validator::make($request->all(), ['userId' => 'required']);

        if ($validator->fails()) {
            return response()
                ->json($validator->errors()
                    ->toJson(), 400);
        }

        $accountSid = config('services.twilio')['TWILIO_ACCOUNT_SID'];
        $apiKeySid = config('services.twilio')['TWILIO_API_KEY_SID'];
        $apiKeySecret = config('services.twilio')['TWILIO_API_KEY_SECRET'];
        $serviceId = config('services.twilio')['TWILIO_SERVICE_SID'];

        // Log::debug(config('services.twilio'));

        // $token = new AccessToken('AC798d9bcc97595492c0417227fa10f5fe', 'SK3d7c7ed68e729f7a90dbbaa5988ad187', 'kT1hCEVXEpLJu17hcvu3oUTTf1fYcLfz', 3600, $request->userId);
        $token = new AccessToken($accountSid, $apiKeySid, $apiKeySecret, 3600, $request->userId);
        $chatGrant = new ChatGrant();
        // $chatGrant->setServiceSid('IS35b010f5e66341eab2cc615ba8743fa3');
        $chatGrant->setServiceSid($serviceId);
        $token->addGrant($chatGrant);

        return $this->returnResponse(['token' => $token->toJWT()]);
    }


    public function createChannel(Request $request)
    {

        if (Auth::check()) {
            $id = Auth::user()->id;
            $user = User::where('id', $id)->first();
            $createdBy = $user->fname . '_' . $user->lname;
            $validator = Validator::make($request->all(), ['chatWith' => 'required',]);

            if ($validator->fails()) {
                return response()
                    ->json($validator->errors()
                        ->toJson(), 400);
            }
            $chat_to = User::where('id', $request->chatWith)
                ->first();
            if (!$chat_to) {
                return $this->returnResponse(['message' => "Id is not founds"], 404);
            }
            $chat_to_name = $chat_to->fname . '_' . $chat_to->lname;
            $friendlyName = $createdBy . '-' . $chat_to_name;
            $uniqueName = $createdBy . time() . '-' . $chat_to_name . '_linkme';
            $accountSid = config('services.twilio')['TWILIO_ACCOUNT_SID'];
            $authToken =  config('services.twilio')['TWILIO_AUTH_TOKEN'];
            $serviceId = config('services.twilio')['TWILIO_SERVICE_SID'];
            //$sid = 'AC609145942d54e3c535cae5de8f5ea53a';
            //$token = 'c7a535c378ea0dbaad3b967c9b37d757';
            // Log::debug(config('services.twilio'));
            $sid = $accountSid;
            $token = $authToken;
            $twilio = new Client($sid, $token);

            $channellist = Chat::orWhere(function ($query) use ($id, $request) {
                $query->where('chatWith', $id)
                    ->where('createdBy', $request->chatWith);
            })->orWhere(function ($query) use ($id, $request) {
                $query->where('chatWith', $request->chatWith)
                    ->where('createdBy', $id);
            })->first();
            if ($channellist) {
                return $this->returnResponse(['channel' => $channellist]);
            } else {
                $channel = $twilio->chat->v2->services($serviceId)->channels->create(['friendlyName' => $friendlyName, 'uniqueName' => $uniqueName, 'createdBy' => $createdBy]);
                $channels = new Chat;
                $channels->createdBy = $id;
                $channels->chatWith = $request->chatWith;
                $channels->friendlyName = $friendlyName;
                $channels->uniqueName = $uniqueName;
                $channels->channelId = $channel->sid;
                $channels->save();
                return response()
                    ->json(['channel' => $channels]);
            }
        }
    }

    public function deleteChannel(Request $request)
    {
        $accountSid = env('TWILIO_ACCOUNT_SID');
        $authToken = env('TWILIO_AUTH_TOKEN');
        $serviceId = env('TWILIO_SERVICE_SID');
        $twilio = new Client($accountSid, $authToken);
        $channels = $twilio
            ->chat
            ->v2
            ->services($serviceId)
            ->channels($request->channelid)
            ->delete();

        return response()
            ->json(['channel' => $channels]);
    }
    public function updateChat(Request $request)
    {
        if (Auth::check()) {
            $validator = Validator::make($request->all(), ['channelId' => 'required']);

            if ($validator->fails()) {
                return response()
                    ->json($validator->errors()
                        ->toJson(), 400);
            }
            $chat = Chat::where('channelId', $request->channelId)->update(['updated_at' => date("Y-m-d H:i:s")]);
            return response()
                ->json(['message' => 'chat updated successfully'], 200);
        } else {
            return $this->returnResponse(['error' => 'Unauthorised'], 401);
        }
    }
}
