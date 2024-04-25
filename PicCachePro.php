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





        // Generates a AVIF dataurl image as base64 from an image path (jpg, png, gif).
        // quality: An int between 1 and 100, 1 = high compression, 100 low compression.
        // targetWidth: Image width in pixels.
        // -----------------------------------------------------------------------------------------------------------------------------

        public static function avifCreateRawFromPath( string $picturePath, int $quality = 30, int $targetWidth = 0 ) : string {

            $rawdata = '';

            if( !empty( $picturePath ) && file_exists( $picturePath ) && extension_loaded('imagick') === true ) {

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
                if( $quality < 1 || $quality > 100 ) $quality = 30;


                $filepath = __DIR__ . DIRECTORY_SEPARATOR . '_cache_chmod777' . DIRECTORY_SEPARATOR . hash('sha3-512', $picturePath . strval($quality) . strval($realWidth)) . '.raw';
                $rawdata  = self::getPersistentData( $filepath );

                if( empty( $rawdata ) ) {

                    $image = new Imagick();
                    $image->readImage( $picturePath );


                    // Resize image.
                    // -----
                    if( $realWidth !== $sourceWidth ) {

                        // Calculate the aspect ratio inner bracket & then calculate new height based on the aspect ratio and new width outer bracket.
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

            return (string) $rawdata;
        }


    }


?>