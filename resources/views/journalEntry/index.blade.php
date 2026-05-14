@extends('layouts.admin')
@section('page-title')
{{__('Manage Journal Entry')}}
@endsection

@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{route('dashboard')}}">{{__('Dashboard')}}</a></li>
<li class="breadcrumb-item">{{__('Journal Entry')}}</li>
@endsection

@section('action-btn')
<div class="float-end">
    @can('create journal entry')
    <a href="{{ route('journal-entry.create') }}" data-title="{{__('Create New Journal')}}" data-bs-toggle="tooltip" title="{{__('Create')}}" class="btn btn-sm btn-primary">
        <i class="ti ti-plus"></i>
    </a>
    @endcan
</div>
@endsection

@section('content')

<div class="row">
    <div class="col-xl-12">
        <div class="card">
            <div class="card-body table-border-style">
                <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-3">
                    <form method="get" action="{{ route('journal-entry.index') }}" class="d-flex align-items-end gap-2">
                        <div>
                            <label for="per_page" class="form-label mb-1">{{ __('Per page') }}</label>
                            <select name="per_page" id="per_page" class="form-control form-control-sm" onchange="this.form.submit()">
                                @foreach ([25, 50, 100, 200] as $n)
                                    <option value="{{ $n }}" {{ (int) request('per_page', $perPage) === $n ? 'selected' : '' }}>{{ $n }}</option>
                                @endforeach
                            </select>
                        </div>
                    </form>

                    <div style="min-width: 280px; max-width: 420px; width: 100%;">
                        <label for="journal-table-search" class="form-label mb-1">{{ __('Search') }}</label>
                        <input type="text" id="journal-table-search" class="form-control form-control-sm" placeholder="{{ __('Search journal entries...') }}">
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table" id="journal-table">
                        <thead>
                            <tr>
                                <th> {{__('Journal ID')}}</th>
                                <th> {{__('Date')}}</th>
                                <th> {{__('Currency')}}</th>
                                <th> {{__('Amount')}}</th>
                                <th> {{__('Description')}}</th>
                                <th width="10%"> {{__('Action')}}</th>
                            </tr>
                        </thead>
                        <tbody id="journal-table-body">
                            @foreach ($journalEntries as $journalEntry)
                            <tr>
                                <td class="Id">
                                    <a href="{{ route('journal-entry.show',$journalEntry->id) }}" class="btn btn-outline-primary">{{ AUth::user()->journalNumberFormat($journalEntry->journal_id) }}</a>
                                </td>
                                <td>{{ Auth::user()->dateFormat($journalEntry->date) }}</td>
                                <td>
                                    @if($journalEntry->currency)
                                        {{ $journalEntry->currency->code }} ({{ $journalEntry->currency->symbol }})
                                        @if($journalEntry->currency_rate !== null && $journalEntry->currency_rate !== '')
                                            <br><span class="text-muted small">{{ __('Rate') }}: {{ number_format((float) $journalEntry->currency_rate, 6) }}</span>
                                        @endif
                                    @else
                                        <span class="text-muted">{{ __('Default') }}</span>
                                    @endif
                                </td>
                                <td>
                                    {{ $journalEntry->formatMoney($journalEntry->totalCredit()) }}
                                </td>
                                <td title="{{ !empty($journalEntry->description) ? $journalEntry->description : '-' }}">
                                    {{ !empty($journalEntry->description) ? \Illuminate\Support\Str::limit($journalEntry->description, 100) : '-' }}
                                </td>
                                <td>
                                    @can('edit journal entry')
                                    <div class="action-btn bg-primary ms-2">
                                        <a data-title="{{__('Edit Journal')}}" href="{{ route('journal-entry.edit',[$journalEntry->id]) }}" class="mx-3 btn btn-sm align-items-center" data-bs-toggle="tooltip" title="{{__('Edit')}}" data-original-title="{{__('Edit')}}">
                                            <i class="ti ti-pencil text-white"></i>
                                        </a>
                                    </div>
                                    @endcan
                                    @can('create journal entry')
                                    <div class="action-btn bg-info ms-2">
                                        <a data-title="{{__('Duplicate Journal')}}" href="{{ route('journal-entry.duplicate',[$journalEntry->id]) }}" class="mx-3 btn btn-sm align-items-center" data-bs-toggle="tooltip" title="{{__('Duplicate')}}" data-original-title="{{__('Duplicate')}}">
                                            <i class="ti ti-copy text-white"></i>
                                        </a>
                                    </div>
                                    @endcan
                                    @can('delete journal entry')
                                    <div class="action-btn bg-warning ms-2">
                                        <form id="reverse-form-{{ $journalEntry->id }}" action="{{ route('journal-entry.reverse', $journalEntry->id) }}" method="POST">
                                            @csrf
                                            <input type="hidden" name="delete_date" id="reverse-date-{{ $journalEntry->id }}">
                                            <a href="#"
                                                class="mx-3 btn btn-sm align-items-center"
                                                onclick="confirmReverse({{ $journalEntry->id }})"
                                                title="{{ __('Reverse') }}">
                                                <i class="ti ti-arrow-back text-white"></i>
                                            </a>
                                        </form>
                                    </div>
                                    <div class="action-btn bg-danger ms-2">
                                        <form id="delete-form-{{ $journalEntry->id }}" action="{{ route('journal-entry.destroy', $journalEntry->id) }}" method="POST">
                                            @csrf
                                            @method('DELETE')
                                            <a href="#"
                                                class="mx-3 btn btn-sm align-items-center"
                                                onclick="confirmDelete({{ $journalEntry->id }})"
                                                title="{{ __('Delete') }}">
                                                <i class="ti ti-trash text-white"></i>
                                            </a>
                                        </form>
                                    </div>
                                    @endcan
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer py-3">
                {{ $journalEntries->withQueryString()->links() }}
            </div>
        </div>
    </div>
</div>

@endsection

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    function confirmReverse(journalId) {
        Swal.fire({
            title: "Select Reverse Date",
            html: `<input type="date" id="reverse-date-input" class="swal2-input" required>`,
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Yes, reverse it",
            cancelButtonText: "Cancel",
            reverseButtons: true,
            focusConfirm: false,
            preConfirm: () => {
                const date = document.getElementById('reverse-date-input').value;
                if (!date) {
                    Swal.showValidationMessage('Please select a date.');
                    return false;
                }
                return date;
            }
        }).then((result) => {
            if (result.isConfirmed) {
                const selectedDate = result.value;
                // Fill the hidden input with selected date
                document.getElementById(`reverse-date-${journalId}`).value = selectedDate;
                // Submit the correct form
                document.getElementById(`reverse-form-${journalId}`).submit();
            }
        });
    }

    function confirmDelete(journalId) {
        Swal.fire({
            title: "Are you sure?",
            text: "This will permanently delete the journal entry and all its ledger entries. This action cannot be undone!",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Yes, delete it",
            cancelButtonText: "Cancel",
            reverseButtons: true,
            focusConfirm: false,
            confirmButtonColor: "#d33",
        }).then((result) => {
            if (result.isConfirmed) {
                // Submit the delete form
                document.getElementById(`delete-form-${journalId}`).submit();
            }
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        const searchInput = document.getElementById('journal-table-search');
        const tableBody = document.getElementById('journal-table-body');
        if (!searchInput || !tableBody) {
            return;
        }

        const rows = Array.from(tableBody.querySelectorAll('tr'));

        searchInput.addEventListener('input', function () {
            const keyword = this.value.toLowerCase().trim();

            rows.forEach(function (row) {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(keyword) ? '' : 'none';
            });
        });
    });
</script>

