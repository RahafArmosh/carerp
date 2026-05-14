<?php

namespace App\Http\Controllers;

use App\Models\Currency;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class CurrencyController extends Controller
{
    public function index()
    {
        $currancies = Currency::query()
            ->select(['id', 'code', 'name', 'symbol', 'exchange_rate', 'created_by'])
            ->orderByDesc('id')
            ->get();

        return view('Currency.index', compact('currancies'));
    }

    public function create()
    {
        return view('Currency.create');
    }

    public function store(Request $request)
    {
        $createdBy = Auth::user()->type === 'super admin' ? 0 : Auth::user()->creatorId();

        $request->validate([
            'name' => 'required|string|max:255',
            'code' => [
                'required',
                'string',
                'max:10',
                Rule::unique('currencies', 'code')->where(fn ($q) => $q->where('created_by', $createdBy)),
            ],
            'symbol' => 'nullable|string|max:10',
            'exchange_rate' => 'nullable|numeric',
        ]);

        Currency::create([
            'name' => $request->name,
            'code' => strtoupper(trim((string) $request->code)),
            'symbol' => $request->symbol,
            'exchange_rate' => $request->input('exchange_rate', 1.0),
            'created_by' => $createdBy,
        ]);

        return redirect()->route('currency.index')->with('success', __('Currency created successfully.'));
    }

    public function show($id)
    {
        $currency = Currency::findOrFail($id);

        return view('Currency.show', compact('currency'));
    }

    public function edit($id)
    {
        $currency = Currency::findOrFail($id);
        if ($response = $this->currencyWriteDeniedResponse($currency)) {
            return $response;
        }

        return view('Currency.edit', compact('currency'));
    }

    public function update(Request $request, $id)
    {
        $currency = Currency::findOrFail($id);
        if ($response = $this->currencyWriteDeniedResponse($currency)) {
            return $response;
        }

        $createdBy = (int) $currency->created_by;

        $request->validate([
            'name' => 'required|string|max:255',
            'code' => [
                'required',
                'string',
                'max:10',
                Rule::unique('currencies', 'code')
                    ->ignore($currency->id)
                    ->where(fn ($q) => $q->where('created_by', $createdBy)),
            ],
            'symbol' => 'nullable|string|max:10',
            'exchange_rate' => 'nullable|numeric',
        ]);

        $currency->update([
            'name' => $request->name,
            'code' => strtoupper(trim((string) $request->code)),
            'symbol' => $request->symbol,
            'exchange_rate' => $request->input('exchange_rate', $currency->exchange_rate ?? 1.0),
        ]);

        return redirect()->route('currency.index')->with('success', __('Currency updated successfully.'));
    }

    public function destroy($id)
    {
        $currency = Currency::findOrFail($id);
        if ($response = $this->currencyWriteDeniedResponse($currency)) {
            return $response;
        }

        $currency->delete();

        return redirect()->route('currency.index')->with('success', __('Currency deleted successfully.'));
    }

    /**
     * Get currency rate for AJAX request
     */
    public function getRate(Request $request)
    {
        $currencyId = $request->input('currency_id');
        $currency = Currency::find($currencyId);

        if ($currency) {
            return response()->json([
                'rate' => $currency->exchange_rate ?? 1.0,
            ]);
        }

        return response()->json(['error' => 'Currency not found'], 404);
    }

    private function currencyWriteDeniedResponse(Currency $currency): ?RedirectResponse
    {
        if (Auth::user()->type === 'super admin') {
            return null;
        }

        if ($currency->isSystemCurrency()) {
            return redirect()->route('currency.index')->with('error', __('System currencies cannot be changed.'));
        }

        if ((int) $currency->created_by !== (int) Auth::user()->creatorId()) {
            return redirect()->route('currency.index')->with('error', __('Permission denied.'));
        }

        return null;
    }
}
