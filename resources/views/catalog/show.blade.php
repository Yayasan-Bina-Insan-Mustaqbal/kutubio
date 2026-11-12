<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Public book details in the Kutubio library catalog.">
    <title>{{ $book->title }} — Kutubio Catalog</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { color-scheme:dark; --bg:#020617; --card:#0f172a; --primary:#f97316; --dim:#94a3b8; --border:rgba(255,255,255,.1); --gradient:linear-gradient(135deg,#f97316 0%,#d946ef 100%); font-family:'Plus Jakarta Sans',sans-serif; background:var(--bg); color:#f8fafc; }
        * { box-sizing:border-box; scroll-behavior:smooth; }
        body { margin:0; min-height:100vh; background:var(--bg); line-height:1.6; }
        a { color:inherit; }
        .container { width:min(1100px,calc(100% - 2rem)); margin-inline:auto; }
        .header { position:sticky; top:0; z-index:100; border-bottom:1px solid var(--border); background:rgba(2,6,23,.88); backdrop-filter:blur(18px); }
        .nav { display:flex; min-height:68px; align-items:center; justify-content:space-between; gap:1rem; }
        .logo { color:#fff; font-size:1.5rem; font-weight:800; text-decoration:none; }
        .logo span { background:var(--gradient); background-clip:text; -webkit-background-clip:text; color:transparent; }
        .back { color:#fdba74; font-weight:700; text-decoration:none; }
        main { padding-block:2.5rem 5rem; }
        .book { display:grid; grid-template-columns:1fr; gap:2rem; align-items:start; }
        .cover { display:grid; min-height:320px; aspect-ratio:3/4; place-items:center; overflow:hidden; padding:1rem; border:1px solid var(--border); border-radius:1.5rem; background:linear-gradient(145deg,#111c32,#080e1d); }
        .cover img { display:block; width:100%; height:100%; object-fit:contain; }
        .eyebrow { margin:0 0 .5rem; color:#fb923c; font-size:.78rem; font-weight:800; letter-spacing:.13em; text-transform:uppercase; }
        h1 { margin:0; font-size:clamp(2rem,5vw,3.25rem); font-weight:800; letter-spacing:-.04em; line-height:1.15; }
        .subtitle { margin:.65rem 0 0; color:#cbd5e1; font-size:1.15rem; }
        .metadata { display:grid; grid-template-columns:minmax(110px,.35fr) 1fr; gap:.85rem 1rem; margin:1.7rem 0 0; padding:1.25rem; border:1px solid var(--border); border-radius:1rem; background:var(--card); }
        dt { color:var(--dim); font-weight:600; }
        dd { margin:0; overflow-wrap:anywhere; }
        .availability { color:#86efac; font-weight:700; }
        .availability.none { color:#cbd5e1; }
        .synopsis { margin-top:1.5rem; padding:1.25rem; border:1px solid var(--border); border-radius:1rem; background:var(--card); color:#cbd5e1; white-space:pre-line; }
        .synopsis h2 { margin:0 0 .6rem; color:#f8fafc; font-size:1.15rem; }
        .synopsis p { margin:0; }
        .back-bottom { display:inline-flex; min-height:44px; align-items:center; margin-top:1.5rem; color:#fdba74; font-weight:700; text-decoration:none; }
        @media(min-width:760px) { .book { grid-template-columns:minmax(250px,360px) 1fr; gap:3rem; } }
        @media(prefers-reduced-motion:reduce) { *,*::before,*::after { scroll-behavior:auto !important; transition:none !important; } }
    </style>
</head>
<body>
<header id="header" class="header">
    <div class="container">
        <nav class="nav" aria-label="Main navigation">
            <a href="{{ route('home') }}" class="logo">Kutub<span>io</span></a>
            <a class="back" href="{{ route('home') }}">Back to catalog</a>
        </nav>
    </div>
</header>
<main class="container">
    @php($coverUrl = $book->public_cover_url)
    <article class="book">
        <div class="cover">
            <img src="{{ $coverUrl ?: asset('images/book-cover-placeholder.svg') }}" alt="Book cover: {{ $book->title }}">
        </div>
        <div>
            <p class="eyebrow">Book details</p>
            <h1>{{ $book->title }}</h1>
            @if ($book->subtitle)
                <p class="subtitle">{{ $book->subtitle }}</p>
            @endif
            <dl class="metadata">
                <dt>ISBN</dt><dd>{{ $book->isbn13 ?: 'Not available' }}</dd>
                <dt>Author</dt><dd>{{ $book->authors_display ?: 'Not available' }}</dd>
                <dt>Category</dt><dd>{{ $book->category ? $book->category->code.' — '.$book->category->label : 'Uncategorized' }}</dd>
                <dt>Publisher</dt><dd>{{ $book->publisher ?: 'Not available' }}</dd>
                @if ($book->page_count)
                    <dt>Pages</dt><dd>{{ $book->page_count }}</dd>
                @endif
                <dt>Available copies</dt><dd class="availability {{ $book->available_copies_count === 0 ? 'none' : '' }}">{{ $book->available_copies_count }}</dd>
            </dl>
            @if ($book->synopsis)
                <section class="synopsis" aria-labelledby="synopsis-heading">
                    <h2 id="synopsis-heading">Synopsis</h2>
                    <p>{{ $book->synopsis }}</p>
                </section>
            @endif
            <a class="back-bottom" href="{{ route('home') }}">← Back to catalog</a>
        </div>
    </article>
</main>
</body>
</html>
