/**
 * Copyright © MageMe. All rights reserved.
 * See LICENSE for license terms, or https://mageme.com/license.
 *
 * Progressive enhancement for the withdrawal order picker: intercept the
 * "Show more orders" link and append the next batch via fetch instead of a
 * full page reload. Falls back to the link's href if the request fails.
 */
{
    const loadMore = async (link) => {
        const endpoint = link.dataset.endpoint;
        if (!endpoint) {
            window.location.assign(link.href);
            return;
        }
        const wrap = link.closest('[data-role="load-more-wrap"]');
        link.setAttribute('aria-busy', 'true');
        link.classList.add('mm-eu-w-btn--loading');
        try {
            const response = await fetch(endpoint, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            const data = await response.json();
            const list = document.querySelector('[data-role="order-list"]');
            if (list && typeof data.html === 'string' && data.html !== '') {
                list.innerHTML = data.html;
            }
            if (data.hasMore) {
                link.href = data.nextHref || link.href;
                link.dataset.endpoint = data.nextEndpoint || '';
                link.classList.remove('mm-eu-w-btn--loading');
                link.removeAttribute('aria-busy');
            } else if (wrap) {
                wrap.remove();
            }
        } catch (error) {
            window.location.assign(link.href);
        }
    };

    document.addEventListener('click', (event) => {
        const link = event.target.closest('[data-role="load-more"]');
        if (!link) {
            return;
        }
        event.preventDefault();
        loadMore(link);
    });
}
