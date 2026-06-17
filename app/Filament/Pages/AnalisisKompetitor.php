<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\CompetitorAnalysisChartWidget;
use App\Filament\Widgets\CompetitorAnalysisDetailWidget;
use App\Filament\Widgets\CompetitorAnalysisSummaryWidget;
use App\Services\GeminiCompetitorAnalysisService;
use App\Support\CompetitorAnalysis;
use Filament\Actions\Action;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Enums\MaxWidth;
use Throwable;

class AnalisisKompetitor extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-sparkles';

    protected static ?string $navigationGroup = 'Artificial Intelligent';

    protected static ?int $navigationSort = 5;

    protected static ?string $navigationLabel = 'Analisis Kompetitor';

    protected static ?string $title = 'Analisis Kompetitor Laundry';

    protected static string $view = 'filament.pages.analisis-kompetitor';

    /**
     * @var array<string, mixed>
     */
    public array $competitorAnalysis = [];

    public function mount(): void
    {
        $this->competitorAnalysis = session(CompetitorAnalysis::SESSION_KEY, []);
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
     * @return array<class-string>
     */
    protected function getHeaderWidgets(): array
    {
        return [
            CompetitorAnalysisSummaryWidget::class,
            CompetitorAnalysisChartWidget::class,
            CompetitorAnalysisDetailWidget::class,
        ];
    }

    /**
     * @return int|array<string, int|string>
     */
    public function getHeaderWidgetsColumns(): int|array
    {
        return [
            'default' => 1,
            'xl'      => 2,
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
                        'md'      => 2,
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
                                'ya'             => 'Ya',
                                'tidak'          => 'Tidak',
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
        session()->put(CompetitorAnalysis::SESSION_KEY, $analysis);

        $this->dispatch('competitor-analysis-updated');

        Notification::make()
            ->title('Analisis kompetitor berhasil')
            ->body('Hasil AI sudah diperbarui di halaman ini.')
            ->success()
            ->send();
    }
}
