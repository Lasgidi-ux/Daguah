/**
 * Smooth scrolling with Lenis.
 *
 * Runs only for mouse and trackpad visitors who have not asked for reduced
 * motion. It stops while the mobile menu is open and shuts down if the
 * reduced motion setting changes mid visit.
 */
import Lenis from 'lenis';

const reduced = window.matchMedia( '(prefers-reduced-motion: reduce)' );
const finePointer = window.matchMedia( '(pointer: fine)' );

let lenis = null;
let frame = 0;

function loop( time ) {
	if ( ! lenis ) {
		return;
	}
	lenis.raf( time );
	frame = window.requestAnimationFrame( loop );
}

function start() {
	if ( lenis || reduced.matches || ! finePointer.matches ) {
		return;
	}

	lenis = new Lenis( {
		lerp: 0.1,
		smoothWheel: true,
		anchors: true,
	} );
	frame = window.requestAnimationFrame( loop );
}

function stop() {
	if ( ! lenis ) {
		return;
	}
	window.cancelAnimationFrame( frame );
	lenis.destroy();
	lenis = null;
}

function sync() {
	if ( reduced.matches || ! finePointer.matches ) {
		stop();
	} else {
		start();
	}
}

reduced.addEventListener( 'change', sync );
finePointer.addEventListener( 'change', sync );

document.addEventListener( 'daguah:menu-open', () => lenis && lenis.stop() );
document.addEventListener( 'daguah:menu-close', () => lenis && lenis.start() );

sync();
