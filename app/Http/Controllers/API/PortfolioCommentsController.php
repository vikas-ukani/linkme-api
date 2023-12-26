<?php

namespace App\Http\Controllers\API;

use App\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\DB;
use App\Portfolio;
use App\PortfolioComments;
use App\Portfolioreactions;
use Validator;

class PortfolioCommentsController extends Controller
{
    public function PortComments(Request $request)
    {
        if (Auth::check()) {
            $id = Auth::user()->id;
            // $user = User::findOrFail($id);
            $validator = Validator::make($request->all(), ['photo_id' => 'required', 'comments' => 'required']);
            $photo_id = $request->photo_id;
            $comments = $request->comments;
            if ($validator->fails()) {
                return response()
                    ->json($validator->errors()
                        ->toJson(), 400);
            }

            $commentdata = new PortfolioComments;
            $commentdata->photo_id = $photo_id;
            $commentdata->user_by_id = $id;
            $commentdata->comments = $comments;
            $commentdata->save();

            $portcomments = count(PortfolioComments::where('photo_id', '=', $photo_id)->get());
            $portlikes = count(Portfolioreactions::where('photo_id', $photo_id)->where('reaction_type', 0)
                ->get());
            $portloves = count(Portfolioreactions::where('photo_id', $photo_id)->where('reaction_type', 1)
                ->get());

            Portfolio::where('id', $photo_id)->update(['comments_count' => $portcomments, 'likes_count' => $portlikes, 'loves_count' => $portloves]);

            return response()->json(['success' => 'Review Submitted Successfully']);
        } else {
            return response()
                ->json(['error' => 'Unauthorised']);
        }
    }

      public function portfolioreaction(Request $request){

	$validator = Validator::make($request->all(), ['photo_id' => 'required', 'reaction_type' => 'required']);

	if ($validator->fails()) {
		return response()
		    ->json($validator->errors()
		        ->toJson(), 400);
	}


        $id = Auth::user()->id;
	$photo_id = $request->photo_id;
	$reaction = $request->reaction_type;

	$userreaction = Portfolioreactions::where('user_by_id', $id)
			->where('photo_id', $photo_id)
			->where('reaction_type', $reaction)
			->first();


        if(!empty($userreaction)){
                $userreaction->delete();
        }
        else{
		$commentreaction = new Portfolioreactions;
		$commentreaction->photo_id = $photo_id;
		$commentreaction->user_by_id = $id;
		$commentreaction->reaction_type = $reaction;
		$commentreaction->save();
        }


        $reactions = Portfolioreactions::groupBy('reaction_type')
                        ->select('reaction_type', DB::raw('count(reaction_type) as count'))
			->where('photo_id', $photo_id)
                        ->get();
        $likecount = 0;
        $lovecount = 0;

        foreach($reactions as $reaction){

                if($reaction->reaction_type == 0)
                        $likecount = $reaction->count;

                 if($reaction->reaction_type == 1)
                       $lovecount = $reaction->count;
        }

	Portfolio::where('id', $photo_id)
	->update(['likes_count' => $likecount, 'loves_count' => $lovecount]);

        return response()->json(['photo_id'=>$photo_id, 'likecount' => $likecount,'lovecount'=>$lovecount]);
    }

}
