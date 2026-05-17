    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="utf-8">
        <title>Laporan Biaya</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                background: #f8fafc;
                color: #111827;
                margin: 0;
                padding: 0;
            }
            .email-wrapper {
                width: 100%;
                padding: 20px;
                background: #f8fafc;
            }
            .email-content {
                max-width: 680px;
                margin: 0 auto;
                background: #ffffff;
                border-radius: 12px;
                box-shadow: 0 4px 20px rgba(17, 24, 39, 0.06);
                padding: 32px;
            }
            .header {
                margin-bottom: 24px;
            }
            .header h1 {
                margin: 0;
                font-size: 28px;
                color: #111827;
            }
            .header p {
                margin: 8px 0 0 0;
                color: #6b7280;
                font-size: 14px;
            }
            .content p {
                line-height: 1.75;
                color: #374151;
                font-size: 15px;
            }
            .table-box {
                margin: 24px 0;
                overflow-x: auto;
            }
            table {
                width: 100%;
                border-collapse: collapse;
                margin: 0;
            }
            th,
            td {
                padding: 12px 14px;
                border: 1px solid #e5e7eb;
                text-align: left;
            }
            th {
                background: #f3f4f6;
                color: #4b5563;
                font-weight: 600;
            }
            td {
                color: #374151;
            }
            .text-right {
                text-align: right;
            }
            .footer {
                margin-top: 24px;
                color: #6b7280;
                font-size: 13px;
            }
        </style>
    </head>
    <body>
        <div class="email-wrapper">
            <div class="email-content">
                <div class="header">
                    <h1>Laporan Biaya</h1>
                    <p>Periode: {{ $data['periode'] ?? 'Belum ditentukan' }}</p>
                </div>

                <div class="content">
                    <p>Halo {{ $data['owner_name'] ?? 'Owner' }},</p>
                    <p>Berikut adalah ringkasan pencatatan biaya untuk periode <strong>{{ $data['periode'] ?? 'terkini' }}</strong>.</p>
                </div>

                <div class="table-box">
                    <table>
                        <thead>
                            <tr>
                                <th>No.</th>
                                <th>Jenis Beban</th>
                                <th>Pegawai</th>
                                <th>COA</th>
                                <th class="text-right">Nominal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if(!empty($data['biayas']) && count($data['biayas']) > 0)
                                @foreach($data['biayas'] as $index => $biaya)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>{{ $biaya->jenis_beban ?? '-' }}</td>
                                        <td>{{ $biaya->pegawai->nama_pegawai ?? 'N/A' }}</td>
                                        <td>{{ $biaya->coa->nama_akun ?? 'N/A' }}</td>
                                        <td class="text-right">{{ number_format($biaya->nominal ?? 0, 0, ',', '.') }}</td>
                                    </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td colspan="5">Tidak ada data biaya.</td>
                                </tr>
                            @endif
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="4" class="text-right"><strong>Total</strong></td>
                                <td class="text-right"><strong>{{ number_format($data['total'] ?? 0, 0, ',', '.') }}</strong></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="content">
                    <p>Terima kasih telah menggunakan sistem ini. Jika ada pertanyaan, silakan balas email ini.</p>
                </div>

                <div class="footer">
                    <p>Salam,<br>Tim Keuangan</p>
                </div>
            </div>
        </div>
    </body>
    </html>
