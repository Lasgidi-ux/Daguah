/**
 * Market word: crossfade to the next language every few seconds.
 *
 * Opacity only, so it runs for visitors who prefer reduced motion too, at a
 * slower pace. Pauses while hovered or focused (so the language can be read)
 * and while the tab is hidden.
 */
function start( root ) {
	const items = Array.from( root.querySelectorAll( '.daguah-market-word__item' ) );

	if ( items.length < 2 ) {
		return;
	}

	const reduced = window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;
	const interval = Number( root.dataset.interval || 2600 ) * ( reduced ? 2 : 1 );
	let index = 0;
	let held = false;

	root.addEventListener( 'pointerenter', () => ( held = true ) );
	root.addEventListener( 'pointerleave', () => ( held = false ) );

	window.setInterval( () => {
		if ( held || document.hidden ) {
			return;
		}
		items[ index ].classList.remove( 'is-active' );
		index = ( index + 1 ) % items.length;
		items[ index ].classList.add( 'is-active' );
	}, interval );
}

function setUp() {
	document.querySelectorAll( '.wp-block-daguah-market-word' ).forEach( start );
}

if ( document.readyState === 'loading' ) {
	document.addEventListener( 'DOMContentLoaded', setUp );
} else {
	setUp();
}
