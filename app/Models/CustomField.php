<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomField extends Model
{
    protected $fillable = [
        'name',
        'type',
        'module',
        'created_by',
        'options',
        'show_in_bill',
        'show_in_invoice',
        'field_type',
    ];

    public static $fieldTypes = [
        'text' => 'Text',
        'email' => 'Email',
        'number' => 'Number',
        'date' => 'Date',
        'textarea' => 'Textarea',
        'dropdown' => 'Dropdown',
    ];

    public static $modules = [
        'user' => 'User',
        'customer' => 'Customer',
        'vendor' => 'Vendor',
        'product' => 'Product',
        'proposal' => 'Proposal',
        'proposal_item' => 'Proposal Item',
        'Invoice' => 'Invoice',
        'Bill' => 'Bill',
        'account' => 'Account',
        'sub-product' => 'subProduct',
        'brand' => 'brand',
        'sub_brand' => 'sub_brand',
        'country' => 'country',
        'color' => 'color',
    ];

    public static function saveData($obj, $data)
    {

        if($data)
        {
            $RecordId = $obj->id;
            foreach($data as $fieldId => $value)
            {
                // Handle multi-select values (arrays)
                if (is_array($value)) {
                    // Delete existing values for this field
                    \DB::table('custom_field_values')
                        ->where('record_id', $RecordId)
                        ->where('field_id', $fieldId)
                        ->delete();
                    
                    // Insert each selected value
                    foreach ($value as $val) {
                        if (!empty($val)) {
                            \DB::insert(
                                'insert into custom_field_values (`record_id`, `field_id`,`value`,`created_at`,`updated_at`) values (?, ?, ?, ?, ?)', [
                                    $RecordId,
                                    $fieldId,
                                    $val,
                                    date('Y-m-d H:i:s'),
                                    date('Y-m-d H:i:s'),
                                ]
                            );
                        }
                    }
                } else {
                    // Single value - existing behavior
                    \DB::insert(
                        'insert into custom_field_values (`record_id`, `field_id`,`value`,`created_at`,`updated_at`) values (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`),`updated_at` = VALUES(`updated_at`) ', [
                            $RecordId,
                            $fieldId,
                            $value,
                            date('Y-m-d H:i:s'),
                            date('Y-m-d H:i:s'),
                        ]
                    );
                }
            }
        }
    }

    public static function getData($obj, $module)
    {
        // Get all custom fields for this module to check which ones allow multiple
        $customFields = self::where('module', $module)->get()->keyBy('id');
        
        $values = \DB::table('custom_field_values')->select(
            [
                'custom_field_values.value',
                'custom_fields.id',
            ]
        )->join('custom_fields', 'custom_field_values.field_id', '=', 'custom_fields.id')
        ->where('custom_fields.module', '=', $module)
        ->where('record_id', '=', $obj->id)
        ->get();
        
        $result = [];
        foreach ($values as $value) {
            $fieldId = $value->id;
            $customField = $customFields->get($fieldId);
            
            if ($customField && $customField->type === 'dropdown') {
                // Check if this field allows multiple selections
                $optionsData = json_decode($customField->options, true);
                $allowMultiple = isset($optionsData['allow_multiple']) ? $optionsData['allow_multiple'] : false;
                
                if ($allowMultiple) {
                    // For multi-select, collect all values in an array
                    if (!isset($result[$fieldId])) {
                        $result[$fieldId] = [];
                    }
                    $result[$fieldId][] = $value->value;
                } else {
                    // For single-select, use the last value (or first if only one)
                    $result[$fieldId] = $value->value;
                }
            } else {
                // For non-dropdown fields, use single value
                $result[$fieldId] = $value->value;
            }
        }
        
        return collect($result);
    }

    public function categories()
    {
        return $this->belongsToMany(ProductServiceCategory::class, 'custom_field_category', 'custom_field_id', 'product_service_category_id');
    }

    /**
     * Legacy method for backward compatibility
     * Returns the first category if multiple exist
     */
    public function category()
    {
        return $this->belongsToMany(ProductServiceCategory::class, 'custom_field_category', 'custom_field_id', 'product_service_category_id')->first();
    }

    public function customFieldValues()
    {
        return $this->hasMany(CustomFieldValue::class, 'field_id'); // Assuming 'field_id' links to CustomFieldValue
    }

    /**
     * Scope to filter custom fields by category ID(s)
     * Supports both single category ID and array of category IDs
     */
    public function scopeForCategory($query, $categoryId)
    {
        if (is_array($categoryId)) {
            return $query->whereHas('categories', function($q) use ($categoryId) {
                $q->whereIn('product_service_categories.id', $categoryId);
            });
        }
        return $query->whereHas('categories', function($q) use ($categoryId) {
            $q->where('product_service_categories.id', $categoryId);
        });
    }
}
