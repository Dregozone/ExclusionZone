/**
 * Survivor's Manual search box (Alpine component `manualSearch`).
 *
 * The index of every guide, skill, location, item and action is fetched the
 * first time the box is focused, then filtered in the browser as the reader
 * types. Press "/" anywhere to jump to the box, arrows to move, Enter to go.
 */
function manualSearch(indexUrl) {
    return {
        query: '',
        entries: null,
        open: false,
        active: 0,
        loading: false,

        init() {
            this.onSlash = (event) => {
                const typing = ['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement?.tagName);

                if (event.key === '/' && !typing) {
                    event.preventDefault();
                    this.$refs.input.focus();
                }
            };

            window.addEventListener('keydown', this.onSlash);
        },

        destroy() {
            window.removeEventListener('keydown', this.onSlash);
        },

        async load() {
            this.open = true;

            if (this.entries !== null || this.loading) {
                return;
            }

            this.loading = true;

            try {
                const response = await fetch(indexUrl, { headers: { Accept: 'application/json' } });
                this.entries = response.ok ? await response.json() : [];
            } catch (error) {
                this.entries = [];
            } finally {
                this.loading = false;
            }
        },

        get results() {
            const terms = this.query.trim().toLowerCase().split(/\s+/).filter(Boolean);

            if (terms.length === 0 || this.entries === null) {
                return [];
            }

            return this.entries
                .map((entry) => {
                    const title = entry.title.toLowerCase();
                    const haystack = `${title} ${entry.type.toLowerCase()} ${entry.hint.toLowerCase()}`;

                    if (!terms.every((term) => haystack.includes(term))) {
                        return null;
                    }

                    const score = (title.startsWith(terms[0]) ? 0 : title.includes(terms[0]) ? 1 : 2);

                    return { ...entry, score };
                })
                .filter(Boolean)
                .sort((a, b) => a.score - b.score || a.title.localeCompare(b.title))
                .slice(0, 12);
        },

        move(step) {
            const count = this.results.length;

            if (count === 0) {
                return;
            }

            this.active = (this.active + step + count) % count;
        },

        go() {
            const result = this.results[this.active];

            if (result) {
                window.location.href = result.url;
            }
        },
    };
}

window.manualSearch = manualSearch;
