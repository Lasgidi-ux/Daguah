/**
 * DAGUAH theme front end.
 *
 * Scroll reveals only. Each reveal runs once, through IntersectionObserver,
 * never a scroll listener. The mobile menu and theme toggle ship with their
 * own blocks in daguah-core and load only where those blocks are used.
 */
import './css/main.css';
import './css/home.css';
import './css/shop.css';
import './css/market.css';

/**
 * The Thread as a loading bar: a gold line grows across the top as soon as
 * a link to another page on the site is followed.
 */
function setUpLoadingBar() {
	const bar = document.createElement( 'div' );
	bar.className = 'daguah-loading';
	bar.setAttribute( 'aria-hidden', 'true' );
	document.body.appendChild( bar );

	document.addEventListener( 'click', ( event ) => {
		const link = event.target.closest?.( 'a[href]' );
		if (
			! link ||
			event.defaultPrevented ||
			event.metaKey ||
			event.ctrlKey ||
			event.shiftKey ||
			link.target === '_blank' ||
			link.hasAttribute( 'download' ) ||
			link.origin !== window.location.origin ||
			( link.pathname === window.location.pathname && link.hash )
		) {
			return;
		}
		bar.classList.add( 'is-active' );
	} );

	// Coming back with the back button restores the page as it was.
	window.addEventListener( 'pageshow', () => bar.classList.remove( 'is-active' ) );
}

/**
 * Unrolling the bolt. Browsers with cross document view transitions animate
 * between shop pages in CSS; the rest get a short settle on arrival.
 */
function setUpUnroll() {
	const body = document.body;
	if ( body.classList.contains( 'daguah-shop-page' ) && ! ( 'startViewTransition' in document ) ) {
		body.classList.add( 'is-unrolling' );
	}
}

function setUpReveals() {
	const items = document.querySelectorAll( '.daguah-reveal' );

	if ( ! items.length ) {
		return;
	}

	if ( ! ( 'IntersectionObserver' in window ) ) {
		items.forEach( ( item ) => item.classList.add( 'is-revealed' ) );
		return;
	}

	const observer = new IntersectionObserver(
		( entries ) => {
			entries.forEach( ( entry ) => {
				if ( entry.isIntersecting ) {
					entry.target.classList.add( 'is-revealed' );
					observer.unobserve( entry.target );
				}
			} );
		},
		{ rootMargin: '0px 0px -10% 0px', threshold: 0.1 }
	);

	items.forEach( ( item ) => observer.observe( item ) );
}

/**
 * The Opening's film. It plays only from here, so visitors who prefer
 * reduced motion or have asked to save data keep the still. The button
 * pauses and plays it, and the choice is remembered on this device. Off
 * screen it rests, to save battery.
 */
function setUpFilm() {
	const film = document.querySelector( '.daguah-opening__film' );

	if ( ! film ) {
		return;
	}

	const button = film.parentElement.querySelector( '.daguah-opening__pause' );

	if ( film.dataset.posterPortrait && window.matchMedia( '(max-aspect-ratio: 4/5)' ).matches ) {
		film.poster = film.dataset.posterPortrait;
	}

	let chosen = '';
	try {
		chosen = window.localStorage.getItem( 'daguah-film' ) || '';
	} catch ( error ) {
		// Storage blocked: the default applies.
	}

	const reduce = window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;
	const saveData = Boolean( navigator.connection && navigator.connection.saveData );
	let wanted = chosen ? chosen === 'play' : ! reduce && ! saveData;

	const show = ( playing ) => {
		if ( ! button ) {
			return;
		}
		button.classList.toggle( 'is-paused', ! playing );
		button.setAttribute( 'aria-label', playing ? button.dataset.labelPause : button.dataset.labelPlay );
	};

	const play = () => {
		const started = film.play();
		if ( started && started.catch ) {
			started.catch( () => show( false ) );
		}
		show( true );
	};

	if ( button ) {
		button.hidden = false;
		button.addEventListener( 'click', () => {
			wanted = film.paused;
			if ( wanted ) {
				play();
			} else {
				film.pause();
				show( false );
			}
			try {
				window.localStorage.setItem( 'daguah-film', wanted ? 'play' : 'pause' );
			} catch ( error ) {
				// Not remembered; fine for this visit.
			}
		} );
	}

	if ( wanted ) {
		play();
	} else {
		show( false );
	}

	if ( 'IntersectionObserver' in window ) {
		new IntersectionObserver( ( [ entry ] ) => {
			if ( ! entry.isIntersecting ) {
				film.pause();
			} else if ( wanted && film.paused ) {
				play();
			}
		} ).observe( film );
	}
}

function start() {
	setUpFilm();
	setUpReveals();
	setUpLoadingBar();
	setUpUnroll();
}

if ( document.readyState === 'loading' ) {
	document.addEventListener( 'DOMContentLoaded', start );
} else {
	start();
}
