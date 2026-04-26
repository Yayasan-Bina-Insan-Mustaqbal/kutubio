<x-filament-panels::page>
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
                <div class="relative aspect-[4/3] bg-black">
                    <video id="captureVideo" class="h-full w-full object-cover" autoplay playsinline muted></video>
                    <div id="frontGuideBox" class="pointer-events-none absolute left-[18%] top-[8%] hidden h-[84%] w-[64%] rounded-lg border-2 border-white/90 shadow-[0_0_0_9999px_rgba(0,0,0,0.35)]"></div>
                    <div id="captureStatus" class="absolute left-4 top-4 rounded-full bg-gray-900/80 px-3 py-1 text-sm font-semibold text-white">
                        Camera idle
                    </div>
                    <div id="cameraHelp" class="absolute inset-x-4 top-16 hidden rounded-lg bg-danger-600/95 p-3 text-sm font-medium text-white shadow-lg"></div>
                    <div class="absolute bottom-4 left-4 right-4">
                        <div class="mb-2 flex items-center justify-between text-xs font-medium text-white">
                            <span id="captureSideLabel">Front</span>
                            <span id="stabilityPercent">0%</span>
                        </div>
                        <div class="h-2 overflow-hidden rounded-full bg-white/25">
                            <div id="stabilityBar" class="h-full w-0 rounded-full bg-primary-500 transition-all"></div>
                        </div>
                    </div>
                </div>

                <div class="border-t border-gray-200 p-4 dark:border-white/10">
                    <button id="manualCaptureButton" type="button" class="w-full rounded-lg bg-gray-900 px-4 py-3 text-sm font-semibold text-white hover:bg-gray-800 disabled:opacity-50 dark:bg-white dark:text-gray-950" disabled>
                        Capture Now
                    </button>
                </div>
            </section>

            <aside class="space-y-4">
                <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-gray-900">
                    <label for="quantity" class="mb-2 block text-sm font-medium text-gray-950 dark:text-white">
                        Quantity
                    </label>
                    
                    <div style="display: flex; align-items: center; gap: 12px; width: 100%; margin-top: 8px;">
                        <button 
                            type="button"
                            x-on:click="$wire.quantity = Math.max(1, (parseInt($wire.quantity) || 1) - 1)"
                            style="flex: 1; height: 64px; background-color: #ffffff !important; color: #000000 !important; border: none; border-radius: 16px; font-size: 40px; font-weight: 900; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 6px -1px rgb(255 255 255 / 0.1);"
                        >
                            −
                        </button>

                        <div style="width: 80px; display: flex; flex-direction: column; align-items: center; justify-content: center;">
                            <span style="font-size: 40px; font-weight: 900; color: #ffffff !important;" x-text="$wire.quantity"></span>
                            <span style="font-size: 12px; font-weight: 700; color: #9ca3af; text-transform: uppercase;">Qty</span>
                        </div>

                        <button 
                            type="button"
                            x-on:click="$wire.quantity = (parseInt($wire.quantity) || 1) + 1"
                            style="flex: 1; height: 64px; background-color: #ffffff !important; color: #000000 !important; border: none; border-radius: 16px; font-size: 40px; font-weight: 900; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 6px -1px rgb(255 255 255 / 0.1);"
                        >
                            +
                        </button>
                    </div>

                    @error('quantity')
                        <p class="mt-1 text-xs text-danger-600">{{ $message }}</p>
                    @enderror
                </div>

                <div id="frontOcrPanel" class="hidden rounded-xl border border-primary-200 bg-primary-50 p-4 shadow-sm dark:border-primary-400/30 dark:bg-primary-950/30">
                    <div class="mb-3 flex items-center justify-between gap-3">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-primary-700 dark:text-primary-300">Live OCR</p>
                            <p id="frontOcrState" class="text-sm text-gray-600 dark:text-gray-300">Waiting for a stable cover frame</p>
                        </div>
                        <button id="acceptOcrButton" type="button" class="rounded-lg bg-primary-600 px-3 py-2 text-xs font-semibold text-white hover:bg-primary-500 disabled:opacity-50" disabled>
                            Use Reading
                        </button>
                    </div>
                    <dl class="space-y-2 text-sm">
                        <div>
                            <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">Title</dt>
                            <dd id="frontOcrTitlePreview" class="font-semibold text-gray-950 dark:text-white">-</dd>
                            <div id="titleSelectorContainer" class="mt-2 hidden">
                                <input id="titleSlider" type="range" min="0" max="0" step="1" class="h-1.5 w-full cursor-pointer appearance-none rounded-lg bg-primary-200 dark:bg-primary-700">
                                <div class="mt-1 flex justify-between text-[10px] font-medium text-primary-600 dark:text-primary-400">
                                    <span>Top text</span>
                                    <span>Bottom text</span>
                                </div>
                            </div>
                        </div>
                        <div>
                            <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">Authors</dt>
                            <dd id="frontOcrAuthorsPreview" class="text-gray-800 dark:text-gray-200">-</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">Raw text</dt>
                            <dd id="frontOcrRawPreview" class="max-h-24 overflow-auto whitespace-pre-wrap rounded-lg bg-white/70 p-2 text-xs text-gray-700 dark:bg-gray-900/70 dark:text-gray-300">-</dd>
                        </div>
                    </dl>
                </div>

                <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-gray-900">
                    <label for="isbnBarcodeInput" class="mb-2 block text-sm font-medium text-gray-950 dark:text-white">
                        1D Code
                    </label>
                    <input
                        id="isbnBarcodeInput"
                        type="text"
                        inputmode="numeric"
                        pattern="[0-9]*"
                        autocomplete="off"
                        class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-base font-semibold text-gray-950 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-white/10 dark:bg-gray-950 dark:text-white"
                    >
                    <p id="isbnBarcodeInputHint" class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                        Scanner result appears here. Edit it before submitting if needed.
                    </p>
                </div>

                <button id="submitCaptureButton" type="submit" class="w-full rounded-lg bg-success-600 px-4 py-3 text-sm font-semibold text-white hover:bg-success-500 disabled:opacity-50" disabled wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="submit, frontImageData">
                        Submit Book
                    </span>
                    <span wire:loading wire:target="frontImageData">
                        Uploading...
                    </span>
                    <span wire:loading wire:target="submit">
                        Processing...
                    </span>
                </button>

                <div class="flex flex-row gap-3 lg:flex-col lg:gap-4">
                    <div class="w-1/2 rounded-xl border border-gray-200 bg-white p-3 shadow-sm dark:border-white/10 dark:bg-gray-900 lg:w-full sm:p-4">
                        <div class="mb-2 flex items-center justify-between gap-2 sm:mb-3 sm:gap-4">
                            <h2 class="truncate text-[10px] font-semibold text-gray-950 dark:text-white sm:text-base">Front</h2>
                            <button id="retakeFrontButton" type="button" class="shrink-0 text-[10px] font-semibold text-primary-600 disabled:text-gray-400 sm:text-sm">
                                Retake
                            </button>
                        </div>
                        <div class="aspect-[4/3] overflow-hidden rounded-lg bg-gray-100 dark:bg-gray-800">
                            <img id="frontPreview" class="hidden h-full w-full object-cover" alt="Front preview">
                            <div id="frontPlaceholder" class="flex h-full items-center justify-center px-1 text-center text-[9px] text-gray-500 sm:px-4 sm:text-sm">
                                Waiting...
                            </div>
                        </div>
                    </div>

                    <div class="w-1/2 rounded-xl border border-gray-200 bg-white p-3 shadow-sm dark:border-white/10 dark:bg-gray-900 lg:w-full sm:p-4">
                        <div class="mb-2 flex items-center justify-between gap-2 sm:mb-3 sm:gap-4">
                            <h2 class="truncate text-[10px] font-semibold text-gray-950 dark:text-white sm:text-base">1D Code</h2>
                            <button id="retakeBackButton" type="button" class="shrink-0 text-[10px] font-semibold text-primary-600 disabled:text-gray-400 sm:text-sm">
                                Retake
                            </button>
                        </div>
                        <div class="aspect-[4/3] overflow-hidden rounded-lg bg-gray-100 p-3 dark:bg-gray-800">
                            <div id="backPlaceholder" class="flex h-full items-center justify-center px-1 text-center text-[9px] text-gray-500 sm:px-4 sm:text-sm">
                                Waiting for code...
                            </div>
                        </div>
                    </div>
                </div>
            </aside>
        </div>

        @error('frontImageData')
            <p class="text-sm font-medium text-danger-600">{{ $message }}</p>
        @enderror

        @error('isbnBarcodeValue')
            <p class="text-sm font-medium text-danger-600">{{ $message }}</p>
        @enderror
    </form>

    <canvas id="mathCanvas" width="64" height="48" class="hidden"></canvas>
    <canvas id="snapshotCanvas" class="hidden"></canvas>

    <script>
        // Use a function to avoid polluting global scope while still allowing re-execution
        (function() {
            const video = document.getElementById('captureVideo');
            const mathCanvas = document.getElementById('mathCanvas');
            const mathContext = mathCanvas.getContext('2d', { willReadFrequently: true });
            const snapshotCanvas = document.getElementById('snapshotCanvas');
            const snapshotContext = snapshotCanvas.getContext('2d');

            const statusBadge = document.getElementById('captureStatus');
            if (statusBadge) {
                statusBadge.textContent = 'Initializing camera...';
            }
            const cameraHelp = document.getElementById('cameraHelp');
            const frontGuideBox = document.getElementById('frontGuideBox');
            const sideLabel = document.getElementById('captureSideLabel');
            const stabilityPercent = document.getElementById('stabilityPercent');
            const stabilityBar = document.getElementById('stabilityBar');

            const manualCaptureButton = document.getElementById('manualCaptureButton');
            const submitCaptureButton = document.getElementById('submitCaptureButton');
            const retakeFrontButton = document.getElementById('retakeFrontButton');
            const retakeBackButton = document.getElementById('retakeBackButton');
            const isbnBarcodeInput = document.getElementById('isbnBarcodeInput');
            const acceptOcrButton = document.getElementById('acceptOcrButton');
            const frontOcrPanel = document.getElementById('frontOcrPanel');
            const frontOcrState = document.getElementById('frontOcrState');
            const frontOcrTitlePreview = document.getElementById('frontOcrTitlePreview');
            const frontOcrAuthorsPreview = document.getElementById('frontOcrAuthorsPreview');
            const frontOcrRawPreview = document.getElementById('frontOcrRawPreview');
            const titleSelectorContainer = document.getElementById('titleSelectorContainer');
            const titleSlider = document.getElementById('titleSlider');

            const fields = {
                front: {
                    data: document.getElementById('frontImageData'),
                    width: document.getElementById('frontImageWidth'),
                    height: document.getElementById('frontImageHeight'),
                    preview: document.getElementById('frontPreview'),
                    placeholder: document.getElementById('frontPlaceholder'),
                    ocrTitle: document.getElementById('frontOcrTitle'),
                    ocrSubtitle: document.getElementById('frontOcrSubtitle'),
                    ocrAuthors: document.getElementById('frontOcrAuthors'),
                    ocrPublisher: document.getElementById('frontOcrPublisher'),
                    ocrText: document.getElementById('frontOcrText'),
                    ocrConfidence: document.getElementById('frontOcrConfidence'),
                    label: 'Front',
                },
                back: {
                    barcode: document.getElementById('isbnBarcodeValue'),
                    placeholder: document.getElementById('backPlaceholder'),
                    label: '1D Code',
                },
            };

            let previousPixels = [];
            let state = 'IDLE';
            let stableFrames = 0;
            let loopId = null;
            let stream = null;
            let activeSide = 'front';
            let barcodeDetector = null;
            let barcodeDetectorFormats = [];
            let barcodeScanInFlight = false;
            let barcodeScanningAvailable = false;
            let ocrPreviewInFlight = false;
            let lastOcrPreviewAt = 0;
            let ocrCandidate = null;
            let ocrLines = [];

            const motionThresholdHigh = 15;
            const motionThresholdLow = 4;
            const framesToStabilize = 10;
            const ocrPreviewIntervalMs = 5000;
            const barcodeFormats = [
                'isbn',
                'ean_13',
                'ean_upc',
                'ean_8',
                'upc_a',
                'upc_e',
                'code_128',
                'code_39',
                'code_93',
                'itf',
                'itf_14',
                'codabar',
            ];

            const setStatus = (text, colorClass = 'bg-gray-900/80') => {
                statusBadge.className = `absolute left-4 top-4 rounded-full px-3 py-1 text-sm font-semibold text-white ${colorClass}`;
                statusBadge.textContent = text;
            };

            const setCameraHelp = (text = '') => {
                cameraHelp.textContent = text;
                cameraHelp.classList.toggle('hidden', text.length === 0);
            };

            const setFieldValue = (element, value) => {
                element.value = value;
                element.dispatchEvent(new Event('input', { bubbles: true }));
            };

            const setBarcodeValue = (value) => {
                const numericValue = String(value || '').replace(/\D/g, '');

                isbnBarcodeInput.value = numericValue;
                setFieldValue(fields.back.barcode, numericValue);
                fields.back.placeholder.textContent = numericValue || 'Waiting for code...';

                return numericValue;
            };

            const setActiveSide = (side) => {
                activeSide = side;
                sideLabel.textContent = fields[side].label;
                frontGuideBox.classList.toggle('hidden', side !== 'front');
                frontOcrPanel.classList.toggle('hidden', side !== 'front' && !fields.front.data.value);
                state = 'IDLE';
                stableFrames = 0;
                updateStability();
                setStatus(side === 'back' && barcodeScanningAvailable ? 'Scanning 1D code' : `Ready for ${fields[side].label}`);
            };

            const updateStability = () => {
                const percent = Math.min((stableFrames / framesToStabilize) * 100, 100);
                stabilityBar.style.width = `${percent}%`;
                stabilityPercent.textContent = `${Math.round(percent)}%`;
            };

            const refreshActions = () => {
                const hasFront = Boolean(fields.front.data.value);
                const hasBack = Boolean(fields.back.barcode.value);

                retakeFrontButton.disabled = !hasFront;
                retakeBackButton.disabled = !hasBack;
                submitCaptureButton.disabled = !(hasFront && hasBack);

                if (hasFront && !hasBack && activeSide === 'front') {
                    setActiveSide('back');
                }
            };

            const setOcrState = (text) => {
                frontOcrState.textContent = text;
            };

            const clearOcrCandidate = () => {
                ocrCandidate = null;
                ocrLines = [];
                titleSelectorContainer.classList.add('hidden');
                titleSlider.value = 0;
                acceptOcrButton.disabled = true;
                frontOcrTitlePreview.textContent = '-';
                frontOcrAuthorsPreview.textContent = '-';
                frontOcrRawPreview.textContent = '-';
                setFieldValue(fields.front.ocrTitle, '');
                setFieldValue(fields.front.ocrSubtitle, '');
                setFieldValue(fields.front.ocrAuthors, '');
                setFieldValue(fields.front.ocrPublisher, '');
                setFieldValue(fields.front.ocrText, '');
                setFieldValue(fields.front.ocrConfidence, '');
                setOcrState('Waiting for a stable cover frame');
            };

            const startLoop = () => {
                if (loopId) {
                    return;
                }

                loopId = window.setInterval(() => {
                    if (!stream) {
                        return;
                    }

                    analyzeFrame();
                }, 100);
            };

            const startCamera = async () => {
                if (!window.isSecureContext) {
                    setStatus('Camera blocked', 'bg-danger-600');
                    setCameraHelp('Camera access requires HTTPS on mobile browsers. Open this page through an HTTPS tunnel or a trusted local HTTPS URL.');
                    return;
                }

                if (!navigator.mediaDevices?.getUserMedia) {
                    setStatus('Camera unsupported', 'bg-danger-600');
                    setCameraHelp('This browser cannot access the camera from the current page context.');
                    return;
                }

                try {
                    stream = await navigator.mediaDevices.getUserMedia({
                        video: {
                            width: { ideal: 1280 },
                            height: { ideal: 960 },
                            facingMode: { ideal: 'environment' },
                        },
                        audio: false,
                    });

                    video.srcObject = stream;
                    manualCaptureButton.disabled = false;
                    setStatus(activeSide === 'back' && barcodeScanningAvailable ? 'Scanning 1D code' : `Ready for ${fields[activeSide].label}`);
                    startLoop();
                } catch (error) {
                    console.error(error);
                    setStatus('Camera unavailable', 'bg-danger-600');
                    setCameraHelp(error?.message || 'The browser denied or could not start the camera.');
                }
            };

            const analyzeFrame = () => {
                if (!video.videoWidth || !video.videoHeight || video.offsetParent === null) {
                    return;
                }

                mathContext.drawImage(video, 0, 0, mathCanvas.width, mathCanvas.height);
                const pixels = mathContext.getImageData(0, 0, mathCanvas.width, mathCanvas.height).data;
                const currentPixels = [];
                let totalDiff = 0;

                for (let index = 0; index < pixels.length; index += 4) {
                    const gray = (pixels[index] * 0.299) + (pixels[index + 1] * 0.587) + (pixels[index + 2] * 0.114);
                    currentPixels.push(gray);

                    if (previousPixels.length > 0) {
                        totalDiff += Math.abs(gray - previousPixels[index / 4]);
                    }
                }

                previousPixels = currentPixels;

                const motionScore = currentPixels.length > 0 ? totalDiff / currentPixels.length : 0;

                if (activeSide === 'back') {
                    scanBarcodeFrame(motionScore);

                    return;
                }

                handleStateMachine(motionScore);
            };

            const scanBarcodeFrame = (motionScore) => {
                if (!barcodeDetector || barcodeScanInFlight || state === 'CAPTURED') {
                    return;
                }

                if (motionScore > motionThresholdLow) {
                    state = 'MOVING';
                    stableFrames = 0;
                    updateStability();
                    setStatus('Hold 1D code steady', 'bg-warning-600');

                    return;
                }

                state = 'SCANNING_BARCODE';
                stableFrames = Math.min(stableFrames + 1, framesToStabilize);
                updateStability();
                setStatus('Scanning 1D code', 'bg-primary-600');

                barcodeScanInFlight = true;

                barcodeDetector.detect(video)
                    .then((barcodes) => {
                        const barcode = barcodes.find((detectedBarcode) => {
                            return detectedBarcode.rawValue && barcodeDetectorFormats.includes(detectedBarcode.format);
                        });

                        if (!barcode || activeSide !== 'back' || state === 'CAPTURED') {
                            return;
                        }

                        const barcodeValue = setBarcodeValue(barcode.rawValue);

                        setStatus(`1D code ${barcodeValue} captured`, 'bg-success-600');
                        state = 'CAPTURED';
                        stableFrames = 0;
                        updateStability();
                        refreshActions();
                    })
                    .catch((error) => {
                        console.error(error);
                        barcodeDetector = null;
                        barcodeScanningAvailable = false;
                        setStatus('Use manual capture', 'bg-warning-600');
                        setCameraHelp('1D barcode scanning is unavailable in this browser. Capture the code manually.');
                    })
                    .finally(() => {
                        barcodeScanInFlight = false;
                    });
            };

            const handleStateMachine = (motionScore) => {
                if (state === 'IDLE' && motionScore > motionThresholdHigh) {
                    state = 'MOVING';
                    setStatus('Movement detected', 'bg-warning-600');
                    return;
                }

                if (state !== 'MOVING' && state !== 'STABILIZING') {
                    return;
                }

                if (motionScore > motionThresholdLow) {
                    state = 'MOVING';
                    stableFrames = 0;
                    updateStability();
                    setStatus('Waiting to stabilize', 'bg-warning-600');
                    return;
                }

                state = 'STABILIZING';
                stableFrames += 1;
                updateStability();
                setStatus('Holding still', 'bg-primary-600');

                if (stableFrames >= framesToStabilize) {
                    requestFrontOcrPreview();
                }
            };

            const guideCrop = () => {
                return {
                    sourceX: Math.round(video.videoWidth * 0.18),
                    sourceY: Math.round(video.videoHeight * 0.08),
                    sourceWidth: Math.round(video.videoWidth * 0.64),
                    sourceHeight: Math.round(video.videoHeight * 0.84),
                };
            };

            const renderFrame = ({ cropGuide = false, maxDimension = 1600, quality = 0.75 } = {}) => {
                if (!video.videoWidth || !video.videoHeight) {
                    return null;
                }

                const crop = cropGuide
                    ? guideCrop()
                    : {
                        sourceX: 0,
                        sourceY: 0,
                        sourceWidth: video.videoWidth,
                        sourceHeight: video.videoHeight,
                    };

                let width = crop.sourceWidth;
                let height = crop.sourceHeight;

                if (width > maxDimension || height > maxDimension) {
                    if (width > height) {
                        height = Math.round((height * maxDimension) / width);
                        width = maxDimension;
                    } else {
                        width = Math.round((width * maxDimension) / height);
                        height = maxDimension;
                    }
                }

                snapshotCanvas.width = width;
                snapshotCanvas.height = height;
                snapshotContext.drawImage(
                    video,
                    crop.sourceX,
                    crop.sourceY,
                    crop.sourceWidth,
                    crop.sourceHeight,
                    0,
                    0,
                    width,
                    height,
                );

                return {
                    dataUrl: snapshotCanvas.toDataURL('image/jpeg', quality),
                    width,
                    height,
                };
            };

            const setCapturedImage = (side, frame, capturedStatus = null) => {
                const field = fields[side];

                setFieldValue(field.data, frame.dataUrl);
                setFieldValue(field.width, String(frame.width));
                setFieldValue(field.height, String(frame.height));

                field.preview.src = frame.dataUrl;
                field.preview.classList.remove('hidden');
                field.placeholder.classList.add('hidden');

                setStatus(capturedStatus || `${field.label} captured`, 'bg-success-600');
                state = 'CAPTURED';
                stableFrames = 0;
                updateStability();
                refreshActions();

                const sideCaptured = side;
                window.setTimeout(() => {
                    if (sideCaptured === 'back' || !stream) {
                        // Don't return to IDLE if we're done or stream lost
                        return;
                    }

                    if (activeSide === sideCaptured) {
                        setActiveSide('back');
                    }
                }, 350);
            };

            const captureSnapshot = (capturedStatus = null) => {
                if (activeSide === 'back') {
                    setStatus('Enter or scan 1D code', 'bg-primary-600');
                    isbnBarcodeInput.focus();

                    return;
                }

                const frame = renderFrame({ cropGuide: activeSide === 'front' });

                if (!frame) {
                    return;
                }

                setCapturedImage(activeSide, frame, capturedStatus);
            };

            const requestFrontOcrPreview = () => {
                if (ocrPreviewInFlight || activeSide !== 'front' || state === 'CAPTURED') {
                    return;
                }

                const now = Date.now();

                if (now - lastOcrPreviewAt < ocrPreviewIntervalMs) {
                    return;
                }

                const frame = renderFrame({ cropGuide: true, maxDimension: 900, quality: 0.72 });

                if (!frame) {
                    return;
                }

                ocrPreviewInFlight = true;
                lastOcrPreviewAt = now;
                setStatus('Reading cover text', 'bg-primary-600');
                setOcrState('Reading...');

                @this.previewFrontOcr(frame.dataUrl)
                    .then((result) => {
                        if (!result?.ok || activeSide !== 'front') {
                            setOcrState(result?.error || 'OCR could not read this frame');
                            setStatus('Adjust cover position', 'bg-warning-600');
                            return;
                        }

                        const metadata = result.metadata || {};
                        const authors = Array.isArray(metadata.authors)
                            ? metadata.authors.join(', ')
                            : (metadata.authors || '');

                        ocrCandidate = { frame, metadata, authors };
                        ocrLines = metadata.ocr_text
                            ? metadata.ocr_text.split(/\r\n|\r|\n/).map((line) => line.trim()).filter((line) => line.length > 0)
                            : (metadata.title ? [metadata.title] : []);

                        if (ocrLines.length > 1) {
                            titleSelectorContainer.classList.remove('hidden');
                            titleSlider.max = ocrLines.length - 1;
                            // Find the index of the detected title to set the slider initial position
                            const detectedTitleIndex = ocrLines.findIndex(l => l.toLowerCase() === (metadata.title || '').toLowerCase());
                            titleSlider.value = detectedTitleIndex !== -1 ? detectedTitleIndex : 0;
                        } else {
                            titleSelectorContainer.classList.add('hidden');
                        }

                        frontOcrTitlePreview.textContent = metadata.title || '-';
                        frontOcrAuthorsPreview.textContent = authors || '-';
                        frontOcrRawPreview.textContent = metadata.ocr_text || metadata.title || '-';
                        acceptOcrButton.disabled = !metadata.title;
                        setOcrState(metadata.title ? 'Reading available' : 'No title found');
                        setStatus(metadata.title ? 'Check title, then use reading' : 'Adjust cover position', metadata.title ? 'bg-success-600' : 'bg-warning-600');
                    })
                    .catch((error) => {
                        console.error(error);
                        setOcrState('OCR preview failed');
                        setStatus('OCR preview failed', 'bg-warning-600');
                    })
                    .finally(() => {
                        ocrPreviewInFlight = false;
                        stableFrames = 0;
                        updateStability();
                        if (state !== 'CAPTURED') {
                            state = 'MOVING';
                        }
                    });
            };

            const acceptOcrCandidate = () => {
                if (!ocrCandidate) {
                    return;
                }

                const { frame, metadata, authors } = ocrCandidate;

                setFieldValue(fields.front.ocrTitle, metadata.title || '');
                setFieldValue(fields.front.ocrSubtitle, metadata.subtitle || '');
                setFieldValue(fields.front.ocrAuthors, authors || '');
                setFieldValue(fields.front.ocrPublisher, metadata.publisher || '');
                setFieldValue(fields.front.ocrText, metadata.ocr_text || '');
                setFieldValue(fields.front.ocrConfidence, metadata.confidence ? String(metadata.confidence) : '');
                setCapturedImage('front', frame, 'Front OCR accepted');
            };

            titleSlider.addEventListener('input', (e) => {
                if (!ocrCandidate || ocrLines.length === 0) {
                    return;
                }

                const index = parseInt(e.target.value, 10);
                const selectedTitle = ocrLines[index] || '';

                ocrCandidate.metadata.title = selectedTitle;
                frontOcrTitlePreview.textContent = selectedTitle || '-';
                acceptOcrButton.disabled = !selectedTitle;
            });

            const retake = (side) => {
                const field = fields[side];

                if (field.data) {
                    setFieldValue(field.data, '');
                }
                if (field.width) {
                    setFieldValue(field.width, '');
                }
                if (field.height) {
                    setFieldValue(field.height, '');
                }
                if (field.barcode) {
                    setBarcodeValue('');
                }
                if (field.preview) {
                    field.preview.removeAttribute('src');
                    field.preview.classList.add('hidden');
                    field.placeholder.classList.remove('hidden');
                }
                if (side === 'front') {
                    clearOcrCandidate();
                }
                setActiveSide(side);
                refreshActions();
            };

            manualCaptureButton.addEventListener('click', () => captureSnapshot());
            isbnBarcodeInput.addEventListener('input', () => {
                setBarcodeValue(isbnBarcodeInput.value);
                refreshActions();
            });
            acceptOcrButton.addEventListener('click', acceptOcrCandidate);
            retakeFrontButton.addEventListener('click', () => retake('front'));
            retakeBackButton.addEventListener('click', () => retake('back'));

            window.addEventListener('reset-capture', () => {
                fields.front.preview.src = '';
                fields.front.preview.classList.add('hidden');
                fields.front.placeholder.classList.remove('hidden');
                setBarcodeValue('');
                clearOcrCandidate();

                state = 'IDLE';
                stableFrames = 0;
                updateStability();
                setActiveSide('front');
                refreshActions();
            });

            const initializeBarcodeDetector = async () => {
                if (window.kutubioBarcodeDetectorReady) {
                    await window.kutubioBarcodeDetectorReady;
                }

                if (!('BarcodeDetector' in window)) {
                    await new Promise((resolve) => {
                        window.addEventListener('kutubio-barcode-detector-ready', resolve, { once: true });
                        window.setTimeout(resolve, 2000);
                    });
                }

                if (!('BarcodeDetector' in window)) {
                    setCameraHelp('1D barcode scanning is unavailable in this browser. Capture the code manually.');

                    return;
                }

                try {
                    let supportedFormats = barcodeFormats;

                    if (typeof BarcodeDetector.getSupportedFormats === 'function') {
                        const browserFormats = await BarcodeDetector.getSupportedFormats();
                        supportedFormats = barcodeFormats.filter((format) => browserFormats.includes(format));
                    }

                    if (supportedFormats.length === 0) {
                        setCameraHelp('This browser does not support 1D barcode scanning. Capture the code manually.');

                        return;
                    }

                    barcodeDetectorFormats = supportedFormats;
                    barcodeDetector = new BarcodeDetector({ formats: supportedFormats });
                    barcodeScanningAvailable = true;
                } catch (error) {
                    console.error(error);
                    barcodeDetector = null;
                    barcodeDetectorFormats = [];
                    barcodeScanningAvailable = false;
                    setCameraHelp('1D barcode scanning is unavailable in this browser. Capture the code manually.');
                }
            };

            initializeBarcodeDetector().finally(() => {
                setActiveSide('front');
                refreshActions();
                startCamera();
            });
        })();
    </script>
</x-filament-panels::page>
