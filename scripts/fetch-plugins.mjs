/**
 * Download WooCommerce and Dokan Lite into .cache/plugins for Playground.
 *
 * Versions are pinned to match .wp-env.json. Archives are extracted with
 * long path support on Windows, where WooCommerce's deepest files pass the
 * 260 character limit.
 */
import { execFileSync } from 'node:child_process';
import { existsSync, mkdirSync, writeFileSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = join( dirname( fileURLToPath( import.meta.url ) ), '..' );
const cache = join( root, '.cache/plugins' );

const PLUGINS = {
	woocommerce: '11.3.0',
	'dokan-lite': '5.3.4',
};

mkdirSync( cache, { recursive: true } );

for ( const [ slug, version ] of Object.entries( PLUGINS ) ) {
	if ( existsSync( join( cache, slug ) ) ) {
		console.log( `${ slug } already in .cache/plugins` );
		continue;
	}

	const url = `https://downloads.wordpress.org/plugin/${ slug }.${ version }.zip`;
	console.log( `Downloading ${ url }` );

	const response = await fetch( url );
	if ( ! response.ok ) {
		throw new Error( `Download failed: ${ response.status } ${ url }` );
	}

	const zip = join( cache, `${ slug }.${ version }.zip` );
	writeFileSync( zip, Buffer.from( await response.arrayBuffer() ) );

	if ( process.platform === 'win32' ) {
		execFileSync(
			'powershell.exe',
			[
				'-NoProfile',
				'-Command',
				`Add-Type -AssemblyName System.IO.Compression.FileSystem; [System.IO.Compression.ZipFile]::ExtractToDirectory('\\\\?\\${ zip }', '\\\\?\\${ cache }')`,
			],
			{ stdio: 'inherit' }
		);
	} else {
		execFileSync( 'unzip', [ '-q', zip, '-d', cache ], { stdio: 'inherit' } );
	}

	console.log( `${ slug } ${ version } ready` );
}
