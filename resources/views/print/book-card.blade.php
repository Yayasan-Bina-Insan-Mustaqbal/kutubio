<!DOCTYPE html>
<html>
<head>
    <style>
        @page {
            margin: 10mm;
            size: A4;
        }
        body {
            font-family: serif;
            margin: 0;
            padding: 0;
        }
        .cards-container {
            display: flex;
            flex-wrap: wrap;
            gap: 10mm;
        }
        .card {
            width: 85mm;
            height: 125mm;
            border: 0.5mm solid #000;
            padding: 5mm;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
        }
        .header {
            text-align: center;
            border-bottom: 0.3mm solid #000;
            margin-bottom: 3mm;
            padding-bottom: 2mm;
        }
        .library-name {
            font-size: 10pt;
            font-weight: bold;
            text-transform: uppercase;
        }
        .card-title {
            font-size: 12pt;
            margin-top: 1mm;
        }
        .book-info {
            font-size: 9pt;
            margin-bottom: 4mm;
        }
        .info-row {
            margin-bottom: 1mm;
        }
        .label {
            font-weight: bold;
            display: inline-block;
            width: 25mm;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            flex: 1;
        }
        th, td {
            border: 0.2mm solid #000;
            padding: 1.5mm;
            text-align: left;
            font-size: 8pt;
        }
        th {
            background: #f0f0f0;
        }
        .col-date { width: 30%; }
        .col-borrower { width: 70%; }
    </style>
</head>
<body>
    <div class="cards-container">
        @foreach ($books as $book)
            <div class="card">
                <div class="header">
                    <div class="library-name">Kutubio Smart Library</div>
                    <div class="card-title">BOOK CARD</div>
                </div>
                
                <div class="book-info">
                    <div class="info-row"><span class="label">Call No:</span> {{ $book->category->code ?? '...' }}</div>
                    <div class="info-row"><span class="label">Author:</span> {{ $book->authors_display ?? '...' }}</div>
                    <div class="info-row"><span class="label">Title:</span> {{ $book->title }}</div>
                    <div class="info-row"><span class="label">Acc. No:</span> {{ $book->public_id }}</div>
                </div>

                <table>
                    <thead>
                        <tr>
                            <th class="col-date">Date Due</th>
                            <th class="col-borrower">Borrower's Name</th>
                        </tr>
                    </thead>
                    <tbody>
                        @for ($i = 0; $i < 12; $i++)
                            <tr>
                                <td>&nbsp;</td>
                                <td>&nbsp;</td>
                            </tr>
                        @endfor
                    </tbody>
                </table>
            </div>
        @endforeach
    </div>
</body>
</html>
