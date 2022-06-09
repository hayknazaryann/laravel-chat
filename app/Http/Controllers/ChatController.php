<?php

namespace App\Http\Controllers;

use App\Events\ChatMessages;
use App\Events\MessageSent;
use App\Events\UsersActivity;
use App\Http\Requests\SendMessageRequest;
use App\Jobs\SendEmailJob;
use App\Models\Chat;
use App\Models\Message;
use App\Models\User;
use App\Notifications\MessageNotify;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ChatController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $auth_user = User::find(auth()->id());
        return view('chats.index', compact( 'auth_user'));
    }

    public function getUsers(Request $request){
        $users = User::where('id', '!=', auth()->id())->get();
        return response()->json(['success' => true, 'users' => $users],200);
    }

    public function openChat(Request $request){
        try{
            $receiver_id = $request->receiver_id;
            if ($receiver = User::find($receiver_id)){
                $chat = Chat::where(['user1_id' => auth()->id(), 'user2_id' => $receiver->id])->orWhere(function ($q) use ($receiver){
                    $q->where(['user2_id' => auth()->id(), 'user1_id' => $receiver->id]);
                })->first();
                if (!$chat){
                    $chat = Chat::create(['user1_id' => auth()->id(), 'user2_id' => $receiver->id]);
                }
            }
            $messages = $chat->messages()->with('sender')->get();
            return response()->json(['success' => true, 'receiver' => $receiver, 'chat' => $chat, 'messages' => $messages]);
        }catch (\Exception $exception){
            dd($exception->getMessage());
            Log::error($exception->getMessage());
            return response()->json(['success' => false, 'error' => 'Something went wrong']);
        }
    }
    public function getMessages(Request $request)
    {
        try{
            $receiver_id = $request->receiver_id;
            if ($receiver = User::find($receiver_id)){

                $messages = Message::with(['sender'])->where(['sender_id' => auth()->id(), 'receiver_id' => $receiver_id])->orWhere(function ($q) use ($receiver_id){
                    $q->where(['sender_id' => $receiver_id, 'receiver_id' => auth()->id()]);
                })->orderBy(Message::CREATED_AT)->get()->toArray();
                return response()->json(['success' => true, 'messages' => $messages, 'receiver' => $receiver],200);
            }
            return response()->json(['success' => false, 'error' => 'User not found'],400);
        }catch (\Exception $exception){
            Log::error($exception->getMessage());
            return response()->json(['success' => false, 'error' => 'Something went wrong'],400);
        }
    }

    public function sendMessage(SendMessageRequest $request){
        try{

            if ($chat = Chat::find($request->chat_id)){
                $new_message = $chat->messages()->create([
                    'sender_id' => auth()->id(),
                    'message' => $request->message
                ])->load('sender');

                if (!Cache::has('user-online-' . $request->receiver_id)){
                    $receiver = User::find($request->receiver_id);
                    dispatch(new SendEmailJob($receiver, $new_message->sender));
                }
                broadcast(new MessageSent($new_message->load('sender')))->toOthers();
                return response()->json(['success' => true, 'message' => $new_message],200);
            }
            return response()->json(['success' => false, 'error' => 'Chat not found'],400);

        }catch (\Exception $exception){
            dd($exception->getMessage());
            Log::error($exception->getMessage());
            return response()->json(['success' => false, 'error' => 'Something went wrong'],400);
        }
    }
}
