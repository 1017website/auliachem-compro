const sidebar = document.querySelector('#cms-sidebar');
const menuButton = document.querySelector('[data-open-sidebar]');
const scrim = document.querySelector('.sidebar-scrim');
const narrowNavigation = matchMedia('(max-width:850px)');
const syncSidebar = () => {
    if (sidebar) sidebar.inert = narrowNavigation.matches && !document.body.classList.contains('sidebar-open');
};
const setSidebar = open => {
    document.body.classList.toggle('sidebar-open', open);
    menuButton?.setAttribute('aria-expanded', String(open));
    if (scrim) scrim.hidden = !open;
    syncSidebar();
    if (open) sidebar?.querySelector('a')?.focus();
    else menuButton?.focus();
};
narrowNavigation.addEventListener('change', () => {
    if (!narrowNavigation.matches) {
        document.body.classList.remove('sidebar-open');
        if (scrim) scrim.hidden = true;
        menuButton?.setAttribute('aria-expanded', 'false');
    }
    syncSidebar();
});
syncSidebar();
menuButton?.addEventListener('click', () => setSidebar(true));
document.querySelectorAll('[data-close-sidebar]').forEach(button => button.addEventListener('click', () => setSidebar(false)));
document.addEventListener('keydown', event => {
    if (event.key === 'Escape') {
        if (document.body.classList.contains('sidebar-open')) setSidebar(false);
        document.querySelector('.editor')?.classList.remove('preview-open');
    }
    if (event.key === 'Tab' && document.body.classList.contains('sidebar-open')) {
        const controls = [...sidebar.querySelectorAll('a,button')].filter(control => control.offsetParent !== null);
        if (event.shiftKey && document.activeElement === controls[0]) { event.preventDefault(); controls.at(-1).focus(); }
        else if (!event.shiftKey && document.activeElement === controls.at(-1)) { event.preventDefault(); controls[0].focus(); }
    }
});

const editor = document.querySelector('[data-editor]');
if (editor) {
    const studio = document.querySelector('.editor');
    const frame = document.querySelector('[data-preview-frame]');
    const viewport = document.querySelector('.preview-viewport');
    const canvas = document.querySelector('.preview-canvas');
    const loading = document.querySelector('[data-preview-loading]');
    const caption = document.querySelector('[data-preview-caption]');
    const language = document.querySelector('[data-language]');
    const status = document.querySelector('[data-save-status]');
    const cards = [...editor.querySelectorAll('[data-field-key]')];
    let dirty = false;
    let ready = false;
    let selected = null;
    let device = 'desktop';
    let loadingTimer;
    const pendingFiles = new Map();
    const markDirty = () => { dirty = true; status.textContent = 'Perubahan belum disimpan'; };
    const resizePreview = () => {
        const width = device === 'mobile' ? 390 : 1280;
        const available = viewport.clientWidth;
        if (!available) return;
        const scale = Math.min(1, available / width);
        canvas.style.width = `${width * scale}px`;
        canvas.style.height = `${viewport.clientHeight}px`;
        frame.style.width = `${width}px`;
        frame.style.height = `${viewport.clientHeight / scale}px`;
        frame.style.transform = `scale(${scale})`;
    };
    new ResizeObserver(resizePreview).observe(viewport);
    const sendPreview = (scroll = false) => {
        if (!ready) return;
        const values = {};
        for (const card of cards) {
            const input = card.querySelector(`[data-value-lang="${language.value}"]`) || card.querySelector('[data-value-lang="shared"]');
            if (input) values[card.dataset.fieldKey] = pendingFiles.get(card.dataset.fieldKey) || input.value;
        }
        if (values.brand_favicon && /^(https?:\/\/|\/[^/]|data:image\/)/i.test(values.brand_favicon)) document.querySelector('[data-preview-favicon]').src = values.brand_favicon;
        frame.contentWindow.postMessage({type:'cms:update', values, selected, scroll, section:studio.dataset.section}, location.origin);
    };
    const selectCard = (card, scroll = true) => {
        if (!card) return;
        selected = card.dataset.fieldKey;
        cards.forEach(item => item.classList.toggle('is-active', item === card));
        caption.textContent = card.dataset.fieldKey === 'brand_favicon'
            ? 'Favicon tampil pada tab browser. Lihat contoh ikon di kartu upload.'
            : `Sedang diedit: ${card.dataset.fieldLabel}`;
        sendPreview(scroll);
    };
    const togglePreview = () => {
        if (matchMedia('(max-width:1199px)').matches) {
            studio.classList.toggle('preview-open');
        } else {
            studio.classList.toggle('preview-collapsed');
        }
        resizePreview();
        sendPreview(true);
    };
    document.querySelectorAll('[data-toggle-preview]').forEach(button => button.addEventListener('click', togglePreview));
    const beginLoading = () => {
        ready = false;
        loading.hidden = false;
        loading.textContent = 'Memuat preview…';
        clearTimeout(loadingTimer);
        loadingTimer = setTimeout(() => {
            if (!ready) {
                loading.replaceChildren(document.createTextNode('Preview belum dapat dimuat. '));
                const retry = document.createElement('button');
                retry.type = 'button'; retry.className = 'secondary'; retry.textContent = 'Coba lagi';
                retry.addEventListener('click', () => { beginLoading(); frame.src = frame.src; });
                loading.append(retry);
            }
        }, 15000);
    };
    frame.addEventListener('load', () => {
        resizePreview();
        frame.contentWindow.postMessage({type:'cms:hello'}, location.origin);
    });
    window.addEventListener('message', event => {
        if (event.origin !== location.origin || event.source !== frame.contentWindow) return;
        if (event.data?.type === 'cms:ready') {
            ready = true; loading.hidden = true; clearTimeout(loadingTimer); resizePreview(); sendPreview(true);
        }
        if (event.data?.type === 'cms:language' && ['id','en','zh'].includes(event.data.locale)) {
            language.value = event.data.locale; changeLanguage();
        }
        if (event.data?.type === 'cms:metadata') {
            const title = document.querySelector('[data-seo-title]');
            const description = document.querySelector('[data-seo-description]');
            if (title) title.textContent = event.data.title;
            if (description) description.textContent = event.data.description;
        }
    });
    const changeLanguage = () => {
        document.querySelectorAll('[data-locale]').forEach(field => { field.hidden = field.dataset.locale !== language.value; });
        beginLoading();
        const url = new URL(frame.src);
        url.searchParams.set('lang', language.value);
        frame.src = url.href;
    };
    language.addEventListener('change', changeLanguage);
    document.querySelectorAll('[data-locale]').forEach(field => { field.hidden = field.dataset.locale !== language.value; });
    document.querySelectorAll('[data-device]').forEach(button => button.addEventListener('click', () => {
        device = button.dataset.device;
        document.querySelectorAll('[data-device]').forEach(item => {
            item.classList.toggle('active', item === button);
            item.setAttribute('aria-pressed', String(item === button));
        });
        resizePreview(); sendPreview(true);
    }));
    for (const card of cards) {
        card.addEventListener('focusin', () => { if (selected !== card.dataset.fieldKey) selectCard(card); });
        card.querySelector('[data-locate]').addEventListener('click', () => {
            selectCard(card);
            if (matchMedia('(max-width:1199px)').matches) studio.classList.add('preview-open');
            else studio.classList.remove('preview-collapsed');
            resizePreview(); sendPreview(true);
        });
        const upload = card.querySelector('[data-upload]');
        if (!upload) continue;
        const zone = card.querySelector('[data-dropzone]');
        const feedback = card.querySelector('[data-upload-feedback]');
        const image = card.querySelector('[data-image-preview]');
        const cancel = card.querySelector('[data-cancel-upload]');
        const input = card.querySelector('[data-value-lang="shared"]');
        const cancelUpload = () => {
            upload.value = ''; pendingFiles.delete(card.dataset.fieldKey);
            image.src = input.value; cancel.hidden = true; feedback.textContent = ''; feedback.classList.remove('is-error');
            sendPreview();
        };
        cancel.addEventListener('click', () => { cancelUpload(); markDirty(); });
        const acceptFile = () => {
            const file = upload.files[0];
            if (!file) { cancelUpload(); return; }
            const allowed = ['image/jpeg','image/png','image/webp'];
            if (card.dataset.fieldKey === 'brand_favicon') allowed.push('image/x-icon','image/vnd.microsoft.icon');
            if (file.size > 2 * 1024 * 1024 || !allowed.includes(file.type)) {
                cancelUpload(); feedback.textContent = 'File tidak sesuai. Pilih gambar yang didukung, maksimal 2 MB.'; feedback.classList.add('is-error'); return;
            }
            const reader = new FileReader();
            reader.onload = () => {
                if (upload.files[0] !== file) return;
                pendingFiles.set(card.dataset.fieldKey, reader.result); image.src = reader.result;
                feedback.textContent = `${file.name} · ${Math.round(file.size / 1024)} KB · Siap disimpan`;
                feedback.classList.remove('is-error'); cancel.hidden = false; markDirty(); selectCard(card); sendPreview();
            };
            reader.onerror = () => { feedback.textContent = 'File tidak dapat dibaca. Pilih ulang gambar.'; feedback.classList.add('is-error'); };
            reader.readAsDataURL(file);
        };
        upload.addEventListener('change', acceptFile);
        zone.addEventListener('dragover', event => { event.preventDefault(); zone.classList.add('is-dragging'); });
        zone.addEventListener('dragleave', () => zone.classList.remove('is-dragging'));
        zone.addEventListener('drop', event => {
            event.preventDefault(); zone.classList.remove('is-dragging');
            if (event.dataTransfer.files.length) {
                const transfer = new DataTransfer(); transfer.items.add(event.dataTransfer.files[0]); upload.files = transfer.files; acceptFile();
            }
        });
        input.addEventListener('input', () => { if (!pendingFiles.has(card.dataset.fieldKey)) image.src = input.value; });
    }
    editor.addEventListener('input', event => {
        if (event.target === language || event.target.type === 'file') return;
        markDirty(); selectCard(event.target.closest('[data-field-key]'), false); sendPreview();
    });
    window.addEventListener('beforeunload', event => { if (dirty) { event.preventDefault(); event.returnValue = ''; } });
    editor.addEventListener('submit', () => {
        dirty = false;
        const button = editor.querySelector('button[type=submit]');
        button.disabled = true; button.textContent = 'Menyimpan…';
    });
    beginLoading();
    frame.contentWindow?.postMessage({type:'cms:hello'}, location.origin);
}
