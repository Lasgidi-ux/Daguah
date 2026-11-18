/**
 * DAGUAH shop: one script for every shop feature that depends on the visitor.
 *
 *   Preferences   currency, country and city, kept on this device
 *   Prices        "≈ ₦131,480" next to pound prices, from the current rates
 *   Fit profile   kept on this device; for signed in buyers also on the
 *                 account, merged on sign in (newest value wins per field)
 *   Fit chips     "Fits you", "Needs a small alteration", "Not your size"
 *   Fit Mirror    the panel where measurements are saved
 *   Landed price  item, delivery and duties for the buyer's country
 *   The Thread    redrawn to the buyer's city
 *   Filters       Fits me, currency and country added on submit
 *   Saved search  for signed in buyers
 *
 * Everything the server rendered stays usable without this script.
 */
import { compare, serialise, KEYS } from './fit';

const cfg = window.daguahShop || {};
const doc = document;

/* Storage -------------------------------------------------------------------- */

const store = {
	get( key ) {
		try {
			return JSON.parse( window.localStorage.getItem( `daguah-${ key }` ) );
		} catch ( error ) {
			return null;
		}
	},
	set( key, value ) {
		try {
			if ( value === null ) {
				window.localStorage.removeItem( `daguah-${ key }` );
			} else {
				window.localStorage.setItem( `daguah-${ key }`, JSON.stringify( value ) );
			}
		} catch ( error ) {
			// Storage can be blocked. Choices then last for this page only.
		}
	},
};

/* Preferences ---------------------------------------------------------------- */

const countries = cfg.countries || {};

function regionFromBrowser() {
	const match = /-([A-Za-z]{2})$/.exec( navigator.language || '' );
	const code = match ? match[ 1 ].toUpperCase() : '';
	return countries[ code ] ? code : '';
}

const savedCountry = store.get( 'country' );
const prefs = {
	country: countries[ savedCountry ] ? savedCountry : regionFromBrowser() || 'GB',
	city: store.get( 'city' ) || '',
	currency: '',
};

const savedCurrency = store.get( 'currency' );
prefs.currency = ( cfg.currencies || [] ).includes( savedCurrency )
	? savedCurrency
	: countries[ prefs.country ]?.currency || 'GBP';

function setPrefs( changes ) {
	Object.assign( prefs, changes );
	Object.keys( changes ).forEach( ( key ) => store.set( key, prefs[ key ] ) );
	doc.dispatchEvent( new CustomEvent( 'daguah:prefs', { detail: { ...prefs } } ) );
}

/* Money ---------------------------------------------------------------------- */

const rates = cfg.rates?.rates || { GBP: 1 };
const isDemo = cfg.rates?.source === 'demo';

export function money( amount, currency ) {
	const whole = [ 'NGN', 'KES' ].includes( currency );
	try {
		return new Intl.NumberFormat( undefined, {
			style: 'currency',
			currency,
			// ₦ and GH₵ rather than NGN and GHS.
			currencyDisplay: 'narrowSymbol',
			maximumFractionDigits: whole ? 0 : 2,
			minimumFractionDigits: whole ? 0 : 2,
		} ).format( amount );
	} catch ( error ) {
		return `${ currency } ${ amount.toFixed( 2 ) }`;
	}
}

function renderApprox( root = doc ) {
	root.querySelectorAll( '[data-daguah-gbp]' ).forEach( ( node ) => {
		const gbp = Number( node.dataset.daguahGbp );
		const rate = rates[ prefs.currency ];

		if ( prefs.currency === 'GBP' || ! rate || ! Number.isFinite( gbp ) ) {
			node.hidden = true;
			node.textContent = '';
			return;
		}

		node.hidden = false;
		node.textContent = `≈ ${ money( gbp * rate, prefs.currency ) }`;
		node.title = isDemo ? cfg.i18n.demoRates : '';
	} );
}

/* Shared form controls --------------------------------------------------------- */

function syncControls( root = doc ) {
	root.querySelectorAll( 'select[data-daguah-currency]' ).forEach( ( select ) => {
		select.value = prefs.currency;
	} );
	root.querySelectorAll( 'select[data-daguah-country]' ).forEach( ( select ) => {
		if ( select.dataset.keep && select.value ) {
			return;
		}
		select.value = prefs.country;
	} );
}

doc.addEventListener( 'change', ( event ) => {
	const target = event.target;

	if ( target.matches?.( 'select[data-daguah-currency]' ) ) {
		setPrefs( { currency: target.value } );
	}

	if ( target.matches?.( 'select[data-daguah-country]' ) && ! target.dataset.keep ) {
		setPrefs( { country: target.value, city: '' } );
	}

	if ( target.matches?.( 'select[data-daguah-city]' ) ) {
		setPrefs( { city: target.value } );
	}
} );

/* REST ----------------------------------------------------------------------- */

async function api( path, options = {} ) {
	const headers = { Accept: 'application/json', ...( options.headers || {} ) };
	if ( cfg.nonce ) {
		headers[ 'X-WP-Nonce' ] = cfg.nonce;
	}
	if ( options.body && typeof options.body !== 'string' ) {
		headers[ 'Content-Type' ] = 'application/json';
		options.body = JSON.stringify( options.body );
	}
	const response = await fetch( `${ cfg.rest }${ path }`, {
		credentials: 'same-origin',
		...options,
		headers,
	} );
	if ( ! response.ok ) {
		throw new Error( String( response.status ) );
	}
	return response.json();
}

/* Fit profile ------------------------------------------------------------------ */

const blankProfile = () => ( { unit: 'cm', measurements: {}, updated: '' } );
let profile = store.get( 'fit' ) || blankProfile();

function hasMeasurements() {
	return Object.keys( profile.measurements || {} ).length > 0;
}

function announceFit() {
	doc.dispatchEvent( new CustomEvent( 'daguah:fit', { detail: profile } ) );
}

async function saveProfile( next ) {
	profile = { ...next, updated: new Date().toISOString() };
	store.set( 'fit', profile );
	announceFit();

	if ( ! cfg.loggedIn ) {
		return 'device';
	}

	const body = { unit: profile.unit };
	KEYS.forEach( ( key ) => {
		body[ key ] = profile.measurements[ key ] ?? null;
	} );
	await api( 'fit-profile', { method: 'POST', body } );
	return 'account';
}

// Signed in: merge the account copy with this device's copy.
async function syncProfile() {
	if ( ! cfg.loggedIn ) {
		return;
	}

	try {
		const server = await api( 'fit-profile' );
		const local = store.get( 'fit' ) || blankProfile();
		const serverNewer = ( server.updated || '' ) > ( local.updated || '' );
		const merged = {
			unit: serverNewer ? server.unit : local.unit,
			measurements: serverNewer
				? { ...local.measurements, ...server.measurements }
				: { ...server.measurements, ...local.measurements },
			updated: serverNewer ? server.updated : local.updated,
		};
		const changed = JSON.stringify( merged.measurements ) !== JSON.stringify( server.measurements || {} );

		profile = merged;
		store.set( 'fit', profile );
		announceFit();

		if ( changed && Object.keys( merged.measurements ).length ) {
			await saveProfile( merged );
		}
	} catch ( error ) {
		// The device copy still works; the account copy syncs next visit.
	}
}

/* Units ------------------------------------------------------------------------ */

const toUnit = ( cm, unit ) => ( unit === 'in' ? Math.round( ( cm / 2.54 ) * 10 ) / 10 : cm );
const fromUnit = ( value, unit ) => ( unit === 'in' ? Math.round( value * 2.54 * 10 ) / 10 : value );
const unitLabel = ( unit ) => ( unit === 'in' ? 'in' : 'cm' );

/* Fit chips ------------------------------------------------------------------- */

const STATUS = {
	fits: { label: () => cfg.i18n.fits, cls: 'is-fits' },
	alter: { label: () => cfg.i18n.alter, cls: 'is-alter' },
	no: { label: () => cfg.i18n.no, cls: 'is-no' },
};

function chipDetail( result ) {
	const unit = profile.unit || 'cm';
	return Object.entries( result.diffs )
		.map( ( [ key, diff ] ) => {
			const name = cfg.i18n.measurements[ key ];
			if ( Math.abs( diff ) < 0.05 ) {
				return `${ name }: ${ cfg.i18n.same }`;
			}
			const amount = `${ toUnit( Math.abs( diff ), unit ) } ${ unitLabel( unit ) }`;
			return `${ name }: ${ ( diff > 0 ? cfg.i18n.bigger : cfg.i18n.smaller ).replace( '%s', amount ) }`;
		} )
		.join( '\n' );
}

function renderChips( root = doc ) {
	root.querySelectorAll( '.daguah-fit-chip[data-measurements]' ).forEach( ( chip ) => {
		let piece = {};
		try {
			piece = JSON.parse( chip.dataset.measurements || '{}' );
		} catch ( error ) {
			piece = {};
		}

		if ( ! Object.keys( piece ).length ) {
			chip.hidden = true;
			return;
		}

		const label = chip.querySelector( '.daguah-fit-chip__label' );
		const detail = chip.parentElement.querySelector( '.daguah-fit-chip__detail' );
		chip.classList.remove( 'is-fits', 'is-alter', 'is-no', 'is-unknown' );
		chip.hidden = false;

		if ( ! hasMeasurements() ) {
			chip.classList.add( 'is-unknown' );
			label.textContent = cfg.i18n.check;
			chip.dataset.action = 'open';
			chip.setAttribute( 'aria-expanded', 'false' );
			if ( detail ) {
				detail.hidden = true;
			}
			return;
		}

		const result = compare( piece, profile.measurements );
		if ( ! result ) {
			chip.classList.add( 'is-unknown' );
			label.textContent = cfg.i18n.noOverlap;
			chip.dataset.action = 'open';
			return;
		}

		chip.classList.add( STATUS[ result.status ].cls );
		label.textContent = STATUS[ result.status ].label();
		chip.dataset.action = 'detail';
		if ( detail ) {
			detail.textContent = chipDetail( result );
		}
	} );
}

doc.addEventListener( 'click', ( event ) => {
	const chip = event.target.closest?.( '.daguah-fit-chip' );
	if ( ! chip ) {
		return;
	}
	event.preventDefault();
	event.stopPropagation();

	if ( chip.dataset.action === 'open' ) {
		openMirror( chip );
		return;
	}

	const detail = chip.parentElement.querySelector( '.daguah-fit-chip__detail' );
	if ( detail ) {
		const open = detail.hidden;
		detail.hidden = ! open;
		chip.setAttribute( 'aria-expanded', String( open ) );
	}
} );

/* Fit Mirror ------------------------------------------------------------------ */

const mirror = doc.getElementById( 'daguah-fit-mirror' );
let returnFocus = null;

function fillMirror() {
	if ( ! mirror ) {
		return;
	}
	const unit = profile.unit || 'cm';
	mirror.querySelectorAll( '[data-unit-choice]' ).forEach( ( button ) => {
		button.setAttribute( 'aria-pressed', String( button.dataset.unitChoice === unit ) );
	} );
	mirror.querySelectorAll( '.daguah-fit-mirror__unit' ).forEach( ( node ) => {
		node.textContent = unitLabel( unit );
	} );
	KEYS.forEach( ( key ) => {
		const input = mirror.querySelector( `[name="${ key }"]` );
		if ( input ) {
			const cm = profile.measurements?.[ key ];
			input.value = cm ? toUnit( cm, unit ) : '';
			input.min = unit === 'in' ? 8 : 20;
			input.max = unit === 'in' ? 98 : 250;
		}
	} );
}

function setMessage( text, isError = false ) {
	const node = mirror?.querySelector( '.daguah-fit-mirror__message' );
	if ( node ) {
		node.textContent = text;
		node.classList.toggle( 'is-error', isError );
	}
}

function openMirror( from ) {
	if ( ! mirror ) {
		return;
	}
	returnFocus = from || doc.activeElement;
	fillMirror();
	setMessage( '' );
	mirror.hidden = false;
	requestAnimationFrame( () => mirror.classList.add( 'is-open' ) );
	doc.documentElement.classList.add( 'daguah-menu-open' );
	doc.dispatchEvent( new CustomEvent( 'daguah:menu-open' ) );
	mirror.querySelector( 'input, button' )?.focus( { preventScroll: true } );
}

function closeMirror() {
	if ( ! mirror || mirror.hidden ) {
		return;
	}
	mirror.classList.remove( 'is-open' );
	doc.documentElement.classList.remove( 'daguah-menu-open' );
	doc.dispatchEvent( new CustomEvent( 'daguah:menu-close' ) );
	window.setTimeout( () => {
		mirror.hidden = true;
	}, 320 );
	returnFocus?.focus?.();
}

doc.addEventListener( 'click', ( event ) => {
	if ( event.target.closest?.( '[data-open-fit-mirror]' ) ) {
		event.preventDefault();
		openMirror( event.target.closest( '[data-open-fit-mirror]' ) );
	}
	if ( mirror && ( event.target.closest?.( '[data-close-fit-mirror]' ) || event.target === mirror ) ) {
		closeMirror();
	}
} );

doc.addEventListener( 'keydown', ( event ) => {
	if ( ! mirror || mirror.hidden ) {
		return;
	}
	if ( event.key === 'Escape' ) {
		closeMirror();
	}
	if ( event.key === 'Tab' ) {
		const stops = Array.from( mirror.querySelectorAll( 'button, input, a[href]' ) ).filter( ( n ) => ! n.disabled );
		const first = stops[ 0 ];
		const last = stops[ stops.length - 1 ];
		if ( event.shiftKey && doc.activeElement === first ) {
			event.preventDefault();
			last.focus();
		} else if ( ! event.shiftKey && doc.activeElement === last ) {
			event.preventDefault();
			first.focus();
		}
	}
} );

if ( mirror ) {
	mirror.addEventListener( 'click', ( event ) => {
		const choice = event.target.closest( '[data-unit-choice]' );
		if ( ! choice ) {
			return;
		}
		// Convert what is typed so far, then switch.
		const from = profile.unit || 'cm';
		const to = choice.dataset.unitChoice;
		KEYS.forEach( ( key ) => {
			const input = mirror.querySelector( `[name="${ key }"]` );
			if ( input && input.value !== '' ) {
				input.value = toUnit( fromUnit( Number( input.value ), from ), to );
			}
		} );
		profile = { ...profile, unit: to };
		mirror.querySelectorAll( '[data-unit-choice]' ).forEach( ( b ) => b.setAttribute( 'aria-pressed', String( b === choice ) ) );
		mirror.querySelectorAll( '.daguah-fit-mirror__unit' ).forEach( ( n ) => ( n.textContent = unitLabel( to ) ) );
		KEYS.forEach( ( key ) => {
			const input = mirror.querySelector( `[name="${ key }"]` );
			if ( input ) {
				input.min = to === 'in' ? 8 : 20;
				input.max = to === 'in' ? 98 : 250;
			}
		} );
	} );

	mirror.querySelector( 'form' )?.addEventListener( 'submit', async ( event ) => {
		event.preventDefault();
		const form = event.currentTarget;
		const unit = profile.unit || 'cm';
		const measurements = {};
		let invalid = null;

		KEYS.forEach( ( key ) => {
			const input = form.querySelector( `[name="${ key }"]` );
			input?.removeAttribute( 'aria-invalid' );
			if ( ! input || input.value === '' ) {
				return;
			}
			const cm = fromUnit( Number( input.value ), unit );
			if ( ! Number.isFinite( cm ) || cm < 20 || cm > 250 ) {
				invalid = invalid || input;
				input.setAttribute( 'aria-invalid', 'true' );
				return;
			}
			measurements[ key ] = cm;
		} );

		if ( invalid ) {
			setMessage( unit === 'in' ? cfg.i18n.rangeIn : cfg.i18n.rangeCm, true );
			invalid.focus();
			return;
		}

		if ( ! Object.keys( measurements ).length ) {
			setMessage( cfg.i18n.needOne, true );
			form.querySelector( 'input' )?.focus();
			return;
		}

		const button = form.querySelector( '[type="submit"]' );
		button?.setAttribute( 'aria-busy', 'true' );

		try {
			const where = await saveProfile( { unit, measurements } );
			setMessage( where === 'account' ? cfg.i18n.savedAccount : cfg.i18n.savedDevice );
		} catch ( error ) {
			setMessage( cfg.i18n.savedOffline, true );
		} finally {
			button?.removeAttribute( 'aria-busy' );
		}
	} );

	mirror.querySelector( '[data-clear-fit]' )?.addEventListener( 'click', async () => {
		try {
			await saveProfile( { unit: profile.unit || 'cm', measurements: {} } );
		} catch ( error ) {
			// The device copy is already cleared.
		}
		fillMirror();
		setMessage( cfg.i18n.cleared );
	} );
}

/* Landed price ---------------------------------------------------------------- */

function landedSkeleton( box ) {
	box.querySelector( '.daguah-landed__lines' ).innerHTML =
		'<span class="daguah-skeleton daguah-skeleton--line"></span>'.repeat( 4 );
}

async function renderLanded( box ) {
	const product = box.dataset.product;
	const lines = box.querySelector( '.daguah-landed__lines' );
	const note = box.querySelector( '.daguah-landed__note' );
	if ( ! product || ! lines ) {
		return;
	}

	landedSkeleton( box );
	box.setAttribute( 'aria-busy', 'true' );

	try {
		const data = await api( `landed-price?product=${ product }&country=${ prefs.country }&currency=${ prefs.currency }` );
		const row = ( label, value, cls = '' ) =>
			`<div class="daguah-landed__row ${ cls }"><dt>${ label }</dt><dd>${ value }</dd></div>`;

		if ( ! data.ships ) {
			lines.innerHTML = `<p class="daguah-landed__nope">${ cfg.i18n.noShip.replace( '%s', countries[ prefs.country ]?.name || prefs.country ) }</p>`;
		} else {
			lines.innerHTML =
				row( cfg.i18n.item, money( data.item, data.currency ) ) +
				row(
					cfg.i18n.delivery.replace( '%1$d', data.delivery.min_days ).replace( '%2$d', data.delivery.max_days ),
					money( data.delivery.amount, data.currency )
				) +
				row( cfg.i18n.duties, money( data.duties.amount, data.currency ) ) +
				row( cfg.i18n.total, money( data.total, data.currency ), 'is-total' );
		}

		if ( note ) {
			note.textContent = data.rates.source === 'demo' ? cfg.i18n.demoNote : cfg.i18n.estimateNote;
		}
	} catch ( error ) {
		lines.innerHTML = `<p class="daguah-landed__error" role="alert">${ cfg.i18n.landedError } <button type="button" class="daguah-link-button">${ cfg.i18n.retry }</button></p>`;
		lines.querySelector( 'button' ).addEventListener( 'click', () => renderLanded( box ) );
	} finally {
		box.removeAttribute( 'aria-busy' );
	}
}

/* The Thread ------------------------------------------------------------------- */

function curve( a, b ) {
	const mx = ( a.x + b.x ) / 2;
	const my = ( a.y + b.y ) / 2;
	const dist = Math.hypot( b.x - a.x, b.y - a.y );
	return `M${ a.x } ${ a.y } Q${ Math.round( mx ) } ${ Math.round( my - dist * 0.28 ) } ${ b.x } ${ b.y }`;
}

function renderThread( map ) {
	let data = {};
	try {
		data = JSON.parse( map.dataset.thread || '{}' );
	} catch ( error ) {
		return;
	}

	const dest = data.destinations?.[ prefs.country ];
	if ( ! dest || ! data.origin ) {
		return;
	}

	const city = dest.cities.find( ( c ) => c.name === prefs.city ) || dest.cities[ 0 ];
	const path = map.querySelector( '.daguah-thread__path' );
	const pin = map.querySelector( '.daguah-thread__to' );
	const label = map.querySelector( '.daguah-thread__to-label' );
	const caption = map.querySelector( '.daguah-thread__caption' );
	const citySelect = map.querySelector( 'select[data-daguah-city]' );

	path?.setAttribute( 'd', curve( data.origin, city ) );
	pin?.setAttribute( 'cx', city.x );
	pin?.setAttribute( 'cy', city.y );
	if ( label ) {
		label.setAttribute( 'x', city.x );
		label.setAttribute( 'y', city.y - 10 );
		label.textContent = city.name;
	}

	// Reveal direction follows the thread.
	map.classList.toggle( 'is-westward', city.x < data.origin.x );

	if ( citySelect ) {
		citySelect.innerHTML = dest.cities
			.map( ( c ) => `<option value="${ c.name }"${ c.name === city.name ? ' selected' : '' }>${ c.name }</option>` )
			.join( '' );
	}

	const days = data.days?.[ dest.zone ];
	if ( caption ) {
		caption.textContent = days
			? cfg.i18n.thread
					.replace( '%1$s', data.origin.verb )
					.replace( '%2$s', data.origin.town )
					.replace( '%3$s', city.name )
					.replace( '%4$d', days[ 0 ] )
					.replace( '%5$d', days[ 1 ] )
			: cfg.i18n.threadNoShip
					.replace( '%1$s', data.origin.verb )
					.replace( '%2$s', data.origin.town )
					.replace( '%3$s', dest.name );
	}
}

function revealThreads() {
	const maps = doc.querySelectorAll( '.wp-block-daguah-thread-map' );
	if ( ! ( 'IntersectionObserver' in window ) ) {
		maps.forEach( ( m ) => m.classList.add( 'is-drawn' ) );
		return;
	}
	const observer = new IntersectionObserver(
		( entries ) => {
			entries.forEach( ( entry ) => {
				if ( entry.isIntersecting ) {
					entry.target.classList.add( 'is-drawn' );
					observer.unobserve( entry.target );
				}
			} );
		},
		{ threshold: 0.35 }
	);
	maps.forEach( ( m ) => {
		m.classList.add( 'is-armed' );
		observer.observe( m );
	} );
}

/* Filters --------------------------------------------------------------------- */

function setUpFilters() {
	// On phones the filters start folded away; the summary shows how many are on.
	if ( window.matchMedia( '(max-width: 1023px)' ).matches ) {
		doc.querySelectorAll( 'details.daguah-filters-drawer' ).forEach( ( drawer ) => drawer.removeAttribute( 'open' ) );
	}

	doc.querySelectorAll( 'form.daguah-filters' ).forEach( ( form ) => {
		const fit = form.querySelector( '[name="fit_toggle"]' );
		const fitHidden = form.querySelector( '[name="fit"]' );
		const cur = form.querySelector( '[name="cur"]' );
		const fitHelp = form.querySelector( '.daguah-filters__fit-help' );

		const refreshFit = () => {
			if ( ! fit ) {
				return;
			}
			const ready = hasMeasurements();
			fit.disabled = ! ready;
			if ( fitHelp ) {
				fitHelp.hidden = ready;
			}
			if ( fitHidden && fit.checked && ready ) {
				fitHidden.value = serialise( profile.measurements );
			}
		};

		refreshFit();
		doc.addEventListener( 'daguah:fit', refreshFit );

		if ( cur && ! cur.value ) {
			cur.value = prefs.currency;
		}
		form.querySelectorAll( '.daguah-filters__currency' ).forEach( ( n ) => ( n.textContent = cur?.value || prefs.currency ) );

		form.addEventListener( 'submit', () => {
			if ( fitHidden ) {
				fitHidden.value = fit?.checked && hasMeasurements() ? serialise( profile.measurements ) : '';
				fitHidden.disabled = ! fitHidden.value;
			}
			// Empty fields stay out of the address.
			form.querySelectorAll( 'input, select' ).forEach( ( field ) => {
				if ( field.name && field.value === '' ) {
					field.disabled = true;
				}
			} );
			if ( fit ) {
				fit.disabled = true;
			}
		} );

		// Apply as soon as a choice changes; prices wait for the button.
		form.addEventListener( 'change', ( event ) => {
			if ( event.target.matches( 'input[type="checkbox"], select' ) ) {
				form.requestSubmit();
			}
		} );
	} );
}

/* Short video: load the player only when asked -------------------------------- */

doc.addEventListener( 'click', ( event ) => {
	const play = event.target.closest?.( '.daguah-media__play' );
	if ( ! play ) {
		return;
	}
	const frame = doc.createElement( 'iframe' );
	frame.src = `${ play.dataset.embed }${ play.dataset.embed.includes( '?' ) ? '&' : '?' }autoplay=1`;
	frame.title = play.textContent.trim();
	frame.allow = 'autoplay; fullscreen; picture-in-picture';
	frame.allowFullscreen = true;
	frame.loading = 'lazy';
	frame.className = 'daguah-media__frame';
	play.closest( '.daguah-media__video' ).replaceChildren( frame );
} );

/* Saved search ---------------------------------------------------------------- */

doc.addEventListener( 'click', async ( event ) => {
	const button = event.target.closest?.( '.daguah-save-search' );
	if ( ! button ) {
		return;
	}
	event.preventDefault();
	button.setAttribute( 'aria-busy', 'true' );
	const status = button.parentElement.querySelector( '.daguah-save-search__status' );

	try {
		await api( 'saved-searches', {
			method: 'POST',
			body: { query: window.location.search.replace( /^\?/, '' ) },
		} );
		button.disabled = true;
		button.textContent = cfg.i18n.searchSaved;
		if ( status ) {
			status.textContent = cfg.i18n.searchSavedNote;
		}
	} catch ( error ) {
		if ( status ) {
			status.textContent = error.message === '409' ? cfg.i18n.searchFull : cfg.i18n.searchError;
		}
	} finally {
		button.removeAttribute( 'aria-busy' );
	}
} );

/* Start ---------------------------------------------------------------------- */

function refreshAll( root = doc ) {
	syncControls( root );
	renderApprox( root );
	renderChips( root );
	root.querySelectorAll?.( '.wp-block-daguah-thread-map' ).forEach( renderThread );
}

doc.addEventListener( 'daguah:prefs', ( event ) => {
	refreshAll();
	if ( 'country' in ( event.detail || {} ) || 'currency' in ( event.detail || {} ) ) {
		doc.querySelectorAll( '.wp-block-daguah-landed-price' ).forEach( renderLanded );
	}
} );

doc.addEventListener( 'daguah:fit', () => renderChips() );

// Content swapped in later (the calendar collection) gets the same treatment.
doc.addEventListener( 'daguah:content', ( event ) => refreshAll( event.detail?.root || doc ) );

function start() {
	doc.documentElement.classList.add( 'daguah-shop-ready' );
	refreshAll();
	setUpFilters();
	revealThreads();

	const country = prefs.country;
	const currency = prefs.currency;
	doc.querySelectorAll( '.wp-block-daguah-landed-price' ).forEach( ( box ) => {
		// The server rendered the store default; fetch only if this visitor differs.
		if ( box.dataset.country !== country || box.dataset.currency !== currency ) {
			renderLanded( box );
		}
	} );

	syncProfile();
}

if ( doc.readyState === 'loading' ) {
	doc.addEventListener( 'DOMContentLoaded', start );
} else {
	start();
}
