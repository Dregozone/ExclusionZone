/**
 * Quick actions on the city menu (eating, using medical items, wearing gear and trading at a
 * bazaar) are posted in the background so the page keeps its scroll position. The count on the
 * button drops straight away, then the live parts of the page are refreshed from the server so
 * they show what really happened.
 */

let refreshing = null;

/**
 * Fetch the city menu again and swap in every part marked with data-work-live. Resolves with the
 * fetched page, or null when it could not be loaded.
 */
async function refreshLive() {
    refreshing ??= (async () => {
        try {
            const response = await fetch(window.location.href, {
                headers: { Accept: 'text/html' },
                credentials: 'same-origin',
            });

            if (!response.ok) {
                return null;
            }

            const page = new DOMParser().parseFromString(await response.text(), 'text/html');

            page.querySelectorAll('[data-work-live]').forEach((fresh) => {
                const current = document.querySelector(`[data-work-live='${fresh.dataset.workLive}']`);

                if (current !== null) {
                    current.innerHTML = fresh.innerHTML;
                }
            });

            window.dispatchEvent(new CustomEvent('ez:live-refreshed', { detail: { page } }));

            return page;
        } catch {
            return null;
        } finally {
            refreshing = null;
        }
    })();

    return refreshing;
}

window.ezRefreshLive = refreshLive;

function toast(options) {
    window.Flux?.toast({ duration: 6000, ...options });
}

/**
 * Show the action as done before the server answers: one fewer of the item, and the form greyed
 * out while it is in flight.
 */
function showOptimistically(form) {
    form.setAttribute('aria-busy', 'true');
    form.classList.add('opacity-60');

    const count = form.querySelector('[data-quick-count]');

    if (count !== null) {
        count.textContent = String(Math.max(0, Number(count.textContent) - 1));
    }
}

document.addEventListener('submit', async (event) => {
    const form = event.target;

    if (!(form instanceof HTMLFormElement) || !form.hasAttribute('data-quick-action')) {
        return;
    }

    event.preventDefault();

    if (form.getAttribute('aria-busy') === 'true') {
        return;
    }

    const body = new FormData(form, event.submitter ?? undefined);

    showOptimistically(form);

    try {
        const response = await fetch(form.action, {
            method: 'POST',
            body,
            credentials: 'same-origin',
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        });
        const data = await response.json().catch(() => ({}));

        if (response.ok) {
            toast({ text: data.status, variant: 'success' });
        } else if (response.status === 419) {
            window.location.reload();

            return;
        } else {
            toast({ heading: 'Action unavailable', text: data.message ?? 'That did not work. Try again.', variant: 'danger' });
        }
    } catch {
        toast({ heading: 'No signal', text: 'Could not reach the server. Try again in a moment.', variant: 'danger' });
    }

    await refreshLive();

    form.removeAttribute('aria-busy');
    form.classList.remove('opacity-60');
});
