<?php

namespace App\Http\Controllers;

use App\Models\CustomField;
use App\Models\ProductServiceCategory;
use Illuminate\Http\Request;

class CustomFieldController extends Controller
{
    public function __construct()
    {

    }

    public function index()
    {
        if(\Auth::user()->can('manage constant custom field'))
        {
            $custom_fields = CustomField::where('created_by', '=', \Auth::user()->creatorId())->with('categories')->get();

            return view('customFields.index', compact('custom_fields'));
        }
        else
        {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }


    public function create()
    {
        if(\Auth::user()->can('create constant custom field'))
        {
            $category     = ProductServiceCategory::where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $types   = CustomField::$fieldTypes;
            $modules = CustomField::$modules;

            return view('customFields.create', compact('types', 'modules','category'));
        }
        else
        {
            return response()->json(['error' => __('Permission Denied.')], 401);
        }
    }


    public function store(Request $request)
    {
        if(\Auth::user()->can('create constant custom field'))
        {

            $validator = \Validator::make(
                $request->all(), [
                                   'name' => 'required|max:40',
                                   'type' => 'required',
                                   'module' => 'required',
                               ]
            );

            if($validator->fails())
            {
                $messages = $validator->getMessageBag();

                return redirect()->route('custom-field.index')->with('error', $messages->first());
            }

            $modulesInput = $request->input('module');
            $selectedModules = is_array($modulesInput) ? $modulesInput : [$modulesInput];
            $selectedModules = array_values(array_filter(array_map('strval', $selectedModules)));

            // Normalize legacy / typo module keys
            $selectedModules = array_map(function ($m) {
                $m = trim((string) $m);
                if ($m === 'proposalitem') {
                    return 'proposal_item';
                }
                return $m;
            }, $selectedModules);

            $allowedModules = array_keys(CustomField::$modules);
            $selectedModules = array_values(array_filter($selectedModules, function ($m) use ($allowedModules) {
                return in_array($m, $allowedModules, true);
            }));

            if (empty($selectedModules)) {
                return redirect()->route('custom-field.index')->with('error', __('Please select at least one valid module.'));
            }

            $existingModules = CustomField::where('created_by', \Auth::user()->creatorId())
                ->where('name', $request->name)
                ->whereIn('module', $selectedModules)
                ->pluck('module')
                ->all();

            if (!empty($existingModules)) {
                $existingLabels = array_map(function ($m) {
                    return CustomField::$modules[$m] ?? $m;
                }, $existingModules);

                return redirect()->route('custom-field.index')->with(
                    'error',
                    __('A custom field with the same name already exists for: ') . implode(', ', $existingLabels)
                );
            }

            // Filter and sanitize categories
            $categoryIds = [];
            if ($request->has('category_id') && is_array($request->category_id) && !empty($request->category_id)) {
                $categoryIds = array_values(array_filter($request->category_id, function ($id) {
                    return $id != null && $id != -1;
                }));
            }

            \DB::beginTransaction();
            try {
                foreach ($selectedModules as $module) {
                    $custom_field             = new CustomField();
                    $custom_field->name       = $request->name;
                    $custom_field->type       = $request->type;
                    $custom_field->module     = $module;
                    $custom_field->field_type = $request->field_type ?? 'constant';

                    // Handle dropdown options
                    if ($custom_field->type === 'dropdown') {
                        $options = (string) $request->input('dropdown_options', '');
                        $custom_field->options = json_encode(array_filter(array_map('trim', explode("\n", $options))));
                    }

                    $custom_field->created_by = \Auth::user()->creatorId();
                    $custom_field->show_in_bill = $request->show_in_bill ?? 0;
                    $custom_field->show_in_invoice = $request->show_in_invoice ?? 0;
                    $custom_field->save();

                    // Only sync categories when provided (safe for other modules)
                    if (!empty($categoryIds)) {
                        $custom_field->categories()->sync($categoryIds);
                    }
                }

                \DB::commit();
            } catch (\Throwable $e) {
                \DB::rollBack();
                throw $e;
            }

            return redirect()->route('custom-field.index')->with('success', __('Custom Field successfully created for selected modules!'));
        }
        else
        {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }


    public function show(CustomField $customField)
    {
        return redirect()->route('custom-field.index');
    }

    public function edit(CustomField $customField)
    {
        if(\Auth::user()->can('edit constant custom field'))
        {
            if($customField->created_by == \Auth::user()->creatorId())
            {
                $types   = CustomField::$fieldTypes;
                $modules = CustomField::$modules;
                $category = ProductServiceCategory::where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('name', 'id');

                return view('customFields.edit', compact('customField', 'types', 'modules', 'category'));
            }
            else
            {
                return response()->json(['error' => __('Permission Denied.')], 401);
            }
        }
        else
        {
            return response()->json(['error' => __('Permission Denied.')], 401);
        }
    }


    public function update(Request $request, CustomField $customField)
    {
        if(\Auth::user()->can('edit constant custom field'))
        {

            if($customField->created_by == \Auth::user()->creatorId())
            {

                $validator = \Validator::make(
                    $request->all(), [
                                       'name' => 'required|max:40',
                                   ]
                );

                if($validator->fails())
                {
                    $messages = $validator->getMessageBag();

                    return redirect()->route('custom-field.index')->with('error', $messages->first());
                }

                $customField->name = $request->name;
                $customField->type = $request->type;
                // $customField->module = $request->module;
                $customField->field_type = $request->field_type ?? 'constant';
                $customField->show_in_bill = $request->show_in_bill;
                $customField->show_in_invoice = $request->show_in_invoice;
                if ($customField->type === 'dropdown' && $request->filled('dropdown_options')) {
                    $options = $request->input('dropdown_options');
                    $customField->options = json_encode(array_filter(array_map('trim', explode("\n", $options))));
                }
                $customField->save();

                // Sync categories (many-to-many relationship)
                if($request->has('category_id') && is_array($request->category_id) && !empty($request->category_id)){
                    // Filter out -1 and null values
                    $categoryIds = array_filter($request->category_id, function($id) {
                        return $id != null && $id != -1;
                    });
                    if (!empty($categoryIds)) {
                        $customField->categories()->sync($categoryIds);
                    } else {
                        // If all categories were removed, detach all
                        $customField->categories()->detach();
                    }
                } else {
                    // No categories provided, detach all
                    $customField->categories()->detach();
                }

                return redirect()->route('custom-field.index')->with('success', __('Custom Field successfully updated!'));
            }
            else
            {
                return redirect()->back()->with('error', __('Permission Denied.'));
            }
        }
        else
        {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }


    public function destroy(CustomField $customField)
    {
        if(\Auth::user()->can('delete constant custom field'))
        {
            if($customField->created_by == \Auth::user()->creatorId())
            {
                $customField->delete();

                return redirect()->route('custom-field.index')->with('success', __('Custom Field successfully deleted!'));
            }
            else
            {
                return redirect()->back()->with('error', __('Permission Denied.'));
            }
        }
        else
        {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    /**
     * Export dropdown options to CSV
     */
    public function exportOptions(CustomField $customField)
    {
        if($customField->created_by != \Auth::user()->creatorId())
        {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }

        if($customField->type !== 'dropdown')
        {
            return redirect()->back()->with('error', __('This custom field is not a dropdown type.'));
        }

        try {
            // Get dropdown options from JSON
            $options = json_decode($customField->options, true);
            
            if(empty($options) || !is_array($options))
            {
                return redirect()->back()->with('error', __('No options found for this dropdown field.'));
            }

            // Sanitize field name for filename
            $fieldName = preg_replace('/[^a-zA-Z0-9]/', '_', $customField->name);
            $filename = 'custom_field_' . $fieldName . '_options_' . date('Y-m-d_H-i-s') . '.csv';
            
            $headers = [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ];

            $callback = function() use ($options, $customField) {
                $file = fopen('php://output', 'w');
                
                // Add BOM for UTF-8 (helps Excel recognize encoding)
                fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
                
                // Add CSV headers
                fputcsv($file, [
                    __('Custom Field Name'),
                    __('Option')
                ]);

                // Add data rows
                foreach ($options as $option) {
                    if(!empty(trim($option))) {
                        fputcsv($file, [
                            $customField->name,
                            trim($option)
                        ]);
                    }
                }

                fclose($file);
            };

            return response()->stream($callback, 200, $headers);

        } catch (\Exception $e) {
            \Log::error('Custom field options export failed', [
                'error' => $e->getMessage(),
                'field_id' => $customField->id,
                'user_id' => \Auth::user()->creatorId()
            ]);

            return redirect()->back()->with('error', __('Export failed: ') . $e->getMessage());
        }
    }
}
