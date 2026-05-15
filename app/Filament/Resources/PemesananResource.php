<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PemesananResource\Pages;
use App\Models\Pemesanan;
use App\Models\DetailPemesanan;
use App\Models\Pembayaran;
use App\Models\Member;
use App\Models\Layanan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Section;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Actions\Action;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\HtmlString;

class PemesananResource extends Resource
{
    protected static ?string $model = Pemesanan::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    protected static ?string $navigationLabel = 'Pemesanan';

    protected static ?string $navigationGroup = 'Transaksi';

    // =========================================================================
    // FORM
    // =========================================================================
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Wizard::make([

                    // =========================================================
                    // STEP 1 — PESANAN
                    // =========================================================
                    Wizard\Step::make('Pesanan')
                        ->icon('heroicon-o-clipboard-document-list')
                        ->schema([

                            Section::make('Informasi Pesanan')
                                ->icon('heroicon-m-document-text')
                                ->collapsible()
                                ->schema([
                                    TextInput::make('kode_pemesanan')
                                        ->label('Kode Pesanan')
                                        ->default(fn () => Pemesanan::getKodePemesanan())
                                        ->required()
                                        ->readonly(),

                                    DatePicker::make('tgl_pesan')
                                        ->label('Tanggal Pesan')
                                        ->default(today())
                                        ->required(),

                                    Select::make('id_pelanggan')
                                        ->label('Pelanggan')
                                        ->options(
                                            Member::query()
                                                ->pluck('nama_pelanggan', 'id_pelanggan')
                                                ->toArray()
                                        )
                                        ->searchable()
                                        ->preload()
                                        ->required()
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
                                            return Member::create($data)->id_pelanggan;
                                        }),

                                    // Hidden — diisi via recalcTotal
                                    Hidden::make('total_harga')->default(0),
                                    Hidden::make('status')->default('on process'),
                                ])
                                ->columns(3),

                            // -------------------------------------------------
                            // Daftar Layanan — Repeater
                            // -------------------------------------------------
                            Section::make('Daftar Layanan')
                                ->icon('heroicon-m-list-bullet')
                                ->description('Tambahkan satu atau lebih layanan untuk pesanan ini.')
                                ->schema([
                                    Repeater::make('detailPemesanan')
                                        ->relationship('detailPemesanan')
                                        ->label('')
                                        ->schema([
                                            Select::make('id_layanan')
                                                ->label('Layanan')
                                                ->options(
                                                    Layanan::with('kategoriLayanan')
                                                        ->get()
                                                        ->mapWithKeys(fn ($l) => [
                                                            $l->id_layanan => $l->nama_layanan
                                                                . ' ('
                                                                . ($l->kategoriLayanan?->nama_kategori ?? '-')
                                                                . ')',
                                                        ])
                                                        ->toArray()
                                                )
                                                ->required()
                                                ->reactive()
                                                ->searchable()
                                                ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                                                ->placeholder('Pilih Layanan')
                                                ->columnSpan(2)
                                                ->afterStateUpdated(function ($state, Set $set) {
                                                    $layanan = Layanan::with('kategoriLayanan')->find($state);
                                                    $set('nama_layanan',  $layanan?->nama_layanan ?? '');
                                                    $set('harga_per_kg',  $layanan?->harga_per_kg ?? 0);
                                                    $set('nama_kategori', $layanan?->kategoriLayanan?->nama_kategori ?? 'Kiloan');
                                                    $set('berat_kg', 1);
                                                    $set('subtotal', (int) ($layanan?->harga_per_kg ?? 0));
                                                }),

                                            Hidden::make('nama_layanan'),
                                            Hidden::make('nama_kategori'),
                                            Hidden::make('harga_per_kg')->dehydrated(),

                                            // Jumlah / Berat
                                            TextInput::make('berat_kg')
                                                ->label(fn (Get $get): string =>
                                                    $get('nama_kategori') === 'Satuan'
                                                        ? 'Jumlah (pcs)'
                                                        : 'Berat (kg)'
                                                )
                                                ->numeric()
                                                ->default(1)
                                                ->required()
                                                ->minValue(0.1)
                                                ->step(fn (Get $get): string =>
                                                    $get('nama_kategori') === 'Satuan' ? '1' : '0.1'
                                                )
                                                ->reactive()
                                                ->live()
                                                ->afterStateUpdated(function ($state, Get $get, Set $set) {
                                                    $harga = (float) ($get('harga_per_kg') ?? 0);
                                                    $jml   = (float) ($state ?? 0);
                                                    $set('subtotal', (int) ($harga * $jml));
                                                }),

                                            Placeholder::make('subtotal_display')
                                                ->label('Subtotal')
                                                ->content(fn (Get $get): string =>
                                                    'Rp ' . number_format(
                                                        (int) ($get('subtotal') ?? 0),
                                                        0, ',', '.'
                                                    )
                                                ),

                                            Hidden::make('subtotal')->dehydrated(),
                                        ])
                                        ->columns(['md' => 4])
                                        ->addActionLabel('+ Tambah Layanan')
                                        ->minItems(1)
                                        ->required()
                                        ->live()
                                        ->afterStateUpdated(function (Get $get, Set $set) {
                                            self::recalcTotal($get, $set);
                                        }),
                                ]),

                            // -------------------------------------------------
                            // Detail Pengantaran + Grand Total
                            // -------------------------------------------------
                            Section::make('Detail Pengantaran')
                                ->icon('heroicon-m-truck')
                                ->schema([
                                    Select::make('pengantaran')
                                        ->label('Jenis Pengantaran')
                                        ->options([
                                            'antar-jemput'  => 'Antar Jemput',
                                            'antar saja'    => 'Antar Saja',
                                            'ambil sendiri' => 'Ambil Sendiri',
                                        ])
                                        ->default('ambil sendiri')
                                        ->required()
                                        ->reactive()
                                        ->live()
                                        ->afterStateUpdated(function ($state, Get $get, Set $set) {
                                            if ($state === 'ambil sendiri') {
                                                $set('ongkir', 0);
                                            }
                                            self::recalcTotal($get, $set);
                                        }),

                                    TextInput::make('ongkir')
                                        ->label('Ongkos Kirim (Rp)')
                                        ->numeric()
                                        ->default(0)
                                        ->prefix('Rp')
                                        ->live()
                                        ->minValue(0)
                                        ->hidden(fn (Get $get) =>
                                            $get('pengantaran') === 'ambil sendiri'
                                            || blank($get('pengantaran'))
                                        )
                                        ->afterStateUpdated(function (Get $get, Set $set) {
                                            self::recalcTotal($get, $set);
                                        }),

                                    // Grand Total — Placeholder agar selalu reactive
                                    Placeholder::make('grand_total_display')
                                        ->label('Grand Total (Layanan + Ongkir)')
                                        ->content(function (Get $get): string {
                                            $items   = $get('detailPemesanan') ?? [];
                                            $layanan = collect($items)->sum(fn ($i) => (int) ($i['subtotal'] ?? 0));
                                            $isAmbil = $get('pengantaran') === 'ambil sendiri';
                                            $ongkir  = $isAmbil ? 0 : (float) ($get('ongkir') ?? 0);
                                            return 'Rp ' . number_format($layanan + $ongkir, 0, ',', '.');
                                        }),
                                ])
                                ->columns(3),

                            Forms\Components\Actions::make([
                                Forms\Components\Actions\Action::make('simpan_sementara')
                                    ->label('Proses Pesanan')
                                    ->color('primary')
                                    ->icon('heroicon-o-check-circle')
                                    ->action(function (Get $get) {
                                        self::simpanPemesanan($get);
                                    }),
                            ]),
                        ]),

                    // =========================================================
                    // STEP 2 — PROSES
                    // =========================================================
                    Wizard\Step::make('Proses')
                        ->icon('heroicon-o-arrow-path')
                        ->schema([
                            Section::make('Status Pesanan')
                                ->icon('heroicon-m-tag')
                                ->schema([
                                    Select::make('status')
                                        ->label('Status Pesanan')
                                        ->options([
                                            'on process' => 'On Process',
                                            'done'       => 'Done',
                                        ])
                                        ->default('on process')
                                        ->required()
                                        ->native(false),

                                    Placeholder::make('info_proses')
                                        ->label('')
                                        ->content('Ubah status pesanan sesuai kondisi laundry saat ini. Ubah status pesanan ke "Done" jika pesanan sudah selesai diproses.'),
                                ])
                                ->columns(2),
                        ]),

                    // =========================================================
                    // STEP 3 — PEMBAYARAN
                    // =========================================================
                    Wizard\Step::make('Pembayaran')
                        ->icon('heroicon-o-banknotes')
                        ->schema([
                            Section::make('Detail Pembayaran')
                                ->icon('heroicon-m-credit-card')
                                ->schema([
                                    Placeholder::make('total_tagihan_display')
                                        ->label('Total Tagihan')
                                        ->content(function (Get $get): string {
                                            $kode    = $get('kode_pemesanan');
                                            $pesan   = Pemesanan::where('kode_pemesanan', $kode)->first();
                                            $nominal = $pesan ? (float) $pesan->total_harga : 0;
                                            return 'Rp ' . number_format($nominal, 0, ',', '.');
                                        }),

                                    Select::make('jenis_pembayaran')
                                        ->label('Metode Pembayaran')
                                        ->options([
                                            'tunai'    => 'Tunai',
                                            'midtrans' => 'Pembayaran Lain (Midtrans)',
                                        ])
                                        ->required()
                                        ->reactive()
                                        ->live()
                                        ->native(false)
                                        ->placeholder('Pilih Metode')
                                        ->dehydrated(false),

                                    TextInput::make('nominal_bayar')
                                        ->label('Nominal Uang Diterima (Rp)')
                                        ->numeric()
                                        ->prefix('Rp')
                                        ->required()
                                        ->live()
                                        ->minValue(0)
                                        ->dehydrated(false)
                                        ->visible(fn (Get $get) => $get('jenis_pembayaran') === 'tunai')
                                        ->afterStateUpdated(function ($state, Get $get, Set $set) {
                                            $kode      = $get('kode_pemesanan');
                                            $pesan     = Pemesanan::where('kode_pemesanan', $kode)->first();
                                            $tagihan   = $pesan ? (float) $pesan->total_harga : 0;
                                            $set('kembalian', max(0, (float) $state - $tagihan));
                                        }),

                                    Placeholder::make('metode_pembayaran_tersimpan')
                                        ->label('Metode Pembayaran Tersimpan')
                                        ->content(function (Get $get): string {
                                            $kode = $get('kode_pemesanan');
                                            $pesan = Pemesanan::where('kode_pemesanan', $kode)->first();
                                            if (!$pesan) return '-';
                                            
                                            $pembayaran = Pembayaran::where('id_pemesanan', $pesan->id_pemesanan)->first();
                                            if (!$pembayaran) return 'Belum ada pembayaran';
                                            
                                            return match($pembayaran->jenis_pembayaran) {
                                                'tunai'    => 'Tunai',
                                                'midtrans' => 'Pembayaran Lain (Midtrans)',
                                                default    => $pembayaran->jenis_pembayaran ?? '-',
                                            };
                                        })
                                            ->visible(fn (Get $get): bool =>         // ← tambah ini
                                                Pembayaran::whereHas('pemesanan', fn ($q) =>
                                                    $q->where('kode_pemesanan', $get('kode_pemesanan'))
                                                )->exists()
                                            ),

                                    Placeholder::make('kembalian')
                                        ->label('Kembalian')
                                        ->content(function (Get $get): string {
                                            $kode      = $get('kode_pemesanan');
                                            $pesan     = Pemesanan::where('kode_pemesanan', $kode)->first();
                                            $tagihan   = $pesan ? (float) $pesan->total_harga : 0;
                                            $nominal   = (float) ($get('nominal_bayar') ?? 0);
                                            return 'Rp ' . number_format(max(0, $nominal - $tagihan), 0, ',', '.');
                                        })
                                        ->visible(fn (Get $get) => $get('jenis_pembayaran') === 'tunai'),

                                    // ── MIDTRANS: auto-trigger snap popup ──────
                                    // Begitu pilih Midtrans, x-init Alpine langsung
                                    // fetch snap token & buka popup otomatis
                                    Placeholder::make('midtrans_snap')
                                        ->label('')
                                        ->columnSpan(2)
                                        ->content(function (Get $get): HtmlString {
                                            if ($get('jenis_pembayaran') !== 'midtrans') {
                                                return new HtmlString('');
                                            }
 
                                            $kode = e($get('kode_pemesanan'));
 
                                            return new HtmlString(<<<HTML
                                            <div
                                                x-data="{
                                                    status: '⏳ Membuka halaman pembayaran Midtrans...',
                                                    statusClass: 'text-gray-500',
                                                    init() {
                                                        setTimeout(() => this.openSnap(), 700);
                                                    },
                                                    openSnap() {
                                                        fetch('/midtrans/snap-token', {
                                                            method: 'POST',
                                                            headers: {
                                                                'Content-Type': 'application/json',
                                                                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                                                            },
                                                            body: JSON.stringify({ kode_pemesanan: '{$kode}' }),
                                                        })
                                                        .then(r => r.json())
                                                        .then(data => {
                                                            if (!data.success) {
                                                                this.status = '❌ Gagal: ' + (data.message || 'Terjadi kesalahan.');
                                                                this.statusClass = 'text-red-600';
                                                                return;
                                                            }
                                                            this.status = '⏳ Menunggu pembayaran...';
                                                            snap.pay(data.snap_token, {
                                                                onSuccess: (result) => {
                                                                    this.status = '✅ Pembayaran berhasil! Order: ' + result.order_id;
                                                                    this.statusClass = 'text-green-600';
                                                                },
                                                                onPending: (result) => {
                                                                    this.status = '⏳ Menunggu pembayaran... Order: ' + result.order_id;
                                                                    this.statusClass = 'text-yellow-600';
                                                                },
                                                                onError: (result) => {
                                                                    this.status = '❌ Gagal: ' + result.status_message;
                                                                    this.statusClass = 'text-red-600';
                                                                },
                                                                onClose: () => {
                                                                    this.status = '💬 Popup ditutup. Pilih metode lain atau klik Midtrans lagi untuk membuka ulang.';
                                                                    this.statusClass = 'text-gray-500';
                                                                },
                                                            });
                                                        })
                                                        .catch(err => {
                                                            this.status = '❌ Error: ' + err.message;
                                                            this.statusClass = 'text-red-600';
                                                        });
                                                    }
                                                }"
                                            >
                                                <p class="text-sm font-medium" :class="statusClass" x-text="status"></p>
                                            </div>
                                            HTML);
                                        })
                                        ->visible(fn (Get $get) => $get('jenis_pembayaran') === 'midtrans'),
                                ])
                                ->columns(2),

                            Forms\Components\Actions::make([
                                Forms\Components\Actions\Action::make('simpan_pembayaran')
                                    ->label('Simpan Pembayaran')
                                    ->color('success')
                                    ->icon('heroicon-o-check-badge')
                                    ->action(function (Get $get) {
                                        $kode  = $get('kode_pemesanan');
                                        $pesan = Pemesanan::where('kode_pemesanan', $kode)->first();

                                        if (! $pesan) {
                                            \Filament\Notifications\Notification::make()
                                                ->title('Proses pesanan terlebih dahulu!')
                                                ->warning()
                                                ->send();
                                            return;
                                        }

                                        $jenis = $get('jenis_pembayaran');
                                        if (! $jenis) {
                                            \Filament\Notifications\Notification::make()
                                                ->title('Pilih metode pembayaran!')
                                                ->warning()
                                                ->send();
                                            return;
                                        }

                                        Pembayaran::updateOrCreate(
                                            ['id_pemesanan' => $pesan->id_pemesanan],
                                            [
                                                'tgl_bayar'        => now()->toDateString(),
                                                'jenis_pembayaran' => $jenis,
                                                'transaction_time' => now(),
                                                'gross_amount'     => $pesan->total_harga,
                                                'order_id'         => $pesan->kode_pemesanan,
                                                'payment_type'     => $jenis === 'tunai' ? 'cash' : 'midtrans',
                                                'status_code'      => '200',
                                                'status_message'   => $jenis === 'tunai'
                                                    ? 'Pembayaran tunai berhasil.'
                                                    : 'Pending Midtrans.',
                                            ]
                                        );

                                        \Filament\Notifications\Notification::make()
                                            ->title('Pembayaran berhasil disimpan!')
                                            ->success()
                                            ->send();
                                    }),
                            ]),
                        ]),

                ])->columnSpan('full'),
            ]);
    }

    // =========================================================================
    // TABLE
    // =========================================================================
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('kode_pemesanan')
                    ->label('Kode Pesanan')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('member.nama_pelanggan')
                    ->label('Pelanggan')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('pengantaran')
                    ->label('Pengantaran')
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

                TextColumn::make('total_harga')
                    ->label('Total')
                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format((float) $state, 0, ',', '.'))
                    ->sortable()
                    ->alignment('end'),

                TextColumn::make('tgl_pesan')
                    ->label('Tgl Pesan')
                    ->date('d/m/Y')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'on process' => 'On Process',
                        'done'       => 'Done',
                    ]),

                SelectFilter::make('pengantaran')
                    ->label('Pengantaran')
                    ->options([
                        'antar-jemput'  => 'Antar Jemput',
                        'antar saja'    => 'Antar Saja',
                        'ambil sendiri' => 'Ambil Sendiri',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->headerActions([
                Action::make('downloadPdf')
                    ->label('Unduh PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('success')
                    ->action(function () {
                        $pemesanan = Pemesanan::with(['member', 'detailPemesanan', 'pembayaran'])->get();
                        $pdf = Pdf::loadView('pdf.pemesanan', ['pemesanan' => $pemesanan]);
                        return response()->streamDownload(
                            fn () => print($pdf->output()),
                            'pemesanan-list.pdf'
                        );
                    }),
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
            'view'   => Pages\ViewPemesanan::route('/{record}'),
        ];
    }

    // =========================================================================
    // HELPERS
    // =========================================================================

    /**
     * Recalculate total_harga = subtotal semua layanan + ongkir.
     */
    public static function recalcTotal(Get $get, Set $set): void
    {
        $items   = $get('detailPemesanan') ?? [];
        $layanan = collect($items)->sum(fn ($i) => (int) ($i['subtotal'] ?? 0));
        $isAmbil = $get('pengantaran') === 'ambil sendiri';
        $ongkir  = $isAmbil ? 0 : (float) ($get('ongkir') ?? 0);
        $set('total_harga', $layanan + $ongkir);
    }

    /**
     * Simpan / update pemesanan + detail_pemesanan dari tombol "Proses Pesanan".
     * berat_kg & id_layanan sudah dihapus dari tabel pemesanan.
     */
    public static function simpanPemesanan(Get $get): void
    {
        $kode  = $get('kode_pemesanan');
        $items = $get('detailPemesanan') ?? [];

        $totalLayanan = collect($items)->sum(fn ($i) => (int) ($i['subtotal'] ?? 0));
        $isAmbil      = ($get('pengantaran') ?? 'ambil sendiri') === 'ambil sendiri';
        $ongkir       = $isAmbil ? 0 : (float) ($get('ongkir') ?? 0);
        $totalHarga   = $totalLayanan + $ongkir;

        $pemesanan = Pemesanan::updateOrCreate(
            ['kode_pemesanan' => $kode],
            [
                'id_pelanggan' => $get('id_pelanggan'),
                'tgl_pesan'    => $get('tgl_pesan') ?? today(),
                'status'       => $get('status') ?? 'on process',
                'total_harga'  => $totalHarga,
                'ongkir'       => $ongkir,
                'pengantaran'  => $get('pengantaran') ?? 'ambil sendiri',
            ]
        );

        // Hapus detail lama lalu insert ulang agar tidak duplikat
        DetailPemesanan::where('id_pemesanan', $pemesanan->id_pemesanan)->delete();

        foreach ($items as $item) {
            if (empty($item['id_layanan'])) {
                continue;
            }

            $layananDb = Layanan::find($item['id_layanan']);
            $harga     = (float) ($layananDb?->harga_per_kg ?? $item['harga_per_kg'] ?? 0);
            $berat     = (float) ($item['berat_kg'] ?? 1);

            DetailPemesanan::create([
                'id_pemesanan' => $pemesanan->id_pemesanan,
                'id_layanan'   => $item['id_layanan'],
                'nama_layanan' => $layananDb?->nama_layanan ?? $item['nama_layanan'] ?? null,
                'harga_per_kg' => $harga,
                'berat_kg'     => $berat,
                'subtotal'     => (int) ($harga * $berat),
            ]);
        }

        \Filament\Notifications\Notification::make()
            ->title('Pesanan berhasil diproses!')
            ->success()
            ->send();
    }
}