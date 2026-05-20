<!DOCTYPE html>
<html>
<head>
    <style>
        @page {
            margin: 5mm;
            size: A4;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #fff;
        }
        .page-break {
            page-break-after: always;
        }
        .cards-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            grid-template-rows: 1fr 1fr;
            gap: 5mm;
            width: 200mm;
            height: 287mm;
            margin: 0 auto;
        }
        .card {
            border: 0.3mm solid #000;
            padding: 5mm;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            height: 138mm; /* Half of A4 approx */
            position: relative;
        }
        .header {
            text-align: center;
            border-bottom: 0.5mm double #000;
            margin-bottom: 3mm;
            padding-bottom: 2mm;
        }
        .library-name {
            font-size: 11pt;
            font-weight: bold;
            text-transform: uppercase;
            color: #000;
        }
        .card-title {
            font-size: 10pt;
            margin-top: 1mm;
            letter-spacing: 1mm;
            font-weight: 500;
        }
        .book-info {
            font-size: 9pt;
            margin-bottom: 3mm;
        }
        .info-row {
            margin-bottom: 1mm;
            display: flex;
        }
        .label {
            font-weight: bold;
            width: 20mm;
            flex-shrink: 0;
        }
        .value {
            flex-grow: 1;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            flex-grow: 1;
        }
        th, td {
            border: 0.2mm solid #000;
            padding: 1.2mm;
            text-align: left;
            font-size: 8pt;
            height: 7mm;
        }
        th {
            background: #f5f5f5;
            text-transform: uppercase;
            font-weight: bold;
        }
        .col-date { width: 22%; }
        .col-borrower { width: 56%; }
    </style>
</head>
<body>
    @foreach ($items->chunk(4) as $chunk)
        <div class="cards-container {{ !$loop->last ? 'page-break' : '' }}">
            @foreach ($chunk as $item)
                @php
                    $book = $item instanceof \App\Models\BookCopy ? $item->book : $item;
                    $publicId = $item instanceof \App\Models\BookCopy ? $item->public_id : $item->public_id;
                @endphp
                <div class="card">
                    <div class="header">
                        <div class="library-name">{{ $libraryName }}</div>
                        <div class="card-title">BOOK CARD</div>
                    </div>
                    
                    <div class="book-info">
                        <div class="info-row"><span class="label">Call No:</span> <span class="value">{{ $book->category->code ?? '...' }}</span></div>
                        <div class="info-row"><span class="label">Author:</span> <span class="value">{{ $book->authors_display ?? '...' }}</span></div>
                        <div class="info-row"><span class="label">Title:</span> <span class="value">{{ Str::limit($book->title, 60) }}</span></div>
                        <div class="info-row"><span class="label">Acc. No:</span> <span class="value">{{ $publicId }}</span></div>
                    </div>

                    <table>
                        <thead>
                            <tr>
                                <th class="col-date">Borrow Date</th>
                                <th class="col-date">Return Date</th>
                                <th class="col-borrower">Borrower's Name</th>
                            </tr>
                        </thead>
                        <tbody>
                            @for ($i = 0; $i < 9; $i++)
                                <tr>
                                    <td>&nbsp;</td>
                                    <td>&nbsp;</td>
                                    <td>&nbsp;</td>
                                </tr>
                            @endfor
                        </tbody>
                    </table>
                </div>
            @endforeach
            
            {{-- Fill empty slots to maintain grid if chunk < 4 --}}
            @for ($i = 0; $i < (4 - count($chunk)); $i++)
                <div style="border: 0.3mm dashed #ccc;"></div>
            @endfor
        </div>
    @endforeach
</body>
</html>
