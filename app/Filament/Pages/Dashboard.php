<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\CompetitorAnalysisChartWidget;
use App\Filament\Widgets\CompetitorAnalysisDetailWidget;
use App\Filament\Widgets\CompetitorAnalysisSummaryWidget;
use App\Services\GeminiCompetitorAnalysisService;
use Filament\Actions\Action;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Support\Enums\MaxWidth;
use Filament\Widgets;
use Throwable;

class Dashboard extends BaseDashboard
{
    public const COMPETITOR_ANALYSIS_SESSION_KEY = 'dashboard.competitor_analysis';

    protected static bool $isDiscovered = false;

    protected static ?string $title = 'Dashboard';

    /**
     * @var array<string, mixed>
     */
    public array $competitorAnalysis = [];

    public function mount(): void
    {
        $this->competitorAnalysis = session(self::COMPETITOR_ANALYSIS_SESSION_KEY, []);
    }

    /**
     * @return array<class-string<Widgets\Widget>|\Filament\Widgets\WidgetConfiguration>
     */
    public function getWidgets(): array
    {
        return [
            CompetitorAnalysisSummaryWidget::class,
            CompetitorAnalysisChartWidget::class,
            CompetitorAnalysisDetailWidget::class,
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
            'xl' => 2,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getWidgetData(): array
    {
        return [
            'competitorAnalysis' => $this->competitorAnalysis,
        ];
    }

    /**
     * @return array<Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('analisisKompetitor')
                ->label('Analisis Kompetitor')
                ->icon('heroicon-o-sparkles')
                ->modalHeading('Analisis Kompetitor Laundry')
                ->modalDescription('Isi data kompetitor yang benar-benar diketahui. Sistem tidak akan memakai data contoh atau asumsi eksternal.')
                ->modalSubmitActionLabel('Kirim ke Gemini')
                ->modalWidth(MaxWidth::FiveExtraLarge)
                ->closeModalByClickingAway(false)
                ->form($this->competitorAnalysisForm())
                ->action(fn (array $data): mixed => $this->analyzeCompetitor($data)),
        ];
    }

    /**
     * @return array<int, mixed>
     */
    private function competitorAnalysisForm(): array
    {
        return [
            Section::make('Data Kompetitor')
                ->description('Field utama wajib diisi agar analisis tidak berdiri di atas data kosong.')
                ->schema([
                    Grid::make([
                        'default' => 1,
                        'md' => 2,
                    ])->schema([
                        TextInput::make('nama_kompetitor')
                            ->label('Nama kompetitor')
                            ->required()
                            ->maxLength(120),
                        TextInput::make('alamat_lokasi')
                            ->label('Alamat / lokasi')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('harga_cuci')
                            ->label('Harga cuci')
                            ->prefix('Rp')
                            ->numeric()
                            ->required()
                            ->minValue(0),
                        TextInput::make('harga_setrika')
                            ->label('Harga setrika')
                            ->prefix('Rp')
                            ->numeric()
                            ->required()
                            ->minValue(0),
                        TextInput::make('harga_express')
                            ->label('Harga express')
                            ->prefix('Rp')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->helperText('Isi 0 hanya jika kompetitor memang tidak menawarkan express atau harga express tidak berlaku.'),
                        TextInput::make('jam_operasional')
                            ->label('Jam operasional')
                            ->required()
                            ->maxLength(120),
                        Select::make('layanan_antar_jemput')
                            ->label('Layanan antar-jemput')
                            ->options([
                                'ya' => 'Ya',
                                'tidak' => 'Tidak',
                                'tidak_diketahui' => 'Tidak diketahui',
                            ])
                            ->native(false)
                            ->required(),
                    ]),
                    Textarea::make('promo')
                        ->label('Promo')
                        ->rows(3)
                        ->maxLength(1000),
                    Textarea::make('rating_ulasan')
                        ->label('Rating / ulasan')
                        ->rows(3)
                        ->maxLength(1000),
                    Textarea::make('catatan_tambahan')
                        ->label('Catatan tambahan')
                        ->rows(4)
                        ->maxLength(1500),
                ]),
        ];
    }

    private function analyzeCompetitor(array $data): void
    {
        try {
            $analysis = app(GeminiCompetitorAnalysisService::class)->analyze($data);
        } catch (Throwable $exception) {
            report($exception);

            Notification::make()
                ->title('Analisis kompetitor gagal')
                ->body($exception->getMessage())
                ->danger()
                ->send();

            return;
        }

        $this->competitorAnalysis = $analysis;
        session()->put(self::COMPETITOR_ANALYSIS_SESSION_KEY, $analysis);

        $this->dispatch('competitor-analysis-updated');

        Notification::make()
            ->title('Analisis kompetitor berhasil')
            ->body('Hasil AI sudah diperbarui di dashboard.')
            ->success()
            ->send();
    }
}
