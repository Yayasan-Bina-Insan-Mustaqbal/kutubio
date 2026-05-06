<x-filament-panels::page>
    @vite(['resources/js/app.js'])
    
    <div class="grid gap-4 lg:grid-cols-[minmax(0,1.35fr)_minmax(320px,0.65fr)]">
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
                        <button type="button" id="switchCameraBtn" class="rounded-full bg-white/10 backdrop-blur-md p-2.5 text-white hover:bg-white/20 transition-colors border border-white/5">
                            <x-heroicon-m-arrow-path class="h-5 w-5" />
                        </button>
                    </div>
                </div>
            </div>
        </section>

        <aside class="space-y-4">
            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-gray-900">
                <label class="mb-4 block text-[10px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                    Last Scanned QR
                </label>
                
                <div class="space-y-4">
                    <div id="qrResult" class="flex min-h-[120px] flex-col items-center justify-center rounded-xl border border-gray-100 bg-gray-50/50 p-4 text-center dark:border-white/10 dark:bg-gray-800/50">
                        <x-heroicon-o-qr-code class="h-10 w-10 text-gray-300 dark:text-gray-600 mb-3" />
                        <span class="text-xs italic text-gray-400">Scan a book's QR code to begin...</span>
                    </div>

                    <div x-show="$wire.scannedQrCode" x-transition class="rounded-xl border border-primary-100 bg-primary-50/30 p-4 dark:border-primary-900/20 dark:bg-primary-900/5 overflow-hidden relative">
                        <div class="absolute top-0 right-0 p-2 opacity-10">
                            <x-heroicon-m-check-circle class="h-12 w-12 text-primary-500" />
                        </div>
                        <p class="mb-1.5 text-[10px] font-bold uppercase tracking-widest text-primary-600 dark:text-primary-400">Scanned Content</p>
                        <p class="text-sm font-mono font-semibold text-gray-900 dark:text-white break-all" x-text="$wire.scannedQrCode"></p>
                    </div>
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
        document.addEventListener('DOMContentLoaded', async () => {
            const video = document.getElementById('qrVideo');
            const statusDot = document.getElementById('statusDot');
            const statusText = document.getElementById('statusText');
            const qrResult = document.getElementById('qrResult');
            const switchBtn = document.getElementById('switchCameraBtn');
            
            let stream = null;
            let detector = null;
            let scanning = true;
            let currentFacingMode = 'environment';

            async function initDetector() {
                try {
                    if (!window.BarcodeDetector) {
                        await new Promise(resolve => {
                            const check = () => {
                                if (window.BarcodeDetector) resolve();
                                else setTimeout(check, 100);
                            };
                            check();
                        });
                    }
                    
                    const formats = await window.BarcodeDetector.getSupportedFormats();
                    if (formats.includes('qr_code')) {
                        detector = new window.BarcodeDetector({ formats: ['qr_code'] });
                        return true;
                    }
                    return false;
                } catch (e) {
                    console.error('Detector init failed:', e);
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
                    
                    statusDot.classList.replace('bg-yellow-500', 'bg-green-500');
                    statusDot.classList.add('shadow-[0_0_10px_rgba(34,197,94,0.5)]');
                    statusText.textContent = 'Scanner Active';
                    
                    scanFrame();
                } catch (err) {
                    statusDot.classList.replace('bg-yellow-500', 'bg-red-500');
                    statusText.textContent = 'Camera Error';
                }
            }

            async function scanFrame() {
                if (!scanning || !detector || video.readyState !== video.HAVE_ENOUGH_DATA) {
                    if (scanning) requestAnimationFrame(scanFrame);
                    return;
                }

                try {
                    const barcodes = await detector.detect(video);
                    if (barcodes.length > 0) {
                        handleQRFound(barcodes[0].rawValue);
                    }
                } catch (e) {}

                if (scanning) requestAnimationFrame(scanFrame);
            }

            function handleQRFound(value) {
                if (qrResult.dataset.lastValue === value) return;
                
                qrResult.dataset.lastValue = value;
                
                // Feedback animation
                statusDot.classList.add('scale-150');
                setTimeout(() => statusDot.classList.remove('scale-150'), 200);

                // Notify Livewire
                Livewire.dispatch('qr-scanned', { value: value });
            }

            switchBtn.addEventListener('click', () => {
                currentFacingMode = currentFacingMode === 'environment' ? 'user' : 'environment';
                startCamera(currentFacingMode);
            });

            if (await initDetector()) {
                startCamera();
            } else {
                statusText.textContent = 'Unsupported Browser';
                statusDot.classList.replace('bg-yellow-500', 'bg-red-500');
            }
        });
    </script>
</x-filament-panels::page>
