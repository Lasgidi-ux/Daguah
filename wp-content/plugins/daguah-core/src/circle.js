/**
 * DAGUAH Circle on the page.
 *
 *   - choosing a country picks its usual currency in the form
 *   - the invite link copies with one press
 *   - the board refreshes from the board endpoint while the tab is visible,
 *     so the host sees family join and pay without reloading
 *
 * Both forms submit normally without this script.
 */
import { __, _n, sprintf } from '@wordpress/i18n';

function currencies() {
	document.querySelectorAll( 'select[data-currency-for]' ).forEach( ( select ) => {
		const target = document.querySelector( select.getAttribute( 'data-currency-for' ) );

		if ( ! target ) {
			return;
		}

		select.addEventListener( 'change', () => {
			const option = select.selectedOptions[ 0 ];
			const currency = option && option.getAttribute( 'data-currency' );

			if ( currency ) {
				target.value = currency;
			}
		} );
	} );
}

function copyButtons() {
	document.querySelectorAll( '.daguah-copy[data-copy]' ).forEach( ( button ) => {
		const field = document.querySelector( button.getAttribute( 'data-copy' ) );

		if ( ! field || ! navigator.clipboard ) {
			return;
		}

		const label = button.querySelector( 'svg' ) ? button.lastChild : null;
		const original = label ? label.textContent : '';

		button.hidden = false;
		button.addEventListener( 'click', async () => {
			try {
				await navigator.clipboard.writeText( field.value );
				if ( label ) {
					label.textContent = ` ${ button.getAttribute( 'data-done' ) }`;
					window.setTimeout( () => ( label.textContent = original ), 2000 );
				}
			} catch ( error ) {
				field.select();
			}
		} );
	} );
}

const regionNames =
	typeof Intl.DisplayNames === 'function' ? new Intl.DisplayNames( undefined, { type: 'region' } ) : null;

function memberItem( member ) {
	const li = document.createElement( 'li' );
	li.className = 'daguah-circle-member';

	const initial = document.createElement( 'span' );
	initial.className = 'daguah-circle-member__initial';
	initial.setAttribute( 'aria-hidden', 'true' );
	initial.textContent = member.name.slice( 0, 1 );

	const who = document.createElement( 'span' );
	who.className = 'daguah-circle-member__who';

	const name = document.createElement( 'span' );
	name.className = 'title-4';
	name.textContent = member.name;

	const place = document.createElement( 'span' );
	place.className = 'caption-2 is-muted';
	const country = regionNames && member.country ? regionNames.of( member.country ) : member.country;
	place.textContent = `${ [ member.city, country ].filter( Boolean ).join( ', ' ) } · ${ member.currency }`;

	who.append( name, place );

	const status = document.createElement( 'span' );
	li.append( initial, who, status );

	return li;
}

function setStatus( li, paid ) {
	const status = li.querySelector( '.daguah-circle-member__status' ) || li.lastElementChild;

	status.className = `daguah-circle-member__status caption-4 ${ paid ? 'is-paid' : 'is-waiting' }`;
	status.textContent = paid ? __( 'Paid', 'daguah' ) : __( 'Waiting', 'daguah' );
}

function board() {
	const section = document.querySelector( '.wp-block-daguah-circle-board[data-rest]' );

	if ( ! section ) {
		return;
	}

	const url = new URL( section.getAttribute( 'data-rest' ) );
	url.searchParams.set( 'key', section.getAttribute( 'data-key' ) || '' );

	const seconds = Math.max( 10, Number( section.getAttribute( 'data-poll' ) ) || 20 );
	const list = section.querySelector( '.daguah-circle-members' );
	const paidCount = section.querySelector( '[data-circle-paid]' );
	const spread = section.querySelector( '[data-circle-spread]' );
	const bar = section.querySelector( '.daguah-circle-progress__bar' );
	let timer = null;

	const render = ( data ) => {
		if ( ! list || ! Array.isArray( data.members ) ) {
			return;
		}

		data.members.forEach( ( member, i ) => {
			let li = list.children[ i ];
			if ( ! li ) {
				li = memberItem( member );
				list.append( li );
			}
			setStatus( li, member.paid );
		} );

		if ( paidCount ) {
			paidCount.textContent = String( data.paid );
		}

		const line = paidCount && paidCount.nextSibling;
		if ( line && line.nodeType === Node.TEXT_NODE ) {
			/* translators: %s: number of members */
			line.textContent = ` ${ sprintf( _n( 'of %s paid', 'of %s paid', data.total, 'daguah' ), data.total ) }`;
		}

		if ( spread ) {
			spread.textContent = sprintf(
				/* translators: 1: number of people, 2: number of countries */
				__( '%1$s in the circle, in %2$s', 'daguah' ),
				/* translators: %s: number of people */
				sprintf( _n( '%s person', '%s people', data.total, 'daguah' ), data.total ),
				/* translators: %s: number of countries */
				sprintf( _n( '%s country', '%s countries', data.countries, 'daguah' ), data.countries )
			);
		}

		if ( bar ) {
			bar.setAttribute( 'aria-valuenow', String( data.paid ) );
			bar.setAttribute( 'aria-valuemax', String( data.total ) );
			const fill = bar.querySelector( 'span' );
			if ( fill ) {
				fill.style.width = `${ data.total ? Math.round( ( 100 * data.paid ) / data.total ) : 0 }%`;
			}
		}
	};

	const poll = async () => {
		timer = null;
		try {
			const response = await fetch( url, { credentials: 'same-origin', headers: { Accept: 'application/json' } } );
			if ( response.ok ) {
				render( await response.json() );
			}
		} catch ( error ) {
			// Offline for a moment: try again next time.
		}
		schedule();
	};

	const schedule = () => {
		if ( ! timer && document.visibilityState === 'visible' ) {
			timer = window.setTimeout( poll, seconds * 1000 );
		}
	};

	document.addEventListener( 'visibilitychange', () => {
		if ( document.visibilityState === 'visible' ) {
			poll();
		} else if ( timer ) {
			window.clearTimeout( timer );
			timer = null;
		}
	} );

	schedule();
}

function init() {
	currencies();
	copyButtons();
	board();
}

if ( document.readyState === 'loading' ) {
	document.addEventListener( 'DOMContentLoaded', init );
} else {
	init();
}
