// qrvalidation.js

document.addEventListener('DOMContentLoaded', function() {
    const courseId = document.getElementById('course-id').value;
    const processQrUrl = M.cfg.wwwroot + '/local/qrcompletion/process_qr.php';

    function onScanSuccess(decodedText, decodedResult) {
        console.log(`Scan result: ${decodedText}`);
        // Send the scanned QR code to the server for validation.
        const xhr = new XMLHttpRequest();
        xhr.open('POST', processQrUrl, true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onload = function() {
            if (xhr.status === 200) {
                document.getElementById('qr-reader-results').innerHTML = xhr.responseText;
                console.log('Server response:', xhr.responseText); // Debugging message
            } else {
                document.getElementById('qr-reader-results').innerHTML = 
                    'An error occurred while validating the QR code. Status: ' + xhr.status + ', Response: ' + xhr.responseText;
                console.log('Validation error:', xhr.status, xhr.responseText); // Debugging message
            }
        };
        xhr.send('qrcode=' + encodeURIComponent(decodedText) + '&courseid=' + encodeURIComponent(courseId));
    }

    let html5QrcodeScanner = new Html5QrcodeScanner(
        'qr-reader', {
            fps: 10,
            qrbox: { width: 250, height: 250 },
            rememberLastUsedCamera: true,
            supportedScanTypes: [Html5QrcodeScanType.SCAN_TYPE_CAMERA]
        }
    );
    html5QrcodeScanner.render(onScanSuccess);

    // Remove the information icon immediately.
    const infoIcon = document.querySelector('img[alt="Info icon"]');
    if (infoIcon) {
        infoIcon.style.display = 'none';
    }
});
