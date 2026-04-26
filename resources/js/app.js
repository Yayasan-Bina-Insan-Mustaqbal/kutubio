import { BarcodeDetector as BarcodeDetectorPonyfill } from 'barcode-detector/ponyfill';

window.BarcodeDetector = BarcodeDetectorPonyfill;
window.kutubioBarcodeDetectorReady = Promise.resolve(BarcodeDetectorPonyfill);

window.dispatchEvent(new CustomEvent('kutubio-barcode-detector-ready'));
