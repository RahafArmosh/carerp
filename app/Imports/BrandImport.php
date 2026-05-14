<?php
namespace App\Imports;

use App\Models\Brand;
use App\Models\Category;
use App\Models\ProductServiceCategory;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Illuminate\Support\Facades\Log;

class BrandImport implements ToCollection
{
    public function collection(Collection $rows)
    {
        $creatorId = \Auth::user()->creatorId();
        
        // Check and fix auto_increment issue at the start of import
        $this->fixAutoIncrementIfNeeded();
        
        foreach ($rows->skip(1) as $rowIndex => $row) {
            try {
                // Convert row to array if it's a Collection
                $rowArray = is_array($row) ? $row : $row->toArray();
                
                // Check if first column is ID (for update) or Brand Name (for create)
                // Format: [ID (optional), Brand Name, Category IDs]
                $brandId = null;
                $brandName = null;
                $categoryColumnIndex = 1; // Default category is in column 2 (index 1)
                
                // Check if first column is numeric (likely an ID)
                if (isset($rowArray[0]) && !empty(trim($rowArray[0])) && is_numeric(trim($rowArray[0]))) {
                    $potentialId = (int)trim($rowArray[0]);
                    // If ID is provided and brand exists, this is an update
                    $existingBrandById = Brand::where('id', $potentialId)
                        ->where('created_by', $creatorId)
                        ->first();
                    
                    if ($existingBrandById) {
                        // First column is ID - this is an update
                        $brandId = $potentialId;
                        $brandName = isset($rowArray[1]) ? trim($rowArray[1]) : null;
                        $categoryColumnIndex = 2; // Category is in column 3 (index 2)
                    } else {
                        // First column is numeric but not a valid ID - treat as brand name
                        $brandName = trim($rowArray[0]);
                        $categoryColumnIndex = 1; // Category is in column 2 (index 1)
                    }
                } else {
                    // First column is brand name - this is a create
                    $brandName = isset($rowArray[0]) ? trim($rowArray[0]) : null;
                    $categoryColumnIndex = 1; // Category is in column 2 (index 1)
                }
                
                // Skip if brand name is missing
                if (empty($brandName) || trim($brandName) === '') {
                    Log::warning('Skipping empty brand name in row', [
                        'row_index' => $rowIndex + 2,
                        'row_data' => $rowArray,
                        'has_id' => $brandId !== null
                    ]);
                    continue;
                }
                
                // Validate brand name length
                if (strlen($brandName) > 200) {
                    Log::warning('Brand name too long, skipping', [
                        'row_index' => $rowIndex + 2,
                        'brand_name' => $brandName,
                        'length' => strlen($brandName)
                    ]);
                    continue;
                }
                
                // Get category IDs - handle empty or missing category column
                $categoryIds = [];
                if (isset($rowArray[$categoryColumnIndex]) && !empty(trim($rowArray[$categoryColumnIndex]))) {
                    $categoryIdsString = trim($rowArray[$categoryColumnIndex]);
                    $categoryIdsArray = array_map('trim', explode(',', $categoryIdsString));
                    
                    // Validate category IDs exist and belong to the creator
                    foreach ($categoryIdsArray as $categoryId) {
                        if (!empty($categoryId) && is_numeric($categoryId)) {
                            $categoryId = (int)$categoryId;
                            // Verify category exists and belongs to creator
                            $category = ProductServiceCategory::where('id', $categoryId)
                                ->where('created_by', $creatorId)
                                ->first();
                            
                            if ($category) {
                                $categoryIds[] = $categoryId;
                            } else {
                                Log::warning('Category ID not found or not owned by creator', [
                                    'row_index' => $rowIndex + 2,
                                    'category_id' => $categoryId,
                                    'creator_id' => $creatorId
                                ]);
                            }
                        }
                    }
                }
                
                // If brand ID is provided, this is an update operation
                if ($brandId !== null) {
                    $brand = Brand::find($brandId);
                    
                    if (!$brand) {
                        Log::error('Brand ID provided but brand not found', [
                            'brand_id' => $brandId,
                            'row_index' => $rowIndex + 2,
                            'creator_id' => $creatorId
                        ]);
                        continue; // Skip this row
                    }
                    
                    if ($brand->created_by != $creatorId) {
                        Log::error('Brand ID provided but user does not own this brand', [
                            'brand_id' => $brandId,
                            'row_index' => $rowIndex + 2,
                            'creator_id' => $creatorId
                        ]);
                        continue; // Skip this row
                    }

                    $duplicate = Brand::where('created_by', $creatorId)
                        ->where('id', '!=', $brand->id)
                        ->whereNull('deleted_at')
                        ->whereRaw('LOWER(TRIM(name)) = ?', [strtolower($brandName)])
                        ->exists();
                    if ($duplicate) {
                        Log::warning('Brand import: duplicate name for creator, skipping update', [
                            'brand_id' => $brandId,
                            'brand_name' => $brandName,
                            'row_index' => $rowIndex + 2,
                        ]);
                        continue;
                    }

                    // Update brand name
                    $brand->name = $brandName;
                    $brand->save();
                    
                    $isNewBrand = false;
                    
                    Log::info('Brand updated by ID during import', [
                        'brand_id' => $brand->id,
                        'brand_name' => $brandName,
                        'old_name' => $brand->getOriginal('name'),
                        'row_index' => $rowIndex + 2,
                        'creator_id' => $creatorId
                    ]);
                } else {
                    // No ID provided - check if brand already exists (case-insensitive search)
                    // Use LOWER() for case-insensitive comparison to handle "PLEASE" vs "please"
                    $brand = Brand::whereRaw('LOWER(name) = ?', [strtolower($brandName)])
                        ->where('created_by', $creatorId)
                        ->whereNull('deleted_at')
                        ->first();
                    
                    $isNewBrand = false;
                    
                    if (!$brand) {
                        // Create new brand if it doesn't exist
                        // Check if there's a record with id=0 OR if auto_increment is broken (0 or 1)
                        $zeroIdBrand = \DB::table('brands')->where('id', 0)->first();
                        $hasIdZeroIssue = $zeroIdBrand !== null;
                        
                        // Also check auto_increment value - if it's 0 or 1, we need manual insertion
                        if (!$hasIdZeroIssue) {
                            try {
                                $autoIncrementResult = \DB::select("SHOW TABLE STATUS LIKE 'brands'");
                            if (!empty($autoIncrementResult)) {
                                $autoIncrement = $autoIncrementResult[0]->Auto_increment ?? null;
                                // If auto_increment is 0 or 1, we need to use manual insertion
                                if ($autoIncrement == 0 || $autoIncrement == 1) {
                                    $maxId = \DB::table('brands')->max('id');
                                    // If max ID exists and is > 0, but auto_increment is 0/1, we have a problem
                                    if ($maxId && $maxId > 0) {
                                        $hasIdZeroIssue = true;
                                        Log::warning('Auto_increment is ' . $autoIncrement . ' but max ID is ' . $maxId . ' - using manual ID insertion', [
                                            'brand_name' => $brandName,
                                            'row_index' => $rowIndex + 2
                                        ]);
                                    }
                                }
                            }
                            } catch (\Exception $e) {
                                // If we can't check, assume we need manual insertion to be safe
                                Log::warning('Could not check auto_increment, using manual ID insertion', [
                                    'brand_name' => $brandName,
                                    'error' => $e->getMessage()
                                ]);
                                $hasIdZeroIssue = true;
                            }
                        }
                        
                        if ($hasIdZeroIssue) {
                            // Database has id=0 record - use manual ID insertion
                            Log::warning('Database has record with id=0 - using manual ID insertion', [
                                'brand_name' => $brandName,
                                'row_index' => $rowIndex + 2,
                                'creator_id' => $creatorId
                            ]);
                            
                            try {
                                // Get next available ID manually
                                // Always start from at least 1, never use 0
                                $maxId = \DB::table('brands')->where('id', '>', 0)->max('id');
                                $nextId = max(1, ($maxId && $maxId > 0 ? (int)$maxId + 1 : 1));
                                
                                // Ensure we never use 0 and check if ID is available
                                while ($nextId == 0 || \DB::table('brands')->where('id', $nextId)->exists()) {
                                    $nextId++;
                                    // Safety check - prevent infinite loop
                                    if ($nextId > 999999999) {
                                        Log::error('ID exceeded maximum, stopping manual insertion', [
                                            'brand_name' => $brandName,
                                            'row_index' => $rowIndex + 2
                                        ]);
                                        throw new \Exception('ID exceeded maximum value');
                                    }
                                }
                                
                                // Update auto_increment to next ID + 1 to prevent future id=0 issues
                                // Use DB::unprepared() to run outside of transaction context (ALTER TABLE auto-commits)
                                try {
                                    \DB::unprepared("ALTER TABLE brands AUTO_INCREMENT = " . (int)($nextId + 1));
                                    Log::info('Updated auto_increment to prevent id=0', [
                                        'new_auto_increment' => $nextId + 1
                                    ]);
                                } catch (\Exception $e) {
                                    Log::warning('Could not update auto_increment, continuing with manual ID', [
                                        'error' => $e->getMessage()
                                    ]);
                                    // Continue anyway - manual ID insertion is working
                                }
                                
                                // Insert with explicit ID
                                \DB::table('brands')->insert([
                                    'id' => $nextId,
                                    'name' => $brandName,
                                    'created_by' => $creatorId,
                                    'created_at' => now(),
                                    'updated_at' => now(),
                                ]);
                                
                                // Reload brand
                                $brand = Brand::find($nextId);
                                
                                if (!$brand) {
                                    Log::error('Failed to create brand with manual ID', [
                                        'brand_name' => $brandName,
                                        'row_index' => $rowIndex + 2,
                                        'creator_id' => $creatorId,
                                        'attempted_id' => $nextId
                                    ]);
                                    continue; // Skip this row
                                }
                                
                                $isNewBrand = true;
                                Log::info('Brand created with manual ID due to auto_increment issue', [
                                    'brand_id' => $brand->id,
                                    'brand_name' => $brandName,
                                    'row_index' => $rowIndex + 2,
                                    'creator_id' => $creatorId
                                ]);
                            } catch (\Illuminate\Database\QueryException $e) {
                                // If manual insert also fails, try to find existing brand
                                Log::error('Manual ID insertion failed, trying to find existing brand', [
                                    'brand_name' => $brandName,
                                    'row_index' => $rowIndex + 2,
                                    'error' => $e->getMessage(),
                                    'creator_id' => $creatorId
                                ]);
                                
                                // Try to find existing brand (case-insensitive)
                                $brand = Brand::whereRaw('LOWER(name) = ?', [strtolower($brandName)])
                                    ->where('created_by', $creatorId)
                                    ->first();
                                
                                if (!$brand) {
                                    Log::error('Brand not found after manual insert failure', [
                                        'brand_name' => $brandName,
                                        'row_index' => $rowIndex + 2,
                                        'creator_id' => $creatorId
                                    ]);
                                    continue; // Skip this row
                                }
                                
                                Log::info('Found existing brand after manual insert failure', [
                                    'brand_id' => $brand->id,
                                    'brand_name' => $brandName,
                                    'row_index' => $rowIndex + 2
                                ]);
                            }
                        } else {
                            // No id=0 issue - use normal Eloquent create
                            try {
                            // Use firstOrCreate which handles duplicates better
                            $brand = Brand::firstOrCreate(
                                [
                                    'name' => $brandName,
                                    'created_by' => $creatorId,
                                ],
                                [
                                    'name' => $brandName,
                                    'created_by' => $creatorId,
                                ]
                            );
                            
                            $isNewBrand = $brand->wasRecentlyCreated;
                            
                            if ($isNewBrand) {
                                Log::info('Brand created during import', [
                                    'brand_id' => $brand->id,
                                    'brand_name' => $brandName,
                                    'row_index' => $rowIndex + 2,
                                    'creator_id' => $creatorId
                                ]);
                            } else {
                                Log::info('Brand already exists (found via firstOrCreate)', [
                                    'brand_id' => $brand->id,
                                    'brand_name' => $brandName,
                                    'row_index' => $rowIndex + 2,
                                    'creator_id' => $creatorId
                                ]);
                            }
                        } catch (\Illuminate\Database\QueryException $e) {
                            // Handle duplicate entry error specifically
                            if ($e->getCode() == 23000 || strpos($e->getMessage(), 'Duplicate entry') !== false) {
                                // Try to find the existing brand (race condition - another process created it)
                                // Use case-insensitive search
                                $brand = Brand::whereRaw('LOWER(name) = ?', [strtolower($brandName)])
                                    ->where('created_by', $creatorId)
                                    ->first();
                                
                                if (!$brand) {
                                    Log::error('Duplicate entry error but brand not found', [
                                        'brand_name' => $brandName,
                                        'row_index' => $rowIndex + 2,
                                        'error' => $e->getMessage(),
                                        'creator_id' => $creatorId
                                    ]);
                                    continue; // Skip this row
                                }
                                
                                Log::warning('Duplicate entry error, using existing brand', [
                                    'brand_id' => $brand->id,
                                    'brand_name' => $brandName,
                                    'row_index' => $rowIndex + 2,
                                    'creator_id' => $creatorId
                                ]);
                            } else {
                                // Log and re-throw if it's a different error
                                Log::error('Error creating brand', [
                                    'brand_name' => $brandName,
                                    'row_index' => $rowIndex + 2,
                                    'error' => $e->getMessage(),
                                    'error_code' => $e->getCode(),
                                    'creator_id' => $creatorId
                                ]);
                                throw $e;
                                }
                            }
                        }
                    }
                }
                
                // At this point, $brand should be set (either from update by ID or create/find by name)
                if (!$brand) {
                    Log::error('Brand is null after creation/lookup, cannot update categories', [
                        'brand_name' => $brandName,
                        'brand_id' => $brandId,
                        'row_index' => $rowIndex + 2,
                        'creator_id' => $creatorId
                    ]);
                    continue; // Skip this row
                }
                
                if ($isNewBrand) {
                    Log::info('Brand created, will set categories', [
                        'brand_id' => $brand->id,
                        'brand_name' => $brandName,
                        'row_index' => $rowIndex + 2,
                        'creator_id' => $creatorId
                    ]);
                } else {
                    Log::info('Brand already exists, will update categories', [
                        'brand_id' => $brand->id,
                        'brand_name' => $brandName,
                        'existing_brand_name' => $brand->name, // Log actual name in DB for comparison
                        'row_index' => $rowIndex + 2,
                        'creator_id' => $creatorId
                    ]);
                }
                
                // Update category associations for the brand
                // If brand exists, sync categories to match the import (update)
                // If brand is new, attach the categories
                // Refresh brand to ensure we have latest data
                $brand->refresh();
                
                if (!empty($categoryIds)) {
                    // Get current category IDs before sync for logging
                    $existingCategoryIds = $brand->categories()
                        ->pluck('product_service_categories.id')
                        ->toArray();
                    
                    Log::info('Before category sync', [
                        'brand_id' => $brand->id,
                        'brand_name' => $brandName,
                        'existing_category_ids' => $existingCategoryIds,
                        'import_category_ids' => $categoryIds,
                        'row_index' => $rowIndex + 2
                    ]);
                    
                    try {
                        // Sync categories: this will detach categories not in the import and attach new ones
                        // This ensures the brand's categories match exactly what's in the import file
                        $brand->syncCategories($categoryIds);
                        
                        // Refresh brand to get updated categories
                        $brand->refresh();
                        
                        // Get updated category IDs after sync
                        $updatedCategoryIds = $brand->categories()
                            ->pluck('product_service_categories.id')
                            ->toArray();
                        
                        // Find what changed for logging
                        $categoriesToAdd = array_diff($categoryIds, $existingCategoryIds);
                        $categoriesToRemove = array_diff($existingCategoryIds, $categoryIds);
                        
                        Log::info('After category sync', [
                            'brand_id' => $brand->id,
                            'brand_name' => $brandName,
                            'updated_category_ids' => $updatedCategoryIds,
                            'added_category_ids' => $categoriesToAdd,
                            'removed_category_ids' => $categoriesToRemove,
                            'row_index' => $rowIndex + 2
                        ]);
                        
                        if (!empty($categoriesToAdd) || !empty($categoriesToRemove)) {
                            Log::info('Brand categories updated during import', [
                                'brand_id' => $brand->id,
                                'brand_name' => $brandName,
                                'added_category_ids' => $categoriesToAdd,
                                'removed_category_ids' => $categoriesToRemove,
                                'final_category_ids' => $updatedCategoryIds,
                                'row_index' => $rowIndex + 2
                            ]);
                        } else {
                            Log::info('Brand categories already match import, no changes needed', [
                                'brand_id' => $brand->id,
                                'brand_name' => $brandName,
                                'category_ids' => $categoryIds,
                                'row_index' => $rowIndex + 2
                            ]);
                        }
                    } catch (\Exception $syncError) {
                        Log::error('Error syncing brand categories', [
                            'brand_id' => $brand->id,
                            'brand_name' => $brandName,
                            'category_ids' => $categoryIds,
                            'row_index' => $rowIndex + 2,
                            'error' => $syncError->getMessage(),
                            'trace' => $syncError->getTraceAsString()
                        ]);
                        // Don't continue here - let the outer catch handle it or continue processing
                        // The sync error is logged but we continue with the next row
                    }
                } else {
                    // If import has no categories specified, keep existing categories (don't remove them)
                    // This allows partial updates - only update categories when explicitly provided in import
                    $existingCategoryIds = $brand->categories()
                        ->pluck('product_service_categories.id')
                        ->toArray();
                    
                    Log::info('No categories specified in import row, keeping existing brand categories', [
                        'brand_id' => $brand->id,
                        'brand_name' => $brandName,
                        'existing_category_ids' => $existingCategoryIds,
                        'row_index' => $rowIndex + 2
                    ]);
                }
            } catch (\Exception $e) {
                // Convert row to array for logging
                $rowArrayForLog = is_array($row) ? $row : (method_exists($row, 'toArray') ? $row->toArray() : (array)$row);
                
                Log::error('Error importing brand row', [
                    'row_index' => $rowIndex + 2,
                    'row_data' => $rowArrayForLog,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                // Continue with next row instead of stopping entire import
                continue;
            }
        }
    }
    
    /**
     * Fix auto_increment if there's an id=0 record or auto_increment is broken
     * Uses DB::unprepared() to run ALTER TABLE outside of transaction context
     */
    private function fixAutoIncrementIfNeeded()
    {
        try {
            // Check if there's a record with id=0
            $zeroIdBrand = \DB::table('brands')->where('id', 0)->first();
            
            if ($zeroIdBrand) {
                Log::warning('Found brand with id=0, attempting to fix auto_increment', [
                    'brand_name' => $zeroIdBrand->name ?? 'N/A'
                ]);
                
                // Get the maximum ID from the table
                $maxId = \DB::table('brands')->where('id', '>', 0)->max('id');
                $nextId = max(1, ($maxId && $maxId > 0 ? (int)$maxId + 1 : 1));
                
                // Use DB::unprepared() to run outside of transaction context (ALTER TABLE auto-commits)
                \DB::unprepared("ALTER TABLE brands AUTO_INCREMENT = " . (int)$nextId);
                
                Log::info('Fixed auto_increment', [
                    'new_auto_increment_value' => $nextId
                ]);
            } else {
                // Check current auto_increment value using DB query
                $result = \DB::select("SHOW TABLE STATUS LIKE 'brands'");
                
                if (!empty($result) && isset($result[0]->Auto_increment)) {
                    $autoIncrement = (int)$result[0]->Auto_increment;
                    
                    // If auto_increment is 0 or 1, fix it
                    if ($autoIncrement == 0 || $autoIncrement == 1) {
                        $maxId = \DB::table('brands')->where('id', '>', 0)->max('id');
                        $nextId = max(1, ($maxId && $maxId > 0 ? (int)$maxId + 1 : 1));
                        
                        // Use DB::unprepared() to run outside of transaction context (ALTER TABLE auto-commits)
                        \DB::unprepared("ALTER TABLE brands AUTO_INCREMENT = " . (int)$nextId);
                        
                        Log::info('Fixed auto_increment (was 0 or 1)', [
                            'old_value' => $autoIncrement,
                            'new_value' => $nextId
                        ]);
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('Error fixing auto_increment', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            // Continue anyway - manual ID insertion will handle it
        }
    }
}
