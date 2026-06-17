<?php

namespace App\Filament\Widgets;

use App\Support\CompetitorAnalysis;
use Filament\Widgets\Widget;
use Livewire\Attributes\On;

class CompetitorAnalysisSummaryWidget extends Widget
{
    protected static bool $isDiscovered = false;

    protected static ?int $sort = -10;

    protected static string $view = 'filament.widgets.competitor-analysis-summary-widget';

    protected int|string|array $columnSpan = 'full';

    /**
     * @var array<string, mixed>
     */
    public array $competitorAnalysis = [];

    #[On('competitor-analysis-updated')]
    public function refreshAnalysis(): void
    {
        $this->competitorAnalysis = session(CompetitorAnalysis::SESSION_KEY, []);
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $analysis = $this->competitorAnalysis ?: session(CompetitorAnalysis::SESSION_KEY, []);

        return [
            'analysis' => $analysis,
            'hasAnalysis' => filled($analysis),
        ];
    }
}
