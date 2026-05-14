@extends('layouts.admin')
@section('page-title')
    {{ __('Manage Deals') }} @if ($pipeline)
        - {{ $pipeline->name }}
    @endif
@endsection
@php
    $setting = \App\Models\Utility::settings();
    $logo = \App\Models\Utility::get_file('uploads/logo/');

    $company_logo = $setting['company_logo_dark'] ?? '';
    $company_logos = $setting['company_logo_light'] ?? '';
    $company_small_logo = $setting['company_small_logo'] ?? '';
@endphp
@push('css-page')
    <link rel="stylesheet" href="{{ asset('css/summernote/summernote-bs4.css') }}">
    <link rel="stylesheet" href="https://cdn.datatables.net/2.3.0/css/dataTables.dataTables.css" />
@endpush
@push('script-page')
    <script src="{{ asset('css/summernote/summernote-bs4.js') }}"></script>
    <script src="https://cdn.datatables.net/2.3.0/js/dataTables.js"></script>
    <script src="https://cdn.datatables.net/select/3.0.0/js/dataTables.select.js"></script>
    <script>
        $(document).on("change", ".change-pipeline select[name=default_pipeline_id]", function() {
            $('#change-pipeline').submit();
        });
    </script>
    <script>
        function submitForm() {
            document.getElementById('report_Gledger').submit();
        }
    </script>
@endpush
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item">{{ __('Lead') }}</li>
@endsection
@section('action-btn')
    <div class="float-end">
        <a href="{{ route('deals.index') }}" data-bs-toggle="tooltip" title="{{ __('Kanban View') }}"
            class="btn btn-sm btn-primary">
            <i class="ti ti-layout-grid"></i>
        </a>
        <a href="#" data-size="md" data-bs-toggle="tooltip" title="{{ __('Import') }}"
            data-url="{{ route('deals.file.import') }}" data-ajax-popup="true"
            data-title="{{ __('Import Deal CSV file') }}" class="btn btn-sm btn-primary">
            <i class="ti ti-file-import"></i>
        </a>
        <a href="{{ route('deals.export') }}" data-bs-toggle="tooltip" title="{{ __('Export') }}"
            class="btn btn-sm btn-primary">
            <i class="ti ti-file-export"></i>
        </a>
        {{-- <a href="#" data-size="lg" data-url="{{ route('deals.create') }}" data-ajax-popup="true"
        data-bs-toggle="tooltip" title="{{ __('Create New Deal') }}" class="btn btn-sm btn-primary">
        <i class="ti ti-plus"></i>
    </a> --}}
    </div>
@endsection

@section('content')
    @if ($pipeline)
        <div class="row">
            @if (auth()->user()->hasAnyRole(['manager', 'company', 'crm admin']) || Gate::check('manage crm admin'))
                <div class="col-sm-12">
                    <div class="mt-2 " id="multiCollapseExample1">
                        <div class="card">
                            <div class="card-body">
                                <form action="{{ route('deals.list') }}" method="GET" id="report_Gledger">
                                    <div class="row align-items-center">
                                        <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12">
                                            <div class="btn-box">
                                                <label for="user" class="form-label">{{ __('Users') }}</label>
                                                <select id="user" name="user_id" class="form-control select2"
                                                    data-placeholder="{{ __('Select User') }}">
                                                    <option value=""></option>
                                                    @foreach ($users as $userId => $userName)
                                                        <option value="{{ $userId }}"
                                                            {{ isset($_GET['user_id']) && $_GET['user_id'] == $userId ? 'selected' : '' }}>
                                                            {{ $userName }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12">
                                            <div class="btn-box">
                                                <label for="user" class="form-label">{{ __('Pipelines') }}</label>
                                                <select name="default_pipeline_id" id="default_pipeline_id"
                                                    class="form-control select me-4">
                                                    @foreach ($pipelines as $key => $value)
                                                        <option value="{{ $key }}"
                                                            {{ $key == $pipeline->id ? 'selected' : '' }}>
                                                            {{ $value }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col mt-4 d-flex justify-content-end">
                                            <a href="#" class="btn btn-sm btn-primary"
                                                onclick="submitForm(); return false;" data-bs-toggle="tooltip"
                                                title="{{ __('Apply') }}" data-original-title="{{ __('apply') }}">
                                                <span class="btn-inner--icon"><i class="ti ti-search"></i></span>
                                            </a>
                                            <a href="{{ route('deals.list') }}" class="btn btn-sm btn-danger ms-2"
                                                data-bs-toggle="tooltip" title="{{ __('Reset') }}"
                                                data-original-title="{{ __('Reset') }}">
                                                <span class="btn-inner--icon"><i
                                                        class="ti ti-trash-off text-white-off"></i></span>
                                            </a>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
            <div class="col-sm-3">
                <div class="card">
                    <div class="card-body">
                        <div class="row align-items-center justify-content-between">
                            <div class="col-auto mb-3 mb-sm-0">
                                <small class="text-muted">{{ __('Total Deals') }}</small>
                                <h3 class="m-0">{{ $cnt_deal['total'] }}</h3>
                            </div>
                            <div class="col-auto">
                                <div class="theme-avtar bg-info">
                                    <i class="ti ti-layers-difference"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-3">
                <div class="card">
                    <div class="card-body">
                        <div class="row align-items-center justify-content-between">
                            <div class="col-auto mb-3 mb-sm-0">
                                <small class="text-muted">{{ __('This Month Total Deals') }}</small>
                                <h3 class="m-0">{{ $cnt_deal['this_month'] }}</h3>
                            </div>
                            <div class="col-auto">
                                <div class="theme-avtar bg-primary">
                                    <i class="ti ti-layers-difference"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-3">
                <div class="card">
                    <div class="card-body">
                        <div class="row align-items-center justify-content-between">
                            <div class="col-auto mb-3 mb-sm-0">
                                <small class="text-muted">{{ __('This Week Total Deals') }}</small>
                                <h3 class="m-0">{{ $cnt_deal['this_week'] }}</h3>
                            </div>
                            <div class="col-auto">
                                <div class="theme-avtar bg-warning">
                                    <i class="ti ti-layers-difference"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-3">
                <div class="card">
                    <div class="card-body">
                        <div class="row align-items-center justify-content-between">
                            <div class="col-auto mb-3 mb-sm-0">
                                <small class="text-muted">{{ __('Last 30 Days Total Deals') }}</small>
                                <h3 class="m-0">{{ $cnt_deal['last_30days'] }}</h3>
                            </div>
                            <div class="col-auto">
                                <div class="theme-avtar bg-danger">
                                    <i class="ti ti-layers-difference"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-xl-12">
                <div class="card">
                    <div class="card-body table-border-style">
                        <div class="table-responsive">
                            <div class="dt-container">
                                <form method="GET" action="{{ route('deals.list') }}" class="mb-3">
                                    <div class="dt-layout-row">
                                        <div class="dt-layout-cell dt-layout-start">
                                            <div class="dt-length">
                                                <select name="per_page" id="dt-length-0" class="dt-input"
                                                    aria-controls="example" onchange="this.form.submit()">
                                                    @foreach ([10, 25, 50, 100] as $limit)
                                                        <option value="{{ $limit }}"
                                                            {{ request('per_page', 100) == $limit ? 'selected' : '' }}>
                                                            {{ $limit }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                <label for="dt-length-0">
                                                    entries per page
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                            <table class="table" id="deals-list-table">
                                <thead>
                                    <tr>
                                        <th>{{ __('Name') }}</th>
                                        <th>{{ __('Phone Number') }}</th>
                                        <th>{{ __('Price') }}</th>
                                        <th>{{ __('DealQTY') }}</th>
                                        <th>{{ __('Stage') }}</th>
                                        <th>{{ __('Lead Date') }}</th>
                                        <th>{{ __('Deal Date') }}</th>
                                        <th>{{ __('Payment') }}</th>
                                        <th>{{ __('Tasks') }}</th>
                                        <th>{{ __('Attachment') }}</th>
                                        <th>{{ __('Users') }}</th>
                                        <th width="300px">{{ __('Action') }}</th>

                                    </tr>
                                </thead>
                                <tbody>
                                    @if (count($deals) > 0)
                                        @foreach ($deals as $deal)
                                            <tr>
                                                <td>{{ $deal->name }}</td>
                                                <td>{{ $deal->phone }}</td>
                                                <td>{{ \Auth::user()->priceFormat($deal->price) }}</td>
                                                <td>{{ $deal->getProductQuantity() }}</td>
                                                <td>{{ !empty($deal->stage) ? $deal->stage->name : '-' }}</td>
                                                <td>{{ !empty($deal->lead->date) ? $deal->lead->date: '-' }}</td>
                                                <td>{{ !empty($deal->created_at) ? $deal->created_at : '-' }}</td>
                                                <td>{{ !empty($deal->payment) ? $deal->payment : '-' }}</td>
                                                <td>{{ count($deal->tasks) }}/{{ count($deal->complete_tasks) }}</td>
                                                <td>{{ count($deal->files) }}</td>
                                                <td>
                                                    @foreach ($deal->users as $user)
                                                        {{ $user->name }}
                                                    @endforeach
                                                </td>
                                                @if (\Auth::user()->type != 'Client')
                                                    <td class="Action">
                                                        <span>
                                                            @can('view deal')
                                                                @if ($deal->is_active)
                                                                    <div class="action-btn bg-warning ms-2">
                                                                        <a href="{{ route('deals.show', $deal->id) }}"
                                                                            class="mx-3 btn btn-sm d-inline-flex align-items-center"
                                                                            data-size="xl" data-bs-toggle="tooltip"
                                                                            title="{{ __('View') }}"
                                                                            data-title="{{ __('Lead Detail') }}">
                                                                            <i class="ti ti-eye text-white"></i>
                                                                        </a>
                                                                    </div>
                                                                @endif
                                                            @endcan
                                                            @can('edit deal')
                                                                <div class="action-btn bg-info ms-2">
                                                                    <a href="#"
                                                                        class="mx-3 btn btn-sm d-inline-flex align-items-center"
                                                                        data-url="{{ URL::to('deals/' . $deal->id . '/edit') }}"
                                                                        data-ajax-popup="true" data-size="xl"
                                                                        data-bs-toggle="tooltip" title="{{ __('Edit') }}"
                                                                        data-title="{{ __('Lead Edit') }}">
                                                                        <i class="ti ti-pencil text-white"></i>
                                                                    </a>
                                                                </div>
                                                            @endcan
                                                            @can('delete deal')
                                                                <div class="action-btn bg-danger ms-2">
                                                                    <form action="{{ route('deals.destroy', $deal->id) }}"
                                                                        method="POST" id="delete-form-{{ $deal->id }}">
                                                                        @csrf
                                                                        @method('DELETE')
                                                                        <a href="#!"
                                                                            class="mx-3 btn btn-sm align-items-center bs-pass-para"
                                                                            data-bs-toggle="tooltip"
                                                                            title="{{ __('Delete') }}"
                                                                            onclick="confirmDelete('{{ __('Are You Sure?') }}', 'delete-form-{{ $deal->id }}')">
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
                                    @endif
                                </tbody>
                            </table>
                            {{-- Pagination navigation --}}
                            @if ($deals->hasPages())
                                <div class="dt-container">
                                    <div class="dt-layout-row">

                                        {{-- Info Section --}}
                                        <div class="dt-layout-cell dt-layout-start">
                                            <div class="dt-info" aria-live="polite" role="status" id="example_info">
                                                Showing {{ $deals->firstItem() }} to {{ $deals->lastItem() }} of
                                                {{ $deals->total() }} entries
                                            </div>
                                        </div>

                                        {{-- Pagination Section --}}
                                        <div class="dt-layout-cell dt-layout-end">
                                            <div class="dt-paging">
                                                <nav aria-label="pagination">

                                                    {{-- First --}}
                                                    <button
                                                        class="dt-paging-button first {{ $deals->onFirstPage() ? 'disabled' : '' }}"
                                                        type="button" aria-label="First"
                                                        onclick="window.location='{{ $deals->url(1) }}'"
                                                        {{ $deals->onFirstPage() ? 'disabled aria-disabled=true tabindex=-1' : '' }}>«</button>

                                                    {{-- Previous --}}
                                                    <button
                                                        class="dt-paging-button previous {{ $deals->onFirstPage() ? 'disabled' : '' }}"
                                                        type="button" aria-label="Previous"
                                                        onclick="window.location='{{ $deals->previousPageUrl() }}'"
                                                        {{ $deals->onFirstPage() ? 'disabled aria-disabled=true tabindex=-1' : '' }}>‹</button>

                                                    {{-- Page Buttons (Limited Range) --}}
                                                    @php
                                                        $current = $deals->currentPage();
                                                        $last = $deals->lastPage();
                                                        $start = max($current - 2, 1);
                                                        $end = min($start + 5, $last);
                                                        if ($end - $start < 5) {
                                                            $start = max($end - 5, 1);
                                                        }
                                                    @endphp

                                                    @for ($i = $start; $i <= $end; $i++)
                                                        <button
                                                            class="dt-paging-button {{ $current == $i ? 'current' : '' }}"
                                                            type="button" aria-controls="example"
                                                            data-dt-idx="{{ $i }}"
                                                            {{ $current == $i ? 'aria-current=page' : '' }}
                                                            onclick="window.location='{{ $deals->url($i) }}'">{{ $i }}</button>
                                                    @endfor

                                                    {{-- Next --}}
                                                    <button
                                                        class="dt-paging-button next {{ !$deals->hasMorePages() ? 'disabled' : '' }}"
                                                        type="button" aria-label="Next"
                                                        onclick="window.location='{{ $deals->nextPageUrl() }}'"
                                                        {{ !$deals->hasMorePages() ? 'disabled aria-disabled=true tabindex=-1' : '' }}>›</button>

                                                    {{-- Last --}}
                                                    <button
                                                        class="dt-paging-button last {{ !$deals->hasMorePages() ? 'disabled' : '' }}"
                                                        type="button" aria-label="Last"
                                                        onclick="window.location='{{ $deals->url($last) }}'"
                                                        {{ !$deals->hasMorePages() ? 'disabled aria-disabled=true tabindex=-1' : '' }}>»</button>

                                                </nav>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
    @push('old-datatable-js')
        <script>
            new DataTable('#deals-list-table', {
                info: false,
                paging: false,
                pageLength: 100,
                scrollY: 800,
                scrollX: true,
                autoWidth: true,
                columnDefs: [{
                    width: 90,
                    targets: 0
                }, {
                    className: 'dt-left',
                    targets: '_all'
                }],
                scrollCollapse: true,
                dom: '<"dt-layout-row"<"dt-layout-cell dt-layout-end"f>>t',
                language: {
                    searchPlaceholder: "Search here...",
                    search: "",
                }
            });
        </script>
    @endpush
@endsection
