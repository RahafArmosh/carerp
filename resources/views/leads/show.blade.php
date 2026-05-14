@extends('layouts.admin')
@section('page-title')
    {{ $lead->name }}
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
    <link rel="stylesheet" href="{{ asset('assets/css/plugins/dropzone.min.css') }}">
    <style>
        .card h5 {
            word-break: break-all;
            white-space: normal;
        }
    </style>
@endpush
@push('script-page')
    <script src="{{ asset('css/summernote/summernote-bs4.js') }}"></script>
    <script src="{{ asset('assets/js/plugins/dropzone-amd-module.min.js') }}"></script>
    <script src="{{ asset('js/jquery.repeater.min.js') }}"></script>

    <script>
        var scrollSpy = new bootstrap.ScrollSpy(document.body, {
            target: '#lead-sidenav',
            offset: 300
        })
        Dropzone.autoDiscover = false;
        Dropzone.autoDiscover = false;
        myDropzone = new Dropzone("#dropzonewidget", {
            maxFiles: 20,
            // maxFilesize: 2000,
            parallelUploads: 1,
            filename: false,
            // acceptedFiles: ".jpeg,.jpg,.png,.pdf,.doc,.txt",
            url: "{{ route('leads.file.upload', $lead->id) }}",
            success: function(file, response) {
                if (response.is_success) {
                    if (response.status == 1) {
                        show_toastr('success', response.success_msg, 'success');
                    }
                    dropzoneBtn(file, response);
                } else {
                    myDropzone.removeFile(file);
                    show_toastr('error', response.error, 'error');
                }
            },
            error: function(file, response) {
                myDropzone.removeFile(file);
                if (response.error) {
                    show_toastr('error', response.error, 'error');
                } else {
                    show_toastr('error', response, 'error');
                }
            }
        });
        myDropzone.on("sending", function(file, xhr, formData) {
            formData.append("_token", $('meta[name="csrf-token"]').attr('content'));
            formData.append("lead_id", {{ $lead->id }});
        });

        function dropzoneBtn(file, response) {
            var download = document.createElement('a');
            download.setAttribute('href', response.download);
            download.setAttribute('class', "badge bg-info mx-1");
            download.setAttribute('data-toggle', "tooltip");
            download.setAttribute('data-original-title', "{{ __('Download') }}");
            download.innerHTML = "<i class='ti ti-download'></i>";

            var del = document.createElement('a');
            del.setAttribute('href', response.delete);
            del.setAttribute('class', "badge bg-danger mx-1");
            del.setAttribute('data-toggle', "tooltip");
            del.setAttribute('data-original-title', "{{ __('Delete') }}");
            del.innerHTML = "<i class='ti ti-trash'></i>";

            del.addEventListener("click", function(e) {
                e.preventDefault();
                e.stopPropagation();
                const swalWithBootstrapButtons = Swal.mixin({
                    customClass: {
                        confirmButton: "btn btn-success",
                        cancelButton: "btn btn-danger",
                    },
                    buttonsStyling: false,
                });
                swalWithBootstrapButtons.fire({
                    title: "Are you sure?",
                    text: "This action can not be undone. Do you want to continue?",
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonText: "Yes",
                    cancelButtonText: "No",
                    reverseButtons: true,
                }).then((result) => {
                    if (result.isConfirmed) {
                        var btn = $(del); // Use the del element directly
                        $.ajax({
                            url: btn.attr('href'),
                            data: {
                                _token: $('meta[name="csrf-token"]').attr('content')
                            },
                            type: 'DELETE',
                            success: function(response) {
                                if (response.is_success) {
                                    myDropzone.removeFile(file);
                                    show_toastr('success', response.success_msg ||
                                        'File deleted successfully', 'success');
                                } else {
                                    show_toastr('error', response.error || 'Delete failed',
                                        'error');
                                }
                            },
                            error: function(response) {
                                response = response.responseJSON;
                                show_toastr('error', (response && response.error) ? response
                                    .error : 'Delete failed', 'error');
                            }
                        });
                    }
                });
            });

            var html = document.createElement('div');
            html.appendChild(download);
            @if (Auth::user()->type != 'client')
                @can('edit lead')
                    html.appendChild(del);
                @endcan
            @endif

            file.previewTemplate.appendChild(html);
        }

        @foreach ($lead->files as $file)
            @if (file_exists(storage_path('app/public/lead_files/' . $file->file_path)))

                var mockFile = {
                    name: "{{ $file->file_name }}",
                    size: {{ \File::size(storage_path('app/public/lead_files/' . $file->file_path)) }}
                };
                myDropzone.emit("addedfile", mockFile);
                // myDropzone.emit("thumbnail", mockFile, "{{ asset('storage/lead_files/' . $file->file_path) }}");
                myDropzone.emit("complete", mockFile);

                dropzoneBtn(mockFile, {
                    download: "{{ route('leads.file.download', [$lead->id, $file->id]) }}",
                    delete: "{{ route('leads.file.delete', [$lead->id, $file->id]) }}"
                });
            @endif
        @endforeach
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.whatsapp-link').forEach(function(link) {
                link.addEventListener('click', function(e) {
                    e.preventDefault();

                    const leadId = this.dataset.leadId;
                    const whatsappUrl = this.href;

                    fetch(`/leads/${leadId}/update-stage`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        }
                    }).then(response => {
                        window.open(whatsappUrl, '_blank');
                    }).catch(error => {
                        console.error('Stage update failed:', error);
                        window.open(whatsappUrl, '_blank');
                    });
                });
            });
        });

        function saveNote() {
            var note = $('[name="note"]').summernote('code');


            $.ajax({
                url: "{{ route('leads.note.store', $lead->id) }}",
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    notes: note
                },
                type: 'POST',
                success: function(response) {
                    if (response.is_success) {
                        show_toastr('Success', response.success, 'success');
                    } else {
                        show_toastr('error', response.error, 'error');
                    }
                },
                error: function(response) {
                    response = response.responseJSON;
                    if (response.is_success) {
                        show_toastr('error', response.error, 'error');
                    } else {
                        show_toastr('error', response, 'error');
                    }
                }
            });
        }
    </script>
@endpush
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('leads.index') }}">{{ __('Lead') }}</a></li>
    <li class="breadcrumb-item"> {{ $lead->name }}</li>
@endsection
@section('action-btn')
    <div class="float-end">
        @can('convert lead to deal')
            @if (!empty($deal))
                <a href="@can('View Deal') @if ($deal->is_active) {{ route('deals.show', $deal->id) }} @else # @endif @else # @endcan"
                    data-size="lg" data-bs-toggle="tooltip" title=" {{ __('Already Converted To Deal') }}"
                    class="btn btn-sm btn-primary">
                    <i class="ti ti-exchange"></i>{{ __('Already Converted To Deal') }}
                </a>
            @else
                <a href="#" data-size="lg" data-url="{{ URL::to('leads/' . $lead->id . '/show_convert') }}"
                    data-ajax-popup="true" data-bs-toggle="tooltip"
                    title="{{ __('Convert [' . $lead->subject . '] To Deal') }}" class="btn btn-sm btn-primary">
                    <i class="ti ti-exchange"></i>{{ __('Convert [' . $lead->subject . '] To Deal') }}
                </a>
            @endif
        @endcan

        <a href="#" data-url="{{ URL::to('leads/' . $lead->id . '/labels') }}" data-ajax-popup="true" data-size="lg"
            data-bs-toggle="tooltip" title="{{ __('Label') }}" class="btn btn-sm btn-primary">
            <i class="ti ti-bookmark"></i>
        </a>
        <a href="#" data-size="lg" data-url="{{ route('leads.edit', $lead->id) }}" data-ajax-popup="true"
            data-bs-toggle="tooltip" title="{{ __('Edit') }}" class="btn btn-sm btn-primary">
            <i class="ti ti-pencil"></i>
        </a>
    </div>
@endsection

@section('content')
    <div class="row">
        <div class="col-sm-12">
            <div class="row">
                <div class="col-xl-3">
                    <div class="card sticky-top" style="top:30px">
                        <div class="list-group list-group-flush" id="lead-sidenav">
                            @if (Auth::user()->type != 'client')
                                <a href="#general"
                                    class="list-group-item list-group-item-action border-0">{{ __('General') }}
                                    <div class="float-end"><i class="ti ti-chevron-right"></i></div>
                                </a>
                            @endif

                            @if (Auth::user()->type != 'client')
                                <a href="#users_products"
                                    class="list-group-item list-group-item-action border-0">{{ __('Users') . ' | ' . __('Products') }}
                                    <div class="float-end"><i class="ti ti-chevron-right"></i></div>
                                </a>
                            @endif

                            @if (Auth::user()->type != 'client')
                                <a href="#sources_emails"
                                    class="list-group-item list-group-item-action border-0">{{ __('Sources') . ' | ' . __('Emails') }}
                                    <div class="float-end"><i class="ti ti-chevron-right"></i></div>
                                </a>
                            @endif
                            @if (Auth::user()->type != 'client')
                                <a href="#discussion_note"
                                    class="list-group-item list-group-item-action border-0">{{ __('Discussion') . ' | ' . __('Notes') }}
                                    <div class="float-end"><i class="ti ti-chevron-right"></i></div>
                                </a>
                            @endif
                            @if (Auth::user()->type != 'client')
                                <a href="#files"
                                    class="list-group-item list-group-item-action border-0">{{ __('Files') }}
                                    <div class="float-end"><i class="ti ti-chevron-right"></i></div>
                                </a>
                            @endif
                            @if (Auth::user()->type != 'client')
                                <a href="#calls"
                                    class="list-group-item list-group-item-action border-0">{{ __('Calls') }}
                                    <div class="float-end"><i class="ti ti-chevron-right"></i></div>
                                </a>
                            @endif
                            @if (Auth::user()->type != 'client')
                                <a href="#activity"
                                    class="list-group-item list-group-item-action border-0">{{ __('Activity') }}
                                    <div class="float-end"><i class="ti ti-chevron-right"></i></div>
                                </a>
                            @endif

                        </div>
                    </div>
                </div>
                <div class="col-xl-9">
                    <?php
                    $products = $lead->leadProducts()->get();
                    $sources = $lead->sources();
                    $calls = $lead->calls;
                    $emails = $lead->emails;
                    ?>
                    <div id="general" class="card">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4 col-sm-4">
                                    <div class="d-flex align-items-start">
                                        <div class="theme-avtar bg-primary">
                                            <i class="ti ti-mail"></i>
                                        </div>
                                        <div class="ms-2">
                                            <p class="text-muted mb-0 text-sm">{{ __('Email') }}</p>
                                            <h5 class="text-primary mb-0">{{ !empty($lead->email) ? $lead->email : '' }}
                                            </h5>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4 col-sm-4">
                                    <div class="d-flex align-items-start">
                                        <div class="theme-avtar bg-warning">
                                            <i class="ti ti-phone"></i>
                                        </div>
                                        <div class="ms-2">
                                            <p class="text-muted mb-0 text-sm">{{ __('Phone') }}</p>
                                            <h5 class="text-warning mb-0">{{ !empty($lead->phone) ? $lead->phone : '' }}
                                            </h5>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4 col-sm-4">
                                    <div class="d-flex align-items-start">
                                        <div class="theme-avtar bg-info">
                                            <i class="ti ti-test-pipe"></i>
                                        </div>
                                        <div class="ms-2">
                                            <p class="text-muted mb-0 text-sm">{{ __('Pipeline') }}</p>
                                            <h5 class="text-info mb-0">{{ optional($lead->pipeline)->name ?? '-' }}
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4 col-sm-4 mt-4">
                                    <div class="d-flex align-items-start">
                                        <div class="theme-avtar bg-primary">
                                            <i class="ti ti-server"></i>
                                        </div>
                                        <div class="ms-2">
                                            <p class="text-muted mb-0 text-sm">{{ __('Stage') }}</p>
                                            <h5 class="text-primary mb-0">{{ optional($lead->stage)->name ?? '-' }}</h5>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4 col-sm-4 mt-4">
                                    <div class="d-flex align-items-start">
                                        <div class="theme-avtar bg-warning">
                                            <i class="ti ti-calendar"></i>
                                        </div>
                                        <div class="ms-2">
                                            <p class="text-muted mb-0 text-sm">{{ __('Created') }}</p>
                                            <h5 class="text-warning mb-0">
                                                {{ \Auth::user()->dateFormat($lead->created_at) }}</h5>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4 col-sm-4 mt-4">
                                    <div class="d-flex align-items-start">
                                        <div class="theme-avtar bg-info">
                                            <i class="ti ti-chart-bar"></i>
                                        </div>
                                        <div class="ms-2">
                                            <h3 class="text-info mb-0">{{ $precentage }}%</h3>
                                            <div class="progress mb-0">
                                                <div class="progress-bar bg-info" style="width: {{ $precentage }}%;">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @if (Auth::user()->getRoleNames()->first() == 'company')
                                    <div class="col-md-4 col-sm-4 mt-4">
                                        <div class="d-flex align-items-start">
                                            <div class="theme-avtar bg-primary">
                                                <i class="ti ti-code"></i>
                                            </div>
                                            <div class="ms-2">
                                                <p class="text-muted mb-0 text-sm">{{ __('gclid') }}</p>
                                                <h5 class="text-primary mb-0">
                                                    {{ !empty($lead->gclid) ? $lead->gclid : '' }}
                                                </h5>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                                <div class="col-md-4 col-sm-4 mt-4">
                                    <div class="d-flex align-items-start">
                                        <div class="theme-avtar bg-warning">
                                            <i class="ti ti-windmill"></i>
                                        </div>
                                        <div class="ms-2">
                                            <p class="text-muted mb-0 text-sm">{{ __('source') }}</p>
                                            <h5 class="text-warning mb-0">
                                                {{ !empty($lead->source)
                                                    ? (Auth::user()->getRoleNames()->first() == 'company'
                                                        ? $lead->source
                                                        : 'Marketing')
                                                    : '' }}
                                            </h5>
                                        </div>
                                    </div>
                                </div>
                                @if (Auth::user()->getRoleNames()->first() == 'company')
                                    <div class="col-md-4 col-sm-4 mt-4">
                                        <div class="d-flex align-items-start">
                                            <div class="theme-avtar bg-info">
                                                <i class="ti ti-link"></i>
                                            </div>
                                            <div class="ms-2">
                                                <p class="text-muted mb-0 text-sm">{{ __('source_url') }}</p>
                                                {{-- <div class="progress mb-0"> --}}
                                                <h5 class="text-warning mb-0">
                                                    {{ !empty($lead->source_url) ? $lead->source_url : '' }}</h5>
                                                {{--
                                        </div> --}}
                                            </div>
                                        </div>
                                    </div>
                                @endif
                                <div class="col-md-4 col-sm-4 mt-4">
                                    <div class="d-flex align-items-start">
                                        <div class="theme-avtar bg-success">
                                            <!-- WhatsApp icon -->
                                            <i class="fab fa-whatsapp"></i>
                                        </div>
                                        <div class="ms-2">
                                            <p class="text-muted mb-0 text-sm">{{ __('Whatsapp') }}</p>
                                            <h5 class="text-success mb-0">
                                                @if (!empty($lead->whatsapp))
                                                    @php
                                                        // Remove everything except numbers
                                                        $whatsapp = preg_replace('/\D+/', '', $lead->whatsapp);

                                                        // If number already starts with country code (971), use as is
                                                        // If number starts with 0, replace with country code (UAE = 971)
                                                        // Otherwise, assume it's already in correct format
                                                        if (substr($whatsapp, 0, 3) === '971') {
                                                            // Already has country code, use as is
                                                            $formattedNumber = $whatsapp;
                                                        } elseif (substr($whatsapp, 0, 1) === '0') {
                                                            // Local number starting with 0, replace with country code
                                                            $formattedNumber = '971' . substr($whatsapp, 1);
                                                        } else {
                                                            // Assume it's already in international format or local without 0
                                                            $formattedNumber = $whatsapp;
                                                        }
                                                    @endphp

                                                    <a href="https://wa.me/{{ $formattedNumber }}"
                                                    class="whatsapp-link text-success"
                                                    data-lead-id="{{ $lead->id }}"
                                                    target="_blank"
                                                    rel="noopener"
                                                    style="text-decoration: none;">
                                                        {{ $lead->whatsapp }}
                                                    </a>
                                                @endif

                                            </h5>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4 col-sm-4 mt-4">
                                    <div class="d-flex align-items-start">
                                        <div class="theme-avtar bg-info">
                                            <i class="ti ti-link"></i>
                                        </div>
                                        <div class="ms-2">
                                            <p class="text-muted mb-0 text-sm">{{ __('Company Name') }}</p>
                                            {{-- <div class="progress mb-0"> --}}
                                            <h5 class="text-warning mb-0">
                                                {{ !empty($lead->subject) ? $lead->subject : '' }}</h5>
                                            {{--
                                        </div> --}}
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4 col-sm-4 mt-4">
                                    <div class="d-flex align-items-start">
                                        <div class="theme-avtar bg-warning">
                                            <i class="ti ti-businessplan"></i>
                                        </div>
                                        <div class="ms-2">
                                            <p class="text-muted mb-0 text-sm">{{ __('Country Name') }}</p>
                                            {{-- <div class="progress mb-0"> --}}
                                            <h5 class="text-warning mb-0">
                                                {{ !empty($lead->country) ? $lead->country : '' }}</h5>
                                            {{--
                                        </div> --}}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm-4">
                            <div class="card">
                                <div class="card-body">
                                    <div class="row align-items-center justify-content-between">
                                        <div class="mb-sm-0 col-auto mb-3">
                                            <small class="text-muted">{{ __('Product') }}</small>
                                            <h3 class="m-0">{{ count($products) }}</h3>
                                        </div>
                                        <div class="col-auto">
                                            <div class="theme-avtar bg-info">
                                                <i class="ti ti-shopping-cart"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="card">
                                <div class="card-body">
                                    <div class="row align-items-center justify-content-between">
                                        <div class="mb-sm-0 col-auto mb-3">
                                            <small class="text-muted">{{ __('Source') }}</small>
                                            <h3 class="m-0">{{ count($sources) }}</h3>
                                        </div>
                                        <div class="col-auto">
                                            <div class="theme-avtar bg-primary">
                                                <i class="ti ti-social"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="card">
                                <div class="card-body">
                                    <div class="row align-items-center justify-content-between">
                                        <div class="mb-sm-0 col-auto mb-3">
                                            <small class="text-muted">{{ __('Files') }}</small>
                                            <h3 class="m-0">{{ count($lead->files) }}</h3>
                                        </div>
                                        <div class="col-auto">
                                            <div class="theme-avtar bg-warning">
                                                <i class="ti ti-file"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                    <div id="users_products">
                        <div class="row">
                            <div class="col-6">
                                <div class="card">
                                    @if (Auth::user()->getRoleNames()->first() == 'company' || Auth::user()->getRoleNames()->first() == 'manager')
                                        <div class="card-header">
                                            <div class="d-flex align-items-center justify-content-between">
                                                <h5>{{ __('Users') }}</h5>
                                                <div class="float-end">
                                                    <a data-size="md"
                                                        data-url="{{ route('leads.users.edit', $lead->id) }}"
                                                        data-ajax-popup="true" data-bs-toggle="tooltip"
                                                        title="{{ __('Add User') }}" class="btn btn-sm btn-primary">
                                                        <i class="ti ti-plus text-white"></i>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table-hover mb-0 table">
                                                <thead>
                                                    <tr>
                                                        <th>{{ __('Name') }}</th>
                                                        <th>{{ __('Action') }}</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($lead->users as $user)
                                                        <tr>
                                                            <td>
                                                                <div class="d-flex align-items-center">
                                                                    <div>
                                                                        <img src="{{ URL::to('/') . '/' . 'documents' . '/' . (isset($company_logos) && !empty($company_logos) ? $company_logos : 'logo-dark.png') }}"
                                                                            alt="{{ config('app.name', 'Orbix') }}"
                                                                            class="logo logo-lg"
                                                                            style="
                                                                            height: 50px;
                                                                            width: 50px;
                                                                         }}"
                                                                            class="wid-30 rounded-circle me-3"
                                                                            alt="avatar image">
                                                                    </div>
                                                                    <p class="mb-0">{{ $user->name }}</p>
                                                                </div>
                                                            </td>
                                                            @if (Auth::user()->getRoleNames()->first() == 'company' || Auth::user()->getRoleNames()->first() == 'manager')
                                                                <td>
                                                                    <div class="action-btn bg-danger ms-2">
                                                                        <form
                                                                            action="{{ route('leads.users.destroy', [$lead->id, $user->id]) }}"
                                                                            method="POST"
                                                                            id="delete-form-{{ $lead->id }}">
                                                                            @csrf
                                                                            @method('DELETE')

                                                                            <a href="#"
                                                                                class="btn btn-sm align-items-center bs-pass-para mx-3"
                                                                                data-bs-toggle="tooltip"
                                                                                title="{{ __('Delete') }}";">
                                                                                <i class="ti ti-trash text-white"></i>
                                                                            </a>
                                                                        </form>

                                                                    </div>
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
                            <div class="col-6">
                                <div class="card">
                                    <div class="card-header">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <h5>{{ __('Products') }}</h5>
                                            <div class="float-end">
                                                <a data-size="lg"
                                                    data-url="{{ route('leads.products.edit', $lead->id) }}"
                                                    data-ajax-popup="true" data-bs-toggle="tooltip"
                                                    title="{{ __('Add Product') }}" class="btn btn-sm btn-primary">
                                                    <i class="ti ti-plus text-white"></i>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            @php
                                                $totalQuantity = 0;
                                                $totalAmount = 0;
                                            @endphp
                                            <table class="table-hover mb-0 table">
                                                <thead>
                                                    <tr>
                                                        <th>{{ __('Name') }}</th>
                                                        <th>{{ __('Price') }}</th>
                                                        <th>{{ __('QTY') }}</th>
                                                        <th>{{ __('Action') }}</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($lead->leadProducts()->get() as $product)
                                                        @php
                                                            $amount = $product->price * $product->quantity;
                                                            $totalQuantity += $product->quantity;
                                                            $totalAmount += $amount;
                                                        @endphp
                                                        <tr>
                                                            <td>
                                                                {{ $product->product->category->name .
                                                                    '/' .
                                                                    $product->product->brand->name .
                                                                    '/' .
                                                                    $product->product->subBrand->name .
                                                                    '/' .
                                                                    $product->product->name }}
                                                            </td>
                                                            <td>
                                                                {{ \Auth::user()->priceFormat($product->price * $product->quantity) }}
                                                            </td>
                                                            <td>
                                                                {{ $product->quantity }}
                                                            </td>
                                                            @can('edit lead')
                                                                <td>
                                                                    <div class="action-btn bg-danger ms-2">
                                                                        <form
                                                                            action="{{ route('leads.products.destroy', [$lead->id, $product->product_id]) }}"
                                                                            method="POST">
                                                                            @csrf
                                                                            @method('DELETE')

                                                                            <a href="#"
                                                                                class="btn btn-sm align-items-center bs-pass-para mx-3"
                                                                                data-bs-toggle="tooltip"
                                                                                title="{{ __('Delete') }}"">
                                                                                <i class="ti ti-trash text-white"></i>
                                                                            </a>
                                                                        </form>
                                                                    </div>
                                                                </td>
                                                            @endcan
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                                <tfoot>
                                                    <tr>
                                                        <th>{{ __('Total') }}</th>
                                                        <th>{{ \Auth::user()->priceFormat($totalAmount) }}</th>
                                                        <th>{{ $totalQuantity }}</th>
                                                        <th></th>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>

                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div id="sources_emails">
                        <div class="row">
                            <div class="col-6">
                                <div class="card">
                                    <div class="card-header">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <h5>{{ __('Sources') }}</h5>
                                            <div class="float-end">
                                                <a data-size="md" data-url="{{ route('leads.sources.edit', $lead->id) }}"
                                                    data-ajax-popup="true" data-bs-toggle="tooltip"
                                                    title="{{ __('Add Source') }}" class="btn btn-sm btn-primary">
                                                    <i class="ti ti-plus text-white"></i>
                                                </a>
                                            </div>
                                        </div>

                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table-hover mb-0 table">
                                                <thead>
                                                    <tr>
                                                        <th>{{ __('Name') }}</th>
                                                        <th>{{ __('Action') }}</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($sources as $source)
                                                        <tr>
                                                            <td>{{ $source->name }} </td>
                                                            @can('edit lead')
                                                                <td>
                                                                    <div class="action-btn bg-danger ms-2">
                                                                        <form
                                                                            action="{{ route('leads.sources.destroy', [$lead->id, $source->id]) }}"
                                                                            method="POST"
                                                                            id="delete-form-{{ $lead->id }}">
                                                                            @csrf
                                                                            @method('DELETE')

                                                                            <a href="#"
                                                                                class="btn btn-sm align-items-center bs-pass-para mx-3"
                                                                                data-bs-toggle="tooltip"
                                                                                title="{{ __('Delete') }}"">
                                                                                <i class="ti ti-trash text-white"></i>
                                                                            </a>
                                                                        </form>
                                                                    </div>
                                                                </td>
                                                            @endcan
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="card">
                                    <div class="card-header">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <h5>{{ __('Emails') }}</h5>
                                            @can('create lead email')
                                                <div class="float-end">
                                                    <a data-size="md"
                                                        data-url="{{ route('leads.emails.create', $lead->id) }}"
                                                        data-ajax-popup="true" data-bs-toggle="tooltip"
                                                        title="{{ __('Create Email') }}" class="btn btn-sm btn-primary">
                                                        <i class="ti ti-plus text-white"></i>
                                                    </a>
                                                </div>
                                            @endcan
                                        </div>

                                    </div>
                                    <div class="card-body">
                                        <div class="list-group list-group-flush mt-2">
                                            @if (!$emails->isEmpty())
                                                @foreach ($emails as $email)
                                                    <li class="list-group-item px-0">
                                                        <div class="d-block d-sm-flex align-items-start">
                                                            <img src="{{ URL::to('/') . '/' . 'documents' . '/' . (isset($company_logos) && !empty($company_logos) ? $company_logos : 'logo-dark.png') }}"
                                                                class="img-fluid wid-40 mb-sm-0 mb-2 me-3" alt="image">
                                                            <div class="w-100">
                                                                <div
                                                                    class="d-flex align-items-center justify-content-between">
                                                                    <div class="mb-sm-0 mb-3">
                                                                        <h6 class="mb-0">{{ $email->subject }}</h6>
                                                                        <span
                                                                            class="text-muted text-sm">{{ $email->to }}</span>
                                                                    </div>
                                                                    <div
                                                                        class="form-check form-switch form-switch-right mb-2">
                                                                        {{ $email->created_at->diffForHumans() }}
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </li>
                                                @endforeach
                                            @else
                                                <li class="text-center">
                                                    {{ __(' No Emails Available.!') }}
                                                </li>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div id="discussion_note">
                        <div class="row">
                            <div class="col-6">
                                <div class="card">
                                    <div class="card-header">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <h5>{{ __('Discussion') }}</h5>
                                            <div class="float-end">
                                                <a data-size="lg"
                                                    data-url="{{ route('leads.discussions.create', $lead->id) }}"
                                                    data-ajax-popup="true" data-bs-toggle="tooltip"
                                                    title="{{ __('Add Message') }}" class="btn btn-sm btn-primary">
                                                    <i class="ti ti-plus text-white"></i>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <ul class="list-group list-group-flush mt-2">
                                            @if (!$lead->discussions->isEmpty())
                                                @foreach ($lead->discussions as $discussion)
                                                    <li class="list-group-item px-0">
                                                        <div class="d-block d-sm-flex align-items-start">
                                                            <img src="{{ URL::to('/') . '/' . 'documents' . '/' . (isset($company_logos) && !empty($company_logos) ? $company_logos : 'logo-dark.png') }}"
                                                                class="img-fluid wid-40 mb-sm-0 mb-2 me-3" alt="image">
                                                            <div class="w-100">
                                                                <div
                                                                    class="d-flex align-items-center justify-content-between">
                                                                    <div class="mb-sm-0 mb-3">
                                                                        <h6 class="mb-0"> {{ $discussion->comment }}
                                                                        </h6>
                                                                        <span
                                                                            class="text-muted text-sm">{{ $discussion->user->name }}</span>
                                                                    </div>
                                                                    <div
                                                                        class="form-check form-switch form-switch-right mb-2">
                                                                        {{ $discussion->created_at->diffForHumans() }}
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </li>
                                                @endforeach
                                            @else
                                                <li class="text-center">
                                                    {{ __(' No Data Available.!') }}
                                                </li>
                                            @endif
                                        </ul>
                                    </div>
                                    <div class="card-body">
                                        <h5>{{ __('Message') }}</h5>
                                        <textarea class="summernote-simple" name="message">{!! $lead->message !!}</textarea>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="card">
                                    <div class="card-header">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <h5>{{ __('Notes') }}</h5>
                                            @php
                                                $user = \App\Models\User::find(\Auth::user()->creatorId());
                                                $plan = \App\Models\Plan::getPlan($user->plan);
                                            @endphp
                                            @if ($plan->chatgpt == 1)
                                                <div class="float-end">
                                                    <a href="#" data-size="md"
                                                        class="btn btn-primary btn-icon btn-sm"
                                                        data-ajax-popup-over="true" id="grammarCheck"
                                                        data-url="{{ route('grammar', ['grammar']) }}"
                                                        data-bs-placement="top"
                                                        data-title="{{ __('Grammar check with AI') }}">
                                                        <i class="ti ti-rotate"></i>
                                                        <span>{{ __('Grammar check with AI') }}</span>
                                                    </a>
                                                    <a href="#" data-size="md"
                                                        class="btn btn-primary btn-icon btn-sm"
                                                        data-ajax-popup-over="true"
                                                        data-url="{{ route('generate', ['lead']) }}"
                                                        data-bs-placement="top"
                                                        data-title="{{ __('Generate content with AI') }}">
                                                        <i class="fas fa-robot"></i>
                                                        <span>{{ __('Generate with AI') }}</span>
                                                    </a>
                                                </div>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="card-body">
                                        <textarea class="summernote-simple" name="note">{!! $lead->notes !!}</textarea>
                                        <button type="button" id="saveNoteBtn" class="btn btn-success mt-2"
                                            onclick="saveNote()">
                                            <i class="ti ti-device-floppy"></i> {{ __('Save Note') }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div id="files" class="card">
                        <div class="card-header">
                            <h5>{{ __('Files') }}</h5>
                        </div>
                        <div class="card-body">
                            <div class="col-md-12 dropzone top-5-scroll browse-file" id="dropzonewidget"></div>
                        </div>
                    </div>
                    <div id="calls" class="card">
                        <div class="card-header">
                            <div class="d-flex align-items-center justify-content-between">
                                <h5>{{ __('Calls') }}</h5>

                                <div class="float-end">
                                    <a data-size="lg" data-url="{{ route('leads.calls.create', $lead->id) }}"
                                        data-ajax-popup="true" data-bs-toggle="tooltip" title="{{ __('Add Call') }}"
                                        class="btn btn-sm btn-primary">
                                        <i class="ti ti-plus text-white"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table-hover mb-0 table">
                                    <thead>
                                        <tr>
                                            <th width="">{{ __('Subject') }}</th>
                                            <th>{{ __('Call Type') }}</th>
                                            <th>{{ __('Duration') }}</th>
                                            <th>{{ __('User') }}</th>
                                            <th>{{ __('Action') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($calls as $call)
                                            <tr>
                                                <td>{{ $call->subject }}</td>
                                                <td>{{ ucfirst($call->call_type) }}</td>
                                                <td>{{ $call->duration }}</td>
                                                <td>{{ isset($call->getLeadCallUser) ? $call->getLeadCallUser->name : '-' }}
                                                </td>
                                                <td>
                                                    @can('edit lead call')
                                                        <div class="action-btn bg-info ms-2">
                                                            <a href="#"
                                                                class="btn btn-sm d-inline-flex align-items-center mx-3"
                                                                data-url="{{ URL::to('leads/' . $lead->id . '/call/' . $call->id . '/edit') }}"
                                                                data-ajax-popup="true" data-size="xl"
                                                                data-bs-toggle="tooltip" title="{{ __('Edit') }}"
                                                                data-title="{{ __('Role Edit') }}">
                                                                <i class="ti ti-pencil text-white"></i>
                                                            </a>
                                                        </div>
                                                    @endcan
                                                    @can('delete lead call')
                                                        <div class="action-btn bg-danger ms-2">
                                                            <form
                                                                action="{{ route('leads.calls.destroy', [$lead->id, $call->id]) }}"
                                                                method="POST" id="delete-form-{{ $lead->id }}">
                                                                @csrf
                                                                @method('DELETE')

                                                                <a href="#"
                                                                    class="btn btn-sm align-items-center bs-pass-para mx-3"
                                                                    data-bs-toggle="tooltip" title="{{ __('Delete') }}">
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
                    </div>
                    <div id="activity" class="card">
                        <div class="card-header">
                            <h5>{{ __('Activity') }}</h5>
                        </div>
                        <div class="card-body">

                            <div class="row leads-scroll">
                                <ul class="event-cards list-group list-group-flush w-100 mt-3">
                                    @if (!$lead->activities->isEmpty())
                                        @foreach ($lead->activities()->get() as $activity)
                                            <?php $activity = clone $activity; ?>
                                            <li class="list-group-item card mb-3">
                                                <div class="row align-items-center justify-content-between">
                                                    <div class="mb-sm-0 col-auto mb-3">
                                                        <div class="d-flex align-items-center">
                                                            <div class="theme-avtar bg-primary">
                                                                <i class="ti {{ $activity->logIcon() }}"></i>
                                                            </div>
                                                            <div class="ms-3">
                                                                <span
                                                                    class="text-dark text-sm">{{ __($activity->log_type) }}</span>
                                                                <h6 class="m-0">{!! $activity->getLeadRemark() !!}</h6>
                                                                <small
                                                                    class="text-muted">{{ $activity->created_at->diffForHumans() }}</small>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-auto">

                                                    </div>
                                                </div>
                                            </li>
                                        @endforeach
                                    @else
                                        No activity found yet.
                                    @endif
                                </ul>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
