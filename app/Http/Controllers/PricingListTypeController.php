<?php

namespace App\Http\Controllers;

use App\Models\PricingListType;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PricingListTypeController extends Controller
{
    /**
     * List page
     */
    public function index()
    {
        $types = PricingListType::where('created_by', \Auth::user()->creatorId())->orderBy('id', 'desc')->get();
        return view('pricing_list_types.index', compact('types'));
    }

    /**
     * Create page
     */
    public function create()
    {
        return view('pricing_list_types.create');
    }

    /**
     * Store
     */
    public function store(Request $request)
    {
        $creatorId = \Auth::user()->creatorId();

        $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('pricing_list_types', 'name')
                    ->where('created_by', $creatorId),
            ],
        ]);

        PricingListType::create([
            'name'       => $request->name,
            'created_by' => $creatorId,
        ]);

        return redirect()
            ->route('pricing-list-types.index')
            ->with('success', __('Pricing list type created successfully.'));
    }

    /**
     * Edit page
     */
    public function edit(PricingListType $pricingListType)
    {
        return view('pricing_list_types.edit', compact('pricingListType'));
    }

    /**
     * Update
     */
    public function update(Request $request, PricingListType $pricingListType)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:pricing_list_types,name,' . $pricingListType->id,
        ]);

        $pricingListType->update([
            'name' => $request->name,
        ]);

        return redirect()
            ->route('pricing-list-types.index')
            ->with('success', __('Pricing list type updated successfully.'));
    }

    /**
     * Delete
     */
    public function destroy(PricingListType $pricingListType)
    {
        $pricingListType->delete();

        return redirect()
            ->route('pricing-list-types.index')
            ->with('success', __('Pricing list type deleted successfully.'));
    }
}
