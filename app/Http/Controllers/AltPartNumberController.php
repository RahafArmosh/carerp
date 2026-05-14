<?php

namespace App\Http\Controllers;

use App\Imports\AltPartNumbersImport;
use App\Models\SubProduct;
use App\Models\AltPartNumber;
use App\Models\Product;
use App\Models\ProductService;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;


class AltPartNumberController extends Controller
{
    public function partsIndex()
    {
        $parts = ProductService::whereNotNull('sku')->where('created_by', \Auth::user()->creatorId())
            ->get();

        return view('subproducts.alternatives.parts_index', compact('parts'));
    }

    public function index($productNo)
    {
        $part = SubProduct::where('chassis_no', $productNo)->first();

        $alternatives = AltPartNumber::with('alternativePart')
            ->where('created_by', \Auth::user()->creatorId())
            ->where('part_number', $productNo)
            ->orderBy('priority')
            ->get();

        return view(
            'subproducts.alternatives.index',
            compact('part', 'productNo', 'alternatives')
        );
    }
    public function create($productNo)
    {
        $productNo = strtoupper(trim($productNo));
        $part = ProductService::where('created_by', \Auth::user()->creatorId())->where('sku', $productNo)->first();
        $parts = ProductService::where('created_by', \Auth::user()->creatorId())->whereNotNull('sku')->where('sku', '!=', $productNo)->get();
        
        return view('subproducts.alternatives.create', compact('part', 'parts'));
    }
    
    public function store(Request $request, $productNo)
    {
        $productNo = strtoupper(trim($productNo));
        $request->validate([
            'alternative_part_number' => 'required|string|different:product_no',
            'priority' => 'nullable|integer|min:1',
        ]);

        try {
            // Create main alternative
            AltPartNumber::create([
                'part_number' => $productNo,
                'alternative_part_number' => strtoupper(trim($request->alternative_part_number)),
                'priority' => $request->priority ?? 1,
                'is_active' => 1,
                'created_by' => \Auth::user()->creatorId(),
            ]);

            // If "both ways" checked, create reverse alternative
            if ($request->has('bothway') && $request->bothway) {
                AltPartNumber::create([
                    'part_number' => strtoupper(trim($request->alternative_part_number)),
                    'alternative_part_number' => $productNo,
                    'priority' => $request->priority ?? 1,
                    'is_active' => 1,
                    'created_by' => \Auth::user()->creatorId(),
                ]);
            }

            return redirect()
                ->back()
                ->with('success', __('Alternative part added successfully'));

        } catch (\Exception $e) {
            // Log the error for debugging (optional)
            \Log::error('AltPartNumber store error: ' . $e->getMessage());

            // Return with user-friendly error
            return redirect()
                ->back()
                ->with('success', __('Failed to add alternative part: ') . $e->getMessage());
        }
    }


    /**
     * Toggle active / update priority
     */
    public function update(Request $request, $id)
    {
        $alt = AltPartNumber::findOrFail($id);

        $alt->update([
            'priority' => $request->priority ?? $alt->priority,
            'is_active' => $request->has('is_active') ? $request->is_active : $alt->is_active,
        ]);

        return redirect()->back()->with('success', __('Alternative updated'));
    }

    /**
     * Delete alternative
     */
    public function destroy($id)
    {
        $alt = AltPartNumber::findOrFail($id);
        $alt->delete();
        return redirect()->back()->with('success', __('Alternative deleted'));
    }
    public function importForm()
    {
        return view('subproducts.alternatives.import');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls',
        ]);

        try {
            Excel::import(new AltPartNumbersImport, $request->file('file'));

            return back()->with('success', __('All alternative parts imported successfully'));

        } catch (\Illuminate\Validation\ValidationException $e) {

            return back()->withErrors($e->errors());

        } catch (\Exception $e) {

            return back()->with('error', __('Import failed: ') . $e->getMessage());
        }
    }
    
    public function downloadTemplate()
    {
        return Excel::download(
            new \App\Exports\AltPartNumbersTemplateExport,
            'alternative_parts_template.xlsx'
        );
    }



}
