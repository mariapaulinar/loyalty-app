import { BrowserQRCodeReader } from '@zxing/browser';
import { NotFoundException } from '@zxing/library';

// Function to initialize QR code scanning
function initQRCodeScanning() {
    // Create a new instance of BrowserQRCodeReader
    const codeReader = new BrowserQRCodeReader();

    // Track active scanning state
    let activeControls = null;
    let activeStream = null;
    let isScanning = false;

    // Get all elements with class 'scan-qr'
    const qrElements = document.querySelectorAll('.scan-qr');

    // Add click event listener to each element
    qrElements.forEach(qrElement => {
        qrElement.addEventListener('click', async function () {
            // If already scanning, stop the scanner
            if (isScanning) {
                stopScanning(qrElement);
                return;
            }

            // Ensure the application is served over HTTPS
            if (window.location.protocol !== 'https:') {
                alert(_lang.scanner_https_camera_notification);
                return;
            }

            try {
                // Check if the device supports scanning a QR code
                const devices = await navigator.mediaDevices.enumerateDevices();
                const videoInputDevices = devices.filter(device => device.kind === 'videoinput');

                if (videoInputDevices.length === 0) {
                    alert(_lang.no_scanner_notification);
                    return;
                }

                // Get the video element
                const video = document.getElementById('video');

                // Show the video element
                video.style.display = '';

                // Get all elements with class 'hide-on-scan'
                const hideOnScanElements = document.querySelectorAll('.hide-on-scan');

                // Try to get the back camera using the facingMode constraint
                navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } })
                    .then(stream => {
                        handleStream(stream);
                    })
                    .catch(error => {
                        console.warn("Couldn't access the back camera:", error);

                        // If failed, try to access the default camera without specifying the facingMode
                        navigator.mediaDevices.getUserMedia({ video: true })
                            .then(stream => {
                                handleStream(stream);
                            })
                            .catch(console.error);
                    });

                function handleStream(stream) {
                    activeStream = stream;
                    video.srcObject = stream;
                    video.play();

                    // Hide all elements with class 'hide-on-scan'
                    hideOnScanElements.forEach(el => el.style.display = 'none');

                    // Switch button to "scanning" state
                    setScanningState(qrElement, true);

                    // Start decoding from the video element
                    codeReader.decodeFromVideoElement(video, (result, error, controls) => {
                        // Store controls reference so we can stop later
                        activeControls = controls;

                        if (result) {
                            // If a QR code is found, verify it is a URL and then redirect to that URL
                            if (isValidURL(result.getText())) {
                                // Get the element with id 'code-found' and remove the 'hidden' class
                                const codeFound = document.getElementById('code-found');
                                if (codeFound) codeFound.classList.remove('hidden');

                                // Stop scanning and clean up
                                stopScanning(qrElement);

                                window.location.href = result.getText();
                            }
                        }

                        if (error && !(error instanceof NotFoundException)) {
                            console.error(error);
                            stopScanning(qrElement);
                        }                        
                    }).then(controls => {
                        // Also capture controls from the promise for immediate availability
                        activeControls = controls;
                    }).catch(console.error);
                }

            } catch (error) {
                console.error(error);
            }
        });
    });

    function stopScanning(buttonElement) {
        const video = document.getElementById('video');
        const hideOnScanElements = document.querySelectorAll('.hide-on-scan');
        const cameraPlaceholder = document.getElementById('camera-placeholder');

        // Stop the code reader
        if (activeControls) {
            activeControls.stop();
            activeControls = null;
        }

        // Stop the camera stream
        if (activeStream) {
            activeStream.getTracks().forEach(track => track.stop());
            activeStream = null;
        }

        // Reset the video element
        if (video) {
            video.srcObject = null;
            video.style.display = '';
            video.classList.remove('active');
        }

        // Show the camera placeholder again
        if (cameraPlaceholder) {
            cameraPlaceholder.style.display = '';
        }

        // Show all elements with class 'hide-on-scan'
        hideOnScanElements.forEach(el => el.style.display = '');

        // Switch button back to "idle" state
        setScanningState(buttonElement, false);
    }

    function setScanningState(buttonElement, scanning) {
        isScanning = scanning;

        const labelEl = buttonElement.querySelector('[data-scan-label]');
        const iconEl = buttonElement.querySelector('[data-scan-icon]');
        const stopIconEl = buttonElement.querySelector('[data-stop-icon]');

        if (scanning) {
            buttonElement.classList.add('scanning');
            if (labelEl) labelEl.textContent = labelEl.dataset.scanningText || 'Scanning…';
            if (iconEl) iconEl.style.display = 'none';
            if (stopIconEl) stopIconEl.style.display = '';
        } else {
            buttonElement.classList.remove('scanning');
            if (labelEl) labelEl.textContent = labelEl.dataset.idleText || 'Scan QR Code';
            if (iconEl) iconEl.style.display = '';
            if (stopIconEl) stopIconEl.style.display = 'none';
        }
    }
}

// Function to check if a string is a valid URL
function isValidURL(string) {
    try {
        new URL(string);
        return true;
    } catch (_) {
        return false;
    }
}

// Initialize QR code scanning
initQRCodeScanning();
