/**
 * Market at Dusk and Market at Noon toggle.
 *
 * Sets data-theme on the root element and remembers the choice. The theme
 * applies a saved choice before first paint, so there is no flash here.
 */
const KEY = 'daguah-theme';

function current() {
	const root = document.documentElement;
	return root.dataset.theme || root.dataset.defaultTheme || 'dusk';
}

function describe( button ) {
	const next = current() === 'dusk' ? 'noon' : 'dusk';
	const label = next === 'noon' ? button.dataset.labelNoon : button.dataset.labelDusk;

	if ( label ) {
		button.setAttribute( 'aria-label', label );
		button.setAttribute( 'title', label );
	}
}

function setUp() {
	const buttons = document.querySelectorAll( '.daguah-theme-toggle' );

	buttons.forEach( ( button ) => {
		describe( button );

		button.addEventListener( 'click', () => {
			const next = current() === 'dusk' ? 'noon' : 'dusk';
			document.documentElement.dataset.theme = next;

			try {
				window.localStorage.setItem( KEY, next );
			} catch ( error ) {
				// Storage can be blocked. The choice still holds for this page.
			}

			buttons.forEach( describe );
		} );
	} );
}

if ( document.readyState === 'loading' ) {
	document.addEventListener( 'DOMContentLoaded', setUp );
} else {
	setUp();
}
