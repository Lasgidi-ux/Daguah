/**
 * Mobile menu toggle.
 *
 * Without JS the toggle is a link to #daguah-mobile-menu and the panel opens
 * through :target. With JS it behaves as a button: it keeps focus inside the
 * open panel, closes on Escape, and returns focus to the toggle.
 */
const PANEL_ID = 'daguah-mobile-menu';

function setUp() {
	const toggle = document.querySelector( '.daguah-menu-toggle' );
	const panel = document.getElementById( PANEL_ID );

	if ( ! toggle || ! panel ) {
		return;
	}

	const root = document.documentElement;
	const labels = {
		open: toggle.dataset.labelOpen || 'Open menu',
		close: toggle.dataset.labelClose || 'Close menu',
	};

	panel.setAttribute( 'role', 'dialog' );
	panel.setAttribute( 'aria-modal', 'true' );
	panel.setAttribute( 'aria-label', toggle.dataset.panelLabel || 'Menu' );

	const focusable = () =>
		Array.from(
			panel.querySelectorAll( 'a[href], button:not([disabled]), input:not([disabled])' )
		);

	function setOpen( open ) {
		panel.classList.toggle( 'is-open', open );
		root.classList.toggle( 'daguah-menu-open', open );
		toggle.setAttribute( 'aria-expanded', String( open ) );
		toggle.setAttribute( 'aria-label', open ? labels.close : labels.open );
		document.dispatchEvent(
			new CustomEvent( open ? 'daguah:menu-open' : 'daguah:menu-close' )
		);

		if ( open ) {
			const first = focusable()[ 0 ];
			if ( first ) {
				first.focus( { preventScroll: true } );
			}
		}
	}

	const isOpen = () => panel.classList.contains( 'is-open' );

	toggle.addEventListener( 'click', ( event ) => {
		event.preventDefault();
		setOpen( ! isOpen() );
	} );

	toggle.addEventListener( 'keydown', ( event ) => {
		if ( event.key === ' ' ) {
			event.preventDefault();
			setOpen( ! isOpen() );
		}
	} );

	document.addEventListener( 'keydown', ( event ) => {
		if ( ! isOpen() ) {
			return;
		}

		if ( event.key === 'Escape' ) {
			setOpen( false );
			toggle.focus();
			return;
		}

		if ( event.key !== 'Tab' ) {
			return;
		}

		// Focus moves between the toggle and the panel, and nowhere else.
		const stops = [ toggle, ...focusable() ];
		const first = stops[ 0 ];
		const last = stops[ stops.length - 1 ];

		if ( event.shiftKey && document.activeElement === first ) {
			event.preventDefault();
			last.focus();
		} else if ( ! event.shiftKey && document.activeElement === last ) {
			event.preventDefault();
			first.focus();
		}
	} );

	// Following a link in the panel closes it.
	panel.addEventListener( 'click', ( event ) => {
		if ( event.target.closest( 'a[href]' ) ) {
			setOpen( false );
		}
	} );

	// The panel is for narrow screens only.
	const wide = window.matchMedia( '(min-width: 960px)' );
	wide.addEventListener( 'change', () => {
		if ( wide.matches && isOpen() ) {
			setOpen( false );
		}
	} );
}

if ( document.readyState === 'loading' ) {
	document.addEventListener( 'DOMContentLoaded', setUp );
} else {
	setUp();
}
