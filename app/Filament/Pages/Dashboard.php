<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Widgets;

class Dashboard extends BaseDashboard
{
    protected static bool $isDiscovered = false;

    protected static ?string $title = 'Dashboard';

    /**
     * @return array<class-string<Widgets\Widget>|\Filament\Widgets\WidgetConfiguration>
     */
    public function getWidgets(): array
    {
        return [
            Widgets\AccountWidget::class,
            Widgets\FilamentInfoWidget::class,
        ];
    }

    /**
     * @return array<string, int|string|null>
     */
    public function getColumns(): array
    {
        return [
            'default' => 1,
            'xl'      => 2,
        ];
    }
}
