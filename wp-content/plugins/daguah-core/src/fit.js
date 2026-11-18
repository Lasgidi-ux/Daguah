/**
 * Fit: the JS twin of includes/class-fit.php. Keep the two identical.
 *
 *   fits   every shared measurement within 2 cm
 *   alter  every shared measurement within 5 cm
 *   no     anything further out
 */
export const KEYS = [ 'bust', 'waist', 'hip', 'length', 'shoulder' ];
export const FITS_CM = 2;
export const ALTER_CM = 5;

const round1 = ( n ) => Math.round( n * 10 ) / 10;

/**
 * @param {Object} piece Piece measurements in cm.
 * @param {Object} body  Buyer measurements in cm.
 * @return {{status: string, diffs: Object}|null} Null when nothing can be compared.
 */
export function compare( piece = {}, body = {} ) {
	const diffs = {};

	KEYS.forEach( ( key ) => {
		const a = Number( piece[ key ] );
		const b = Number( body[ key ] );
		if ( piece[ key ] != null && body[ key ] != null && Number.isFinite( a ) && Number.isFinite( b ) ) {
			diffs[ key ] = round1( a - b );
		}
	} );

	const values = Object.values( diffs );
	if ( ! values.length ) {
		return null;
	}

	const worst = Math.max( ...values.map( Math.abs ) );
	let status = 'no';
	if ( worst <= FITS_CM ) {
		status = 'fits';
	} else if ( worst <= ALTER_CM ) {
		status = 'alter';
	}

	return { status, diffs };
}

/** "bust:92,waist:76" for the Fits me filter. */
export function serialise( body = {} ) {
	return KEYS.filter( ( key ) => body[ key ] )
		.map( ( key ) => `${ key }:${ body[ key ] }` )
		.join( ',' );
}
