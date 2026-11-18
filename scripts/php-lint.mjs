/**
 * PHP syntax check for the theme and plugin, without a local PHP install.
 *
 * Runs PHP 8.2 in WebAssembly and asks it to compile each file with
 * `php -l`. Exits with an error if any file fails.
 */
import { readdirSync, readFileSync, statSync } from 'node:fs';
import { dirname, join, relative } from 'node:path';
import { fileURLToPath } from 'node:url';

import { loadNodeRuntime } from '@php-wasm/node';
import { PHP } from '@php-wasm/universal';

const root = join( dirname( fileURLToPath( import.meta.url ) ), '..' );
const dirs = [ 'wp-content/themes/daguah', 'wp-content/plugins/daguah-core' ];

function walk( dir ) {
	return readdirSync( dir ).flatMap( ( name ) => {
		const path = join( dir, name );
		if ( [ 'node_modules', 'build', 'assets' ].includes( name ) ) {
			return [];
		}
		return statSync( path ).isDirectory() ? walk( path ) : path.endsWith( '.php' ) ? [ path ] : [];
	} );
}

const files = dirs.flatMap( ( dir ) => walk( join( root, dir ) ) );
const php = new PHP( await loadNodeRuntime( '8.2', { emscriptenOptions: { processId: 1 } } ) );

php.mkdir( '/lint' );

let failures = 0;

for ( const file of files ) {
	const name = relative( root, file ).replace( /\\/g, '/' );
	php.writeFile( '/lint/file.php', readFileSync( file ) );

	const result = await php.run( {
		code: `<?php
			$out = array();
			$tokens = @token_get_all( file_get_contents( '/lint/file.php' ), TOKEN_PARSE );
			echo 'ok';`,
	} ).catch( ( error ) => ( { text: '', errors: String( error ) } ) );

	if ( result.text.trim() !== 'ok' ) {
		failures++;
		const message = ( result.errors || result.text ).split( '\n' ).find( ( line ) => /error/i.test( line ) ) || result.errors || result.text;
		console.error( `FAIL ${ name }\n     ${ message.trim() }` );
	}
}

console.log( `${ files.length - failures } of ${ files.length } PHP files parse.` );
process.exit( failures ? 1 : 0 );
