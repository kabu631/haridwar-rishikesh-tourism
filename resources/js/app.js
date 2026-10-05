/**
 * Progressive enhancement only: every page is fully rendered on the server,
 * so content, links and forms work (and are crawlable) without JavaScript.
 */
import { initNavigation } from './modules/navigation';
import { initSearch } from './modules/search';
import { initCarousels } from './modules/carousel';
import { initSliders } from './modules/slider';
import { initLightbox } from './modules/lightbox';
import { initYouTube } from './modules/youtube';
import { initReveal } from './modules/reveal';
import { initForms } from './modules/forms';
import { initShare } from './modules/share';
import { initAnalytics } from './modules/analytics';
import { initBackToTop } from './modules/back-to-top';
import { initChatbot } from './modules/chatbot';

document.documentElement.classList.add('js');

const ready = (callback) => {
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', callback, { once: true });
    } else {
        callback();
    }
};

ready(() => {
    initNavigation();
    initSearch();
    initCarousels();
    initSliders();
    initLightbox();
    initYouTube();
    initReveal();
    initForms();
    initShare();
    initAnalytics();
    initBackToTop();
    initChatbot();
});
