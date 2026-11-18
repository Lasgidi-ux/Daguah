/**
 * Review screenshots for a build stage.
 *
 * Drives a local Chrome or Edge over the DevTools protocol with real device
 * emulation, so phone shots are a true 390px layout (plain headless
 * screenshots cannot go below about 500px wide). No dependencies.
 *
 *   npm run screenshots                  every stage
 *   npm run screenshots -- stage2        one stage
 *   BASE=http://localhost:8888 npm run screenshots
 *   BROWSER="C:/path/to/chrome.exe" npm run screenshots
 */
import { spawn } from 'node:child_process';
import { existsSync, mkdirSync, writeFileSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = join( dirname( fileURLToPath( import.meta.url ) ), '..' );
const out = join( root, 'docs/screenshots' );
const base = ( process.env.BASE || 'http://127.0.0.1:9400' ).replace( /\/$/, '' );
const port = 9333;

// A script that returns the address to open, read from the page at `path`.
const firstRow = 'document.querySelector("a.row-title, td.username a")?.href';

// Close the block editor's welcome guide and show the post sidebar.
const editorReady = `(() => {
	const data = window.wp?.data;
	document.querySelector('.components-guide button[aria-label^="Close"], .components-modal__header button[aria-label^="Close"]')?.click();
	data?.dispatch('core/preferences')?.set('core/edit-post', 'welcomeGuide', false);
	data?.dispatch('core/edit-post')?.openGeneralSidebar('edit-post/document');
	const panel = 'daguah-details/daguah-details';
	if (data && !data.select('core/editor').isEditorPanelOpened(panel)) {
		data.dispatch('core/editor').toggleEditorPanelOpened(panel);
	}
	setTimeout(() => document.querySelector('.daguah-details, [class*="daguah-details"]')?.scrollIntoView({ block: 'start' }), 300);
})()`;

// A visitor who has saved measurements in Fit Mirror and chosen naira.
const personalise = `(() => {
	localStorage.setItem('daguah-fit', JSON.stringify({ unit: 'cm', measurements: { bust: 92, waist: 76, hip: 104, length: 118 }, updated: '2026-11-16T00:00:00Z' }));
	localStorage.setItem('daguah-currency', JSON.stringify('NGN'));
	localStorage.setItem('daguah-country', JSON.stringify('GB'));
})()`;
const forget = `(() => { ['fit', 'currency', 'country', 'city'].forEach((k) => localStorage.removeItem('daguah-' + k)); })()`;

const ALL_SHOTS = [
	[ 'stage5-stall-desktop', '/store/iya-adire-studio/', 1280, 800, true, '', null, 3000, forget ],
	[ 'stage5-stall-mobile', '/store/iya-adire-studio/', 390, 844, true, '', null, 3000, forget ],
	[ 'stage5-stalls', '/stalls/', 1280, 800, true, '', null, 2500 ],
	[ 'stage5-stalls-mobile', '/stalls/?from=GH', 390, 844, true, '', null, 2500 ],
	[ 'stage5-occasions', '/occasions/', 1280, 800, true, '', null, 2500 ],
	[ 'stage5-occasion', '/occasion/wedding-guest-aso-ebi/', 1280, 800, true, '', null, 3000, forget ],
	[ 'stage5-circle', '/circle/', 1280, 800, true, '', null, 2500 ],
	[ 'stage5-circle-board', '/wp-admin/edit.php?post_type=circle', 1280, 800, true, '', 'location.origin + "/circle/?c=" + new URL(document.querySelector("a.row-title").href).searchParams.get("post")', 3000 ],
	[ 'stage5-circle-board-mobile', '/wp-admin/edit.php?post_type=circle', 390, 844, true, '', 'location.origin + "/circle/?c=" + new URL(document.querySelector("a.row-title").href).searchParams.get("post")', 3000 ],
	[ 'stage5-send-home', '/send-home/', 1280, 800, true, '', null, 3000, forget ],
	[ 'stage5-send-home-mobile', '/send-home/?to=GH', 390, 844, true, '', null, 3000, forget ],
	[ 'stage5-live', '/live/', 1280, 800, true, '', null, 2500 ],
	[ 'stage5-live-session', '/live/lace-for-the-wedding-season-live-from-makola/', 1280, 800, true, '', null, 3000, forget ],
	[ 'stage5-live-session-mobile', '/live/lace-for-the-wedding-season-live-from-makola/', 390, 844, true, '', null, 3000, forget ],
	[ 'stage5-stall-noon', '/store/iya-adire-studio/?mode=noon', 1280, 800, false, '', null, 3000 ],
	// name, path, width, height of the viewport, full page, before capture, find (optional), wait ms (optional), prime (optional: run on a first load, then reload)
	[ 'stage4-shop-desktop', '/shop/', 1280, 800, true, '', null, 2500, forget ],
	[ 'stage4-shop-personal', '/shop/', 1280, 800, false, 'scrollTo(0, 260)', null, 3000, personalise ],
	[ 'stage4-shop-mobile', '/shop/?fabric%5B%5D=lace', 390, 844, true, '', null, 2500, forget ],
	[ 'stage4-product-desktop', '/product/indigo-adire-wrap-skirt-hand-dyed/', 1280, 800, true, '', null, 4000, forget ],
	[ 'stage4-product-fit', '/product/kitenge-wrap-dress-sunrise-print/', 1280, 800, false, '', null, 4000, personalise ],
	[ 'stage4-product-mobile', '/product/indigo-adire-wrap-skirt-hand-dyed/', 390, 844, true, '', null, 4000, forget ],
	[ 'stage4-passport', '/product/preloved-agbada-in-cream-damask-worn-once/', 1280, 800, true, '', null, 4000, forget ],
	[ 'stage4-fit-mirror', '/shop/', 1280, 800, false, 'document.querySelector("[data-open-fit-mirror]").click()', null, 2500, personalise ],
	// The Opening's first screen, after the lanterns warm and the headline settles (about 2.5s).
	[ 'stage3-opening-desktop', '/', 1440, 900, false, '', null, 2600 ],
	[ 'stage3-opening-mobile', '/', 390, 844, false, '', null, 2600 ],
	[ 'stage3-home-desktop', '/', 1280, 800, true, '', null, 2600 ],
	[ 'stage3-home-noon', '/?mode=noon', 1280, 800, true, '', null, 2600 ],
	[ 'stage3-home-mobile', '/', 390, 844, true, '', null, 2600 ],
	[ 'stage2-settings', '/wp-admin/admin.php?page=daguah', 1280, 800, true ],
	[ 'stage2-product-tab', '/wp-admin/edit.php?post_type=product&s=Indigo+adire+wrap', 1280, 800, true, 'document.querySelector(".daguah_options a, .daguah_tab a")?.click(); document.getElementById("woocommerce-product-data")?.scrollIntoView()', firstRow, 2500 ],
	[ 'stage2-stall-fields', '/wp-admin/users.php?role=seller&orderby=login', 1280, 800, true, '', 'document.querySelector("#the-list a[href*=\\"user-edit.php\\"]")?.href' ],
	[ 'stage2-moment-editor', '/wp-admin/edit.php?post_type=calendar_moment&orderby=menu_order&order=asc', 1280, 900, false, editorReady, firstRow, 9000 ],
	[ 'stage2-live-editor', '/wp-admin/edit.php?post_type=live_session', 1280, 900, false, editorReady, firstRow, 9000 ],
	[ 'stage2-journal-desktop', '/how-to-tell-real-kente-from-printed-kente/', 1280, 800, true ],
	[ 'stage2-journal-mobile', '/how-to-tell-real-kente-from-printed-kente/', 390, 844, true ],
	[ 'stage1-desktop-dusk', '/', 1280, 800, true ],
	[ 'stage1-desktop-noon', '/?mode=noon', 1280, 800, true ],
	[ 'stage1-mobile-dusk', '/', 390, 844, true ],
	[ 'stage1-mobile-noon', '/?mode=noon', 390, 844, true ],
	[ 'stage1-mobile-menu', '/', 390, 844, false, 'document.querySelector(".daguah-menu-toggle").click()' ],
	[ 'stage1-tablet-dusk', '/', 768, 1024, false ],
	// A missing post ID, because Playground sends unknown pretty URLs to the home page.
	[ 'stage1-404', '/?p=999999', 1280, 800, true ],
	[ 'stage1-404-mobile', '/?p=999999', 390, 844, false ],
];

// Arguments pick a stage ("stage2") or exact shot names ("stage2-settings").
const picks = process.argv.slice( 2 ).filter( ( arg ) => /^stage\d+/.test( arg ) );
const SHOTS = picks.length
	? ALL_SHOTS.filter( ( [ name ] ) => picks.some( ( pick ) => name === pick || ( /^stage\d+$/.test( pick ) && name.startsWith( `${ pick }-` ) ) ) )
	: ALL_SHOTS;

const CANDIDATES = [
	process.env.BROWSER,
	'C:/Program Files/Google/Chrome/Application/chrome.exe',
	'C:/Program Files (x86)/Google/Chrome/Application/chrome.exe',
	'C:/Program Files (x86)/Microsoft/Edge/Application/msedge.exe',
	'/Applications/Google Chrome.app/Contents/MacOS/Google Chrome',
	'/usr/bin/google-chrome',
	'/usr/bin/chromium',
].filter( Boolean );

const browserPath = CANDIDATES.find( ( path ) => existsSync( path ) );

if ( ! browserPath ) {
	console.error( 'No Chrome or Edge found. Set BROWSER to its path.' );
	process.exit( 1 );
}

const wait = ( ms ) => new Promise( ( resolve ) => setTimeout( resolve, ms ) );

// A profile of its own, so it never touches the browser you have open. One
// fixed folder, reused every run, so nothing piles up in the temp folder.
const profile = join( root, '.cache/shots-profile' );
mkdirSync( profile, { recursive: true } );
const browser = spawn(
	browserPath,
	[
		'--headless=new',
		`--remote-debugging-port=${ port }`,
		`--user-data-dir=${ profile }`,
		'--no-first-run',
		'--no-default-browser-check',
		'--hide-scrollbars',
		'about:blank',
	],
	{ stdio: 'ignore' }
);

async function pageSocket() {
	for ( let i = 0; i < 50; i++ ) {
		try {
			const targets = await ( await fetch( `http://127.0.0.1:${ port }/json` ) ).json();
			const page = targets.find( ( target ) => target.type === 'page' );
			if ( page ) {
				return page.webSocketDebuggerUrl;
			}
		} catch {
			// Not listening yet.
		}
		await wait( 200 );
	}
	throw new Error( 'The browser did not start its debugging port.' );
}

function connect( url ) {
	const socket = new WebSocket( url );
	let id = 0;
	const pending = new Map();
	const listeners = new Map();

	socket.addEventListener( 'message', ( event ) => {
		const message = JSON.parse( event.data );
		if ( message.id && pending.has( message.id ) ) {
			const { resolve, reject } = pending.get( message.id );
			pending.delete( message.id );
			if ( message.error ) {
				reject( new Error( message.error.message ) );
			} else {
				resolve( message.result );
			}
		} else if ( message.method && listeners.has( message.method ) ) {
			listeners.get( message.method )();
			listeners.delete( message.method );
		}
	} );

	return new Promise( ( resolve ) => {
		socket.addEventListener( 'open', () =>
			resolve( {
				send: ( method, params = {} ) =>
					new Promise( ( ok, fail ) => {
						id++;
						pending.set( id, { resolve: ok, reject: fail } );
						socket.send( JSON.stringify( { id, method, params } ) );
					} ),
				once: ( method ) => new Promise( ( ok ) => listeners.set( method, ok ) ),
				close: () => socket.close(),
			} )
		);
	} );
}

try {
	mkdirSync( out, { recursive: true } );
	const cdp = await connect( await pageSocket() );
	await cdp.send( 'Page.enable' );

	const open = async ( url ) => {
		const loaded = cdp.once( 'Page.loadEventFired' );
		await cdp.send( 'Page.navigate', { url } );
		await Promise.race( [ loaded, wait( 120000 ) ] );
	};

	for ( const [ name, path, width, height, fullPage, before, find, settle = 1200, prime ] of SHOTS ) {
		const mobile = width < 768;

		await cdp.send( 'Emulation.setDeviceMetricsOverride', {
			width,
			height,
			deviceScaleFactor: mobile ? 2 : 1,
			mobile,
		} );
		await cdp.send( 'Emulation.setTouchEmulationEnabled', { enabled: mobile } );

		await open( base + path );

		// Set up what this visitor has saved, then load the page fresh.
		if ( prime ) {
			await cdp.send( 'Runtime.evaluate', { expression: prime } );
			await open( base + path );
		}

		if ( find ) {
			const { result: target } = await cdp.send( 'Runtime.evaluate', { expression: find, returnByValue: true } );
			if ( ! target.value ) {
				console.log( `${ name }.png  SKIPPED: nothing to open on ${ path }` );
				continue;
			}
			await open( target.value );
		}

		await cdp.send( 'Runtime.evaluate', { expression: 'document.fonts.ready', awaitPromise: true } );

		// Editors and admin screens finish rendering after load.
		await wait( settle );

		if ( before ) {
			await cdp.send( 'Runtime.evaluate', { expression: before } );
		}

		// Let reveals and the menu sequence finish (all under 1s).
		await wait( 1200 );

		if ( fullPage ) {
			const { result } = await cdp.send( 'Runtime.evaluate', {
				expression: 'document.documentElement.scrollHeight',
				returnByValue: true,
			} );
			// Scroll through once so every reveal fires, then back to the top.
			await cdp.send( 'Runtime.evaluate', {
				expression: `(async () => { for (let y = 0; y < ${ result.value }; y += ${ height / 2 }) { scrollTo(0, y); await new Promise(r => setTimeout(r, 60)); } scrollTo(0, 0); })()`,
				awaitPromise: true,
			} );
			await wait( 900 );

			// Grow the viewport to the whole page. Capturing beyond the viewport
			// repeats the page when a phone is emulated, so this is the reliable way.
			// Full pages are shot at 1x to keep the files a sensible size.
			await cdp.send( 'Emulation.setDeviceMetricsOverride', {
				width,
				height: result.value,
				deviceScaleFactor: 1,
				mobile,
			} );
			await wait( 600 );
		}

		const { data } = await cdp.send( 'Page.captureScreenshot', { format: 'png' } );

		const { result: overflow } = await cdp.send( 'Runtime.evaluate', {
			expression: 'document.documentElement.scrollWidth - innerWidth',
			returnByValue: true,
		} );

		writeFileSync( join( out, `${ name }.png` ), Buffer.from( data, 'base64' ) );
		console.log(
			`${ name }.png  ${ width }px  ${ overflow.value > 0 ? `OVERFLOWS by ${ overflow.value }px` : 'no sideways scroll' }`
		);
	}

	cdp.close();
} finally {
	browser.kill();
}
