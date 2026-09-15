<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page {
            margin: 10mm;
            size: A4 portrait;
        }

        body {
            font-family: Arial, sans-serif;
            color: #111827;
            margin: 0;
            padding: 0;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: end;
            border-bottom: 2px solid #111827;
            padding-bottom: 6mm;
            margin-bottom: 8mm;
        }

        .title {
            font-size: 18pt;
            font-weight: bold;
        }

        .meta {
            font-size: 9pt;
            text-align: right;
            color: #374151;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9pt;
        }

        th, td {
            border: 0.8px solid #cbd5e1;
            padding: 3mm 2mm;
            vertical-align: top;
            text-align: left;
        }

        th {
            background: #f3f4f6;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        tbody tr:nth-child(even) {
            background: #f9fafb;
        }

        .badge {
            display: inline-block;
            padding: 1mm 3mm;
            border-radius: 9999px;
            font-size: 8pt;
            font-weight: bold;
            background: #dbeafe;
            color: #1e3a8a;
        }

        .badge.returned {
            background: #dcfce7;
            color: #166534;
        }

        .badge.overdue {
            background: #fee2e2;
            color: #991b1b;
        }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <div class="title">Data Peminjaman Buku</div>
            <div style="font-size: 10pt; margin-top: 2mm;">{{ $libraryName }}</div>
        </div>
        <div class="meta">
            <div>Dicetak: {{ $generatedAt->translatedFormat('d F Y H:i') }}</div>
            <div>Jumlah: {{ $items->count() }} data</div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Judul Buku</th>
                <th>Kelas</th>
                <th>Nama Peminjam</th>
                <th>Status</th>
                <th>Tanggal Pinjam</th>
                <th>Jatuh Tempo</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($items as $index => $item)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $item->bookCopy?->book?->title ?? '-' }}</td>
                    <td>{{ $item->borrower?->class ?? '-' }}</td>
                    <td>{{ $item->borrower?->name ?? '-' }}</td>
                    <td>
                        @php
                            $statusValue = $item->status instanceof \BackedEnum
                                ? $item->status->value
                                : (string) $item->status;
                            $statusLabel = ucfirst(str_replace(['-', '_'], ' ', $statusValue));
                            $statusClass = match ($statusValue) {
                                'returned' => 'returned',
                                'overdue' => 'overdue',
                                default => '',
                            };
                        @endphp
                        <span class="badge {{ $statusClass }}">{{ $statusLabel }}</span>
                    </td>
                    <td>{{ $item->loaned_at?->translatedFormat('d F Y') ?? '-' }}</td>
                    <td>{{ $item->due_at?->translatedFormat('d F Y') ?? '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="text-align: center; padding: 10mm;">Tidak ada data peminjaman.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
