/* Campus Lost & Found Portal - small helper script */
document.addEventListener('DOMContentLoaded', function () {

    // ---- Mobile menu button ----
    var toggle = document.getElementById('navToggle');
    var nav = document.getElementById('mainNav');
    if (toggle && nav) {
        toggle.addEventListener('click', function () {
            var open = nav.classList.toggle('open');
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
    }

    // ---- Ask "Are you sure?" before dangerous buttons (forms with data-confirm) ----
    document.querySelectorAll('form[data-confirm]').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!window.confirm(form.getAttribute('data-confirm'))) {
                event.preventDefault();
            }
        });
    });

    // ---- Full-screen image viewer (lightbox) ----
    var overlay = document.createElement('div');
    overlay.className = 'lightbox';
    overlay.setAttribute('role', 'dialog');
    overlay.setAttribute('aria-modal', 'true');
    overlay.innerHTML = '<button type="button" class="lightbox-close" aria-label="Close">&times;</button>' +
                        '<img class="lightbox-img" alt="Full size photo">';
    document.body.appendChild(overlay);
    var bigImage = overlay.querySelector('.lightbox-img');

    function openLightbox(src, altText) {
        bigImage.src = src;
        bigImage.alt = altText || 'Full size photo';
        overlay.classList.add('open');
        document.body.classList.add('no-scroll');
    }
    function closeLightbox() {
        overlay.classList.remove('open');
        document.body.classList.remove('no-scroll');
        bigImage.removeAttribute('src');
    }
    // Click the X or anywhere outside the picture -> close.
    overlay.addEventListener('click', function (event) {
        if (event.target !== bigImage) { closeLightbox(); }
    });
    // ESC key -> close.
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && overlay.classList.contains('open')) { closeLightbox(); }
    });
    // Any element with data-lightbox="image-url" opens the viewer.
    document.addEventListener('click', function (event) {
        var trigger = event.target.closest('[data-lightbox]');
        if (trigger) {
            event.preventDefault();
            var img = trigger.querySelector('img');
            openLightbox(trigger.getAttribute('data-lightbox'), img ? img.alt : '');
        }
    });

    // ---- Photo picker: size check + preview (the server checks again) ----
    var fileInput = document.querySelector('.image-input');
    var preview = document.getElementById('imagePreview');
    if (fileInput) {
        fileInput.addEventListener('change', function () {
            var file = fileInput.files[0];
            if (!file) { if (preview) { preview.hidden = true; } return; }
            if (file.size > 5 * 1024 * 1024) {
                alert('This image is larger than 5 MB. Please choose a smaller one.');
                fileInput.value = '';
                if (preview) { preview.hidden = true; }
                return;
            }
            if (preview && window.URL) {
                preview.src = URL.createObjectURL(file);
                preview.hidden = false;
            }
        });
    }
});
