/**
 * Google Ads / GA4 tag loaded after the page becomes idle (or on the first
 * interaction), so tracking never competes with LCP or input responsiveness.
 */
export function initAnalytics() {
    const config = document.querySelector('meta[name="analytics-ids"]')?.content;

    if (!config) {
        return;
    }

    const ids = config.split(',').map((id) => id.trim()).filter(Boolean);

    if (!ids.length) {
        return;
    }

    window.dataLayer = window.dataLayer || [];
    window.gtag = function gtag() {
        window.dataLayer.push(arguments);
    };
    window.gtag('js', new Date());
    ids.forEach((id) => window.gtag('config', id));

    let loaded = false;
    const load = () => {
        if (loaded) return;
        loaded = true;
        const script = document.createElement('script');
        script.async = true;
        script.src = `https://www.googletagmanager.com/gtag/js?id=${encodeURIComponent(ids[0])}`;
        document.head.append(script);
    };

    ['pointerdown', 'keydown', 'scroll'].forEach((type) => window.addEventListener(type, load, { once: true, passive: true }));

    if ('requestIdleCallback' in window) {
        window.addEventListener('load', () => requestIdleCallback(load, { timeout: 3500 }), { once: true });
    } else {
        window.addEventListener('load', () => setTimeout(load, 2500), { once: true });
    }
}
