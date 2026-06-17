<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AnalisisCashflowResource\Pages;
use App\Models\AnalisisCashflow;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AnalisisCashflowResource extends Resource
{
    protected static ?string $model = AnalisisCashflow::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?string $navigationGroup = 'Analisis AI'; 
    protected static ?string $pluralModelLabel = 'Analisis Cashflow';
    protected static ?string $navigationLabel = 'Cashflow';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\DatePicker::make('periode')
                    ->disabled(),
                Forms\Components\Textarea::make('analisis_ai')
                    ->disabled()
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('kesimpulan')
                    ->disabled()
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('saran_operasional')
                    ->disabled()
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('periode')
                    ->label('Periode')
                    ->date('F Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('kesimpulan')
                    ->label('Kesimpulan AI')
                    ->limit(50)
                    ->searchable(),
                Tables\Columns\TextColumn::make('analisis_ai')
                    ->label('Analisis Singkat')
                    ->limit(50),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Tanggal Analisis')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->modalHeading('Detail Analisis Cashflow'),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    // ── DAFTARKAN WIDGET ──────────────────────────────────────────
    public static function getWidgets(): array
    {
        return [
            \App\Filament\Resources\AnalisisCashflowResource\Widgets\CashflowBarChart::class,
            \App\Filament\Resources\AnalisisCashflowResource\Widgets\CashflowDoughnutChart::class,
            \App\Filament\Resources\AnalisisCashflowResource\Widgets\LatestCashflowInsight::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageAnalisisCashflows::route('/'),
        ];
    }
}