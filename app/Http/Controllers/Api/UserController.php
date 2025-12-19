<?php

namespace App\Http\Controllers\Api;

use App\Events\UserLoggedIn;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

final class UserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        Log::info('UserController index');

        $user = $request->user();
        broadcast(new UserLoggedIn($user));

        return response()->json(['success' => true]);
    }
}
