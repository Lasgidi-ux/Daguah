/**
 * WCAG contrast check for every text and background pair the theme uses.
 *
 * Reads the colour roles from theme.json (dusk) and styles/noon.json (noon),
 * so the check follows the tokens. Exits with an error if a pair fails.
 *
 *   4.5 : 1  body text (AA)
 *   3.0 : 1  large text and interface boundaries (AA)
 */
import { readFileSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

const theme = join( dirname( fileURLToPath( import.meta.url ) ), '../wp-content/themes/daguah' );
const read = ( file ) => JSON.parse( readFileSync( join( theme, file ), 'utf8' ) ).settings.custom.color;

const modes = { dusk: read( 'theme.json' ), noon: read( 'styles/noon.json' ) };

// [ text role, background role, minimum ratio, what it is ]
const PAIRS = [
	[ 'text', 'base', 4.5, 'Body text on the page' ],
	[ 'text', 'raised', 4.5, 'Body text on a card' ],
	[ 'textMuted', 'base', 4.5, 'Muted text on the page' ],
	[ 'textMuted', 'raised', 4.5, 'Muted text on a card' ],
	[ 'actionText', 'action', 4.5, 'Primary button label' ],
	[ 'actionHoverText', 'actionHover', 4.5, 'Primary button label, hover' ],
	[ 'link', 'base', 4.5, 'Link on the page' ],
	[ 'link', 'raised', 4.5, 'Link on a card' ],
	[ 'onTag', 'tag', 4.5, 'Occasion tag label' ],
	[ 'onSuccess', 'success', 4.5, 'Verified tag label' ],
	[ 'onSecondary', 'secondary', 4.5, 'Text on an indigo surface' ],
	[ 'focus', 'base', 3, 'Focus ring on the page' ],
	[ 'action', 'base', 3, 'Primary button against the page' ],
];

const channel = ( value ) => {
	const c = value / 255;
	return c <= 0.03928 ? c / 12.92 : ( ( c + 0.055 ) / 1.055 ) ** 2.4;
};

const luminance = ( hex ) => {
	const n = parseInt( hex.slice( 1 ), 16 );
	return 0.2126 * channel( ( n >> 16 ) & 255 ) + 0.7152 * channel( ( n >> 8 ) & 255 ) + 0.0722 * channel( n & 255 );
};

const ratio = ( a, b ) => {
	const [ hi, lo ] = [ luminance( a ), luminance( b ) ].sort( ( x, y ) => y - x );
	return ( hi + 0.05 ) / ( lo + 0.05 );
};

let failures = 0;

for ( const [ mode, tokens ] of Object.entries( modes ) ) {
	console.log( `\nMarket at ${ mode }` );
	for ( const [ fg, bg, min, label ] of PAIRS ) {
		const value = ratio( tokens[ fg ], tokens[ bg ] );
		const pass = value >= min;
		if ( ! pass ) {
			failures++;
		}
		console.log(
			`  ${ pass ? 'pass' : 'FAIL' }  ${ value.toFixed( 2 ).padStart( 5 ) } : 1  (needs ${ min })  ${ label }  ${ tokens[ fg ] } on ${ tokens[ bg ] }`
		);
	}
}

if ( failures ) {
	console.error( `\n${ failures } pair(s) fall below WCAG AA.` );
	process.exit( 1 );
}

console.log( '\nEvery pair meets WCAG AA.' );
