<?php

namespace App\Http\Controllers;

use App\Http\Requests\AuthLoginRequest;
use App\Http\Requests\AuthRegisterRequest;
use App\Http\Requests\AuthUpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\Email;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AuthController extends Controller
{
    public function register(AuthRegisterRequest $request)
    {
        $user->save();

        return $token;
    }

    public function login(AuthLoginRequest $request)
    {
        $email = Email::find($email);

        return $token;
    }

    public function logout(Request $request): Response
    {
        return response()->noContent();
    }

    public function getUser(Request $request): UserResource
    {
        return new UserResource($User);
    }

    public function updateUser(AuthUpdateUserRequest $request): UserResource
    {
        $user->update($request->validated());

        return new UserResource($User);
    }
}
