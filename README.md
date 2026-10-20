# PicCachePro
## by Markus Jäger
### Version 2.0.5

---

## Overview

PicCachePro is a lightweight PHP framework for converting JPEG and PNG images into the AVIF format. Processing is done using the full server path to the image file.

Optionally, both image quality and a target width can be defined. The target width is normalized to increments of 50, rounded up, and only applied if it is smaller than the original image width.

Due to the relatively long processing time of AVIF images, a SQLite3-based cache is used to optimize performance. Encoding speed is passed to the underlying HEIF/AVIF encoder and may behave differently depending on the ImageMagick/libheif build.

Previously generated images are referenced via a hash of their parameters, preventing redundant AVIF conversions and significantly reducing response times.

The goal of this framework is to deliver high image quality while maintaining very small file sizes. AVIF files are supported by most modern web browsers.

## Workflow
- The image is loaded via its full server path.
- Image quality and optional target width are processed.
- The target width is normalized using configurable step increments (default: 50) and only applied if it is smaller than the original image width.
- The image is encoded as AVIF and returned as binary data.
- Before generation, the cache is checked for an existing entry.
- If found, the cached image is returned directly.
- Otherwise, the image is generated and stored in the cache.

## Scaling Logic
The target width is normalized as follows:
```
120 → 150  
149 → 150  
151 → 200  
```
The calculation uses the same configurable step increments (default: 50).

## Protected Constants Configuration

The following protected constants define the default behavior and internal configuration of PicCachePro.
They can be overridden in child classes by redefining the constants and accessed through late static binding.

- `DEFAULT_QUALITY` Default image quality level (0–100) used when no valid quality value is provided.
- `DEFAULT_SPEED` Default AVIF encoding speed passed to the HEIF/AVIF encoder. Lower values may improve compression efficiency but increase encoding time depending on the underlying encoder implementation.
- `MULTIPLIER` Scaling multiplier used for calculating resized image dimensions.
- `RESIZE_BLUR` Blur parameter used during resizing. Values greater than 1 increase blur, while values below 1 reduce blur.
- `HASH_ALGORITHM` Hash algorithm used to generate cache identifiers from image content and conversion parameters.
- `TOOLKIT_SQLITE3` Name of the ToolkitSQLite3 class used internally for database operations.
- `CACHE_TABLE` Name of the SQLite3 table used for storing cached image data.
- `CACHE_PATH` File path to the SQLite3 cache database.

## Technical Requirements
- PHP with Imagick extension
- AVIF support in Imagick
- SQLite3
- ToolkitSQLite3 class

## Example:
```php
PicCachePro::avifCreateRawFromPath( '/var/www/images/example.jpg', function( string $pictureRaw, int $width, int $height ) : void {

    header( 'Content-Type: image/avif' );
    header( 'Content-Length: ' . strlen( $pictureRaw ) );

    echo $pictureRaw;

}, 80, 623 );
```

## Attribution / License Notice
This repository is licensed under the MIT License.

Any use, copy, modification, or redistribution of this repository
or any substantial portion of it must retain attribution to the
original author and the original GitHub repository.

Copyright (c) 2026 Markus Jäger
https://github.com/m-O-rpheus/PicCachePro

---

### High-level framework dependencies required:
```
PicCachePro-main
:.. ToolkitSQLite3-main
```

---

#### Version 2.0.5 Changelog:
- Breaking change: New parameter order of avifCreateRawFromPath( $picturePath, $callback, $quality, $targetWidth ). The required callback now follows the image path, so the optional parameters are at the end and their default values apply. Fixes the deprecation "Optional parameter declared before required parameter".

#### Version 2.0.4 Changelog:
- Breaking change: avifCreateRawFromPath() only accepts a Closure as callback. Function names as string (e.g. `'my_function'`) or array callables (e.g. `[$object, 'method']`) are no longer supported.
- Updated the callback docblocks from callable to Closure.

#### Version 2.0.3 Changelog:
- Changed: Replaced void with never for methods that always throw an exception.

#### Version 2.0.2 Changelog:
- Renamed error handling methods for clearer naming and improved validation exception handling.

#### Version 2.0.1 Changelog:
- Replaced internal static configuration properties with protected constants for clearer definition of default values.
- Added new constants for resize behavior, hash generation, and internal ToolkitSQLite3 configuration.
- Improved configuration consistency while maintaining customization through inheritance and late static binding.

#### Version 2.0.0 Changelog:
- Complete redesign and rewrite of the framework.
- Fundamental architecture improvements for better maintainability.
- Improved AVIF encoding workflow and caching logic.
- Enhanced performance through optimized SQLite3 cache handling.
- Refactored configuration system using late static binding.
- Improved code structure, readability, and extensibility.
- General stability and reliability improvements.
- Removal of legacy code and outdated patterns.

#### Version 1.0.7 Changelog:
- Optimize Code.

#### Version 1.0.6 Changelog:
- Optimize Code.

#### Version 1.0.5 Changelog:
- Optimize Code.

#### Version 1.0.4 Changelog:
- Optimized code for better performance and readability
- Added extended file and data validation
- Implemented enhanced support for class extensions

#### Version 1.0.3 Changelog:
- Renaming variables and methods.
- File methods can also work with empty content.

#### Version 1.0.2 Changelog:
- Optimized code quality.

#### Version 1.0.1 Changelog:
- Optimized code quality.

#### Version 1.0 Changelog:
- Optimized code quality.

#### Version 0.99 Changelog:
- Optimized code quality.

#### Version 0.98 Changelog:
- Optimized code quality.
- Added Protected methods and properties for late static binding.

#### Version 0.97 Changelog:
- Optimized code quality.

#### Version 0.96 Changelog:
- Convert avifCreateRawFromPath to Callback function.

#### Version 0.95 Changelog:
- Bugfix: Prevent a PHP error if the server does not support imagemagick AVIF.
- Comments updated.

#### Version 0.94 Changelog:
- Bugfix: avifCreateRawFromPath.

#### Version 0.93 Changelog:
- Round the image width.

#### Version 0.92 Changelog:
- Code Update.

#### Version 0.91 Changelog:
- Code Update.

#### Version 0.9 Changelog:
- Init.