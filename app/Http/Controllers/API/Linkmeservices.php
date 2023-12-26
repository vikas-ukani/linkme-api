<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\User;
use Illuminate\Http\Response;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\URL;
use App\Notifications\PasswordResetRequest;
use App\Notifications\PasswordResetSuccess;
use App\PasswordReset;
use App\Review;
use App\Linkmeservices;
use Validator;
use Image;

class Linkmeservicescontroller extends Controller
{
    public function Createservice(Request $request)
    {

        if (Auth::check()) {

            $validator = Validator::make($request->all(), ['title' => 'required', 'category' => 'required', 'duration' => 'required', 'price' => 'required', 'before_24_cancellation' => 'required', 'after_24_cancellation' => 'required', 'description' => 'required', 'service_img' => 'required']);

            if ($validator->fails()) {
                return response()
                    ->json(['error' => $validator->errors()
                        ->first()], 401);
            }

            $input = $request->all();
            $id = Auth::user()->id;
            $user = User::findOrFail($id);
            $input['provider_id'] = $id;
            $image = $request->file('service_img');
            $new_name = rand() . '.' . $image->getClientOriginalExtension();
            $image->move(public_path('serviceimages'), $new_name);
            $input['service_img'] = URL::to('') . '/' . $new_name;

            $data = Linkmeservices::create($input);
            if ($data) {
                return response()->json(['success' => 'Data Added successfully', 'data' => $data], $this->successStatus);
            } else {
                return response()
                    ->json(['error' => 'Unauthorised']);
            }
        }
    }
}
