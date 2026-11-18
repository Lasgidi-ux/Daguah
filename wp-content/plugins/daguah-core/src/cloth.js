/**
 * The Cloth: a seeded textile pattern generator. No dependencies.
 *
 * This file has a PHP twin, includes/class-cloth.php. Both must return the
 * same SVG string for the same seed and family, so:
 *   - all arithmetic is on integers (no floats to format differently),
 *   - the random source is xorshift32 seeded by FNV-1a over UTF-8 bytes,
 *   - random draws happen in the same order in both files.
 * Run `npm run test:cloth` after changing either file.
 *
 * The motifs are placeholders drawn from the general shape of each tradition.
 * They are to be replaced by motifs drawn with African textile artists; each
 * motif is one small function so a swap touches one place in each twin.
 */

export const PALETTE = {
	night: '#131209',
	raised: '#1F1F1F',
	lantern: '#D9A21B',
	indigo: '#24306B',
	laterite: '#B5532A',
	palm: '#2F5D46',
	shea: '#F2EADB',
};

export const FAMILIES = [ 'adire', 'kente', 'bogolan', 'kuba', 'mix' ];

const TILE = 192;
const CELL = 48;

/** FNV-1a, 32 bit, over the UTF-8 bytes of the string. */
export function hash( str ) {
	const bytes = new TextEncoder().encode( String( str ) );
	let h = 0x811c9dc5;
	for ( const byte of bytes ) {
		h ^= byte;
		h = Math.imul( h, 0x01000193 ) >>> 0;
	}
	return h >>> 0;
}

/** xorshift32. Returns a function giving an integer from 0 to n - 1. */
function makeRng( seed ) {
	let x = seed === 0 ? 0x9e3779b9 : seed;
	return ( n ) => {
		x ^= x << 13;
		x >>>= 0;
		x ^= x >>> 17;
		x ^= x << 5;
		x >>>= 0;
		return x % n;
	};
}

export function normaliseFamily( family ) {
	return FAMILIES.includes( family ) ? family : 'mix';
}

/** A vendor's region picks the cloth family. Anything else gets the seeded mix. */
export function familyForRegion( region ) {
	const key = String( region || '' ).toLowerCase();
	if ( key.includes( 'yoruba' ) ) {
		return 'adire';
	}
	if ( key.includes( 'ashanti' ) || key.includes( 'asante' ) ) {
		return 'kente';
	}
	if ( key.includes( 'mali' ) ) {
		return 'bogolan';
	}
	if ( key.includes( 'congo' ) || key.includes( 'kuba' ) ) {
		return 'kuba';
	}
	return 'mix';
}

/* Shapes ----------------------------------------------------------------- */

const rect = ( x, y, w, h, f ) =>
	`<rect x="${ x }" y="${ y }" width="${ w }" height="${ h }" fill="${ f }"/>`;
const dot = ( cx, cy, r, f ) =>
	`<circle cx="${ cx }" cy="${ cy }" r="${ r }" fill="${ f }"/>`;
const ring = ( cx, cy, r, s ) =>
	`<circle cx="${ cx }" cy="${ cy }" r="${ r }" fill="none" stroke="${ s }" stroke-width="2"/>`;
const line = ( d, s, w ) =>
	`<path d="${ d }" fill="none" stroke="${ s }" stroke-width="${ w }"/>`;
const poly = ( p, f ) => `<polygon points="${ p }" fill="${ f }"/>`;
const diamond = ( cx, cy, r ) =>
	`${ cx },${ cy - r } ${ cx + r },${ cy } ${ cx },${ cy + r } ${ cx - r },${ cy }`;

const RING = [
	[ 16, 0 ],
	[ 11, 11 ],
	[ 0, 16 ],
	[ -11, 11 ],
	[ -16, 0 ],
	[ -11, -11 ],
	[ 0, -16 ],
	[ 11, -11 ],
];

/* Motifs: each draws inside one 48 by 48 cell at x, y ------------------- */

function mConcentric( x, y, a, b ) {
	const cx = x + 24;
	const cy = y + 24;
	return ring( cx, cy, 20, a ) + ring( cx, cy, 13, a ) + ring( cx, cy, 6, b ) + dot( cx, cy, 2, a );
}

function mDots( x, y, a, b ) {
	let s = '';
	for ( let j = 0; j < 3; j++ ) {
		for ( let i = 0; i < 3; i++ ) {
			s += dot( x + 12 + i * 12, y + 12 + j * 12, 3, i === 1 && j === 1 ? b : a );
		}
	}
	return s;
}

function mLines( x, y, a, flip ) {
	let s = '';
	for ( let i = 0; i < 4; i++ ) {
		s += flip
			? line( `M${ x + 9 + i * 10 } ${ y + 8 }V${ y + 40 }`, a, 2 )
			: line( `M${ x + 8 } ${ y + 9 + i * 10 }H${ x + 40 }`, a, 2 );
	}
	return s;
}

function mRing( x, y, a, b ) {
	const cx = x + 24;
	const cy = y + 24;
	let s = '';
	for ( const [ dx, dy ] of RING ) {
		s += dot( cx + dx, cy + dy, 2, a );
	}
	return s + dot( cx, cy, 5, b );
}

function mDiamond( x, y, a, b ) {
	const cx = x + 24;
	const cy = y + 24;
	return (
		`<polygon points="${ diamond( cx, cy, 18 ) }" fill="none" stroke="${ a }" stroke-width="2"/>` +
		dot( cx, cy, 3, b )
	);
}

function mNested( x, y, a, b, c ) {
	const cx = x + 24;
	const cy = y + 24;
	return poly( diamond( cx, cy, 24 ), a ) + poly( diamond( cx, cy, 16 ), b ) + poly( diamond( cx, cy, 8 ), c );
}

function mMaze( x, y, a, flip ) {
	const d = flip
		? `M${ x + 42 } ${ y + 6 }H${ x + 6 }V${ y + 42 }H${ x + 34 }V${ y + 14 }H${ x + 14 }V${ y + 34 }H${ x + 26 }V${ y + 22 }`
		: `M${ x + 6 } ${ y + 6 }H${ x + 42 }V${ y + 42 }H${ x + 14 }V${ y + 14 }H${ x + 34 }V${ y + 34 }H${ x + 22 }V${ y + 22 }`;
	return line( d, a, 4 );
}

function mSplit( x, y, a, b, c, flip ) {
	const x2 = x + 48;
	const y2 = y + 48;
	const halves = flip
		? poly( `${ x },${ y } ${ x2 },${ y } ${ x2 },${ y2 }`, a ) + poly( `${ x },${ y } ${ x2 },${ y2 } ${ x },${ y2 }`, b )
		: poly( `${ x },${ y } ${ x2 },${ y } ${ x },${ y2 }`, a ) + poly( `${ x2 },${ y } ${ x2 },${ y2 } ${ x },${ y2 }`, b );
	return halves + poly( diamond( x + 24, y + 24, 8 ), c );
}

function mCross( x, y, a ) {
	return rect( x + 21, y + 10, 6, 28, a ) + rect( x + 10, y + 21, 28, 6, a );
}

function mBlock( x, y, a, b ) {
	return rect( x + 8, y + 8, 32, 32, a ) + rect( x + 16, y + 16, 16, 16, b );
}

/* Families ---------------------------------------------------------------- */

/** Adire: concentric circles, dot grids and resist lines on indigo. */
function adire( r ) {
	const P = PALETTE;
	let s = rect( 0, 0, TILE, TILE, P.indigo );
	for ( let row = 0; row < 4; row++ ) {
		for ( let col = 0; col < 4; col++ ) {
			const x = col * CELL;
			const y = row * CELL;
			const m = r( 5 );
			const a = r( 6 ) === 0 ? P.lantern : P.shea;
			const b = r( 4 ) === 0 ? P.lantern : P.shea;
			const flip = r( 2 ) === 1;
			let g = `<rect x="${ x }" y="${ y }" width="48" height="48" fill="none" stroke="${ P.night }" stroke-opacity="0.4"/>`;
			if ( m === 0 ) {
				g += mConcentric( x, y, a, b );
			} else if ( m === 1 ) {
				g += mDots( x, y, a, b );
			} else if ( m === 2 ) {
				g += mLines( x, y, a, flip );
			} else if ( m === 3 ) {
				g += mRing( x, y, a, b );
			} else {
				g += mDiamond( x, y, a, b );
			}
			s += `<g>${ g }</g>`;
		}
	}
	return s;
}

/** Kente: five narrow strips, alternating warp colours, blocks, bars and zigzags. */
function kente( r ) {
	const P = PALETTE;
	const warp = [ P.lantern, P.palm, P.laterite, P.indigo, P.night ];
	const start = r( 5 );
	let s = '';
	for ( let i = 0; i < 5; i++ ) {
		const x = i * 32;
		const gi = ( start + i ) % 5;
		let g = rect( x, 0, 32, TILE, warp[ gi ] );
		for ( let j = 0; j < 8; j++ ) {
			const y = j * 24;
			const t = r( 4 );
			const a = warp[ ( gi + 1 + r( 4 ) ) % 5 ];
			const bi = r( 5 );
			const b = bi === 0 ? P.shea : warp[ ( gi + bi ) % 5 ];
			if ( t === 1 ) {
				g += rect( x, y + 2, 32, 4, a ) + rect( x, y + 10, 32, 4, a ) + rect( x, y + 18, 32, 4, a );
			} else if ( t === 2 ) {
				g += line( `M${ x } ${ y + 18 }L${ x + 8 } ${ y + 6 }L${ x + 16 } ${ y + 18 }L${ x + 24 } ${ y + 6 }L${ x + 32 } ${ y + 18 }`, b, 3 );
			} else if ( t === 3 ) {
				g += rect( x + 6, y + 4, 20, 16, a ) + rect( x + 12, y + 8, 8, 8, b );
			}
		}
		g += rect( x + 30, 0, 2, TILE, P.night );
		s += `<g>${ g }</g>`;
	}
	return s;
}

/** Bògòlan: mudcloth marks in bands. Zigzags, crosses, dots, ladders. */
function bogolan( r ) {
	const P = PALETTE;
	const gi = r( 3 );
	const ground = [ P.laterite, P.raised, P.shea ][ gi ];
	const mark = gi === 2 ? P.night : P.shea;
	const accent = gi === 0 ? P.night : P.laterite;
	let s = rect( 0, 0, TILE, TILE, ground );
	for ( let j = 0; j < 4; j++ ) {
		const y = j * 48;
		const t = r( 5 );
		let g = `<rect x="0" y="${ y }" width="192" height="2" fill="${ mark }" fill-opacity="0.5"/>`;
		if ( t === 0 ) {
			let d = `M0 ${ y + 36 }`;
			for ( let i = 1; i <= 16; i++ ) {
				d += `L${ i * 12 } ${ i % 2 === 1 ? y + 12 : y + 36 }`;
			}
			g += line( d, mark, 4 );
		} else if ( t === 1 ) {
			for ( let k = 0; k < 4; k++ ) {
				g += mCross( k * 48, y, mark );
			}
		} else if ( t === 2 ) {
			for ( let k = 0; k < 8; k++ ) {
				g += dot( 12 + k * 24, y + 16, 3, mark ) + dot( 12 + k * 24, y + 34, 3, k % 2 === 0 ? accent : mark );
			}
		} else if ( t === 3 ) {
			g += rect( 0, y + 11, 192, 3, mark ) + rect( 0, y + 36, 192, 3, mark );
			for ( let k = 0; k < 12; k++ ) {
				g += rect( 8 + k * 16, y + 14, 3, 22, mark );
			}
		} else {
			for ( let k = 0; k < 4; k++ ) {
				g += mDiamond( k * 48, y, mark, accent );
			}
		}
		s += `<g>${ g }</g>`;
	}
	return s;
}

/** Kuba: interlocking diamonds and maze lines. */
function kuba( r ) {
	const P = PALETTE;
	const light = r( 2 ) === 1;
	const ground = light ? P.shea : P.night;
	const ink = light ? P.night : P.shea;
	const cols = [ P.laterite, P.lantern ];
	let s = rect( 0, 0, TILE, TILE, ground );
	for ( let row = 0; row < 4; row++ ) {
		for ( let col = 0; col < 4; col++ ) {
			const x = col * CELL;
			const y = row * CELL;
			const m = r( 3 );
			const flip = r( 2 ) === 1;
			const c = cols[ r( 2 ) ];
			let g;
			if ( m === 0 ) {
				g = mNested( x, y, ink, c, ground );
			} else if ( m === 1 ) {
				g = mMaze( x, y, ink, flip );
			} else {
				g = mSplit( x, y, ink, c, ground, flip );
			}
			s += `<g>${ g }</g>`;
		}
	}
	return s;
}

/** Mix: for regions outside the four families. Motifs from all of them. */
function mix( r ) {
	const P = PALETTE;
	const ink = [ P.shea, P.lantern, P.laterite ];
	const acc = [ P.lantern, P.laterite, P.shea, P.palm ];
	let s = rect( 0, 0, TILE, TILE, P.raised );
	for ( let row = 0; row < 4; row++ ) {
		for ( let col = 0; col < 4; col++ ) {
			const x = col * CELL;
			const y = row * CELL;
			const m = r( 8 );
			const a = ink[ r( 3 ) ];
			const b = acc[ r( 4 ) ];
			const flip = r( 2 ) === 1;
			let g;
			if ( m === 0 ) {
				g = mConcentric( x, y, a, b );
			} else if ( m === 1 ) {
				g = mDots( x, y, a, b );
			} else if ( m === 2 ) {
				g = mLines( x, y, a, flip );
			} else if ( m === 3 ) {
				g = mRing( x, y, a, b );
			} else if ( m === 4 ) {
				g = mNested( x, y, a, P.raised, b );
			} else if ( m === 5 ) {
				g = mMaze( x, y, a, flip );
			} else if ( m === 6 ) {
				g = mCross( x, y, a );
			} else {
				g = mBlock( x, y, a, b );
			}
			s += `<g>${ g }</g>`;
		}
	}
	return s;
}

/**
 * The same seed and family always give the same cloth.
 *
 * @param {string} seed   Usually the seller name plus region.
 * @param {string} family adire, kente, bogolan, kuba or mix.
 * @return {string} SVG markup that fills its container and repeats.
 */
export function svg( seed, family ) {
	const fam = normaliseFamily( family );
	const h = hash( `${ seed }|${ fam }` );
	const r = makeRng( h );
	let width = TILE;
	let body;

	if ( fam === 'adire' ) {
		body = adire( r );
	} else if ( fam === 'kente' ) {
		body = kente( r );
		width = 160;
	} else if ( fam === 'bogolan' ) {
		body = bogolan( r );
	} else if ( fam === 'kuba' ) {
		body = kuba( r );
	} else {
		body = mix( r );
	}

	const id = `cloth-${ h.toString( 16 ) }`;

	return (
		`<svg xmlns="http://www.w3.org/2000/svg" class="daguah-cloth daguah-cloth--${ fam }" width="100%" height="100%" aria-hidden="true" focusable="false">` +
		`<defs><pattern id="${ id }" width="${ width }" height="192" patternUnits="userSpaceOnUse">${ body }</pattern></defs>` +
		`<rect width="100%" height="100%" fill="url(#${ id })"/></svg>`
	);
}
