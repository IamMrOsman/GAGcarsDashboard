<?php

namespace App\Filament\Widgets;

use Filament\Widgets\AccountWidget as BaseWidget;

class AccountWidget extends BaseWidget
{
	protected static ?int $sort = 1;

	protected int|string|array $columnSpan = 'full';
}
