/**
 * Record a walkthrough of the site as video.
 *
 * Drives a headless Chrome or Edge through the tour below, captures every
 * frame the browser paints (so the hero film, lanterns and reveals are all
 * real), and joins the pages with short dissolves. Page loads are cut out,
 * so the video shows the site, not the waiting. The admin bar is hidden.
 *
 *   npm run record             desktop (1440 x 900) and phone (390 x 844)
 *   npm run record -- desktop  just one of them
 *
 * With the site running (npm run playground). BASE sets the address.
 * Writes docs/recordings/daguah-desktop.mp4 and daguah-phone.mp4.
 * Needs ffmpeg on the PATH.
 */
import { spawn, spawnSync } from 'node:child_process';
import { existsSync, mkdirSync, rmSync, writeFileSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = join( dirname( fileURLToPath( import.meta.url ) ), '..' );
const out = join( root, 'docs/recordings' );
const work = join( root, '.cache/recording' );
const base = ( process.env.BASE || 'http://127.0.0.1:9400' ).replace( /\/$/, '' );
const port = 9334;

const wait = ( ms ) => new Promise( ( resolve ) => setTimeout( resolve, ms ) );

/*
 * The tour. Each stop: a label, an address (or a script that returns one,
 * run on the page before), and what to do there. Scroll steps are
 * [ 'to', y or 'end' or a selector, milliseconds ]; hold steps are [ 'hold', ms ];
 * script steps are [ 'run', js ].
 */
// The sample circle (five members, five countries), else the newest.
const CIRCLE_BOARD = `(() => { const rows = [...document.querySelectorAll("a.row-title")]; const row = rows.find((a) => a.textContent.includes("Chidinma")) || rows[0]; return location.origin + "/circle/?c=" + new URL(row.href).searchParams.get("post"); })()`;

const TOURS = {
	desktop: {
		width: 1440,
		height: 900,
		scale: 1,
		stops: [
			[ 'Home', '/', [ [ 'hold', 7000 ], [ 'to', 'end', 42000 ], [ 'hold', 1200 ] ] ],
			[ 'Shop', '/shop/', [ [ 'hold', 2200 ], [ 'to', 900, 4000 ], [ 'hold', 1200 ] ] ],
			[ 'Product', '/product/indigo-adire-wrap-skirt-hand-dyed/', [ [ 'hold', 2500 ], [ 'to', '.wp-block-daguah-thread-map', 3500 ], [ 'hold', 3500 ], [ 'to', 'end', 6000 ], [ 'hold', 800 ] ] ],
			[ 'Stall', '/store/iya-adire-studio/', [ [ 'hold', 3000 ], [ 'to', '.wp-block-daguah-stall-pieces', 3500 ], [ 'hold', 2000 ] ] ],
			[ 'Stalls', '/stalls/', [ [ 'hold', 1500 ], [ 'to', 700, 3500 ], [ 'hold', 1200 ] ] ],
			[ 'Occasion', '/occasion/wedding-guest-aso-ebi/', [ [ 'hold', 3000 ], [ 'to', 500, 3000 ], [ 'hold', 1000 ] ] ],
			[ 'Circle', { from: '/wp-admin/edit.php?post_type=circle', find: CIRCLE_BOARD }, [ [ 'hold', 2000 ], [ 'to', '#board', 2500 ], [ 'hold', 3000 ], [ 'to', '#join', 3000 ], [ 'hold', 1500 ] ] ],
			[ 'Send Home', '/send-home/', [ [ 'hold', 2000 ], [ 'to', 800, 4000 ], [ 'to', '.wp-block-daguah-gift-note', 3500 ], [ 'run', 'document.querySelector("#daguah-note-to").value = "Mummy"; document.querySelector("#daguah-note-message").value = "Wear it to church on Sunday and think of me. I will call after the service."; document.querySelector("#daguah-note-from").value = "Tolu"; document.querySelector("#daguah-note-message").dispatchEvent(new Event("input", { bubbles: true }));' ], [ 'hold', 3500 ] ] ],
			[ 'Market Live', '/live/', [ [ 'hold', 2500 ], [ 'to', 600, 3000 ], [ 'hold', 1000 ] ] ],
			[ 'Session', '/live/lace-for-the-wedding-season-live-from-makola/', [ [ 'hold', 3500 ], [ 'to', '.wp-block-daguah-live-tray', 3500 ], [ 'hold', 2000 ] ] ],
			[ 'Home again', '/', [ [ 'hold', 6000 ] ] ],
		],
	},
	phone: {
		width: 390,
		height: 844,
		scale: 2,
		stops: [
			[ 'Home', '/', [ [ 'hold', 6500 ], [ 'to', 'end', 38000 ], [ 'hold', 1000 ] ] ],
			[ 'Product', '/product/indigo-adire-wrap-skirt-hand-dyed/', [ [ 'hold', 2000 ], [ 'to', '.wp-block-daguah-landed-price', 4000 ], [ 'hold', 2000 ], [ 'to', '.wp-block-daguah-thread-map', 3500 ], [ 'hold', 3000 ] ] ],
			[ 'Stall', '/store/iya-adire-studio/', [ [ 'hold', 2500 ], [ 'to', 1100, 5000 ], [ 'hold', 1000 ] ] ],
			[ 'Circle', { from: '/wp-admin/edit.php?post_type=circle', find: CIRCLE_BOARD }, [ [ 'hold', 1500 ], [ 'to', '#board', 2500 ], [ 'hold', 2000 ], [ 'to', '#join', 5000 ], [ 'hold', 1200 ] ] ],
			[ 'Send Home', '/send-home/?to=GH', [ [ 'hold', 1500 ], [ 'to', '.daguah-send__summary', 3000 ], [ 'hold', 1500 ], [ 'to', 2600, 5000 ], [ 'hold', 1000 ] ] ],
			[ 'Session', '/live/lace-for-the-wedding-season-live-from-makola/', [ [ 'hold', 3000 ], [ 'to', 700, 3000 ], [ 'hold', 1500 ] ] ],
		],
	},
};

const picked = process.argv.slice( 2 ).filter( ( arg ) => arg in TOURS );
const tours = picked.length ? picked : Object.keys( TOURS );

/* Browser --------------------------------------------------------------------- */

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

const profile = join( root, '.cache/record-profile' );
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
		'--autoplay-policy=no-user-gesture-required',
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
	const handlers = new Map();

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
		} else if ( message.method && handlers.has( message.method ) ) {
			handlers.get( message.method )( message.params );
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
				on: ( method, handler ) => handlers.set( method, handler ),
				off: ( method ) => handlers.delete( method ),
				close: () => socket.close(),
			} )
		);
	} );
}

function ffmpeg( args ) {
	const result = spawnSync( 'ffmpeg', [ '-v', 'error', '-y', ...args ], { stdio: 'inherit' } );
	if ( result.status !== 0 ) {
		throw new Error( `ffmpeg failed: ${ args.join( ' ' ) }` );
	}
}

/* In the page ------------------------------------------------------------------ */

// Hide the admin bar, as a visitor would see the site.
const TIDY = `(() => {
	const style = document.createElement('style');
	style.textContent = '#wpadminbar{display:none!important}html{margin-top:0!important}:root{--wp-admin--admin-bar--height:0px!important}';
	document.head.appendChild(style);
})()`;

// An eased scroll to a position, a selector or the end of the page.
const scrollScript = ( target, ms ) => `(async () => {
	const t = ${ JSON.stringify( target ) };
	let y = typeof t === 'number' ? t : 0;
	if (t === 'end') { y = document.documentElement.scrollHeight - innerHeight; }
	else if (typeof t === 'string') { const el = document.querySelector(t); if (el) { y = el.getBoundingClientRect().top + scrollY - 96; } }
	y = Math.max(0, Math.min(y, document.documentElement.scrollHeight - innerHeight));
	const from = scrollY, start = performance.now(), ms = ${ ms };
	const ease = (p) => p < 0.5 ? 4 * p * p * p : 1 - Math.pow(-2 * p + 2, 3) / 2;
	await new Promise((done) => {
		const step = (now) => {
			const p = Math.min(1, (now - start) / ms);
			scrollTo(0, from + (y - from) * ease(p));
			p < 1 ? requestAnimationFrame(step) : done();
		};
		requestAnimationFrame(step);
	});
})()`;

/* Recording ------------------------------------------------------------------- */

async function record( name, tour, cdp ) {
	const dir = join( work, name );
	rmSync( dir, { recursive: true, force: true } );
	mkdirSync( dir, { recursive: true } );

	await cdp.send( 'Emulation.setDeviceMetricsOverride', {
		width: tour.width,
		height: tour.height,
		deviceScaleFactor: tour.scale,
		mobile: tour.width < 768,
	} );
	await cdp.send( 'Emulation.setTouchEmulationEnabled', { enabled: tour.width < 768 } );

	const open = async ( url ) => {
		let loaded;
		const done = new Promise( ( resolve ) => ( loaded = resolve ) );
		cdp.on( 'Page.loadEventFired', loaded );
		await cdp.send( 'Page.navigate', { url } );
		await Promise.race( [ done, wait( 120000 ) ] );
		cdp.off( 'Page.loadEventFired' );
	};

	const segments = [];

	for ( const [ label, address, steps ] of tour.stops ) {
		// Load out of shot.
		if ( typeof address === 'object' ) {
			await open( base + address.from );
			const { result } = await cdp.send( 'Runtime.evaluate', { expression: address.find, returnByValue: true } );
			await open( result.value );
		} else {
			await open( base + address );
		}
		await cdp.send( 'Runtime.evaluate', { expression: TIDY } );
		await cdp.send( 'Runtime.evaluate', { expression: 'document.fonts.ready', awaitPromise: true } );
		await cdp.send( 'Runtime.evaluate', { expression: 'scrollTo(0, 0)' } );
		await wait( 600 );

		// Roll.
		const frames = [];
		const segment = join( dir, `${ String( segments.length ).padStart( 2, '0' ) }` );
		mkdirSync( segment, { recursive: true } );

		cdp.on( 'Page.screencastFrame', ( { data, metadata, sessionId } ) => {
			const file = join( segment, `${ String( frames.length ).padStart( 5, '0' ) }.jpg` );
			writeFileSync( file, Buffer.from( data, 'base64' ) );
			frames.push( { file, time: metadata.timestamp } );
			cdp.send( 'Page.screencastFrameAck', { sessionId } ).catch( () => {} );
		} );

		await cdp.send( 'Page.startScreencast', {
			format: 'jpeg',
			quality: 88,
			maxWidth: tour.width * tour.scale,
			maxHeight: tour.height * tour.scale,
			everyNthFrame: 1,
		} );

		const started = Date.now();
		for ( const step of steps ) {
			if ( 'hold' === step[ 0 ] ) {
				await wait( step[ 1 ] );
			} else if ( 'to' === step[ 0 ] ) {
				await cdp.send( 'Runtime.evaluate', { expression: scrollScript( step[ 1 ], step[ 2 ] ), awaitPromise: true } );
			} else if ( 'run' === step[ 0 ] ) {
				await cdp.send( 'Runtime.evaluate', { expression: step[ 1 ] } );
			}
		}
		const held = ( Date.now() - started ) / 1000;

		await cdp.send( 'Page.stopScreencast' );
		cdp.off( 'Page.screencastFrame' );

		if ( frames.length < 2 ) {
			console.log( `  ${ label }: no frames, skipped` );
			continue;
		}

		// Each frame lasts until the next one; the last one fills out the stop.
		const list = frames.map( ( frame, i ) => {
			const next = frames[ i + 1 ];
			const lasts = next ? next.time - frame.time : Math.max( 0.04, held - ( frame.time - frames[ 0 ].time ) );
			return `file '${ frame.file.replace( /\\/g, '/' ) }'\nduration ${ Math.max( 0.001, lasts ).toFixed( 4 ) }`;
		} );
		list.push( `file '${ frames[ frames.length - 1 ].file.replace( /\\/g, '/' ) }'` );
		writeFileSync( `${ segment }.txt`, list.join( '\n' ) );

		const clip = `${ segment }.mp4`;
		const w = tour.width * tour.scale;
		const h = tour.height * tour.scale;
		ffmpeg( [ '-f', 'concat', '-safe', '0', '-i', `${ segment }.txt`, '-vf', `scale=${ w }:${ h }:force_original_aspect_ratio=decrease,pad=${ w }:${ h }:(ow-iw)/2:(oh-ih)/2:color=0x131209,fps=30,format=yuv420p`, '-c:v', 'libx264', '-preset', 'veryfast', '-crf', '16', clip ] );

		segments.push( { clip, length: held } );
		console.log( `  ${ label }: ${ frames.length } frames, ${ held.toFixed( 1 ) }s` );
	}

	// Join the stops with short dissolves.
	const FADE = 0.4;
	const inputs = segments.flatMap( ( s ) => [ '-i', s.clip ] );
	const chain = [];
	let last = '0:v';
	let length = segments[ 0 ].length;

	for ( let i = 1; i < segments.length; i++ ) {
		chain.push( `[${ last }][${ i }:v]xfade=transition=fade:duration=${ FADE }:offset=${ ( length - FADE ).toFixed( 3 ) }[x${ i }]` );
		length += segments[ i ].length - FADE;
		last = `x${ i }`;
	}

	const file = join( out, `daguah-${ name }.mp4` );
	ffmpeg( [
		...inputs,
		...( chain.length ? [ '-filter_complex', chain.join( ';' ), '-map', `[${ last }]` ] : [] ),
		'-c:v', 'libx264',
		'-preset', 'slow',
		'-crf', '20',
		'-pix_fmt', 'yuv420p',
		'-movflags', '+faststart',
		file,
	] );

	console.log( `${ file }  ${ length.toFixed( 0 ) }s` );
}

try {
	mkdirSync( out, { recursive: true } );
	const cdp = await connect( await pageSocket() );
	await cdp.send( 'Page.enable' );

	for ( const name of tours ) {
		console.log( `Recording ${ name }` );
		await record( name, TOURS[ name ], cdp );
	}

	cdp.close();
} finally {
	browser.kill();
}
