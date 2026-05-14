<?php

namespace App\Filament\Resources\PenggajianResource\Pages;

use App\Filament\Resources\PenggajianResource;
use App\Mail\SlipGajiMail;

use Barryvdh\DomPDF\Facade\Pdf;

use Filament\Resources\Pages\CreateRecord;

use Illuminate\Support\Facades\Mail;

class CreatePenggajian extends CreateRecord
{
    protected static string $resource = PenggajianResource::class;

    /*
    |--------------------------------------------------------------------------
    | OTOMATIS SET STATUS SUDAH DIBAYAR SEBELUM SIMPAN
    |--------------------------------------------------------------------------
    */

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['status_pembayaran'] = 'sudah dibayar';

        $data['bonus'] = isset($data['dapat_bonus']) && $data['dapat_bonus'] == 'ya'
            ? 1
            : 0;

        return $data;
    }

    /*
    |--------------------------------------------------------------------------
    | KIRIM EMAIL + PDF ATTACHMENT KE MAILTRAP SETELAH DATA TERSIMPAN
    |--------------------------------------------------------------------------
    */

    protected function afterCreate(): void
    {
        $penggajian = $this->record;

        // LOAD RELASI PEGAWAI
        $penggajian->load('pegawai');

        // GENERATE PDF
        $pdfContent = Pdf::loadView(
            'pdf.slip_gaji',
            ['penggajian' => $penggajian]
        )->output();

        // KIRIM EMAIL DENGAN PDF ATTACHMENT
        Mail::to(
            config('mail.from.address')
        )->send(
            new SlipGajiMail($penggajian, $pdfContent)
        );
    }

    /*
    |--------------------------------------------------------------------------
    | REDIRECT KE LIST PENGGAJIAN SETELAH CREATE
    |--------------------------------------------------------------------------
    */

    protected function getRedirectUrl(): string
    {
        return PenggajianResource::getUrl('index');
    }
}