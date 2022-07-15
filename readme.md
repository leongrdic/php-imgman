# imgman

This library uses GD and EXIF (optional) PHP extensions so make sure you have them installed.

It also uses the latest PHP 8.1 features and backwards compatibility isn't yet supported.

## Install:
```
composer require leongrdic/imgman
```

```php
use \Le\ImgMan\{ImgMan, ImageFormat};
```

## Supported formats
### Input
- any image format supported by php-gd

Input methods: `fromDataUrl()`, `fromString()`, `fromFile()`

### Output

Call the `output()` method with the wanted output format:
- `ImageFormat::jpeg`
- `ImageFormat::png`
- `ImageFormat::webp` (make sure your php-gd is configured to work with webp)

After that use: `toDataUrl()`, `toString()`, `toFile()`

## Example usages
```php
$rawImageBytes = new ImgMan()
    ->fromDataUrl($dataUrlFromJS)
    ->cacheExif()
    ->downscale(2048)
    ->rotateFromExif() // rotating after downscaling should use less memory and be a bit faster
    ->output(ImageFormat::jpeg, quality: 75)
    ->toString();
```

```php
new ImgMan()
    ->fromFile('example.png')
    ->downscale(1920, 1080)
    ->output(ImageFormat::png)
    ->toFile(); // use input filename (replace original file)
```

```php
$dataUrl = new ImgMan()
    ->fromString($rawImageBytes)
    ->output(ImageFormat::webp, quality: 80)
    ->toDataUrl();
```

# Notice

This library hasn't yet been fully tested and is to be used at your own responsibility.
Any feedback and improvement suggestions are appreciated!