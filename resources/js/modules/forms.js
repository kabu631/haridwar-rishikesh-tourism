/**
 * Enquiry forms submit in the background and show the result inline.
 * Without JavaScript they post normally and redirect to /thank-you.
 */
export function initForms() {
    document.querySelectorAll('form[data-enquiry]').forEach((form) => {
        const source = form.querySelector('input[name="source"]');
        if (source && !source.value) {
            source.value = window.location.href;
        }

        linkTravelDates(form);

        form.addEventListener('submit', async (event) => {
            if (!window.fetch || !form.checkValidity()) {
                return;
            }

            event.preventDefault();

            const button = form.querySelector('[type="submit"]');
            const status = form.querySelector('[data-form-status]');
            const original = button?.innerHTML;

            clearErrors(form);
            if (button) {
                button.disabled = true;
                button.innerHTML = '<span class="size-4 animate-spin rounded-full border-2 border-white/40 border-t-white"></span> Sending…';
            }

            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    body: new FormData(form),
                });
                const data = await response.json().catch(() => ({}));

                if (response.ok && data.ok) {
                    trackConversion(form);
                    form.reset();
                    showStatus(status, data.message, 'success');
                    if (form.hasAttribute('data-redirect') && data.redirect) {
                        window.location.href = data.redirect;
                    }
                } else if (response.status === 422 && data.errors) {
                    showErrors(form, data.errors);
                    showStatus(status, 'Please check the highlighted fields.', 'error');
                } else if (response.status === 429) {
                    showStatus(status, 'Too many attempts. Please wait a minute or call us directly.', 'error');
                } else {
                    form.submit();
                }
            } catch {
                form.submit();
            } finally {
                if (button) {
                    button.disabled = false;
                    button.innerHTML = original;
                }
            }
        });
    });
}

/**
 * The departure (return) date can never be before the arrival date: its
 * calendar starts at the arrival day, and an earlier departure is cleared.
 */
function linkTravelDates(form) {
    const arrival = form.querySelector('input[name="travel_date"]');
    const departure = form.querySelector('input[name="return_date"]');

    if (!arrival || !departure) {
        return;
    }

    const today = departure.min;
    const sync = () => {
        departure.min = arrival.value || today;
        if (departure.value && arrival.value && departure.value < arrival.value) {
            departure.value = '';
        }
    };

    arrival.addEventListener('change', sync);
    arrival.addEventListener('input', sync);
    sync();
}

function showErrors(form, errors) {
    Object.entries(errors).forEach(([name, messages]) => {
        const field = form.querySelector(`[name="${name}"]`);
        if (!field) return;
        field.setAttribute('aria-invalid', 'true');
        field.classList.add('ring-2', 'ring-brand-500');
        const message = document.createElement('p');
        message.className = 'mt-1 text-xs font-medium text-brand-700';
        message.dataset.error = '';
        message.id = `${field.id || name}-error`;
        message.textContent = messages[0];
        field.setAttribute('aria-describedby', message.id);
        field.insertAdjacentElement('afterend', message);
    });
    form.querySelector('[aria-invalid="true"]')?.focus();
}

function clearErrors(form) {
    form.querySelectorAll('[data-error]').forEach((el) => el.remove());
    form.querySelectorAll('[aria-invalid]').forEach((field) => {
        field.removeAttribute('aria-invalid');
        field.classList.remove('ring-2', 'ring-brand-500');
    });
}

function showStatus(element, message, type) {
    if (!element) return;
    element.hidden = false;
    element.textContent = message;
    element.className = type === 'success'
        ? 'rounded-xl bg-leaf-50 px-4 py-3 text-sm font-medium text-leaf-700 ring-1 ring-leaf-600/20'
        : 'rounded-xl bg-brand-50 px-4 py-3 text-sm font-medium text-brand-800 ring-1 ring-brand-600/20';
    element.setAttribute('role', type === 'success' ? 'status' : 'alert');
}

function trackConversion(form) {
    const label = document.querySelector('meta[name="ads-conversion"]')?.content;
    if (typeof window.gtag === 'function') {
        window.gtag('event', 'generate_lead', { form_type: form.dataset.enquiry || 'enquiry' });
        if (label) {
            window.gtag('event', 'conversion', { send_to: label });
        }
    }
}
