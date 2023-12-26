<?php

namespace App\Http\Controllers\API;

use App\User;
use Validator;
use App\Portfolio;
use App\PortfolioComments;
use App\Portfolioreactions;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class PortfolioController extends Controller
{

    /**
     * Fetch Portfolio data.
     *
     * @param Request $request
     * @return void
     */
    public function index(Request $request)
    {
        $validator = Validator::make($request->all(), ['userid' => 'required']);
        if ($validator->fails())
            return response()->json($validator->errors(), 400);

        $result = Portfolio::where('user_id', $request->userid)
            ->orderBy('created_at', 'DESC')
            ->paginate($request->PageSize)
            ->prepend(['id' => -1])
            ->toArray();

        if (!empty($result)) return response()->json(['success' => $result]);
        else return response()->json(["message" => "Data not found"], 404);
    }

    /**
     * Portfolio by ID
     *
     * @param int $id
     * @return void
     */
    public function getPortfolio($id)
    {
        $result = Portfolio::Leftjoin('portfolio_comments', 'portfolio_comments.photo_id', '=', 'portfolio.id')
            ->select('portfolio.image', 'portfolio.user_id', 'portfolio_comments.*')
            ->where('portfolio.id', $id)->get()
            ->toArray();

        if (!empty($result)) return response()->json(['success' => $result]);
        else return response()->json(["message" => "Data not found"], 404);
    }

    /**
     * Portfolio updates.
     *
     * @param Request $request
     * @return void
     */
    public function upload(Request $request)
    {
        if (Auth::check()) {
            $id = Auth::user()->id;
            $validator = Validator::make($request->all(), [
                'image' => 'required|mimes:jpeg,png,jpg,gif,svg'
            ]);

            if ($validator->fails()) return response()->json($validator->errors(), 400);

            $image = $request->file('image');
            $filename = $this->cloudUpload($image->getClientOriginalExtension(), file_get_contents($image));
            $input['user_id'] = $id;
            $input['image'] = $filename;

            if ($request->userTags)
                $input['userTags'] = $request->userTags;

            $img = Portfolio::create($input);
            return response()->json(['success' => 'Image Uploaded Successfully', 'detail' => $img]);
        }
    }

    /**
     * Portfolio Details
     *
     * @param int $id
     * @return void
     */
    public function show($id)
    {
        if (Portfolio::where('id', $id)->exists()) {
            $portfolio = Portfolio::where('id', $id)->get();
            // $comments = PortfolioComments::where('photo_id', '=', $id)->get();
            $users = User::join('portfolio_comments', 'portfolio_comments.user_by_id', '=', 'users.id')->where('portfolio_comments.photo_id', $id)->get();
            $likes = Portfolioreactions::where('photo_id', '=', $id)->where('reaction_type', '=', 0)->get();
            $loves = Portfolioreactions::where('photo_id', '=', $id)->where('reaction_type', '=', 1)->get();
            return response()->json(['details' => $portfolio, 'comments' => $users, 'likes' => $likes, 'loves' => $loves]);
        } else
            return response()->json(["message" => "Not found"], 404);
    }

    /**
     * My Portfolio
     *
     * @return void
     */
    public function myPortfolio()
    {
        return response()->json(['data' => Portfolio::where('user_id', '=', auth()->id())->orderBy('created_at', 'DESC')->get()]);
    }

    /**
     * Delete portfolio by id.
     *
     * @param [type] $id
     * @return void
     */
    public function destroy($id)
    {
        $data = Portfolio::findOrFail($id);
        $data->delete();
        return response()->json(['message' => 'Photo deleted Successfully', 'data' => $data]);
    }

    /**
     * Reactions List
     *
     * @param Request $request
     * @return void
     */
    public function reactionList(Request $request)
    {
        $validator = Validator::make($request->all(), ['photo_id' => 'required']);
        if ($validator->fails())
            return response()->json($validator->errors()->toJson(), 400);

        $list = Portfolioreactions::join('users', 'users.id', '=', 'portfolio_reactions.user_by_id')->where('photo_id', $request->photo_id)
            ->select('users.id', 'users.fname', 'users.lname', 'users.avatar', 'user_type', 'portfolio_reactions.reaction_type')
            ->get();
        return response()->json(['userlist' => $list], 200);
    }
}
