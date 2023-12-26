<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\User;
use App\Customer;
use App\Customerreview;
use App\Review;
use App\Bookings;
use Validator;

class ReviewController extends Controller
{
    public function createReview(Request $request)
    {

        if (Auth::check()) {
            $validator = Validator::make($request->all(), ['user_id' => 'required', 'user_type' => 'required', 'stars' => 'required']);

            if ($validator->fails()) {
                return response()
                    ->json(['error' => $validator->errors()], 401);
            }

            $id = Auth::user()->id;
            $user_type = User::where('id', $id)->first();

            if ($user_type->user_type === 0) { //customer

                $bookingcheck = Bookings::where('customerId', $id)
				->where('providerId', $request->user_id)
				->where('status', '>=', 3)
				->get();
            } else {

                $bookingcheck = Bookings::where('customerId', $request->user_id)
                   		->where('providerId', $id)
				->where('status', '>=', 3)
				->get();
            }

            if ($bookingcheck->count() > 0) {

                $checkreview = Review::where('user_id', $request->user_id)
                    ->where('user_type', $request->user_type)
                    ->where('rated_by', $id)->get();

                if ($checkreview->count() > 0) {
                    Review::where('rated_by', $id)->where('user_id', $request->user_id)
                        ->update(['stars' => $request->stars, 'comments' => $request->comments]);

                    $reviews = Review::where('user_id', $request->user_id)
                        ->get();
                    $stars = Review::where('user_id', $request->user_id)
                        ->select('stars')
                        ->get();
                    $totalstars = 0;

                    foreach ($stars as $rev) {
                        $totalstars += $rev->stars;
                    }

                    $rating = $totalstars / $stars->count();
                    $reviewcount = $reviews->count();

                    User::where('id', $request->user_id)
                        ->update(['user_rating' => $rating, 'user_reviewcount' => $reviewcount]);

                    return response()->json(['message' => 'Review Updated!']);
                } else {

                    $review = new Review;
                    $review->user_id = $request->user_id;
                    $review->user_type = $request->user_type;
                    $review->stars = $request->stars;
                    $review->comments = $request->comments;
                    $review->rated_by = $id;

                    if ($review->save()) {
                        $reviews = Review::where('user_id', $request->user_id)
                            ->get();
                        $stars = Review::where('user_id', $request->user_id)
                            ->select('stars')
                            ->get();
                        $totalstars = 0;
                        foreach ($stars as $rev) {
                            $totalstars += $rev->stars;
                        }
                        $rating = $totalstars / $stars->count();
                        $reviewcount = $reviews->count();

                        User::where('id', $request->user_id)
                            ->update(['user_rating' => $rating, 'user_reviewcount' => $reviewcount]);
                        return response()->json(["message" => "Review Submitted Successfully"], 200);
                    } else {
                        $reviews = Review::where('user_id', $request->user_id)
                            ->get();
                        $stars = Review::where('user_id', $request->user_id)
                            ->select('stars')
                            ->get();
                        $totalstars = 0;
                        foreach ($stars as $rev) {
                            $totalstars += $rev->stars;
                        }
                        $rating = $totalstars / $stars->count();
                        $reviewcount = $reviews->count();

                        User::where('id', $request->user_id)
                            ->update(['user_rating' => $rating, 'user_reviewcount' => $reviewcount]);
                        return response()->json(["message" => "Failed!!"], 201);
                    }
                }
            } else {
                return response()
                    ->json(["message" => "Not allowed to review"], 400);
            }
        } else {
            return response()
                ->json(["message" => "Unauthorized Access"], 400);
        }
    }

    public function ReviewList($user_type)
    {
        $result = Review::where('user_type', $user_type)->get()
            ->toArray();
        if (!empty($result)) {
            return response($result, 200);
        } else {
            return response()->json(["message" => "Review not found"], 404);
        }
    }
}
