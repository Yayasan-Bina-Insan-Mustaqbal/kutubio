<!DOCTYPE html>
<html>
<head>
    <style>
        @page {
            margin: 0;
            size: {{ $profile->page_width_mm }}mm {{ $profile->page_height_mm }}mm;
        }
        body {
            margin: 0;
            padding: 0;
            font-family: sans-serif;
        }
        .page {
            position: relative;
            width: {{ $profile->page_width_mm }}mm;
            height: {{ $profile->page_height_mm }}mm;
            overflow: hidden;
        }
        .grid {
            display: grid;
            grid-template-columns: repeat({{ $profile->grid_columns }}, {{ $profile->slot_width_mm }}mm);
            grid-template-rows: repeat({{ $profile->grid_rows }}, {{ $profile->slot_height_mm }}mm);
            column-gap: {{ $profile->gap_x_mm }}mm;
            row-gap: {{ $profile->gap_y_mm }}mm;
            padding-left: {{ $profile->offset_x_mm }}mm;
            padding-top: {{ $profile->offset_y_mm }}mm;
        }
        .slot {
            width: {{ $profile->slot_width_mm }}mm;
            height: {{ $profile->slot_height_mm }}mm;
            border: 0.1mm dashed #eee; /* Light boundary for alignment debug */
            box-sizing: border-box;
            display: flex;
            align-items: center;
            padding: 2mm;
            overflow: hidden;
        }
        .qr {
            width: 18mm;
            height: 18mm;
            margin-right: 2mm;
        }
        .qr svg {
            width: 100%;
            height: 100%;
        }
        .meta {
            flex: 1;
            font-size: 7pt;
            line-height: 1.1;
        }
        .title {
            font-weight: bold;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            margin-bottom: 1mm;
        }
        .call-number {
            font-family: monospace;
            background: #f0f0f0;
            padding: 0.5mm 1mm;
            font-size: 8pt;
        }
    </style>
</head>
<body>
    <div class="page">
        <div class="grid">
            @for ($i = 0; $i < $skipSlots; $i++)
                <div class="slot empty"></div>
            @endfor

            @foreach ($items as $item)
                <div class="slot">
                    <div class="qr">
                        {!! \SimpleSoftwareIO\QrCode\Facades\QrCode::size(100)->format('svg')->generate($item->qr_payload ?? $item->public_id) !!}
                    </div>
                    <div class="meta">
                        <div class="title">{{ $item->book->title ?? $item->name ?? 'Untitled' }}</div>
                        <div class="call-number">
                            {{ $item->book->category->code ?? 'GEN' }}
                            {{ substr($item->book->authors_display ?? 'UNK', 0, 3) }}
                        </div>
                        <div style="font-size: 5pt; margin-top: 1mm; color: #666; display: flex; justify-content: space-between;">
                            <span>{{ $item->public_id }}</span>
                            <span style="font-style: italic; font-weight: bold;">{{ $libraryName }}</span>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</body>
</html>
