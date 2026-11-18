/**
 * Editor: the "Reveal on scroll" toggle.
 *
 * Adds one boolean attribute to every block and a control in the sidebar.
 * inc/motion.php turns it into the class the front end observer watches.
 */
import { addFilter } from '@wordpress/hooks';
import { createHigherOrderComponent } from '@wordpress/compose';
import { InspectorControls } from '@wordpress/block-editor';
import { PanelBody, ToggleControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

addFilter(
	'blocks.registerBlockType',
	'daguah/reveal-attribute',
	( settings ) => ( {
		...settings,
		attributes: {
			...settings.attributes,
			daguahReveal: { type: 'boolean', default: false },
		},
	} )
);

const withRevealControl = createHigherOrderComponent(
	( BlockEdit ) => ( props ) => {
		const { attributes, setAttributes, isSelected } = props;

		return (
			<>
				<BlockEdit { ...props } />
				{ isSelected && (
					<InspectorControls>
						<PanelBody
							title={ __( 'Motion', 'daguah' ) }
							initialOpen={ false }
						>
							<ToggleControl
								__nextHasNoMarginBottom
								label={ __( 'Reveal on scroll', 'daguah' ) }
								help={ __(
									'Rises 32px and fades in once as it enters the screen. Visitors who prefer reduced motion get a plain fade.',
									'daguah'
								) }
								checked={ !! attributes.daguahReveal }
								onChange={ ( value ) =>
									setAttributes( { daguahReveal: value } )
								}
							/>
						</PanelBody>
					</InspectorControls>
				) }
			</>
		);
	},
	'withRevealControl'
);

addFilter( 'editor.BlockEdit', 'daguah/reveal-control', withRevealControl );
