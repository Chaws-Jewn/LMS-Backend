<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Storage; 
use FFI\Exception;

class ImageController extends Controller
{
    // Get an image BLOB format
    public function get(string $url) {
        try {
            // Get image data
            $imageData = file_get_contents($url);
    
            if ($imageData === false) {
                throw new Exception('Failed to retrieve image from URL.');
            }
    
            // Get MIME type from the URL (optional)
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_buffer($finfo, $imageData);
            finfo_close($finfo);
    
            // Return image data and MIME type as a response
            return response($imageData, 200)
                    ->header('Content-Type', $mimeType)
                    ->header('Content-Disposition', 'attachment; filename="image.' . pathinfo($url, PATHINFO_EXTENSION) . '"');
    
        } catch (Exception $e) {
            // Handle exceptions
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // Deletes an Image
    public function delete(string $path) {
        // Check if the directory exists and delete it
        if (Storage::exists($path)) {

            Storage::delete($path);
            return response()->json(['Status' => "Image successfully deleted."], 200);
        } else {
            return response()->json(['Status' => "Directory not found."], 404);
        }
    }

    // Deletes all Images
    public function deleteAll(string $type) {
        
        $directory = 'images/';
        if($type == 'books')
            $directory = $directory . 'books';
        else if ($type == 'projects')
            $directory = $directory . 'projects';
        else
            return response()->json(['Error' => 'Type Error: There is no type ' . $type]);        

        // Check if the directory exists and delete it
        if (Storage::exists($directory)) {

            Storage::deleteDirectory($directory);
            return response()->json(['Status' => "All files within the directory deleted successfully."], 200);
        } else {
            return response()->json(['Status' => "Directory not found."], 404);
        }
    }
}
