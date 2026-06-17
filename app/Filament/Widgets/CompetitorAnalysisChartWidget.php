<?php

namespace App\Filament\Widgets;

use App\Support\CompetitorAnalysis;
use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget;

class CompetitorAnalysisChartWidget extends ChartWidget
{
    protected static bool $isDiscovered = false;

    protected static ?int $sort = -9;

    protected static ?string $heading = 'Visual Skor Kompetitor';

    protected static ?string $maxHeight = '320px';

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return filled(session(CompetitorAnalysis::SESSION_KEY));
    }

    public function rendering(): void
    {
        $this->cachedData = null;
    }

    protected function getType(): string
    {
        return 'bar';
    }

    public function getDescription(): ?string
    {
        return 'Perbandingan skor heuristik dari JSON Gemini. Angka hanya berbasis input user.';
    }

    /**
     * @return array<string, mixed>
     */
    protected function getData(): array
    {
        $analysis = session(CompetitorAnalysis::SESSION_KEY, []);
        $chartData = $analysis['chart_data'] ?? [];

        return [
            'datasets' => [
                [
                    'label' => 'Sinyal Kompetitor',
                    'data' => $chartData['competitor_scores'] ?? [],
                    'backgroundColor' => '#f59e0b',
                    'borderColor' => '#d97706',
                    'borderWidth' => 1,
                ],
                [
                    'label' => 'Peluang Usaha Saya',
                    'data' => $chartData['opportunity_scores'] ?? [],
                    'backgroundColor' => '#14b8a6',
                    'borderColor' => '#0f766e',
                    'borderWidth' => 1,
                ],
            ],
            'labels' => $chartData['labels'] ?? [],
        ];
    }

    protected function getOptions(): RawJs
    {
        return RawJs::make(<<<'JS'
            {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                    },
                    tooltip: {
                        callbacks: {
                            label: (context) => `${context.dataset.label}: ${context.parsed.y}/100`,
                        },
                    },
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        ticks: {
                            stepSize: 20,
                        },
                    },
                },
            }
        JS);
    }
}
