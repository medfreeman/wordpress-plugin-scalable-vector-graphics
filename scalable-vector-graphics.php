<?php
/*
 * Plugin Name: Scalable Vector Graphics (SVG)
 * Plugin URI: http://www.sterlinghamilton.com/projects/scalable-vector-graphics/
 * Description: Scalable Vector Graphics are two-dimensional vector graphics, that can be both static and dynamic. This plugin allows your to easily use them on your site.
 * Version: 3.0
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
*/

class scalable_vector_graphics {

	public function execute() {
		$this->_enable_svg_mime_type();
		add_action( 'admin_enqueue_scripts', array( $this, 'styles' ) );
	}

	private function _enable_svg_mime_type() {
		add_filter( 'upload_mimes', array( &$this, 'allow_svg_uploads' ) );
		add_filter( 'wp_prepare_attachment_for_js', array( &$this, 'plugin_prepare_attachment_for_js_filter' ), 10, 3 );
	}

	public function allow_svg_uploads( $existing_mime_types = array() ) {
		return $this->_add_mime_type( $existing_mime_types );
	}

	private function _add_mime_type( $mime_types ) {
		$mime_types[ 'svg' ] = 'image/svg+xml';

		return $mime_types;
	}

	public function styles() {
		wp_add_inline_style( 'wp-admin', ".media .media-icon img[src$='.svg'] { width: auto; height: auto; }" );
	}

	public function plugin_prepare_attachment_for_js_filter( $response, $attachment, $meta ) {
		if( $response['mime'] == 'image/svg+xml' && empty( $response['sizes'] ) ) {
			$svg_file_path = get_attached_file( $attachment->ID );
			$dimensions = $this->_get_dimensions( $svg_file_path );

			$response[ 'sizes' ] = array(
					'full' => array(
						'url' => $response[ 'url' ],
						'width' => $dimensions->width,
						'height' => $dimensions->height,
						'orientation' => $dimensions->width > $dimensions->height ? 'landscape' : 'portrait'
				)
			);
		}

		return $response;
	}

	private function _get_dimensions( $svg ) {
		$svg = simplexml_load_file( $svg );
		$attributes = $svg->attributes();
		$width = (string) $attributes->width;
		$height = (string) $attributes->height;

		return (object) array( 'width' => $width, 'height' => $height );
	}
}

if ( class_exists( 'scalable_vector_graphics' ) and ! isset( $scalable_vector_graphics ) ) {
	$scalable_vector_graphics = new scalable_vector_graphics();
	$scalable_vector_graphics->execute();
}

?>
