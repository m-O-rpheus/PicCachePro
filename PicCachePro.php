<?php


	class PicCachePro { // PicCachePro::xxx();


		// Write some content to an .raw file.
		// -----------------------------------------------------------------------------------------------------------------------------

		public static function setPersistentData( string $filepath, string $rawdata ) : bool {

			if( !file_exists( $filepath ) ) {

				if( pathinfo( $filepath )['extension'] === 'raw' ) {

					if( file_put_contents( $filepath, $rawdata, LOCK_EX ) !== false ) {

						if( file_exists( $filepath ) ) {

							return (bool) true;
						}
					}
				}
			}

			return (bool) false;
		}





		// Read some content from an .raw file.
		// -----------------------------------------------------------------------------------------------------------------------------

		public static function getPersistentData( string $filepath ) : string {

			if( file_exists( $filepath ) ) {

				if( pathinfo( $filepath )['extension'] === 'raw' ) {

					return (string) file_get_contents( $filepath );
				}
			}

			return (string) '';
		}





		// Converts an image (JPG, PNG, or GIF) to AVIF format and returns the binary data.
		// Parameters:
		// - $picturePath: Full path to the input image.
		// - $quality: Compression quality (1-100). Lower values mean higher compression. Default is 30 if invalid or not set.
		// - $targetWidth: Desired width of the output AVIF image. If 0 or equal to the source width, no resizing occurs.
		// - $callback: Function to be called with the resulting image data.
		// The method stores a cached version of the processed image to improve performance on repeated calls.
		// -----------------------------------------------------------------------------------------------------------------------------

		public static function avifCreateRawFromPath( string $picturePath, int $quality = 0, int $targetWidth = 0, callable $callback ) : void {

			if( !empty( $picturePath ) && file_exists( $picturePath ) && extension_loaded( 'imagick' ) === true ) {

				$image = new Imagick();
				$size  = getimagesize( $picturePath );

				if( !empty( $image->queryFormats( 'AVIF' ) ) && !empty( $size ) ) {

					$sourceWidth  = $size[0];
					$sourceHeight = $size[1];

					// Determine the final image dimensions.
					$realWidth  = $sourceWidth;
					$realHeight = $sourceHeight;

					if( $targetWidth > 0 ) {

						// Round target width to the nearest 50 pixels for optimized storage.
						$targetWidth = ceil( $targetWidth / 50 ) * 50;

						if( $targetWidth < $sourceWidth ) {

							$realWidth  = $targetWidth;
							$realHeight = ceil( $targetWidth / ( $sourceWidth / $sourceHeight ) );
						}
					}

					// Ensure quality is within valid range (defaulting to 30 if not set properly).
					if( $quality < 1 || $quality > 100 ) $quality = 30;

					// Generate cache file path based on input parameters.
					$filepath = __DIR__ . DIRECTORY_SEPARATOR . '_cache_chmod777' . DIRECTORY_SEPARATOR . hash( 'sha3-512', $picturePath . strval( $quality ) . strval( $realWidth ) ) . '.raw';

					// Check if cached data already exists.
					$rawdata  = self::getPersistentData( $filepath );

					if( empty( $rawdata ) ) {

						// Load the image.
						$image->readImage( $picturePath );

						// Resize if needed.
						if( $realWidth !== $sourceWidth ) {

							$image->resizeImage( $realWidth, $realHeight, Imagick::FILTER_LANCZOS, 1, false );
						}

						// Convert to AVIF format.
						$image->setImageFormat( 'avif' );
						//$image->setOption('avif:lossless', 'false'); NOT WORKING
						//$image->setOption('avif:codec', 'libaom-av1'); NOT WORKING
						//$image->setOption('avif:quality', '50'); NOT WORKING
						$image->setCompressionQuality( $quality );
						$rawdata = $image->getImageBlob(); 
						$image->clear();

						// Store the processed image in cache.
						self::setPersistentData( $filepath, $rawdata );
					}

					// Execute the callback function with the generated AVIF data and final image dimensions.
					$callback( (string) $rawdata, (int) $realWidth, (int) $realHeight );
				}
			}
		}


	}


?>