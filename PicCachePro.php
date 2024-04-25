<?php


    class PicCachePro { // PicCachePro::xxx();


        // Write some content to an .cache file.
        // -----------------------------------------------------------------------------------------------------------------------------

        public static function setDataCache( string $cachePath, string $data ) : bool {

            if( !file_exists( $cachePath ) ) {

                if( pathinfo( $cachePath )['extension'] === 'cache' ) {

                    if( file_put_contents( $cachePath, $data, LOCK_EX ) !== false ) {

                        if( file_exists( $cachePath ) ) {

                            return (bool) true;
                        }
                    }
                }
            }

            return (bool) false;
        }





        // Read some content from an .cache file.
        // -----------------------------------------------------------------------------------------------------------------------------

        public static function getDataCache( string $cachePath ) : string {

            if( file_exists( $cachePath ) ) {

                if( pathinfo( $cachePath )['extension'] === 'cache' ) {

                    return (string) file_get_contents( $cachePath );
                }
            }

            return (string) '';
        }





        // Generates a AVIF dataurl image as base64 from an image path (jpg, png, gif).
        // -----------------------------------------------------------------------------------------------------------------------------

        public static function avifCreateFromPath( string $picturePath, int $quality = 30, int $targetWidth = 0 ) : string {

            if( file_exists( $picturePath ) ) {

                // Lösung bauen, wenn ( $targetWidth > 0 ) ist, dann fülle auf die nächste 100er Zahl auf. Dies verhindert das jeder Pixel seperat gespeichert wird. Dies ergibt dann nämlich pro Bild max 40 cache dateien pro bild.

                $cachePath = __DIR__ . DIRECTORY_SEPARATOR . '_cache_chmod777' . DIRECTORY_SEPARATOR . hash('sha3-512', $picturePath . strval($quality) . strval($targetWidth)) . '.cache';
                $cacheData = self::getDataCache( $cachePath );

                if( empty( $cacheData ) ) {

                    if( extension_loaded('imagick') === true ) {

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
                        $dataurl = 'data:image/avif;base64,' . base64_encode( $image->getImageBlob() ); 
                        $image->clear();

                        self::setDataCache( $cachePath, $dataurl );

                        return (string) $dataurl;
                    }
                }
                else {

                    return (string) $cacheData;
                }
            }

            return (string) '';
        }


    }


?>