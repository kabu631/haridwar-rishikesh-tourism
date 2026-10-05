/**
 * Fade slider used for the homepage hero and page photo slideshows.
 *
 * Markup: [data-slider] > [data-slide] (first one has .is-active), optional
 * [data-slider-prev], [data-slider-next], [data-slider-dots], [data-slider-progress].
 * Options: data-autoplay="6000" (ms, 0 = off).
 *
 * All slides are server-rendered (crawlable); without JavaScript the first
 * slide simply stays visible.
 */
export function initSliders() {
    document.querySelectorAll('[data-slider]').forEach((root) => new Slider(root));
}

class Slider {
    constructor(root) {
        this.root = root;
        this.slides = [...root.querySelectorAll('[data-slide]')];
        this.index = Math.max(0, this.slides.findIndex((slide) => slide.classList.contains('is-active')));
        this.delay = parseInt(root.dataset.autoplay || '0', 10);
        this.reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        this.paused = false;
        this.timer = null;

        if (this.slides.length < 2) {
            return;
        }

        this.buildDots();
        root.querySelector('[data-slider-prev]')?.addEventListener('click', () => this.go(this.index - 1, true));
        root.querySelector('[data-slider-next]')?.addEventListener('click', () => this.go(this.index + 1, true));

        root.addEventListener('keydown', (event) => {
            if (event.key === 'ArrowLeft') this.go(this.index - 1, true);
            if (event.key === 'ArrowRight') this.go(this.index + 1, true);
        });

        ['mouseenter', 'focusin'].forEach((type) => root.addEventListener(type, () => this.pause()));
        ['mouseleave', 'focusout'].forEach((type) => root.addEventListener(type, (event) => {
            if (!root.contains(event.relatedTarget)) this.resume();
        }));
        document.addEventListener('visibilitychange', () => (document.hidden ? this.pause() : this.resume()));

        this.enableSwipe();
        this.go(this.index, false);
        this.reserveTallestCaption();
        window.addEventListener('resize', () => this.reserveTallestCaption());
        this.schedule();
    }

    buildDots() {
        const holder = this.root.querySelector('[data-slider-dots]');
        if (!holder) return;

        this.dots = this.slides.map((slide, i) => {
            const dot = document.createElement('button');
            dot.type = 'button';
            dot.className = 'slider-dot';
            dot.setAttribute('aria-label', `Show slide ${i + 1} of ${this.slides.length}`);
            dot.addEventListener('click', () => this.go(i, true));
            holder.append(dot);
            return dot;
        });
    }

    /**
     * Captions in [data-slide-sync-fit] change height per slide; hold the slider
     * at the height it needs for the tallest caption so the page below never
     * jumps while it autoplays (no layout shift).
     */
    reserveTallestCaption() {
        const fit = this.root.querySelector('[data-slide-sync-fit]');
        if (!fit) return;

        this.root.style.minHeight = '';
        const tallest = Math.max(...[...fit.querySelectorAll('[data-slide-sync]')].map((el) => el.offsetHeight));
        this.root.style.minHeight = `${this.root.offsetHeight - fit.offsetHeight + tallest}px`;
    }

    go(target, userAction) {
        const count = this.slides.length;
        this.index = (target + count) % count;

        this.slides.forEach((slide, i) => {
            const active = i === this.index;
            slide.classList.toggle('is-active', active);
            slide.setAttribute('aria-hidden', String(!active));
            slide.inert = !active;

            // Load the image of the slide that is about to be shown.
            if (active) {
                slide.querySelectorAll('img[loading="lazy"]').forEach((img) => (img.loading = 'eager'));
            }
        });

        // Containers marked [data-slide-sync-fit] take the height of the active caption
        // (instead of the tallest one) and animate between heights.
        const fits = [...this.root.querySelectorAll('[data-slide-sync-fit]')];
        const before = fits.map((fit) => fit.offsetHeight);

        // Elements elsewhere in the slider (e.g. hero captions) that follow the active slide.
        this.root.querySelectorAll('[data-slide-sync]').forEach((el) => {
            const active = Number(el.dataset.slideSync) === this.index;
            el.classList.toggle('is-active', active);
            el.setAttribute('aria-hidden', String(!active));
            el.inert = !active;
        });

        fits.forEach((fit, i) => {
            fit.style.height = '';
            const after = fit.offsetHeight;
            if (!userAction && !this.started) return;
            if (before[i] === after || window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
            fit.style.height = `${before[i]}px`;
            fit.offsetHeight; // reflow so the transition starts from the old height
            fit.style.height = `${after}px`;
            fit.addEventListener('transitionend', () => (fit.style.height = ''), { once: true });
        });
        this.started = true;

        this.dots?.forEach((dot, i) => dot.setAttribute('aria-current', String(i === this.index)));

        const status = this.root.querySelector('[data-slider-status]');
        if (status) status.textContent = `Slide ${this.index + 1} of ${count}`;

        if (userAction) {
            this.schedule();
        }
    }

    schedule() {
        clearTimeout(this.timer);
        const progress = this.root.querySelector('[data-slider-progress]');

        if (!this.delay || this.reducedMotion || this.paused) {
            if (progress) progress.style.animation = 'none';
            return;
        }

        if (progress) {
            progress.style.animation = 'none';
            void progress.offsetWidth;
            progress.style.animation = `slider-progress ${this.delay}ms linear forwards`;
        }

        this.timer = setTimeout(() => {
            this.go(this.index + 1, false);
            this.schedule();
        }, this.delay);
    }

    pause() {
        this.paused = true;
        clearTimeout(this.timer);
        const progress = this.root.querySelector('[data-slider-progress]');
        if (progress) progress.style.animationPlayState = 'paused';
    }

    resume() {
        if (!this.paused) return;
        this.paused = false;
        this.schedule();
    }

    enableSwipe() {
        let startX = null;
        this.root.addEventListener('touchstart', (event) => (startX = event.touches[0].clientX), { passive: true });
        this.root.addEventListener('touchend', (event) => {
            if (startX === null) return;
            const delta = event.changedTouches[0].clientX - startX;
            if (Math.abs(delta) > 45) this.go(this.index + (delta < 0 ? 1 : -1), true);
            startX = null;
        });
    }
}
