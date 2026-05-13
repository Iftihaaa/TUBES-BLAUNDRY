<?php

namespace App\Filament\Resources\PemesananResource\Pages;

use App\Filament\Resources\PemesananResource;
use App\Models\Pemesanan;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditPemesanan extends EditRecord
{
    protected static string $resource = PemesananResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('save_status')
                ->label('Save Changes')
                ->icon('heroicon-o-check-circle')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Konfirmasi Simpan Perubahan')
                ->modalDescription('Apakah Anda yakin ingin menyimpan perubahan status pesanan?')
                ->modalSubmitActionLabel('Ya, Simpan')
                ->action(function () {
                    $record    = $this->getRecord();
                    $newStatus = $this->data['status'] ?? $record->status;
                    $oldStatus = $record->status;

                    $record->update(['status' => $newStatus]);

                    if ($oldStatus !== $newStatus) {
                        Notification::make()
                            ->title('Status berhasil diubah!')
                            ->body("Pesanan {$record->kode_pemesanan}: '{$oldStatus}' → '{$newStatus}'")
                            ->success()
                            ->send();
                    } else {
                        Notification::make()
                            ->title('Tidak ada perubahan status.')
                            ->info()
                            ->send();
                    }

                    return redirect($this->getResource()::getUrl('index'));
                }),

            Actions\Action::make('cancel')
                ->label('Close')
                ->color('gray')
                ->icon('heroicon-o-x-circle')
                ->url($this->getResource()::getUrl('index')),
        ];
    }

    // Kosongkan footer — semua action sudah di header
    public function getFormActions(): array
    {
        return [];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['status'] = $this->getRecord()->status ?? 'on process';
        return $data;
    }
}