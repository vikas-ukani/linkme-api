<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Categories;
use Illuminate\Support\Facades\URL;
use Validator;
use Image;

class CategoryController extends Controller
{
    public function create_category(Request $request)
    {

        $validator = Validator::make($request->all(), ['category_name' => 'required',]);
        if ($validator->fails()) {
            return response()
                ->json($validator->errors()
                    ->toJson(), 400);
        }

        $input = $request->all();
        if (!empty($request->catimg)) {
            $input['catimg'] = time() . '.' . $request
                ->catimg
                ->getClientOriginalExtension();
            $request
                ->catimg
                ->move(public_path('catimages'), $input['catimg']);
            $input['catimg'] = URL::to('') . '/catimages/' . $input['catimg'];
        }

        $category = Categories::create($input);
        return response()->json(['message' => 'Category added successfully', 'user' => $category], 201);
    }

    public function categories()
    {
        $categories = Categories::get()->toJson(JSON_PRETTY_PRINT);
        return response($categories, 200);
    }
}
