/**
 * "Details" panel for calendar moments, circles and live sessions.
 *
 * The field list comes from PHP (Post_Types::fields), so adding a field there
 * adds it here. Values save as post meta with the rest of the post.
 */
import { registerPlugin } from '@wordpress/plugins';
import { PluginDocumentSettingPanel } from '@wordpress/editor';
import { useSelect } from '@wordpress/data';
import { useEntityProp } from '@wordpress/core-data';
import {
	ComboboxControl,
	FormTokenField,
	SelectControl,
	TextControl,
	ToggleControl,
	__experimentalVStack as VStack,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';

const config = window.daguahPanels || { fields: {}, choices: {} };

const CHOICES = {
	vendor: 'vendors',
	product: 'products',
	products: 'products',
	occasion: 'occasions',
	category: 'categories',
};

function Field( { name, field, value, onChange } ) {
	const common = {
		__nextHasNoMarginBottom: true,
		label: field.label,
		help: field.help,
	};

	switch ( field.type ) {
		case 'toggle':
			return (
				<ToggleControl
					{ ...common }
					checked={ !! value }
					onChange={ ( next ) => onChange( next ) }
				/>
			);

		case 'select':
			return (
				<SelectControl
					{ ...common }
					__next40pxDefaultSize
					value={ value }
					options={ Object.entries( field.options ).map(
						( [ optionValue, label ] ) => ( { value: optionValue, label } )
					) }
					onChange={ onChange }
				/>
			);

		case 'vendor':
		case 'product':
		case 'occasion':
		case 'category': {
			const options = ( config.choices[ CHOICES[ field.type ] ] || [] ).map(
				( choice ) => ( { value: String( choice.value ), label: choice.label } )
			);
			return (
				<ComboboxControl
					{ ...common }
					__next40pxDefaultSize
					value={ value ? String( value ) : null }
					options={ options }
					onChange={ ( next ) => onChange( next ? Number( next ) : 0 ) }
				/>
			);
		}

		case 'products': {
			const choices = config.choices.products || [];
			const byId = new Map( choices.map( ( c ) => [ c.value, c.label ] ) );
			const byLabel = new Map( choices.map( ( c ) => [ c.label, c.value ] ) );
			return (
				<FormTokenField
					__nextHasNoMarginBottom
					__next40pxDefaultSize
					label={ field.label }
					value={ ( value || [] ).map( ( id ) => byId.get( id ) ).filter( Boolean ) }
					suggestions={ choices.map( ( c ) => c.label ) }
					__experimentalExpandOnFocus
					onChange={ ( labels ) =>
						onChange(
							labels.map( ( label ) => byLabel.get( label ) ).filter( Boolean )
						)
					}
				/>
			);
		}

		case 'number':
			return (
				<TextControl
					{ ...common }
					__next40pxDefaultSize
					type="number"
					min={ field.min }
					max={ field.max }
					value={ value ?? '' }
					onChange={ ( next ) => onChange( next === '' ? 0 : Number( next ) ) }
				/>
			);

		default:
			return (
				<TextControl
					{ ...common }
					__next40pxDefaultSize
					id={ `daguah-field-${ name }` }
					type={
						{ date: 'date', datetime: 'datetime-local', url: 'url' }[
							field.type
						] || 'text'
					}
					value={ value ?? '' }
					onChange={ onChange }
				/>
			);
	}
}

function DetailsPanel() {
	const postType = useSelect(
		( select ) => select( 'core/editor' ).getCurrentPostType(),
		[]
	);
	const [ meta, setMeta ] = useEntityProp( 'postType', postType, 'meta' );
	const fields = config.fields[ postType ];

	if ( ! fields || ! meta ) {
		return null;
	}

	return (
		<PluginDocumentSettingPanel
			name="daguah-details"
			title={ __( 'Details', 'daguah' ) }
		>
			<VStack spacing={ 4 }>
				{ Object.entries( fields ).map( ( [ name, field ] ) => (
					<Field
						key={ name }
						name={ name }
						field={ field }
						value={ meta[ name ] }
						onChange={ ( next ) => setMeta( { ...meta, [ name ]: next } ) }
					/>
				) ) }
			</VStack>
		</PluginDocumentSettingPanel>
	);
}

registerPlugin( 'daguah-details', { render: DetailsPanel } );
