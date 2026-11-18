/**
 * Copy self hosted fonts and Phosphor icons out of node_modules.
 *
 * Fonts go to the theme (presentation). Icons go to daguah-core, where the
 * daguah_icon() helper reads them. Add an icon name to ICONS to ship it.
 */
import { copyFileSync, mkdirSync, existsSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = join( dirname( fileURLToPath( import.meta.url ) ), '..' );
const modules = join( root, 'node_modules' );

const FONTS = [
	[ '@fontsource-variable/manrope/files', 'manrope-latin-wght-normal.woff2' ],
	[ '@fontsource-variable/manrope/files', 'manrope-latin-ext-wght-normal.woff2' ],
	// Vietnamese range carries the dot below letters Yoruba needs (ẹ, ọ).
	[ '@fontsource-variable/manrope/files', 'manrope-vietnamese-wght-normal.woff2' ],
	[ '@fontsource-variable/geist-mono/files', 'geist-mono-latin-wght-normal.woff2' ],
	[ '@fontsource-variable/geist-mono/files', 'geist-mono-latin-ext-wght-normal.woff2' ],
];

const ICONS = [
	'arrow-right',
	'arrow-up-right',
	'calendar-plus',
	'caret-down',
	'check',
	'clock',
	'copy',
	'gift',
	'globe-hemisphere-west',
	'handbag',
	'heart',
	'lock-key',
	'magnifying-glass',
	'map-pin',
	'microphone',
	'moon',
	'package',
	'pause',
	'play',
	'receipt',
	'ruler',
	'seal-check',
	'shield-check',
	'speaker-high',
	'speaker-slash',
	'stop',
	'storefront',
	'sun',
	'trash',
	'truck',
	'user',
	'users-three',
	'video-camera',
	'warning-circle',
	'x',
];

function copy( from, to ) {
	if ( ! existsSync( from ) ) {
		throw new Error( `Missing ${ from }. Run npm install first.` );
	}
	mkdirSync( dirname( to ), { recursive: true } );
	copyFileSync( from, to );
}

for ( const [ dir, file ] of FONTS ) {
	copy( join( modules, dir, file ), join( root, 'wp-content/themes/daguah/assets/fonts', file ) );
}

for ( const name of ICONS ) {
	copy(
		join( modules, '@phosphor-icons/core/assets/regular', `${ name }.svg` ),
		join( root, 'wp-content/plugins/daguah-core/assets/icons', `${ name }.svg` )
	);
}

console.log( `Copied ${ FONTS.length } font files and ${ ICONS.length } icons.` );
