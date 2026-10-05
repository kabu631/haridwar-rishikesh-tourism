/**
 * Share buttons: native share sheet where supported, copy-link fallback.
 * (Plain share links render server-side, so no third-party scripts load.)
 */
export function initShare() {
    document.querySelectorAll('[data-share-native]').forEach((button) => {
        if (!navigator.share) {
            return;
        }

        button.hidden = false;
        button.addEventListener('click', () => {
            navigator.share({ title: document.title, url: button.dataset.url || window.location.href }).catch(() => {});
        });
    });

    document.querySelectorAll('[data-copy-link]').forEach((button) => {
        button.addEventListener('click', async () => {
            try {
                await navigator.clipboard.writeText(button.dataset.url || window.location.href);
                const label = button.querySelector('[data-label]');
                const original = label?.textContent;
                if (label) label.textContent = 'Link copied';
                setTimeout(() => label && (label.textContent = original), 2000);
            } catch {
                window.prompt('Copy this link', button.dataset.url || window.location.href);
            }
        });
    });
}
