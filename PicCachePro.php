<?php


	class PicCachePro { // PicCachePro::xxx();


		// Protected methods and properties for late static binding.
		// -----------------------------------------------------------------------------------------------------------------------------

		// Returns the full file path for a persistent file.
		protected static function protectedHookPath( string $filename ) : string {

			return __DIR__ . DIRECTORY_SEPARATOR . '_cache_chmod777' . DIRECTORY_SEPARATOR . $filename;
		}


		// Returns the default file extension for persistent storage.
		protected static function protectedHookExtension() : string {

			return 'raw';
		}


		// Hook for setting content before writing to the file.
		protected static function protectedHookContentSet( string $filecontent ) : string {

			return $filecontent; // Can be extended for further processing.
		}


		// Hook for getting content after reading from the file.
		protected static function protectedHookContentGet( string $filecontent ) : string {

			return $filecontent; // Can be extended for further processing.
		}





		// Write data to a persistent file. The file extension must match the one defined in protectedHookExtension.
		// -----------------------------------------------------------------------------------------------------------------------------

		public static function setPersistentData( string $filename, string $filecontent ) : bool {

			$filepath = static::protectedHookPath( $filename );

			if( pathinfo( $filepath, PATHINFO_EXTENSION ) === static::protectedHookExtension() ) {

				if( file_put_contents( $filepath, static::protectedHookContentSet( $filecontent ), LOCK_EX ) !== false ) {

					if( file_exists( $filepath ) ) {

						return true;
					}
				}
			}

			return false;
		}





		// Read data from a persistent file. The file extension must match the one defined in protectedHookExtension.
		// -----------------------------------------------------------------------------------------------------------------------------

		public static function getPersistentData( string $filename ) : string {

			$filepath = static::protectedHookPath( $filename );

			if( pathinfo( $filepath, PATHINFO_EXTENSION ) === static::protectedHookExtension() && file_exists( $filepath ) ) {

				$filecontent = file_get_contents( $filepath );

				if( !empty( $filecontent ) ) {

					return static::protectedHookContentGet( $filecontent );
				}
			}

			return '';
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
					$filename = hash( 'sha3-512', $picturePath . strval( $quality ) . strval( $realWidth ) ) . '.' . static::protectedHookExtension();

					// Check if cached data already exists.
					$filecontent = static::getPersistentData( $filename );

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
						static::setPersistentData( $filename, $filecontent );
					}

					// Execute the callback function with the generated AVIF data and final image dimensions.
					$callback( (string) $filecontent, (int) $realWidth, (int) $realHeight );
				}
			}
		}


	}


?>