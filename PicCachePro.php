<?php


    class PicCachePro { // PicCachePro::xxx();


        // Generates a WebP data image URL as base64 from an image path (jpg, png, gif).

        /*
            Override the default settings:

            $options = array(
                'imageFormat' => 'webp',                        // Expects a string with the target file format 'webp'
                'compressionQuality' => 65,                     // An int between 1 and 100, 1 = high compression, 100 low compression
                'targetWidth' => 0,                             // Resize the source image int 0 no, int 500 pixel width. The height adjusts automatically
                'resizeFilter' => Imagick::FILTER_CATROM,       // https://www.php.net/manual/de/imagick.constants.php#imagick.constants.filters
                'setOption' => array(                           // https://imagemagick.org/script/webp.php
                    'webp:method' => '6',
                    'webp:alpha-filtering' => '2',
                    'webp:lossless' => 'false',
                )
            );
        */
        // -----------------------------------------------------------------------------------------------------------------------------

        public static function picture_createfrom_path( string $picturePath, array $options = array() ) : string {

            if( file_exists( $picturePath ) ) {

                if( extension_loaded('imagick') === true ) {
    
                    $settings = array_merge( [
                        'imageFormat' => 'webp',
                        'compressionQuality' => 65,
                        'targetWidth' => 0,
                        'resizeFilter' => Imagick::FILTER_CATROM,
                        'setOption' => array(
                            'webp:method' => '6',
                            'webp:alpha-filtering' => '2',
                            'webp:lossless' => 'false',
                        )
                    ], $options );



                    $image = new Imagick();
                    $image->readImage($picturePath);
    
                    if( $settings['targetWidth'] > 0 ) {
    
                        $sourceWidth  = $image->getImageWidth();
                        $sourceHeight = $image->getImageHeight();
    
                        // Calculate the aspect ratio inner bracket & then calculate new height based on the aspect ratio and new width outer bracket.
                        $targetWidth  = $settings['targetWidth'];
                        $targetHeight = ceil($targetWidth / ($sourceWidth / $sourceHeight));
    
                        $image->resizeImage($targetWidth, $targetHeight, $settings['resizeFilter'], 1, true);
                    }
    
                    $image->setImageFormat( $settings['imageFormat'] );
    
                    foreach( $settings['setOption'] as $key => $value ) {

                        $image->setOption($key, $value); 
                    }
                    $image->setImageCompressionQuality( $settings['compressionQuality'] );
    
                    $dataurl = 'data:image/' . $settings['imageFormat'] . ';base64,' . base64_encode( $image->getImageBlob() ); 
                                    
                    $image->clear();
    
                    return (string) $dataurl;
                }
            }

            return (string) '';
        }


    }
    

?>