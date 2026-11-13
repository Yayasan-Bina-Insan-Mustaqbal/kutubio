<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Jelajahi katalog buku Kutubio.">
    <title>Katalog Buku — Kutubio</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { color-scheme: dark; --bg:#020617; --card:#0f172a; --primary:#f97316; --primary-hover:#ea580c; --dim:#94a3b8; --border:rgba(255,255,255,.1); --gradient:linear-gradient(135deg,#f97316 0%,#d946ef 100%); font-family:'Plus Jakarta Sans',sans-serif; background:var(--bg); color:#f8fafc; }
        * { box-sizing:border-box; scroll-behavior:smooth; }
        body { margin:0; min-height:100vh; line-height:1.6; background:var(--bg); color:#f8fafc; overflow-x:hidden; }
        a { color:inherit; }
        .container { width:min(1200px,calc(100% - 2rem)); margin-inline:auto; }
        .header { position:sticky; top:0; z-index:100; padding:1rem 0; border-bottom:1px solid var(--border); background:rgba(2,6,23,.88); backdrop-filter:blur(18px); }
        .nav { display:flex; min-height:42px; align-items:center; justify-content:space-between; gap:1.5rem; }
        .logo { color:#fff; font-size:1.5rem; font-weight:800; letter-spacing:-.02em; text-decoration:none; }
        .logo span { background:var(--gradient); background-clip:text; -webkit-background-clip:text; color:transparent; }
        .nav-links { display:flex; align-items:center; gap:1.5rem; margin:0; padding:0; list-style:none; }
        .nav-links a { color:var(--dim); font-size:.9rem; text-decoration:none; }
        .nav-links a:hover,.nav-links a:focus-visible { color:#fff; }
        .btn { display:inline-flex; min-height:44px; align-items:center; justify-content:center; gap:.5rem; padding:.7rem 1.2rem; border:0; border-radius:.75rem; font:inherit; font-size:.9rem; font-weight:600; text-decoration:none; cursor:pointer; }
        .btn-primary { background:var(--primary); color:#fff; box-shadow:0 10px 18px rgba(249,115,22,.2); }
        .btn-primary:hover { background:var(--primary-hover); }
        .main { padding:3rem 0 5rem; }
        .catalog-hero { position:relative; margin-bottom:2rem; padding:2rem 0 1.4rem; }
        .catalog-hero::before { position:absolute; z-index:-1; top:-8rem; right:-8rem; width:25rem; height:25rem; border-radius:50%; background:rgba(249,115,22,.11); filter:blur(85px); content:''; }
        .eyebrow { margin:0 0 .45rem; color:#fb923c; font-size:.78rem; font-weight:800; letter-spacing:.14em; text-transform:uppercase; }
        h1 { margin:0; font-size:clamp(2rem,5vw,3.25rem); font-weight:800; letter-spacing:-.04em; line-height:1.1; }
        .intro { max-width:650px; margin:.75rem 0 0; color:var(--dim); }
        .filters { display:grid; grid-template-columns:1fr; gap:.85rem; margin:1.5rem 0 2rem; padding:1rem; border:1px solid var(--border); border-radius:1.2rem; background:rgba(15,23,42,.88); box-shadow:0 18px 50px rgba(0,0,0,.2); }
        .field { position:relative; display:grid; gap:.4rem; }
        .field label { color:#cbd5e1; font-size:.78rem; font-weight:700; }
        .control { width:100%; min-height:46px; border:1px solid rgba(148,163,184,.3); border-radius:.7rem; padding:.65rem .8rem; background:#020617; color:#f8fafc; font:inherit; }
        .control:focus-visible { outline:2px solid var(--primary); outline-offset:2px; }
        .search-field { position:relative; }
        .search-suggestions { position:absolute; z-index:30; top:calc(100% + .3rem); left:0; right:0; display:none; max-height:22rem; overflow:auto; border:1px solid var(--border); border-radius:.75rem; background:#0b1223; box-shadow:0 18px 42px rgba(0,0,0,.45); }
        .search-suggestions.is-open { display:block; }
        .suggestion { display:block; padding:.7rem .85rem; border-bottom:1px solid var(--border); color:#e2e8f0; text-decoration:none; }
        .suggestion:last-child { border-bottom:0; }
        .suggestion:hover,.suggestion:focus-visible { background:#172238; outline:none; }
        .suggestion-title { display:block; font-size:.9rem; font-weight:700; }
        .suggestion-isbn { display:block; margin-top:.15rem; color:var(--dim); font-size:.76rem; }
        .no-suggestions { padding:.8rem; color:var(--dim); font-size:.85rem; }
        .category-dropdown { position:relative; }
        .category-trigger { display:flex; align-items:center; justify-content:space-between; gap:.6rem; cursor:pointer; text-align:left; }
        .category-menu { position:absolute; z-index:40; top:calc(100% + .35rem); left:0; right:0; display:none; overflow:hidden; border:1px solid var(--border); border-radius:.75rem; background:#0b1223; box-shadow:0 18px 42px rgba(0,0,0,.45); }
        .category-menu.is-open { display:block; }
        .category-search-wrap { padding:.6rem; border-bottom:1px solid var(--border); }
        .category-options { max-height:16rem; overflow:auto; padding:.3rem; }
        .category-option { display:block; width:100%; padding:.6rem .7rem; border:0; border-radius:.45rem; background:transparent; color:#e2e8f0; cursor:pointer; font:inherit; text-align:left; }
        .category-option:hover,.category-option:focus-visible,.category-option[aria-selected="true"] { outline:none; background:#172238; color:#fdba74; }
        .check-field { display:flex; align-items:center; gap:.65rem; min-height:46px; color:#e2e8f0; font-size:.9rem; cursor:pointer; }
        .availability-toggle { position:absolute; width:1px; height:1px; overflow:hidden; clip:rect(0,0,0,0); white-space:nowrap; clip-path:inset(50%); }
        .availability-track { position:relative; display:inline-flex; width:2.65rem; height:1.5rem; flex:none; align-items:center; border:1px solid rgba(148,163,184,.4); border-radius:999px; background:#1e293b; transition:background .18s,border-color .18s; }
        .availability-track::before { position:absolute; left:.16rem; width:1.05rem; height:1.05rem; border-radius:50%; background:#cbd5e1; content:''; transition:transform .18s,background .18s; }
        .availability-toggle:checked + .availability-track { border-color:#f97316; background:#c2410c; }
        .availability-toggle:checked + .availability-track::before { transform:translateX(1.12rem); background:#fff; }
        .availability-toggle:focus-visible + .availability-track { outline:2px solid #fb923c; outline-offset:3px; }
        .filter-actions { display:flex; flex-wrap:wrap; align-items:end; gap:.6rem; }
        .filter-actions .btn { flex:1 1 7rem; border:0; cursor:pointer; }
        .filter-actions .check-field { flex:1 0 100%; }
        .reset-link { display:inline-flex; min-height:44px; align-items:center; justify-content:center; padding:0 .8rem; color:var(--dim); font-size:.88rem; text-decoration:none; white-space:nowrap; }
        .result-summary { margin:0 0 1rem; color:var(--dim); font-size:.9rem; }
        .grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:1rem; }
        .card { overflow:hidden; border:1px solid var(--border); border-radius:1rem; background:var(--card); text-decoration:none; transition:transform .2s,border-color .2s,box-shadow .2s; }
        .card:hover { transform:translateY(-4px); border-color:rgba(249,115,22,.48); box-shadow:0 20px 45px rgba(0,0,0,.32); }
        .card:focus-visible { outline:3px solid #fb923c; outline-offset:3px; }
        .cover { position:relative; display:grid; aspect-ratio:3/4; place-items:center; overflow:hidden; padding:.8rem; background:linear-gradient(145deg,#111c32,#080e1d); }
        .cover img { display:block; width:100%; height:100%; object-fit:contain; }
        .available-badge { position:absolute; right:.55rem; bottom:.55rem; border:1px solid rgba(134,239,172,.28); border-radius:999px; padding:.3rem .55rem; background:rgba(20,83,45,.9); color:#bbf7d0; font-size:.7rem; font-weight:700; }
        .unavailable-badge { border-color:rgba(203,213,225,.18); background:rgba(30,41,59,.94); color:#cbd5e1; }
        .info { padding:.85rem; }
        .title { display:-webkit-box; overflow:hidden; margin:0; color:#f8fafc; font-size:.98rem; font-weight:700; line-height:1.45; -webkit-box-orient:vertical; -webkit-line-clamp:3; }
        .isbn,.category { overflow-wrap:anywhere; margin:.5rem 0 0; color:var(--dim); font-size:.78rem; }
        .category { color:#fdba74; }
        .empty { padding:3rem 1rem; border:1px dashed rgba(148,163,184,.3); border-radius:1rem; background:var(--card); color:var(--dim); text-align:center; }
        .pagination { display:flex; flex-wrap:wrap; align-items:center; justify-content:center; gap:.75rem; margin-top:2rem; }
        .pagination a { border:1px solid var(--border); border-radius:.7rem; padding:.65rem .9rem; background:var(--card); color:#e2e8f0; text-decoration:none; }
        .pagination a:hover { border-color:var(--primary); }
        .pagination span { color:var(--dim); font-size:.88rem; }
        .footer { border-top:1px solid var(--border); padding:1.5rem 0; color:#64748b; font-size:.82rem; }
        @media(min-width:640px) { .grid { grid-template-columns:repeat(3,minmax(0,1fr)); gap:1.2rem; } .filters { grid-template-columns:2fr 1fr 1fr auto; align-items:end; padding:1.1rem; } .filter-actions .check-field { flex:0 0 auto; } .info { padding:1rem; } }
        @media(min-width:980px) { .grid { grid-template-columns:repeat(4,minmax(0,1fr)); gap:1.4rem; } }
        @media(max-width:680px) { .nav { flex-wrap:wrap; } .nav-links { order:3; width:100%; justify-content:space-between; gap:.75rem; } .nav-links a { font-size:.78rem; } .nav-cta .btn { min-height:40px; padding:.55rem .8rem; font-size:.8rem; } }
        @media(prefers-reduced-motion:reduce) { *,*::before,*::after { scroll-behavior:auto !important; transition:none !important; } }
    </style>
</head>
<body>
<header id="header" class="header">
    <div class="container">
        <nav class="nav" aria-label="Main navigation">
            <a href="{{ route('home') }}" class="logo">Kutub<span>io</span></a>
            <ul class="nav-links">
                <li><a href="#catalog">Catalog</a></li>
                <li><a href="#how-it-works">How it Works</a></li>
                <li><a href="{{ url('/admin') }}">Admin Panel</a></li>
            </ul>
            <div class="nav-cta">
                @auth
                    <a href="{{ url('/admin') }}" class="btn btn-primary">Dashboard</a>
                @else
                    <a href="{{ url('/admin/login') }}" class="btn btn-primary">Sign In</a>
                @endauth
            </div>
        </nav>
    </div>
</header>
<main id="catalog" class="main container">
    <section class="catalog-hero" aria-labelledby="catalog-title">
        <p class="eyebrow">Kutubio Library</p>
        <h1 id="catalog-title">Public Book Catalog</h1>
        <p class="intro">Jelajahi koleksi buku dan ketersediaan salinan di perpustakaan.</p>
    </section>

    <form id="catalog-filters" class="filters" method="GET" action="{{ route('home') }}" aria-label="Search and filter catalog">
        <div class="field search-field">
            <label for="catalog-search">Search books</label>
            <input id="catalog-search" class="control" type="search" name="q" value="{{ $filters['q'] }}" placeholder="Title, author, ISBN, publisher or category" autocomplete="off" role="combobox" aria-autocomplete="list" aria-controls="search-suggestions" aria-expanded="{{ $filters['q'] !== '' ? 'true' : 'false' }}">
            <div class="search-suggestions {{ $filters['q'] !== '' ? 'is-open' : '' }}" id="search-suggestions" role="listbox" aria-label="Book suggestions">
                @foreach ($suggestions as $suggestion)
                    <a class="suggestion" role="option" href="{{ route('catalog.books.show', $suggestion->public_id) }}">
                        <span class="suggestion-title">{{ $suggestion->title }}</span>
                        @if ($suggestion->isbn13)
                            <span class="suggestion-isbn">ISBN {{ $suggestion->isbn13 }}</span>
                        @endif
                    </a>
                @endforeach
                @if ($filters['q'] !== '' && $suggestions->isEmpty())
                    <div class="no-suggestions" role="status">No matching book suggestions.</div>
                @endif
            </div>
        </div>
        <div class="field category-dropdown" id="category-dropdown">
            <label for="category-trigger">Category</label>
            <button id="category-trigger" class="control category-trigger" type="button" aria-haspopup="listbox" aria-expanded="false">
                <span id="category-selected-label">
                    @if ($filters['category'])
                        {{ $categories->firstWhere('id', (int) $filters['category'])?->code }} — {{ $categories->firstWhere('id', (int) $filters['category'])?->label }}
                    @else
                        All categories
                    @endif
                </span>
                <span aria-hidden="true">⌄</span>
            </button>
            <div id="category-menu" class="category-menu">
                <div class="category-search-wrap">
                    <input id="category-search" class="control" type="search" placeholder="Search category name or DDC" autocomplete="off" aria-label="Search categories">
                </div>
                <div id="category-options" class="category-options" role="listbox" aria-label="Categories">
                    <button class="category-option" type="button" role="option" data-value="" data-label="All categories" aria-selected="{{ $filters['category'] === '' ? 'true' : 'false' }}">All categories</button>
                    @foreach ($categories as $category)
                        <button class="category-option" type="button" role="option" data-value="{{ $category->id }}" data-label="{{ $category->code }} — {{ $category->label }}" aria-selected="{{ (string) $filters['category'] === (string) $category->id ? 'true' : 'false' }}">{{ $category->code }} — {{ $category->label }}</button>
                    @endforeach
                </div>
            </div>
            <input id="category-value" type="hidden" name="category" value="{{ $filters['category'] }}">
        </div>
        <div class="field">
            <label for="catalog-sort">Sort by</label>
            <select id="catalog-sort" class="control" name="sort">
                @foreach ($sorts as $value => $label)
                    <option value="{{ $value }}" @selected($filters['sort'] === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="filter-actions">
            <label class="check-field" for="catalog-available">
                <input id="catalog-available" class="availability-toggle" type="checkbox" name="available" value="1" @checked($filters['available'])>
                <span class="availability-track" aria-hidden="true"></span>
                <span>Available now</span>
            </label>
            <button class="btn btn-primary" type="submit">Search</button>
            @if ($filters['q'] || $filters['category'] || $filters['available'] || $filters['sort'] !== 'title_asc')
                <a class="reset-link" href="{{ route('home') }}">Reset</a>
            @endif
        </div>
    </form>

    <section aria-label="Catalog results">
        <div id="catalog-results" aria-live="polite" aria-busy="false">
            @include('catalog.partials.results')
        </div>
    </section>
    <section id="how-it-works" class="catalog-hero" aria-labelledby="how-heading">
        <p class="eyebrow">Browse freely</p>
        <h2 id="how-heading">Find your next book</h2>
        <p class="intro">Search the public catalog by title, author, ISBN, publisher, or category. Availability counts include copies marked available only.</p>
    </section>
</main>
<footer class="footer"><div class="container">© {{ date('Y') }} Kutubio — Library catalog</div></footer>
<script>
(() => {
    const searchInput = document.getElementById('catalog-search');
    const suggestionList = document.getElementById('search-suggestions');
    const results = document.getElementById('catalog-results');
    const categoryRoot = document.getElementById('category-dropdown');
    const categoryTrigger = document.getElementById('category-trigger');
    const categoryMenu = document.getElementById('category-menu');
    const categorySearch = document.getElementById('category-search');
    const categoryValue = document.getElementById('category-value');
    const categoryLabel = document.getElementById('category-selected-label');
    const categoryOptions = [...document.querySelectorAll('.category-option')];
    const form = document.getElementById('catalog-filters');

    if (!form || !results || !searchInput || !suggestionList) return;

    let debounceTimer = null;
    let pendingTimer = null;
    let activeIndex = -1;
    let requestSequence = 0;

    const suggestionOptions = () => [...suggestionList.querySelectorAll('[role="option"]')];
    const closeSuggestions = () => {
        suggestionList.classList.remove('is-open');
        searchInput.setAttribute('aria-expanded', 'false');
        activeIndex = -1;
    };
    const openSuggestions = () => {
        if (suggestionOptions().length || suggestionList.querySelector('[role="status"]')) {
            suggestionList.classList.add('is-open');
            searchInput.setAttribute('aria-expanded', 'true');
        } else closeSuggestions();
    };
    const bindPagination = () => {
        results.querySelectorAll('.pagination a').forEach(link => {
            link.addEventListener('click', event => {
                event.preventDefault();
                const pageUrl = new URL(link.href);
                refreshCatalog({ immediate: true, page: Number(pageUrl.searchParams.get('page') ?? 1) });
            }, { once: true });
        });
    };
    const refreshCatalog = ({ immediate = false, page = 1, updateUrl = true } = {}) => {
        if (pendingTimer !== null) clearTimeout(pendingTimer);
        const currentSequence = ++requestSequence;
        const data = new FormData(form);
        data.set('fragment', '1');
        if (page > 1) data.set('page', String(page));
        else data.delete('page');

        const requestUrl = new URL(form.action, window.location.origin);
        data.forEach((value, key) => {
            if (value !== '') requestUrl.searchParams.set(key, value);
        });

        results.setAttribute('aria-busy', 'true');
        pendingTimer = window.setTimeout(async () => {
            try {
                const response = await fetch(requestUrl, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        Accept: 'application/json',
                    },
                });
                if (!response.ok) throw new Error('Catalog request failed');

                const payload = await response.json();
                if (currentSequence !== requestSequence) return;

                results.innerHTML = payload.results;
                results.setAttribute('aria-busy', 'false');
                requestUrl.searchParams.delete('fragment');
                if (updateUrl) window.history.replaceState({}, '', requestUrl);
                renderSuggestions(payload.suggestions ?? []);
                bindPagination();
            } catch {
                if (currentSequence === requestSequence) {
                    results.setAttribute('aria-busy', 'false');
                    closeSuggestions();
                }
            }
        }, immediate ? 0 : 320);
    };
    const applyCatalogFilters = () => refreshCatalog({ immediate: true });

    searchInput.addEventListener('input', () => {
        if (debounceTimer !== null) clearTimeout(debounceTimer);
        closeSuggestions();
        debounceTimer = window.setTimeout(() => refreshCatalog(), 320);
    });
    searchInput.addEventListener('focus', openSuggestions);
    searchInput.addEventListener('keydown', event => {
        const options = suggestionOptions();
        if (event.key === 'Escape') closeSuggestions();
        if (event.key === 'ArrowDown' && options.length > 0) {
            event.preventDefault();
            activeIndex = (activeIndex + 1) % options.length;
            options[activeIndex].focus();
        }
    });
    document.addEventListener('click', event => {
        if (!suggestionList.contains(event.target) && event.target !== searchInput) closeSuggestions();
    });
    form.addEventListener('submit', event => {
        event.preventDefault();
        if (debounceTimer !== null) clearTimeout(debounceTimer);
        applyCatalogFilters();
    });
    document.getElementById('catalog-sort')?.addEventListener('change', applyCatalogFilters);
    document.getElementById('catalog-available')?.addEventListener('change', applyCatalogFilters);

    if (categoryRoot && categoryTrigger && categoryMenu && categorySearch && categoryValue && categoryLabel) {
        const visibleCategoryOptions = () => categoryOptions.filter(option => !option.hidden);
        const closeCategoryMenu = () => {
            categoryMenu.classList.remove('is-open');
            categoryTrigger.setAttribute('aria-expanded', 'false');
        };
        const filterCategories = () => {
            const query = categorySearch.value.trim().toLocaleLowerCase();
            categoryOptions.forEach(option => {
                option.hidden = !option.dataset.label.toLocaleLowerCase().includes(query);
            });
            visibleCategoryOptions()[0]?.scrollIntoView({ block: 'nearest' });
        };
        const openCategoryMenu = () => {
            categoryMenu.classList.add('is-open');
            categoryTrigger.setAttribute('aria-expanded', 'true');
            categorySearch.value = '';
            filterCategories();
            categorySearch.focus();
        };

        categoryTrigger.addEventListener('click', () => {
            if (categoryMenu.classList.contains('is-open')) closeCategoryMenu();
            else openCategoryMenu();
        });
        categorySearch.addEventListener('input', filterCategories);
        categorySearch.addEventListener('keydown', event => {
            if (event.key === 'Escape') {
                closeCategoryMenu();
                categoryTrigger.focus();
            }
            if (event.key === 'ArrowDown') {
                event.preventDefault();
                visibleCategoryOptions()[0]?.focus();
            }
        });
        categoryOptions.forEach(option => {
            option.addEventListener('click', () => {
                categoryValue.value = option.dataset.value;
                categoryLabel.textContent = option.dataset.label;
                categoryOptions.forEach(item => item.setAttribute('aria-selected', item === option ? 'true' : 'false'));
                closeCategoryMenu();
                categoryTrigger.focus();
                applyCatalogFilters();
            });
            option.addEventListener('keydown', event => {
                const options = visibleCategoryOptions();
                const index = options.indexOf(option);
                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    option.click();
                }
                if ((event.key === 'ArrowDown' || event.key === 'ArrowUp') && options.length > 0) {
                    event.preventDefault();
                    const offset = event.key === 'ArrowDown' ? 1 : -1;
                    options[(index + offset + options.length) % options.length].focus();
                }
                if (event.key === 'Escape') {
                    closeCategoryMenu();
                    categoryTrigger.focus();
                }
            });
        });
        document.addEventListener('click', event => {
            if (!categoryRoot.contains(event.target)) closeCategoryMenu();
        });
    }

    bindPagination();
})();
</script>