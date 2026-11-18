/**
 * Cloth parity test: cloth.js and class-cloth.php must return the same SVG,
 * byte for byte, for every seed and family.
 *
 * PHP runs in WebAssembly through @php-wasm/node (installed with the
 * Playground CLI), so no local PHP install is needed.
 */
import { readFileSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

import { loadNodeRuntime } from '@php-wasm/node';
import { PHP } from '@php-wasm/universal';

import { svg, FAMILIES } from '../wp-content/plugins/daguah-core/src/cloth.js';

const root = join( dirname( fileURLToPath( import.meta.url ) ), '..' );

const SEEDS = [
	'DAGUAH',
	'Ìyá Àdìrẹ Studio|yoruba',
	'Bonwire Loom House|ashanti',
	'Makola Lace Merchants|accra',
	'Gikomba Thread Co.|nairobi',
	'Peckham Preloved by Funmi|london',
	'Maison Kinshasa Wax|congo',
	'Habesha Edit|washington dc',
	'Zaria Leather Works|kaduna',
	'',
	'ọjà',
	'a',
];

const cases = [];
for ( const seed of SEEDS ) {
	for ( const family of [ ...FAMILIES, 'unknown' ] ) {
		cases.push( [ seed, family ] );
	}
}

const expected = cases.map( ( [ seed, family ] ) => svg( seed, family ) );

const php = new PHP( await loadNodeRuntime( '8.3', { emscriptenOptions: { processId: 1 } } ) );

php.writeFile(
	'/class-cloth.php',
	readFileSync( join( root, 'wp-content/plugins/daguah-core/includes/class-cloth.php' ) )
);
php.writeFile( '/cases.json', JSON.stringify( cases ) );
php.writeFile(
	'/run.php',
	`<?php
define( 'ABSPATH', '/' );
require '/class-cloth.php';
$out = array();
foreach ( json_decode( file_get_contents( '/cases.json' ), true ) as $case ) {
	$out[] = \\Daguah\\Core\\Cloth::svg( $case[0], $case[1] );
}
echo json_encode( $out, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
`
);

const response = await php.run( { scriptPath: '/run.php' } );

if ( response.errors ) {
	console.error( response.errors );
	process.exit( 1 );
}

const actual = JSON.parse( response.text );

let failures = 0;
cases.forEach( ( [ seed, family ], index ) => {
	if ( expected[ index ] !== actual[ index ] ) {
		failures++;
		console.error( `MISMATCH seed="${ seed }" family="${ family }"` );
	}
} );

if ( failures ) {
	console.error( `${ failures } of ${ cases.length } cloths differ between JS and PHP.` );
	process.exit( 1 );
}

console.log( `Cloth parity: ${ cases.length } of ${ cases.length } cloths match between JS and PHP.` );
process.exit( 0 );
