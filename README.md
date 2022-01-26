# imgman

This library uses GD and EXIF (optional) PHP extensions so make sure you have them

Install:
```
composer require leongrdic/imgman
```


Example usages:
```
use Le\ImgMan\{ImgMan, ImageFormat};

$img = new ImgMan()
    ->fromDataUrl($dataUrlFromJS)
    ->cacheExif()
    ->downscale(2048)
    ->rotateFromExif()
    ->output(ImageFormat::jpeg, 75)
    ->toString();
```

```
use Le\ImgMan\{ImgMan, ImageFormat};

new ImgMan()
    ->fromFile('example.png')
    ->downscale(1024)
    ->output(ImageFormat::webp, 80)
    ->toFile('example.webp');
```

```
use Le\ImgMan\{ImgMan, ImageFormat};

new ImgMan()
    ->fromString($rawImageBytes)
    ->output(ImageFormat::png, 80)
    ->toFile('example.png');
```

# Notice

This library hasn't yet been fully tested and is to be used at your own responsibility.
Any feedback and improvement suggestions are appreciated!