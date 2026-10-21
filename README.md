# PicCachePro
## by Markus Jäger
### Version 2.0.7

---

## Overview

PicCachePro is a lightweight PHP framework for converting JPEG and PNG images into the AVIF format. Processing is done using the full server path to the image file.

Optionally, both image quality and a target width can be defined. The target width is normalized to increments of 50, rounded up, and only applied if it is smaller than the original image width.

Due to the relatively long processing time of AVIF images, a SQLite3-based cache is used to optimize performance. Encoding speed is passed to the underlying HEIF/AVIF encoder and may behave differently depending on the ImageMagick/libheif build.

Previously generated images are referenced via a hash of their parameters, preventing redundant AVIF conversions and significantly reducing response times.

The goal of this framework is to deliver high image quality while maintaining very small file sizes. AVIF files are supported by most modern web browsers.

## Workflow
- The image is loaded via its full server path. Only JPEG and PNG images are supported.
- Image quality and optional target width are processed.
- The target width is normalized using configurable step increments (default: 50) and only applied if it is smaller than the original image width.
- The cache is checked for an existing entry with the same image content, quality and dimensions.
- If found, the cached AVIF image is passed to the callback directly.
- Otherwise, the image is encoded as AVIF, stored in the cache and then passed to the callback as binary data.

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

---

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

#### Version 2.0.7 Changelog:
- README: Removed the section "Exceptions", the error messages are self-explanatory. No functional changes.

#### Version 2.0.6 Changelog:
- Added an additional error check: avifCreateRawFromPath() throws "Unable to read the image dimensions." if the image dimensions cannot be read (throw_invalid_image_size()).
- Corrected the comments of avifCreateRawFromPath(): only JPEG and PNG images are supported, and the target width is rounded up to a multiple of `MULTIPLIER`.
- Code style: Replaced `array()` with `[]`.
- README: Added the section "Exceptions", corrected the order of the workflow (the cache is checked before the AVIF encoding) and added the `---` separator before the license notice.
- Revised the complete changelog: All versions since 0.9 were reviewed against the commits. Short entries were completed with the actual changes, and breaking changes are marked.

#### Version 2.0.5 Changelog:
- Breaking change: New parameter order of avifCreateRawFromPath( $picturePath, $callback, $quality, $targetWidth ). The required callback now follows the image path, so the optional parameters are at the end and their default values apply. Fixes the deprecation "Optional parameter declared before required parameter".

#### Version 2.0.4 Changelog:
- Breaking change: avifCreateRawFromPath() only accepts a Closure as callback. Function names as string (e.g. `'my_function'`) or array callables (e.g. `[$object, 'method']`) are no longer supported.
- Updated the callback docblocks from callable to Closure.

#### Version 2.0.3 Changelog:
- Changed: Replaced void with never for methods that always throw an exception.

#### Version 2.0.2 Changelog:
- Changed the exception class of all error helpers from `InvalidArgumentException` to `RuntimeException`.

#### Version 2.0.1 Changelog:
- Replaced the protected static properties (`$DefaultQuality`, `$DefaultSpeed`, `$Multiplier`, `$CacheTable`, `$CachePath`, `$ToolkitSQLite3`) with protected constants (`DEFAULT_QUALITY`, `DEFAULT_SPEED`, `MULTIPLIER`, `CACHE_TABLE`, `CACHE_PATH`, `TOOLKIT_SQLITE3`).
- Added the constants `RESIZE_BLUR` and `HASH_ALGORITHM`.
- Breaking change: Class extensions that override the configuration must redefine the constants instead of the static properties.
- Added license info in source code (license header in `PicCachePro.php`).

#### Version 2.0.0 Changelog:
- Complete redesign and rewrite of the framework, released on `main` with the merge of the branch `Beta-Includes-SQLite3` (2026-06-17).
- The AVIF cache is stored in a SQLite3 database (ToolkitSQLite3, table `picturecache`) instead of `.raw` files. The cache key is an xxh128 hash of the image content, the quality and the dimensions (before: SHA3-384 of the path, quality and dimensions).
- Only JPEG and PNG images are accepted (MIME type check).
- Centralized exception handling helpers: invalid input and failures throw an `InvalidArgumentException` instead of being ignored silently.
- Configuration through protected static properties for late static binding (`$DefaultQuality`, `$DefaultSpeed`, `$Multiplier`, `$CacheTable`, `$CachePath`, `$ToolkitSQLite3`). The AVIF encoding speed is passed to the encoder (`heic:speed`, `heif:speed`), resizing uses a blur of 0.9.
- Breaking change: Removed writePersistentData(), readPersistentData(), generateFullPath(), sanitizeAlphaNumDashUnderscore(), sanitizeAlphaNum() and the hooks protectedHookBasePath(), protectedHookFullPath(), protectedHookBeforeWrite() and protectedHookAfterRead(). The default value of `$quality` is -1 (falls back to the default quality).
- Added LICENSE file and the license notice in the README.

#### Version 1.0.7 Changelog:
- Optimize Code: writePersistentData() and readPersistentData() check the file name before the path is generated.

#### Version 1.0.6 Changelog:
- Optimize Code: Internal calls of the final methods use `self::` instead of `static::`.

#### Version 1.0.5 Changelog:
- Optimize Code: Declared all public methods as final. Escaped the dash in the pattern of sanitizeAlphaNumDashUnderscore().
- Breaking change: Class extensions can no longer override the public methods.

#### Version 1.0.4 Changelog:
- Optimized code for better performance and readability.
- Added extended file and data validation: The directory must exist and be writable before writing, the file must be readable before reading.
- Implemented enhanced support for class extensions: Added generateFullPath(), sanitizeAlphaNumDashUnderscore(), sanitizeAlphaNum() and the hooks protectedHookBasePath() and protectedHookFullPath().
- Breaking change: protectedHookFilePath() was replaced by protectedHookBasePath() and protectedHookFullPath().

#### Version 1.0.3 Changelog:
- Renaming variables and methods: setPersistentData() / getPersistentData() are now writePersistentData() / readPersistentData(), protectedHookPath() is now protectedHookFilePath(), protectedHookContentSet() / protectedHookContentGet() are now protectedHookBeforeWrite() / protectedHookAfterRead().
- File methods can also work with empty content.
- Breaking change: The previous method names no longer exist.

#### Version 1.0.2 Changelog:
- Optimized code quality: One return statement per method (`$fnResult`) with `@var` docblocks, `@param` docblock for the callback.

#### Version 1.0.1 Changelog:
- Optimized code quality: Removed protectedHookExtension(), the file extension is fixed to `.raw`. Simplified the file checks.

#### Version 1.0 Changelog:
- Optimized code quality: setPersistentData() and getPersistentData() take a file slug (only `a-z`, `A-Z`, `0-9`, `-` and `_` are allowed), protectedHookPath() builds the full path including the extension.
- The cache file name is `picturecache__` followed by a SHA3-384 hash of the path, quality, width and height.

#### Version 0.99 Changelog:
- Optimized code quality: Added the hooks protectedHookContentSet() and protectedHookContentGet() to modify the content before writing and after reading.

#### Version 0.98 Changelog:
- Optimized code quality.
- Added Protected methods and properties for late static binding: protectedHookPath() and protectedHookExtension(). setPersistentData() and getPersistentData() take a file name instead of a full path.

#### Version 0.97 Changelog:
- Optimized code quality: The target width is rounded up to a multiple of 50 instead of 100. Indentation with tabs.

#### Version 0.96 Changelog:
- Convert avifCreateRawFromPath to Callback function: The AVIF data, width and height are passed to a callback instead of being returned.
- Breaking change: New parameter `$callback`, the return type is `void`.

#### Version 0.95 Changelog:
- Bugfix: Prevent a PHP error if the server does not support imagemagick AVIF (`queryFormats()` check).
- Comments updated. The default value of `$quality` is 0 (falls back to 30).

#### Version 0.94 Changelog:
- Bugfix: avifCreateRawFromPath: The target width is only applied if it is smaller than the source width (read with `getimagesize()`), an invalid quality falls back to 30.

#### Version 0.93 Changelog:
- Round the image width: The target width is rounded up to a multiple of 100, so not every pixel width is cached separately.

#### Version 0.92 Changelog:
- Code Update: Renamed setDataCache() / getDataCache() to setPersistentData() / getPersistentData() (file extension `.raw` instead of `.cache`) and avifCreateFromPath() to avifCreateRawFromPath(), which returns the AVIF binary data instead of a Base64 data URL.

#### Version 0.91 Changelog:
- Code Update: Replaced picture_createfrom_path() (WebP) with avifCreateFromPath() (AVIF data URL) and a file cache in `_cache_chmod777` (setDataCache(), getDataCache()).

#### Version 0.9 Changelog:
- Init: picture_createfrom_path() creates a WebP data URL (Base64) from an image path with Imagick, with optional resizing and compression settings.
