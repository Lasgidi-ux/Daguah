/**
 * Fit parity test: src/fit.js and includes/class-fit.php must give the same
 * verdict and the same differences for every case. PHP runs in WebAssembly.
 */
import { readFileSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

import { loadNodeRuntime } from '@php-wasm/node';
import { PHP } from '@php-wasm/universal';

import { compare } from '../wp-content/plugins/daguah-core/src/fit.js';

const root = join( dirname( fileURLToPath( import.meta.url ) ), '..' );
const core = join( root, 'wp-content/plugins/daguah-core/includes' );

const dress = { bust: 92, waist: 76, hip: 104, length: 118 };
const cases = [
	// [ piece, body, expected status ]
	[ dress, { bust: 92, waist: 76, hip: 104, length: 118 }, 'fits' ],
	[ dress, { bust: 94, waist: 74 }, 'fits' ],
	[ dress, { bust: 94.5, waist: 76 }, 'alter' ],
	[ dress, { bust: 97, waist: 76 }, 'alter' ],
	[ dress, { bust: 97.5, waist: 76 }, 'no' ],
	[ dress, { hip: 99 }, 'alter' ],
	[ dress, { shoulder: 40 }, null ],
	[ {}, { bust: 92 }, null ],
	[ { length: 96 }, { length: 118 }, 'no' ],
	[ { bust: 116, length: 112, shoulder: 50 }, { bust: 114, shoulder: 51.5 }, 'fits' ],
	[ { waist: 74, hip: 102, length: 96 }, { waist: 79, hip: 102, length: 96 }, 'alter' ],
	[ { waist: 74.2 }, { waist: 76.2 }, 'fits' ],
];

const php = new PHP( await loadNodeRuntime( '8.2', { emscriptenOptions: { processId: 1 } } ) );
php.writeFile( '/product-data.php', readFileSync( join( core, 'data/class-product-data.php' ) ) );
php.writeFile( '/fit.php', readFileSync( join( core, 'class-fit.php' ) ) );
php.writeFile( '/cases.json', JSON.stringify( cases ) );
php.writeFile(
	'/run.php',
	`<?php
define( 'ABSPATH', '/' );
function __( $s ) { return $s; }
require '/product-data.php';
require '/fit.php';
$out = array();
foreach ( json_decode( file_get_contents( '/cases.json' ), true ) as $case ) {
	$out[] = \\Daguah\\Core\\Fit::compare( (array) $case[0], (array) $case[1] );
}
echo json_encode( $out );
`
);

const response = await php.run( { scriptPath: '/run.php' } );
if ( response.errors ) {
	console.error( response.errors );
	process.exit( 1 );
}
const actual = JSON.parse( response.text );

let failures = 0;
cases.forEach( ( [ piece, body, expected ], i ) => {
	const js = compare( piece, body );
	const fromPhp = actual[ i ];
	const jsStatus = js ? js.status : null;
	const phpStatus = fromPhp ? fromPhp.status : null;
	const sameDiffs = JSON.stringify( js?.diffs ?? null ) === JSON.stringify( fromPhp?.diffs ?? null );

	if ( jsStatus !== expected || phpStatus !== expected || ! sameDiffs ) {
		failures++;
		console.error( `Case ${ i + 1 }: expected ${ expected }, JS ${ jsStatus }, PHP ${ phpStatus }, diffs ${ JSON.stringify( js?.diffs ) } vs ${ JSON.stringify( fromPhp?.diffs ) }` );
	}
} );

if ( failures ) {
	console.error( `${ failures } of ${ cases.length } fit cases disagree.` );
	process.exit( 1 );
}

console.log( `Fit parity: ${ cases.length } of ${ cases.length } cases give the same verdict and differences in JS and PHP.` );
process.exit( 0 );
