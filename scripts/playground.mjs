/**
 * Run the site locally without Docker, using WordPress Playground.
 *
 * WordPress and PHP run in WebAssembly inside Node. The theme and plugin are
 * mounted from this repo, so edits show on refresh.
 *
 *   npm run playground                 WooCommerce, Dokan Lite, theme, plugin,
 *                                      then seeds the demo content
 *   npm run playground -- --core-only  Theme and plugin only, no seeding
 *
 * WooCommerce and Dokan Lite are read from .cache/plugins (run
 * `npm run fetch-plugins` once). The database lives in memory: stopping the
 * server resets the site. For a persistent site with MySQL, use wp-env
 * (needs Docker): npm run env
 */
import { spawn } from 'node:child_process';
import { existsSync, mkdirSync, readFileSync, writeFileSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = join( dirname( fileURLToPath( import.meta.url ) ), '..' );
const coreOnly = process.argv.includes( '--core-only' );
const port = process.env.PORT || '9400';

const mount = ( from, to ) => [ '--mount-dir', join( root, from ), to ];

const args = [
	join( root, 'node_modules/@wp-playground/cli/wp-playground.js' ),
	'server',
	'--port', port,
	'--php', '8.3',
	'--wp', 'latest',
	'--login',
	...mount( 'wp-content/themes/daguah', '/wordpress/wp-content/themes/daguah' ),
	...mount( 'wp-content/plugins/daguah-core', '/wordpress/wp-content/plugins/daguah-core' ),
];

if ( ! coreOnly ) {
	for ( const plugin of [ 'woocommerce', 'dokan-lite' ] ) {
		if ( ! existsSync( join( root, '.cache/plugins', plugin ) ) ) {
			console.error( `Missing .cache/plugins/${ plugin }. Run: npm run fetch-plugins` );
			process.exit( 1 );
		}
		args.push( ...mount( `.cache/plugins/${ plugin }`, `/wordpress/wp-content/plugins/${ plugin }` ) );
	}
}

// --verify runs every stage's checks after seeding; --verify=4 or --verify=3,4
// runs just those. Results go to .cache/results/stageN.json.
const verifyArg = process.argv.find( ( arg ) => arg === '--verify' || arg.startsWith( '--verify=' ) );
const ALL_STAGES = [ 2, 3, 4, 5 ];
let blueprint = join( root, 'playground', coreOnly ? 'blueprint-core-only.json' : 'blueprint.json' );

if ( verifyArg && ! coreOnly ) {
	const stages = verifyArg.includes( '=' )
		? verifyArg.split( '=' )[ 1 ].split( ',' ).map( Number ).filter( ( n ) => ALL_STAGES.includes( n ) )
		: ALL_STAGES;

	mkdirSync( join( root, '.cache/results' ), { recursive: true } );
	args.push( ...mount( 'playground', '/wordpress/wp-content/daguah-dev' ) );
	args.push( ...mount( '.cache/results', '/wordpress/wp-content/daguah-results' ) );

	// Each stage runs in its own step; a fatal error is written beside the results.
	const step = ( n ) => ( {
		step: 'runPHP',
		code: `<?php require '/wordpress/wp-load.php'; $out = WP_CONTENT_DIR . '/daguah-results'; register_shutdown_function( static function () use ( $out ) { $e = error_get_last(); if ( $e && in_array( $e['type'], array( E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR ), true ) ) { file_put_contents( $out . '/stage${ n }-error.txt', print_r( $e, true ) ); } } ); try { require '/wordpress/wp-content/daguah-dev/verify-stage${ n }.php'; } catch ( Throwable $t ) { file_put_contents( $out . '/stage${ n }-error.txt', (string) $t ); }`,
	} );

	const base = JSON.parse( readFileSync( join( root, 'playground', 'blueprint.json' ), 'utf8' ) );
	base.steps = [
		...base.steps.filter( ( s ) => s.step !== 'runPHP' ),
		{ step: 'runPHP', code: "<?php require '/wordpress/wp-load.php'; \\Daguah\\Core\\Seeder::run();" },
		...stages.map( step ),
	];

	blueprint = join( root, '.cache', 'blueprint-verify.json' );
	writeFileSync( blueprint, JSON.stringify( base, null, '\t' ) );
	console.log( `Verifying stages ${ stages.join( ', ' ) }` );
}

args.push( '--blueprint', blueprint );

const child = spawn( process.execPath, args, { cwd: root, stdio: 'inherit' } );

child.on( 'exit', ( code ) => process.exit( code ?? 0 ) );
process.on( 'SIGINT', () => child.kill( 'SIGINT' ) );
process.on( 'SIGTERM', () => child.kill( 'SIGTERM' ) );
