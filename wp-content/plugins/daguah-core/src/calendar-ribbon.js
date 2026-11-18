/**
 * Cultural calendar: choose a moment, swap the collection in place.
 *
 * The moment links work without this script (they go to the occasion page).
 * With it, they behave as tabs: click or arrow keys select, the collection
 * shows skeleton cards while loading, then crossfades to the new pieces.
 * Responses are kept for the visit, so switching back is instant.
 */
const SKELETON_CARDS = 4;

function skeleton() {
	const card =
		'<div class="daguah-card" aria-hidden="true">' +
		'<span class="daguah-skeleton daguah-skeleton--media"></span>' +
		'<div class="daguah-card__body">' +
		'<span class="daguah-skeleton daguah-skeleton--line daguah-skeleton--short"></span>' +
		'<span class="daguah-skeleton daguah-skeleton--line"></span>' +
		'<span class="daguah-skeleton daguah-skeleton--line daguah-skeleton--short"></span>' +
		'</div></div>';
	return `<div class="daguah-product-grid">${ card.repeat( SKELETON_CARDS ) }</div>`;
}

function setUp( root ) {
	const endpoint = root.dataset.endpoint;
	const tabs = Array.from( root.querySelectorAll( '.daguah-calendar__moment' ) );
	const panel = root.querySelector( '.daguah-calendar__panel' );
	const collection = root.querySelector( '.daguah-calendar__collection' );
	const excerpt = root.querySelector( '.daguah-calendar__excerpt' );
	const note = root.querySelector( '.daguah-calendar__note' );
	const all = root.querySelector( '.daguah-calendar__all' );
	const cache = new Map();
	let current = 0;

	if ( ! endpoint || ! tabs.length || ! collection ) {
		return;
	}

	async function load( id ) {
		if ( ! cache.has( id ) ) {
			const response = await fetch( `${ endpoint }${ id }/collection`, {
				headers: { Accept: 'application/json' },
			} );
			if ( ! response.ok ) {
				throw new Error( String( response.status ) );
			}
			cache.set( id, await response.json() );
		}
		return cache.get( id );
	}

	function showError( tab ) {
		collection.classList.remove( 'is-loading' );
		collection.innerHTML =
			'<div class="daguah-calendar__error" role="alert">' +
			'<p class="body-6">These pieces did not load. Check your connection and try again.</p>' +
			'<button type="button" class="wp-element-button is-style-outline">Try again</button>' +
			'</div>';
		collection.querySelector( 'button' ).addEventListener( 'click', () => select( tab, true ) );
	}

	async function select( tab, force = false ) {
		const index = tabs.indexOf( tab );
		if ( index === current && ! force ) {
			return;
		}

		tabs.forEach( ( t, i ) => {
			const on = i === index;
			t.classList.toggle( 'is-selected', on );
			t.setAttribute( 'aria-selected', String( on ) );
			t.tabIndex = on ? 0 : -1;
		} );
		panel.setAttribute( 'aria-labelledby', tab.id );
		current = index;

		const id = tab.dataset.moment;
		if ( ! cache.has( id ) ) {
			collection.innerHTML = skeleton();
		}
		collection.classList.add( 'is-loading' );

		try {
			const data = await load( id );
			if ( current !== index ) {
				return;
			}
			if ( excerpt ) {
				excerpt.textContent = tab.dataset.excerpt || '';
			}
			if ( note ) {
				note.textContent = tab.dataset.note || '';
				note.hidden = ! tab.dataset.note;
			}
			if ( all ) {
				all.href = data.link;
			}
			collection.innerHTML = data.html;
			collection.classList.remove( 'is-loading' );
			// Fit chips and prices in the new cards.
			document.dispatchEvent( new CustomEvent( 'daguah:content', { detail: { root: collection } } ) );
		} catch ( error ) {
			if ( current === index ) {
				showError( tab );
			}
		}
	}

	tabs.forEach( ( tab ) => {
		tab.addEventListener( 'click', ( event ) => {
			event.preventDefault();
			select( tab );
		} );

		tab.addEventListener( 'keydown', ( event ) => {
			const moves = { ArrowRight: 1, ArrowLeft: -1, Home: -Infinity, End: Infinity };
			if ( ! ( event.key in moves ) ) {
				return;
			}
			event.preventDefault();
			const step = moves[ event.key ];
			let next = Number.isFinite( step ) ? current + step : step < 0 ? 0 : tabs.length - 1;
			next = ( next + tabs.length ) % tabs.length;
			tabs[ next ].focus();
			tabs[ next ].scrollIntoView( { block: 'nearest', inline: 'nearest' } );
			select( tabs[ next ] );
		} );

		// Warm the cache on hover so the swap feels instant.
		tab.addEventListener( 'pointerenter', () => load( tab.dataset.moment ).catch( () => {} ), { once: true } );
	} );
}

function init() {
	document.querySelectorAll( '.wp-block-daguah-calendar-ribbon' ).forEach( setUp );
}

if ( document.readyState === 'loading' ) {
	document.addEventListener( 'DOMContentLoaded', init );
} else {
	init();
}
