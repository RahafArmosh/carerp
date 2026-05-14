@extends('layouts.admin')
@section('page-title')
    {{ __('Manage Bills') }}
@endsection
@push('script-page')
    <script>
       $(document).ready(function () {
            $('#vender_id').select2({
                placeholder: "Select vender",
                allowClear: true,
                width: '100%'
            });
        });
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
    <li class="breadcrumb-item">{{ __('Bill') }}</li>
@endsection

@section('action-btn')
    <div class="float-end">
        <a href="#" data-size="md" data-bs-toggle="tooltip" title="{{ __('Import') }}"
             data-url="{{ route('bill.file.import') }}" data-ajax-popup="true"
             data-title="{{ __('Import product CSV file') }}" class="btn btn-sm btn-primary">
             <i class="ti ti-file-import me-1"></i>{{ __('Import') }}
         </a>
        <a href="{{ route('bill.export') }}" class="btn btn-sm btn-primary" data-bs-toggle="tooltip"
            title="{{ __('Export') }}">
            <i class="ti ti-file-export me-1"></i>{{ __('Export') }}
        </a>
        @can('create bill')
            <a href="{{ route('bill.create', 0) }}" class="btn btn-sm btn-primary" data-bs-toggle="tooltip"
                title="{{ __('Create') }}">
                <i class="ti ti-plus me-1"></i>{{ __('Create') }}
            </a>
        @endcan
    </div>
@endsection


@section('content')
    <div class="row">
        <div class="col-sm-12">
            <div class=" mt-2 " id="multiCollapseExample1">
                @if (session('error'))
                    <div class="alert alert-danger">
                        {{ session('error') }}
                    </div>
                @endif
                <div class="card">
                    <div class="card-body">
                        <form action="{{ route('bill.index') }}" method="GET" id="frm_submit">
                            <div class="row align-items-center justify-content-end">
                                <div class="col-xl-10">
                                    <div class="row">
                                        {{-- <div class="col-3"></div> --}}
                                        <div class="col-3">
                                            <div class="btn-box">
                                                <label for="vender_id" class="form-label">{{ __('Vender') }}</label>
                                                <select id="vender_id" name="vender_id" class="form-control select2">
                                                    <option value="">Select vender</option>
                                                    @foreach ($vender as $key => $value)
                                                        <option value="{{ $key }}"
                                                            {{ isset($_GET['vender_id']) && $_GET['vender_id'] == $key ? 'selected' : '' }}>
                                                            {{ $value }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12 month">
                                            <div class="btn-box">
                                                <label for="bill_date" class="form-label">{{ __('Bill Date') }}</label>
                                                <input type="date"  name="bill_date"
                                                    value="{{ isset($_GET['bill_date']) ? $_GET['bill_date'] : '' }}"
                                                    id="pc-daterangepicker-1"
                                                    class="form-control month-btn">

                                            </div>
                                        </div>
                                        <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12">
                                            <div class="btn-box">
                                                <label for="status" class="form-label">{{ __('Status') }}</label>
                                                <select id="status" name="status" class="form-control select">
                                                    <option value="">Select Status</option>
                                                    @foreach ($status as $key => $value)
                                                        <option value="{{ $key }}"
                                                            {{ isset($_GET['status']) && $_GET['status'] == $key ? 'selected' : '' }}>
                                                            {{ $value }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12">
                                            <div class="btn-box">
                                                <label for="status" class="form-label">{{ __('Payment Status') }}</label>
                                                <select id="paymentstatues" name="paymentstatues"
                                                    class="form-control select">
                                                    <option value="">Select Payment Status</option>
                                                    @foreach ($paymentstatues as $key => $value)
                                                        <option value="{{ $key }}"
                                                            {{ isset($_GET['paymentstatues']) && $_GET['paymentstatues'] == $key ? 'selected' : '' }}>
                                                            {{ $value }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-auto mt-4">
                                    <div class="row">
                                        <div class="col-auto">
                                            <a href="#" class="btn btn-sm btn-primary"
                                                onclick="document.getElementById('frm_submit').submit(); return false;"
                                                data-bs-toggle="tooltip" title="{{ __('Apply') }}"
                                                data-original-title="{{ __('apply') }}">
                                                <span class="btn-inner--icon"><i class="ti ti-search"></i></span>
                                            </a>
                                            <a href="{{ route('bill.index') }}" class="btn btn-sm btn-danger "
                                                data-bs-toggle="tooltip" title="{{ __('Reset') }}"
                                                data-original-title="{{ __('Reset') }}">
                                                <span class="btn-inner--icon"><i
                                                        class="ti ti-trash-off text-white-off "></i></span>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body table-border-style">
                    <div class="table-responsive">
                        <table class="table datatable">
                            <thead>
                                <tr>
                                    <th> {{ __('Bill') }}</th>
                                    <th> {{ __('Vendor') }}</th>
                                    <th> {{ __('Category') }}</th>
                                    <th> {{ __('Bill Date') }}</th>
                                    <th> {{ __('Due Date') }}</th>
                                    <th>{{ __('Status') }}</th>
                                    <th>{{ __('Payment Status') }}</th>
                                    @if (Gate::check('edit bill') || Gate::check('delete bill') || Gate::check('show bill'))
                                        <th width="10%"> {{ __('Action') }}</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($bills as $bill)
                                    <tr>
                                        <td class="Id">
                                            <a href="{{ route('bill.show', \Crypt::encrypt($bill->id)) }}"
                                                class="btn btn-outline-primary">{{ \Auth::user()->billNumberFormat($bill->bill_id) }}</a>
                                        </td>
                                        <td>{{ App\Models\Vender::where('id',$bill->vender_id)->first()->name }}</td>
                                        <td>{{ !empty($bill->category) ? $bill->category->name : '-' }}</td>
                                        <td>{{ Auth::user()->dateFormat($bill->bill_date) }}</td>
                                        <td>{{ Auth::user()->dateFormat($bill->due_date) }}</td>
                                        <td>
                                            @if ($bill->status == 0)
                                                <span
                                                    class="status_badge badge bg-primary p-2 px-3 rounded">{{ __(\App\Models\Bill::$statues[$bill->status]) }}</span>
                                            @elseif($bill->status == 1)
                                                <span
                                                    class="status_badge badge bg-secondary p-2 px-3 rounded">{{ __(\App\Models\Bill::$statues[$bill->status]) }}</span>
                                            @elseif($bill->status == 2)
                                                <span
                                                    class="status_badge badge bg-warning p-2 px-3 rounded">{{ __(\App\Models\Bill::$statues[$bill->status]) }}</span>
                                            @elseif($bill->status == 4)
                                                <span
                                                    class="status_badge badge bg-danger p-2 px-3 rounded">{{ __(\App\Models\Bill::$statues[$bill->status]) }}</span>
                                            @elseif($bill->status == 6)
                                                <span
                                                    class="status_badge badge bg-info p-2 px-3 rounded">{{ __(\App\Models\Bill::$statues[$bill->status]) }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($bill->payment_status == 0)
                                                <span
                                                    class="status_badge badge bg-secondary p-2 px-3 rounded">{{ __(\App\Models\Bill::$paymentstatues[$bill->payment_status]) }}</span>
                                            @elseif($bill->payment_status == 2)
                                                <span
                                                    class="status_badge badge bg-warning p-2 px-3 rounded">{{ __(\App\Models\Bill::$paymentstatues[$bill->payment_status]) }}</span>
                                            @elseif($bill->payment_status == 4)
                                                <span
                                                    class="status_badge badge bg-primary p-2 px-3 rounded">{{ __(\App\Models\Bill::$paymentstatues[$bill->payment_status]) }}</span>
                                            @endif
                                        </td>
                                        @if (Gate::check('edit bill') || Gate::check('delete bill') || Gate::check('show bill'))
                                            <td class="Action">
                                                <span>
                                                    @can('duplicate bill')
                                                        <div class="action-btn bg-primary ms-2" style="align-items: baseline;">
                                                            <form action="{{ route('bill.duplicate', $bill->id) }}"
                                                                method="GET" id="duplicate-form-{{ $bill->id }}">

                                                                <a href="#"
                                                                    class="mx-3 btn btn-sm align-items-center bs-pass-para "
                                                                    data-bs-toggle="tooltip"
                                                                    data-original-title="{{ __('Duplicate') }}"
                                                                    data-bs-toggle="tooltip"
                                                                    title="{{ __('Duplicate Bill') }}"
                                                                    data-original-title="{{ __('Delete') }}"
                                                                    data-confirm="You want to confirm this action. Press Yes to continue or Cancel to go back"
                                                                    data-confirm-yes="document.getElementById('duplicate-form-{{ $bill->id }}').submit();">
                                                                    <i class="ti ti-copy text-white"></i>
                                                            </form>
                                                            </a>
                                                        </div>
                                                    @endcan
                                                    @can('show bill')
                                                        <div class="action-btn bg-info ms-2" style="align-items: baseline;">
                                                            <a href="{{ route('bill.show', \Crypt::encrypt($bill->id)) }}"
                                                                class="mx-3 btn btn-sm align-items-center"
                                                                data-bs-toggle="tooltip" title="{{ __('Show') }}"
                                                                data-original-title="{{ __('Detail') }}">
                                                                <i class="ti ti-eye text-white"></i>
                                                            </a>
                                                        </div>
                                                    @endcan
                                                    @if ($bill->status == 0)
                                                        @can('edit bill')
                                                            <div class="action-btn bg-primary ms-2"
                                                                style="align-items: baseline;">
                                                                <a href="{{ route('bill.edit', \Crypt::encrypt($bill->id)) }}"
                                                                    class="mx-3 btn btn-sm align-items-center"
                                                                    data-bs-toggle="tooltip" title="Edit"
                                                                    data-original-title="{{ __('Edit') }}">
                                                                    <i class="ti ti-pencil text-white"></i>
                                                                </a>
                                                            </div>
                                                        @endcan
                                                    @endif
                                                    @can('delete bill')
                                                        <div class="action-btn bg-danger ms-2" style="align-items: baseline;">
                                                            <form id="delete-form" action="{{ route('bill.destroy', $bill->id) }}" method="POST">
                                                                @csrf
                                                                @method('DELETE')
                                                                <input type="hidden" name="delete_date" id="delete-date">
                                                                <input type="hidden" name="bill_id" id="delete-bill-id">
                                                            <a href="#" 
                                                            class="mx-3 btn btn-sm align-items-center"
                                                            onclick="confirmDelete({{ $bill->id }})"
                                                            title="{{ __('Delete') }}">
                                                            <i class="ti ti-trash text-white"></i>
                                                            </a>
                                                        </form>
                                                        </div>
                                                    @endcan
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

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    function confirmDelete(billId) {
        Swal.fire({
            title: "Select Delete Date",
            html: `<input type="date" id="delete-date-input" class="swal2-input" required>`,
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Yes, delete it",
            cancelButtonText: "Cancel",
            reverseButtons: true,
            preConfirm: () => {
                const date = document.getElementById('delete-date-input').value;
                if (!date) {
                    Swal.showValidationMessage('Please select a date.');
                    return false;
                }
                return date;
            }
        }).then((result) => {
            if (result.isConfirmed) {
                const selectedDate = result.value;
                document.getElementById('delete-bill-id').value = billId;
                document.getElementById('delete-date').value = selectedDate;
                document.getElementById('delete-form').submit();
            }
        });
    }
</script>
