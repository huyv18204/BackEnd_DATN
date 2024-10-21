<?php

namespace App\Http\Controllers;

use App\Events\ChatEvent;
use App\Models\ConversationUser;
use App\Models\User;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class ChatController extends Controller
{
    public function SendMessage(Request $request)
    {

        if ($request->user_id) {
            $user = User::query()->find($request->user_id);
        } else {
            $user = JWTAuth::parseToken()->authenticate();
        }
        if ($user) {
//            $conversation = ConversationUser::query()->where('user_id', $user->id)->first();
            broadcast(new ChatEvent($request->message, $user, $request->sender));
        }
        return response()->json($user);
    }
}
