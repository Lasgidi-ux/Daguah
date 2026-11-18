/**
 * Cloth block, front end.
 *
 * The pattern is already in the page from PHP. This only adds the optional
 * "weave in": parts of the pattern fade up in turn the first time the cloth
 * enters the screen. Opacity only, once, through IntersectionObserver.
 */
const STEP_MS = 40;
const MAX_DELAY_MS = 600;

function weave( cloth ) {
	const reduced = window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;

	cloth.querySelectorAll( 'pattern > g' ).forEach( ( part, index ) => {
		const delay = reduced ? 0 : Math.min( index * STEP_MS, MAX_DELAY_MS );
		part.style.transitionDelay = `${ delay }ms`;
	} );

	cloth.classList.add( 'is-woven' );
}

function setUp() {
	const cloths = document.querySelectorAll( '.wp-block-daguah-cloth[data-animate]' );

	if ( ! cloths.length ) {
		return;
	}

	if ( ! ( 'IntersectionObserver' in window ) ) {
		cloths.forEach( weave );
		return;
	}

	const observer = new IntersectionObserver(
		( entries ) => {
			entries.forEach( ( entry ) => {
				if ( entry.isIntersecting ) {
					weave( entry.target );
					observer.unobserve( entry.target );
				}
			} );
		},
		{ threshold: 0.2 }
	);

	cloths.forEach( ( cloth ) => observer.observe( cloth ) );
}

if ( document.readyState === 'loading' ) {
	document.addEventListener( 'DOMContentLoaded', setUp );
} else {
	setUp();
}
