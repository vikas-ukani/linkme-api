<?php

namespace App\Http\Controllers\API;

use App\Post;
use App\User;
use Validator;
use App\Newsfeeds;
use App\Categories;
use App\Hashmaster;
use App\PostMedias;
use App\PostComments;
use App\Posthashtags;
use App\PostReactions;
use App\PostCategories;
use Illuminate\Http\Request;
use App\PostcommentReactions;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\API\StorePostRequest;

class Postcontroller extends Controller
{

    /**
     * Creating a post
     *
     * @param Request $request
     * @return void
     */
    public function createPost(StorePostRequest $request)
    {
        if (Auth::check()) {
            $user_id = Auth::user()->id;
            $post = new Post;
            $post->description = $request->description;
            $post->status = $request->status;
            $post->createdBy = $user_id;
            $post->save();

            $PostCategories = $request->categoriesid;
            if ($PostCategories) {
                $categories = new PostCategories;
                $categories->postid = $post->id;
                $categories->categoryid = $PostCategories;
                $categories->save();
            }

            $newhastag = $request->newhastag;
            $newhastag = explode(",", $newhastag);

            if ($newhastag) {
                foreach ($newhastag as $key => $value) {
                    $newhastag = new Hashmaster;
                    $newhastag->hastags = $value;
                    $newhastag->save();
                    $addnewtag[] = $newhastag->id;
                }
                $addnewtag_str = implode(",", $addnewtag);
            }

            $Posthashtags = $request->hashtagsid;

            if (!empty($Posthashtags) && !empty($addnewtag_str)) {
                $Posthashtags = $Posthashtags . "," . $addnewtag_str;
            } elseif (!empty($Posthashtags) && empty($addnewtag_str)) {
                $Posthashtags = $Posthashtags;
            } elseif (!empty($addnewtag_str) && empty($Posthashtags)) {
                $Posthashtags = $addnewtag_str;
            } else {
                $Posthashtags = $Posthashtags;
            }

            if ($Posthashtags) {
                $hashtags = new Posthashtags;
                $hashtags->postid = $post->id;
                $hashtags->hashid = $Posthashtags;
                $hashtags->save();
            }

            $PostMedias = $request->media;
            if ($PostMedias) {
                $media = new PostMedias;
                $media->postid = $post->id;
                $media->type = $request->type;
                $media->width = $request->width;
                $media->height = $request->height;
                $PostMedias = time() . '.' . $PostMedias->getClientOriginalExtension();

                $image = $request->file('media');
                $filename = $this->cloudUpload($image->getClientOriginalExtension(), file_get_contents($image));
                $media->mediaUrl = $filename;
                $media->save();
            }

            if ($post->id) {
                $medias = PostMedias::where('postid', $post->id)->first();

                $reactionjson = ["reactionType" => ["like" => 0, "love" => 0]];
                $category = PostCategories::where('postid', $post->id)
                    ->first();
                $categories = explode(',', $category->categoryid);
                $catedetails = Categories::whereIn('id', $categories)->get();
                $cats = [];
                for ($count = 0; $count < count($catedetails); $count++) {
                    $cats[$count]['id'] = $catedetails[$count]->id;
                    $cats[$count]['category_name'] = $catedetails[$count]->category_name;
                }
                // $count = 0;
                // foreach ($catedetails as $cat) {
                //     $cats[$count]['id'] = $cat->id;
                //     $cats[$count]['category_name'] = $cat->category_name;
                //     $count++;
                // }

                //News Feed
                $newsfeeds = new Newsfeeds;
                $newsfeeds->postid = $post->id;
                $newsfeeds->visibilityType = $post->status;
                $newsfeeds->createdBy = $post->createdBy;
                $newsfeeds->categoryJson = json_encode($cats);

                $newsfeeds->feedJson = $medias
                    ? json_encode(["feed" => ['url' => $medias->mediaUrl, 'height' => $medias->height, 'width' => $medias->width, 'description' => $post->description]])
                    : json_encode(["feed" => ['url' => '', 'height' => '', 'width' => '', 'description' => $post->description]]);

                $Hashtags = Posthashtags::where('postid', $post->id)->first();
                $newsfeeds->hashJson = '';
                if ($Hashtags) {
                    $hashtagarray = explode(',', $Hashtags->hashid);
                    $hashdetails = Hashmaster::whereIn('id', $hashtagarray)->get();
                    $hashes = [];
                    $count = 0;
                    foreach ($hashdetails as $hash) {
                        $hashes[$count]['id'] = $hash->id;
                        $hashes[$count]['hastags'] = $hash->hastags;
                        $count++;
                    }
                    $newsfeeds->hashJson = json_encode($hashes);
                }
                $newsfeeds->reactionJson = json_encode($reactionjson);
                $newsfeeds->searchIndex = "test";
                // $newsfeeds->save();

                if ($newsfeeds->save())
                    return response()->json(['message' => 'Added Post Successfully',], 201);
                else {
                    Post::where('id', $post->id)->delete();
                    PostMedias::where('postid', $post->id)->delete();
                    PostCategories::where('postid', $post->id)->delete();
                    Posthashtags::where('postid', $post->id)->delete();
                    return response()->json(['message' => 'Failed to add Post',], 400);
                }
            }
        } else
            return response()->json(['message' => 'Unauthorized Access',], 201);
    }

    /**
     * Getting Posts
     */
    public function posts(Request $request)
    {
        $PageSize = $request->get('PageSize');
        $user = auth('api')->user();
        $category = $request->category;
        $keyword = $request->keyword;

        $posts = Newsfeeds::join('users', 'users.id', '=', 'newsfeeds.createdBy')
            ->join('posts', 'posts.id', '=', 'newsfeeds.postid')
            ->orderBy('newsfeeds.created_at', 'desc')
            ->select(
                'users.id',
                'users.user_type',
                'users.fname',
                'users.lname',
                'users.email',
                'users.avatar',
                'newsfeeds.postid',
                "newsfeeds.visibilityType",
                'newsfeeds.feedJson',
                'newsfeeds.categoryJson',
                'newsfeeds.hashJson',
                'newsfeeds.reactionJson',
                'posts.views as postview',
                'newsfeeds.createdBy',
                'newsfeeds.created_at',
                DB::raw('case when "newsfeeds"."visibilityType" = 1 and "newsfeeds"."createdBy" <> '
                    . $user->id .
                    '  then (select count(id) from postcomments as pc where pc.postid = "newsfeeds"."postid" and "pc"."commentBy" = '
                    . $user->id .
                    ') else "newsfeeds"."commentsCount" end as commentsCount')
            )
            ->where(function ($result) use ($category, $keyword) {

                if (is_numeric($category) && $category > 0) // If 0 then return all category posts.
                    $result->whereJsonContains('categoryJson', [['id' => (int)$category]]);

                if (!empty($keyword))
                    $result->where('feedJson', 'like', "%{$keyword}%")->orWhere('hashjson', 'like', "%#{$keyword}%");
            })
            ->paginate($PageSize);
        return response()->json(['community' => $posts], 200);
    }

    /**
     * Post Reaction.
     *
     * @param Request $request
     * @return void
     */
    public function postreaction(Request $request)
    {
        $id = Auth::user()->id;
        $validator = Validator::make($request->all(), ['postid' => 'required', 'reactionType' => 'required']);

        if ($validator->fails()) return $this->returnResponse($validator->errors()->toJson(), 400);

        $postid = $request->postid;
        $reaction = $request->reactionType;

        $userreaction = PostReactions::where('postid', $postid)->where('reactionBy', $id)->where('reactionType', $reaction)->first();
        if ($userreaction) $userreaction->delete();
        else PostReactions::create(['postid' => $postid, 'reactionType' => $reaction, 'reactionBy' => $id,]);

        $reactions = PostReactions::groupBy('postreactions.reactionType')
            ->select('reactionType', DB::raw('count("reactionType") as count'))
            ->where('postid', $postid)->get();
        $likecount = 0;
        $lovecount = 0;

        foreach ($reactions as $reaction) {
            if ($reaction->reactionType == 0)
                $likecount = $reaction->count;

            if ($reaction->reactionType == 1)
                $lovecount = $reaction->count;
        }
        $reactionjson = ["reactionType" => ["like" => $likecount, "love" => $lovecount]];
        Newsfeeds::where('postid', $postid)->update(['reactionJson' => json_encode($reactionjson)]);
        return response()->json(['postid' => $postid, 'likecount' => $likecount, 'lovecount' => $lovecount]);
    }



    public function CategoryPost($catid, Request $request)
    {
        $cats = PostCategories::get();
        $postids = [];
        $count = 0;

        foreach ($cats as $cat) {
            $catarray = explode(',', $cat->categoryid);
            if (in_array($catid, $catarray)) {
                $postids[$count] = $cat->postid;
            }
            $count++;
        }
        $PageSize = $request->get('PageSize');
        $user = auth('api')->user();
        if ($user) {
            $posts = Newsfeeds::join('users', 'users.id', '=', 'newsfeeds.createdBy')->where('createdBy', $user->id)
                ->whereIn('postid', $postids)->select('users.id', 'users.user_type', 'users.fname', 'users.lname', 'users.email', 'users.avatar', 'newsfeeds.*')
                ->orderBy('newsfeeds.created_at', 'desc')
                ->paginate($PageSize);
            return response()->json(['community' => $posts], 201);
        } else {
            $posts = Newsfeeds::join('users', 'users.id', '=', 'newsfeeds.createdBy')->where('visibilityType', 0)
                ->whereIn('postid', $postids)->select('users.id', 'users.user_type', 'users.fname', 'users.lname', 'users.email', 'users.avatar', 'newsfeeds.*')
                ->orderBy('newsfeeds.created_at', 'desc')
                ->paginate($PageSize);
            return response()->json(['community' => $posts], 201);
        }
    }

    /**
     * Returning post view counts
     *
     * @param int $postid
     * @return void
     */
    public function countPostView($postid)
    {
        if (!empty($postid)) {
            $views = Post::findOrFail($postid);
            $views->views += 1;
            $views->save();
            return $this->returnResponse(['success' => ' success'], 200); // 200
        } else {
            return $this->returnResponse(['error' => 'not success']);
        }
    }

    /**
     * Post Details API.
     *
     * @param Request $request
     * @param int $post
     * @return void
     */
    public function postDetail(Request $request, $postid)
    {
        $user = auth('api')->user();
        $postDetail = Newsfeeds::where('postid', $postid)->first();
        if ($request->get('views') == "true") {
            $postDetail->postviews += 1;
        }

        $views = Post::findOrFail($postid);
        if ($request->get('views') == "true") {
            $views->views += 1;
            $views->save();
            Newsfeeds::where('postid', $postid)->update(['postviews' => $views->views]);
        }

        $details = Newsfeeds::join('users', 'users.id', '=', 'newsfeeds.createdBy')
            ->join('posts', 'posts.id', '=', 'newsfeeds.postid')
            ->where('postid', $postid)
            ->select(
                'users.fname',
                'users.lname',
                'users.email',
                'users.avatar',
                'users.user_type',
                'newsfeeds.postid',
                "newsfeeds.visibilityType",
                'newsfeeds.feedJson',
                'newsfeeds.categoryJson',
                'newsfeeds.hashJson',
                'newsfeeds.reactionJson',
                'posts.views as postview',
                'newsfeeds.createdBy',
                'newsfeeds.created_at',
                DB::raw('case when "newsfeeds.visibilityType" = 1 and newsfeeds.createdBy <> '
                    . $user->id . '  then (select count(id) from postcomments as pc where pc.postid = newsfeeds.postid and pc.commentBy = '
                    . $user->id . ') else newsfeeds.commentsCount end as commentsCount')
            )
            ->get();
        return $this->returnResponse($details, 200); // 200
    }

    public function getcommunity(Request $request)
    {

        $PageSize = $request->get('PageSize');

        $data = $request->get('data');
        $hastags = preg_replace('/#(\\w+)/', '', $data);
        $category = $request->get('category');

        //->whereJsonContains('categoryJson', [['id' => (int)$category]])
        if (!empty($category)) {
            if ($hastags) {
                $searchpost = Newsfeeds::join('users', 'users.id', '=', 'newsfeeds.createdBy')
                    ->where('categoryJson', '=', $category)
                    ->orWhere('feedJson', 'like', "%{$data}%")
                    ->orWhere('hashjson', 'like', "%{$hastags}%")
                    ->select('users.fname', 'users.lname', 'users.email', 'users.avatar', 'users.user_type', 'newsfeeds.*')
                    ->paginate($PageSize);
                return response()->json(['data' => $searchpost]);
            } else {
                $searchpost = Newsfeeds::join('users', 'users.id', '=', 'newsfeeds.createdBy')
                    ->where('feedJson', 'like', "%{$data}%")
                    ->where('categoryJson', '=', $category)
                    ->select('users.fname', 'users.lname', 'users.email', 'users.avatar', 'users.user_type', 'newsfeeds.*')
                    ->paginate($PageSize);
                return response()->json(['data' => $searchpost]);
            }
        } else {
            if ($hastags) {
                $searchpost = Newsfeeds::join('users', 'users.id', '=', 'newsfeeds.createdBy')
                    ->orWhere('feedJson', 'like', "%{$data}%")
                    ->orWhere('hashjson', 'like', "%{$hastags}%")
                    ->select('users.fname', 'users.lname', 'users.email', 'users.avatar', 'users.user_type', 'newsfeeds.*')
                    ->paginate($PageSize);
                return response()->json(['data' => $searchpost]);
            } else {
                $searchpost = Newsfeeds::join('users', 'users.id', '=', 'newsfeeds.createdBy')
                    ->where('feedJson', 'like', "%{$data}%")
                    ->select('users.fname', 'users.lname', 'users.email', 'users.avatar', 'users.user_type', 'newsfeeds.*')
                    ->paginate($PageSize);
                return response()->json(['data' => $searchpost]);
            }
        }
    }

    public function reactionList(Request $request)
    {
        $validator = Validator::make($request->all(), ['postid' => 'required']);

        if ($validator->fails())
            return response()->json($validator->errors()->toJson(), 400);

        $list = PostReactions::join('users', 'users.id', '=', 'postreactions.reactionBy')->where('postid', $request->postid)
            ->select('users.id', 'users.fname', 'users.lname', 'users.avatar', 'user_type', 'postreactions.reactionType')
            ->get();

        return response()
            ->json(['userlist' => $list], 200);
    }

    public function shareProfile(Request $request)
    {
        $validator = Validator::make($request->all(), ['providerId' => 'required']);

        if ($validator->fails()) return response()
            ->json($validator->errors()
                ->toJson(), 400);

        $user = Auth::user();
        $user_id = Auth::user()->id;

        $provider = User::where('id', $request->providerId)
            ->where('user_type', 1)
            ->first();

        if (!$provider) return response()->json(['message' => 'Incorrect Provider'], 400);

        $post = new Post;
        $post->description = $provider->fname . ' ' . $provider->lname;
        $post->status = 0;
        $post->createdBy = $user_id;
        $post->save();

        $newsfeeds = new Newsfeeds;
        $newsfeeds->postid = $post->id;
        $newsfeeds->visibilityType = $post->status;
        $newsfeeds->createdBy = $post->createdBy;
        $newsfeeds->categoryJson = json_encode('');
        $newsfeeds->hashJson = json_encode('');
        $newsfeeds->reactionJson = json_encode(["reactionType" => ["like" => 0, "love" => 0]]);
        $newsfeeds->searchIndex = json_encode('');

        $m = ["feed" => ['url' => $provider->avatar, 'height' => 0, 'width' => 0, 'description' => $post->description, 'redirectTo' => $provider->id]];
        $newsfeeds->feedJson = json_encode($m);
        $newsfeeds->save();

        if ($newsfeeds->id) {
            return $this->returnResponse(['message' => 'Profile shared Successfully',], 201);
        } else {

            Post::where('id', $post->id)
                ->delete();
            PostMedias::where('postid', $post->id)
                ->delete();
            PostCategories::where('postid', $post->id)
                ->delete();
            Posthashtags::where('postid', $post->id)
                ->delete();
            return $this->returnResponse(['message' => 'Profile could not be shared',], 400);
        }
    }



    /**
     * Comment and Reactions
     *
     * @param Request $request
     * @return void
     */
    public function commentReaction(Request $request)
    {
        $id = Auth::user()->id;
        $validator = Validator::make($request->all(), ['commentid' => 'required', 'reactionType' => 'required']);

        if ($validator->fails()) return $this->returnResponse($validator->errors(), 400);

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


    public function getComments(Request $request)
    {
        $id = Auth::user()->id;
        $postid = $request->postid;
        $pagesize = $request->PageSize;
        $post = Post::where('id', $postid)->select('createdBy', 'status')->first();

        $comments = PostComments::select(
            'postcomments.id',
            'comment',
            'commentBy',
            'reactionCount',
            'commentparentId',
            'userTags',
            'postid',
            'creator.fname',
            'creator.lname',
            'creator.avatar'
        )
            ->join('users as creator', 'creator.id', '=', 'commentBy')
            ->where('postid', $postid)
            ->whereNull('commentparentId')
            ->where(function ($query) use ($id, $post) {
                if ($post->status == 1 && $post->createdBy != $id) // 1=private post
                    $query->where('commentBy', '=', $id);
            })
            ->OrderBy('postcomments.created_at', 'desc')
            ->paginate($pagesize);

        return response()->json(['comments' => $comments]);
    }

    public function getchildComments(Request $request)
    {
        $commentid = $request->commentid;
        $pagesize = $request->PageSize;
        $postchildcomments = PostComments::join('users', 'users.id', '=', 'postcomments.commentBy')
            ->where('commentparentId', $commentid)
            ->select('users.fname', 'users.lname', 'users.avatar', 'postcomments.*')
            ->paginate($pagesize);
        if ($postchildcomments) {
            return response()->json(['Childcomments' => $postchildcomments]);
        } else {

            return response()->json(['error' => 'Somthing went wrong']);
        }
    }
}
