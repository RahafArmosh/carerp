<?php
namespace App\Imports;

use App\Models\VehicleModel;
use App\Models\Brand;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Illuminate\Support\Facades\Log;

class SubBrandImport implements ToCollection
{
    public function collection(Collection $rows)
    {
        $creatorId = \Auth::user()->creatorId();
        
        if (class_exists('\Barryvdh\Debugbar\Facades\Debugbar')) {
            \Barryvdh\Debugbar\Facades\Debugbar::disable();
        }
        if (config('debugbar.enabled')) {
            config(['debugbar.enabled' => false]);
        }
        
        $brandsCache = Brand::where('created_by', $creatorId)
            ->select('id', 'name', 'created_by')
            ->get()
            ->keyBy('id');
        
        $subBrandsCache = VehicleModel::where('created_by', $creatorId)
            ->select('id', 'name', 'brand_id', 'created_by')
            ->get()
            ->keyBy('id');
        
        foreach ($rows->skip(1) as $rowIndex => $row) {
            try {
                $rowArray = is_array($row) ? $row : $row->toArray();
                
                $subBrandId = null;
                $subBrandName = null;
                $brandIdColumnIndex = 1;
                
                if (isset($rowArray[0]) && !empty(trim($rowArray[0])) && is_numeric(trim($rowArray[0]))) {
                    $potentialId = (int) trim($rowArray[0]);
                    $existingById = $subBrandsCache->get($potentialId);
                    
                    if ($existingById) {
                        $subBrandId = $potentialId;
                        $subBrandName = isset($rowArray[1]) ? trim($rowArray[1]) : null;
                        $brandIdColumnIndex = 2;
                    } else {
                        $subBrandName = trim($rowArray[0]);
                        $brandIdColumnIndex = 1;
                    }
                } else {
                    $subBrandName = isset($rowArray[0]) ? trim($rowArray[0]) : null;
                    $brandIdColumnIndex = 1;
                }

                if (empty($subBrandName)) {
                    Log::warning('Skipping empty model name in row', [
                        'row_index' => $rowIndex + 2,
                        'row_data' => $rowArray,
                    ]);
                    continue;
                }
                
                $defaultBrandId = isset($rowArray[$brandIdColumnIndex]) ? trim($rowArray[$brandIdColumnIndex]) : null;
                
                if (empty($defaultBrandId) || !is_numeric($defaultBrandId)) {
                    Log::warning('Brand ID missing or invalid', [
                        'row_index' => $rowIndex + 2,
                        'model_name' => $subBrandName,
                        'brand_id' => $defaultBrandId
                    ]);
                    continue;
                }

                $defaultBrandId = (int) $defaultBrandId;
                
                $defaultBrand = $brandsCache->get($defaultBrandId);

                if (!$defaultBrand) {
                    Log::warning('Brand not found', [
                        'row_index' => $rowIndex + 2,
                        'model_name' => $subBrandName,
                        'brand_id' => $defaultBrandId,
                        'user_id' => $creatorId
                    ]);
                    continue;
                }

                if ($subBrandId !== null) {
                    $subBrand = $subBrandsCache->get($subBrandId);
                    
                    if (!$subBrand || $subBrand->created_by != $creatorId) {
                        Log::error('Model ID invalid or not owned by user', [
                            'sub_brand_id' => $subBrandId,
                            'row_index' => $rowIndex + 2,
                        ]);
                        continue;
                    }

                    $duplicate = VehicleModel::where('created_by', $creatorId)
                        ->where('brand_id', $defaultBrandId)
                        ->where('id', '!=', $subBrand->id)
                        ->whereNull('deleted_at')
                        ->whereRaw('LOWER(TRIM(name)) = ?', [strtolower($subBrandName)])
                        ->exists();
                    if ($duplicate) {
                        Log::warning('Sub-brand import: duplicate model name for brand, skipping update', [
                            'sub_brand_id' => $subBrandId,
                            'model_name' => $subBrandName,
                            'brand_id' => $defaultBrandId,
                            'row_index' => $rowIndex + 2,
                        ]);
                        continue;
                    }

                    $subBrand->name = $subBrandName;
                    $subBrand->brand_id = $defaultBrandId;
                    $subBrand->save();
                } else {
                    $subBrand = $subBrandsCache->first(function ($sb) use ($subBrandName, $defaultBrandId) {
                        return (int) $sb->brand_id === $defaultBrandId
                            && strtolower($sb->name) === strtolower($subBrandName);
                    });

                    if (! $subBrand) {
                        $existsInDb = VehicleModel::where('created_by', $creatorId)
                            ->where('brand_id', $defaultBrandId)
                            ->whereNull('deleted_at')
                            ->whereRaw('LOWER(TRIM(name)) = ?', [strtolower($subBrandName)])
                            ->exists();
                        if ($existsInDb) {
                            Log::warning('Sub-brand import: model name already exists for brand, skipping create', [
                                'model_name' => $subBrandName,
                                'brand_id' => $defaultBrandId,
                                'row_index' => $rowIndex + 2,
                            ]);
                            continue;
                        }

                        $subBrand = VehicleModel::create([
                            'name' => $subBrandName,
                            'brand_id' => $defaultBrandId,
                            'created_by' => $creatorId
                        ]);
                        $subBrandsCache->put($subBrand->id, $subBrand);
                    } else {
                        $subBrand->brand_id = $defaultBrandId;
                        $subBrand->save();
                    }
                }
            } catch (\Exception $e) {
                $rowArrayForLog = is_array($row) ? $row : (method_exists($row, 'toArray') ? $row->toArray() : (array) $row);
                
                Log::error('Error importing model row', [
                    'row_index' => $rowIndex + 2,
                    'row_data' => $rowArrayForLog,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                continue;
            }
        }
    }
}
