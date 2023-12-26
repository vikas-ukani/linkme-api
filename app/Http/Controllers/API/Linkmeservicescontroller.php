<?php

namespace App\Http\Controllers\API;

use App\User;
use Validator;
use App\Bookings;
use App\ServiceReview;
use App\Linkmeservices;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class Linkmeservicescontroller extends Controller
{

    /**
     * Getting All Active services
     *
     * @return void
     */
    public function index()
    {
        return $this->returnResponse(['data' => Linkmeservices::where('isActive', 1)->orderBy('created_at', 'DESC')->get()], 200);
    }

    /**
     * Create Service
     *
     * @param Request $request
     * @return void
     */
    public function createService(Request $request)
    {
        if (Auth::check()) {
            $user = Auth::user();
            if (is_null($user->stripe_connect_id)) {
                $url = config('services.stripe.connect') . base64_encode($user->id);
                return $this->returnResponse(['error' => "Your Stripe account not setup! Please setup stripe account using stripe connect url.", 'strip_connect_url' => $url], 401);
            }
            $input = $request->validated();
            if ($user->user_type === 1) {
                $input['provider_id'] = $user->id;
                $image = $request->file('service_img');
                $input['service_img'] = null;
                if (!empty($image))
                    $input['service_img'] = $this->cloudUpload($image->getClientOriginalExtension(), file_get_contents($image));
                $data = Linkmeservices::create($input);
                return $this->returnResponse(['success' => 'Data Added successfully', 'data' => $data], 200);
            } else
                return $this->returnResponse(['message' => 'user not authorized to use'], 400);
        }
    }

    /**
     * Update Service
     *
     * @param Request $request
     * @param int $serviceId
     * @return void
     */
    public function update(Request $request, $serviceId)
    {
        if (Auth::check()) {
            $service = Linkmeservices::where('id', $serviceId)->first();
            if ($service->provider_id == Auth::user()->id) {
                $input = $request->all();
                $service->fill($input)->save(); // Mass fill details.
                // if (!empty($request->before_24_cancellation) || $request->before_24_cancellation == '0') {
                //     $updatedata = Linkmeservices::where('id', $serviceId)->update(['before_24_cancellation' => $request->before_24_cancellation]);
                // }

                // if (!empty($request->after_24_cancellation) || $request->after_24_cancellation == '0') {
                //     $updatedata = Linkmeservices::where('id', $serviceId)->update(['after_24_cancellation' => $request->after_24_cancellation]);
                //     //dd($updatedata);
                // }

                // if ($request->description) {
                //     $updatedata = Linkmeservices::where('id', $serviceId)->update(['description' => $request->description]);
                // }

                if ($input['service_img']) {
                    $image = $request->file('service_img');
                    $service->service_img = $this->cloudUpload($image->getClientOriginalExtension(), file_get_contents($image));
                    $service->save();
                    // $updatedata = Linkmeservices::where('id', $serviceId)->update(['service_img' => $filename]);
                }

                // $service = Linkmeservices::where('id', $serviceId)->first();
                return $this->returnResponse(['success' => 'Service Updated successfully', 'data' => $service], 200);
            } else {
                return $this->returnResponse(['message' => 'user not authorized to use'], 400);
            }
        }
    }

    /**
     * Service Delete 
     *
     * @param int $serviceId
     * @return void
     */
    public function destroy($serviceId)
    {
        if (Auth::check()) {
            $service = Linkmeservices::where('id', $serviceId)->first();
            if ($service->provider_id == Auth::user()->id) {
                $service->isActive = 0;
                $service->save();
                return $this->returnResponse(['message' => 'service deleted Successfully', 'data' => $service]);
            } else
                return $this->returnResponse(['message' => 'user not authorized to use'], 400);
        }
    }

    /**
     * Get Service by ID
     *
     * @param int $id
     * @param Request $request
     * @return void
     */
    public function show($id, Request $request)
    {
        $PageSize = $request->get('PageSize');
        $data = Linkmeservices::join('users', 'users.id', '=', 'linkmeservices.provider_id')
            ->select(
                'linkmeservices.*',
                'users.fname',
                'users.lname',
                'users.email',
                'users.address',
                'users.avatar',
                'users.latitude',
                'users.longitude'
            )
            ->where('provider_id', '=', $id)->orderBy('created_at', 'DESC')
            ->where('isActive', 1)
            ->paginate($PageSize);
        return $this->returnResponse(['data' => $data], 200);
    }

    /**
     * Get Service By Id
     *
     * @param int $id
     * @return void
     */
    public function servicedetails($id)
    {
        if ($service = Linkmeservices::where('id', $id)->find()) {
            return $this->returnResponse(['details' => $service]);
        } else {
            return $this->returnResponse(["message" => "Not found"], 404);
        }
    }

    public function Servicerating(Request $request)
    {
        if (Auth::check()) {
            $validator = Validator::make($request->all(), ['providerId' => 'required', 'bookingid' => 'required', 'serviceId' => 'required', 'stars' => 'required']);

            if ($validator->fails()) {
                return response()
                    ->json(['error' => $validator->errors()
                        ->first()], 401);
            }
            $id = Auth::user()->id;

            $servicerating = new ServiceReview;
            $servicerating->serviceId = $request->serviceId;
            $servicerating->stars = $request->stars;
            $servicerating->customer_id = $id;
            $servicerating->providerId = $request->providerId;

            if ($request->comments) {
                $servicerating->comments = $request->comments;
            }

            $servicerating->save();

            Bookings::where('id', $request->bookingid)
                ->update(['status' => 4]);

            $serviceRating = ServiceReview::selectRaw('AVG(stars) as avgRating,count(stars) as totalReviews')
                ->where('serviceId', $request->serviceId)
                ->first();

            $providerRating = ServiceReview::selectRaw('AVG(stars) as avgRating,count(stars) as totalReviews')
                ->where('providerId', $request->providerId)
                ->first();

            Linkmeservices::where('id', $request->serviceId)
                ->update(['total_reviews' => $serviceRating->totalReviews, 'avg_rating' => $serviceRating->avgRating]);

            User::where('id', $request->providerId)
                ->update(['user_reviewcount' => $providerRating->totalReviews, 'user_rating' => $providerRating->avgRating]);

            return $this->returnResponse(['reviewdetails' => $serviceRating], 200);
        } else {
            return $this->returnResponse(['error' => 'Unauthorized'], 405);
        }
    }
}
