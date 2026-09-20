const nav = document.getElementById('navbar');
const menu = document.getElementById('menu');
const button = document.getElementById('menuBtn');
const closeMenu = () => {
    menu.classList.remove('mobile');
    button.setAttribute('aria-expanded', 'false');
};
window.addEventListener('scroll', () => nav.classList.toggle('scrolled', window.scrollY > 20), { passive: true });
button.addEventListener('click', () => {
    button.setAttribute('aria-expanded', String(menu.classList.toggle('mobile')));
});
document.querySelectorAll('#menu a').forEach(link => link.addEventListener('click', closeMenu));
document.addEventListener('keydown', event => {
    if (event.key === 'Escape' && menu.classList.contains('mobile')) {
        closeMenu();
        button.focus();
    }
});
document.querySelectorAll('[data-lang]').forEach(button => button.addEventListener('click', () => {
    const url = new URL(window.location.href);
    url.searchParams.set('lang', button.dataset.lang);
    window.location.assign(url);
}));
document.querySelectorAll('.reveal').forEach(element => element.classList.add('show'));
document.querySelectorAll('a[href^="mailto:"],a[href^="tel:"],a[href*="wa.me"],a[href*="whatsapp.com"]').forEach(link => link.addEventListener('click', () => {
    const type = link.href.startsWith('mailto:') ? 'email_click' : link.href.startsWith('tel:') ? 'phone_click' : 'whatsapp_click';
    const locale = new URL(window.location.href).searchParams.get('lang') || 'id';
    navigator.sendBeacon('/track', new Blob([new URLSearchParams({type, locale}).toString()], {type:'application/x-www-form-urlencoded'}));
}));
