(function () {
    'use strict';

    var banner = document.getElementById('connectivity-banner');
    if (!banner) {
        return;
    }

    var onlineText = banner.dataset.online || 'Back online';
    var offlineText = banner.dataset.offline || 'You are offline — upload when connected';
    var slowText = banner.dataset.slow || 'Slow connection — uploads may take longer';

    function setState(state, message) {
        banner.hidden = state === 'online';
        banner.className = 'connectivity-banner connectivity-' + state;
        banner.textContent = message;
        document.body.classList.toggle('is-offline', state === 'offline');
    }

    function updateOnline() {
        setState('online', onlineText);
    }

    function updateOffline() {
        setState('offline', offlineText);
    }

    window.addEventListener('online', updateOnline);
    window.addEventListener('offline', updateOffline);

    if (!navigator.onLine) {
        updateOffline();
    }

    if (navigator.connection && navigator.connection.addEventListener) {
        navigator.connection.addEventListener('change', function () {
            var conn = navigator.connection;
            if (!navigator.onLine) {
                updateOffline();
            } else if (conn.effectiveType === 'slow-2g' || conn.effectiveType === '2g') {
                setState('slow', slowText);
            } else {
                updateOnline();
            }
        });
    }

    document.querySelectorAll('[data-requires-online]').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!navigator.onLine) {
                event.preventDefault();
                updateOffline();
            }
        });
    });

    var uploadForm = document.getElementById('csv-upload-form');
    if (!uploadForm || !window.FormData) {
        return;
    }

    var progressWrap = document.getElementById('upload-progress');
    var progressBar = document.getElementById('upload-progress-bar');
    var statusEl = document.getElementById('upload-status');
    var submitBtn = uploadForm.querySelector('[type="submit"]');

    uploadForm.addEventListener('submit', function (event) {
        if (!navigator.onLine) {
            event.preventDefault();
            updateOffline();
            return;
        }

        if (uploadForm.dataset.enhanced === '1') {
            return;
        }

        event.preventDefault();
        uploadForm.dataset.enhanced = '1';

        var fileInput = uploadForm.querySelector('input[type="file"]');
        if (!fileInput || !fileInput.files || !fileInput.files.length) {
            uploadForm.submit();
            return;
        }

        var formData = new FormData(uploadForm);
        var xhr = new XMLHttpRequest();
        xhr.open('POST', uploadForm.action, true);

        if (progressWrap) {
            progressWrap.hidden = false;
        }
        if (submitBtn) {
            submitBtn.disabled = true;
        }
        if (statusEl) {
            statusEl.textContent = statusEl.dataset.uploading || 'Uploading…';
        }

        xhr.upload.addEventListener('progress', function (e) {
            if (!progressBar || !e.lengthComputable) {
                return;
            }
            var pct = Math.round((e.loaded / e.total) * 100);
            progressBar.style.width = pct + '%';
            progressBar.setAttribute('aria-valuenow', String(pct));
        });

        xhr.addEventListener('load', function () {
            if (xhr.status >= 200 && xhr.status < 400) {
                document.open();
                document.write(xhr.responseText);
                document.close();
                return;
            }
            if (statusEl) {
                statusEl.textContent = statusEl.dataset.failed || 'Upload failed';
            }
            if (submitBtn) {
                submitBtn.disabled = false;
            }
            uploadForm.dataset.enhanced = '';
        });

        xhr.addEventListener('error', function () {
            if (statusEl) {
                statusEl.textContent = statusEl.dataset.retry || 'Upload failed — tap to retry';
            }
            if (submitBtn) {
                submitBtn.disabled = false;
            }
            uploadForm.dataset.enhanced = '';
        });

        xhr.send(formData);
    });
})();
