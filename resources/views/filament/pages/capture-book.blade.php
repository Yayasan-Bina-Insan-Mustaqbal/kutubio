<x-filament-panels::page>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/quagga/0.12.1/quagga.min.js" referrerpolicy="no-referrer"></script>
    @vite(['resources/js/app.js'])
    
    <form wire:submit="submit" class="space-y-6">
        <textarea id="frontImageData" wire:model.live="frontImageData" class="hidden"></textarea>
        <input id="frontImageWidth" wire:model.live="frontImageWidth" type="hidden">
        <input id="frontImageHeight" wire:model.live="frontImageHeight" type="hidden">
        <input id="isbnBarcodeValue" wire:model.live="isbnBarcodeValue" type="hidden">
        <input id="frontOcrTitle" wire:model.live="frontOcrTitle" type="hidden">
        <input id="frontOcrSubtitle" wire:model.live="frontOcrSubtitle" type="hidden">
        <input id="frontOcrAuthors" wire:model.live="frontOcrAuthors" type="hidden">
        <input id="frontOcrPublisher" wire:model.live="frontOcrPublisher" type="hidden">
        <input id="frontOcrText" wire:model.live="frontOcrText" type="hidden">
        <input id="frontOcrConfidence" wire:model.live="frontOcrConfidence" type="hidden">

        <div class="grid gap-4 lg:grid-cols-[minmax(0,1.35fr)_minmax(320px,0.65fr)]">
            <!-- Left Side: Camera Viewport -->
            <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-white/10 dark:bg-gray-900">
                <div class="relative aspect-square w-full bg-black overflow-hidden">
                    <!-- Main Video for Front Capture -->
                    <video id="captureVideo" autoplay playsinline muted class="h-full w-full object-cover"></video>
                    
                    <!-- Quagga Scanner Container (Hidden by default) -->
                    <div id="barcodeScanner" class="hidden h-full w-full object-cover [&>video]:h-full [&>video]:w-full [&>video]:object-cover [&>canvas]:absolute [&>canvas]:inset-0 [&>canvas]:h-full [&>canvas]:w-full"></div>
                    
                    <canvas id="mathCanvas" class="hidden"></canvas>

                    <!-- Professional Overlay Guides -->
                    <div class="absolute inset-0 pointer-events-none border-[16px] border-black/10"></div>
                    
                    <!-- Scanner Overlay for Barcode mode -->
                    <div id="scannerOverlay" class="absolute inset-x-8 top-1/3 bottom-1/3 border-2 border-dashed border-primary-500/50 transition-opacity duration-300 opacity-0 flex items-center justify-center">
                        <div class="text-primary-500 font-mono text-[10px] uppercase tracking-widest bg-black/60 backdrop-blur-sm px-3 py-1.5 rounded-full border border-primary-500/30">
                            Align Barcode
                        </div>
                    </div>

                    <!-- Stability Progress Bar -->
                    <div id="stabilityContainer" class="absolute inset-x-12 top-1/2 -translate-y-1/2 hidden flex-col items-center gap-3">
                        <div class="w-full h-1.5 bg-black/40 rounded-full overflow-hidden backdrop-blur-md border border-white/10">
                            <div id="stabilityBar" class="h-full bg-primary-500 transition-all duration-100 ease-out shadow-[0_0_10px_rgba(var(--primary-500),0.5)]" style="width: 0%"></div>
                        </div>
                        <span id="timerVal" class="text-[10px] font-bold text-white uppercase tracking-widest drop-shadow-md">Stabilizing...</span>
                    </div>

                    <!-- Bottom Status Bar -->
                    <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/90 via-black/40 to-transparent p-5">
                        <div class="flex items-center justify-between">
                            <div id="statusIndicator" class="flex items-center gap-2.5">
                                <div class="h-2 w-2 rounded-full bg-blue-500 shadow-[0_0_8px_rgba(59,130,246,0.6)]"></div>
                                <span class="text-[10px] font-bold text-white uppercase tracking-widest">Ready: Front</span>
                                <span id="motionVal" class="ml-2 text-[9px] font-medium text-white/40 font-mono"></span>
                            </div>
                            <div class="flex gap-2">
                                <button type="button" id="switchCameraBtn" class="rounded-full bg-white/10 backdrop-blur-md p-2.5 text-white hover:bg-white/20 transition-colors border border-white/5">
                                    <x-heroicon-m-arrow-path class="h-5 w-5" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <aside class="space-y-4" x-data="tokenSelector">
                <!-- Book Title Section -->
                <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-gray-900">
                    <label class="mb-3 block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        Book Title Extraction
                    </label>

                    <div class="space-y-4">
                        <!-- Token Sentence Container -->
                        <div class="flex min-h-[100px] flex-wrap content-start gap-1.5 rounded-xl border border-gray-100 bg-gray-50/50 p-4 text-base leading-relaxed dark:border-white/10 dark:bg-gray-800/50">
                            <template x-if="! $wire.ocrTokens || $wire.ocrTokens.length === 0">
                                <div class="flex flex-col items-center justify-center w-full py-4 text-center">
                                    <x-heroicon-m-camera class="h-8 w-8 text-gray-300 dark:text-gray-600 mb-2" />
                                    <span class="text-xs italic text-gray-400">Capture front cover...</span>
                                </div>
                            </template>
                            <template x-for="(token, index) in $wire.ocrTokens" :key="'title-'+index">
                                <span 
                                    x-text="token"
                                    x-on:click="toggleTitleToken(index)"
                                    class="cursor-pointer select-none rounded-lg px-2 py-1 text-sm font-medium transition-all duration-200"
                                    :class="isTitleSelected(index) ? 'bg-primary-500 text-white shadow-lg shadow-primary-500/30 scale-105' : 'bg-white border border-gray-100 text-gray-700 hover:border-primary-300 dark:bg-gray-800 dark:border-white/5 dark:text-gray-300 dark:hover:bg-gray-700'"
                                ></span>
                            </template>
                        </div>

                        <!-- Selected Title Preview -->
                        <div class="rounded-xl border border-primary-100 bg-primary-50/30 p-4 dark:border-primary-900/20 dark:bg-primary-900/5">
                            <p class="mb-1 text-[10px] font-bold uppercase tracking-widest text-primary-600 dark:text-primary-400">Selected Title</p>
                            <p class="text-sm font-semibold text-gray-900 dark:text-white" :class="!$wire.bookTitle && 'italic font-normal text-gray-400'" x-text="$wire.bookTitle || 'Select title words...'"></p>
                        </div>
                    </div>
                </div>

                <!-- Book Author Section -->
                <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-gray-900">
                    <label class="mb-3 block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        Author Name Extraction
                    </label>

                    <div class="space-y-4">
                        <!-- Token Sentence Container -->
                        <div class="flex min-h-[100px] flex-wrap content-start gap-1.5 rounded-xl border border-gray-100 bg-gray-50/50 p-4 text-base leading-relaxed dark:border-white/10 dark:bg-gray-800/50">
                            <template x-if="! $wire.ocrTokens || $wire.ocrTokens.length === 0">
                                <div class="flex flex-col items-center justify-center w-full py-4 text-center">
                                    <x-heroicon-m-user class="h-8 w-8 text-gray-300 dark:text-gray-600 mb-2" />
                                    <span class="text-xs italic text-gray-400">Capture front cover...</span>
                                </div>
                            </template>
                            <template x-for="(token, index) in $wire.ocrTokens" :key="'author-'+index">
                                <span 
                                    x-text="token"
                                    x-on:click="toggleAuthorToken(index)"
                                    class="cursor-pointer select-none rounded-lg px-2 py-1 text-sm font-medium transition-all duration-200"
                                    :class="isAuthorSelected(index) ? 'bg-primary-500 text-white shadow-lg shadow-primary-500/30 scale-105' : 'bg-white border border-gray-100 text-gray-700 hover:border-primary-300 dark:bg-gray-800 dark:border-white/5 dark:text-gray-300 dark:hover:bg-gray-700'"
                                ></span>
                            </template>
                        </div>

                        <!-- Selected Author Preview -->
                        <div class="rounded-xl border border-primary-100 bg-primary-50/30 p-4 dark:border-primary-900/20 dark:bg-primary-900/5">
                            <p class="mb-1 text-[10px] font-bold uppercase tracking-widest text-primary-600 dark:text-primary-400">Selected Author</p>
                            <p class="text-sm font-semibold text-gray-900 dark:text-white" :class="!$wire.bookAuthors && 'italic font-normal text-gray-400'" x-text="$wire.bookAuthors || 'Select author words...'"></p>
                        </div>

                        <button 
                            type="button" 
                            wire:click="resetCapture" 
                            class="flex w-full items-center justify-center gap-2 rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-xs font-bold text-gray-700 shadow-sm hover:bg-gray-50 transition-all dark:border-white/10 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
                        >
                            <x-heroicon-m-camera class="h-4 w-4" />
                            <span>Re-capture Front Cover</span>
                        </button>
                    </div>
                </div>

                <!-- ISBN Section -->
                <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-gray-900">
                    <div class="flex items-center justify-between mb-3">
                        <label for="isbnBarcodeInput" class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            ISBN Barcode
                        </label>
                        <button type="button" x-on:click="$wire.isbnBarcodeValue = ''; document.getElementById('isbnBarcodeInput').value = '';" class="text-[10px] font-bold uppercase tracking-wider text-gray-400 hover:text-danger-500 transition-colors">
                            Clear
                        </button>
                    </div>

                    <div class="space-y-3">
                        <button 
                            type="button" 
                            x-on:click="window.startBarcodeScanner()"
                            class="flex w-full items-center justify-center gap-2 rounded-xl bg-primary-50 px-4 py-3 text-xs font-bold text-primary-700 shadow-sm hover:bg-primary-100 transition-all dark:bg-primary-500/10 dark:text-primary-400 dark:hover:bg-primary-500/20"
                            x-show="! $wire.isbnBarcodeValue"
                        >
                            <x-heroicon-m-qr-code class="h-4 w-4" />
                            Start Scanning ISBN
                        </button>

                        <input
                            id="isbnBarcodeInput"
                            type="text"
                            inputmode="numeric"
                            pattern="[0-9]*"
                            wire:model.live="isbnBarcodeValue"
                            class="block w-full rounded-xl border border-gray-200 bg-gray-50 font-mono text-lg font-bold tracking-[0.2em] shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-gray-800 dark:text-white text-center"
                            placeholder="SCAN OR ENTER ISBN"
                            maxlength="13"
                        >
                        @error('isbnBarcodeValue')
                            <p class="text-[10px] font-bold text-danger-600 dark:text-danger-400 uppercase tracking-wider">Invalid ISBN format</p>
                        @enderror
                    </div>
                </div>

                <!-- Quantity Section -->
                <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-gray-900">
                    <label for="quantity" class="mb-3 block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        Quantity
                    </label>
                    <div class="flex items-center gap-4">
                        <button type="button" x-on:click="$wire.quantity = Math.max(1, $wire.quantity - 1)" class="flex h-12 w-12 items-center justify-center rounded-xl border border-gray-200 bg-white text-gray-700 shadow-sm hover:bg-gray-50 transition-all dark:border-white/10 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700">
                            <x-heroicon-m-minus class="h-6 w-6" />
                        </button>
                        <input type="number" id="quantity" wire:model.live="quantity" class="block w-full rounded-xl border border-gray-200 bg-gray-50 text-center text-xl font-bold dark:border-white/10 dark:bg-gray-800 dark:text-white" min="1">
                        <button type="button" x-on:click="$wire.quantity = $wire.quantity + 1" class="flex h-12 w-12 items-center justify-center rounded-xl border border-gray-200 bg-white text-gray-700 shadow-sm hover:bg-gray-50 transition-all dark:border-white/10 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700">
                            <x-heroicon-m-plus class="h-6 w-6" />
                        </button>
                    </div>
                </div>

                <!-- Funding Source Section -->
                <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-gray-900"
                     x-data="{ 
                         source: (localStorage.getItem('kutubio_last_funding_source') === 'BOS' ? 'BOSP' : localStorage.getItem('kutubio_last_funding_source')) || 'self',
                         init() {
                             this.$wire.set('fundingSource', this.source);
                             this.$watch('source', value => {
                                 localStorage.setItem('kutubio_last_funding_source', value);
                                 this.$wire.set('fundingSource', value);
                             });
                         }
                     }">
                    <label class="mb-3 block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        Funding Source
                    </label>
                    <div class="relative flex rounded-xl bg-gray-100 p-1 dark:bg-gray-800">
                        <!-- Slide Pill Indicator -->
                        <div class="absolute bottom-1 top-1 left-1 w-[calc(50%-4px)] rounded-lg bg-white shadow-sm transition-all duration-300 ease-in-out dark:bg-gray-700"
                             :class="source === 'BOSP' ? 'translate-x-full' : ''"></div>
                             
                        <button type="button" 
                                @click="source = 'self'"
                                class="relative z-10 w-1/2 py-2 text-center text-xs font-bold transition-colors duration-200"
                                :class="source === 'self' ? 'text-primary-600 dark:text-primary-400' : 'text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white'">
                            Self-Fund
                        </button>
                        
                        <button type="button" 
                                @click="source = 'BOSP'"
                                class="relative z-10 w-1/2 py-2 text-center text-xs font-bold transition-colors duration-200"
                                :class="source === 'BOSP' ? 'text-primary-600 dark:text-primary-400' : 'text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white'">
                            BOSP (Gov-Fund)
                        </button>
                    </div>
                </div>

                <!-- Year of Purchase Section -->
                <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-gray-900"
                     x-data="{
                         selectedYear: localStorage.getItem('kutubio_last_purchase_year') || 'Old Collection',
                         init() {
                             this.$wire.set('purchaseYear', this.selectedYear);
                             this.$watch('selectedYear', value => {
                                 localStorage.setItem('kutubio_last_purchase_year', value);
                                 this.$wire.set('purchaseYear', value);
                             });
                         }
                     }">
                    <label class="mb-3 block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        Year of Purchase
                    </label>
                    
                    <div class="grid grid-cols-3 gap-2">
                        <template x-for="year in ['Old Collection', '2023', '2024', '2025', '2026', '2027', '2028', '2029', '20230']" :key="year">
                            <button type="button"
                                    @click="selectedYear = year"
                                    class="rounded-xl border py-2.5 text-center text-xs font-bold transition-all duration-200"
                                    :class="selectedYear === year 
                                        ? 'border-primary-500 bg-primary-50 text-primary-700 shadow-sm dark:bg-primary-500/10 dark:text-primary-400 dark:border-primary-500/30' 
                                        : 'border-gray-200 bg-white text-gray-600 hover:border-gray-300 hover:bg-gray-50 dark:border-white/10 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700'">
                                <span x-text="year"></span>
                            </button>
                        </template>
                    </div>
                </div>

                <!-- Submit Section -->
                <button 
                    type="submit" 
                    id="submitBtn" 
                    x-bind:disabled="! ($wire.frontImageData && $wire.bookTitle)"
                    class="group relative flex w-full items-center justify-center gap-3 overflow-hidden rounded-2xl bg-primary-600 px-6 py-5 text-sm font-bold text-white shadow-xl shadow-primary-500/25 transition-all hover:bg-primary-500 disabled:opacity-50 disabled:grayscale disabled:shadow-none"
                >
                    <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/10 to-transparent -translate-x-full group-hover:translate-x-full transition-transform duration-1000"></div>
                    <x-heroicon-m-check-circle class="h-5 w-5" />
                    SUBMIT CAPTURE
                </button>

                <!-- Help Section -->
                <div class="rounded-xl bg-gray-50 p-4 dark:bg-white/5 border border-gray-100 dark:border-white/5">
                    <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400 dark:text-gray-500 mb-2">Instructions</p>
                    <ul class="space-y-1.5 text-[10px] font-medium text-gray-500 dark:text-gray-400">
                        <li class="flex items-start gap-2">
                            <span class="flex-shrink-0 h-3.5 w-3.5 rounded-full bg-primary-500/10 text-primary-500 flex items-center justify-center text-[8px] font-bold">1</span>
                            <span>Align the <strong>front cover</strong> for auto-capture.</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="flex-shrink-0 h-3.5 w-3.5 rounded-full bg-primary-500/10 text-primary-500 flex items-center justify-center text-[8px] font-bold">2</span>
                            <span>Pick the <strong>book title</strong> from extracted text.</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="flex-shrink-0 h-3.5 w-3.5 rounded-full bg-primary-500/10 text-primary-500 flex items-center justify-center text-[8px] font-bold">3</span>
                            <span>Scan the <strong>ISBN barcode</strong>.</span>
                        </li>
                    </ul>
                </div>
            </aside>
        </div>
    </form>

    <canvas id="snapshotCanvas" class="hidden"></canvas>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('tokenSelector', () => ({
                selectedTitleTokens: [],
                selectedAuthorTokens: [],
                
                init() {
                    this.$watch('$wire.ocrTokens', (tokens) => {
                        this.selectedTitleTokens = [];
                        this.selectedAuthorTokens = [];
                        this.$wire.bookTitle = '';
                        this.$wire.bookAuthors = '';
                        
                        // Auto-reset if OCR failed but we have image
                        if (this.$wire.frontImageData && (!tokens || tokens.length === 0)) {
                            this.$wire.resetCapture();
                        }

                        if (window.refreshActions) window.refreshActions();
                    });
                },

                toggleTitleToken(index) {
                    if (this.selectedTitleTokens.includes(index)) {
                        this.selectedTitleTokens = this.selectedTitleTokens.filter(i => i !== index);
                    } else {
                        this.selectedTitleTokens.push(index);
                    }
                    this.updateTitle();
                },

                toggleAuthorToken(index) {
                    if (this.selectedAuthorTokens.includes(index)) {
                        this.selectedAuthorTokens = this.selectedAuthorTokens.filter(i => i !== index);
                    } else {
                        this.selectedAuthorTokens.push(index);
                    }
                    this.updateAuthors();
                },

                isTitleSelected(index) {
                    return this.selectedTitleTokens.includes(index);
                },

                isAuthorSelected(index) {
                    return this.selectedAuthorTokens.includes(index);
                },

                updateTitle() {
                    const sortedIndices = [...this.selectedTitleTokens].sort((a, b) => a - b);
                    this.$wire.bookTitle = sortedIndices.map(i => this.$wire.ocrTokens[i]).join(' ');
                    if (window.refreshActions) window.refreshActions();
                },

                updateAuthors() {
                    const sortedIndices = [...this.selectedAuthorTokens].sort((a, b) => a - b);
                    this.$wire.bookAuthors = sortedIndices.map(i => this.$wire.ocrTokens[i]).join(' ');
                    if (window.refreshActions) window.refreshActions();
                }
            }));
        });

        (function() {
            if (window.kutubioCapturePageInitialized) return;
            window.kutubioCapturePageInitialized = true;

            const video = document.getElementById('captureVideo');
            const barcodeScanner = document.getElementById('barcodeScanner');
            const mathCanvas = document.getElementById('mathCanvas');
            const mathContext = mathCanvas.getContext('2d', { willReadFrequently: true });
            const snapshotCanvas = document.getElementById('snapshotCanvas');
            const statusIndicator = document.getElementById('statusIndicator');
            const scannerOverlay = document.getElementById('scannerOverlay');
            const submitBtn = document.getElementById('submitBtn');
            const switchCameraBtn = document.getElementById('switchCameraBtn');
            const stabilityContainer = document.getElementById('stabilityContainer');
            const stabilityBar = document.getElementById('stabilityBar');
            const timerVal = document.getElementById('timerVal');
            const motionVal = document.getElementById('motionVal');
            const isbnBarcodeInput = document.getElementById('isbnBarcodeInput');

            let lastPixels = null;
            let stableFrames = 0;
            let state = 'IDLE'; // IDLE -> MOVING -> STABILIZING -> CAPTURED
            const FRAMES_TO_STABILIZE = 45; 
            let loopId = null;
            let stream = null;
            let activeSide = 'front';
            let quaggaStarted = false;
            let currentFacingMode = 'environment';

            const motionThresholdHigh = 22;
            const motionThresholdLow = 8;

            const updateStatus = (state) => {
                const indicator = document.getElementById('statusIndicator');
                if (!indicator) return;

                const dot = indicator.querySelector('div');
                const label = indicator.querySelector('span');
                if (!dot || !label) return;

                switch(state) {
                    case 'IDLE':
                        dot.className = 'h-2 w-2 rounded-full bg-blue-500 shadow-[0_0_8px_rgba(59,130,246,0.6)]';
                        label.innerText = activeSide === 'isbn' ? 'SCAN ISBN BARCODE' : 'READY: FRONT';
                        scannerOverlay.classList.toggle('opacity-0', activeSide !== 'isbn');
                        break;
                    case 'STABILIZING':
                        dot.className = 'h-2 w-2 rounded-full bg-yellow-500 animate-pulse shadow-[0_0_8px_rgba(234,179,8,0.6)]';
                        label.innerText = 'STABILIZING...';
                        break;
                    case 'CAPTURING':
                        dot.className = 'h-2 w-2 rounded-full bg-green-500 animate-ping shadow-[0_0_8px_rgba(34,197,94,0.6)]';
                        label.innerText = 'CAPTURING!';
                        break;
                    case 'DONE':
                        dot.className = 'h-2 w-2 rounded-full bg-success-500 shadow-[0_0_8px_rgba(var(--success-500),0.6)]';
                        label.innerText = 'READY TO SUBMIT';
                        scannerOverlay.classList.add('opacity-0');
                        break;
                }
            };

            const stopMotionLoop = () => { if (loopId) { cancelAnimationFrame(loopId); loopId = null; } };
            const stopCameraStream = () => { if (stream) { stream.getTracks().forEach(track => track.stop()); stream = null; } };

            const stopBarcodeScanner = () => {
                if (!quaggaStarted || typeof Quagga === 'undefined') return;
                Quagga.offDetected(handleBarcodeDetected);
                Quagga.stop();
                quaggaStarted = false;
                const canvas = barcodeScanner.querySelector('canvas.drawingBuffer');
                if (canvas) canvas.getContext('2d').clearRect(0, 0, canvas.width, canvas.height);
            };

            const normalizeBarcode = (value) => value.replace(/\D/g, '').slice(0, 13);

            function handleBarcodeDetected(result) {
                const code = normalizeBarcode(result?.codeResult?.code || '');
                if (code.length < 10) return;
                
                @this.set('isbnBarcodeValue', code);
                if (isbnBarcodeInput) isbnBarcodeInput.value = code;
                stopBarcodeScanner();
                refreshActions();
            }

            window.startBarcodeScanner = () => {
                activeSide = 'isbn';
                stopMotionLoop();
                stopCameraStream();

                if (!barcodeScanner || typeof Quagga === 'undefined') {
                    updateStatus('IDLE');
                    return;
                }

                video.classList.add('hidden');
                barcodeScanner.classList.remove('hidden');
                updateStatus('IDLE');

                if (quaggaStarted) { Quagga.stop(); quaggaStarted = false; }

                Quagga.init({
                    inputStream: {
                        name: 'Live',
                        type: 'LiveStream',
                        target: barcodeScanner,
                        constraints: {
                            facingMode: currentFacingMode,
                            width: { min: 640, ideal: 1280 },
                            height: { min: 480, ideal: 720 },
                        },
                    },
                    decoder: { readers: ['ean_reader', 'ean_8_reader'] },
                    locate: true,
                    frequency: 10,
                }, (error) => {
                    if (error) { console.error(error); return; }
                    Quagga.start();
                    quaggaStarted = true;
                    Quagga.onDetected(handleBarcodeDetected);
                });
            };

            const captureSide = (side) => {
                updateStatus('CAPTURING');
                stabilityContainer.classList.add('hidden');
                
                const width = video.videoWidth;
                const height = video.videoHeight;
                snapshotCanvas.width = width;
                snapshotCanvas.height = height;
                snapshotCanvas.getContext('2d').drawImage(video, 0, 0, width, height);
                
                const dataUrl = snapshotCanvas.toDataURL('image/jpeg', 0.85);

                if (side === 'front') {
                    @this.set('frontImageData', dataUrl);
                    @this.set('frontImageWidth', width);
                    @this.set('frontImageHeight', height);
                    @this.extractTitleFromFrontImage(false, dataUrl);
                }

                refreshActions();
            };

            window.refreshActions = () => {
                const hasFront = @this.get('frontImageData');
                const hasTitle = @this.get('bookTitle');
                const isReady = !!(hasFront && hasTitle);

                // Note: submitBtn is now also handled by Alpine x-bind:disabled
                const btn = document.getElementById('submitBtn');
                if (btn) {
                    btn.disabled = !isReady;
                }

                if (isReady) updateStatus('DONE');
            };

            const updateStability = () => {
                if (!mathContext) return;
                mathContext.drawImage(video, 0, 0, 160, 120);
                const pixels = mathContext.getImageData(0, 0, 160, 120).data;
                const currentPixels = [];
                let totalDiff = 0;

                for (let index = 0; index < pixels.length; index += 4) {
                    const gray = (pixels[index] * 0.299) + (pixels[index + 1] * 0.587) + (pixels[index + 2] * 0.114);
                    currentPixels.push(gray);
                    if (lastPixels) totalDiff += Math.abs(gray - lastPixels[index/4]);
                }

                const avgDiff = lastPixels ? totalDiff / (pixels.length / 4) : 100;
                lastPixels = currentPixels;
                if (motionVal) motionVal.innerText = `Motion: ${avgDiff.toFixed(1)}`;

                if (state === 'IDLE') {
                    if (avgDiff > motionThresholdHigh) {
                        state = 'MOVING';
                        updateStatus('STABILIZING');
                    }
                } else if (state === 'MOVING' || state === 'STABILIZING') {
                    if (avgDiff > motionThresholdLow) {
                        state = 'MOVING';
                        stableFrames = 0;
                        stabilityContainer.classList.add('hidden');
                    } else {
                        state = 'STABILIZING';
                        stableFrames++;
                        const percent = Math.min((stableFrames / FRAMES_TO_STABILIZE) * 100, 100);
                        if (stabilityBar) stabilityBar.style.width = percent + '%';
                        if (timerVal) timerVal.innerText = `STABILIZING... ${Math.round(percent)}%`;
                        
                        if (stableFrames > 5) stabilityContainer.classList.remove('hidden');

                        if (stableFrames >= FRAMES_TO_STABILIZE) {
                            state = 'CAPTURED';
                            if (!@this.get('frontImageData')) captureSide('front');
                        }
                    }
                }

                loopId = requestAnimationFrame(updateStability);
            };

            const startCamera = async (facingMode = 'environment') => {
                stopCameraStream();
                try {
                    stream = await navigator.mediaDevices.getUserMedia({
                        video: { facingMode, width: { ideal: 1280 }, height: { ideal: 960 } },
                    });
                    video.srcObject = stream;
                    video.onloadedmetadata = () => {
                        mathCanvas.width = 160; mathCanvas.height = 120;
                        updateStatus('IDLE');
                        loopId = requestAnimationFrame(updateStability);
                    };
                } catch (err) {
                    console.error(err);
                    statusIndicator.querySelector('span').innerText = "CAMERA ERROR";
                }
            };

            switchCameraBtn.addEventListener('click', () => {
                currentFacingMode = currentFacingMode === 'environment' ? 'user' : 'environment';
                if (activeSide === 'isbn') { stopBarcodeScanner(); startBarcodeScanner(); }
                else startCamera(currentFacingMode);
            });

            if (isbnBarcodeInput) {
                isbnBarcodeInput.addEventListener('input', () => {
                    const normalized = normalizeBarcode(isbnBarcodeInput.value);
                    if (isbnBarcodeInput.value !== normalized) {
                        isbnBarcodeInput.value = normalized;
                        @this.set('isbnBarcodeValue', normalized);
                    }
                    refreshActions();
                });
            }

            startCamera();

            document.addEventListener('livewire:navigating', () => {
                stopMotionLoop(); stopCameraStream(); stopBarcodeScanner();
            });

            window.addEventListener('capture-reset', () => {
                activeSide = 'front'; state = 'MOVING'; stableFrames = 0; lastPixels = null;
                stopBarcodeScanner();
                barcodeScanner.classList.add('hidden');
                video.classList.remove('hidden');
                if (!stream) startCamera(currentFacingMode);
                else { updateStatus('IDLE'); refreshActions(); }
            });
        })();
    </script>
</x-filament-panels::page>
