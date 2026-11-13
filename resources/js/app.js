/**
 * The world map is registered first so it still works if broadcasting
 * is not configured and Echo fails to start.
 */
import './geo-map/geo-map.js';
import './manual-search.js';

/**
 * Echo exposes an expressive API for subscribing to channels and listening
 * for events that are broadcast by Laravel. Echo and event broadcasting
 * allow your team to quickly build robust real-time web applications.
 */

import './echo';
