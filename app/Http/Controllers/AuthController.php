<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    public function register(Request $request){

        $response = ['status' => 1 ,'msg' => null];

        $validatedData = Validator::make($request->all(),[
            'name' => 'required|string|max:255|unique:users',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|regex:/^\S*(?=\S{8,})(?=\S*[a-z])(?=\S*[A-Z])(?=\S*[\d])\S*$/',
            'role' => ['required',Rule::in('Particular','Profesional','Administrador')]
        ]);

        if ($validatedData->fails()) {
            $response['status'] = 0;
            $response['msg']['info'] = "Invalid format";
            $response['msg']['error'] = $validatedData->errors();
            return response()->json($response, 400);
        }else{
            try{


                $user = User::create([
                    'name' => $request->input('name'),
                    'email' => $request->input('email'),
                    'password' => Hash::make($request->input('password')),
                    'role' => $request->input('role')
                ]);

                $response['msg']['info'] = 'Register complete succesfully';
                return response()->json($response);
            }catch(\Exception $e){
                $response['status'] = 0;
                $response['msg']['info'] = 'Register failed';
                $response['msg']['error'] = $e;
                return response()->json($response,400);
            }
        }

    }

    public function getUser(Request $request){
        return response()->json($request->user());
    }

    public function login(Request $request){

        if (!Auth::attempt($request->only('name', 'password'))) {
            return response()->json([
                'message' => 'Invalid login details'
            ], 401);
        }

        $user = User::where('name',$request['name'])->firstOrFail();
        $user->tokens()->delete();

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_Token' => $token,
            'token_type' => 'Bearer'
        ]);
    }

    public function recoverPass(Request $request){


        $Pass_pattern = "/^\S*(?=\S{8,})(?=\S*[a-z])(?=\S*[A-Z])(?=\S*[\d])\S*$/";

        $user = User::where('email',$request->email)->first();

        if ($user) {
            do{
                $password = Str::random(8);
            }while(!preg_match($Pass_pattern, $password));
            $user->password = Hash::make($password);
            $user->save();

            return response()->json([
            'new password' => $password
        ]);
        }else{
            return response()->json([
                'message' => 'Not find email in database'
            ], 404);
        }
    }
}
