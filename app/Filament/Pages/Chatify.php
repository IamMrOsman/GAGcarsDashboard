<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class Chatify extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static string $view = 'filament.pages.chatify';

    protected static ?string $navigationLabel = 'Chat';

    protected static ?string $title = 'Chat';

    protected static ?string $slug = 'chat';

    protected static ?int $navigationSort = 10;

    public function mount(): void
    {
        // You can add any initialization logic here if needed
    }
}
