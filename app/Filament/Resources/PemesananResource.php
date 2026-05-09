<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PemesananResource\Pages;
use App\Models\Pemesanan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

// Komponen Form
use Filament\Forms\Components\Wizard;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Forms\Components\Hidden;

// Komponen Table
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;

// Model
use App\Models\Member;
use App\Models\Layanan;
use App\Models\Pembayaran;
use App\Models\DetailPemesanan;

// Notifikasi
use Filament\Notifications\Notification;

// Lainnya
use Illuminate\Support\Facades\DB;

class PemesananResource extends Resource
{
    protected static ?string $model = Pemesanan::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationLabel = 'Pemesanan';
    protected static ?string $navigationGroup = 'Transaksi';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Wizard::make([

                    // =====================
                    // STEP 1: Data Pesanan
                    // =====================
                    Wizard\Step::make('Pesanan')
                        ->icon('heroicon-o-shopping-cart')
                        ->schema([
                            Forms\Components\Section::make('Informasi Pemesanan')
                                ->icon('heroicon-m-document-text')
                                ->schema([
                                    TextInput::make('kode_pemesanan')
                                        ->default(fn () => Pemesanan::getKodePemesanan())
                                        ->label('Kode Pemesanan')
                                        ->required()
                                        ->readonly(),

                                    DatePicker::make('tgl_pesan')
                                        ->label('Tanggal Pesan')
                                        ->default(today())
                                        ->required(),

                                    Select::make('id_pelanggan')
                                        ->label('Pelanggan')
                                        ->options(fn () => Member::pluck('nama_pelanggan', 'id_pelanggan')->toArray())
                                        ->required()
                                        ->searchable()
                                        ->placeholder('Pilih atau tambah pelanggan baru')
                                        ->createOptionForm([
                                            TextInput::make('nama_pelanggan')
                                                ->label('Nama Pelanggan')
                                                ->required()
                                                ->maxLength(255),

                                            TextInput::make('no_telp')
                                                ->label('No. Telepon')
                                                ->required()
                                                ->tel()
                                                ->maxLength(20),

                                            Forms\Components\Textarea::make('alamat')
                                                ->label('Alamat')
                                                ->required()
                                                ->rows(3),
                                        ])
                                        ->createOptionUsing(function (array $data): int {
                                            $member = Member::create([
                                                'nama_pelanggan' => $data['nama_pelanggan'],
                                                'no_telp'        => $data['no_telp'],
                                                'alamat'         => $data['alamat'],
                                            ]);
                                            return $member->id_pelanggan;
                                        })
                                        ->createOptionModalHeading('Tambah Pelanggan Baru'),
                                ])
                                ->columns(3),

                            Forms\Components\Section::make('Daftar Layanan')
                                ->icon('heroicon-m-list-bullet')
                                ->description('Tambahkan satu atau lebih layanan untuk pesanan ini.')
                                ->schema([
                                    Repeater::make('items_layanan')
                                        ->label('')
                                        ->schema([
                                            Select::make('id_layanan')
                                                ->label('Layanan')
                                                ->options(Layanan::pluck('nama_layanan', 'id_layanan')->toArray())
                                                ->required()
                                                ->searchable()
                                                ->live()
                                                ->afterStateUpdated(function ($state, Set $set) {
                                                    $layanan = Layanan::with('kategoriLayanan')->find($state);
                                                    if (!$layanan) return;
                                                    $isSatuan = $layanan->kategoriLayanan?->nama_kategori === 'Satuan';
                                                    $set('harga_satuan', $layanan->harga_per_kg);
                                                    $set('is_satuan', $isSatuan ? '1' : '0');
                                                    $set('jumlah', 1);
                                                    $set('subtotal', $layanan->harga_per_kg);
                                                })
                                                ->columnSpan(3),

                                            Hidden::make('is_satuan')->default('0'),
                                            Hidden::make('harga_satuan')->default(0),

                                            TextInput::make('jumlah')
                                                ->label(fn (Get $get): string => $get('is_satuan') === '1' ? 'Jumlah (pcs)' : 'Berat (kg)')
                                                ->numeric()
                                                ->required()
                                                ->default(1)
                                                ->minValue(0.1)
                                                ->live(onBlur: true)
                                                ->afterStateUpdated(function ($state, Get $get, Set $set) {
                                                    $harga    = (float) $get('harga_satuan');
                                                    $jumlah   = (float) ($state ?? 1);
                                                    $subtotal = round($harga * $jumlah, 2);
                                                    $set('subtotal', $subtotal);

                                                    // Hitung total semua item dari state,
                                                    // tapi subtotal item ini pakai nilai baru (belum ke-commit)
                                                    $items      = $get('../../items_layanan') ?? [];
                                                    $currentKey = $get('../../items_layanan');
                                                    $total      = collect($items)->sum(fn ($item) => (float) ($item['subtotal'] ?? 0));

                                                    // Koreksi: subtotal di state item ini masih lama,
                                                    // jadi kita kurangi lama + tambah baru
                                                    $subtotalLama = (float) ($get('subtotal') ?? 0);
                                                    $total        = $total - $subtotalLama + $subtotal;

                                                    $set('../../total_harga', $total);
                                                    // Recalc grand total juga
                                                    $ongkir = in_array($get('../../pengantaran'), ['antar-jemput', 'antar saja'])
                                                        ? (float) ($get('../../ongkir') ?? 0)
                                                        : 0;
                                                    $set('../../grand_total', $total + $ongkir);
                                                })
                                                ->columnSpan(2),

                                            TextInput::make('subtotal')
                                                ->label('Subtotal')
                                                ->numeric()
                                                ->readonly()
                                                ->default(0)
                                                ->prefix('Rp')
                                                ->dehydrated()
                                                ->columnSpan(2),
                                        ])
                                        ->columns(7)
                                        ->addActionLabel('+ Tambah Layanan')
                                        ->minItems(fn ($livewire) => $livewire instanceof \App\Filament\Resources\PemesananResource\Pages\CreatePemesanan ? 1 : 0)
                                        ->live()
                                        ->afterStateUpdated(function (Get $get, Set $set) {
                                            // Ini tetap ada untuk handle tambah/hapus item
                                            self::recalcTotal($get, $set);
                                        })
                                        ->deleteAction(
                                            fn ($action) => $action->after(function (Get $get, Set $set) {
                                                self::recalcTotal($get, $set);
                                            })
                                        ),
                                ]),

                            Forms\Components\Section::make()
                                ->schema([
                                    TextInput::make('total_harga')
                                        ->label('Total Harga Layanan')
                                        ->numeric()
                                        ->readonly()
                                        ->default(0)
                                        ->prefix('Rp')
                                        ->dehydrated()
                                        ->extraInputAttributes(['class' => 'font-bold text-lg']),
                                ])
                                ->columns(1),
                        ]),

                    // =====================
                    // STEP 2: Pengantaran
                    // =====================
                    Wizard\Step::make('Pengantaran')
                        ->icon('heroicon-o-truck')
                        ->schema([
                            Forms\Components\Section::make('Detail Pengantaran')
                                ->icon('heroicon-m-truck')
                                ->schema([
                                    Select::make('pengantaran')
                                        ->label('Jenis Pengantaran')
                                        ->options([
                                            'antar-jemput'  => 'Antar-Jemput',
                                            'antar saja'    => 'Antar Saja',
                                            'ambil sendiri' => 'Ambil Sendiri',
                                        ])
                                        ->default('ambil sendiri')
                                        ->required()
                                        ->live()
                                        ->afterStateUpdated(function ($state, Get $get, Set $set) {
                                            if (!in_array($state, ['antar-jemput', 'antar saja'])) {
                                                $set('ongkir', 0);
                                            }
                                            self::recalcGrandTotal($get, $set);
                                        }),

                                    TextInput::make('ongkir')
                                        ->label('Ongkos Kirim')
                                        ->numeric()
                                        ->default(0)
                                        ->prefix('Rp')
                                        ->visible(fn (Get $get): bool => in_array($get('pengantaran'), ['antar-jemput', 'antar saja']))
                                        ->live()
                                        ->afterStateUpdated(function ($state, Get $get, Set $set) {
                                            self::recalcGrandTotal($get, $set);
                                        })
                                        ->dehydrated(),

                                    TextInput::make('grand_total')
                                        ->label('Grand Total (Layanan + Ongkir)')
                                        ->numeric()
                                        ->readonly()
                                        ->default(0)
                                        ->prefix('Rp')
                                        ->dehydrated(false)
                                        ->afterStateHydrated(function ($state, Set $set, $record) {
                                            // Saat Edit, isi dari total_harga record
                                            if ($record && !$state) {
                                                $set('grand_total', $record->total_harga);
                                            }
                                        })
                                        ->extraInputAttributes(['class' => 'font-bold']),
                                ])
                                ->columns(3),
                        ]),

                    // =====================
                    // STEP 3: Proses
                    // =====================
                    Wizard\Step::make('Proses')
                        ->icon('heroicon-o-cog-6-tooth')
                        ->schema([
                            Forms\Components\Section::make('Konfirmasi & Proses Pemesanan')
                                ->icon('heroicon-m-clipboard-document-check')
                                ->schema([
                                    Select::make('status')
                                        ->label('Status Awal')
                                        ->options([
                                            'on process' => 'On Process',
                                            'done'       => 'Done',
                                        ])
                                        ->default('on process')
                                        ->required(),

                                    Hidden::make('saved_id_pemesanan'),

                                    Forms\Components\Actions::make([
                                        Forms\Components\Actions\Action::make('proses_pemesanan')
                                            ->label('Proses Pemesanan')
                                            ->color('primary')
                                            ->icon('heroicon-o-bolt')
                                            ->requiresConfirmation()
                                            ->modalHeading('Konfirmasi Proses Pemesanan')
                                            ->modalDescription('Pastikan semua data sudah benar. Setelah diproses, pesanan akan tersimpan ke database.')
                                            ->modalSubmitActionLabel('Ya, Proses Sekarang')
                                            ->action(function (Get $get, Set $set) {
                                                $items      = array_values($get('items_layanan') ?? []);
                                                $totalHarga = collect($items)->sum(fn ($i) => (float) ($i['subtotal'] ?? 0));
                                                $ongkir     = in_array($get('pengantaran'), ['antar-jemput', 'antar saja'])
                                                    ? (float) ($get('ongkir') ?? 0)
                                                    : 0;
                                                $grandTotal = $totalHarga + $ongkir;

                                                // Ambil item pertama untuk kolom legacy di tabel pemesanan
                                                $firstItem = $items[0] ?? [];

                                                $pemesanan = Pemesanan::create([
                                                    'kode_pemesanan' => $get('kode_pemesanan'),
                                                    'id_pelanggan'   => $get('id_pelanggan'),
                                                    'id_layanan'     => $firstItem['id_layanan'] ?? null,
                                                    'tgl_pesan'      => $get('tgl_pesan'),
                                                    'berat_kg'       => $firstItem['jumlah'] ?? 1,
                                                    'total_harga'    => $grandTotal,
                                                    'pengantaran'    => $get('pengantaran'),
                                                    'ongkir'         => $ongkir,
                                                    'status'         => $get('status'),
                                                ]);

                                                // Simpan semua detail layanan ke detail_pemesanan
                                                foreach ($items as $item) {
                                                    $layanan = Layanan::find($item['id_layanan'] ?? null);
                                                    if (!$layanan) continue;

                                                    DetailPemesanan::create([
                                                        'id_pemesanan' => $pemesanan->id_pemesanan,
                                                        'id_layanan'   => $layanan->id_layanan,
                                                        'nama_layanan' => $layanan->nama_layanan,
                                                        'harga_per_kg' => $layanan->harga_per_kg,
                                                        'berat_kg'     => (float) ($item['jumlah'] ?? 1),
                                                        'subtotal'     => (float) ($item['subtotal'] ?? 0),
                                                    ]);
                                                }

                                                $set('saved_id_pemesanan', $pemesanan->id_pemesanan);

                                                Notification::make()
                                                    ->title('Pemesanan Berhasil Diproses!')
                                                    ->body('Pesanan ' . $get('kode_pemesanan') . ' telah disimpan. Silakan lanjut ke tahap Pembayaran.')
                                                    ->success()
                                                    ->icon('heroicon-o-check-circle')
                                                    ->duration(6000)
                                                    ->send();
                                            }),
                                    ]),
                                ]),
                        ]),

                    // =====================
                    // STEP 4: Pembayaran
                    // =====================
                    Wizard\Step::make('Pembayaran')
                        ->icon('heroicon-o-banknotes')
                        ->schema([

                            // Blade: ringkasan pesanan + tabel detail layanan
                            Placeholder::make('ringkasan_pesanan')
                                ->label('')
                                ->content(fn (Get $get) => view('filament.components.pemesanan-ringkasan', [
                                    'pemesanan' => Pemesanan::with([
                                        'member',
                                        'detailPemesanan.layanan.kategoriLayanan',
                                    ])->find($get('saved_id_pemesanan')),
                                ])),

                            // Garis pemisah
                            Placeholder::make('divider')
                                ->label('')
                                ->content(fn () => new \Illuminate\Support\HtmlString(
                                    '<hr class="border-gray-200 my-1">'
                                )),

                            // Metode pembayaran
                            Select::make('metode_pembayaran')
                                ->label('Metode Pembayaran')
                                ->options([
                                    'tunai' => '💵 Tunai',
                                    'qris'  => '📱 QRIS (Coming Soon)',
                                ])
                                ->default('tunai')
                                ->required()
                                ->live()
                                ->disableOptionWhen(fn (string $value): bool => $value === 'qris'),

                            // Input nominal — hanya muncul kalau tunai
                            TextInput::make('nominal_bayar')
                                ->label('Nominal Dibayar')
                                ->numeric()
                                ->default(0)
                                ->prefix('Rp')
                                ->live()
                                ->visible(fn (Get $get): bool => $get('metode_pembayaran') === 'tunai')
                                ->afterStateUpdated(function ($state, Get $get, Set $set) {
                                    $pemesanan  = Pemesanan::find($get('saved_id_pemesanan'));
                                    $grandTotal = $pemesanan ? (float) $pemesanan->total_harga : 0;
                                    $nominal    = (float) ($state ?? 0);
                                    $kembalian  = $nominal >= $grandTotal ? $nominal - $grandTotal : 0;
                                    $set('kembalian', $kembalian);
                                })
                                ->required(fn (Get $get): bool => $get('metode_pembayaran') === 'tunai'),

                            // Kembalian — readonly, auto-hitung
                            TextInput::make('kembalian')
                                ->label('Kembalian')
                                ->numeric()
                                ->default(0)
                                ->prefix('Rp')
                                ->readonly()
                                ->visible(fn (Get $get): bool => $get('metode_pembayaran') === 'tunai')
                                ->dehydrated(false),

                            // Tombol Selesaikan Pembayaran
                            Forms\Components\Actions::make([
                                Forms\Components\Actions\Action::make('selesaikan_pembayaran')
                                    ->label('Selesaikan Pembayaran')
                                    ->color('success')
                                    ->icon('heroicon-o-check-circle')
                                    ->requiresConfirmation()
                                    ->modalHeading('Konfirmasi Pembayaran')
                                    ->modalDescription('Pastikan nominal sudah benar. Pembayaran tidak bisa dibatalkan.')
                                    ->modalSubmitActionLabel('Ya, Selesaikan')
                                    ->visible(fn (Get $get): bool => filled($get('saved_id_pemesanan')))
                                    ->action(function (Get $get) {
                                        $pemesanan = Pemesanan::find($get('saved_id_pemesanan'));

                                        if (!$pemesanan) {
                                            Notification::make()
                                                ->title('Pesanan tidak ditemukan!')
                                                ->danger()
                                                ->send();
                                            return;
                                        }

                                        // Cek sudah bayar
                                        if ($pemesanan->pembayaran()->exists()) {
                                            Notification::make()
                                                ->title('Pembayaran sudah ada!')
                                                ->body('Pesanan ini sudah pernah dibayar.')
                                                ->warning()
                                                ->send();
                                            return;
                                        }

                                        $metode     = $get('metode_pembayaran') ?? 'tunai';
                                        $grandTotal = (float) $pemesanan->total_harga;
                                        $nominal    = (float) ($get('nominal_bayar') ?? 0);

                                        // Validasi nominal tunai
                                        if ($metode === 'tunai' && $nominal < $grandTotal) {
                                            Notification::make()
                                                ->title('Nominal kurang!')
                                                ->body('Nominal yang dibayar kurang dari total tagihan.')
                                                ->danger()
                                                ->send();
                                            return;
                                        }

                                        // Simpan ke tabel pembayaran
                                        Pembayaran::create([
                                            'id_pemesanan'     => $pemesanan->id_pemesanan,
                                            'tgl_bayar'        => today(),
                                            'jenis_pembayaran' => $metode,
                                            'gross_amount'     => $grandTotal,
                                            'order_id'         => $pemesanan->kode_pemesanan,
                                            'payment_type'     => $metode === 'tunai' ? 'cash' : 'qris',
                                            'status_code'      => '200',
                                            'status_message'   => 'Pembayaran ' . $metode . ' berhasil.',
                                            'transaction_time' => now(),
                                        ]);

                                        // Update status pemesanan → done
                                        $pemesanan->update(['status' => 'done']);

                                        Notification::make()
                                            ->title('Pembayaran Berhasil! 🎉')
                                            ->body('Pesanan ' . $pemesanan->kode_pemesanan . ' telah lunas.')
                                            ->success()
                                            ->icon('heroicon-o-check-circle')
                                            ->duration(6000)
                                            ->send();
                                    }),
                            ]),

                        ]),

                ])->columnSpan(3)->skippable(fn ($livewire) => !($livewire instanceof \App\Filament\Resources\PemesananResource\Pages\CreatePemesanan)),

                // =====================
                // Tombol Close
                // =====================
                Forms\Components\Actions::make([
                    Forms\Components\Actions\Action::make('close')
                        ->label('Tutup / Batalkan')
                        ->color('gray')
                        ->icon('heroicon-o-x-circle')
                        ->url(fn () => static::getUrl('index'))
                        ->requiresConfirmation()
                        ->modalHeading('Batalkan Pemesanan?')
                        ->modalDescription('Data yang belum diproses akan hilang. Yakin ingin keluar?')
                        ->modalSubmitActionLabel('Ya, Keluar'),
                ])->columnSpan(3)->alignEnd(),

            ])->columns(3);
    }

    // =============================================
    // Helper: hitung ulang total_harga dari repeater
    // $overrideSubtotal: nilai subtotal terbaru yang belum ke-commit ke state
    // =============================================
    protected static function recalcTotal(Get $get, Set $set, ?float $overrideSubtotal = null): void
    {
        $items = array_values($get('items_layanan') ?? []);

        // Kalau ada override, ambil semua subtotal dari state kecuali item terakhir
        // yang baru diupdate — ganti dengan override
        if ($overrideSubtotal !== null) {
            $allItems   = array_values($items);
            $totalItems = count($allItems);
            $total      = 0;

            foreach ($allItems as $i => $item) {
                if ($i === $totalItems - 1) {
                    // Item terakhir yang baru diupdate, pakai override
                    $total += $overrideSubtotal;
                } else {
                    $total += (float) ($item['subtotal'] ?? 0);
                }
            }
        } else {
            // Kalkulasi normal (tambah/hapus item, ganti layanan)
            $total = collect($items)->sum(fn ($item) => (float) ($item['subtotal'] ?? 0));
        }

        $set('total_harga', $total);
        self::recalcGrandTotal($get, $set, $total);
    }

    // =============================================
    // Helper: hitung grand_total = total_harga + ongkir
    // =============================================
    protected static function recalcGrandTotal(Get $get, Set $set, ?float $totalHarga = null): void
    {
        $total  = $totalHarga ?? (float) ($get('total_harga') ?? 0);
        $ongkir = in_array($get('pengantaran'), ['antar-jemput', 'antar saja'])
            ? (float) ($get('ongkir') ?? 0)
            : 0;
        $set('grand_total', $total + $ongkir);
    }

    // =============================================
    // Table
    // =============================================
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('kode_pemesanan')
                    ->label('Kode Pemesanan')
                    ->searchable()
                    ->weight('bold'),

                TextColumn::make('member.nama_pelanggan')
                    ->label('Nama Pelanggan')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('layanan.nama_layanan')
                    ->label('Layanan Utama')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('berat_kg')
                    ->label('Berat/Jml')
                    ->sortable(),

                TextColumn::make('total_harga')
                    ->label('Total Harga')
                    ->formatStateUsing(fn ($state): string => 'Rp ' . number_format($state, 0, ',', '.'))
                    ->sortable()
                    ->alignment('end')
                    ->weight('bold'),

                TextColumn::make('pengantaran')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'antar-jemput'  => 'info',
                        'antar saja'    => 'warning',
                        'ambil sendiri' => 'gray',
                        default         => 'gray',
                    }),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'done'       => 'success',
                        'on process' => 'warning',
                        default      => 'gray',
                    }),

                TextColumn::make('tgl_pesan')
                    ->label('Tanggal Pesan')
                    ->date()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Filter Status')
                    ->options([
                        'on process' => 'On Process',
                        'done'       => 'Done',
                    ]),

                SelectFilter::make('pengantaran')
                    ->label('Filter Pengantaran')
                    ->options([
                        'antar-jemput'  => 'Antar-Jemput',
                        'antar saja'    => 'Antar Saja',
                        'ambil sendiri' => 'Ambil Sendiri',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),

                // Edit = hanya ubah status via modal kecil
                Tables\Actions\Action::make('edit')
                    ->label('Edit Status')
                    ->icon('heroicon-o-pencil-square')
                    ->color('warning')
                    ->form([
                        Select::make('status')
                            ->label('Status')
                            ->options([
                                'on process' => 'On Process',
                                'done'       => 'Done',
                            ])
                            ->required()
                            ->default(fn (Pemesanan $record): string => $record->status),
                    ])
                    ->fillForm(fn (Pemesanan $record): array => [
                        'status' => $record->status,
                    ])
                    ->action(function (Pemesanan $record, array $data): void {
                        $record->update(['status' => $data['status']]);

                        Notification::make()
                            ->title('Status diperbarui!')
                            ->body('Pesanan ' . $record->kode_pemesanan . ' → ' . strtoupper($data['status']))
                            ->success()
                            ->send();
                    })
                    ->modalHeading('Ubah Status Pemesanan')
                    ->modalSubmitActionLabel('Simpan'),

                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListPemesanans::route('/'),
            'create' => Pages\CreatePemesanan::route('/create'),
            'edit'   => Pages\EditPemesanan::route('/{record}/edit'),
        ];
    }
}