<?php

namespace App\Http\Controllers;

use App\Exports\WarehouseStockCountImportLinesExport;
use App\Models\WarehouseStockCountImport;
use App\Models\warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

class WarehouseStockCountImportController extends Controller
{
    public function index(Request $request)
    {
        if (!Auth::user()->can('edit warehouse')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $creatorId = Auth::user()->creatorId();

        $warehouses = warehouse::where('created_by', $creatorId)
            ->orderBy('name')
            ->get();

        $query = WarehouseStockCountImport::query()
            ->where('created_by', $creatorId)
            ->with(['warehouse', 'user'])
            ->orderByDesc('id');

        $filterWarehouseId = $request->input('warehouse_id');
        if ($filterWarehouseId !== null && $filterWarehouseId !== '') {
            $wid = (int) $filterWarehouseId;
            $allowedIds = $warehouses->pluck('id')->all();
            if (in_array($wid, $allowedIds, true)) {
                $query->where(function ($q) use ($wid) {
                    $q->where('warehouse_id', $wid)
                        ->orWhereHas('lines', function ($l) use ($wid) {
                            $l->where('warehouse_id', $wid);
                        });
                });
            }
        }

        $imports = $query->paginate((int) $request->input('per_page', 25))->withQueryString();

        return view('warehouse.stock_count_import_index', compact('imports', 'warehouses', 'filterWarehouseId'));
    }

    public function show(Request $request, WarehouseStockCountImport $warehouseStockCountImport)
    {
        if (!Auth::user()->can('edit warehouse')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        if ((int) $warehouseStockCountImport->created_by !== (int) Auth::user()->creatorId()) {
            abort(403);
        }

        $warehouseStockCountImport->load(['warehouse', 'user']);

        $lines = $warehouseStockCountImport->lines()
            ->with(['warehouse'])
            ->orderBy('id')
            ->paginate((int) $request->input('per_page', 50))
            ->withQueryString();

        return view('warehouse.stock_count_import_show', [
            'import' => $warehouseStockCountImport,
            'lines' => $lines,
        ]);
    }

    public function exportLines(WarehouseStockCountImport $warehouseStockCountImport)
    {
        if (!Auth::user()->can('edit warehouse')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        if ((int) $warehouseStockCountImport->created_by !== (int) Auth::user()->creatorId()) {
            abort(403);
        }

        $safeName = preg_replace('/[^a-zA-Z0-9._-]+/', '_', pathinfo($warehouseStockCountImport->source_filename, PATHINFO_FILENAME));
        $safeName = $safeName !== '' ? $safeName : 'import';
        $fileName = 'stock_count_import_' . $warehouseStockCountImport->id . '_' . $safeName . '_lines.xlsx';

        return Excel::download(
            new WarehouseStockCountImportLinesExport((int) $warehouseStockCountImport->id),
            $fileName
        );
    }
}
