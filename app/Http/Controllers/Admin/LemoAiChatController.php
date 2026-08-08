<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Chat;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LemoAiChatController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $audience = trim((string) $request->query('audience', ''));

        $chats = Chat::query()
            ->with(['user'])
            ->withCount('messages')
            ->when($audience === 'users', fn ($query) => $query->whereNotNull('user_id'))
            ->when($audience === 'guests', fn ($query) => $query->whereNull('user_id'))
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('title', 'like', '%'.$search.'%')
                        ->orWhere('ip_address', 'like', '%'.$search.'%')
                        ->orWhere('device_type', 'like', '%'.$search.'%')
                        ->orWhereHas('user', function ($userQuery) use ($search): void {
                            $userQuery->where('name', 'like', '%'.$search.'%')
                                ->orWhere('email', 'like', '%'.$search.'%');
                        });
                });
            })
            ->orderByDesc('last_message_at')
            ->orderByDesc('id')
            ->paginate(40)
            ->withQueryString();

        return view('admin.lemo-ai.index', [
            'chats' => $chats,
            'filters' => [
                'search' => $search,
                'audience' => $audience,
            ],
            'totalChats' => Chat::count(),
            'userChats' => Chat::whereNotNull('user_id')->count(),
            'guestChats' => Chat::whereNull('user_id')->count(),
        ]);
    }

    public function show(Chat $chat): View
    {
        $chat->load([
            'user',
            'messages' => fn ($query) => $query->orderBy('id'),
        ]);

        return view('admin.lemo-ai.show', [
            'chat' => $chat,
        ]);
    }
}
