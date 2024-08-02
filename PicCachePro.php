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





        // Creates an AVIF image as binary data from an image path
        // #picturePath: Enter the full path to a raster image in JPG, PNG, or GIF format.
        // #quality:     An integer between 1 and 100. 1 = high compression, 100 = low compression. The default value is 0, which corresponds to a compression level of 30.
        // #targetWidth: The desired image width in pixels to which the target image (AVIF) should be downscaled. If targetWidth is 0 or the same as the source width,
        // no downscaling will be applied, and the original image size will be retained. Downscaling will always be to the nearest rounded down hundred.
        // -----------------------------------------------------------------------------------------------------------------------------

        public static function avifCreateRawFromPath( string $picturePath, int $quality = 0, int $targetWidth = 0 ) : string {

            $rawdata = '';

            if( !empty( $picturePath ) && file_exists( $picturePath ) && extension_loaded('imagick') === true ) {

                $image = new Imagick();

                if( !empty( $image->queryFormats('AVIF') ) ) {

                    $size = getimagesize( $picturePath );

                    $sourceWidth  = $size[0];
                    $sourceHeight = $size[1];


                    // Initially use sourceWidth as the width for the image.
                    // Override this value if targetWidth is greater than 0 but less than sourceWidth.
                    // -----
                    $realWidth = $sourceWidth;

                    if( $targetWidth > 0 ) {

                        // Round up the image width. This prevents each pixel size from being stored separately. Instead, it is rounded up to the nearest 100th place.
                        $targetWidth = ( ceil( $targetWidth / 100 ) * 100 );

                        if( $targetWidth < $sourceWidth ) {

                            $realWidth = $targetWidth;
                        }
                    }
                    // -----


                    // Adjust the image quality range.
                    if( $quality < 1 || $quality > 100 ) $quality = 30; // Fallback for default or invalid setting.


                    $filepath = __DIR__ . DIRECTORY_SEPARATOR . '_cache_chmod777' . DIRECTORY_SEPARATOR . hash('sha3-512', $picturePath . strval($quality) . strval($realWidth)) . '.raw';
                    $rawdata  = self::getPersistentData( $filepath );

                    if( empty( $rawdata ) ) {

                        $image->readImage( $picturePath );


                        // Resize image.
                        // -----
                        if( $realWidth !== $sourceWidth ) {

                            // Calculate the aspect ratio inner bracket & then calculate new height based on the aspect ratio and real width outer bracket.
                            $realHeight = ceil($realWidth / ($sourceWidth / $sourceHeight));

                            $image->resizeImage($realWidth, $realHeight, Imagick::FILTER_LANCZOS, 1, true);
                        }
                        // -----


                        $image->setImageFormat('avif');

                        //$image->setOption('avif:lossless', 'false'); NOT WORKING
                        //$image->setOption('avif:codec', 'libaom-av1'); NOT WORKING
                        //$image->setOption('avif:quality', '50'); NOT WORKING

                        $image->setCompressionQuality($quality);
                        $rawdata = $image->getImageBlob(); 
                        $image->clear();

                        self::setPersistentData( $filepath, $rawdata );
                    }
                }
            }

            return (string) $rawdata;
        }


    }


?>