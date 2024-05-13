<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\Video;
use App\Models\User;

class VideoController extends Controller
{
    public function show_all()
    {
        $videos = Video::all();
        $response = [];
        foreach ($videos as $video) {
            $authorId = $video->author;
            $author = User::find($authorId);
            $authorUsername = $author ? $author->username : null;
            $response[] = [
                'id' => $video->id,
                'video_name' => $video->video_name,
                'video_url' => $video->video_url,
                'views' => $video->views,
                'author' => $authorUsername,
                'created_at' => $video->created_at,
                'description' => $video->description,
                'unique_code' => $video->unique_code
            ];
        }
        return response()->json($response);
    }

    public function show($unique_code)
    {
        $video = Video::where('unique_code', $unique_code)->first();
        if (!$video) {
            return response()->json(['message' => 'Video not found'], 404);
        }
        $authorId = $video->author;
        $author = User::find($authorId);
        $authorUsername = $author ? $author->username : null;
        $video->views++;
        $video->save();
        return response()->json([
            'id' => $video->id,
            'video_name' => $video->video_name,
            'video_url' => $video->video_url,
            'views' => $video->views,
            'author' => $authorUsername,
            'created_at' => $video->created_at,
            'description' => $video->description,
            'unique_code' => $video->unique_code
        ]);
    }

    public function generateUniqueCode($length = 5)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[random_int(0, strlen($characters) - 1)];
        }
        return $randomString;
    }

    public function upload(Request $request)
    {
        Log::info('Request data:', $request->all());
        $request->validate([
            'video' => 'required|file|mimes:mp4,mov,avi,flv',
            'video_name' => 'required|string|max:255'
        ]);
        if (!auth()->check()) {
            return response()->json(['message' => 'Authentication required'], 401);
        }
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
                $videoUrl = Storage::disk('s3')->url($path);
                $video = new Video();
                $video->video_name = $request->video_name;
                $video->video_url = $videoUrl;
                do {
                    $uniqueCode = $this->generateUniqueCode();
                } while (Video::where('unique_code', $uniqueCode)->exists());
                $video->unique_code = $uniqueCode;
                $video->description = $request->description;
                $video->save();
                return response()->json([
                    'id' => $video->id,
                    'video_name' => $video->video_name,
                    'video_url' => $video->video_url,
                    'unique_code' => $video->unique_code
                ], 201);
            } catch (Exception $e) {
                Log::error('Failed to upload video to S3: ' . $e->getMessage());
                return response()->json(['message' => 'Failed to upload video to S3: ' . $e->getMessage()], 500);
            }
        } else {
            return response()->json(['message' => 'No video file found in the request'], 400);
        }
    }

    public function delete($id)
    {
        $video = Video::find($id);
        if (!$video) {
            return response()->json(['message' => 'Video not found'], 404);
        }
        if (auth()->id() != $video->author) {
            return response()->json(['message' => 'Unauthorized to perform this action'], 403);
        }
        $path = parse_url($video->video_url, PHP_URL_PATH);
        $filename = basename($path);
        Storage::disk('s3')->delete($filename);
        $video->delete();
        return response()->json(['message' => 'Video deleted successfully'], 200);
    }
}
