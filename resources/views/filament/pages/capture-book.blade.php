<x-filament-panels::page>
    <script src="https://unpkg.com/html5-qrcode"></script>
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

        <div wire:ignore class="grid gap-4 lg:grid-cols-[minmax(0,1.35fr)_minmax(320px,0.65fr)]">
            <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-white/10 dark:bg-gray-900">
                <div class="relative aspect-[3/4] w-full bg-black">
                    <video id="captureVideo" autoplay playsinline muted class="h-full w-full object-cover"></video>
                    <canvas id="mathCanvas" class="hidden"></canvas>

                    <div class="absolute inset-0 pointer-events-none border-[12px] border-black/20"></div>

                    <div id="scannerOverlay" class="absolute inset-x-8 top-1/4 bottom-1/4 border-2 border-dashed border-primary-500/50 transition-opacity duration-300 opacity-0 flex items-center justify-center">
                        <div class="text-primary-500 font-mono text-xs bg-black/50 px-2 py-1 rounded">SCANNING ISBN...</div>
                    </div>

                    <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/80 to-transparent p-4">
                        <div class="flex items-center justify-between">
                            <div id="statusIndicator" class="flex items-center gap-2">
                                <div class="h-2.5 w-2.5 rounded-full bg-gray-500 animate-pulse"></div>
                                <span class="text-xs font-medium text-white uppercase tracking-wider">Initializing...</span>
                            </div>
                            <div class="flex gap-2">
                                <button type="button" id="switchCameraBtn" class="rounded-full bg-white/10 p-2 text-white hover:bg-white/20">
                                    <x-heroicon-m-arrow-path class="h-5 w-5" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <aside class="space-y-4" x-data="tokenSelector">
                <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-gray-900">
                    <label for="bookTitle" class="mb-2 block text-sm font-medium text-gray-950 dark:text-white">
                        Book Title
                    </label>

                    <div class="space-y-4">
                        <div id="token-sentence-container" class="flex flex-wrap gap-1 rounded-xl border border-gray-200 bg-gray-50 p-4 text-lg leading-relaxed dark:border-white/10 dark:bg-gray-800">
                            <template x-if="! $wire.ocrTokens || $wire.ocrTokens.length === 0">
                                <span class="italic text-gray-400">Capture front cover to extract title...</span>
                            </template>
                            <template x-for="(token, index) in $wire.ocrTokens" :key="index">
                                <span 
                                    x-text="token"
                                    x-on:click="toggleToken(index)"
                                    class="cursor-pointer select-none rounded-md px-1.5 py-0.5 transition-colors duration-150"
                                    :class="isSelected(index) ? 'bg-primary-500 text-white shadow-sm' : 'bg-transparent text-gray-800 hover:bg-gray-200 dark:text-gray-300 dark:hover:bg-gray-700'"
                                ></span>
                            </template>
                        </div>

                        <div class="rounded-lg border border-primary-100 bg-primary-50/50 p-4 dark:border-primary-900/30 dark:bg-primary-900/10">
                            <p class="mb-1 text-xs font-semibold uppercase tracking-wider text-primary-600 dark:text-primary-400">Selected Title</p>
                            <p class="min-h-[1.5rem] text-sm text-gray-700 dark:text-gray-300" :class="!$wire.bookTitle && 'italic text-gray-400'" x-text="$wire.bookTitle || 'No title selected yet...'"></p>
                        </div>
                        
                        <input type="hidden" wire:model="bookTitle" id="bookTitle">

                        <button 
                            type="button" 
                            wire:click="extractTitleFromFrontImage(silent: false)" 
                            wire:loading.attr="disabled"
                            class="flex w-full items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-semibold text-gray-700 shadow-sm hover:bg-gray-50 disabled:opacity-50 dark:border-white/10 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
                            :disabled="!$wire.frontImageData"
                        >
                            <x-heroicon-m-sparkles class="h-4 w-4" wire:loading.remove wire:target="extractTitleFromFrontImage" />
                            <svg wire:loading wire:target="extractTitleFromFrontImage" class="h-4 w-4 animate-spin text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span wire:loading.remove wire:target="extractTitleFromFrontImage">Re-extract from Front Image</span>
                            <span wire:loading wire:target="extractTitleFromFrontImage">Extracting...</span>
                        </button>
                    </div>
                </div>

                <div x-show="$wire.lastScannedIsbn" x-transition class="rounded-xl border border-success-200 bg-success-50 p-4 shadow-sm dark:border-success-900/30 dark:bg-success-950/20">
                    <div class="flex items-center justify-between">
                        <label class="block text-sm font-medium text-success-900 dark:text-success-400">
                            Scanned ISBN
                        </label>
                        <button type="button" x-on:click="$wire.lastScannedIsbn = null" class="text-xs font-semibold text-success-700 hover:text-success-600 dark:text-success-500">
                            Clear
                        </button>
                    </div>
                    <p class="mt-1 font-mono text-lg font-bold text-success-950 dark:text-success-300" x-text="$wire.lastScannedIsbn"></p>
                </div>

                <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-gray-900">
                    <label for="quantity" class="mb-2 block text-sm font-medium text-gray-950 dark:text-white">
                        Quantity
                    </label>
                    <div class="flex items-center gap-3">
                        <button type="button" x-on:click="$wire.quantity = Math.max(1, $wire.quantity - 1)" class="flex h-10 w-10 items-center justify-center rounded-lg border border-gray-300 bg-white text-gray-700 shadow-sm hover:bg-gray-50 dark:border-white/10 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700">
                            <x-heroicon-m-minus class="h-5 w-5" />
                        </button>
                        <input type="number" id="quantity" wire:model.live="quantity" class="block w-full rounded-lg border-gray-300 text-center text-lg font-semibold dark:border-white/10 dark:bg-gray-800 dark:text-white" min="1">
                        <button type="button" x-on:click="$wire.quantity = $wire.quantity + 1)" class="flex h-10 w-10 items-center justify-center rounded-lg border border-gray-300 bg-white text-gray-700 shadow-sm hover:bg-gray-50 dark:border-white/10 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700">
                            <x-heroicon-m-plus class="h-5 w-5" />
                        </button>
                    </div>
                </div>

                <button type="submit" id="submitBtn" disabled class="flex w-full items-center justify-center gap-2 rounded-xl bg-primary-600 px-4 py-4 text-sm font-bold text-white shadow-lg shadow-primary-500/20 transition-all hover:bg-primary-500 disabled:opacity-50 disabled:grayscale">
                    <x-heroicon-m-check-circle class="h-5 w-5" />
                    SUBMIT CAPTURE
                </button>

                <div class="rounded-lg bg-gray-100 p-3 text-[10px] leading-relaxed text-gray-500 dark:bg-white/5">
                    <p class="font-bold uppercase mb-1">How it works:</p>
                    <ul class="list-disc pl-4 space-y-1">
                        <li>Point camera at the <strong>front cover</strong>.</li>
                        <li>Keep it steady for auto-capture.</li>
                        <li>Then, scan the <strong>barcode/ISBN</strong> on the back.</li>
                        <li>Review details and submit.</li>
                    </ul>
                </div>
            </aside>
        </div>
    </form>

    <canvas id="snapshotCanvas" class="hidden"></canvas>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('tokenSelector', () => ({
                selectedTokens: [],
                
                init() {
                    this.$watch('$wire.ocrTokens', () => {
                        this.selectedTokens = [];
                        this.$wire.bookTitle = '';
                    });
                },

                toggleToken(index) {
                    if (this.selectedTokens.includes(index)) {
                        this.selectedTokens = this.selectedTokens.filter(i => i !== index);
                    } else {
                        this.selectedTokens.push(index);
                    }
                    this.updateTitle();
                },

                isSelected(index) {
                    return this.selectedTokens.includes(index);
                },

                updateTitle() {
                    const sortedIndices = [...this.selectedTokens].sort((a, b) => a - b);
                    this.$wire.bookTitle = sortedIndices.map(i => this.$wire.ocrTokens[i]).join(' ');
                }
            }));
        });

        (function() {
            if (window.kutubioCapturePageInitialized) {
                return;
            }

            window.kutubioCapturePageInitialized = true;

            const video = document.getElementById('captureVideo');
            const mathCanvas = document.getElementById('mathCanvas');
            const mathContext = mathCanvas.getContext('2d', { willReadFrequently: true });
            const snapshotCanvas = document.getElementById('snapshotCanvas');
            const statusIndicator = document.getElementById('statusIndicator');
            const scannerOverlay = document.getElementById('scannerOverlay');
            const submitBtn = document.getElementById('submitBtn');
            const switchCameraBtn = document.getElementById('switchCameraBtn');

            let lastPixels = null;
            let stableFrames = 0;
            let loopId = null;
            let stream = null;
            let activeSide = 'front';
            let html5QrCode = null;
            let lastExtractionTime = 0;
            const extractionInterval = 5000;

            let barcodeDetector = null;
            let barcodeDetectorFormats = [];
            let barcodeScanInFlight = false;
            let barcodeScanningAvailable = false;
            let ocrPreviewInFlight = false;
            let lastOcrPreviewAt = 0;
            let ocrCandidate = null;
            let ocrLines = [];

            const periodicTitleExtraction = () => {
                if (activeSide !== 'front' || !stream || $wire.isExtracting) return;
                
                const now = Date.now();
                if (now - lastExtractionTime < extractionInterval) return;

                if (stableFrames < 2) return;

                lastExtractionTime = now;

                const tempCanvas = document.createElement('canvas');
                tempCanvas.width = 640;
                tempCanvas.height = 480;
                const tempCtx = tempCanvas.getContext('2d');
                tempCtx.drawImage(video, 0, 0, 640, 480);
                
                const dataUrl = tempCanvas.toDataURL('image/jpeg', 0.6);
                $wire.set('frontImageData', dataUrl, false);
                $wire.extractTitleFromFrontImage();
            };

            const scanFrame = async () => {
                if (activeSide !== 'back' || !stream || $wire.lastScannedIsbn) return;

                const tempCanvas = document.createElement('canvas');
                tempCanvas.width = video.videoWidth;
                tempCanvas.height = video.videoHeight;
                const tempCtx = tempCanvas.getContext('2d');
                tempCtx.drawImage(video, 0, 0);

                try {
                    if (!html5QrCode) {
                        html5QrCode = new Html5Qrcode("mathCanvas");
                    }

                    const result = await html5QrCode.scanFile(tempCanvas.toDataURL('image/jpeg'), false);
                    if (result) {
                        $wire.scannedIsbn(result);
                    }
                } catch (e) {
                }
            };

            const motionThresholdHigh = 15;
            const motionThresholdLow = 4;

            const updateStatus = (state) => {
                const dot = statusIndicator.querySelector('div');
                const label = statusIndicator.querySelector('span');

                switch(state) {
                    case 'IDLE':
                        dot.className = 'h-2.5 w-2.5 rounded-full bg-blue-500';
                        label.innerText = `READY: ${activeSide.toUpperCase()}`;
                        scannerOverlay.classList.toggle('opacity-0', activeSide !== 'back');
                        break;
                    case 'STABILIZING':
                        dot.className = 'h-2.5 w-2.5 rounded-full bg-yellow-500 animate-pulse';
                        label.innerText = 'STABILIZING...';
                        break;
                    case 'CAPTURING':
                        dot.className = 'h-2.5 w-2.5 rounded-full bg-green-500 animate-ping';
                        label.innerText = 'CAPTURING!';
                        break;
                    case 'DONE':
                        dot.className = 'h-2.5 w-2.5 rounded-full bg-success-500';
                        label.innerText = 'READY TO SUBMIT';
                        scannerOverlay.classList.add('opacity-0');
                        break;
                }
            };

            const captureSide = (side) => {
                updateStatus('CAPTURING');
                
                const width = video.videoWidth;
                const height = video.videoHeight;
                snapshotCanvas.width = width;
                snapshotCanvas.height = height;
                
                const context = snapshotCanvas.getContext('2d');
                context.drawImage(video, 0, 0, width, height);
                
                const dataUrl = snapshotCanvas.toDataURL('image/jpeg', 0.85);

                if (side === 'front') {
                    @this.set('frontImageData', dataUrl);
                    @this.set('frontImageWidth', width);
                    @this.set('frontImageHeight', height);
                } else {
                    // back image removed in upstream
                }

                updateStability();
                refreshActions();

                if (activeSide === 'front') {
                    @this.extractTitleFromFrontImage();
                }

                const sideCaptured = activeSide;
                window.setTimeout(() => {
                    if (sideCaptured === 'back' || !stream) {
                        return;
                    }
                    activeSide = 'back';
                    stableFrames = 0;
                    lastPixels = null;
                    updateStatus('IDLE');
                }, 1000);
            };

            const refreshActions = () => {
                const hasFront = @this.get('frontImageData');
                const hasIsbn = @this.get('isbnBarcodeValue') || @this.get('lastScannedIsbn');
                
                submitBtn.disabled = !hasFront;
                if (hasFront && hasIsbn) {
                    updateStatus('DONE');
                }
            };

            const updateStability = () => {
                const pixels = mathContext.getImageData(0, 0, 160, 120).data;
                const currentPixels = [];
                let totalDiff = 0;

                scanFrame();
                periodicTitleExtraction();

                for (let index = 0; index < pixels.length; index += 4) {
                    const gray = (pixels[index] * 0.299) + (pixels[index + 1] * 0.587) + (pixels[index + 2] * 0.114);
                    currentPixels.push(gray);
                    
                    if (lastPixels) {
                        totalDiff += Math.abs(gray - lastPixels[index/4]);
                    }
                }

                const avgDiff = lastPixels ? totalDiff / (pixels.length / 4) : 100;
                lastPixels = currentPixels;

                if (avgDiff < motionThresholdLow) {
                    stableFrames++;
                    if (stableFrames > 5) {
                        updateStatus('STABILIZING');
                    }
                    if (stableFrames > 15) {
                        const hasCurrentSide = activeSide === 'front' ? @this.get('frontImageData') : true;
                        if (!hasCurrentSide) {
                            captureSide(activeSide);
                        }
                    }
                } else if (avgDiff > motionThresholdHigh) {
                    stableFrames = 0;
                    if (!(@this.get('frontImageData') && activeSide === 'back' && (@this.get('isbnBarcodeValue') || @this.get('lastScannedIsbn')))) {
                        updateStatus('IDLE');
                    }
                }

                loopId = requestAnimationFrame(updateStability);
            };

            const startCamera = async (facingMode = 'environment') => {
                if (stream) {
                    stream.getTracks().forEach(track => track.stop());
                }

                try {
                    stream = await navigator.mediaDevices.getUserMedia({
                        video: { 
                            facingMode: facingMode,
                            width: { ideal: 1280 },
                            height: { ideal: 960 }
                        },
                        audio: false
                    });
                    
                    video.srcObject = stream;
                    video.onloadedmetadata = () => {
                        mathCanvas.width = 160;
                        mathCanvas.height = 120;
                        updateStatus('IDLE');
                        loopId = requestAnimationFrame(updateStability);
                    };
                } catch (err) {
                    console.error("Camera error:", err);
                    statusIndicator.querySelector('span').innerText = "CAMERA ERROR";
                }
            };

            let currentFacingMode = 'environment';
            switchCameraBtn.addEventListener('click', () => {
                currentFacingMode = currentFacingMode === 'environment' ? 'user' : 'environment';
                startCamera(currentFacingMode);
            });

            startCamera();

            document.addEventListener('livewire:navigating', () => {
                if (loopId) cancelAnimationFrame(loopId);
                if (stream) stream.getTracks().forEach(track => track.stop());
            });
        })();
    </script>
</x-filament-panels::page>
