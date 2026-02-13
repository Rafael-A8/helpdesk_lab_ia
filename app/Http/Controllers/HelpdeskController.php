<?php

namespace App\Http\Controllers;

use App\Ai\AgentResolver;
use App\Models\HelpdeskThread;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Laravel\Ai\Responses\StreamedAgentResponse;

class HelpdeskController extends Controller
{
    public function index(): \Inertia\Response
    {
        return Inertia::render('Helpdesk/Index');
    }

    public function storeThread(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'agent_key' => 'required|in:sales,support,finance',
        ]);

        $thread = HelpdeskThread::create([
            'user_id' => Auth::id(),
            'agent_key' => $request->agent_key,
        ]);

        return response()->json([
            'thread' => $thread,
        ]);
    }

    public function showThread(HelpdeskThread $thread): \Illuminate\Http\JsonResponse
    {
        $messages = [];

        if ($thread->conversation_id) {
            $messages = DB::table('agent_conversation_messages')
                ->where('conversation_id', $thread->conversation_id)
                ->orderBy('created_at')
                ->get(['role', 'content'])
                ->filter(fn ($m) => in_array($m->role, ['user', 'assistant']))
                ->map(fn ($m) => [
                    'role' => $m->role,
                    'content' => $m->content,
                ])
                ->values()
                ->all();
        }

        return response()->json([
            'thread' => $thread,
            'messages' => $messages,
        ]);
    }

    public function streamMessage(Request $request, HelpdeskThread $thread): mixed
    {
        $request->validate([
            'message' => 'required|string',
        ]);

        $user = Auth::user();
        $agentClass = AgentResolver::resolve($thread->agent_key);
        $agent = new $agentClass;

        if ($thread->conversation_id) {
            return $agent
                ->continue($thread->conversation_id, as: $user)
                ->stream($request->message);
        }

        return $agent
            ->forUser($user)
            ->stream($request->message)
            ->then(function (StreamedAgentResponse $response) use ($thread) {
                $thread->update(['conversation_id' => $response->conversationId]);
            });
    }

    public function sendMessage(Request $request, HelpdeskThread $thread): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'message' => 'required|string',
        ]);

        $user = Auth::user();
        $agentClass = AgentResolver::resolve($thread->agent_key);
        $agent = new $agentClass;

        if ($thread->conversation_id) {
            $response = $agent
                ->continue($thread->conversation_id, as: $user)
                ->prompt($request->message);
        } else {
            $response = $agent
                ->forUser($user)
                ->prompt($request->message);

            $thread->update(['conversation_id' => $response->conversationId]);
        }

        return response()->json([
            'response' => $response->text,
        ]);
    }
}
