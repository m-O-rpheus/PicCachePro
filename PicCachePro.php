<?php


	class PicCachePro { // PicCachePro::xxx();


		// Protected methods and properties for late static binding.
		// -----------------------------------------------------------------------------------------------------------------------------

		// Returns the base path for data storage. By default, the "_cache_chmod777" directory is used.
		protected static function protectedHookBasePath() : string {

			/** @var string */
			return __DIR__ . DIRECTORY_SEPARATOR . '_cache_chmod777'; // Can be extended for further processing.
		}

		// Returns the full file path, which includes the base path, filename, and extension.
		protected static function protectedHookFullPath( string $fullpath ) : string {

			/** @var string */
			return $fullpath; // Can be extended for further processing.
		}


		// Allows processing of content before writing it to the file.
		protected static function protectedHookBeforeWrite( string $filecontent ) : string {

			/** @var string */
			return $filecontent; // Can be extended for further processing.
		}


		// Allows processing of content after reading it from the file.
		protected static function protectedHookAfterRead( string $filecontent ) : string {

			/** @var string */
			return $filecontent; // Can be extended for further processing.
		}





		// Cleans a string by removing characters not permitted by the following method(s).
		// -----------------------------------------------------------------------------------------------------------------------------

		// Allows only letters (a-z, A-Z), digits (0-9), dash (-), and underscore (_).
		final public static function sanitizeAlphaNumDashUnderscore( string $str ) : string {

			/** @var string */
			return preg_replace( '/[^a-zA-Z0-9\-_]/', '', $str );
		}


		// Allows only letters (a-z, A-Z) and digits (0-9).
		final public static function sanitizeAlphaNum( string $str ) : string {

			/** @var string */
			return preg_replace( '/[^a-zA-Z0-9]/', '', $str );
		}





		// Generates a full file path consisting of the base path, filename, and extension. The path can be customized in the future by overriding the "protectedHook*" methods in subclasses.
		// -----------------------------------------------------------------------------------------------------------------------------

		final public static function generateFullPath( string $filename, string $extension ) : string {

			$basepath  = static::protectedHookBasePath();
			$filename  = self::sanitizeAlphaNumDashUnderscore( $filename );
			$extension = self::sanitizeAlphaNum( $extension );

			/** @var string */
			return static::protectedHookFullPath( rtrim( $basepath, DIRECTORY_SEPARATOR ) . DIRECTORY_SEPARATOR . $filename . '.' . $extension );
		}





		// Write and read string data to/from files with filenames sanitized to allow only letters, numbers, dash, and underscore.
		// Applies hooks to process content before writing and after reading.
		// -----------------------------------------------------------------------------------------------------------------------------

		final public static function writePersistentData( string $filename, string $filecontent ) : bool {

			$fnResult = false;
			$fullpath = self::generateFullPath( $filename, 'raw' );

			if( !empty( $fullpath ) && is_dir( dirname( $fullpath ) ) && is_writable( dirname( $fullpath ) ) ) {

				$filecontent = static::protectedHookBeforeWrite( $filecontent );

				if( file_put_contents( $fullpath, $filecontent, LOCK_EX ) !== false ) {

					$fnResult = true; 
				}
			}

			/** @var bool */
			return $fnResult;
		}


		final public static function readPersistentData( string $filename ) : string {

			$fnResult = ''; 
			$fullpath = self::generateFullPath( $filename, 'raw' );

			if( !empty( $fullpath ) && file_exists( $fullpath ) && is_file( $fullpath ) && is_readable( $fullpath ) ) {

				if( ( $filecontent = file_get_contents( $fullpath ) ) !== false ) {

					$fnResult = static::protectedHookAfterRead( $filecontent );
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

		final public static function avifCreateRawFromPath( string $picturePath, int $quality = 0, int $targetWidth = 0, callable $callback ) : void {

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
					$filecontent = self::readPersistentData( $filename );

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
						self::writePersistentData( $filename, $filecontent );
					}

					/** @param callable(string, int, int) $callback */
					$callback( $filecontent, $realWidth, $realHeight ); // Execute the callback function with the generated AVIF data and final image dimensions.
				}
			}
		}


	}


?>