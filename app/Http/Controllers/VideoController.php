<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage; 
use App\Models\Video;

class VideoController extends Controller {
    public function show_all() {
        $videos = Video::all();
        return response()->json($videos);
    }
    
    public function show($id) {
        $video = Video::find($id);
        if (!$video) {
            return response()->json(['message' => 'Video not found'], 404);
        }
        return response()->json([
            'id' => $video->id,
            'video_name' => $video->video_name,
            'video_url' => $video->video_url
        ]);
    }
    public function upload(Request $request) {
        Log::info('Request data:', $request->all());
        $request->validate([
            'video' => 'required|file|mimes:mp4,mov,avi,flv',
            'video_name' => 'required|string|max:255'
        ]);
        // Handle the video file upload to Amazon S3
        if ($request->hasFile('video')) {
            $file = $request->file('video');
            Log::info("Is the uploaded file valid?: " . $file->isValid());
            $filename = $request->video_name . '-' . time() . '.' . $file->getClientOriginalExtension();
            Log::info("Attempting to upload file with name: $filename");
            try {
                $path = Storage::disk('s3')->putFileAs('', $file, $filename, 'public');
                Log::info("File uploaded to S3 at path: $path");
                if (empty($path)) {
                    throw new Exception("Upload failed, no path returned.");
                }
                // Create video URL
                $videoUrl = Storage::disk('s3')->url($path);
                // Store new video details in the database
                $video = new Video();
                $video->video_name = $request->video_name;
                $video->video_url = $videoUrl;
                $video->save();
                return response()->json([
                    'id' => $video->id,
                    'video_name' => $video->video_name,
                    'video_url' => $video->video_url
                ], 201);
            } catch (Exception $e) {
                Log::error('Failed to upload video to S3: ' . $e->getMessage());
                return response()->json(['message' => 'Failed to upload video to S3: ' . $e->getMessage()], 500);
            }
        } else {
            return response()->json(['message' => 'No video file found in the request'], 400);
        }
    }
    public function delete($id) {
        $video = Video::find($id);
        if (!$video) {
            return response()->json(['message' => 'Video not found'], 404);
        }

        // Delete the video file from Amazon S3
        $path = parse_url($video->video_url, PHP_URL_PATH);
            $filename = basename($path);
        Storage::disk('s3')->delete($filename);

        // Delete the video record from the database
        $video->delete();

        return response()->json(['message' => 'Video deleted successfully'], 200);
    }
}