<?php

namespace App\Helpers;

use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MediaHelper
{
    /**

    *
     * @param Media $media
     * @param string $conversion
     * @return string
     */
    public static function getMediaUrl(Media $media, string $conversion = ''): string
    {
        // Get the relative path from the media library
        $relativePath = $media->getPathRelativeToRoot($conversion);

        // Construct the URL using the storage path
        return '/storage/' . $relativePath;
    }

    /**
     * Generate a URL for a media file with conversion that works in any environment
     *
     * @param Media $media
     * @param string $conversion
     * @return string
     */
    public static function getMediaConversionUrl(Media $media, string $conversion): string
    {
        // For conversions, we need to adjust the path
        $basePath = $media->getKey();
        $fileName = pathinfo($media->file_name, PATHINFO_FILENAME);
        $extension = pathinfo($media->file_name, PATHINFO_EXTENSION);

        // Construct the conversion file name
        $conversionFileName = $fileName . '-' . $conversion . '.' . $extension;

        // Construct the URL
        return '/storage/' . $basePath . '/conversions/' . $conversionFileName;
    }
}
