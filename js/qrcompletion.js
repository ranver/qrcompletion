// qrcompletion.js

document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('qr-modal');
    const modalContent = document.getElementById('modal-content');
    const qrCode = document.getElementById('qrcode');
    const span = document.getElementsByClassName('close')[0];

    qrCode.onclick = function() {
        const qrCodeSrc = qrCode.src;
        const spinnerHtml = '<div class="spinner"></div>';
        modalContent.innerHTML = '<div style="position: relative;"><img src="' + qrCodeSrc + '" alt="QR Code" style="width: 100%;">' + spinnerHtml + '</div>';
        modal.style.display = 'flex';
        if (window.brightness && typeof window.brightness.setBrightness === 'function') {
            window.brightness.setBrightness(1.0); // Set brightness to maximum if supported
        }
    }

    span.onclick = function() {
        modal.style.display = 'none';
    }

    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    }
});
