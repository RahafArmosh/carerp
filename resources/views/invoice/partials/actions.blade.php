@php $invoiceID = Crypt::encrypt($invoice->id); @endphp
@can('copy invoice')
    <div class="action-btn bg-warning ms-2">
        <a href="#" id="{{ route('invoice.link.copy', [$invoiceID]) }}" class="mx-2 px-2 btn btn-sm align-items-center text-white"
            onclick="copyToClipboard(this)" data-bs-toggle="tooltip" title="{{ __('Copy Invoice') }}"
            data-original-title="{{ __('Copy Invoice') }}">{{ __('Copy link') }}</a>
    </div>
@endcan
@can('duplicate invoice')
    @if ($invoice->status != 0)
        <div class="action-btn bg-primary ms-2">
            <form method="get" action="{{ route('invoice.duplicate', ['id' => $invoice->id]) }}"
                id="duplicate-form-{{ $invoice->id }}">
                <a href="#" class="mx-2 px-2 btn btn-sm align-items-center text-white bs-pass-para" data-toggle="tooltip"
                    data-original-title="{{ __('Duplicate') }}" data-bs-toggle="tooltip" title="{{ __('Duplicate') }}"
                    data-confirm="You want to confirm this action. Press Yes to continue or Cancel to go back"
                    data-confirm-yes="document.getElementById('duplicate-form-{{ $invoice->id }}').submit();">
                    {{ __('Duplicate') }}
                </a>
            </form>
        </div>
    @endif
@endcan
@can('show invoice')
    <div class="action-btn bg-info ms-2">
        <a href="{{ route('invoice.show', $invoiceID) }}" class="mx-2 px-2 btn btn-sm align-items-center text-white"
            data-bs-toggle="tooltip" title="{{ __('View') }}" data-original-title="{{ __('Detail') }}">
            {{ __('View') }}
        </a>
    </div>
@endcan
@can('edit invoice')
    @if ($invoice->status == 0)
        <div class="action-btn bg-primary ms-2">
            <a href="{{ route('invoice.edit', $invoiceID) }}" class="mx-2 px-2 btn btn-sm align-items-center text-white"
                data-bs-toggle="tooltip" title="{{ __('Edit') }}" data-original-title="{{ __('Edit') }}">
                {{ __('Edit') }}
            </a>
        </div>
    @endif
@endcan
@can('delete invoice')
    <div class="action-btn bg-danger ms-2">
        <form method="POST" action="{{ route('invoice.destroy', ['invoice' => $invoice->id]) }}"
            id="delete-form-{{ $invoice->id }}">
            @csrf
            @method('DELETE')
            <input type="hidden" name="delete_date" id="delete-date-{{ $invoice->id }}">
            <a href="#" onclick="confirmDelete(event, {{ $invoice->id }})"
                class="mx-2 px-2 btn btn-sm align-items-center text-white" data-bs-toggle="tooltip" title="{{ __('Delete') }}">
                {{ __('Delete') }}
            </a>
        </form>
    </div>
@endcan
