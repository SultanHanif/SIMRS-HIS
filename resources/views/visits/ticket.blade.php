<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tiket antrean {{ $visit->visit_number }} · SIMRS</title>
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

        .ticket-shell {
            display: grid;
            justify-items: center;
            gap: 1rem;
            padding: 2rem 1rem;
        }

        .ticket {
            width: 80mm;
            padding: 7mm;
            border: 1px solid #e2e8f0;
            border-radius: 1rem;
            background: #fff;
            box-shadow: 0 10px 30px rgb(15 23 42 / 8%);
        }

        .brand,
        .clinic,
        .patient,
        .queue-number,
        .visit-time,
        .detail-label,
        .detail-value,
        .ticket-note {
            margin: 0;
        }

        .brand {
            color: #0f766e;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: .16em;
            text-align: center;
            text-transform: uppercase;
        }

        .clinic {
            margin-top: 5px;
            font-size: 17px;
            font-weight: 700;
            line-height: 1.25;
            text-align: center;
        }

        .ticket-divider {
            margin: 1rem 0;
            border: 0;
            border-top: 1px dashed #cbd5e1;
        }

        .queue-label {
            margin: 0;
            color: #64748b;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: .12em;
            text-align: center;
            text-transform: uppercase;
        }

        .queue-number {
            margin-top: 4px;
            color: #0f172a;
            font-family: "Courier New", monospace;
            font-size: 28px;
            font-weight: 700;
            letter-spacing: .08em;
            text-align: center;
            overflow-wrap: anywhere;
        }

        .patient {
            font-size: 16px;
            font-weight: 700;
            line-height: 1.35;
            text-align: center;
            overflow-wrap: anywhere;
        }

        .patient-record {
            margin: 4px 0 0;
            color: #64748b;
            font-size: 11px;
            text-align: center;
        }

        .visit-time {
            margin-top: 5px;
            font-size: 14px;
            font-weight: 700;
            text-align: center;
        }

        .ticket-details {
            display: grid;
            gap: 10px;
        }

        .detail-label {
            color: #64748b;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: .06em;
            text-transform: uppercase;
        }

        .detail-value {
            margin-top: 3px;
            font-size: 12px;
            font-weight: 600;
            line-height: 1.4;
            overflow-wrap: anywhere;
        }

        .ticket-note {
            color: #475569;
            font-size: 11px;
            line-height: 1.5;
            text-align: center;
        }

        .ticket-actions {
            display: flex;
            gap: .75rem;
        }

        .ticket-button {
            min-height: 42px;
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

        .ticket-button.secondary {
            border: 1px solid #cbd5e1;
            background: #fff;
            color: #334155;
        }

        @media print {
            @page {
                margin: 4mm;
            }

            body {
                background: #fff;
            }

            .ticket-shell {
                display: block;
                padding: 0;
            }

            .ticket {
                width: 72mm;
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
    <main class="ticket-shell">
        <article class="ticket" aria-label="Tiket antrean pasien">
            <p class="brand">SIMRS · Rawat Jalan</p>
            <h1 class="clinic">{{ $visit->clinic->name }}</h1>

            <hr class="ticket-divider">

            <p class="queue-label">Nomor antrean · {{ $visit->clinic->code }}</p>
            <p class="queue-number">{{ $visit->formattedQueueNumber() }}</p>

            <hr class="ticket-divider">

            <p class="patient">{{ $visit->patient->name }}</p>
            <p class="patient-record">No. RM: {{ $visit->patient->medical_record_number }}</p>
            <p class="visit-time">{{ $visit->visited_at->translatedFormat('d F Y · H:i') }}</p>
            <p class="patient-record">No. kunjungan: {{ $visit->visit_number }}</p>

            <hr class="ticket-divider">

            <div class="ticket-details">
                <div>
                    <p class="detail-label">Dokter</p>
                    <p class="detail-value">{{ $visit->doctor->name }}{{ $visit->doctor->specialization ? ' · '.$visit->doctor->specialization : '' }}</p>
                </div>
                <div>
                    <p class="detail-label">Status</p>
                    <p class="detail-value">{{ [
                        'waiting' => $visit->called_at ? 'Menunggu — pasien telah dipanggil' : 'Menunggu panggilan',
                        'in_consultation' => 'Dalam pemeriksaan',
                        'awaiting_pharmacy' => 'Menunggu farmasi',
                        'awaiting_payment' => 'Menunggu pembayaran',
                        'completed' => 'Kunjungan selesai',
                        'no_show' => 'Pasien tidak hadir',
                        'cancelled' => 'Kunjungan dibatalkan',
                    ][$visit->status] ?? $visit->status }}</p>
                </div>
            </div>

            <hr class="ticket-divider">

            <p class="ticket-note">
                @if ($visit->status === 'waiting')
                    Mohon menunggu di area poli. Petugas akan memanggil nomor antrean Anda.
                @else
                    Simpan tiket ini sebagai referensi kunjungan Anda.
                @endif
            </p>
        </article>

        <div class="ticket-actions no-print">
            <button type="button" class="ticket-button" onclick="window.print()">Cetak tiket</button>
            <a href="{{ route('visits.show', $visit) }}" class="ticket-button secondary">Kembali</a>
        </div>
    </main>
</body>
</html>
