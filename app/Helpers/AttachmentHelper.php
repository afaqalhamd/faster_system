<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;

class AttachmentHelper
{
    /**
     * Store ticket attachment
     *
     * @param UploadedFile $file
     * @param int $ticketId
     * @return string|false Path to stored file or false on failure
     */
    public static function storeTicketAttachment(UploadedFile $file, int $ticketId)
    {
        try {
            $directory = "support/tickets/{$ticketId}";
            
            // Ensure directory exists
            if (!Storage::disk('public')->exists($directory)) {
                Storage::disk('public')->makeDirectory($directory, 0755, true);
            }
            
            // Store file
            return $file->store($directory, 'public');
        } catch (\Exception $e) {
            \Log::error("Failed to store ticket attachment: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Store message attachment
     *
     * @param UploadedFile $file
     * @param int $ticketId
     * @return string|false Path to stored file or false on failure
     */
    public static function storeMessageAttachment(UploadedFile $file, int $ticketId)
    {
        try {
            $directory = "support/tickets/{$ticketId}/messages";
            
            // Ensure directory exists
            if (!Storage::disk('public')->exists($directory)) {
                Storage::disk('public')->makeDirectory($directory, 0755, true);
            }
            
            // Store file
            return $file->store($directory, 'public');
        } catch (\Exception $e) {
            \Log::error("Failed to store message attachment: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete attachment
     *
     * @param string $path
     * @return bool
     */
    public static function deleteAttachment(string $path): bool
    {
        try {
            if (Storage::disk('public')->exists($path)) {
                return Storage::disk('public')->delete($path);
            }
            return true;
        } catch (\Exception $e) {
            \Log::error("Failed to delete attachment: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get attachment URL
     *
     * @param string $path
     * @return string
     */
    public static function getAttachmentUrl(string $path): string
    {
        return Storage::disk('public')->url($path);
    }

    /**
     * Check if file is valid image
     *
     * @param UploadedFile $file
     * @return bool
     */
    public static function isImage(UploadedFile $file): bool
    {
        return in_array($file->getMimeType(), [
            'image/jpeg',
            'image/jpg',
            'image/png',
            'image/gif',
            'image/webp'
        ]);
    }

    /**
     * Get file size in human readable format
     *
     * @param int $bytes
     * @return string
     */
    public static function formatFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
}
