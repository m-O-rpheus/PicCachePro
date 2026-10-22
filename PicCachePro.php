<?php


/*
 * PicCachePro
 *
 * This repository is licensed under the MIT License.
 *
 * Any use, copy, modification, or redistribution of this repository
 * or any substantial portion of it must retain attribution to the
 * original author and the original GitHub repository.
 *
 * Copyright (c) 2026 Markus Jäger
 * https://github.com/m-O-rpheus/PicCachePro
 */


	class PicCachePro {


		// Protected constants used for configuration and default settings.
		// -----------------------------------------------------------------------------------------------------------------------------

		protected const DEFAULT_QUALITY = 30;               // Default image quality level (0-100).
		protected const DEFAULT_SPEED   = 4;                // Default AVIF encoding speed level (typically 0-9 depending on encoder support).
		protected const MULTIPLIER      = 50;               // Scaling multiplier used for size calculations.
		protected const RESIZE_BLUR     = 0.9;              // Blur parameter used during resizing: >1 increases blur, <1 reduces blur.
		protected const HASH_ALGORITHM  = 'xxh128';         // Hash algorithm used for generating cache keys.
		protected const TOOLKIT_SQLITE3 = 'ToolkitSQLite3'; // Name of the SQLite3 toolkit class used internally.
		protected const CACHE_TABLE     = 'picturecache';   // Name of the database table used for cached images.
		protected const CACHE_PATH      = __DIR__ . DIRECTORY_SEPARATOR . '_cache_chmod777' . DIRECTORY_SEPARATOR . 'PicCachePro.sqlite3'; // SQLite3 cache database path.





		// Centralized exception handling helpers.
		// -----------------------------------------------------------------------------------------------------------------------------

		private static function throw_invalid_filepath() : never {

			throw new RuntimeException( 'PicCachePro: Invalid image file path or file does not exist.' );
		}


		private static function throw_unsupported_format() : never {

			throw new RuntimeException( 'PicCachePro: Invalid image format. Only JPEG and PNG files are supported.' );
		}


		private static function throw_imagick_missing() : never {

			throw new RuntimeException( 'PicCachePro: Imagick extension is missing or not enabled.' );
		}


		private static function throw_avif_not_supported() : never {

			throw new RuntimeException( 'PicCachePro: AVIF format is not supported by this Imagick installation.' );
		}


		private static function throw_invalid_image_size() : never {

			throw new RuntimeException( 'PicCachePro: Unable to read the image dimensions.' );
		}


		private static function throw_database_connection_failed() : never {

			throw new RuntimeException( 'PicCachePro: Database connection failed or could not be initialized.' );
		}


		private static function throw_database_write_failed() : never {

			throw new RuntimeException( 'PicCachePro: Failed to write AVIF image to cache database.' );
		}





		// Converts an image to AVIF format and provides the generated binary data via a callback.
		// A cached version of the processed image is stored in SQLite3 to avoid repeated AVIF conversions.
		// Parameters:
		// - $picturePath: Absolute path to the source image.
		// - $callback: Receives the generated AVIF binary data and final image dimensions.
		// - $quality: AVIF compression quality from 0-100. Falls back to the default quality value if invalid.
		// - $targetWidth: Optional target width. It is rounded up to a multiple of MULTIPLIER (default: 50) and only applied if it is smaller than the source width. The height is adjusted proportionally.
		// Only JPEG and PNG images are supported. The Imagick installation must support the AVIF format.
		// -----------------------------------------------------------------------------------------------------------------------------

		final public static function avifCreateRawFromPath( string $picturePath, Closure $callback, int $quality = -1, int $targetWidth = 0 ) : void {

			if( !empty( $picturePath ) && is_file( $picturePath ) ) {

				$mimeType = mime_content_type( $picturePath );

				if( $mimeType === 'image/jpeg' || $mimeType === 'image/png' ) {

					if( extension_loaded( 'imagick' ) === true ) {

						if( in_array( 'AVIF', Imagick::queryFormats(), true ) ) {

							$size = getimagesize( $picturePath );

							if( !empty( $size ) && is_array( $size ) && isset( $size[0], $size[1] ) ) {

								$class = static::TOOLKIT_SQLITE3;
								$db = new $class( static::CACHE_PATH, static::CACHE_TABLE );

								if( $db->table_add_ignore() && $db->columns_add_ignore( 'quality', 'speed', 'realWidth', 'realHeight', 'basename', 'sizeKB', 'binaryAvif' ) ) {


									// Calculate output dimensions based on source size and normalized target width.
									$sourceWidth  = $size[0];
									$sourceHeight = $size[1];

									$realWidth  = $sourceWidth;
									$realHeight = $sourceHeight;

									if( $targetWidth > 0 ) {

										$targetWidth = intval( ceil( $targetWidth / static::MULTIPLIER ) * static::MULTIPLIER );

										if( $targetWidth < $sourceWidth ) {

											$realWidth  = $targetWidth;
											$realHeight = intval( ceil( $targetWidth / ( $sourceWidth / $sourceHeight ) ) );
										}
									}


									// Fallback to the default quality if the value is outside the valid range.
									if( $quality < 0 || $quality > 100 ) $quality = static::DEFAULT_QUALITY;


									// Generate a unique cache key based on image content, quality, and target dimensions.
									$hash = hash( static::HASH_ALGORITHM, hash_file( static::HASH_ALGORITHM, $picturePath, true ) . '|' . $quality . '|' . $realWidth . '|' . $realHeight );


									// Create the SELECT statement to check whether the image already exists in the cache table.
									$select = [
										'columns' => ['binaryAvif'],
										'where' => [
											'column' => '_slug',
											'op'     => '=',
											'value'  => $hash,
										],
										'limit' => 1,
									];


									// Return cached AVIF if available, otherwise generate and store it in cache.
									if( !empty( $pictureCache = $db->select( $select ) ) ) {

										$filecontent = $class::resource_to_string( array_shift( $pictureCache )['binaryAvif'] );

										/** @param Closure(string, int, int) $callback */
										$callback( $filecontent, $realWidth, $realHeight );
									}
									else {

										// Load the source image into Imagick.
										$image = new Imagick();
										$image->readImage( $picturePath );

										// Resize the image proportionally if the calculated dimensions differ from the original image size.
										if( $realWidth !== $sourceWidth ) {

											$image->resizeImage( $realWidth, $realHeight, Imagick::FILTER_LANCZOS, static::RESIZE_BLUR, false );
										}

										// Prepare and encode the image as AVIF.
										$image->stripImage();
										$image->setImageFormat( 'avif' );
										$image->setCompressionQuality( $quality );

										// Lower speed levels improve compression efficiency but increase encoding time.
										// This behavior is dependent on the underlying AVIF/HEIF library (e.g. libheif / ImageMagick build),
										// and may not be consistently applied across all Imagick installations or encoders.
										$image->setOption( 'heic:speed', static::DEFAULT_SPEED );
										$image->setOption( 'heif:speed', static::DEFAULT_SPEED );

										// Export the encoded AVIF image as binary data.
										$filecontent = $image->getImageBlob(); 
										$image->clear();
										$image->destroy();

										// Store the generated AVIF image in the cache database.
										if( $db->row_upsert( $hash, [
											'quality'     => $quality,
											'speed'       => static::DEFAULT_SPEED,
											'realWidth'   => $realWidth,
											'realHeight'  => $realHeight,
											'basename'    => basename( $picturePath ),
											'sizeKB'      => round( strlen( $filecontent ) / 1024, 2 ),
											'binaryAvif'  => $class::string_to_resource( $filecontent ),
										] ) ) {

											/** @param Closure(string, int, int) $callback */
											$callback( $filecontent, $realWidth, $realHeight );
										}
										else self::throw_database_write_failed();
									}
								}
								else self::throw_database_connection_failed();
							}
							else self::throw_invalid_image_size();
						}
						else self::throw_avif_not_supported();
					}
					else self::throw_imagick_missing();
				}
				else self::throw_unsupported_format();
			}
			else self::throw_invalid_filepath();
		}


	}


?>