<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateUserRequest;
use App\Models\User;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Laravel\Passport\Client as OClient;

class RegisterController extends Controller
{

    public function __construct(Application $app)
    {
        $this->app = $app;

    }
    public function store(CreateUserRequest $request)
    {
        $data = $request->validated();

        $data['password'] = Hash::make($data['password']);

        $user = User::create($data);

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

        $token = $result['access_token'];
        $refresh_token = $result['refresh_token'];

        $response = [
            'status' => 'success',
            'message' => 'user signed up',
            'user' => $user,
            'token' => $token
        ];

        return response()->json($response);
    }
}
