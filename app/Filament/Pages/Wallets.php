<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class Wallets extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-wallet';

    protected static ?string $navigationLabel = 'Wallets';

    protected static ?string $title = 'Wallets';

    protected static ?string $slug = 'wallets';

    protected static ?int $navigationSort = 11;

    protected static string $view = 'filament.pages.wallets';
}

