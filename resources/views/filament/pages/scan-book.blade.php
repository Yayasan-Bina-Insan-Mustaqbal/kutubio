<x-filament-panels::page>
    @vite(['resources/js/app.js'])
    
    <div class="grid gap-4 lg:grid-cols-[minmax(0,1.35fr)_minmax(320px,0.65fr)]">
        <!-- Scanner Viewport -->
        <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-white/10 dark:bg-gray-900">
            <div class="relative aspect-video w-full bg-black overflow-hidden">
                <video id="qrVideo" autoplay playsinline muted class="h-full w-full object-cover"></video>
                <canvas id="qrCanvas" class="hidden"></canvas>
                
                <!-- Scanner Overlay -->
                <div class="absolute inset-0 pointer-events-none flex items-center justify-center">
                    <div class="w-64 h-64 border-2 border-primary-500 rounded-lg relative">
                        <div class="absolute inset-0 border-4 border-black/20"></div>
                        <div class="absolute -top-1 -left-1 w-6 h-6 border-t-4 border-l-4 border-primary-500"></div>
                        <div class="absolute -top-1 -right-1 w-6 h-6 border-t-4 border-r-4 border-primary-500"></div>
                        <div class="absolute -bottom-1 -left-1 w-6 h-6 border-b-4 border-l-4 border-primary-500"></div>
                        <div class="absolute -bottom-1 -right-1 w-6 h-6 border-b-4 border-r-4 border-primary-500"></div>
                        
                        <!-- Scanning Line Animation -->
                        <div class="absolute inset-x-0 top-0 h-0.5 bg-primary-500 shadow-[0_0_8px_rgba(var(--primary-500),0.8)] animate-[scan_2s_linear_infinite]"></div>
                    </div>
                </div>

                <!-- Status Overlay -->
                <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/80 to-transparent p-6">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div id="statusDot" class="h-2.5 w-2.5 rounded-full bg-yellow-500 shadow-[0_0_10px_rgba(234,179,8,0.5)]"></div>
                            <span id="statusText" class="text-[10px] font-bold text-white uppercase tracking-widest">Initializing...</span>
                        </div>
                        <div class="flex gap-2">
                            <button type="button" wire:click="resetScanner" class="rounded-full bg-white/10 backdrop-blur-md p-2 text-white hover:bg-white/20 transition-colors border border-white/5" title="Reset Scanner">
                                <x-heroicon-m-arrow-path class="h-5 w-5" />
                            </button>
                            <button type="button" id="switchCameraBtn" class="rounded-full bg-white/10 backdrop-blur-md p-2.5 text-white hover:bg-white/20 transition-colors border border-white/5">
                                <x-heroicon-m-device-phone-mobile class="h-5 w-5" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bottom Information Area -->
            <div x-show="$wire.bookCopy" x-transition class="p-6 border-t border-gray-100 dark:border-white/5 bg-gray-50/30 dark:bg-gray-800/20">
                <div class="flex items-start gap-4">
                    <div class="flex-shrink-0 w-16 h-20 bg-gray-200 dark:bg-gray-700 rounded-lg flex items-center justify-center overflow-hidden">
                        <template x-if="$wire.bookCopy?.book?.front_image">
                            <img :src="'/storage/' + $wire.bookCopy.book.front_image" class="w-full h-full object-cover">
                        </template>
                        <template x-if="!$wire.bookCopy?.book?.front_image">
                            <x-heroicon-o-book-open class="h-8 w-8 text-gray-400" />
                        </template>
                    </div>
                    <div class="flex-grow">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider" 
                                :class="$wire.processMode === 'return' ? 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400' : 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'">
                                <span x-text="$wire.processMode === 'return' ? 'Currently Borrowed' : 'Available for Loan'"></span>
                            </span>
                            <span class="text-[10px] font-mono text-gray-500" x-text="$wire.bookCopy?.tracking_code"></span>
                        </div>
                        <h2 class="text-lg font-bold text-gray-900 dark:text-white leading-tight mb-1" x-text="$wire.bookCopy?.book?.title"></h2>
                        <p class="text-xs text-gray-500 dark:text-gray-400" x-text="$wire.bookCopy?.book?.authors"></p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Sidebar: Circulation Controls -->
        <aside class="space-y-4">
            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-gray-900">
                <div x-show="!$wire.bookCopy" class="flex flex-col items-center justify-center py-8 text-center">
                    <div class="w-16 h-16 bg-primary-50 dark:bg-primary-900/20 rounded-full flex items-center justify-center mb-4">
                        <x-heroicon-o-qr-code class="h-8 w-8 text-primary-500" />
                    </div>
                    <h3 class="text-sm font-bold text-gray-900 dark:text-white mb-1">Scan to Start</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 max-w-[200px]">Point the camera at a book's QR code to begin the circulation process.</p>
                </div>

                <div x-show="$wire.bookCopy">
                    <!-- RETURN MODE -->
                    <div x-show="$wire.processMode === 'return'" class="space-y-6">
                        <div class="p-4 rounded-xl bg-orange-50 dark:bg-orange-900/10 border border-orange-100 dark:border-orange-900/20">
                            <label class="block text-[10px] font-bold uppercase tracking-widest text-orange-600 mb-3">Borrowed By</label>
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full bg-orange-100 dark:bg-orange-900/30 flex items-center justify-center">
                                    <x-heroicon-s-user class="h-5 w-5 text-orange-600" />
                                </div>
                                <div>
                                    <div class="text-sm font-bold text-gray-900 dark:text-white" x-text="$wire.currentLoan?.borrower?.name"></div>
                                    <div class="text-[10px] text-orange-600 font-medium" x-text="($wire.currentLoan?.borrower?.class || 'No Class') + ' • ' + ($wire.currentLoan?.borrower?.identifier)"></div>
                                </div>
                            </div>
                        </div>

                        <button type="button" 
                            wire:click="processReturn"
                            wire:loading.attr="disabled"
                            class="w-full inline-flex items-center justify-center px-4 py-3 bg-orange-600 hover:bg-orange-700 text-white rounded-xl font-bold text-sm transition-colors shadow-lg shadow-orange-500/20">
                            <x-filament::loading-indicator wire:loading class="h-4 w-4 mr-2" />
                            Process Return
                        </button>
                    </div>

                    <!-- LOAN MODE -->
                    <div x-show="$wire.processMode === 'loan'" class="space-y-6">
                        <div class="p-1">
                            {{ $this->form }}
                        </div>

                        <button type="button" 
                            wire:click="processLoan"
                            wire:loading.attr="disabled"
                            x-bind:disabled="!$wire.data.borrower_id"
                            class="w-full inline-flex items-center justify-center px-4 py-3 bg-primary-600 hover:bg-primary-700 disabled:opacity-50 disabled:cursor-not-allowed text-white rounded-xl font-bold text-sm transition-colors shadow-lg shadow-primary-500/20">
                            <x-filament::loading-indicator wire:loading class="h-4 w-4 mr-2" />
                            Process Loan
                        </button>
                    </div>

                    <button type="button" wire:click="resetScanner" class="w-full mt-4 text-[10px] font-bold uppercase tracking-widest text-gray-400 hover:text-gray-600 transition-colors py-2">
                        Cancel & Clear
                    </button>
                </div>
            </div>
        </aside>
    </div>

    <style>
        @keyframes scan {
            0% { top: 0; }
            50% { top: 100%; }
            100% { top: 0; }
        }
    </style>

    <script type="module">
        (function() {
            const video = document.getElementById('qrVideo');
            const statusDot = document.getElementById('statusDot');
            const statusText = document.getElementById('statusText');
            const switchBtn = document.getElementById('switchCameraBtn');
            
            let stream = null;
            let detector = null;
            let scanning = true;
            let currentFacingMode = 'environment';
            let loopId = null;

            window.addEventListener('scanner-reset', () => {
                scanning = true;
                if (!loopId) loopId = requestAnimationFrame(scanFrame);
            });

            async function initDetector() {
                try {
                    const check = () => window.BarcodeDetector ? true : false;
                    
                    if (!check()) {
                        // Wait for polyfill
                        await new Promise(resolve => {
                            const interval = setInterval(() => {
                                if (check()) {
                                    clearInterval(interval);
                                    resolve();
                                }
                            }, 50);
                        });
                    }
                    
                    const formats = await window.BarcodeDetector.getSupportedFormats();
                    if (formats.includes('qr_code')) {
                        detector = new window.BarcodeDetector({ formats: ['qr_code'] });
                        return true;
                    }
                    return false;
                } catch (e) {
                    return false;
                }
            }

            async function startCamera(facingMode = 'environment') {
                if (stream) {
                    stream.getTracks().forEach(track => track.stop());
                }

                try {
                    stream = await navigator.mediaDevices.getUserMedia({
                        video: { 
                            facingMode: facingMode,
                            width: { ideal: 1280 },
                            height: { ideal: 720 }
                        },
                        audio: false
                    });
                    video.srcObject = stream;
                    currentFacingMode = facingMode;
                    
                    video.onloadedmetadata = () => {
                        statusDot.classList.replace('bg-yellow-500', 'bg-green-500');
                        statusDot.classList.add('shadow-[0_0_10px_rgba(34,197,94,0.5)]');
                        statusText.textContent = 'Scanner Active';
                        scanning = true;
                        if (loopId) cancelAnimationFrame(loopId);
                        loopId = requestAnimationFrame(scanFrame);
                    };
                } catch (err) {
                    statusDot.classList.replace('bg-yellow-500', 'bg-red-500');
                    statusText.textContent = 'Camera Error';
                }
            }

            async function scanFrame() {
                if (!scanning || !detector || video.readyState !== video.HAVE_ENOUGH_DATA) {
                    loopId = requestAnimationFrame(scanFrame);
                    return;
                }

                try {
                    const barcodes = await detector.detect(video);
                    if (barcodes.length > 0) {
                        const value = barcodes[0].rawValue;
                        console.log('QR Scanned:', value);
                        
                        // Feedback
                        statusDot.classList.add('scale-150');
                        setTimeout(() => statusDot.classList.remove('scale-150'), 200);

                        @this.handleQrScanned(value);
                        scanning = false; // Pause while processing
                        loopId = null;
                        return;
                    }
                } catch (e) {}

                loopId = requestAnimationFrame(scanFrame);
            }

            switchBtn.addEventListener('click', () => {
                currentFacingMode = currentFacingMode === 'environment' ? 'user' : 'environment';
                startCamera(currentFacingMode);
            });

            const init = async () => {
                if (await initDetector()) {
                    startCamera(currentFacingMode);
                } else {
                    statusText.textContent = 'Unsupported Browser';
                    statusDot.classList.replace('bg-yellow-500', 'bg-red-500');
                }
            };

            init();

            document.addEventListener('livewire:navigating', () => {
                scanning = false;
                if (loopId) cancelAnimationFrame(loopId);
                if (stream) stream.getTracks().forEach(track => track.stop());
            });
        })();
    </script>
</x-filament-panels::page>
