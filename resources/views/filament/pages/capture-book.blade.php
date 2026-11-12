<x-filament-panels::page>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/quagga/0.12.1/quagga.min.js" referrerpolicy="no-referrer"></script>

    <style>
        .capture-book-form {
            width: 100%;
            max-width: none;
        }

        .capture-book-form .capture-book-layout {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
            width: 100%;
        }

        .capture-book-form .capture-book-layout > section:first-child,
        .capture-book-form .capture-book-layout > section:last-child {
            min-width: 0;
            width: 100%;
        }

        .capture-book-form .capture-book-camera-stage {
            position: relative;
            width: 100%;
            min-height: 280px;
            aspect-ratio: 4 / 3;
            overflow: hidden;
            background: #030712;
        }

        .capture-book-form .capture-book-camera-stage #barcodeScanner,
        .capture-book-form .capture-book-camera-stage #barcodeScanner video {
            position: absolute;
            inset: 0;
            display: block;
            width: 100% !important;
            height: 100% !important;
            object-fit: contain !important;
        }

        @media (min-width: 1100px) {
            .capture-book-form .capture-book-layout {
                display: grid;
                grid-template-columns: minmax(280px, 0.8fr) minmax(0, 1.7fr);
                align-items: start;
                gap: 1.5rem;
            }

            .capture-book-form .capture-book-layout > section:first-child {
                position: sticky;
                top: 1.5rem;
            }

            .capture-book-form .capture-book-camera-stage {
                min-height: 320px;
                max-height: 65vh;
            }
        }
    </style>

    <form wire:submit="submit" class="capture-book-form">
        <div class="capture-book-layout">
            <section class="min-w-0 space-y-4">
                <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-gray-900">
                    <div class="mb-4 flex items-start justify-between gap-3">
                        <div>
                            <h2 class="text-sm font-bold uppercase tracking-wide text-gray-700 dark:text-gray-200">{{ $coverPanelVisible ? 'Book Cover' : 'ISBN Scanner' }}</h2>
                            <p id="scannerStatus" role="status" aria-live="polite" class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $coverPanelVisible ? 'Capture or replace this book cover.' : 'Starting camera…' }}</p>
                        </div>
                    </div>
                    <div id="captureViewport" class="capture-book-camera-stage rounded-xl" data-mode="{{ $coverPanelVisible ? 'cover' : 'scanner' }}">
                        <div id="barcodeScanner" class="absolute inset-0 overflow-hidden [&>video]:h-full [&>video]:w-full [&>video]:object-cover [&>canvas]:hidden"></div>
                        <div id="scannerLoading" class="pointer-events-none absolute inset-0 flex flex-col items-center justify-center gap-2 bg-gray-950 text-center text-sm text-gray-300">
                            <x-heroicon-m-camera class="h-9 w-9 text-gray-500" />
                            <span>Connecting to camera…</span>
                            <span class="text-xs text-gray-500">Allow camera access when prompted.</span>
                        </div>
                        <video id="coverCameraVideo" class="absolute inset-0 hidden h-full w-full object-contain" autoplay playsinline></video>
                        <canvas id="coverCaptureCanvas" class="hidden"></canvas>
                        <div id="coverPreview" class="absolute inset-0 flex items-center justify-center bg-gray-50 dark:bg-gray-900 {{ $coverPanelVisible ? '' : 'hidden' }}">
                            <img id="coverPreviewImage" src="{{ $coverDraftPreviewUrl ?? $matchedBookCoverUrl ?? asset('images/book-cover-placeholder.svg') }}" alt="Book cover preview" class="max-h-full w-full object-contain">
                            @if ($coverDraft)
                                <span class="absolute left-3 top-3 rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-900">Unsaved change</span>
                            @endif
                        </div>
                        <div id="scannerGuide" class="pointer-events-none absolute inset-0 flex items-center justify-center {{ $coverPanelVisible ? 'hidden' : '' }}">
                            <div class="relative h-24 w-[88%] rounded-lg border-2 border-amber-400/90 shadow-[0_0_0_999px_rgba(0,0,0,0.22)] sm:h-28">
                                <span class="absolute inset-x-0 top-1/2 border-t border-dashed border-amber-300/80"></span>
                            </div>
                        </div>
                    </div>
                    <div class="mt-4 mb-2 grid grid-cols-2 gap-2 {{ $coverPanelVisible ? '' : 'hidden' }}">
                        <button id="captureCoverButton" type="button" @disabled(! $coverPanelVisible) class="rounded-xl bg-primary-600 px-4 py-3 text-sm font-bold text-white hover:bg-primary-500 disabled:cursor-not-allowed disabled:opacity-50">{{ $coverDraft ? 'Retake Cover' : 'Capture Cover' }}</button>
                    <label for="coverFileInput" class="mt-2 mb-2 cursor-pointer rounded-xl border border-gray-300 px-4 py-3 text-center text-sm font-bold text-gray-700 dark:border-white/20 dark:text-gray-200">Choose File</label>
                    </div>
                    <input id="coverFileInput" type="file" accept="image/jpeg,image/png,image/webp" aria-label="Choose cover image" class="hidden" wire:model="coverDraft" @disabled(! $coverPanelVisible)>
                    <label id="coverFileName" for="coverFileInput" class="mt-2 hidden truncate text-center text-sm text-gray-600 dark:text-gray-300" aria-live="polite"></label>
                    @error('coverDraft')
                        <p class="mt-2 mb-2 text-xs font-semibold text-danger-600">{{ $message }}</p>
                    @enderror
                    <button id="restartBarcodeButton" type="button" wire:click="resetCapture" wire:loading.attr="disabled" wire:target="resetCapture" class="mt-2 w-full rounded-xl border border-primary-200 bg-primary-50 px-4 py-3 text-sm font-bold text-primary-700 hover:bg-primary-100 disabled:opacity-50 dark:border-primary-500/20 dark:bg-primary-500/10 dark:text-primary-300 {{ $coverPanelVisible ? '' : 'hidden' }}">Scan Another ISBN</button>
                    <button id="rescanBarcodeButton" type="button" class="mt-4 flex w-full items-center justify-center gap-2 rounded-xl border border-primary-200 bg-primary-50 px-4 py-3 text-sm font-bold text-primary-700 hover:bg-primary-100 dark:border-primary-500/20 dark:bg-primary-500/10 dark:text-primary-300 {{ $coverPanelVisible ? 'hidden' : '' }}">
                        <x-heroicon-m-arrow-path class="h-4 w-4" /> Restart / Rescan ISBN
                    </button>
                    <label for="isbnBarcodeInput" class="mb-2 mt-2 block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Or enter ISBN manually</label>
                    <input id="isbnBarcodeInput" type="text" inputmode="numeric" pattern="[0-9]*" wire:model.live="isbnBarcodeValue" class="mb-2 mt-2 block w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-center font-mono text-lg font-bold tracking-[0.18em] dark:border-white/10 dark:bg-gray-800 dark:text-white" placeholder="ISBN-13" maxlength="13">
                    @error('isbnBarcodeValue')
                        <p class="mt-1 text-xs font-semibold text-danger-600">Enter a valid ISBN-13.</p>
                    @enderror
                    <button type="button" wire:click="lookupIsbn" wire:loading.attr="disabled" class="mt-3 w-full rounded-xl bg-primary-600 px-4 py-3 text-sm font-bold text-white hover:bg-primary-500 disabled:opacity-50">
                        <span wire:loading.remove wire:target="lookupIsbn">Look Up ISBN</span>
                        <span wire:loading wire:target="lookupIsbn">Searching catalog and provider…</span>
                    </button>
                </div>
                <div class="rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm text-gray-600 dark:border-white/10 dark:bg-gray-900 dark:text-gray-300">
                    <span class="font-semibold">Lookup status:</span> {{ $metadataLookupMessage !== '' ? $metadataLookupMessage : 'Scan or enter an ISBN to search the local catalog and metadata provider.' }}
                </div>
            </section>

            <section class="min-w-0 space-y-5">
                <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-gray-900 sm:p-7">
                    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <h2 class="text-lg font-bold uppercase tracking-wide text-gray-800 dark:text-gray-100">Bibliographic Details</h2>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Review or enter the book details before saving.</p>
                        </div>
                        @if ($matchedBookId)
                            <span class="inline-flex items-center gap-1.5 rounded-full border border-emerald-300 bg-emerald-100 px-3 py-1.5 text-xs font-bold text-emerald-900 shadow-sm dark:border-emerald-700 dark:bg-emerald-950 dark:text-emerald-200">
                                <x-heroicon-m-check-circle class="h-4 w-4" aria-hidden="true" />
                                Existing catalog book
                            </span>
                        @elseif ($metadataLookupState === 'external_match')
                            <span class="inline-flex items-center gap-1.5 rounded-full border border-sky-300 bg-sky-100 px-3 py-1.5 text-xs font-bold text-sky-900 shadow-sm dark:border-sky-700 dark:bg-sky-950 dark:text-sky-200">
                                <x-heroicon-m-information-circle class="h-4 w-4" aria-hidden="true" />
                                Metadata found
                            </span>
                        @endif
                    </div>
                    <div class="grid gap-x-6 gap-y-5 md:grid-cols-2">
                        <label class="mt-2 block text-sm font-semibold text-gray-700 dark:text-gray-300">ISBN
                            <input type="text" wire:model="isbnBarcodeValue" class="mt-2 w-full rounded-lg border-gray-300 bg-gray-50 px-3.5 py-3 font-mono dark:border-white/10 dark:bg-gray-800 dark:text-white" placeholder="Optional ISBN-13">
                        </label>
                        <label class="mt-2 block text-sm font-semibold text-gray-700 dark:text-gray-300">Title <span class="text-danger-600">*</span>
                            <input type="text" wire:model="bookTitle" class="mt-2 w-full rounded-lg border-gray-300 px-3.5 py-3 dark:border-white/10 dark:bg-gray-800 dark:text-white" placeholder="Book title" required>
                            @error('bookTitle') <span class="mt-1 block text-xs text-danger-600">{{ $message }}</span> @enderror
                        </label>
                        <label class="m-2 block text-sm font-semibold text-gray-700 dark:text-gray-300">Subtitle
                            <input type="text" wire:model="bookSubtitle" class="mt-2 w-full rounded-lg border-gray-300 px-3.5 py-3 dark:border-white/10 dark:bg-gray-800 dark:text-white" placeholder="Optional subtitle">
                        </label>
                        <label class="mt-2 block text-sm font-semibold text-gray-700 dark:text-gray-300">Author(s) <span class="text-danger-600">*</span>
                            <input type="text" wire:model="bookAuthors" class="mt-2 w-full rounded-lg border-gray-300 px-3.5 py-3 dark:border-white/10 dark:bg-gray-800 dark:text-white" placeholder="Separate multiple authors with commas" required>
                            @error('bookAuthors') <span class="mt-1 block text-xs text-danger-600">{{ $message }}</span> @enderror
                        </label>
                        <label class="mt-2 block text-sm font-semibold text-gray-700 dark:text-gray-300">Author Name Style
                            <select wire:model="authorNameStyle" class="mt-2 w-full rounded-lg border-gray-300 px-3.5 py-3 dark:border-white/10 dark:bg-gray-800 dark:text-white">
                                <option value="local">Local author (first name)</option>
                                <option value="foreign">Foreign author (last name)</option>
                            </select>
                            @error('authorNameStyle') <span class="mt-1 block text-xs text-danger-600">{{ $message }}</span> @enderror
                        </label>
                        <label class="mt-2 block text-sm font-semibold text-gray-700 dark:text-gray-300">Category
                            <div class="relative mt-2" x-data="{ open: false }" @click.outside="open = false" @keydown.escape.window="open = false">
                                <button type="button" @click="open = ! open; if (open) $nextTick(() => $refs.categorySearch.focus())" aria-haspopup="listbox" aria-expanded="false" class="flex w-full items-center justify-between rounded-lg border border-gray-300 bg-white px-3.5 py-3 text-left font-normal dark:border-white/10 dark:bg-gray-800 dark:text-white">
                                    <span>{{ $categoryId ? (collect($this->categoryOptions)->firstWhere('id', $categoryId)['label'] ?? 'Select category') : 'Uncategorized' }}</span>
                                    <x-heroicon-m-chevron-down class="h-4 w-4 text-gray-500" />
                                </button>
                                <div x-cloak x-show="open" x-transition class="absolute z-50 mt-1 w-full rounded-lg border border-gray-200 bg-white shadow-lg dark:border-white/10 dark:bg-gray-800">
                                    <div class="border-b border-gray-200 p-2 dark:border-white/10">
                                        <input x-ref="categorySearch" type="search" wire:model.live.debounce.300ms="categorySearch" @click.stop placeholder="Search category name or DDC code" class="w-full rounded-md border-gray-300 px-3 py-2 text-sm dark:border-white/10 dark:bg-gray-900 dark:text-white">
                                    </div>
                                    <ul role="listbox" class="max-h-60 overflow-y-auto py-1">
                                        <li>
                                            <button type="button" wire:click="selectCategory(null)" @click="open = false" class="w-full px-3 py-2 text-left text-sm hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700">Uncategorized</button>
                                        </li>
                                        @forelse ($this->categoryOptions as $category)
                                            <li wire:key="capture-category-{{ $category['id'] }}">
                                                <button type="button" wire:click="selectCategory({{ $category['id'] }})" @click="open = false" class="w-full px-3 py-2 text-left text-sm hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700">{{ $category['label'] }}</button>
                                            </li>
                                        @empty
                                            <li class="px-3 py-2 text-sm text-gray-500">No matching categories.</li>
                                        @endforelse
                                    </ul>
                                </div>
                            </div>
                            @error('categoryId') <span class="mt-1 block text-xs text-danger-600">{{ $message }}</span> @enderror
                        </label>
                        <label class="mt-2 block text-sm font-semibold text-gray-700 dark:text-gray-300">Publisher
                            <input type="text" wire:model="bookPublisher" class="mt-2 w-full rounded-lg border-gray-300 px-3.5 py-3 dark:border-white/10 dark:bg-gray-800 dark:text-white" placeholder="Optional publisher">
                        </label>
                        <label class="mt-2 block text-sm font-semibold text-gray-700 dark:text-gray-300">Page Count
                            <input type="number" min="1" wire:model="bookPageCount" class="mt-2 w-full rounded-lg border-gray-300 px-3.5 py-3 dark:border-white/10 dark:bg-gray-800 dark:text-white" placeholder="Optional page count">
                        </label>
                    </div>
                </div>

                <div class="mt-2 mb-2 rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-gray-900 sm:p-7">
                    <div class="mb-5">
                        <h2 class="text-lg font-bold uppercase tracking-wide text-gray-800 dark:text-gray-100">Copy Intake</h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Set quantity and acquisition details for the copies being added.</p>
                    </div>
                    <div class="grid gap-5 md:grid-cols-3">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300">Quantity to Add</label>
                            <input type="number" wire:model.live.number="quantity" min="0" step="1" inputmode="numeric" class="mt-2 h-12 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-center text-lg font-bold dark:border-white/10 dark:bg-gray-800 dark:text-white">
                            <p class="mt-2 text-xs text-gray-500">Enter 0 to save the catalog book without creating a copy.</p>
                        </div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300">Funding Source
                            <select wire:model="fundingSource" class="mt-2 w-full rounded-lg border-gray-300 px-3.5 py-3 dark:border-white/10 dark:bg-gray-800 dark:text-white">
                                <option value="self">Self-Fund</option>
                                <option value="BOSP">BOSP (Gov-Fund)</option>
                            </select>
                        </label>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300">Year of Purchase
                            <select wire:model="purchaseYear" class="mt-2 w-full rounded-lg border-gray-300 px-3.5 py-3 dark:border-white/10 dark:bg-gray-800 dark:text-white">
                                @foreach (['Old Collection', '2023', '2024', '2025', '2026', '2027', '2028', '2029', '2030'] as $year)
                                    <option value="{{ $year }}">{{ $year }}</option>
                                @endforeach
                            </select>
                        </label>
                    </div>
                </div>

                <button type="submit" @disabled(blank($bookTitle)) class="flex w-full items-center justify-center gap-3 rounded-2xl bg-primary-600 px-6 py-4 text-sm font-bold text-white shadow-lg hover:bg-primary-500 disabled:cursor-not-allowed disabled:opacity-50">
                    <x-heroicon-m-check-circle class="h-5 w-5" />
                    {{ $matchedBookId ? 'Save Book and Add Copies' : 'Save Book' }}
                </button>
            </section>
        </div>
    </form>

    <script>
        (() => {
            let scannerRunning = false;
            let scannerStarting = false;
            let scannerFailed = false;
            let scannerInitAttempts = 0;
            let coverStream = null;
            let coverDraftUrl = null;
            const scannerElement = document.getElementById('barcodeScanner');
            const isbnInput = document.getElementById('isbnBarcodeInput');
            const scannerStatus = document.getElementById('scannerStatus');
            const scannerLoading = document.getElementById('scannerLoading');
            const coverPreview = document.getElementById('coverPreview');
            const coverPreviewImage = document.getElementById('coverPreviewImage');
            const coverVideo = document.getElementById('coverCameraVideo');
            const coverCanvas = document.getElementById('coverCaptureCanvas');
            const scannerGuide = document.getElementById('scannerGuide');
            const rescanButton = document.getElementById('rescanBarcodeButton');
            const restartButton = document.getElementById('restartBarcodeButton');
            const captureButton = document.getElementById('captureCoverButton');
            const coverFileInput = document.getElementById('coverFileInput');
            let panelMode = document.getElementById('captureViewport')?.dataset.mode ?? 'scanner';
            let pendingIsbn = null;

            const stopCoverCamera = () => {
                if (coverStream) {
                    coverStream.getTracks().forEach(track => track.stop());
                    coverStream = null;
                }
                if (coverVideo) coverVideo.srcObject = null;
                coverVideo?.classList.add('hidden');
            };

            const stopScanner = () => {
                if (scannerRunning && window.Quagga) {
                    Quagga.offDetected(onDetected);
                    Quagga.stop();
                    scannerRunning = false;
                }
                scannerStarting = false;
                scannerElement?.querySelectorAll('video').forEach(video => video.srcObject?.getTracks().forEach(track => track.stop()));
            };

            const showCoverPanel = () => {
                panelMode = 'cover';
                stopScanner();
                scannerLoading?.classList.add('hidden');
                scannerGuide?.classList.add('hidden');
                coverPreview?.classList.remove('hidden');
                rescanButton?.classList.add('hidden');
                restartButton?.classList.remove('hidden');
                if (scannerStatus) scannerStatus.textContent = 'Capture or replace this book cover.';
            };

            const stopCapture = () => {
                stopScanner();
                stopCoverCamera();
            };

            const setScannerUnavailable = message => {
                scannerStarting = false;
                scannerFailed = true;
                scannerLoading?.classList.remove('hidden');
                if (scannerLoading) {
                    scannerLoading.innerHTML = `<div class="flex flex-col items-center justify-center gap-2 px-6 text-center text-gray-300"><span class="text-sm font-semibold">Camera unavailable</span><span class="text-xs text-gray-400">${message}</span><span class="text-xs text-gray-500">Check browser permission and use HTTPS or localhost.</span></div>`;
                }
                if (scannerStatus) scannerStatus.textContent = message;
            };

            function onDetected(result) {
                const isbn = (result?.codeResult?.code ?? '').replace(/\D/g, '').slice(0, 13);
                if (isbn.length !== 13) return;
                if (panelMode === 'awaiting-cover') return;
                stopScanner();
                panelMode = 'awaiting-cover';
                scannerLoading?.classList.remove('hidden');
                if (scannerStatus) scannerStatus.textContent = 'ISBN found. Loading book and cover…';
                if (isbnInput) {
                    isbnInput.value = isbn;
                    isbnInput.dispatchEvent(new Event('input', { bubbles: true }));
                    isbnInput.dispatchEvent(new Event('change', { bubbles: true }));
                }
                pendingIsbn = isbn;
                window.Livewire?.dispatch('capture-isbn-scanned', { isbn });
            }

            window.startBarcodeScanner = async () => {
                if (!scannerElement || scannerRunning || scannerStarting || scannerFailed || panelMode === 'cover') return;
                if (!window.Quagga) {
                    scannerInitAttempts++;
                    if (scannerInitAttempts < 20) setTimeout(window.startBarcodeScanner, 250);
                    else setScannerUnavailable('Barcode scanner library did not load. Refresh the page or enter ISBN manually.');
                    return;
                }
                if (!navigator.mediaDevices?.getUserMedia) {
                    setScannerUnavailable('This browser does not provide camera access.');
                    return;
                }
                scannerStarting = true;
                scannerLoading?.classList.remove('hidden');
                if (scannerStatus) scannerStatus.textContent = 'Requesting camera permission…';
                try {
                    const permissionStream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: { ideal: 'environment' } } });
                    permissionStream.getTracks().forEach(track => track.stop());
                } catch (error) {
                    setScannerUnavailable(error.name === 'NotAllowedError' ? 'Camera permission was denied.' : `Could not access camera: ${error.message || error.name}`);
                    console.error('ISBN camera permission failed.', error);
                    return;
                }
                Quagga.init({
                    inputStream: {
                        name: 'ISBN camera scanner',
                        type: 'LiveStream',
                        target: scannerElement,
                        constraints: { facingMode: { ideal: 'environment' }, width: { ideal: 1280 }, height: { ideal: 720 } },
                    },
                    decoder: { readers: ['ean_reader'] },
                    locate: true,
                    frequency: 8,
                }, error => {
                    if (error) {
                        setScannerUnavailable(error.message || 'Could not initialize ISBN camera scanner.');
                        console.error('ISBN scanner initialization failed.', error);
                        return;
                    }
                    Quagga.onDetected(onDetected);
                    Quagga.start();
                    scannerRunning = true;
                    scannerFailed = false;
                    scannerLoading?.classList.add('hidden');
                    if (scannerStatus) scannerStatus.textContent = 'Camera active — point at an ISBN-13 barcode.';
                });
            };

            window.startCoverCamera = async () => {
                stopScanner();
                stopCoverCamera();
                if (!navigator.mediaDevices?.getUserMedia || !coverVideo) {
                    if (scannerStatus) scannerStatus.textContent = 'Camera unavailable. Choose an image file instead.';
                    return;
                }
                try {
                    coverStream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: { ideal: 'environment' }, width: { ideal: 1600 } }, audio: false });
                    coverVideo.srcObject = coverStream;
                    coverVideo.classList.remove('hidden');
                    coverPreview?.classList.add('hidden');
                    if (scannerStatus) scannerStatus.textContent = 'Camera active. Align the cover, then capture.';
                    if (captureButton) captureButton.textContent = 'Take Photo';
                } catch (error) {
                    stopCoverCamera();
                    coverPreview?.classList.remove('hidden');
                    if (scannerStatus) scannerStatus.textContent = error.name === 'NotAllowedError' ? 'Camera permission denied. Choose an image file instead.' : `Camera unavailable: ${error.message || error.name}`;
                }
            };

            window.captureCoverPhoto = () => {
                if (!coverVideo?.videoWidth || !coverVideo.videoHeight) return;
                coverCanvas.width = Math.min(coverVideo.videoWidth, 1600);
                coverCanvas.height = Math.round(coverVideo.videoHeight * (coverCanvas.width / coverVideo.videoWidth));
                coverCanvas.getContext('2d').drawImage(coverVideo, 0, 0, coverCanvas.width, coverCanvas.height);
                coverCanvas.toBlob(blob => {
                    if (!blob || !coverFileInput) return;
                    stopCoverCamera();
                    if (coverDraftUrl) URL.revokeObjectURL(coverDraftUrl);
                    const file = new File([blob], 'book-cover.jpg', { type: 'image/jpeg' });
                    const transfer = new DataTransfer();
                    transfer.items.add(file);
                    coverFileInput.files = transfer.files;
                    const coverFileName = document.getElementById('coverFileName');
                    if (coverFileName) {
                        coverFileName.textContent = file.name;
                        coverFileName.classList.remove('hidden');
                    }
                    coverDraftUrl = URL.createObjectURL(file);
                    coverPreviewImage.src = coverDraftUrl;
                    coverPreview?.classList.remove('hidden');
                    if (captureButton) captureButton.textContent = 'Retake Cover';
                    if (scannerStatus) scannerStatus.textContent = 'Cover captured. Save Book to apply it.';
                    coverFileInput.dispatchEvent(new Event('change', { bubbles: true }));
                }, 'image/jpeg', 0.85);
            };

            window.restartBarcodeScanner = () => {
                stopCapture();
                panelMode = 'scanner';
                pendingIsbn = null;
                scannerFailed = false;
                coverPreview?.classList.add('hidden');
                coverPreviewImage.src = @js(asset('images/book-cover-placeholder.svg'));
                scannerGuide?.classList.remove('hidden');
                scannerLoading?.classList.remove('hidden');
                restartButton?.classList.add('hidden');
                rescanButton?.classList.remove('hidden');
                if (coverFileInput) coverFileInput.value = '';
                const coverFileName = document.getElementById('coverFileName');
                if (coverFileName) {
                    coverFileName.textContent = '';
                    coverFileName.classList.add('hidden');
                }
                if (coverDraftUrl) URL.revokeObjectURL(coverDraftUrl);
                coverDraftUrl = null;
                if (captureButton) captureButton.textContent = 'Capture Cover';
                if (scannerStatus) scannerStatus.textContent = 'Starting camera…';
                window.startBarcodeScanner();
            };

            captureButton?.addEventListener('click', () => {
                if (coverStream) window.captureCoverPhoto();
                else window.startCoverCamera();
            });

            restartButton?.addEventListener('click', () => window.restartBarcodeScanner());
            rescanButton?.addEventListener('click', () => window.startBarcodeScanner());

            coverFileInput?.addEventListener('change', () => {
                stopCapture();
                panelMode = 'cover';
                const file = coverFileInput.files?.[0];
                if (file) {
                    if (coverDraftUrl) URL.revokeObjectURL(coverDraftUrl);
                    coverDraftUrl = URL.createObjectURL(file);
                    coverPreviewImage.src = coverDraftUrl;
                    const coverFileName = document.getElementById('coverFileName');
                    if (coverFileName) {
                        coverFileName.textContent = file.name;
                        coverFileName.classList.remove('hidden');
                    }
                }
                showCoverPanel();
                if (captureButton) captureButton.textContent = 'Retake Cover';
                if (scannerStatus) scannerStatus.textContent = 'Cover selected. Save Book to apply it.';
            });

            const initializeBarcodeScanner = () => {
                const viewport = document.getElementById('captureViewport');
                panelMode = viewport?.dataset.mode ?? 'scanner';
                if (panelMode === 'cover') {
                    showCoverPanel();
                    return;
                }
                scannerFailed = false;
                scannerLoading?.classList.remove('hidden');
                if (scannerStatus) scannerStatus.textContent = 'Starting camera…';
                window.startBarcodeScanner();
            };

            Livewire.on('cover-draft-updated', event => {
                if (panelMode !== 'cover') return;
                if (coverDraftUrl) return;
                const previewUrl = event.url ?? event[0]?.url;
                if (previewUrl) coverPreviewImage.src = previewUrl;
                showCoverPanel();
            });
            Livewire.on('cover-panel-ready', () => {
                const viewport = document.getElementById('captureViewport');
                if (viewport?.dataset.mode !== 'cover') return;
                if (pendingIsbn && isbnInput && isbnInput.value.replace(/\D/g, '') !== pendingIsbn) return;
                pendingIsbn = null;
                panelMode = 'cover';
                showCoverPanel();
            });
            Livewire.on('cover-panel-reset', () => {
                if (panelMode === 'scanner') return;
                window.restartBarcodeScanner();
            });
            document.addEventListener('livewire:navigating', stopCapture);
            document.addEventListener('livewire:navigated', () => setTimeout(initializeBarcodeScanner, 150));
            document.addEventListener('livewire:init', () => setTimeout(initializeBarcodeScanner, 150), { once: true });
            if (panelMode === 'cover') showCoverPanel();
            else setTimeout(initializeBarcodeScanner, 250);
        })();
    </script>
</x-filament-panels::page>
