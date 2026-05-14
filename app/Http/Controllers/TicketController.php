<?php

namespace App\Http\Controllers;

use App\Models\warehouse;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    public function printBarcode(){
        // get the subproduct id and 
        // $subproduct = $request->;
        // and but the 
        // return pdf or image that is making the 
        
        // Get warehouses - filter by user's assigned warehouses if user has any
        $user = \Auth::user();
        if ($user->warehouses()->count() > 0) {
            // User has assigned warehouses - only show those
            $warehouses = $user->warehouses()
                ->select('*', \DB::raw("CONCAT(name) AS name"))
                ->get()
                ->pluck('name', 'id');
        } else {
            // No assigned warehouses - show all company warehouses (backward compatibility)
            $warehouses = warehouse::select('*', \DB::raw("CONCAT(name) AS name"))
                ->where('created_by', \Auth::user()->creatorId())
                ->get()
                ->pluck('name', 'id');
        }
        
        return view('pos.print', compact('warehouses'));

    }

    public function post_print_tikets(Request $request){
        // get the subproduct id and 
        // $subproduct = $request->;
        // and but the 
        // return pdf or image that is making the 

    }
}
