<?php

namespace App\Http\Controllers\API;

use App\Post;
use App\Newsfeeds;
use App\PostComments;
use App\PostReactions;
use Illuminate\Http\Request;
use App\PostcommentReactions;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class Postcommentcontroller extends Controller
{
	/**
	 * Post Community
	 *
	 * @param Request $request
	 * @return void
	 */
	public function PostComments(Request $request)
	{
		if (Auth::check()) {
			$validator = Validator::make($request->all(), ['postid' => 'required|nullable']);

			if ($validator->fails())
				return $this->returnResponse($validator->errors(), 400);

			$postid = $request->postid;
			$comment = $request->comment;

			$commentdata = new PostComments;
			$commentdata->postid = $postid;
			$commentdata->commentBy = auth()->id();
			$commentdata->comment = $comment;
			if ($request->commentparentId)
				$commentdata->commentparentId = $request->commentparentId;
			if ($request->userTags)
				$commentdata->userTags = $request->userTags;
			$commentdata->save();

			$postcomments = count(PostComments::where('postid', '=', $postid)->get());
			/*            $postlikes = count(PostReactions::where('postid', $postid)->where('reactionType', 0)
                ->get());
            $postloves = count(PostReactions::where('postid', $postid)->where('reactionType', 1)
                ->get());
            $reactionjson = ["reactionType" => ["like" => $postlikes, "love" => $postloves]];*/

			if ($commentdata->save())
				Newsfeeds::where('postid', $postid)->update(['commentsCount' => $postcomments]);

			return response()->json(['success' => 'Review Submitted Successfully']);
		} else {
			return $this->returnResponse(['error' => 'Unauthorised']);
		}
	}

	public function postreaction(Request $request)
	{
		$id = Auth::user()->id;
		$validator = Validator::make($request->all(), ['postid' => 'required', 'reactionType' => 'required']);

		if ($validator->fails()) {
			return $this->returnResponse($validator->errors(), 400);
		}

		$postid = $request->postid;
		$reaction = $request->reactionType;

		$userreaction = PostReactions::where('postid', $postid)
			->where('reactionBy', $id)
			->where('reactionType', $reaction)
			->first();

		if (!empty($userreaction)) {
			$userreaction->delete();
		} else {
			$newreaction = new PostReactions;
			$newreaction->postId = $postid;
			$newreaction->reactionType = $reaction;
			$newreaction->reactionBy = $id;
			$newreaction->save();
		}


		$reactions = PostReactions::groupBy('reactionType')
			->select('reactionType', DB::raw('count(reactionType) as count'))
			->where('postId', $postid)
			->get();
		$likecount = 0;
		$lovecount = 0;

		foreach ($reactions as $reaction) {

			if ($reaction->reactionType == 0)
				$likecount = $reaction->count;

			if ($reaction->reactionType == 1)
				$lovecount = $reaction->count;
		}

		$reactionjson = ["reactionType" => ["like" => $likecount, "love" => $lovecount]];

		Newsfeeds::where('postid', $postid)
			->update(['reactionJson' => json_encode($reactionjson)]);

		return response()->json(['postid' => $postid, 'likecount' => $likecount, 'lovecount' => $lovecount]);
	}

	public function getComments(Request $request)
	{
		$id = Auth::user()->id;
		$postid = $request->postid;
		$post = Post::where('id', $postid)->select('createdBy')->first();

		$comments = PostComments::select(
			'postcomments.id',
			'comment',
			'commentBy',
			'reactionCount',
			'commentparentId',
			'userTags',
			'creator.fname',
			'creator.lname',
			'creator.avatar'
		)
			->join('users as creator', 'creator.id', '=', 'commentBy')
			->where('postid', $postid)
			->where(function ($query) use ($id, $post) {
				if ($post->createdBy != $id)
					$query->where('commentBy', '=', $id);
			})
			->get();

		return response()->json(['comments' => $comments]);
	}

	public function getchildComments($commentid)
	{
		$postchildcomments = PostComments::join('users', 'users.id', '=', 'postcomments.commentBy')->where('commentparentId', $commentid)
			->select('users.fname', 'users.lname', 'users.avatar', 'postcomments.*')
			->get();
		if ($postchildcomments) {
			return response()->json(['Childcomments' => $postchildcomments]);
		} else {

			return response()->json(['error' => 'Somthing went wrong']);
		}
	}

	public function commentReaction(Request $request)
	{

		$id = Auth::user()->id;
		$validator = Validator::make($request->all(), ['commentid' => 'required', 'reactionType' => 'required']);

		if ($validator->fails()) {
			return $this->returnResponse($validator->errors()
				->toJson(), 400);
		}
		$commentid = $request->commentid;
		$reaction = $request->reactionType;

		$userreaction = PostcommentReactions::where('reactionBy', $id)
			->where('commentid', $commentid)
			->where('reactionType', 1)
			->first();


		if (!empty($userreaction)) {
			$userreaction->delete();
		} else {
			$newreaction = new PostcommentReactions;
			$newreaction->commentId = $commentid;
			$newreaction->reactionType = $reaction;
			$newreaction->reactionBy = $id;
			$newreaction->save();
		}

		$reaction = PostcommentReactions::select(DB::raw('count(reactionType) as count'))
			->where('commentid', $commentid)
			->where('reactionType', 1)
			->first();

		PostComments::where('id', $commentid)->update(['reactionCount' => $reaction->count]);

		return response()->json(['commentid' => $commentid, 'count' => $reaction->count]);
	}
}
