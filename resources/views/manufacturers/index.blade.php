@extends('layouts.admin')
@section('page-title')
    {{ __('Manage Manufacturers') }}
@endsection
@push('script-page')
    <script>
        $('.copy_link').click(function(e) {
            e.preventDefault();
            var copyText = $(this).attr('href');

            document.addEventListener('copy', function(e) {
                e.clipboardData.setData('text/plain', copyText);
                e.preventDefault();
            }, true);

            document.execCommand('copy');
            show_toastr('success', 'Url copied to clipboard', 'success');
        });
    </script>
@endpush
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item">{{ __('Manufacturers') }}</li>
@endsection


@section('action-btn')
    <div class="float-end">
        @can('create bill')
            <a href="{{ route('manufacturers.create', 0) }}" class="btn btn-sm btn-primary" data-bs-toggle="tooltip"
                title="{{ __('Create') }}">
                <i class="ti ti-plus"></i>
            </a>
        @endcan
    </div>
@endsection

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body table-border-style">
                <div class="table-responsive">
                    <table class="table datatable">
                        <thead>
                            <tr>
                                <th> {{ __('Manufacturer') }}</th>
                                <th> {{ __('Category') }}</th>
                                <th> {{ __('Date') }}</th>
                                <th>{{ __('Status') }}</th>
                                @if (Gate::check('edit manufacturer') || Gate::check('delete manufacturer') || Gate::check('show manufacturer'))
                                    <th width="10%"> {{ __('Action') }}</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($manufacturers as $expense)
                                <tr>
                                    <td class="Id">
                                        <a href="{{ route('manufacturers.show', \Crypt::encrypt($expense->id)) }}"
                                            class="btn btn-outline-primary">{{ AUth::user()->manufacturerNumberFormat($expense->bill_id) }}</a>
                                    </td>
                                    <td>{{ !empty($expense->category) ? $expense->category->name : '-' }}</td>
                                    <td>{{ Auth::user()->dateFormat($expense->bill_date) }}</td>
                                    <td>
                                        <span
                                            class="status_badge badge bg-primary p-2 px-3 rounded">{{ __(\App\Models\Invoice::$statues[$expense->status]) }}</span>
                                    </td>
                                    @if (Gate::check('edit manufacturer') || Gate::check('delete manufacturer') || Gate::check('show manufacturer'))
                                        <td class="Action">
                                            <span>

                                                @can('show manufacturer')
                                                    <div class="action-btn bg-info ms-2">
                                                        <a href="{{ route('manufacturers.show', \Crypt::encrypt($expense->id)) }}"
                                                            class="mx-3 btn btn-sm align-items-center"
                                                            data-bs-toggle="tooltip" title="{{ __('Show') }}"
                                                            data-original-title="{{ __('Detail') }}">
                                                            <i class="ti ti-eye text-white"></i>
                                                        </a>
                                                    </div>
                                                @endcan
                                                @can('edit manufacturer')
                                                    <div class="action-btn bg-primary ms-2">
                                                        <a href="{{ route('manufacturers.edit', \Crypt::encrypt($expense->id)) }}"
                                                            class="mx-3 btn btn-sm align-items-center"
                                                            data-bs-toggle="tooltip" title="Edit"
                                                            data-original-title="{{ __('Edit') }}">
                                                            <i class="ti ti-pencil text-white"></i>
                                                        </a>
                                                    </div>
                                                @endcan
                                                @can('delete manufacturer')
                                                    <div class="action-btn bg-danger ms-2">
                                                        <form method="POST"
                                                            action="{{ route('manufacturers.destroy', $expense->id) }}"
                                                            class="delete-form-btn" id="delete-form-{{ $expense->id }}">
                                                            @csrf
                                                            @method('DELETE')
                                                            <a href="#"
                                                                class="mx-3 btn btn-sm align-items-center bs-pass-para"
                                                                data-bs-toggle="tooltip" title="{{ __('Delete') }}"
                                                                data-original-title="{{ __('Delete') }}"
                                                                data-confirm="{{ __('Are You Sure?') . '|' . __('This action can not be undone. Do you want to continue?') }}"
                                                                data-confirm-yes="document.getElementById('delete-form-{{ $expense->id }}').submit();">
                                                                <i class="ti ti-trash text-white"></i>
                                                            </a>
                                                        </form>
                                                    </div>
                                                @endcan

                                                <div class="action-btn bg-warning ms-2">
                                                    <a href="{{ route('manufacturers.Tobill', \Crypt::encrypt($expense->id)) }}"
                                                        class="mx-3 btn btn-sm align-items-center"
                                                        data-bs-toggle="tooltip" title="{{ __('To Bill') }}"
                                                        data-original-title="{{ __('Detail') }}">
                                                        <i class="ti ti-send text-white"></i>
                                                    </a>
                                                </div>

                                            </span>
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
