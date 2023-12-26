<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\User;
use App\Hashmaster;
use Validator;

class Hashmastercontroller extends Controller
{

    public function createhashtags(Request $request)
    {

        if (Auth::check()) {

            $validator = Validator::make($request->all(), ['hastags' => 'required',]);

            if ($validator->fails()) {
                return response()
                    ->json($validator->errors()
                        ->toJson(), 400);
            } else {
                $input = $request->all();
                $hashtags = Hashmaster::create($input);

                return response()->json(['message' => 'Added Hastags Successfully', 'hashtags' => $hashtags], 201);
            }
        }
    }

    public function gethashtags(Request $request)
    {
        $result = Hashmaster::where('hastags', '!=', "")->get();
        return response()->json(['success' => $result]);
    }
}
