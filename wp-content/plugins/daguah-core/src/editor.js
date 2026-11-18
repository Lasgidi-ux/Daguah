/**
 * Editor registration for DAGUAH blocks.
 *
 * Every block is rendered by PHP, so the editor shows the real front end
 * markup through ServerSideRender and the preview always matches the site.
 * Settings live in the block sidebar.
 */
import { registerBlockType } from '@wordpress/blocks';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import {
	FormTokenField,
	PanelBody,
	RangeControl,
	SelectControl,
	TextareaControl,
	TextControl,
	ToggleControl,
} from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';
import { __ } from '@wordpress/i18n';

import clothMeta from '../blocks/cloth/block.json';
import menuToggleMeta from '../blocks/menu-toggle/block.json';
import themeToggleMeta from '../blocks/theme-toggle/block.json';
import iconMeta from '../blocks/icon/block.json';
import marketWordMeta from '../blocks/market-word/block.json';
import lanternRowMeta from '../blocks/lantern-row/block.json';
import calendarMeta from '../blocks/calendar-ribbon/block.json';
import occasionMeta from '../blocks/occasion-grid/block.json';
import stallsMeta from '../blocks/featured-stalls/block.json';
import liveMeta from '../blocks/live-strip/block.json';
import lanternWordsMeta from '../blocks/lantern-words/block.json';
import currencyMeta from '../blocks/currency-switcher/block.json';
import fitMirrorMeta from '../blocks/fit-mirror/block.json';
import fitChipMeta from '../blocks/fit-chip/block.json';
import holdMeta from '../blocks/hold-badge/block.json';
import landedMeta from '../blocks/landed-price/block.json';
import threadMeta from '../blocks/thread-map/block.json';
import passportMeta from '../blocks/heritage-passport/block.json';
import measurementsMeta from '../blocks/measurements/block.json';
import mediaMeta from '../blocks/product-media/block.json';
import factsMeta from '../blocks/product-facts/block.json';
import productStallMeta from '../blocks/product-stall/block.json';
import lookMeta from '../blocks/complete-the-look/block.json';
import filtersMeta from '../blocks/shop-filters/block.json';
import resultsMeta from '../blocks/product-results/block.json';
import stallHeaderMeta from '../blocks/stall-header/block.json';
import stallPiecesMeta from '../blocks/stall-pieces/block.json';
import stallDirectoryMeta from '../blocks/stall-directory/block.json';
import occasionIntroMeta from '../blocks/occasion-intro/block.json';
import circleBoardMeta from '../blocks/circle-board/block.json';
import circleStartMeta from '../blocks/circle-start/block.json';
import liveScheduleMeta from '../blocks/live-schedule/block.json';
import liveStageMeta from '../blocks/live-stage/block.json';
import liveTrayMeta from '../blocks/live-tray/block.json';
import sendHomeMeta from '../blocks/send-home/block.json';
import giftNoteMeta from '../blocks/gift-note/block.json';

const choices = window.daguahChoices || {};

const FAMILY_OPTIONS = [
	{ value: 'adire', label: __( 'Adire (Yoruba lands)', 'daguah' ) },
	{ value: 'kente', label: __( 'Kente (Ashanti)', 'daguah' ) },
	{ value: 'bogolan', label: __( 'Bògòlan (Mali)', 'daguah' ) },
	{ value: 'kuba', label: __( 'Kuba (Congo)', 'daguah' ) },
	{ value: 'mix', label: __( 'Seeded mix', 'daguah' ) },
];

const HEIGHT_OPTIONS = [ 48, 96, 160, 240, 320 ].map( ( value ) => ( {
	value: String( value ),
	label: `${ value }px`,
} ) );

/** Pick several items by name; stores their IDs in order. */
function Picker( { label, help, list = [], value = [], onChange } ) {
	const byId = new Map( list.map( ( item ) => [ item.value, item.label ] ) );
	const byLabel = new Map( list.map( ( item ) => [ item.label, item.value ] ) );

	return (
		<FormTokenField
			__nextHasNoMarginBottom
			__next40pxDefaultSize
			label={ label }
			__experimentalShowHowTo={ false }
			__experimentalExpandOnFocus
			value={ value.map( ( id ) => byId.get( id ) ).filter( Boolean ) }
			suggestions={ list.map( ( item ) => item.label ) }
			onChange={ ( labels ) =>
				onChange( labels.map( ( l ) => byLabel.get( l ) ).filter( Boolean ) )
			}
			help={ help }
		/>
	);
}

/** Register a server rendered block with optional sidebar controls. */
function register( meta, Controls = null ) {
	registerBlockType( meta.name, {
		edit( props ) {
			return (
				<div { ...useBlockProps() }>
					{ Controls && (
						<InspectorControls>
							<PanelBody title={ meta.title }>
								<Controls { ...props } />
							</PanelBody>
						</InspectorControls>
					) }
					<ServerSideRender block={ meta.name } attributes={ props.attributes } />
				</div>
			);
		},
		save: () => null,
	} );
}

register( clothMeta, ( { attributes, setAttributes } ) => (
	<>
		<TextControl
			__nextHasNoMarginBottom
			label={ __( 'Seed', 'daguah' ) }
			help={ __( 'Usually the seller name and region. The same seed always gives the same cloth.', 'daguah' ) }
			value={ attributes.seed }
			onChange={ ( seed ) => setAttributes( { seed } ) }
		/>
		<SelectControl
			__nextHasNoMarginBottom
			label={ __( 'Family', 'daguah' ) }
			value={ attributes.family }
			options={ FAMILY_OPTIONS }
			onChange={ ( family ) => setAttributes( { family } ) }
		/>
		<SelectControl
			__nextHasNoMarginBottom
			label={ __( 'Height', 'daguah' ) }
			value={ String( attributes.height ) }
			options={ HEIGHT_OPTIONS }
			onChange={ ( height ) => setAttributes( { height: Number( height ) } ) }
		/>
		<ToggleControl
			__nextHasNoMarginBottom
			label={ __( 'Weave in on scroll', 'daguah' ) }
			checked={ attributes.animate }
			onChange={ ( animate ) => setAttributes( { animate } ) }
		/>
	</>
) );

register( menuToggleMeta );
register( themeToggleMeta );
register( liveMeta );

// Product and shop blocks: they show the current product, or the newest one
// while editing a template, and take their content from the product itself.
[
	currencyMeta,
	fitChipMeta,
	holdMeta,
	landedMeta,
	threadMeta,
	passportMeta,
	measurementsMeta,
	mediaMeta,
	factsMeta,
	productStallMeta,
	filtersMeta,
	resultsMeta,
].forEach( ( meta ) => register( meta ) );

// Stall, occasion, Circle and Market Live blocks: they read the stall, the
// occasion, the invite link or the session from the page they are on, and
// preview the busiest one while a template is being edited.
[
	stallHeaderMeta,
	stallPiecesMeta,
	stallDirectoryMeta,
	occasionIntroMeta,
	circleBoardMeta,
	circleStartMeta,
	liveScheduleMeta,
	liveStageMeta,
	liveTrayMeta,
	giftNoteMeta,
].forEach( ( meta ) => register( meta ) );

register( sendHomeMeta, ( { attributes, setAttributes } ) => (
	<RangeControl
		__nextHasNoMarginBottom
		__next40pxDefaultSize
		label={ __( 'Pieces to show', 'daguah' ) }
		min={ 4 }
		max={ 24 }
		value={ attributes.limit }
		onChange={ ( limit ) => setAttributes( { limit } ) }
	/>
) );

register( fitMirrorMeta, ( { attributes, setAttributes } ) => (
	<ToggleControl
		__nextHasNoMarginBottom
		label={ __( 'Show the words "Fit Mirror"', 'daguah' ) }
		help={ __( 'Off shows the ruler icon only, for the header.', 'daguah' ) }
		checked={ !! attributes.showLabel }
		onChange={ ( showLabel ) => setAttributes( { showLabel } ) }
	/>
) );

register( lookMeta, ( { attributes, setAttributes } ) => (
	<RangeControl
		__nextHasNoMarginBottom
		__next40pxDefaultSize
		label={ __( 'Pieces to show', 'daguah' ) }
		help={ __( 'Cross sells set on the product come first.', 'daguah' ) }
		min={ 2 }
		max={ 8 }
		value={ attributes.limit }
		onChange={ ( limit ) => setAttributes( { limit } ) }
	/>
) );

register( iconMeta, ( { attributes, setAttributes } ) => (
	<SelectControl
		__nextHasNoMarginBottom
		label={ __( 'Icon', 'daguah' ) }
		value={ attributes.name }
		options={ ( choices.icons || [] ).map( ( name ) => ( { value: name, label: name.replace( /-/g, ' ' ) } ) ) }
		onChange={ ( name ) => setAttributes( { name } ) }
	/>
) );

register( marketWordMeta, ( { attributes, setAttributes } ) => (
	<TextareaControl
		__nextHasNoMarginBottom
		label={ __( 'Words', 'daguah' ) }
		help={ __( 'One per line: word | language | language code. Ask a native speaker to check each spelling.', 'daguah' ) }
		rows={ 9 }
		value={ ( attributes.words || [] )
			.map( ( w ) => [ w.word, w.language, w.lang ].join( ' | ' ) )
			.join( '\n' ) }
		onChange={ ( text ) =>
			setAttributes( {
				words: text
					.split( '\n' )
					.map( ( line ) => line.split( '|' ).map( ( part ) => part.trim() ) )
					.filter( ( [ word ] ) => word )
					.map( ( [ word, language = '', lang = '' ] ) => ( { word, language, lang } ) ),
			} )
		}
	/>
) );

register( lanternRowMeta, ( { attributes, setAttributes } ) => (
	<>
		<RangeControl
			__nextHasNoMarginBottom
			__next40pxDefaultSize
			label={ __( 'Lanterns', 'daguah' ) }
			min={ 3 }
			max={ 15 }
			value={ attributes.count }
			onChange={ ( count ) => setAttributes( { count } ) }
		/>
		<TextControl
			__nextHasNoMarginBottom
			label={ __( 'Cloth seed', 'daguah' ) }
			value={ attributes.seed }
			onChange={ ( seed ) => setAttributes( { seed } ) }
		/>
		<SelectControl
			__nextHasNoMarginBottom
			label={ __( 'Cloth family', 'daguah' ) }
			value={ attributes.family }
			options={ FAMILY_OPTIONS }
			onChange={ ( family ) => setAttributes( { family } ) }
		/>
	</>
) );

register( calendarMeta, ( { attributes, setAttributes } ) => (
	<RangeControl
		__nextHasNoMarginBottom
		__next40pxDefaultSize
		label={ __( 'Moments on the ribbon', 'daguah' ) }
		help={ __( 'Add and date moments under DAGUAH, Calendar moments.', 'daguah' ) }
		min={ 3 }
		max={ 10 }
		value={ attributes.limit }
		onChange={ ( limit ) => setAttributes( { limit } ) }
	/>
) );

register( occasionMeta, ( { attributes, setAttributes } ) => (
	<>
		<Picker
			label={ __( 'Occasions', 'daguah' ) }
			help={ __( 'Leave empty to show the occasions with the most pieces.', 'daguah' ) }
			list={ choices.occasions }
			value={ attributes.ids }
			onChange={ ( ids ) => setAttributes( { ids } ) }
		/>
		<RangeControl
			__nextHasNoMarginBottom
			__next40pxDefaultSize
			label={ __( 'How many when automatic', 'daguah' ) }
			min={ 2 }
			max={ 12 }
			value={ attributes.limit }
			disabled={ attributes.all }
			onChange={ ( limit ) => setAttributes( { limit } ) }
		/>
		<ToggleControl
			__nextHasNoMarginBottom
			label={ __( 'Every occasion with pieces', 'daguah' ) }
			help={ __( 'For the Occasions page. Ignores the number above.', 'daguah' ) }
			checked={ !! attributes.all }
			onChange={ ( all ) => setAttributes( { all } ) }
		/>
		<ToggleControl
			__nextHasNoMarginBottom
			label={ __( 'Show when each comes round next', 'daguah' ) }
			help={ __( 'Taken from the cultural calendar.', 'daguah' ) }
			checked={ !! attributes.dates }
			onChange={ ( dates ) => setAttributes( { dates } ) }
		/>
	</>
) );

register( stallsMeta, ( { attributes, setAttributes } ) => (
	<>
		<Picker
			label={ __( 'Stalls', 'daguah' ) }
			help={ __( 'Leave empty to show the most verified stalls.', 'daguah' ) }
			list={ choices.vendors }
			value={ attributes.ids }
			onChange={ ( ids ) => setAttributes( { ids } ) }
		/>
		<RangeControl
			__nextHasNoMarginBottom
			__next40pxDefaultSize
			label={ __( 'How many when automatic', 'daguah' ) }
			min={ 1 }
			max={ 12 }
			value={ attributes.limit }
			onChange={ ( limit ) => setAttributes( { limit } ) }
		/>
	</>
) );

register( lanternWordsMeta, ( { attributes, setAttributes } ) => (
	<TextareaControl
		__nextHasNoMarginBottom
		label={ __( 'Lines', 'daguah' ) }
		help={ __( 'One line per row. Lines break exactly where you break them.', 'daguah' ) }
		rows={ 4 }
		value={ ( attributes.lines || [] ).join( '\n' ) }
		onChange={ ( text ) => setAttributes( { lines: text.split( '\n' ) } ) }
	/>
) );
