<?php

namespace App\Filament\Widgets;

use App\Support\CompetitorAnalysis;
use Filament\Widgets\Widget;

class CompetitorAnalysisSummaryWidget extends Widget
{
    protected static bool $isDiscovered = false;

    protected static ?int $sort = -10;

    protected static string $view = 'filament.widgets.competitor-analysis-summary-widget';

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return filled(session(CompetitorAnalysis::SESSION_KEY));
    }


    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $analysis = session(CompetitorAnalysis::SESSION_KEY, []);

        return [
            'analysis'    => $analysis,
            'hasAnalysis' => filled($analysis),
        ];
    }
}
