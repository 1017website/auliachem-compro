(() => {
    const bindings = JSON.parse(document.getElementById('cms-bindings').textContent);
    const nodes = new Map(Object.entries(bindings).map(([key, binding]) => [key, document.evaluate(binding.path, document, null, XPathResult.FIRST_ORDERED_NODE_TYPE, null).singleNodeValue]));
    const outline = document.createElement('div');
    outline.style.cssText = 'position:absolute;pointer-events:none;border:3px solid #1386dd;box-shadow:0 0 0 4px #1386dd22;z-index:9999;display:none;border-radius:3px;';
    document.body.append(outline);
    let selected = null;
    let target = null;
    const locate = () => {
        if (!target || !target.isConnected) { outline.style.display = 'none'; return; }
        const rect = target.getBoundingClientRect();
        if (!rect.width || !rect.height) { outline.style.display = 'none'; return; }
        Object.assign(outline.style, {display:'block',left:`${rect.left + scrollX}px`,top:`${rect.top + scrollY}px`,width:`${rect.width}px`,height:`${rect.height}px`});
    };
    const safeImage = value => /^(https?:\/\/|\/[^/]|data:image\/(png|jpeg|webp|x-icon|vnd.microsoft.icon);base64,)/i.test(value);
    const sectionTarget = section => document.getElementById(section) || (section === 'footer' ? document.querySelector('footer') : section === 'solution-strip' ? document.querySelector('.solution-strip') : document.querySelector('#home'));
    const ready = () => parent.postMessage({type:'cms:ready'}, location.origin);
    window.addEventListener('message', event => {
        if (event.origin !== location.origin || event.source !== parent) return;
        const data = event.data;
        if (data?.type === 'cms:hello') { ready(); return; }
        if (data?.type !== 'cms:update') return;
        for (const [key, value] of Object.entries(data.values || {})) {
            const binding = bindings[key];
            const node = nodes.get(key);
            if (!binding || !node || typeof value !== 'string') continue;
            if (binding.attribute === 'background' && safeImage(value)) {
                const element = document.querySelector(binding.selector);
                if (element) element.style.backgroundImage = getComputedStyle(element).backgroundImage.replace(/url\((?:"[^"]*"|'[^']*'|[^)]*)\)/g, () => `url(${JSON.stringify(value)})`);
            } else if (binding.attribute === 'src' || key === 'brand_favicon') {
                if (safeImage(value)) node.setAttribute(binding.attribute, value);
            } else if (binding.attribute === 'href') {
                if (/^(https?:\/\/|\/[^/]|#|mailto:|tel:)/i.test(value)) node.setAttribute('href', value);
            } else if (binding.attribute) {
                node.setAttribute(binding.attribute, value);
            } else {
                node.nodeValue = value;
            }
        }
        const key = data.selected;
        const binding = bindings[key];
        if (binding) {
            const node = nodes.get(key);
            target = binding.selector ? document.querySelector(binding.selector) : node?.nodeType === Node.TEXT_NODE ? node.parentElement : node;
            if (target?.closest('head')) {
                target = null;
            }
        } else target = sectionTarget(data.section);
        parent.postMessage({type:'cms:metadata', title:document.title, description:document.querySelector('meta[name="description"]')?.content}, location.origin);
        if (data.scroll) {
            const destination = target || sectionTarget(data.section);
            const top = destination.getBoundingClientRect().top + scrollY - 110;
            window.scrollTo({top:Math.max(0,top),behavior:'instant'});
        }
        selected = key;
        requestAnimationFrame(locate);
    });
    document.addEventListener('click', event => {
        const language = event.target.closest('[data-lang]');
        if (language) { event.preventDefault(); event.stopImmediatePropagation(); parent.postMessage({type:'cms:language',locale:language.dataset.lang}, location.origin); }
        const link = event.target.closest('a');
        if (link) {
            event.preventDefault();
            const href = link.getAttribute('href');
            if (href?.startsWith('#')) document.getElementById(href.slice(1))?.scrollIntoView({behavior:'instant'});
        }
    }, true);
    window.addEventListener('resize', locate);
    window.addEventListener('scroll', locate, {passive:true});
    document.addEventListener('load', locate, true);
    new ResizeObserver(locate).observe(document.body);
    document.fonts.ready.then(locate);
    ready();
})();
