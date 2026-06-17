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

    /**
     * Matikan polling bawaan Filament (default 5 detik).
     * Refresh dilakukan via dispatch('$refresh') setelah analisis selesai.
     * Polling aktif menyebabkan parent::updateChartData() men-dispatch event
     * 'updateChartData' ke semua widget dan memicu MethodNotFoundException
     * pada SummaryWidget/DetailWidget yang tidak memiliki method tersebut.
     */
    protected static ?string $pollingInterval = null;

    public static function canView(): bool
    {
        return filled(session(CompetitorAnalysis::SESSION_KEY));
    }

    public function rendering(): void
    {
        // Reset cache agar getData() selalu baca ulang dari session.
        $this->cachedData = null;

        // Panggil parent agar updateChartData() berjalan dan
        // men-dispatch JS event ke chart.js Alpine component.
        parent::rendering();
    }

    /**
     * Override updateChartData() untuk men-scope dispatch hanya ke
     * komponen ini sendiri (->self()), mencegah event menyebar ke
     * SummaryWidget/DetailWidget yang tidak memiliki method ini.
     */
    public function updateChartData(): void
    {
        $newDataChecksum = $this->generateDataChecksum();

        if ($newDataChecksum !== $this->dataChecksum) {
            $this->dataChecksum = $newDataChecksum;

            $this->dispatch('updateChartData', data: $this->getCachedData())->self();
        }
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
