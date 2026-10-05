/**
 * Header behaviour: sticky shadow, mega-menu keyboard/touch support and the
 * mobile drawer (focus trapped, closes on Escape).
 */
export function initNavigation() {
    const header = document.querySelector('[data-header]');

    if (header) {
        const onScroll = () => header.classList.toggle('header-scrolled', window.scrollY > 8);
        onScroll();
        window.addEventListener('scroll', onScroll, { passive: true });
    }

    initMegaMenu();
    initDrawer();
}

function initMegaMenu() {
    const items = document.querySelectorAll('[data-nav-item]');

    items.forEach((item) => {
        const trigger = item.querySelector('[data-nav-trigger]');

        if (!trigger) {
            return;
        }

        trigger.addEventListener('click', (event) => {
            // On touch devices the first tap opens the panel instead of navigating.
            if (window.matchMedia('(hover: none)').matches && !item.classList.contains('is-open')) {
                event.preventDefault();
                closeAll(items, item);
                item.classList.add('is-open');
                trigger.setAttribute('aria-expanded', 'true');
            }
        });

        trigger.addEventListener('keydown', (event) => {
            if (event.key === 'ArrowDown') {
                event.preventDefault();
                item.classList.add('is-open');
                trigger.setAttribute('aria-expanded', 'true');
                item.querySelector('[data-nav-panel] a')?.focus();
            }
        });

        item.addEventListener('mouseenter', () => trigger.setAttribute('aria-expanded', 'true'));
        item.addEventListener('mouseleave', () => {
            item.classList.remove('is-open');
            trigger.setAttribute('aria-expanded', 'false');
        });

        item.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                item.classList.remove('is-open');
                trigger.setAttribute('aria-expanded', 'false');
                trigger.focus();
            }
        });

        item.addEventListener('focusout', (event) => {
            if (!item.contains(event.relatedTarget)) {
                item.classList.remove('is-open');
                trigger.setAttribute('aria-expanded', 'false');
            }
        });
    });

    document.addEventListener('click', (event) => {
        if (!event.target.closest('[data-nav-item]')) {
            closeAll(items);
        }
    });
}

function closeAll(items, except = null) {
    items.forEach((item) => {
        if (item !== except) {
            item.classList.remove('is-open');
            item.querySelector('[data-nav-trigger]')?.setAttribute('aria-expanded', 'false');
        }
    });
}

function initDrawer() {
    const drawer = document.querySelector('[data-drawer]');

    if (!drawer) {
        return;
    }

    const openers = document.querySelectorAll('[data-drawer-open]');
    const closers = drawer.querySelectorAll('[data-drawer-close]');
    const panel = drawer.querySelector('[data-drawer-panel]');
    let lastFocus = null;

    const open = () => {
        lastFocus = document.activeElement;
        drawer.hidden = false;
        document.body.style.overflow = 'hidden';
        openers.forEach((button) => button.setAttribute('aria-expanded', 'true'));
        requestAnimationFrame(() => {
            drawer.classList.add('is-open');
            panel?.querySelector('a, button, input')?.focus();
        });
    };

    const close = () => {
        drawer.classList.remove('is-open');
        openers.forEach((button) => button.setAttribute('aria-expanded', 'false'));
        document.body.style.overflow = '';
        setTimeout(() => {
            drawer.hidden = true;
        }, 250);
        lastFocus?.focus();
    };

    openers.forEach((button) => button.addEventListener('click', open));
    closers.forEach((button) => button.addEventListener('click', close));

    drawer.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            close();
        }

        if (event.key === 'Tab' && panel) {
            const focusable = [...panel.querySelectorAll('a, button, input, [tabindex]:not([tabindex="-1"])')].filter((el) => el.offsetParent !== null);
            const first = focusable[0];
            const last = focusable[focusable.length - 1];

            if (event.shiftKey && document.activeElement === first) {
                event.preventDefault();
                last.focus();
            } else if (!event.shiftKey && document.activeElement === last) {
                event.preventDefault();
                first.focus();
            }
        }
    });

    // Accordion sections inside the drawer.
    drawer.querySelectorAll('[data-accordion-trigger]').forEach((trigger) => {
        trigger.addEventListener('click', () => {
            const target = document.getElementById(trigger.getAttribute('aria-controls'));
            const expanded = trigger.getAttribute('aria-expanded') === 'true';

            if (target && !target.children.length && target.dataset.menuSource) {
                fillFromHeader(target);
            }
            trigger.setAttribute('aria-expanded', String(!expanded));
            if (target) {
                target.hidden = expanded;
            }
        });
    });
}

/**
 * Copy the links of one header mega-menu panel into a drawer group.
 */
function fillFromHeader(list) {
    const source = document.querySelector(`[data-menu-id="${list.dataset.menuSource}"] [data-nav-panel] ul`);

    source?.querySelectorAll('a').forEach((link) => {
        const item = document.createElement('li');
        const copy = document.createElement('a');
        copy.href = link.getAttribute('href');
        copy.className = 'drawer-link';
        copy.textContent = link.textContent.trim();
        if (link.target) {
            copy.target = link.target;
            copy.rel = 'noopener';
        }
        item.append(copy);
        list.append(item);
    });
}
