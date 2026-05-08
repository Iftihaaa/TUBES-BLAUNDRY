<?php

namespace App\Filament\Resources\MemberResource\Pages;

use App\Filament\Resources\MemberResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use App\Services\FonnteService; // ← tambahan

class CreateMember extends CreateRecord
{
    protected static string $resource = MemberResource::class;

    // ← tambahan: otomatis dipanggil setelah member berhasil disimpan
    protected function afterCreate(): void
    {
        $member = $this->record;

        $pesan = "Halo, {$member->nama_pelanggan}! 🎉\n"
               . "Selamat, kamu resmi jadi *Member Laundry* kami!\n\n"
               . "🪪 ID Member : #{$member->id}\n"
               . "📍 Alamat    : {$member->alamat}\n\n"
               . "Nikmati berbagai layanan laundry terbaik kami 🙏";

        $fonnte = app(FonnteService::class);
        $fonnte->sendMessage($member->no_telp, $pesan);
    }
}