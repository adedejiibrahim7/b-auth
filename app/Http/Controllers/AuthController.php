<?php

namespace App\Http\Controllers;

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
            'password' => $request->input('password')
        );
        /* check if user credentials is okay */
//        dd(Auth::attempt($conditions));

        if (Auth::attempt($conditions)) {

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
                    'username' => $request->input('email'),
                    'password' => $request->input('password'),
                    'scope' => '*'
                ];

                $request = Request::create('/oauth/token', 'POST', $body);
                $result = $this->app->handle($request);

                $result = json_decode($result->getContent(), true);

                $response['token'] = $result['access_token'];
                $response['refresh_token'] = $result['refresh_token'];

                return response()->json([$response]);
//            }
        } else {
            $response['failure'] = 'Incorrect email or password';
        }
        return response()->json($response);
    }

}
