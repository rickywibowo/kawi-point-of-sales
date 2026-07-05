<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class Help extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQuestionMarkCircle;

    protected static ?string $navigationLabel = 'Help';

    protected static string|UnitEnum|null $navigationGroup = 'Support';

    protected static ?int $navigationSort = 100;

    protected static ?string $title = 'Help / Cara Pakai';

    protected static string $routePath = '/help';

    protected string $view = 'filament.pages.help';
}
