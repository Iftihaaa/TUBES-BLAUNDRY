<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\Dashboard;
use Filament\Widgets\Widget;
use Livewire\Attributes\On;

class CompetitorAnalysisDetailWidget extends Widget
{
    protected static bool $isDiscovered = false;

    protected static ?int $sort = -8;

    protected static string $view = 'filament.widgets.competitor-analysis-detail-widget';

    protected int|string|array $columnSpan = 'full';

    /**
     * @var array<string, mixed>
     */
    public array $competitorAnalysis = [];

    public static function canView(): bool
    {
        return filled(session(Dashboard::COMPETITOR_ANALYSIS_SESSION_KEY));
    }

    #[On('competitor-analysis-updated')]
    public function refreshAnalysis(): void
    {
        $this->competitorAnalysis = session(Dashboard::COMPETITOR_ANALYSIS_SESSION_KEY, []);
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        return [
            'analysis' => $this->competitorAnalysis ?: session(Dashboard::COMPETITOR_ANALYSIS_SESSION_KEY, []),
        ];
    }
}
