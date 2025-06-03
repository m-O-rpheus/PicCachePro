<?php


	class PicCachePro { // PicCachePro::xxx();


		// Protected methods and properties for late static binding.
		// -----------------------------------------------------------------------------------------------------------------------------

		// Returns the full file path for a persistent file.
		protected static function protectedHookFilePath( string $filename ) : string {

			/** @var string */
			return __DIR__ . DIRECTORY_SEPARATOR . '_cache_chmod777' . DIRECTORY_SEPARATOR . $filename . '.raw'; // Can be extended for further processing.
		}


		// Hook for setting content before writing to the file.
		protected static function protectedHookBeforeWrite( string $filecontent ) : string {

			/** @var string */
			return $filecontent; // Can be extended for further processing.
		}


		// Hook for getting content after reading from the file.
		protected static function protectedHookAfterRead( string $filecontent ) : string {

			/** @var string */
			return $filecontent; // Can be extended for further processing.
		}





		// Write and read string data to/from files with filenames sanitized to allow only letters, numbers, dash, and underscore.
		// Applies hooks to process content before writing and after reading.
		// -----------------------------------------------------------------------------------------------------------------------------

		public static function writePersistentData( string $filename, string $filecontent ) : bool {

			$fnResult = false; 
			$filename = preg_replace( '/[^a-zA-Z0-9-_]/', '', $filename ); // Only allow a-z A-Z 0-9 - _

			if( !empty( $filename ) ) {

				$filename    = static::protectedHookFilePath( $filename ); // Override variable filename with full file path, including filename.
				$filecontent = static::protectedHookBeforeWrite( $filecontent );

				if( file_put_contents( $filename, $filecontent, LOCK_EX ) !== false ) {

					$fnResult = true; 
				}
			}

			/** @var bool */
			return $fnResult;
		}


		public static function readPersistentData( string $filename ) : string {

			$fnResult = ''; 
			$filename = preg_replace( '/[^a-zA-Z0-9-_]/', '', $filename ); // Only allow a-z A-Z 0-9 - _

			if( !empty( $filename ) ) {

				$filename = static::protectedHookFilePath( $filename ); // Override variable filename with full file path, including filename.

				if( file_exists( $filename ) && is_file( $filename ) ) {

					$filecontent = file_get_contents( $filename );

					if( $filecontent !== false ) {

						$fnResult = static::protectedHookAfterRead( $filecontent );
					}
				}
			}

			/** @var string */
			return $fnResult;
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

			if( !empty( $picturePath ) && file_exists( $picturePath ) && is_file( $picturePath ) && extension_loaded( 'imagick' ) === true ) {

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

					// Generate picture cache file slug based on input parameters.
					$filename = 'picturecache__' . hash( 'sha3-384', $picturePath . strval( $quality ) . strval( $realWidth ) . strval( $realHeight ) );

					// Check if cached data already exists.
					$filecontent = static::readPersistentData( $filename );

					if( empty( $filecontent ) ) {

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
						$filecontent = $image->getImageBlob(); 
						$image->clear();

						// Store the processed image in cache.
						static::writePersistentData( $filename, $filecontent );
					}

					/** @param callable(string, int, int) $callback */
					$callback( $filecontent, $realWidth, $realHeight ); // Execute the callback function with the generated AVIF data and final image dimensions.
				}
			}
		}


	}


?>