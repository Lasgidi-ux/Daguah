/**
 * Lantern words: light each word, in reading order, as it crosses a trigger
 * line 60% down the screen.
 *
 * One scroll listener, throttled to one run per animation frame, and only
 * attached while the block is on screen (an IntersectionObserver turns it on
 * and off). Words never go dark again once lit. With reduced motion the
 * block is left at full colour and nothing is attached.
 */
const TRIGGER = 0.6;

function setUp( block ) {
	const words = Array.from( block.querySelectorAll( '.daguah-lantern-words__word' ) );

	if ( ! words.length ) {
		return;
	}

	block.classList.add( 'is-armed' );

	let lit = 0;
	let frame = 0;

	const update = () => {
		frame = 0;
		const line = window.innerHeight * TRIGGER;

		// Light words in order: stop at the first one still below the line.
		while ( lit < words.length && words[ lit ].getBoundingClientRect().top < line ) {
			words[ lit ].classList.add( 'is-lit' );
			lit++;
		}

		if ( lit >= words.length ) {
			stop();
		}
	};

	const onScroll = () => {
		if ( ! frame ) {
			frame = window.requestAnimationFrame( update );
		}
	};

	const observer = new IntersectionObserver( ( entries ) => {
		entries.forEach( ( entry ) => {
			if ( entry.isIntersecting ) {
				window.addEventListener( 'scroll', onScroll, { passive: true } );
				onScroll();
			} else {
				window.removeEventListener( 'scroll', onScroll );
			}
		} );
	} );

	function stop() {
		window.removeEventListener( 'scroll', onScroll );
		observer.disconnect();
	}

	observer.observe( block );
}

function init() {
	if ( window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches || ! ( 'IntersectionObserver' in window ) ) {
		return;
	}
	document.querySelectorAll( '.wp-block-daguah-lantern-words' ).forEach( setUp );
}

if ( document.readyState === 'loading' ) {
	document.addEventListener( 'DOMContentLoaded', init );
} else {
	init();
}
