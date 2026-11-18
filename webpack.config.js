/**
 * One build for both packages.
 *
 * Entry names are output paths, so each bundle lands in its own package:
 * the theme's presentation scripts in wp-content/themes/daguah/build and the
 * plugin's block scripts in wp-content/plugins/daguah-core/build.
 */
const path = require( 'path' );
const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );

const theme = 'wp-content/themes/daguah';
const core = 'wp-content/plugins/daguah-core';
const src = ( file ) => path.resolve( __dirname, file );

module.exports = {
	...defaultConfig,
	entry: {
		[ `${ theme }/build/theme` ]: src( `${ theme }/src/theme.js` ),
		[ `${ theme }/build/smooth` ]: src( `${ theme }/src/smooth.js` ),
		[ `${ theme }/build/editor` ]: src( `${ theme }/src/editor.js` ),
		[ `${ core }/build/editor` ]: src( `${ core }/src/editor.js` ),
		[ `${ core }/build/editor-panels` ]: src( `${ core }/src/editor-panels.js` ),
		[ `${ core }/build/cloth-view` ]: src( `${ core }/src/cloth-view.js` ),
		[ `${ core }/build/market-word` ]: src( `${ core }/src/market-word.js` ),
		[ `${ core }/build/calendar-ribbon` ]: src( `${ core }/src/calendar-ribbon.js` ),
		[ `${ core }/build/live` ]: src( `${ core }/src/live.js` ),
		[ `${ core }/build/circle` ]: src( `${ core }/src/circle.js` ),
		[ `${ core }/build/gift-note` ]: src( `${ core }/src/gift-note.js` ),
		[ `${ core }/build/lantern-words` ]: src( `${ core }/src/lantern-words.js` ),
		[ `${ core }/build/shop` ]: src( `${ core }/src/shop.js` ),
		[ `${ core }/build/menu-toggle` ]: src( `${ core }/src/menu-toggle.js` ),
		[ `${ core }/build/theme-toggle` ]: src( `${ core }/src/theme-toggle.js` ),
	},
	output: {
		...defaultConfig.output,
		path: __dirname,
		filename: '[name].js',
		clean: false,
	},
	// The default config cleans its output folder, which here is the repo root.
	plugins: defaultConfig.plugins.filter(
		( plugin ) =>
			plugin.constructor.name !== 'CleanWebpackPlugin' &&
			plugin.constructor.name !== 'CopyPlugin'
	),
};
