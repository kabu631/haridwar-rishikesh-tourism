/**
 * Click-to-play YouTube: a static thumbnail until the visitor asks for the
 * video, so the ~1 MB player never slows down page load (LCP / INP).
 */
export function initYouTube() {
    document.addEventListener('click', (event) => {
        const facade = event.target.closest('[data-youtube]');

        if (!facade) {
            return;
        }

        event.preventDefault();

        const iframe = document.createElement('iframe');
        iframe.src = `https://www.youtube-nocookie.com/embed/${facade.dataset.youtube}?autoplay=1&rel=0`;
        iframe.title = facade.getAttribute('aria-label') || 'YouTube video';
        iframe.allow = 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture';
        iframe.allowFullscreen = true;
        iframe.className = 'aspect-video w-full rounded-2xl border-0';

        facade.replaceWith(iframe);
    });
}
