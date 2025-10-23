<?php


	class PicCachePro { // PicCachePro::xxx();


		// Protected properties for late static binding.
		// -----------------------------------------------------------------------------------------------------------------------------

		protected static $DefaultQuality = 30; // 0-100
		protected static $DefaultSpeed   = 4;  // 0-9
		protected static $Multiplier     = 50;
		protected static $CacheTable     = 'picturecache';
		protected static $CachePath      = __DIR__ . DIRECTORY_SEPARATOR . '_cache_chmod777' . DIRECTORY_SEPARATOR . 'PicCachePro.sqlite3';
		protected static $ToolkitSQLite3 = 'ToolkitSQLite3';





		// Centralized exception handling helpers.
		// -----------------------------------------------------------------------------------------------------------------------------

		private static function throw_invalid_filepath() : void {

			throw new InvalidArgumentException( 'PicCachePro: Invalid image file path or file does not exist.' );
		}


		private static function throw_unsupported_format() : void {

			throw new InvalidArgumentException( 'PicCachePro: Invalid image format. Only JPEG and PNG files are supported.' );
		}


		private static function throw_imagick_missing() : void {

			throw new InvalidArgumentException( 'PicCachePro: Imagick extension is missing or not enabled.' );
		}


		private static function throw_avif_not_supported() : void {

			throw new InvalidArgumentException( 'PicCachePro: AVIF format is not supported by this Imagick installation.' );
		}


		private static function throw_database_connection_failed() : void {

			throw new InvalidArgumentException( 'PicCachePro: Database connection failed or could not be initialized.' );
		}


		private static function throw_database_write_failed() : void {

			throw new InvalidArgumentException( 'PicCachePro: Failed to write AVIF image to cache database.' );
		}





		// Converts an image to AVIF format and provides the generated binary data via a callback.
		// A cached version of the processed image is stored in SQLite3 to avoid repeated AVIF conversions.
		// Parameters:
		// - $picturePath: Absolute path to the source image.
		// - $quality: AVIF compression quality from 0-100. Falls back to the default quality value if invalid.
		// - $targetWidth: Optional target width. The image is resized proportionally if the target width is smaller than the source width.
		// - $callback: Receives the generated AVIF binary data and final image dimensions.
		// Supported image formats depend on the local Imagick installation.
		// -----------------------------------------------------------------------------------------------------------------------------

		final public static function avifCreateRawFromPath( string $picturePath, int $quality = -1, int $targetWidth = 0, callable $callback ) : void {

			if( !empty( $picturePath ) && is_file( $picturePath ) ) {

				$mimeType = mime_content_type( $picturePath );

				if( $mimeType === 'image/jpeg' || $mimeType === 'image/png' ) {

					if( extension_loaded( 'imagick' ) === true ) {

						$size = getimagesize( $picturePath );

						if( in_array( 'AVIF', Imagick::queryFormats(), true ) && !empty( $size ) && is_array( $size ) && isset( $size[0], $size[1] ) ) {

							$class = static::$ToolkitSQLite3;
							$db = new $class( static::$CachePath, static::$CacheTable );

							if( $db->table_add_ignore() && $db->columns_add_ignore( 'quality', 'speed', 'realWidth', 'realHeight', 'basename', 'sizeKB', 'binaryAvif' ) ) {


								// Calculate output dimensions based on source size and normalized target width.
								$sourceWidth  = $size[0];
								$sourceHeight = $size[1];

								$realWidth  = $sourceWidth;
								$realHeight = $sourceHeight;

								if( $targetWidth > 0 ) {

									$targetWidth = intval( ceil( $targetWidth / static::$Multiplier ) * static::$Multiplier );

									if( $targetWidth < $sourceWidth ) {

										$realWidth  = $targetWidth;
										$realHeight = intval( ceil( $targetWidth / ( $sourceWidth / $sourceHeight ) ) );
									}
								}


								// Fallback to the default quality if the value is outside the valid range.
								if( $quality < 0 || $quality > 100 ) $quality = static::$DefaultQuality;


								// Generate a unique cache key based on image content, quality, and target dimensions.
								$hash = hash( 'xxh128', hash_file( 'xxh128', $picturePath, true ) . '|' . $quality . '|' . $realWidth . '|' . $realHeight );


								// Create the SELECT statement to check whether the image already exists in the cache table.
								$select = array(
									'columns' => ['binaryAvif'],
									'where' => [
										'column' => '_slug',
										'op'     => '=',
										'value'  => $hash,
									],
									'limit' => 1,
								);


								// Return cached AVIF if available, otherwise generate and store it in cache.
								if( !empty( $pictureCache = $db->select( $select ) ) ) {

									$filecontent = $class::resource_to_string( array_shift( $pictureCache )['binaryAvif'] );

									/** @param callable(string, int, int) $callback */
									$callback( $filecontent, $realWidth, $realHeight );
								}
								else {

									// Load the source image into Imagick.
									$image = new Imagick();
									$image->readImage( $picturePath );

									// Resize the image proportionally if the calculated dimensions differ from the original image size.
									if( $realWidth !== $sourceWidth ) {

										$image->resizeImage( $realWidth, $realHeight, Imagick::FILTER_LANCZOS, 0.9, false );
									}

									// Prepare and encode the image as AVIF.
									$image->stripImage();
									$image->setImageFormat( 'avif' );
									$image->setCompressionQuality( $quality );

									// Lower speed levels improve compression efficiency but increase encoding time.
									// This behavior is dependent on the underlying AVIF/HEIF library (e.g. libheif / ImageMagick build),
									// and may not be consistently applied across all Imagick installations or encoders.
									$image->setOption( 'heic:speed', static::$DefaultSpeed );
									$image->setOption( 'heif:speed', static::$DefaultSpeed );

									// Export the encoded AVIF image as binary data.
									$filecontent = $image->getImageBlob(); 
									$image->clear();
									$image->destroy();

									// Store the generated AVIF image in the cache database.
									if( $db->row_upsert( $hash, array(
										'quality'     => $quality,
										'speed'       => static::$DefaultSpeed,
										'realWidth'   => $realWidth,
										'realHeight'  => $realHeight,
										'basename'    => basename( $picturePath ),
										'sizeKB'      => round( strlen( $filecontent ) / 1024, 2 ),
										'binaryAvif'  => $class::string_to_resource( $filecontent ),
									) ) ) {

										/** @param callable(string, int, int) $callback */
										$callback( $filecontent, $realWidth, $realHeight );
									}
									else self::throw_database_write_failed();
								}
							}
							else self::throw_database_connection_failed();
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