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
        // -----------------------------------------------------------------------------------------------------------------------------

        public static function avifCreateRawFromPath( string $picturePath, int $quality = 30, int $targetWidth = 0 ) : string {

            $rawdata = '';

            if( file_exists( $picturePath ) ) {

                // Lösung bauen, wenn ( $targetWidth > 0 ) ist, dann fülle auf die nächste 100er Zahl auf. Dies verhindert das jeder Pixel seperat gespeichert wird. Dies ergibt dann nämlich pro Bild max 40 dateien pro bild.

                $filepath = __DIR__ . DIRECTORY_SEPARATOR . '_cache_chmod777' . DIRECTORY_SEPARATOR . hash('sha3-512', $picturePath . strval($quality) . strval($targetWidth)) . '.raw';
                $rawdata  = self::getPersistentData( $filepath );

                if( empty( $rawdata ) && extension_loaded('imagick') === true ) {

                    $image = new Imagick();
                    $image->readImage($picturePath);

                    if( $targetWidth > 0 ) {

                        $sourceWidth  = $image->getImageWidth();
                        $sourceHeight = $image->getImageHeight();

                        if( $targetWidth < $sourceWidth ) {

                            // Calculate the aspect ratio inner bracket & then calculate new height based on the aspect ratio and new width outer bracket.
                            $targetHeight = ceil($targetWidth / ($sourceWidth / $sourceHeight));

                            $image->resizeImage($targetWidth, $targetHeight, Imagick::FILTER_CATROM, 1, true);
                        }
                    }

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