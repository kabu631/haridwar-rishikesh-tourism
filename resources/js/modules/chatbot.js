/**
 * Trip assistant: a guided chat that suggests tour packages for the option a
 * visitor taps, then lets them narrow the list by trip length. Package lists
 * come from /chatbot/packages and are fetched once per page view.
 */
const VISIBLE_PACKAGES = 4;

const DURATIONS = [
    { key: 'short', label: '1–3 days', matches: (days) => days <= 3 },
    { key: 'medium', label: '4–6 days', matches: (days) => days >= 4 && days <= 6 },
    { key: 'long', label: 'A week or more', matches: (days) => days >= 7 },
];

export function initChatbot() {
    const root = document.querySelector('[data-chatbot]');

    if (root) {
        new Chatbot(root);
    }
}

class Chatbot {
    constructor(root) {
        this.root = root;
        this.panel = root.querySelector('[data-chatbot-panel]');
        this.log = root.querySelector('[data-chatbot-log]');
        this.toggle = root.querySelector('[data-chatbot-toggle]');
        this.request = null;
        this.started = false;

        if (!this.panel || !this.log || !this.toggle) {
            return;
        }

        this.topics = new Map(
            [...this.template('topics').querySelectorAll('[data-topic]')].map((button) => [
                button.dataset.topic,
                { label: button.querySelector('[data-label]').textContent.trim(), reply: button.dataset.reply, url: button.dataset.url },
            ]),
        );

        this.toggle.addEventListener('click', () => (this.panel.hidden ? this.open() : this.close()));
        root.querySelector('[data-chatbot-close]')?.addEventListener('click', () => this.close());
        this.log.addEventListener('click', (event) => this.choose(event));

        this.panel.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                this.close();
            }
        });
    }

    open() {
        this.panel.hidden = false;
        this.toggle.setAttribute('aria-expanded', 'true');
        requestAnimationFrame(() => this.panel.classList.add('is-open'));

        if (!this.started) {
            this.started = true;
            this.say('bot', this.root.dataset.greeting);
            this.append(this.template('topics'));
        }

        this.packages();
        this.log.querySelector('[data-chatbot-choices] button')?.focus({ preventScroll: true });
    }

    close() {
        this.panel.classList.remove('is-open');
        this.toggle.setAttribute('aria-expanded', 'false');
        setTimeout(() => {
            this.panel.hidden = !this.panel.classList.contains('is-open');
        }, 200);
        this.toggle.focus();
    }

    choose(event) {
        const choice = event.target.closest('button[data-choice]');

        if (!choice) {
            return;
        }

        const { topic, duration } = choice.dataset;
        this.log.querySelectorAll('[data-chatbot-choices]').forEach((group) => group.remove());
        this.say('user', choice.textContent.trim());

        if (choice.dataset.choice === 'restart') {
            this.say('bot', 'Sure – what kind of trip are you planning?');
            this.append(this.template('topics'));
            this.scrollToEnd();
            this.log.querySelector('[data-chatbot-choices] button')?.focus({ preventScroll: true });
            return;
        }

        this.suggest(topic, duration);
    }

    async suggest(key, durationKey = null) {
        const topic = this.topics.get(key);
        const duration = DURATIONS.find((option) => option.key === durationKey);
        const typing = this.append(this.template('typing'));
        this.scrollToEnd();

        const [data] = await Promise.all([this.packages(), pause()]);
        typing.remove();

        if (!topic || !data) {
            const reply = this.say('bot', 'Sorry, I couldn’t load our tours just now. Call or WhatsApp our Haridwar office and we’ll suggest one for you.');
            this.followUps(key, []);
            this.scrollTo(reply);
            return;
        }

        const all = data.topics?.[key] ?? [];
        const matching = duration ? all.filter((item) => item.days && duration.matches(item.days)) : all;
        let message = topic.reply;

        if (duration) {
            message = matching.length
                ? `${topic.label} tours of ${duration.label.toLowerCase()}:`
                : `We don’t have a fixed ${duration.label.toLowerCase()} ${topic.label} tour, but we can tailor one for you. Meanwhile, these are popular:`;
        } else if (!all.length) {
            message = `We plan ${topic.label} trips on request. Tell us your dates on WhatsApp and we’ll send an itinerary.`;
        }

        const reply = this.say('bot', message);
        const shown = (matching.length ? matching : all).slice(0, VISIBLE_PACKAGES);

        if (shown.length) {
            this.append(this.packageList(shown));
        }

        this.followUps(key, all, durationKey);
        this.scrollTo(reply);
    }

    /**
     * Next steps after a suggestion: filter by trip length, see every tour,
     * ask on WhatsApp, or pick another kind of trip.
     */
    followUps(key, packages, currentDuration = null) {
        const topic = this.topics.get(key);
        const group = this.template('choices');

        DURATIONS.filter((option) => option.key !== currentDuration && packages.some((item) => item.days && option.matches(item.days))).forEach((option) => {
            group.append(this.option({ choice: 'duration', topic: key, duration: option.key }, option.label, 'clock'));
        });

        if (topic && packages.length > VISIBLE_PACKAGES) {
            group.append(this.link(topic.url, `See all ${topic.label} tours`, 'arrow-right'));
        }

        const text = topic ? `Hello! I am interested in ${topic.label} tours. Please suggest a package.` : 'Hello! Please suggest a tour package for my trip.';
        group.append(this.link(`${this.root.dataset.whatsapp}?text=${encodeURIComponent(text)}`, 'Ask on WhatsApp', 'whatsapp', true));
        group.append(this.option({ choice: 'restart' }, 'Other trip types', 'refresh'));

        this.append(group);
    }

    packageList(packages) {
        const list = this.template('packages');

        packages.forEach((item) => {
            const row = this.template('package');
            const link = row.querySelector('[data-package]');
            const image = row.querySelector('[data-image]');

            link.href = item.url;
            row.querySelector('[data-title]').textContent = item.title;
            row.querySelector('[data-duration]').textContent = formatDuration(item);

            if (item.image) {
                image.src = item.image;
            } else {
                image.remove();
            }

            list.append(row);
        });

        return list;
    }

    option(data, label, icon) {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'chat-option';
        Object.assign(button.dataset, data);
        button.append(this.template(`icon-${icon}`), document.createTextNode(label));

        return button;
    }

    link(href, label, icon, external = false) {
        const link = document.createElement('a');
        link.href = href;
        link.className = 'chat-option';
        link.append(this.template(`icon-${icon}`), document.createTextNode(label));

        if (external) {
            link.target = '_blank';
            link.rel = 'noopener';
        }

        return link;
    }

    packages() {
        this.request ??= fetch(this.root.dataset.endpoint, { headers: { Accept: 'application/json' } })
            .then((response) => (response.ok ? response.json() : Promise.reject(new Error(String(response.status)))))
            .catch(() => {
                this.request = null;

                return null;
            });

        return this.request;
    }

    say(who, text) {
        const message = this.template(who);
        message.querySelector('[data-content]').textContent = text;

        return this.append(message);
    }

    append(node) {
        this.log.append(node);

        return node;
    }

    template(name) {
        return this.root.querySelector(`template[data-chatbot-template="${name}"]`).content.firstElementChild.cloneNode(true);
    }

    /**
     * Keep the start of the latest reply in view, so a long list of
     * suggestions never pushes the answer out of sight.
     */
    scrollTo(element) {
        this.log.scrollTo({ top: Math.max(0, element.offsetTop - 16), behavior: motion() });
    }

    scrollToEnd() {
        this.log.scrollTo({ top: this.log.scrollHeight, behavior: motion() });
    }
}

function formatDuration({ days, nights }) {
    if (!days) {
        return 'Flexible duration';
    }

    const dayLabel = `${days} ${days === 1 ? 'Day' : 'Days'}`;

    return nights ? `${nights} ${nights === 1 ? 'Night' : 'Nights'} / ${dayLabel}` : dayLabel;
}

function motion() {
    return window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth';
}

/**
 * A short "typing" beat so replies feel conversational (skipped when the
 * visitor prefers reduced motion).
 */
function pause() {
    return new Promise((resolve) => setTimeout(resolve, motion() === 'auto' ? 0 : 550));
}
