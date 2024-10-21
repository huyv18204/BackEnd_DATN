<?php

use Illuminate\Support\Facades\Broadcast;
use Tymon\JWTAuth\Facades\JWTAuth;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int)$user->id === (int)$id;
});

Broadcast::channel('chat.{conversation_id}', function ($user, $conversation_id) {
    $user_token = JWTAuth::parseToken()->authenticate();
    $conversation_user = \App\Models\ConversationUser::query()
        ->where('conversation_id', (int)$conversation_id)
        ->where('user_id', $user_token->id)
        ->first();
    return $conversation_user != null;

});
