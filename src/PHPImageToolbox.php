<?php namespace Carimus\PHPImageToolbox;

/**
 * Class PHPImageToolbox
 *
 * Tools for image manipulation.
 *
 * @package Carimus\PHPImageToolbox
 */
class PHPImageToolbox
{
    /**
     * Determine if the necessary extensions are available.
     * @return bool
     */
    public static function supported()
    {
        return class_exists('\Imagick');
    }

    /**
     * Require extensions to be installed, throwing an exception if not.
     *
     * @throws \Exception
     */
    public static function ensureSupported()
    {
        if (!static::supported()) {
            throw new \Exception(
                'Imagick/imagemagick is not available. Please see ' .
                'https://secure.php.net/manual/en/book.imagick.php to install.'
            );
        }
    }

    /**
     * Will fade one image to another image from top to bottom according to the given parameters.
     *
     * This will create new images and not modify the existing ones.
     *
     * @param \Imagick $topImage The image to show at the top.
     * @param \Imagick $bottomImage The image to fade to at the bottom.
     * @param int|float $fadeHeight The height of the linear fade. The smaller this number, the
     *      faster the fade appears to happen.
     * @param int|float $fadeOffset The offset of the fade from the top of the image.
     *
     * @return \Imagick The final faded image.
     * @throws \ImagickException
     */
    public static function fadeTo(\Imagick $topImage, \Imagick $bottomImage, $fadeHeight, $fadeOffset = 0)
    {
        // Clone the images
        $fadeLayer = clone $topImage;
        $finalImage = clone $bottomImage;

        // Crop the fade layer to the region that will be faded
        // This is the full width of the image starting from the top edge and extending
        // down ($fadeHeight + $fadeOffset) pixels.
        $width = $finalImage->getImageWidth();
        $fadeHeight = intval($fadeHeight);
        $fadeLayer->cropImage($width, $fadeHeight + $fadeOffset, 0, 0);

        // Generate a mask layer from a gradient
        $gradient = new \Imagick();
        $gradient->newPseudoImage($width, $fadeHeight, 'gradient:#ffffff-#000000');
        $gradientMask = new \Imagick();
        $gradientMask->newImage($width, $fadeHeight, "#000000", "jpg");
        $gradientMask->compositeImage($gradient, \Imagick::COMPOSITE_COPYOPACITY, 0, 0);

        // Apply the gradient mask to the fade layer
        $fadeLayer->compositeImage($gradientMask, \Imagick::COMPOSITE_COPYOPACITY, 0, $fadeOffset);

        // Layer the fade layer on top of the final image.
        $finalImage->compositeImage($fadeLayer, \Imagick::COMPOSITE_DEFAULT, 0, 0);

        // Cleanup just in case
        unset($fadeLayer);
        unset($gradientMask);
        unset($gradient);

        return $finalImage;
    }

    /**
     * Fade an image to transparent from top to bottom.
     *
     * @param \Imagick $image The image to fade.
     * @param int|float $fadeHeight The height of the linear fade. The smaller this number, the
     *      faster the fade appears to happen.
     * @param int|float $fadeOffset The offset of the fade from the top of the image.
     *
     * @uses \Carimus\PHPImageToolbox\PHPImageToolbox::fadeTo()
     *
     * @return \Imagick The faded image.
     * @throws \ImagickException
     */
    public static function fadeToTransparent(\Imagick $image, $fadeHeight, $fadeOffset = 0)
    {
        // Generate a transparent image to fade to
        $to = new \Imagick();
        $to->newImage($image->getImageWidth(), $image->getImageHeight(), 'transparent', 'png');
        $finalImage = static::fadeTo($image, $to, $fadeHeight, $fadeOffset);

        // Cleanup just in case
        unset($to);

        return $finalImage;
    }

    /**
     * Fade an image to a blurred version of itself from top to bottom
     *
     * @param \Imagick $image The image to fade and blur.
     * @param float $blurRadius The blur radius
     * @param float $blurSigma The blur standard deviation
     * @param int|float $fadeHeight The height of the linear fade. The smaller this number, the
     *      faster the fade appears to happen.
     * @param int|float $fadeOffset The offset of the fade from the top of the image.
     *
     * @uses \Carimus\PHPImageToolbox\PHPImageToolbox::fadeTo()
     * @uses \Imagick::blurImage()
     *
     * @return \Imagick The blurred and faded image.
     * @throws \ImagickException
     */
    public static function fadeToBlur(\Imagick $image, $blurRadius, $blurSigma, $fadeHeight, $fadeOffset = 0)
    {
        // Copy the provided image and blur it
        $blurredImage = clone $image;
        $blurredImage->setImageBackgroundColor("transparent");
        $blurredImage->setImageFormat("png");
        $blurredImage->blurImage($blurRadius, $blurSigma);

        // Fade the original image to its blurred form
        $finalImage = static::fadeTo($image, $blurredImage, $fadeHeight, $fadeOffset);

        // Cleanup just in case
        unset($blurredImage);

        return $finalImage;
    }

    /**
     * Fade an image to a blurred version of itself and fade it also to fully transparent from
     * top to bottom.
     *
     * @param \Imagick $image The image to fade and blur
     * @param float $blurRadius The blur radius
     * @param float $blurSigma The blur standard deviation
     * @param int|float $blurFadeHeight The height of the linear fade for blurring. The smaller this
     *      number, the faster the fade appears to happen.
     * @param int|float $blurFadeOffset The offset of the fade for blurring from the top of the image.
     * @param int $transparentFadeHeight The height of the linear fade to transparency. The smaller this
     *      number, the faster the fade appears to happen.
     * @param int $transparentFadeOffset The offset of the fade to transparency from the top of the image.
     *
     * @uses \Carimus\PHPImageToolbox\PHPImageToolbox::fadeToBlur()
     * @uses \Carimus\PHPImageToolbox\PHPImageToolbox::fadeToTransparent()
     *
     * @return \Imagick The image that has been faded to a blur and then faded to transparent
     * @throws \ImagickException
     */
    public static function fadeToTransparentBlur(
        \Imagick $image,
        $blurRadius,
        $blurSigma,
        $blurFadeHeight,
        $blurFadeOffset = 0,
        $transparentFadeHeight = 0,
        $transparentFadeOffset = 0
    )
    {
        // Fade it to a blur and then fade THAT to transparent
        $blurImage = static::fadeToBlur($image, $blurRadius, $blurSigma, $blurFadeHeight, $blurFadeOffset);
        $finalImage = static::fadeToTransparent($blurImage, $transparentFadeHeight, $transparentFadeOffset);

        // Cleanup just in case
        unset($blurImage);

        return $finalImage;
    }

    /**
     * Take a long string of text and wrap it, capping the number of lines (determined by the $break
     * param) to a certain number ($maxLines).
     *
     * @param string $text The text to wrap
     * @param int $lineCharacterLimit The number of characters to limit each line to.
     * @param string $break The string used to delimit lines.
     * @param bool $cut Whether or not to cut off long words that exceed $lineCharacterLimit
     * @param null $maxLines The maximum number of lines to allow.
     *
     * @uses \wordwrap()
     *
     * @return string The text properly wrapped and capped at $maxLines lines.
     */
    public static function wordWrapWithMaxLines(
        $text,
        $lineCharacterLimit,
        $break = "\n",
        $cut = true,
        $maxLines = null
    )
    {
        // Wrap the text traditionally
        $wrappedText = wordwrap($text, $lineCharacterLimit, $break, $cut);
        // Get the number of lines by assuming $break is the line delimiter.
        $lines = explode($break, $wrappedText);

        if (count($lines) > $maxLines) {
            // If there are too many lines, slice out $maxLines lines and join them with the delimiter
            return implode($break, array_slice($lines, 0, $maxLines));
        } else {
            // If there aren't too many lines, return the traditionally wrapped text.
            return $wrappedText;
        }
    }

    /**
     * Intelligently generate an image containing text of an arbitrary length that is automatically
     * wrapped to the bounds of the image with optional padding.
     *
     * @param int|float $width The width of the image to generate.
     * @param string $text The text to place and wrap in the image.
     * @param array $options <p>
     * Options for how the final image is generated:
     * <ul>
     *      <li><code>backgroundColor</code>: The background color of the image</li>
     *      <li><code>format</code>: The format (e.g. <code>"png"</code> of the image)</li>
     *      <li><code>horizontalPadding</code>: The number of pixels to pad right and left sides with.</li>
     *      <li><code>verticalPadding</code>: The number of pixels to pad top and bottom sides with.</li>
     *      <li><code>maxLines</code>: The maximum number of lines to keep in the image text.</li>
     * </ul>
     * </p>
     * @param array $textSettings <p>
     * Pass any settings that are available to be set on an
     * <code>\ImagickDraw</code> instance. Any setting that has a "setter" function
     * can be used, e.g. <code>['fillColor' => '#CCCCCC']</code> will invoke
     * <code>$imagickDrawInstance->setFillColor('#CCCCCC')</code>.
     * </p>
     *
     * @see \Imagick
     * @see \ImagickDraw
     * @uses \Carimus\PHPImageToolbox\PHPImageToolbox::wordWrapWithMaxLines()
     *
     *
     * @return \Imagick
     * @throws \ImagickException
     */
    public static function generateTextImage($width, $text, $options = [], $textSettings = [])
    {
        $optionsDefaults = [
            'backgroundColor'   => '#FFFFFF',
            'format'            => 'png',
            'horizontalPadding' => 50,
            'verticalPadding'   => 20,
            'maxLines'          => 10,
        ];

        $options = array_merge(
            $optionsDefaults,
            $options
        );

        $textSettingsDefaults = [
            'fillColor'     => '#000000',
            'font'          => 'Arial',
            'fontSize'      => 14,
            'textAntialias' => true,
        ];

        $textSettings = array_merge(
            $textSettingsDefaults,
            $textSettings
        );

        // Create a new image instance that will eventually be the final image.
        // We create it now so that we can use it's `queryFontMetrics` faculties
        // to help with text wrapping and height calculation.
        $image = new \Imagick();

        // For each setting passed in $textSettings we want to call the setter
        // function for it on the text's `ImagickDraw` instance.
        $drawnText = new \ImagickDraw();
        foreach ($textSettings as $textSettingName => $textSettingValue) {
            $textSetMethodName = 'set' . ucfirst($textSettingName);
            $drawnText->{$textSetMethodName}($textSettingValue);
        }

        // The safe width to render text is within the padded area of the image.
        $safeWidth = $width - ($options['horizontalPadding'] * 2);
        // Make a safe guess as to the max number of characters to wrap text at.
        // This should be greater than actually expected since it will be slowly
        // decreased until the text fits in the safe ares.
        // TODO Optimize algorithm to also increase the limit if its too small to find the
        // sweet spot. Or find a library that has this algorithm already implemented.
        $lineCharacterLimit = $safeWidth / 5;

        do {
            // Generate the wrapped text
            $wrappedText = static::wordWrapWithMaxLines(
                $text,
                $lineCharacterLimit,
                "\n",
                true,
                $options['maxLines']
            );
            // Calculate its geometry
            $textMetrics = $image->queryFontMetrics($drawnText, $wrappedText);
            $textWidth = $textMetrics['textWidth'];
            $textHeight = $textMetrics['textHeight'];
            // Decrease the number of characters per line to try next time.
            $lineCharacterLimit--;
            // Repeat if the text takes up too much space
        } while ($textWidth > $safeWidth);

        // Calculate the height of the final image with vertical padding.
        $imageHeight = $textHeight + ($options['verticalPadding'] * 2);

        // Create the canvas for the final image.
        $image->newImage($width, $imageHeight, $options['backgroundColor'], $options['format']);

        // Draw the wrapped text on the final image.
        $drawnText->annotation(
            $options['horizontalPadding'],
            $drawnText->getFontSize() + $options['verticalPadding'],
            $wrappedText
        );

        // Draw the text onto the canvas for the final image
        $image->drawImage($drawnText);

        // Clean up just in case
        unset($drawnText);

        return $image;
    }
}