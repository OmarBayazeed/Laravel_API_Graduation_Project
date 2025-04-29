<?php

namespace App\Http\Controllers;

use App\Models\Chat;
use Illuminate\Http\Request;
use Kreait\Firebase\Factory;
use App\Models\ChatMessage;
use App\Services\NotificationSender;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class ChatController extends Controller
{
    public function create_chat(Request $request)
    {
        $request->validate([
            'craftsman_id' => 'required|exists:craftsmen,id',
            'client_id' => 'required|exists:clients,id',
        ]);
        // Check if chat already exists
        $existingChat = Chat::where('craftsman_id', $request->craftsman_id)
            ->where('client_id', $request->client_id)
            ->first();
        if ($existingChat) {
            return response()->json(['message' => 'chat already exist','status' => true],200);
        }
        // Create a new chat
        $chat = Chat::create([
            'craftsman_id' => $request->craftsman_id,
            'client_id' => $request->client_id,
        ]);
        if (!$chat) {
            return response()->json(['message' => 'chat not created','status' => false],500);
        }
        return response()->json(['message' => 'chat created successfully','status' => true],200);
    }

    public function sendMessage(Request $request)
    {
        $firebase = (new Factory)
            ->withServiceAccount(config('firebase.credentials'))
            ->withDatabaseUri(config('firebase.database_url'))
            ->createDatabase(); // ✅ Realtime DB, not Firestore

        if ($request->hasFile('image')) {
            $validator = Validator::make($request->all(), [
                'image' => 'image|mimes:png,jpg,jpeg|max:50120',
                'chat_id' => 'required|exists:chats,id',
                'sender' => 'required|in:craftsman,client',
            ]);
            if ($validator->fails()) {
                return response()->json(['message' => $validator->errors(), 'status' => false], 400);
            }

            $imageName = 'chat_images/' . Str::random() . '.' . $request->image->getClientOriginalExtension();
            Storage::disk('public')->putFileAs('images/', $request->image, $imageName);

            $message = ChatMessage::create([
                'chat_id' => $request->chat_id,
                'sender' => $request->sender,
                'msg' => $imageName,
                'type' => 'image',
            ]);

            $firebase->getReference("chats/{$message->chat_id}/messages")
                ->push([
                    'sender' => $message->sender,
                    'message' => $message->msg,
                    'type' => 'image',
                    'timestamp' => now()->toDateTimeString(),
                ]);
            if (request()->sender == 'craftsman') {
                $user_id = Chat::where('id', $request->chat_id)->client_id;
                $user_type = 'client';
            } else {
                $user_id = Chat::where('id', $request->chat_id)->craftsman_id;
                $user_type = 'craftsman';
            }
            $title = 'New message';
            $body = 'You have a new message';
            $sender = app(NotificationSender::class);
            $result = $sender->send($user_id, $title, $body, $user_type);

            if ($result['status'] === 'success') {
                return response()->json(['message' => 'Image sent successfully with notification', 'status' => true], 200);
            } elseif ($result['status'] === 'partial') {
                return response()->json(['message' => 'Image sent successfully but without some notifications', 'status' => true], 200);
            } else {
                return response()->json(['message' => 'Image sent successfully but without notification', 'status' => true], 200);
            }

        } else {
            $validator = Validator::make($request->all(), [
                'msg' => 'required',
                'chat_id' => 'required|exists:chats,id',
                'sender' => 'required|in:craftsman,client',
            ]);
            if ($validator->fails()) {
                return response()->json(['message' => $validator->errors(), 'status' => false], 400);
            }

            $message = ChatMessage::create([
                'chat_id' => $request->chat_id,
                'sender' => $request->sender,
                'msg' => $request->msg,
                'type' => 'text',
            ]);

            $firebase->getReference("chats/{$message->chat_id}/messages")
                ->push([
                    'sender' => $message->sender,
                    'message' => $message->msg,
                    'type' => 'text',
                    'timestamp' => now()->toDateTimeString(),
                ]);

            if (request()->sender == 'craftsman') {
                $user_id = Chat::where('id', $request->chat_id)->client_id;
                $user_type = 'client';
            } else {
                $user_id = Chat::where('id', $request->chat_id)->craftsman_id;
                $user_type = 'craftsman';
            }
            $title = 'New message';
            $body = 'You have a new message';
            $sender = app(NotificationSender::class);
            $result = $sender->send($user_id, $title, $body, $user_type);

            if ($result['status'] === 'success') {
                return response()->json(['message' => 'message sent successfully with notification', 'status' => true], 200);
            } elseif ($result['status'] === 'partial') {
                return response()->json(['message' => 'message sent successfully but without some notifications', 'status' => true], 200);
            } else {
                return response()->json(['message' => 'message sent successfully but without notification', 'status' => true], 200);
            }
        }
    }
}
