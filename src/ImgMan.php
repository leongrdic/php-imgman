<?php namespace Le\ImgMan;

use GdImage;
use Throwable, Exception;

class ImgMan
{

    private string $string;
    private string $dataUrl;
    private string $filename;

    private array $exif;

    private ImageFormat $outputFormat;
    private ?int $outputQuality;

    /**
     * @param GdImage|null $image optionally provide an image object to be
     *                             manipulated with instead of using the from methods
     */
    public function __construct(
        private ?GdImage $image = null
    )
    {
    }

    /**
     * @param string $string raw image byte stream
     * @return $this a new instance
     */
    public function fromString(string $string): static
    {
        $instance = new static;
        $instance->string = $string;
        return $instance;
    }

    /**
     * @param string $dataUrl data URL or data URI that can be provided
     *                        by FileReader method readAsDataURL in Javascript
     * @return $this a new instance
     */
    public function fromDataUrl(string $dataUrl): static
    {
        $instance = new static;
        $instance->dataUrl = $dataUrl;
        return $instance;

    }

    /**
     * @param string $filename relative path to the image file
     * @return $this a new instance
     */
    public function fromFile(string $filename): static
    {
        $instance = new static;
        $instance->filename = $filename;
        return $instance;
    }

    /**
     * reads exif data (if it exists) from the provided source
     * if used, this method must be called before any manipulation methods
     *
     * @throws Exception if object is not initialized or has already been manipulated
     * @return $this
     */
    public function cacheExif(): static
    {
        if(!isset($this->dataUrl) && !isset($this->filename) && !isset($this->string))
            throw new Exception('original source missing');

        $prefix = 'data://image/jpeg;base64,'; // because only jpegs can have exif, right?

        try {
            $this->exif = match(true) {
                isset($this->dataUrl) =>    exif_read_data($this->dataUrl),
                isset($this->filename) =>   exif_read_data($this->filename),
                isset($this->string) =>     exif_read_data($prefix . base64_encode($this->string))
            };
        }
        catch(Throwable){ $this->exif = []; }

        return $this;
    }

    /**
     * rotates the image to match the exif data (if existent or cached)
     *
     * recommended way to use is after downscaling the image so less memory usage occurs
     * to achieve this use cacheExif() method directly after providing the image source
     *
     * @throws Exception if object is not initialized, use one of the "from" methods first
     *                   or if the image format is not supported or damaged
     * @return $this
     */
    public function rotateFromExif(): static
    {
        if(!isset($this->exif)) $this->cacheExif();
        if(empty($this->exif['Orientation'])) return $this;

        $image = match($this->exif['Orientation']){
            3, 4 =>     imagerotate($this->getImage(), 180, 0),
            5, 6 =>     imagerotate($this->getImage(), 270, 0),
            7, 8 =>     imagerotate($this->getImage(), 90, 0),
            default =>  $this->getImage()
        };

        if(in_array($this->exif['Orientation'], [4, 5, 7]))
            imageflip($image, IMG_FLIP_HORIZONTAL);

        $this->image = $image;
        return $this;
    }


    /**
     * downscales the image while preserving aspect ratio
     *
     * if image is smaller than provided max dimensions, no downscaling will occur
     *
     * @param int $maxWidth         new image width
     * @param int|null $maxHeight   new image height, optional: will be equal to width if not provided
     * @throws Exception if object is not initialized, use one of the "from" methods first
     *                   or if the image format is not supported or damaged
     * @return $this
     */
    public function downscale(int $maxWidth, ?int $maxHeight = null): static
    {
        if($maxHeight === null) $maxHeight = $maxWidth;

        $this->image = $this->getImage();
        $x = imagesx($this->image);
        $y = imagesy($this->image);
        $ratio = $x / $y;

        if($x <= $maxWidth && $y <= $maxHeight)
            return $this; // image is already within limits

        if( $ratio > 1 ) { // landscape
            $width = $maxWidth;
            $height = $width/$ratio;
        }
        else { // portrait
            $height = $maxHeight;
            $width = $height*$ratio;
        }

        $scaledImage = imagecreatetruecolor($width, $height);
        imagecopyresampled($scaledImage, $this->image, 0, 0, 0, 0, $width, $height, $x, $y);
        $this->image = $scaledImage;

        return $this;
    }

    /**
     * @param ImageFormat $format   specify the output format that will be used
     * @param int|null $quality     this parameter will be passed to the image____() function's quality parameter
     * @return $this
     */
    public function output(ImageFormat $format, ?int $quality = null): static
    {
        $this->outputFormat = $format;
        $this->outputQuality = $quality;

        return $this;
    }

    /**
     * @param string|null $filename image will be written to this file;
     *                              if you wish to overwrite the existing image,
     *                              leave this parameter out (only if initialized using
     *                              the fromFile() method)
     * @throws Exception if no output method was specified using the output() method
     */
    public function toFile(?string $filename = null): void
    {
        if(is_null($filename)) $filename = $this->filename;
        $this->toFormat($filename);
    }

    /**
     * @return string raw image bytes
     * @throws Exception if no output method was specified using the output() method
     *                   or the image was not initialized, or is not supported or damaged
     */
    public function toString(): string
    {
        return $this->toFormat();
    }

    /**
     * @return string data URL or data URI
     * @throws Exception if no output method was specified using the output() method
     *                   or the image was not initialized, or is not supported or damaged
     */
    public function toDataUrl(): string
    {
        $prefix = 'data://' . $this->outputFormat->value . ';base64,';
        return $prefix . base64_encode($this->toFormat());
    }

    /**
     * @throws Exception
     */
    private function getImage(): GdImage
    {
        if(isset($this->image)) return $this->image;

        if(!isset($this->dataUrl) && !isset($this->filename) && !isset($this->string))
            throw new Exception('object not initialized');


        if (isset($this->string))
            $image = @imagecreatefromstring($this->string);
        else
        if (isset($this->dataUrl))
            $image = @imagecreatefromstring(
                base64_decode(
                    explode(',', $this->dataUrl, 2)[1]
                )
            );
        else {
            $image = match (getimagesize($this->filename)['mime']) {
                'image/bmp' => @imagecreatefrombmp($this->filename),
                'image/gif' => @imagecreatefromgif($this->filename),
                'image/png' => @imagecreatefrompng($this->filename),
                'image/jpeg' => @imagecreatefromjpeg($this->filename),
                'image/webp' => @imagecreatefromwebp($this->filename),
                default => throw new Exception('not a supported image file extension')
            };
        }

        if($image === false)
            throw new Exception('unsuccessful image loading - could be damaged or an unsupported format');

        unset($this->string);
        unset($this->dataUrl);

        return $image;
    }

    /**
     * @throws Exception
     */
    private function toFormat(string $filename = null): string
    {
        if(!isset($this->outputFormat)) throw new Exception('no output format specified (output() method)');

        ob_start();
        match($this->outputFormat){
            ImageFormat::jpeg =>    imagejpeg($this->getImage(), $filename, $this->outputQuality),
            ImageFormat::png =>     imagepng($this->getImage(), $filename, $this->outputQuality ?? -1),
            ImageFormat::webp =>    imagewebp($this->getImage(), $filename, $this->outputQuality)
        };
        return ob_get_clean();
    }



}