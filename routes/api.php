<?php

use Carbon\Carbon;
use App\Models\User;
use Illuminate\Support\Str;
use App\Models\InsOmvMetric;
use App\Models\InsOmvRecipe;
use Illuminate\Http\Request;
use App\Models\InsOmvCapture;
use App\Models\InsRubberBatch;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Models\InvTag;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

Route::get('/ins-rubber-batches/recents', function() {
    $batches = InsRubberBatch::latest('updated_at')->limit(19)->get();
    return response()->json($batches);
});

Route::get('/ins-rubber-batches/{code}', function($code) {
    $batch = InsRubberBatch::where('code', $code)->latest('updated_at')->first();
    return response()->json($batch);
});

Route::get('/inv-tags', function(Request $request) {
    $q = trim($request['q']);
    $hints = [];
    if ($q) {
        $hints = InvTag::where('name', 'LIKE', '%' . $q . '%')
            ->orderBy('name')
            ->limit(100)
            ->get()
            ->pluck('name')
            ->toArray();
    }

    return response()->json($hints);
});

Route::get('/time', function() {
    $currentTime = Carbon::now('UTC');
    return response()->json([
        'timestamp' => $currentTime->timestamp,
        'formatted' => $currentTime->toIso8601String(),
    ]);
});

Route::get('/omv-recipes', function() {
    $recipes = InsOmvRecipe::all()->map(function($recipe) {
        // Parse the steps JSON field
        $steps = json_decode($recipe->steps);

        // Parse the capture_points JSON field
        $capture_points = json_decode($recipe->capture_points);

        return [
            'id' => $recipe->id,
            'type' => $recipe->type,
            'name' => $recipe->name,
            'capture_points' => $capture_points,
            'steps' => $steps,
        ];
    });

    return response()->json($recipes);
});

Route::post('/omv-metric', function (Request $request) {
    $validator = Validator::make($request->all(), [
        'recipe_id'         => 'required|exists:ins_omv_recipes,id',
        'code'              => 'nullable|string|max:20',
        'model'             => 'nullable|string|max:30',    
        'color'             => 'nullable|string|max:20',
        'mcs'               => 'nullable|string|max:10',
        'line'              => 'required|integer|min:1|max:99',
        'team'              => 'required|in:A,B,C',
        'user_1_emp_id'     => 'required|exists:users,emp_id',
        'user_2_emp_id'     => 'nullable|string',
        'eval'              => 'required|in:too_soon,on_time,too_late,on_time_manual',
        'start_at'          => 'required|date_format:Y-m-d H:i:s',
        'end_at'            => 'required|date_format:Y-m-d H:i:s',
        'images'            => 'nullable|array',
        'images.*.step_index' => 'required|integer',
        'images.*.taken_at' => 'required|numeric',
        'images.*.image'    => 'required|string',
        'amps'              => 'nullable|array',
        'amps.*.taken_at'   => 'required|numeric',
        'amps.*.value'      => 'required|integer',
        'composition'       => 'required|size:7|array',
        'composition.*'     => 'nullable|min:0|max:5000|numeric',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'status'        => 'invalid',
            'msg'           => $validator->errors()->all(),
        ], 400);
    } 
    
    $validated = $validator->validated();
    $user1 = User::where('emp_id', $validated['user_1_emp_id'])->first();
    $user2 = User::where('emp_id', $validated['user_2_emp_id'])->first();

    $errors = [];

    if (!$user1) {
        $errors[] = "The emp_id '{$validated['user_1_emp_id']}' on user_1_emp_id does not exist.";
    }

    $isExists = InsOmvMetric::where('line', $validated['line'])->where('start_at', $validated['start_at'])->exists();

    if($isExists) {
        $errors[] = "A metric for line '{$validated['line']}' already exists at '{$validated['start_at']}'.";
    }

    if (!empty($errors)) {
        return response()->json([
            'status' => 'invalid',
            'msg' => $errors,
        ], 400);
    }

    $code   = strtoupper(trim($validated['code']));
    $model  = strtoupper(trim($validated['model']));
    $color  = strtoupper(trim($validated['color']));
    $mcs    = strtoupper(trim($validated['mcs']));

    $batch = null;
    if ($code) {
        $batch = InsRubberBatch::updateOrCreate(
            [
                'code' => $code
            ], 
            [
                'model' => $model,
                'color' => $color,
                'mcs' => $mcs, 
                'composition' => json_encode($validated['composition']),
            ]);
    }

    $amps = $validated['amps']; 
    $filteredAmps = [];

    // limit the array if it's too big then just return empty filteredamps altogether
    if (count($validated['amps']) < 10000) {
        $maxTakenAt = null;
        // Traverse the array from the last element to the first
        for ($i = count($amps) - 1; $i >= 0; $i--) {
            $current = $amps[$i];
            if ($maxTakenAt === null || $current['taken_at'] <= $maxTakenAt) {
                $filteredAmps[] = $current;
                $maxTakenAt = $current['taken_at'];
            } else {
                // We found an increase in `taken_at`, discard everything before this point
                break;
            }
        }
    }

    $amps = array_reverse($filteredAmps);

    $voltage = 380; // Voltase
    $kwhUsage = 0;  // Total energi, diinisiasi dengan 0
    $powerFactor = 0.85;
    $calibrationFactor = 0.8;

    for ($i = 1; $i < count($amps); $i++) {
        // Hitung rerata arus per interval
        $avgCurrent = ($amps[$i]['value'] + $amps[$i - 1]['value']) / 2;

        // Hitung durasi interval lalu konversi dari detik ke jam
        $timeInterval = ($amps[$i]['taken_at'] - $amps[$i - 1]['taken_at']) / 3600; 
        
        // Hitung energi per interval
        $energy = (sqrt(3) * $avgCurrent * $voltage * $timeInterval * $powerFactor * $calibrationFactor) / 1000;
        $kwhUsage += $energy; // Jumlahkan total energi semua interval
    }
    
    $omvMetric = new InsOmvMetric();
    $omvMetric->ins_omv_recipe_id = $validated['recipe_id'];
    $omvMetric->line = $validated['line'];
    $omvMetric->team = $validated['team'];
    $omvMetric->user_1_id = $user1->id;
    $omvMetric->user_2_id = $user2->id ?? null;
    $omvMetric->eval = strtolower($validated['eval']); // converting eval to lowercase
    $omvMetric->start_at = $validated['start_at'];
    $omvMetric->end_at = $validated['end_at'];
    $omvMetric->data = json_encode(['amps' => $amps]);
    $omvMetric->ins_rubber_batch_id = $batch ? $batch->id : null;
    $omvMetric->kwh_usage = $kwhUsage;
    $omvMetric->save();
    
    $captureMessages = [];
    
    foreach ($validated['images'] as $index => $image) {
        try {
            if (!isset($image['image'])) {
                throw new Exception("Image data is missing.");
            }

            $parts = explode(',', $image['image']);
            if (count($parts) != 2) {
                throw new Exception("Invalid image format.");
            }
        
            $imageData = base64_decode($parts[1], true);
            if ($imageData === false) {
                throw new Exception("Invalid base64 encoding.");
            }

            $imageInfo = getimagesizefromstring($imageData);
            if ($imageInfo === false) {
                throw new Exception("Invalid image data.");
            }
        
            $mimeType = $imageInfo['mime'];
            $extension = explode('/', $mimeType)[1] ?? 'png'; // Default to png if mime type is unexpected
        
            $fileName = sprintf(
                '$s_%s.%s',
                $omvMetric->id,
                Str::random(6),
                $extension
            );
        
            if (!Storage::put('/public/omv-captures/'.$fileName, $imageData)) {
                throw new Exception("Failed to save image file.");
            }
        
            $omvCapture = new InsOmvCapture();
            $omvCapture->ins_omv_metric_id = $omvMetric->id;
            $omvCapture->file_name = $fileName;
            $omvCapture->taken_at = $image['taken_at'];
            $omvCapture->save();
        
        } catch (Exception $e) {
            $captureMessages[] = "Error saving capture {$index}: " . $e->getMessage();
            // You might want to log the full exception details here
            // Log::error('Image capture error: ' . $e->getMessage(), ['exception' => $e]);
        }
    }
    
    $responseMessage = 'OMV Metric saved successfully.';
    if (!empty($captureMessages)) {
        $responseMessage .= ' However, there were issues with some captures: ' . implode(', ', $captureMessages);
    }
    
    return response()->json([
        'status' => 'valid',
        'msg' => $responseMessage,
    ], 200);
});
