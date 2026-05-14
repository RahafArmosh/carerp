<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Country;
use App\Models\CustomField;
class CountryController extends Controller
{
    //
    public function index()
    {
        $countries = Country::query()
            ->select(['id', 'name'])
            ->orderByDesc('id')
            ->get();

        return view('countries.index', compact('countries'));
    }
    public function create()
    {
        $customFields = CustomField::where('created_by', '=', \Auth::user()->creatorId())->where('module', '=', 'country')->get();
        return view('countries.create', compact('customFields'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            // 'code' => 'required|string|max:10|unique:countries,code',
            'description' => 'nullable|string|max:1000',
        ]);

        $country = Country::create(array_merge($request->all(), [
            'created_by' => \Auth::user()->creatorId(),
        ]));

        CustomField::saveData($country, $request->customField);
        return redirect()->route('countries.index')->with('success', 'Country created successfully.');
    }

    public function show($id)
    {
        $country = Country::findOrFail($id);
        return view('countries.show', compact('country'));
    }

    public function edit($id)
    {
        $country = Country::findOrFail($id);
        $customFields       = CustomField::where('created_by', '=', \Auth::user()->creatorId())->where('module', '=', 'country')->get();
        return view('countries.edit', compact('country','customFields'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            // 'code' => 'required|string|max:10|unique:countries,code,' . $id,
            // 'description' => 'nullable|string|max:1000',
        ]);

        $country = Country::findOrFail($id);
        $country->update($request->all());
        CustomField::saveData($country, $request->customField);
        return redirect()->route('countries.index')->with('success', 'Country updated successfully.');
    }

    public function destroy($id)
    {
        $country = Country::findOrFail($id);
        $country->delete();

        return redirect()->route('countries.index')->with('success', 'Country deleted successfully.');
    }
}
