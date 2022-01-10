<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Passport\Client as OClient;


class AuthController extends Controller
{

    public function __construct(Application $app)
    {
        $this->app = $app;

    }

    public function login(Request $request)
    {
        $response = [];
        $conditions = array(
            'email' => $request->input('email'),
            'phone' => $request->input('phone'),
            'password' => $request->input('password')
        );
        /* check if user credentials is okay */
//        dd(Auth::attempt($conditions));

//        if ($this->attempt($conditions)) {
        if ($this->attempt($request)) {

                $response['status'] = 'success';
                $response['message'] = 'Successfully logged in';
                $response["user_data"] = Auth::user();
                // $response['token'] = Auth::user()->createToken('myApp')->accessToken;

                $oClient = OClient::where(
                    [
                        'password_client' => 1,
                        'provider' => 'users'
                    ]
                )->latest()->first();

                $body = [
                    'grant_type' => 'password',
                    'client_id' => $oClient->id,
                    'client_secret' => $oClient->secret,
                    'username' => $request->input('email') ? : $request->input('phone'),
                    'password' => $request->input('password'),
                    'scope' => '*'
                ];

                $request = Request::create('/oauth/token', 'POST', $body);
                $result = $this->app->handle($request);

                $result = json_decode($result->getContent(), true);
                dd($result);

                $response['token'] = $result['access_token'];
                $response['refresh_token'] = $result['refresh_token'];

                return response()->json([$response]);
//            }
        } else {
            $response['failure'] = 'Incorrect email or password';
        }
        return response()->json($response);
    }

    protected function attempt($data){
//        dd($data);
        if($data->input('phone')){
            $user = User::where('phone', $data['phone'])->first();
//            dd($user);
        }else{
            $user = User::where('email', $data['email'])->first();
//            dd($user);

        }

        if($user == null){
            return false;
        }
        return true;
    }
}
