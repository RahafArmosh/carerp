<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Color;
use App\Models\CustomField;
class ColorController extends Controller
{
    public function index()
    {
        $colors = Color::query()
            ->select(['id', 'name', 'code'])
            ->orderByDesc('id')
            ->get();

        return view('colors.index', compact('colors'));
    }

    public function create()
    {
        $customFields = CustomField::where('created_by', '=', \Auth::user()->creatorId())->where('module', '=', 'color')->get();
        return view('colors.create', compact('customFields'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:10|unique:colors,code',
        ]);

        $color = Color::create($request->all());
        CustomField::saveData($color, $request->customField);
        return redirect()->route('colors.index')->with('success', 'Color created successfully.');
    }

    public function show($id)
    {
        $color = Color::findOrFail($id);
        return view('colors.show', compact('color'));
    }

    public function edit($id)
    {
        $color = Color::findOrFail($id);
        $customFields       = CustomField::where('created_by', '=', \Auth::user()->creatorId())->where('module', '=', 'color')->get();
        return view('colors.edit', compact('color','customFields'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:10|unique:colors,code,' . $id,
        ]);

        $color = Color::findOrFail($id);
        $color->update($request->all());
        CustomField::saveData($color, $request->customField);
        return redirect()->route('colors.index')->with('success', 'Color updated successfully.');
    }

    public function destroy($id)
    {
        $color = Color::findOrFail($id);
        $color->delete();

        return redirect()->route('colors.index')->with('success', 'Color deleted successfully.');
    }
}
