<?php
/**
 * The Opening's film: one video, two framings, and a way to stop it.
 *
 * The cover block saves a plain autoplaying <video>. On the front end this
 * rebuilds it so that:
 *   - phones in portrait get the 9:16 cut, framed shot by shot around the
 *     hands, and everything else gets the 16:9 cut (no JavaScript needed)
 *   - the still shows until the film is ready, and instead of it for
 *     visitors who prefer reduced motion or have asked to save data
 *   - nothing plays until the theme script starts it, which is what lets it
 *     respect those preferences; without JavaScript the still stays
 *   - a pause button sits in the corner, as any moving background longer
 *     than five seconds needs one
 * A shade behind the headline keeps it readable over the brighter shots.
 *
 * The portrait cut and the still are only added for the theme's own film.
 * A video chosen in the editor plays as chosen, with the same button.
 *
 * @package Daguah
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

add_filter(
	'render_block_core/cover',
	static function ( string $content, array $block ): string {
		if ( ! str_contains( (string) ( $block['attrs']['className'] ?? '' ), 'daguah-opening' ) || 'video' !== ( $block['attrs']['backgroundType'] ?? '' ) ) {
			return $content;
		}

		if ( ! preg_match( '#<video\b[^>]*\bsrc="([^"]+)"[^>]*></video>#', $content, $match ) ) {
			return $content;
		}

		$src      = html_entity_decode( $match[1], ENT_QUOTES );
		$own      = get_theme_file_uri( 'assets/video/opening.mp4' );
		$is_own   = untrailingslashit( strtok( $src, '?' ) ) === untrailingslashit( $own );
		$version  = $is_own && is_readable( get_theme_file_path( 'assets/video/opening.mp4' ) ) ? (string) filemtime( get_theme_file_path( 'assets/video/opening.mp4' ) ) : '';
		$with_ver = static fn( string $url ): string => '' !== $version ? add_query_arg( 'v', $version, $url ) : $url;

		$sources = '';
		$poster  = '';

		if ( $is_own ) {
			// Wide first: a browser that ignores media on <source> takes the first one.
			$sources .= sprintf( '<source src="%s" type="video/mp4" media="(min-aspect-ratio: 4/5)">', esc_url( $with_ver( $own ) ) );

			if ( is_readable( get_theme_file_path( 'assets/video/opening-portrait.mp4' ) ) ) {
				$sources .= sprintf( '<source src="%s" type="video/mp4">', esc_url( $with_ver( get_theme_file_uri( 'assets/video/opening-portrait.mp4' ) ) ) );
			} else {
				$sources .= sprintf( '<source src="%s" type="video/mp4">', esc_url( $with_ver( $own ) ) );
			}

			if ( is_readable( get_theme_file_path( 'assets/video/opening.jpg' ) ) ) {
				$poster = $with_ver( get_theme_file_uri( 'assets/video/opening.jpg' ) );
			}
		} else {
			$sources = sprintf( '<source src="%s">', esc_url( $src ) );
		}

		$video = sprintf(
			'<video class="wp-block-cover__video-background intrinsic-ignore daguah-opening__film" muted loop playsinline preload="metadata" disablepictureinpicture data-object-fit="cover"%1$s%2$s>%3$s</video>',
			'' !== $poster ? ' poster="' . esc_url( $poster ) . '"' : '',
			$is_own && is_readable( get_theme_file_path( 'assets/video/opening-portrait.jpg' ) ) ? ' data-poster-portrait="' . esc_url( $with_ver( get_theme_file_uri( 'assets/video/opening-portrait.jpg' ) ) ) . '"' : '',
			$sources
		);

		$icon   = static fn( string $name ): string => function_exists( 'daguah_icon' ) ? daguah_icon( $name ) : '';
		$button = sprintf(
			'<button type="button" class="daguah-opening__pause" aria-label="%1$s" data-label-pause="%1$s" data-label-play="%2$s" hidden><span class="daguah-opening__pause-icon is-pause" aria-hidden="true">%3$s</span><span class="daguah-opening__pause-icon is-play" aria-hidden="true">%4$s</span></button>',
			esc_attr__( 'Pause the background film', 'daguah' ),
			esc_attr__( 'Play the background film', 'daguah' ),
			$icon( 'pause' ),
			$icon( 'play' )
		);

		$shade = '<span class="daguah-opening__shade" aria-hidden="true"></span>';

		return str_replace( $match[0], $video . $shade . $button, $content );
	},
	10,
	2
);
