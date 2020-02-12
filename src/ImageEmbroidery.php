<?php

namespace redrum0x\embroidery;

use SimpleXMLElement;

class ImageEmbroidery
{
    /**
     * @param PesFile $embroidery
     * @param bool $base
     * @param int $scale_post
     * @param bool $scale_pre
     */
    public function imageEmbroidery(PesFile $embroidery, bool $base = false, int $scale_post = 1, bool $scale_pre = false)
    {
        $im = $this->embroidery2image($embroidery, $scale_post, $scale_pre);
        if ($base) {
            // Save png
            imagepng($im, $base . '.png');
            // Optimize png
            exec('optipng ' . escapeshellcmd($base . '.png'));
            // Save svg
            $svg = $this->embroidery2svg($embroidery, $scale_post);
            file_put_contents($base . '.svg', $svg);
        } else {
            // Output image
            header('Content-type: image/png');
            imagepng($im);
        }
        imagedestroy($im);
    }

    /**
     * @param PesFile $embroidery
     * @return array
     */
    public function getEmbroideryInformation(PesFile $embroidery): array
    {
        $info = array(
            'width' => $embroidery->imageWidth,
            'height' => $embroidery->imageHeight,
            'stitches' => 0,
            'colors_pes' => array(),
        );
        foreach ($embroidery->blocks as $block) {
            $info['stitches'] += $block->stitchesTotal;
            $info['colors_pes'][] = $block->colorIndex;
        }
        return ($info);
    }

    /**
     * @param PesFile $embroidery
     * @param int $scale_post
     * @param bool $scale_pre
     * @param int $thickness
     * @return false|resource
     */
    public function embroidery2image(PesFile $embroidery, int $scale_post = 1, bool $scale_pre = false, int $thickness = 1)
    {
        // Create image
        $im = imagecreatetruecolor(ceil($embroidery->imageWidth * $scale_post), ceil($embroidery->imageHeight * $scale_post));
        imagesavealpha($im, true);
        imagealphablending($im, false);
        $color = imagecolorallocatealpha($im, 255, 255, 255, 127);
        imagefill($im, 0, 0, $color);
        imagesetthickness($im, $thickness);

        // Draw stitches
        foreach ($embroidery->blocks as $block) {
            $color = imagecolorallocate($im, $block->color->r, $block->color->g, $block->color->b);
            $x = false;
            foreach ($block->stitches as $stitch) {
                if ($x !== false) {
                    imageline($im,
                        ($x - $embroidery->min->x) * $scale_post,
                        ($y - $embroidery->min->y) * $scale_post,
                        ($stitch->x - $embroidery->min->x) * $scale_post,
                        ($stitch->y - $embroidery->min->y) * $scale_post,
                        $color);
                }
                $x = $stitch->x;
                $y = $stitch->y;
            }
        }

        // Scale finished image
        if ($scale_pre) {
            $im2 = imagecreatetruecolor($embroidery->imageWidth * $scale_post * $scale_pre, $embroidery->imageHeight * $scale_post * $scale_pre);
            imagesavealpha($im2, true);
            imagealphablending($im2, false);
            imagecopyresized($im2, $im, 0, 0, 0, 0, $embroidery->imageWidth * $scale_post * $scale_pre, $embroidery->imageHeight * $scale_post * $scale_pre, $embroidery->imageWidth * $scale_post, $embroidery->imageHeight * $scale_post);
            imagedestroy($im);
            $im = $im2;
        }

        return ($im);
    }

    /**
     * @param PesFile $embroidery
     * @param int $scale
     * @param int $thickness
     * @return mixed
     */
    public function embroidery2svg(PesFile $embroidery, int $scale = 1, int $thickness = 1)
    {
        // header('Content-Type: image/svg+xml');
        $xml = new SimpleXMLElement('<svg />');
        $xml->addAttribute('xmlns', 'http://www.w3.org/2000/svg');
        $xml->addAttribute('xmlns:xlink', 'http://www.w3.org/1999/xlink');
        $xml->addAttribute('xmlns:ev', 'http://www.w3.org/2001/xml-events');
        $xml->addAttribute('version', '1.1');
        $xml->addAttribute('baseProfile', 'full');
        $xml->addAttribute('width', $embroidery->imageWidth * $scale);
        $xml->addAttribute('height', $embroidery->imageHeight * $scale);

        foreach ($embroidery->blocks as $block) {
            $line = $xml->addChild('path');
            $line->addAttribute('stroke', $this->rgb2html($block->color->r, $block->color->g, $block->color->b));
            $line->addAttribute('fill', 'none');
            $line->addAttribute('stroke-width', $thickness);
            $points = '';
            foreach ($block->stitches as $stitch) {
                $points .= ($points ? ' L ' : 'M ') . (($stitch->x - $embroidery->min->x) * $scale) . ' ' . (($stitch->y - $embroidery->min->y) * $scale);
            }
            $line->addAttribute('d', $points);
        }

        return ($xml->asXML());
    }

    /**
     * @param PesFile $embroidery
     * @param string $path
     * @param int $scale_post
     * @param bool $scale_pre
     * @param int $thickness
     * @return bool
     */
    public function embroidery2Jpg(PesFile $embroidery, string $path = null, int $scale_post = 1, bool $scale_pre = false, int $thickness = 1): bool
    {
        $im = $this->embroidery2image($embroidery, $scale_post, $scale_pre, $thickness);
        if($path === null) {
            header('Content-type: image/jpeg');
        }
        return imagejpeg($im, $path, 100);
    }


    /**
     * @param PesFile $embroidery
     * @param string $path
     * @param int $scale_post
     * @param bool $scale_pre
     * @param int $thickness
     * @return bool
     */
    public function embroidery2Png(PesFile $embroidery, string $path = null, int $scale_post = 1, bool $scale_pre = false, int $thickness = 1): bool
    {
        $im = $this->embroidery2image($embroidery, $scale_post, $scale_pre, $thickness);
        if($path === null) {
            header('Content-type: image/png');
        }
        return imagepng($im, $path, 100);
    }

    /**
     * @param int $r
     * @param int $g
     * @param int $b
     * @return string
     */
    private function rgb2html(int $r, int $g, int $b): string
    {
        return ('#' . substr('0' . dechex($r), -2) . substr('0' . dechex($g), -2) . substr('0' . dechex($b), -2));
    }
}