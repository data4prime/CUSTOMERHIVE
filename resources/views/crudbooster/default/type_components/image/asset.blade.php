<script>
// Campo "immagine": anteprima immediata della nuova scelta (delegato, vale per ogni campo).
document.addEventListener('change', function (e) {
    var input = e.target;
    if (!input.matches || !input.matches('input[data-ch-image-input]')) { return; }
    var root = input.closest('[data-ch-image]');
    if (!root) { return; }
    var img = root.querySelector('[data-ch-image-img]');
    var ph = root.querySelector('[data-ch-image-ph]');
    var fname = root.querySelector('[data-ch-image-fname]');
    var file = input.files && input.files[0];
    if (file && /^image\//.test(file.type)) {
        if (img.dataset.chObjUrl) { URL.revokeObjectURL(img.dataset.chObjUrl); }
        img.dataset.chObjUrl = URL.createObjectURL(file);
        img.src = img.dataset.chObjUrl;
        img.hidden = false;
        if (ph) { ph.hidden = true; }
    }
    if (fname) {
        fname.textContent = file ? file.name : '';
        fname.hidden = !file;
    }
});
</script>
