<?php

namespace App\Filament\Resources\AnalisisCashflowResource\Widgets;

use App\Models\AnalisisCashflow;
use Filament\Widgets\Widget;

class LatestCashflowInsight extends Widget
{
    protected static string $view = 'filament.resources.analisis-cashflow-resource.widgets.latest-cashflow-insight';
    protected int|string|array $columnSpan = '1';

    public function getLatestAnalisis(): ?AnalisisCashflow
    {
        return AnalisisCashflow::latest()->first();
    }
}