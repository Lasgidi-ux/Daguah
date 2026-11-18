/**
 * Build the home page hero video: "One Cloth, Many Hands".
 *
 * Five shots of hands at work, cut into one graded loop that never shows a
 * seam: the parcel's indigo cloth at the end dissolves into the indigo dye
 * pot at the start. Writes, into the theme:
 *
 *   assets/video/opening.mp4           1280 x 720, for wide screens
 *   assets/video/opening-portrait.mp4  540 x 960, framed per shot for phones
 *   assets/video/opening.jpg           still shown before the video plays
 *   assets/video/opening-portrait.jpg
 *
 * Source clips live in media/hero, named in running order.
 * To swap in real footage, replace a clip and run: npm run hero-video
 *
 * Needs ffmpeg on the PATH.
 */
import { spawnSync } from 'node:child_process';
import { existsSync, mkdirSync, statSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = join( dirname( fileURLToPath( import.meta.url ) ), '..' );
const src = join( root, 'media/hero' );
const out = join( root, 'wp-content/themes/daguah/assets/video' );

/*
 * Each shot: file, first and last second used, the horizontal centre of the
 * action (0 to 1) for the portrait crop, and a slight zoom where the source
 * has soft dark corners to lose.
 */
const SHOTS = [
	{ file: '1-dye.mp4', from: 0, to: 4, centre: 0.6, zoom: 1 },
	{ file: '2-loom.mp4', from: 0, to: 3, centre: 0.48, zoom: 1.06 },
	{ file: '3-lace.mp4', from: 0.4, to: 4, centre: 0.64, zoom: 1 },
	{ file: '4-leather.mp4', from: 0, to: 3.6, centre: 0.62, zoom: 1 },
	{ file: '5-parcel.mp4', from: 0, to: 4, centre: 0.62, zoom: 1 },
];

// Dissolve between shots, and across the loop point.
const FADE = 0.5;

/*
 * One grade over every shot, so they read as one film: a touch more
 * contrast, a little less saturation, cool shadows and warm lantern
 * highlights, a soft vignette and fine moving grain.
 */
const GRADE = [
	'eq=contrast=1.06:saturation=0.86:gamma=0.97',
	'colorbalance=rs=-0.03:bs=0.05:rh=0.04:bh=-0.04',
	'vignette=angle=PI/5',
	'noise=alls=5:allf=t+u',
].join( ',' );

for ( const shot of SHOTS ) {
	if ( ! existsSync( join( src, shot.file ) ) ) {
		console.error( `Missing media/hero/${ shot.file }` );
		process.exit( 1 );
	}
}

mkdirSync( out, { recursive: true } );

/**
 * The filter graph for one format.
 *
 * @param {number}  width    Output width.
 * @param {number}  height   Output height.
 * @param {boolean} portrait Crop each shot to 9:16 around its action.
 */
function graph( width, height, portrait ) {
	const parts = [];

	SHOTS.forEach( ( shot, i ) => {
		const length = shot.to - shot.from;
		const zoom = shot.zoom;
		let frame;

		if ( portrait ) {
			// A 9:16 window around the action, inside the (zoomed) frame.
			const cropW = `ih*9/16/${ zoom }`;
			frame = `crop=${ cropW }:ih/${ zoom }:min(max(iw*${ shot.centre }-(${ cropW })/2\\,0)\\,iw-(${ cropW })):(ih-ih/${ zoom })/2`;
		} else {
			frame = `crop=iw/${ zoom }:ih/${ zoom }`;
		}

		parts.push(
			`[${ i }:v]trim=${ shot.from }:${ shot.to },setpts=PTS-STARTPTS,fps=24,${ frame },` +
				`scale=${ width }:${ height }:flags=lanczos,unsharp=5:5:0.4,setsar=1,format=yuv420p,trim=duration=${ length }[s${ i }]`
		);
	} );

	// Chain the shots with dissolves.
	let last = 's0';
	let length = SHOTS[ 0 ].to - SHOTS[ 0 ].from;

	for ( let i = 1; i < SHOTS.length; i++ ) {
		const next = `c${ i }`;
		parts.push( `[${ last }][s${ i }]xfade=transition=fade:duration=${ FADE }:offset=${ ( length - FADE ).toFixed( 3 ) }[${ next }]` );
		length += SHOTS[ i ].to - SHOTS[ i ].from - FADE;
		last = next;
	}

	// The loop: start half a second in, and dissolve the end into those
	// first half second, so the last frame leads straight into the first.
	parts.push( `[${ last }]split[a][b]` );
	parts.push( `[a]trim=${ FADE }:${ length },setpts=PTS-STARTPTS[body]` );
	parts.push( `[b]trim=0:${ FADE },setpts=PTS-STARTPTS[head]` );
	parts.push( `[body][head]xfade=transition=fade:duration=${ FADE }:offset=${ ( length - 2 * FADE ).toFixed( 3 ) },${ GRADE }[out]` );

	return { filter: parts.join( ';' ), length: length - FADE };
}

function run( args ) {
	const result = spawnSync( 'ffmpeg', [ '-v', 'error', '-y', ...args ], { stdio: 'inherit' } );
	if ( result.status !== 0 ) {
		process.exit( result.status ?? 1 );
	}
}

const inputs = SHOTS.flatMap( ( shot ) => [ '-i', join( src, shot.file ) ] );

for ( const [ name, width, height, portrait ] of [
	[ 'opening', 1280, 720, false ],
	[ 'opening-portrait', 540, 960, true ],
] ) {
	const { filter, length } = graph( width, height, portrait );
	const file = join( out, `${ name }.mp4` );

	run( [
		...inputs,
		'-filter_complex', filter,
		'-map', '[out]',
		'-an',
		'-c:v', 'libx264',
		'-preset', 'slow',
		'-crf', portrait ? '27' : '26',
		'-maxrate', portrait ? '1500k' : '2600k',
		'-bufsize', '5000k',
		'-profile:v', 'high',
		'-pix_fmt', 'yuv420p',
		'-g', '48',
		'-movflags', '+faststart',
		file,
	] );

	// The still: the dye pot, a second in.
	run( [ '-ss', '1', '-i', file, '-frames:v', '1', '-q:v', '4', join( out, `${ name }.jpg` ) ] );

	const mb = ( statSync( file ).size / 1024 / 1024 ).toFixed( 2 );
	console.log( `${ name }.mp4  ${ width }x${ height }  ${ length.toFixed( 1 ) }s  ${ mb } MB` );
}
