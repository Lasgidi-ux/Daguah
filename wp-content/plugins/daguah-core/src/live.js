/**
 * Market Live in the visitor's own time.
 *
 * The server prints every session time in GMT, which stays as the fallback.
 * This script:
 *   - rewrites each time[data-local-time] in the visitor's time zone, for
 *     example "Sat 21 Nov, 12:00 your time" in Toronto
 *   - counts down to the start on a session page ([data-countdown])
 *   - loads the stream's player only when the visitor presses play, so no
 *     third party loads before then
 */
import { __, sprintf } from '@wordpress/i18n';

function localise() {
	document.querySelectorAll( 'time[data-local-time]' ).forEach( ( node ) => {
		const date = new Date( node.getAttribute( 'datetime' ) );

		if ( Number.isNaN( date.getTime() ) ) {
			return;
		}

		// "18:15 GMT" is the end of a range: show the time alone.
		const timeOnly = /^\d{2}:\d{2} GMT$/.test( node.textContent.trim() );
		const options = timeOnly
			? { hour: '2-digit', minute: '2-digit' }
			: { weekday: 'short', day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' };

		const formatted = new Intl.DateTimeFormat( undefined, options ).format( date );

		/* translators: %s: a date and time */
		node.textContent = timeOnly ? formatted : sprintf( __( '%s your time', 'daguah' ), formatted );
	} );
}

function countdown() {
	document.querySelectorAll( '[data-countdown]' ).forEach( ( node ) => {
		const start = new Date( node.getAttribute( 'data-countdown' ) ).getTime();

		if ( Number.isNaN( start ) ) {
			return;
		}

		const tick = () => {
			const left = Math.max( 0, Math.floor( ( start - Date.now() ) / 1000 ) );

			if ( left === 0 ) {
				node.textContent = node.getAttribute( 'data-live-text' ) || '';
				return;
			}

			const days = Math.floor( left / 86400 );
			const hours = Math.floor( ( left % 86400 ) / 3600 );
			const minutes = Math.floor( ( left % 3600 ) / 60 );
			const seconds = left % 60;

			node.textContent =
				days > 0
					? `${ days }d ${ hours }h ${ minutes }m`
					: `${ hours }h ${ String( minutes ).padStart( 2, '0' ) }m ${ String( seconds ).padStart( 2, '0' ) }s`;

			// Every second in the last day, every minute before that.
			window.setTimeout( tick, days > 0 ? 60000 - ( Date.now() % 60000 ) : 1000 );
		};

		tick();
	} );
}

function players() {
	document.querySelectorAll( '.daguah-stage__player[data-embed]' ).forEach( ( player ) => {
		const button = player.querySelector( '.daguah-stage__play' );

		if ( ! button ) {
			return;
		}

		button.hidden = false;
		button.addEventListener( 'click', () => {
			const frame = document.createElement( 'iframe' );
			const url = new URL( player.getAttribute( 'data-embed' ) );

			url.searchParams.set( 'autoplay', '1' );
			frame.src = url.toString();
			frame.title = __( 'Market Live player', 'daguah' );
			frame.allow = 'autoplay; fullscreen; picture-in-picture; encrypted-media';
			frame.allowFullscreen = true;
			frame.className = 'daguah-stage__frame';

			player.replaceChildren( frame );
			frame.focus();
		} );
	} );
}

function init() {
	localise();
	countdown();
	players();
}

if ( document.readyState === 'loading' ) {
	document.addEventListener( 'DOMContentLoaded', init );
} else {
	init();
}
