<?php
/**
 * The Cloth: a seeded textile pattern generator.
 *
 * PHP twin of src/cloth.js. Both must return the same SVG string for the same
 * seed and family, so all arithmetic is on integers, the random source is
 * xorshift32 seeded by FNV-1a over UTF-8 bytes, and random draws happen in the
 * same order in both files. Run `npm run test:cloth` after changing either.
 *
 * The motifs are placeholders drawn from the general shape of each tradition,
 * to be replaced by motifs drawn with African textile artists. Each motif is
 * one small method so a swap touches one place in each twin.
 *
 * @package Daguah\Core
 */

declare( strict_types=1 );

namespace Daguah\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Seeded SVG textile patterns.
 */
final class Cloth {

	public const FAMILIES = array( 'adire', 'kente', 'bogolan', 'kuba', 'mix' );

	private const NIGHT    = '#131209';
	private const RAISED   = '#1F1F1F';
	private const LANTERN  = '#D9A21B';
	private const INDIGO   = '#24306B';
	private const LATERITE = '#B5532A';
	private const PALM     = '#2F5D46';
	private const SHEA     = '#F2EADB';

	private const TILE = 192;
	private const CELL = 48;

	private const RING = array(
		array( 16, 0 ),
		array( 11, 11 ),
		array( 0, 16 ),
		array( -11, 11 ),
		array( -16, 0 ),
		array( -11, -11 ),
		array( 0, -16 ),
		array( 11, -11 ),
	);

	/**
	 * State of the xorshift32 generator for the cloth being drawn.
	 *
	 * @var int
	 */
	private int $state;

	/**
	 * Start a generator from a 32 bit seed.
	 *
	 * @param int $seed Unsigned 32 bit integer.
	 */
	private function __construct( int $seed ) {
		$this->state = 0 === $seed ? 0x9e3779b9 : $seed;
	}

	/**
	 * FNV-1a, 32 bit, over the UTF-8 bytes of the string.
	 *
	 * @param string $str Any string.
	 */
	public static function hash( string $str ): int {
		$h      = 0x811c9dc5;
		$length = strlen( $str );

		for ( $i = 0; $i < $length; $i++ ) {
			$h ^= ord( $str[ $i ] );
			$h  = ( $h * 0x01000193 ) & 0xFFFFFFFF;
		}

		return $h;
	}

	/**
	 * Keep to the known families; anything else is the mix.
	 *
	 * @param string $family Requested family.
	 */
	public static function normalise_family( string $family ): string {
		return in_array( $family, self::FAMILIES, true ) ? $family : 'mix';
	}

	/**
	 * A vendor's region picks the cloth family.
	 *
	 * @param string $region Vendor region.
	 */
	public static function family_for_region( string $region ): string {
		$key = strtolower( $region );

		if ( str_contains( $key, 'yoruba' ) ) {
			return 'adire';
		}
		if ( str_contains( $key, 'ashanti' ) || str_contains( $key, 'asante' ) ) {
			return 'kente';
		}
		if ( str_contains( $key, 'mali' ) ) {
			return 'bogolan';
		}
		if ( str_contains( $key, 'congo' ) || str_contains( $key, 'kuba' ) ) {
			return 'kuba';
		}

		return 'mix';
	}

	/**
	 * The same seed and family always give the same cloth.
	 *
	 * The markup is built only from integers and the fixed palette above, so
	 * it is safe to print without further escaping.
	 *
	 * @param string $seed   Usually the seller name plus region.
	 * @param string $family adire, kente, bogolan, kuba or mix.
	 * @return string SVG markup that fills its container and repeats.
	 */
	public static function svg( string $seed, string $family = 'mix' ): string {
		$fam   = self::normalise_family( $family );
		$h     = self::hash( $seed . '|' . $fam );
		$cloth = new self( $h );
		$width = self::TILE;

		if ( 'adire' === $fam ) {
			$body = $cloth->adire();
		} elseif ( 'kente' === $fam ) {
			$body  = $cloth->kente();
			$width = 160;
		} elseif ( 'bogolan' === $fam ) {
			$body = $cloth->bogolan();
		} elseif ( 'kuba' === $fam ) {
			$body = $cloth->kuba();
		} else {
			$body = $cloth->mix();
		}

		$id = 'cloth-' . dechex( $h );

		return '<svg xmlns="http://www.w3.org/2000/svg" class="daguah-cloth daguah-cloth--' . $fam . '" width="100%" height="100%" aria-hidden="true" focusable="false">'
			. '<defs><pattern id="' . $id . '" width="' . $width . '" height="192" patternUnits="userSpaceOnUse">' . $body . '</pattern></defs>'
			. '<rect width="100%" height="100%" fill="url(#' . $id . ')"/></svg>';
	}

	/**
	 * Next integer from 0 to n - 1.
	 *
	 * @param int $n Upper bound, exclusive.
	 */
	private function r( int $n ): int {
		$x  = $this->state;
		$x ^= ( $x << 13 ) & 0xFFFFFFFF;
		$x ^= $x >> 17;
		$x ^= ( $x << 5 ) & 0xFFFFFFFF;

		$this->state = $x;

		return $x % $n;
	}

	/* Shapes ------------------------------------------------------------- */

	private static function rect( int $x, int $y, int $w, int $h, string $f ): string {
		return '<rect x="' . $x . '" y="' . $y . '" width="' . $w . '" height="' . $h . '" fill="' . $f . '"/>';
	}

	private static function dot( int $cx, int $cy, int $r, string $f ): string {
		return '<circle cx="' . $cx . '" cy="' . $cy . '" r="' . $r . '" fill="' . $f . '"/>';
	}

	private static function ring( int $cx, int $cy, int $r, string $s ): string {
		return '<circle cx="' . $cx . '" cy="' . $cy . '" r="' . $r . '" fill="none" stroke="' . $s . '" stroke-width="2"/>';
	}

	private static function line( string $d, string $s, int $w ): string {
		return '<path d="' . $d . '" fill="none" stroke="' . $s . '" stroke-width="' . $w . '"/>';
	}

	private static function poly( string $p, string $f ): string {
		return '<polygon points="' . $p . '" fill="' . $f . '"/>';
	}

	private static function diamond( int $cx, int $cy, int $r ): string {
		return $cx . ',' . ( $cy - $r ) . ' ' . ( $cx + $r ) . ',' . $cy . ' ' . $cx . ',' . ( $cy + $r ) . ' ' . ( $cx - $r ) . ',' . $cy;
	}

	/* Motifs: each draws inside one 48 by 48 cell at x, y ---------------- */

	private static function m_concentric( int $x, int $y, string $a, string $b ): string {
		$cx = $x + 24;
		$cy = $y + 24;

		return self::ring( $cx, $cy, 20, $a ) . self::ring( $cx, $cy, 13, $a ) . self::ring( $cx, $cy, 6, $b ) . self::dot( $cx, $cy, 2, $a );
	}

	private static function m_dots( int $x, int $y, string $a, string $b ): string {
		$s = '';

		for ( $j = 0; $j < 3; $j++ ) {
			for ( $i = 0; $i < 3; $i++ ) {
				$s .= self::dot( $x + 12 + $i * 12, $y + 12 + $j * 12, 3, 1 === $i && 1 === $j ? $b : $a );
			}
		}

		return $s;
	}

	private static function m_lines( int $x, int $y, string $a, bool $flip ): string {
		$s = '';

		for ( $i = 0; $i < 4; $i++ ) {
			$s .= $flip
				? self::line( 'M' . ( $x + 9 + $i * 10 ) . ' ' . ( $y + 8 ) . 'V' . ( $y + 40 ), $a, 2 )
				: self::line( 'M' . ( $x + 8 ) . ' ' . ( $y + 9 + $i * 10 ) . 'H' . ( $x + 40 ), $a, 2 );
		}

		return $s;
	}

	private static function m_ring( int $x, int $y, string $a, string $b ): string {
		$cx = $x + 24;
		$cy = $y + 24;
		$s  = '';

		foreach ( self::RING as $offset ) {
			$s .= self::dot( $cx + $offset[0], $cy + $offset[1], 2, $a );
		}

		return $s . self::dot( $cx, $cy, 5, $b );
	}

	private static function m_diamond( int $x, int $y, string $a, string $b ): string {
		$cx = $x + 24;
		$cy = $y + 24;

		return '<polygon points="' . self::diamond( $cx, $cy, 18 ) . '" fill="none" stroke="' . $a . '" stroke-width="2"/>'
			. self::dot( $cx, $cy, 3, $b );
	}

	private static function m_nested( int $x, int $y, string $a, string $b, string $c ): string {
		$cx = $x + 24;
		$cy = $y + 24;

		return self::poly( self::diamond( $cx, $cy, 24 ), $a ) . self::poly( self::diamond( $cx, $cy, 16 ), $b ) . self::poly( self::diamond( $cx, $cy, 8 ), $c );
	}

	private static function m_maze( int $x, int $y, string $a, bool $flip ): string {
		$d = $flip
			? 'M' . ( $x + 42 ) . ' ' . ( $y + 6 ) . 'H' . ( $x + 6 ) . 'V' . ( $y + 42 ) . 'H' . ( $x + 34 ) . 'V' . ( $y + 14 ) . 'H' . ( $x + 14 ) . 'V' . ( $y + 34 ) . 'H' . ( $x + 26 ) . 'V' . ( $y + 22 )
			: 'M' . ( $x + 6 ) . ' ' . ( $y + 6 ) . 'H' . ( $x + 42 ) . 'V' . ( $y + 42 ) . 'H' . ( $x + 14 ) . 'V' . ( $y + 14 ) . 'H' . ( $x + 34 ) . 'V' . ( $y + 34 ) . 'H' . ( $x + 22 ) . 'V' . ( $y + 22 );

		return self::line( $d, $a, 4 );
	}

	private static function m_split( int $x, int $y, string $a, string $b, string $c, bool $flip ): string {
		$x2 = $x + 48;
		$y2 = $y + 48;

		$halves = $flip
			? self::poly( $x . ',' . $y . ' ' . $x2 . ',' . $y . ' ' . $x2 . ',' . $y2, $a ) . self::poly( $x . ',' . $y . ' ' . $x2 . ',' . $y2 . ' ' . $x . ',' . $y2, $b )
			: self::poly( $x . ',' . $y . ' ' . $x2 . ',' . $y . ' ' . $x . ',' . $y2, $a ) . self::poly( $x2 . ',' . $y . ' ' . $x2 . ',' . $y2 . ' ' . $x . ',' . $y2, $b );

		return $halves . self::poly( self::diamond( $x + 24, $y + 24, 8 ), $c );
	}

	private static function m_cross( int $x, int $y, string $a ): string {
		return self::rect( $x + 21, $y + 10, 6, 28, $a ) . self::rect( $x + 10, $y + 21, 28, 6, $a );
	}

	private static function m_block( int $x, int $y, string $a, string $b ): string {
		return self::rect( $x + 8, $y + 8, 32, 32, $a ) . self::rect( $x + 16, $y + 16, 16, 16, $b );
	}

	/* Families ----------------------------------------------------------- */

	/**
	 * Adire: concentric circles, dot grids and resist lines on indigo.
	 */
	private function adire(): string {
		$s = self::rect( 0, 0, self::TILE, self::TILE, self::INDIGO );

		for ( $row = 0; $row < 4; $row++ ) {
			for ( $col = 0; $col < 4; $col++ ) {
				$x    = $col * self::CELL;
				$y    = $row * self::CELL;
				$m    = $this->r( 5 );
				$a    = 0 === $this->r( 6 ) ? self::LANTERN : self::SHEA;
				$b    = 0 === $this->r( 4 ) ? self::LANTERN : self::SHEA;
				$flip = 1 === $this->r( 2 );
				$g    = '<rect x="' . $x . '" y="' . $y . '" width="48" height="48" fill="none" stroke="' . self::NIGHT . '" stroke-opacity="0.4"/>';

				if ( 0 === $m ) {
					$g .= self::m_concentric( $x, $y, $a, $b );
				} elseif ( 1 === $m ) {
					$g .= self::m_dots( $x, $y, $a, $b );
				} elseif ( 2 === $m ) {
					$g .= self::m_lines( $x, $y, $a, $flip );
				} elseif ( 3 === $m ) {
					$g .= self::m_ring( $x, $y, $a, $b );
				} else {
					$g .= self::m_diamond( $x, $y, $a, $b );
				}

				$s .= '<g>' . $g . '</g>';
			}
		}

		return $s;
	}

	/**
	 * Kente: five narrow strips, alternating warp colours, blocks, bars and zigzags.
	 */
	private function kente(): string {
		$warp  = array( self::LANTERN, self::PALM, self::LATERITE, self::INDIGO, self::NIGHT );
		$start = $this->r( 5 );
		$s     = '';

		for ( $i = 0; $i < 5; $i++ ) {
			$x  = $i * 32;
			$gi = ( $start + $i ) % 5;
			$g  = self::rect( $x, 0, 32, self::TILE, $warp[ $gi ] );

			for ( $j = 0; $j < 8; $j++ ) {
				$y  = $j * 24;
				$t  = $this->r( 4 );
				$a  = $warp[ ( $gi + 1 + $this->r( 4 ) ) % 5 ];
				$bi = $this->r( 5 );
				$b  = 0 === $bi ? self::SHEA : $warp[ ( $gi + $bi ) % 5 ];

				if ( 1 === $t ) {
					$g .= self::rect( $x, $y + 2, 32, 4, $a ) . self::rect( $x, $y + 10, 32, 4, $a ) . self::rect( $x, $y + 18, 32, 4, $a );
				} elseif ( 2 === $t ) {
					$g .= self::line( 'M' . $x . ' ' . ( $y + 18 ) . 'L' . ( $x + 8 ) . ' ' . ( $y + 6 ) . 'L' . ( $x + 16 ) . ' ' . ( $y + 18 ) . 'L' . ( $x + 24 ) . ' ' . ( $y + 6 ) . 'L' . ( $x + 32 ) . ' ' . ( $y + 18 ), $b, 3 );
				} elseif ( 3 === $t ) {
					$g .= self::rect( $x + 6, $y + 4, 20, 16, $a ) . self::rect( $x + 12, $y + 8, 8, 8, $b );
				}
			}

			$g .= self::rect( $x + 30, 0, 2, self::TILE, self::NIGHT );
			$s .= '<g>' . $g . '</g>';
		}

		return $s;
	}

	/**
	 * Bògòlan: mudcloth marks in bands. Zigzags, crosses, dots, ladders.
	 */
	private function bogolan(): string {
		$gi      = $this->r( 3 );
		$grounds = array( self::LATERITE, self::RAISED, self::SHEA );
		$ground  = $grounds[ $gi ];
		$mark    = 2 === $gi ? self::NIGHT : self::SHEA;
		$accent  = 0 === $gi ? self::NIGHT : self::LATERITE;
		$s       = self::rect( 0, 0, self::TILE, self::TILE, $ground );

		for ( $j = 0; $j < 4; $j++ ) {
			$y = $j * 48;
			$t = $this->r( 5 );
			$g = '<rect x="0" y="' . $y . '" width="192" height="2" fill="' . $mark . '" fill-opacity="0.5"/>';

			if ( 0 === $t ) {
				$d = 'M0 ' . ( $y + 36 );
				for ( $i = 1; $i <= 16; $i++ ) {
					$d .= 'L' . ( $i * 12 ) . ' ' . ( 1 === $i % 2 ? $y + 12 : $y + 36 );
				}
				$g .= self::line( $d, $mark, 4 );
			} elseif ( 1 === $t ) {
				for ( $k = 0; $k < 4; $k++ ) {
					$g .= self::m_cross( $k * 48, $y, $mark );
				}
			} elseif ( 2 === $t ) {
				for ( $k = 0; $k < 8; $k++ ) {
					$g .= self::dot( 12 + $k * 24, $y + 16, 3, $mark ) . self::dot( 12 + $k * 24, $y + 34, 3, 0 === $k % 2 ? $accent : $mark );
				}
			} elseif ( 3 === $t ) {
				$g .= self::rect( 0, $y + 11, 192, 3, $mark ) . self::rect( 0, $y + 36, 192, 3, $mark );
				for ( $k = 0; $k < 12; $k++ ) {
					$g .= self::rect( 8 + $k * 16, $y + 14, 3, 22, $mark );
				}
			} else {
				for ( $k = 0; $k < 4; $k++ ) {
					$g .= self::m_diamond( $k * 48, $y, $mark, $accent );
				}
			}

			$s .= '<g>' . $g . '</g>';
		}

		return $s;
	}

	/**
	 * Kuba: interlocking diamonds and maze lines.
	 */
	private function kuba(): string {
		$light  = 1 === $this->r( 2 );
		$ground = $light ? self::SHEA : self::NIGHT;
		$ink    = $light ? self::NIGHT : self::SHEA;
		$cols   = array( self::LATERITE, self::LANTERN );
		$s      = self::rect( 0, 0, self::TILE, self::TILE, $ground );

		for ( $row = 0; $row < 4; $row++ ) {
			for ( $col = 0; $col < 4; $col++ ) {
				$x    = $col * self::CELL;
				$y    = $row * self::CELL;
				$m    = $this->r( 3 );
				$flip = 1 === $this->r( 2 );
				$c    = $cols[ $this->r( 2 ) ];

				if ( 0 === $m ) {
					$g = self::m_nested( $x, $y, $ink, $c, $ground );
				} elseif ( 1 === $m ) {
					$g = self::m_maze( $x, $y, $ink, $flip );
				} else {
					$g = self::m_split( $x, $y, $ink, $c, $ground, $flip );
				}

				$s .= '<g>' . $g . '</g>';
			}
		}

		return $s;
	}

	/**
	 * Mix: for regions outside the four families. Motifs from all of them.
	 */
	private function mix(): string {
		$ink = array( self::SHEA, self::LANTERN, self::LATERITE );
		$acc = array( self::LANTERN, self::LATERITE, self::SHEA, self::PALM );
		$s   = self::rect( 0, 0, self::TILE, self::TILE, self::RAISED );

		for ( $row = 0; $row < 4; $row++ ) {
			for ( $col = 0; $col < 4; $col++ ) {
				$x    = $col * self::CELL;
				$y    = $row * self::CELL;
				$m    = $this->r( 8 );
				$a    = $ink[ $this->r( 3 ) ];
				$b    = $acc[ $this->r( 4 ) ];
				$flip = 1 === $this->r( 2 );

				if ( 0 === $m ) {
					$g = self::m_concentric( $x, $y, $a, $b );
				} elseif ( 1 === $m ) {
					$g = self::m_dots( $x, $y, $a, $b );
				} elseif ( 2 === $m ) {
					$g = self::m_lines( $x, $y, $a, $flip );
				} elseif ( 3 === $m ) {
					$g = self::m_ring( $x, $y, $a, $b );
				} elseif ( 4 === $m ) {
					$g = self::m_nested( $x, $y, $a, self::RAISED, $b );
				} elseif ( 5 === $m ) {
					$g = self::m_maze( $x, $y, $a, $flip );
				} elseif ( 6 === $m ) {
					$g = self::m_cross( $x, $y, $a );
				} else {
					$g = self::m_block( $x, $y, $a, $b );
				}

				$s .= '<g>' . $g . '</g>';
			}
		}

		return $s;
	}
}
