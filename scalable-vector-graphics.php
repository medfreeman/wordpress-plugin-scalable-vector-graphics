<?php
/**
 * Plugin Name: Scalable Vector Graphics (SVG)
 * Plugin URI: http://www.sterlinghamilton.com/projects/scalable-vector-graphics/
 * Description: Scalable Vector Graphics are two-dimensional vector graphics, that can be both static and dynamic. This plugin allows your to easily use them on your site.
 * Version: 3.3.1
 * Author: Sterling Hamilton
 * Author URI: http://www.sterlinghamilton.com/
 * License: GPLv2 or later

 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.

 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.	See the
 * GNU General Public License for more details.

 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA	02110-1301, USA.
 *
 * @package scalable-vector-graphics-svg
 */

namespace SterlingHamilton\Plugins\ScalableVectorGraphics;

/**
 * Returns the accepted value for SVG mime-types in compliance with the RFC 3023.
 * RFC 3023: https://www.ietf.org/rfc/rfc3023.txt 8.19, A.1, A.2, A.3, A.5, and A.7
 * Expects to interface with https://codex.wordpress.org/Plugin_API/Filter_Reference/upload_mimes.
 *
 * @param array $existing_mime_types Current wordpress allowed mime types for upload.
 *
 * @return array Edited mime types array.
 */
function allow_svg_uploads( $existing_mime_types = array() ) {
	return $existing_mime_types + array( 'svg' => 'image/svg+xml' );
}

/**
 * This is a decent way of grabbing the dimensions of SVG files.
 * Depends on http://php.net/manual/en/function.simplexml-load-file.php
 * I believe this to be a reasonable dependency and should be common enough to
 * not cause problems.
 *
 * @param string $svg Svg file path.
 *
 * @return object Object with 'width' and 'height' attributes values of svg file.
 */
function get_dimensions( $svg ) {
	$width = '0';
	$height = '0';

	if ( file_exists( $svg ) ) {
		$svg_markup = simplexml_load_file( $svg );
		if ( false !== $svg_markup ) {
			$attributes = $svg_markup->attributes();
			$width = isset( $attributes->width ) ? (string) $attributes->width : '0';
			$height = isset( $attributes->height ) ? (string) $attributes->height : '0';
		}
	}

	return (object) array( 'width' => $width, 'height' => $height );
}

/**
 * Save svg metadata
 *
 * Consider this the "server side" fix for dimensions.
 * Which is needed for the Media Grid within the Administratior.
 *
 * @param array $metadata      Array of empty attachment
 * metadata (since wordpress doesn't fill metadata for svg files).
 * @param int   $attachment_id Current attachment ID.
 *
 * @return array Modified array of attachment metadata.
 */
function store_metadata_for_svg( $metadata, $attachment_id ) {
	$attachment = get_post( $attachment_id );
	if ( null !== $attachment ) {
		$mime_type = get_post_mime_type( $attachment );

		if ( 'image/svg+xml' === $mime_type ) {
			$svg_file_path = $svg_file_path_relative = get_attached_file( $attachment->ID );
			$uploads = wp_get_upload_dir();
			if ( false === $uploads['error'] ) {
				$svg_file_path_relative = str_replace( $uploads['basedir'] . '/', '', $svg_file_path );
			}
			$dimensions = get_dimensions( $svg_file_path );
			$svg_url = wp_get_attachment_url( $attachment->ID );

			$metadata = array(
				'width'  => $dimensions->width,
				'height' => $dimensions->height,
				'file'   => $svg_file_path_relative,
				'sizes'  => array(
					'full' => array(
						'url' => $svg_url,
						'file' => basename( $svg_file_path_relative ),
						'width' => $dimensions->width,
						'height' => $dimensions->height,
						'orientation' => $dimensions->width > $dimensions->height ? 'landscape' : 'portrait',
						'mime-type' => $mime_type,
					),
				),
			);
		}
	}

	return $metadata;
}

/**
 * Browsers may or may not show SVG files properly without a height/width.
 * WordPress specifically defines width/height as "0" if it cannot figure it out.
 * Thus the below is needed.
 *
 * Consider this the "client side" fix for dimensions. But only for the Administratior.
 *
 * WordPress requires inline administration styles to be wrapped in an actionable function.
 * These styles specifically address the Media Listing styling and Featured Image
 * styling so that the images show up in the Administration area.
 *
 * @return void
 */
function administration_styles() {
	// Media Listing Fix.
	wp_add_inline_style( 'wp-admin', ".media .media-icon img[src$='.svg'] { width: auto; height: auto; }" );
	// Featured Image Fix.
	wp_add_inline_style( 'wp-admin', "#postimagediv .inside img[src$='.svg'] { width: 100%; height: auto; }" );
}

/**
 * Browsers may or may not show SVG files properly without a height/width.
 * WordPress specifically defines width/height as "0" if it cannot figure it out.
 * Thus the below is needed.
 *
 * Consider this the "client side" fix for dimensions. But only for the End User.
 *
 * @prints style tag
 */
function public_styles() {
	// Featured Image Fix.
	echo "<style type=\"text/css\">.post-thumbnail img[src$='.svg'] { width: 100%; height: auto; }</style>";
}

// Do work son.
add_filter( 'upload_mimes', __NAMESPACE__ . '\\allow_svg_uploads' );
add_filter( 'wp_generate_attachment_metadata', __NAMESPACE__ . '\\store_metadata_for_svg', 10, 2 );
add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\\administration_styles' );
add_action( 'wp_head', __NAMESPACE__ . '\\public_styles' );
