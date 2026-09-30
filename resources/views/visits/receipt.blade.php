<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kuitansi {{ $visit->visit_number }} · SIMRS</title>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: #f1f5f9;
            color: #0f172a;
            font-family: Arial, Helvetica, sans-serif;
        }

        .receipt-shell {
            display: grid;
            justify-items: center;
            gap: 1rem;
            padding: 2rem 1rem;
        }

        .receipt {
            width: min(100%, 760px);
            padding: 2.5rem;
            border: 1px solid #e2e8f0;
            border-radius: 1rem;
            background: #fff;
            box-shadow: 0 10px 30px rgb(15 23 42 / 8%);
        }

        .receipt-heading,
        .receipt-subtitle,
        .receipt-meta,
        .receipt-label,
        .receipt-value,
        .receipt-note {
            margin: 0;
        }

        .receipt-heading {
            color: #0f766e;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: .16em;
            text-transform: uppercase;
        }

        .receipt-title {
            margin: .5rem 0 0;
            font-size: 28px;
            letter-spacing: -.03em;
        }

        .receipt-subtitle {
            margin-top: .4rem;
            color: #64748b;
            font-size: 14px;
        }

        .receipt-rule {
            margin: 1.5rem 0;
            border: 0;
            border-top: 1px solid #e2e8f0;
        }

        .receipt-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 1.25rem;
        }

        .receipt-label {
            color: #64748b;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: .06em;
            text-transform: uppercase;
        }

        .receipt-value {
            margin-top: .35rem;
            font-size: 14px;
            font-weight: 600;
            line-height: 1.5;
            overflow-wrap: anywhere;
        }

        .receipt-amount {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: 1rem 0;
            border-top: 1px dashed #cbd5e1;
            border-bottom: 1px dashed #cbd5e1;
        }

        .receipt-amount .receipt-value {
            color: #047857;
            font-size: 22px;
        }

        .receipt-note {
            color: #64748b;
            font-size: 12px;
            line-height: 1.6;
        }

        .receipt-actions {
            display: flex;
            gap: .75rem;
        }

        .receipt-button {
            display: inline-flex;
            min-height: 42px;
            align-items: center;
            justify-content: center;
            padding: .65rem 1rem;
            border: 0;
            border-radius: .75rem;
            background: #0f766e;
            color: #fff;
            cursor: pointer;
            font-size: 14px;
            font-weight: 700;
            text-decoration: none;
        }

        .receipt-button.secondary {
            border: 1px solid #cbd5e1;
            background: #fff;
            color: #334155;
        }

        @media (max-width: 600px) {
            .receipt {
                padding: 1.5rem;
            }
        }

        @media print {
            @page {
                margin: 12mm;
            }

            body {
                background: #fff;
            }

            .receipt-shell {
                display: block;
                padding: 0;
            }

            .receipt {
                width: 100%;
                padding: 0;
                border: 0;
                border-radius: 0;
                box-shadow: none;
            }

            .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body>
    <main class="receipt-shell">
        <article class="receipt" aria-label="Kuitansi pembayaran pasien">
            <p class="receipt-heading">SIMRS · Rawat Jalan</p>
            <h1 class="receipt-title">Kuitansi pembayaran</h1>
            <p class="receipt-subtitle">Bukti pembayaran kunjungan pasien</p>

            <hr class="receipt-rule">

            <div class="receipt-grid">
                <div>
                    <p class="receipt-label">No. kuitansi</p>
                    <p class="receipt-value">INV-{{ str_pad((string) $visit->invoice->id, 6, '0', STR_PAD_LEFT) }}</p>
                </div>
                <div>
                    <p class="receipt-label">Waktu pembayaran</p>
                    <p class="receipt-value">{{ $visit->invoice->paid_at?->translatedFormat('d F Y, H:i') ?? 'Tidak tercatat' }}</p>
                </div>
                <div>
                    <p class="receipt-label">Nama pasien</p>
                    <p class="receipt-value">{{ $visit->patient->name }}</p>
                </div>
                <div>
                    <p class="receipt-label">Nomor rekam medis</p>
                    <p class="receipt-value">{{ $visit->patient->medical_record_number }}</p>
                </div>
                <div>
                    <p class="receipt-label">Nomor kunjungan</p>
                    <p class="receipt-value">{{ $visit->visit_number }}</p>
                </div>
                <div>
                    <p class="receipt-label">Poli / unit layanan</p>
                    <p class="receipt-value">{{ $visit->clinic->name }}</p>
                </div>
                <div>
                    <p class="receipt-label">Dokter</p>
                    <p class="receipt-value">{{ $visit->doctor->name }}</p>
                </div>
                <div>
                    <p class="receipt-label">Metode pembayaran</p>
                    <p class="receipt-value">{{ ['cash' => 'Tunai', 'qris' => 'QRIS', 'bank_transfer' => 'Transfer bank', 'card' => 'Kartu debit/kredit'][$visit->invoice->payment_method] ?? 'Tidak tercatat' }}</p>
                </div>
            </div>

            <hr class="receipt-rule">

            <div class="receipt-amount">
                <div>
                    <p class="receipt-label">Layanan</p>
                    <p class="receipt-value">Konsultasi rawat jalan</p>
                </div>
                @if (($visit->prescription?->total_price ?? 0) > 0)
                    <div>
                        <p class="receipt-label">Obat</p>
                        <p class="receipt-value">{{ $visit->prescription->medication_name }} ({{ number_format($visit->prescription->quantity) }} satuan)</p>
                        <p class="receipt-note">Rp {{ number_format($visit->prescription->total_price, 0, ',', '.') }}</p>
                    </div>
                @endif
                <div>
                    <p class="receipt-label">Total dibayar</p>
                    <p class="receipt-value">Rp {{ number_format($visit->invoice->amount, 0, ',', '.') }}</p>
                </div>
            </div>

            <div class="receipt-grid" style="margin-top: 1.25rem">
                <div>
                    <p class="receipt-label">Status</p>
                    <p class="receipt-value">LUNAS</p>
                </div>
                <div>
                    <p class="receipt-label">Diterima oleh</p>
                    <p class="receipt-value">{{ $visit->invoice->paidBy?->name ?? 'Tidak tercatat' }}</p>
                </div>
                <div>
                    <p class="receipt-label">Nominal diterima</p>
                    <p class="receipt-value">Rp {{ number_format($visit->invoice->amount_received ?? $visit->invoice->amount, 0, ',', '.') }}</p>
                </div>
                @if (($visit->invoice->change_amount ?? 0) > 0)
                    <div>
                        <p class="receipt-label">Kembalian</p>
                        <p class="receipt-value">Rp {{ number_format($visit->invoice->change_amount, 0, ',', '.') }}</p>
                    </div>
                @endif
                @if ($visit->invoice->payment_reference)
                    <div>
                        <p class="receipt-label">No. referensi</p>
                        <p class="receipt-value">{{ $visit->invoice->payment_reference }}</p>
                    </div>
                @endif
            </div>

            <hr class="receipt-rule">
            <p class="receipt-note">Simpan kuitansi ini sebagai bukti pembayaran. Kuitansi ini diterbitkan oleh sistem secara elektronik.</p>
        </article>

        <div class="receipt-actions no-print">
            <button type="button" class="receipt-button" onclick="window.print()">Cetak kuitansi</button>
            <a href="{{ route('visits.show', $visit) }}" class="receipt-button secondary">Kembali ke kunjungan</a>
        </div>
    </main>
</body>
</html>
