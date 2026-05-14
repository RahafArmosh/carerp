@extends('layouts.admin')
@section('page-title')
    {{ $deal->name }}
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
@endpush
@push('script-page')
    <script src="{{ asset('css/summernote/summernote-bs4.js') }}"></script>
    <script src="{{ asset('assets/js/plugins/dropzone-amd-module.min.js') }}"></script>
    <script src="{{ asset('js/jquery.repeater.min.js') }}"></script>

    <script>
        var scrollSpy = new bootstrap.ScrollSpy(document.body, {
            target: '#deal-sidenav',
            offset: 300
        })
        Dropzone.autoDiscover = false;
        Dropzone.autoDiscover = false;
        myDropzone = new Dropzone("#dropzonewidget", {
            maxFiles: 20,
            // maxFilesize: 20,
            parallelUploads: 1,
            filename: false,
            // acceptedFiles: ".jpeg,.jpg,.png,.pdf,.doc,.txt",
            url: "{{ route('deals.file.upload', $deal->id) }}",
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
                    show_toastr('error', response.error, 'error');
                }
            }
        });
        myDropzone.on("sending", function(file, xhr, formData) {
            formData.append("_token", $('meta[name="csrf-token"]').attr('content'));
            formData.append("deal_id", {{ $deal->id }});
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
                if (confirm("Are you sure ?")) {
                    var btn = $(this);
                    $.ajax({
                        url: btn.attr('href'),
                        data: {
                            _token: $('meta[name="csrf-token"]').attr('content')
                        },
                        type: 'DELETE',
                        success: function(response) {
                            if (response.is_success) {
                                btn.closest('.dz-image-preview').remove();
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
                    })
                }
            });

            var html = document.createElement('div');
            html.appendChild(download);
            @if (Auth::user()->type != 'client')
                @can('edit deal')
                    html.appendChild(del);
                @endcan
            @endif

            file.previewTemplate.appendChild(html);
        }

        @foreach ($deal->files as $file)
            @if (file_exists(storage_path('app/public/deal_files/' . $file->file_path)))
                // Create the mock file:
                var mockFile = {
                    name: "{{ $file->file_name }}",
                    size: {{ \File::size(storage_path('app/public/deal_files/' . $file->file_path)) }}
                };
                // Call the default addedfile event handler
                myDropzone.emit("addedfile", mockFile);
                // And optionally show the thumbnail of the file:
                // myDropzone.emit("thumbnail", mockFile, "{{ asset(storage_path('app/public/deal_files/' . $file->file_path)) }}");
                myDropzone.emit("complete", mockFile);

                dropzoneBtn(mockFile, {
                    download: "{{ route('deals.file.download', [$deal->id, $file->id]) }}",
                    delete: "{{ route('deals.file.delete', [$deal->id, $file->id]) }}"
                });
            @endif
        @endforeach

        @can('edit deal')
            $('.summernote-simple').on('summernote.blur', function() {

                $.ajax({
                    url: "{{ route('deals.note.store', $deal->id) }}",
                    data: {
                        _token: $('meta[name="csrf-token"]').attr('content'),
                        notes: $(this).val()
                    },
                    type: 'POST',
                    success: function(response) {
                        if (response.is_success) {
                            // show_toastr('Success', response.success,'success');
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
                })
            });
        @else
            $('.summernote-simple').summernote('disable');
        @endcan

        @can('edit task')
            $(document).on("click", ".task-checkbox", function() {
                var chbox = $(this);
                var lbl = chbox.parent().parent().find('label');

                $.ajax({
                    url: chbox.attr('data-url'),
                    data: {
                        _token: $('meta[name="csrf-token"]').attr('content'),
                        status: chbox.val()
                    },
                    type: 'PUT',
                    success: function(response) {
                        if (response.is_success) {
                            chbox.val(response.status);
                            if (response.status) {
                                lbl.addClass('strike');
                                lbl.find('.badge').removeClass('badge-warning').addClass(
                                    'badge-success');
                            } else {
                                lbl.removeClass('strike');
                                lbl.find('.badge').removeClass('badge-success').addClass(
                                    'badge-warning');
                            }
                            lbl.find('.badge').html(response.status_label);

                            show_toastr('success', response.success);
                        } else {
                            show_toastr('error', response.error);
                        }
                    },
                    error: function(response) {
                        response = response.responseJSON;
                        if (response.is_success) {
                            show_toastr('success', response.success);
                        } else {
                            show_toastr('error', response.error);
                        }
                    }
                })
            });
        @endcan
    </script>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            document.querySelectorAll(".editProductBtn").forEach(function (btn) {
                btn.addEventListener("click", function () {
                    let productId = this.getAttribute("data-id");
                    let dealId = this.getAttribute("data-deal");
                    let url = `/deals/${dealId}/products/${productId}/edit1`; // adjust to your route if needed
    
                    // Show loading text
                    document.getElementById("editProductFormContainer").innerHTML = "<p class='text-muted'>Loading...</p>";
    
                    // Fetch form via AJAX
                    fetch(url)
                        .then(response => response.text())
                        .then(html => {
                            document.getElementById("editProductFormContainer").innerHTML = html;
                        })
                        .catch(error => {
                            document.getElementById("editProductFormContainer").innerHTML = "<p class='text-danger'>Error loading form.</p>";
                            console.error(error);
                        });
                });
            });
        });
    </script>
@endpush
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('deals.index') }}">{{ __('Deal') }}</a></li>
    <li class="breadcrumb-item"> {{ $deal->name }}</li>
@endsection
@section('action-btn')
    <div class="float-end">
        @can('convert deal to deal')
            @if (!empty($deal))
                <a href="@can('View Deal') @if ($deal->is_active) {{ route('deals.show', $deal->id) }} @else # @endif @else # @endcan"
                    data-size="lg" data-bs-toggle="tooltip" title=" {{ __('Already Converted To Deal') }}"
                    class="btn btn-sm btn-primary">
                    <i class="ti ti-exchange"></i>
                </a>
            @else
                <a href="#" data-size="lg" data-url="{{ URL::to('deals/' . $deal->id . '/show_convert') }}"
                    data-bs-toggle="tooltip" title="{{ __('Convert [' . $deal->subject . '] To Deal') }}"
                    class="btn btn-sm btn-primary">
                    <i class="ti ti-exchange"></i>
                </a>
            @endif
        @endcan
        <a href="#" data-url="{{ URL::to('deals/' . $deal->id . '/labels') }}" data-ajax-popup="true" data-size="lg"
            data-bs-toggle="tooltip" title="{{ __('Label') }}" class="btn btn-sm btn-primary">
            <i class="ti ti-bookmark"></i>
        </a>
        <a href="#" data-size="lg" data-url="{{ route('deals.edit', $deal->id) }}" data-ajax-popup="true"
            data-bs-toggle="tooltip" title="{{ __('Edit') }}" class="btn btn-sm btn-primary">
            <i class="ti ti-pencil"></i>
        </a>
        <a href="{{ !empty($deal->lead_id) ? route('leads.show', $deal->lead_id) : '#' }}" data-size="lg"
            data-bs-toggle="tooltip" title="{{ __('View Lead') }}" class="btn btn-sm btn-primary">
            <i class="ti ti-eye text-white"></i> View Lead
        </a>
    </div>
@endsection

@section('content')
    <div class="row">
        <div class="col-sm-12">
            <div class="row">
                <div class="col-xl-3">
                    <div class="card sticky-top" style="top:30px">
                        <div class="list-group list-group-flush" id="deal-sidenav">

                            <a href="#general" class="list-group-item list-group-item-action border-0">{{ __('General') }}
                                <div class="float-end"><i class="ti ti-chevron-right"></i></div>
                            </a>

                            <a href="#tasks" class="list-group-item list-group-item-action border-0">{{ __('Task') }}
                                <div class="float-end"><i class="ti ti-chevron-right"></i></div>
                            </a>

                            <a href="#users_products"
                                class="list-group-item list-group-item-action border-0">{{ __('Users') . ' | ' . __('Products') }}
                                <div class="float-end"><i class="ti ti-chevron-right"></i></div>
                            </a>

                            <a href="#sources_emails"
                                class="list-group-item list-group-item-action border-0">{{ __('Sources') . ' | ' . __('Emails') }}
                                <div class="float-end"><i class="ti ti-chevron-right"></i></div>
                            </a>

                            <a href="#discussion_note"
                                class="list-group-item list-group-item-action border-0">{{ __('Discussion') . ' | ' . __('Notes') }}
                                <div class="float-end"><i class="ti ti-chevron-right"></i></div>
                            </a>

                            <a href="#files" class="list-group-item list-group-item-action border-0">{{ __('Files') }}
                                <div class="float-end"><i class="ti ti-chevron-right"></i></div>
                            </a>

                            <a href="#calls" class="list-group-item list-group-item-action border-0">{{ __('Calls') }}
                                <div class="float-end"><i class="ti ti-chevron-right"></i></div>
                            </a>

                            <a href="#activity"
                                class="list-group-item list-group-item-action border-0">{{ __('Activity') }}
                                <div class="float-end"><i class="ti ti-chevron-right"></i></div>
                            </a>

                        </div>
                    </div>
                </div>
                <div class="col-xl-9">
                    <?php
                    $tasks = $deal->tasks;
                    $products = $deal->dealProducts()->with('currency')->get();
                    $sources = $deal->sources();
                    $calls = $deal->calls;
                    $emails = $deal->emails;
                    $lead = $deal->lead;
                    ?>
                    @if ($lead)
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
                                                <h5 class="text-primary mb-0">
                                                    {{ !empty($lead->email) ? $lead->email : '' }}
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
                                                <h5 class="text-warning mb-0">
                                                    {{ !empty($lead->phone) ? $lead->phone : '' }}
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
                                                <h5 class="text-info mb-0">{{ $lead->pipeline->name }}</h5>
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
                                                <h5 class="text-primary mb-0">{{ $lead->stage->name }}</h5>
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
                                                    {{ !empty($lead->source) ? (Auth::user()->getRoleNames()->first() == 'company' ? $lead->source : 'Marketing') : '' }}
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
                                                <p class="text-muted mb-0 text-sm">{{ __('source_url') }}</p>
                                                {{-- <div class="progress mb-0"> --}}
                                                <h5 class="text-warning mb-0">
                                                    {{ !empty($lead->source_url) ? $lead->source_url : '' }}</h5>
                                                {{-- </div> --}}
                                            </div>
                                        </div>
                                    </div>
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
                                                        <?php
                                                            $whatsapp = preg_replace('/\D+/', '', $lead->whatsapp);
                                                            if (substr($whatsapp, 0, 3) == '971') {
                                                                $formattedNumber = $whatsapp;
                                                            } elseif (substr($whatsapp, 0, 1) == '0') {
                                                                $formattedNumber = '971' . substr($whatsapp, 1);
                                                            } else {
                                                                $formattedNumber = $whatsapp;
                                                            }
                                                        ?>
                                                        <a href="https://wa.me/{{ $formattedNumber }}" class="whatsapp-link text-success" data-lead-id="{{ $lead->id }}" target="_blank" rel="noopener" style="text-decoration: none;">
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
                                                {{-- </div> --}}
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4 col-sm-4 mt-4">
                                        <div class="d-flex align-items-start">
                                            <div class="theme-avtar bg-info">
                                                <i class="ti ti-user"></i>
                                            </div>
                                            <div class="ms-2">
                                                <p class="text-muted mb-0 text-sm">{{ __('Name') }}</p>
                                                {{-- <div class="progress mb-0"> --}}
                                                <h5 class="text-warning mb-0">
                                                    {{ !empty($lead->name) ? $lead->name : '' }}</h5>
                                                {{-- </div> --}}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                    <div id="general" class="card">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3 col-sm-6">
                                    <div class="d-flex align-items-start">
                                        <div class="theme-avtar bg-primary">
                                            <i class="ti ti-test-pipe"></i>
                                        </div>
                                        <div class="ms-2">
                                            <p class="text-muted mb-0 text-sm">{{ __('Pipeline') }}</p>
                                            <h5 class="text-success mb-0">{{ $deal->pipeline->name }}</h5>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3 col-sm-6 my-sm-0 my-3">
                                    <div class="d-flex align-items-start">
                                        <div class="theme-avtar bg-primary">
                                            <i class="ti ti-server"></i>
                                        </div>
                                        <div class="ms-2">
                                            <p class="text-muted mb-0 text-sm">{{ __('Stage') }}</p>
                                            <h5 class="text-primary mb-0">{{ $deal->stage->name }}</h5>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3 col-sm-6">
                                    <div class="d-flex align-items-start">
                                        <div class="theme-avtar bg-warning">
                                            <i class="ti ti-calendar"></i>
                                        </div>
                                        <div class="ms-2">
                                            <p class="text-muted mb-0 text-sm">{{ __('Created') }}</p>
                                            <h5 class="text-warning mb-0">
                                                {{ \Auth::user()->dateFormat($deal->created_at) }}</h5>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3 col-sm-6">
                                    <div class="d-flex align-items-start">
                                        <div class="theme-avtar bg-info">
                                            <i class="ti ti-report-money"></i>
                                        </div>
                                        <div class="ms-2">
                                            <p class="text-muted mb-0 text-sm">{{ __('Price') }}</p>
                                            <h5 class="text-info mb-0">{{ \Auth::user()->priceFormat($deal->price) }}</h5>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3 col-sm-6 my-3">
                                    <div class="d-flex align-items-start">
                                        <div class="theme-avtar bg-danger">
                                            <i class="ti ti-file-report"></i>
                                        </div>
                                        <div class="ms-2">
                                            <p class="text-muted mb-0 text-sm">{{ __('SOID') }}</p>
                                            <h5 class="text-info mb-0">{{ $deal->SOID }}</h5>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm-3">
                            <div class="card">
                                <div class="card-body">
                                    <div class="row align-items-center justify-content-between">
                                        <div class="mb-sm-0 col-auto mb-3">
                                            <small class="text-muted">{{ __('Task') }}</small>
                                            <h3 class="m-0">{{ count($tasks) }}</h3>
                                        </div>
                                        <div class="col-auto">
                                            <div class="theme-avtar bg-danger">
                                                <i class="ti ti-subtask"></i>
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
                        <div class="col-sm-3">
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
                        <div class="col-sm-3">
                            <div class="card">
                                <div class="card-body">
                                    <div class="row align-items-center justify-content-between">
                                        <div class="mb-sm-0 col-auto mb-3">
                                            <small class="text-muted">{{ __('Files') }}</small>
                                            <h3 class="m-0">{{ count($deal->files) }}</h3>
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

                    <div id="tasks" class="card">
                        <div class="card-header">
                            <div class="d-flex align-items-center justify-content-between">
                                <h5>{{ __('Tasks') }}</h5>
                                @can('create task')
                                    <div class="float-end">
                                        <a href="#" data-size="lg"
                                            data-url="{{ route('deals.tasks.create', $deal->id) }}" data-ajax-popup="true"
                                            data-bs-toggle="tooltip" title="{{ __('Add Task') }}"
                                            class="btn btn-sm btn-primary">
                                            <i class="ti ti-plus"></i>
                                        </a>
                                    </div>
                                @endcan

                            </div>
                        </div>
                        <div class="card-body">
                            @if (!$tasks->isEmpty())
                                <ul class="list-group list-group-flush mt-2">
                                    @foreach ($tasks as $task)
                                        <li class="list-group-item px-0">
                                            <div class="d-block d-sm-flex align-items-start">
                                                <div
                                                    class="form-check form-switch form-switch-right img-fluid mb-sm-0 mb-2 me-3">
                                                    <input class="form-check-input task-checkbox" type="checkbox"
                                                        role="switch" id="task_{{ $task->id }}"
                                                        @if ($task->status) checked="checked" @endcan value="{{ $task->status }}" data-url="{{ route('deals.tasks.update_status', [$deal->id, $task->id]) }}">
                                                    <label class="form-check-label pe-5" for="task_{{ $task->id }}"></label>
                                                </div>
                                                <div class="w-100">
                                                    <div class="d-flex align-items-center justify-content-between">
                                                        <div class="mb-3 mb-sm-0">
                                                            <h5 class="mb-0">
                                                                {{ $task->name }}
                                                                @if ($task->status)
                                                                    <div class="badge bg-primary mb-1">{{ __(\App\Models\DealTask::$status[$task->status]) }}</div>
                                                                @else
                                                                    <div class="badge bg-warning mb-1">{{ __(\App\Models\DealTask::$status[$task->status]) }}</div> @endif
                                                        </h5>
                                                    <small
                                                        class="text-sm">{{ __(\App\Models\DealTask::$priorities[$task->priority]) }}
                                                        - {{ Auth::user()->dateFormat($task->date) }}
                                                        {{ Auth::user()->timeFormat($task->time) }}</small>
                                                    <span class="text-muted text-sm">
                                                        @if ($task->status)
                                                            <div class="badge badge-pill badge-success mb-1">
                                                                {{ __(\App\Models\DealTask::$status[$task->status]) }}
                                                            </div>
                                                        @else
                                                            <div class="badge badge-pill badge-warning mb-1">
                                                                {{ __(\App\Models\DealTask::$status[$task->status]) }}
                                                            </div>
                                                        @endif
                                                    </span>
                                                </div>
                                                <span>
                                                    @can('edit task')
                                                        <div class="action-btn bg-info ms-2">
                                                            <a href="#" class=""
                                                                data-title="{{ __('Edit Task') }}"
                                                                data-url="{{ route('deals.tasks.edit', [$deal->id, $task->id]) }}"
                                                                data-ajax-popup="true" data-bs-toggle="tooltip"
                                                                title="{{ __('Edit') }}"><i
                                                                    class="ti ti-pencil text-white"></i></a>
                                                        </div>
                                                    @endcan
                                                    @can('delete task')
                                                        <div class="action-btn bg-danger ms-2">
                                                            <form
                                                                action="{{ route('deals.tasks.destroy', [$deal->id, $task->id]) }}"
                                                                method="post">
                                                                @csrf
                                                                @method('DELETE')

                                                                <a href="#"
                                                                    class="btn btn-sm align-items-center bs-pass-para mx-3"
                                                                    data-bs-toggle="tooltip" title="{{ __('Delete') }}"
                                                                    onclick="event.preventDefault(); document.getElementById('delete-form-{{ $task->id }}').submit();">
                                                                    <i class="ti ti-trash text-white"></i>
                                                                </a>
                                                            </form>
                                                        </div>
                                                    @endcan
                                                </span>
                                            </div>
                        </div>
                    </div>
                    </li>
                    @endforeach
                    </ul>
                @else
                    <div class="text-center">
                        No Tasks Available.!
                    </div>
                    @endif
                </div>
            </div>
            <div id="users_products">
                <div class="row">
                    <div class="col-6">
                        <div class="card">
                            <div class="card-header">
                                <div class="d-flex align-items-center justify-content-between">
                                    <h5>{{ __('Users') }}</h5>

                                    <div class="float-end">
                                        <a data-size="md" data-url="{{ route('deals.users.edit', $deal->id) }}"
                                            data-ajax-popup="true" data-bs-toggle="tooltip"
                                            title="{{ __('Add User') }}" class="btn btn-sm btn-primary">
                                            <i class="ti ti-plus"></i>
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
                                            @foreach ($deal->users as $user)
                                                <tr>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <div
                                                                style="
                                                            margin: 15px;
                                                        ">
                                                                <img src="{{ URL::to('/') . '/' . 'documents' . '/' . (isset($company_logos) && !empty($company_logos) ? $company_logos : 'logo-dark.png') }}"
                                                                    alt="{{ config('app.name', 'Orbix') }}"
                                                                    class="logo logo-lg"
                                                                    style="
                                                                height: 70px;
                                                                width: 70px;
                                                             }}"
                                                                    class="wid-30 rounded-circle me-3">
                                                            </div>
                                                            <p class="mb-0">{{ $user->name }}</p>
                                                        </div>
                                                    </td>
                                                    @can('edit deal')
                                                        <td>
                                                            <div class="action-btn bg-danger ms-2">
                                                                <form
                                                                    action="{{ route('deals.users.destroy', [$deal->id, $user->id]) }}"
                                                                    method="post" id="delete-form-{{ $deal->id }}">
                                                                    @csrf
                                                                    @method('DELETE')

                                                                    <a href="#"
                                                                        class="btn btn-sm align-items-center bs-pass-para mx-3"
                                                                        data-bs-toggle="tooltip" title="{{ __('Delete') }}"
                                                                        onclick="event.preventDefault(); document.getElementById('delete-form-{{ $deal->id }}').submit();">
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
                                    <h5>{{ __('Products') }}</h5>

                                    <div class="float-end">
                                        <a data-size="lg" data-url="{{ route('deals.products.edit', $deal->id) }}"
                                            data-ajax-popup="true" data-bs-toggle="tooltip"
                                            title="{{ __('Add Product') }}" class="btn btn-sm btn-primary">
                                            <i class="ti ti-plus"></i>
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
                                                <th>{{ __('Currency') }}</th>
                                                <th>{{ __('QTY') }}</th>
                                                <th>{{ __('Action') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($deal->dealProducts()->get() as $product)
                                                <tr>
                                                    @php
                                                        $amount = $product->price * $product->quantity;
                                                        $totalQuantity += $product->quantity;
                                                        $totalAmount += $amount;
                                                    @endphp
                                                    <td>
                                                        {{ $product->product->category->name . '/' . $product->product->brand->name . '/' . $product->product->subBrand->name . '/' . $product->product->name }}
                                                    </td>
                                                    <td>
                                                        @if($product->currency_id != null && $product->exchange_price)
                                                            {{ \Auth::user()->priceFormatCurr($product->exchange_price * $product->quantity, $product->currency->symbol) }}
                                                            @if($product->exchange_rate && $product->exchange_rate != 1)
                                                                <br><small class="text-muted">(Rate: {{ number_format($product->exchange_rate, 4) }})</small>
                                                            @endif
                                                        @else
                                                            {{ \Auth::user()->priceFormat($product->price * $product->quantity) }}
                                                        @endif
                                                    </td>
                                                    <td>
                                                        @if($product->currency)
                                                            {{ $product->currency->code }}
                                                        @else
                                                            {{ Auth::user()->currencySymbol() }}
                                                        @endif
                                                    </td>
                                                    <td>
                                                        {{ $product->quantity }}
                                                    </td>
                                                    @can('edit deal')
                                                        <td>
                                                            <div class="d-flex">
                                                                {{-- Edit Button --}}
                                                                <div class="action-btn bg-primary ms-2">
                                                                    <a href="javascript:void(0)"
                                                                       class="btn btn-sm align-items-center editProductBtn"
                                                                       data-id="{{ $product->id }}"
                                                                       data-deal="{{ $deal->id }}"
                                                                       data-bs-toggle="modal"
                                                                       data-bs-target="#editProductModal"
                                                                       title="{{ __('Edit') }}">
                                                                        <i class="ti ti-edit text-white"></i>
                                                                    </a>
                                                                </div>

                                                                {{-- Delete Button --}}
                                                                <div class="action-btn bg-danger ms-2">
                                                                    <form action="{{ route('deals.products.destroy', [$deal->id, $product->id]) }}" method="post" id="delete-form-{{ $deal->id }}-{{ $product->id }}">
                                                                        @csrf
                                                                        @method('DELETE')
                                                                        <button type="submit" class="btn btn-sm align-items-center"
                                                                            data-bs-toggle="tooltip" title="{{ __('Delete') }}">
                                                                            <i class="ti ti-trash text-white"></i>
                                                                        </button>
                                                                    </form>
                                                                </div>
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
                                                <th></th>
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
                                        <a data-size="md" data-url="{{ route('deals.sources.edit', $deal->id) }}"
                                            data-ajax-popup="true" data-bs-toggle="tooltip"
                                            title="{{ __('Add Source') }}" class="btn btn-sm btn-primary">
                                            <i class="ti ti-plus"></i>
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
                                                    @can('edit deal')
                                                        <td>
                                                            <div class="action-btn bg-danger ms-2">
                                                                <form
                                                                    action="{{ route('deals.sources.destroy', [$deal->id, $source->id]) }}"
                                                                    method="post" id="delete-form-{{ $deal->id }}">
                                                                    @csrf
                                                                    @method('DELETE')

                                                                    <a href="#"
                                                                        class="btn btn-sm align-items-center bs-pass-para mx-3"
                                                                        data-bs-toggle="tooltip" title="{{ __('Delete') }}"
                                                                        onclick="event.preventDefault(); document.getElementById('delete-form-{{ $deal->id }}').submit();">
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
                                    @can('create deal email')
                                        <div class="float-end">
                                            <a data-size="lg" data-url="{{ route('deals.emails.create', $deal->id) }}"
                                                data-ajax-popup="true" data-bs-toggle="tooltip"
                                                title="{{ __('Create Email') }}" class="btn btn-sm btn-primary">
                                                <i class="ti ti-plus"></i>
                                            </a>
                                        </div>
                                    @endcan
                                </div>
                            </div>
                            <div class="card-body">
                                <ul class="list-group list-group-flush mt-2">
                                    @if (!$emails->isEmpty())
                                        @foreach ($emails as $email)
                                            <li class="list-group-item px-0">
                                                <div class="d-block d-sm-flex align-items-start">
                                                    <img src="{{ URL::to('/') . '/' . 'documents' . '/' . (isset($company_logos) && !empty($company_logos) ? $company_logos : 'logo-dark.png') }}"
                                                        class="img-fluid wid-40 mb-sm-0 mb-2 me-3" alt="image">
                                                    <div class="w-100">
                                                        <div class="d-flex align-items-center justify-content-between">
                                                            <div class="mb-sm-0 mb-3">
                                                                <h5 class="mb-0">{{ $email->subject }}</h5>
                                                                <span
                                                                    class="text-muted text-sm">{{ $email->to }}</span>
                                                            </div>
                                                            <div class="form-check form-switch form-switch-right mb-2">
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
                                </ul>
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
                                        <a data-size="lg" data-url="{{ route('deals.discussions.create', $deal->id) }}"
                                            data-ajax-popup="true" data-bs-toggle="tooltip"
                                            title="{{ __('Add Message') }}" class="btn btn-sm btn-primary">
                                            <i class="ti ti-plus"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <ul class="list-group list-group-flush mt-2">
                                    @if (!$deal->discussions->isEmpty())
                                        @foreach ($deal->discussions as $discussion)
                                            <li class="list-group-item px-0">
                                                <div class="d-block d-sm-flex align-items-start">
                                                    <img src="{{ URL::to('/') . '/' . 'documents' . '/' . (isset($company_logos) && !empty($company_logos) ? $company_logos : 'logo-dark.png') }}"
                                                        class="img-fluid wid-40 mb-sm-0 mb-2 me-3" alt="image">
                                                    <div class="w-100">
                                                        <div class="d-flex align-items-center justify-content-between">
                                                            <div class="mb-sm-0 mb-3">
                                                                <h5 class="mb-0"> {{ $discussion->comment }}</h5>
                                                                <span
                                                                    class="text-muted text-sm">{{ $discussion->user->name }}</span>
                                                            </div>
                                                            <div class="form-switch form-switch-right mb-4">
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
                                            <a href="#" data-size="md" class="btn btn-primary btn-icon btn-sm"
                                                data-ajax-popup-over="true" id="grammarCheck"
                                                data-url="{{ route('grammar', ['grammar']) }}" data-bs-placement="top"
                                                data-title="{{ __('Grammar check with AI') }}">
                                                <i class="ti ti-rotate"></i>
                                                <span>{{ __('Grammar check with AI') }}</span>
                                            </a>
                                            <a href="#" data-size="md" class="btn btn-primary btn-icon btn-sm"
                                                data-ajax-popup-over="true" data-url="{{ route('generate', ['deal']) }}"
                                                data-bs-placement="top"
                                                data-title="{{ __('Generate content with AI') }}">
                                                <i class="fas fa-robot"></i> <span>{{ __('Generate with AI') }}</span>
                                            </a>
                                        </div>
                                    @endif
                                </div>
                            </div>
                            <div class="card-body">
                                <form action="{{ route('deals.update', $deal->id) }}" method="POST">
                                    @csrf
                                    @method('PUT')
                                    <div class="form-group">
                                        <label for="note" class="form-label">{{ __('Notes') }}</label>
                                        <textarea class="summernote-simple grammer_textarea" name="notes">{!! $deal->notes !!}</textarea>
                                    </div>
                                    <div class="text-end">
                                        <button type="submit" class="btn btn-primary"> {{ __('Update') }}</button>
                                    </div>
                                </form>
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
                        @can('create deal call')
                            <div class="float-end">
                                <a data-size="lg" data-url="{{ route('deals.calls.create', $deal->id) }}"
                                    data-ajax-popup="true" data-bs-toggle="tooltip" title="{{ __('Add Call') }}"
                                    class="btn btn-sm btn-primary">
                                    <i class="ti ti-plus"></i>
                                </a>
                            </div>
                        @endcan
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
                                        <td>{{ isset($call->getLeadCallUser) ? $call->getLeadCallUser->name : '-' }}</td>
                                        <td>
                                            @can('edit deal call')
                                                <div class="action-btn bg-info ms-2">
                                                    <a href="#" class="btn btn-sm d-inline-flex align-items-center mx-3"
                                                        data-url="{{ URL::to('deals/' . $deal->id . '/call/' . $call->id . '/edit') }}"
                                                        data-ajax-popup="true" data-size="xl" data-bs-toggle="tooltip"
                                                        title="{{ __('Edit') }}" data-title="{{ __('Edit Call') }}">
                                                        <i class="ti ti-pencil text-white"></i>
                                                    </a>
                                                </div>
                                            @endcan
                                            @can('delete deal call')
                                                <div class="action-btn bg-danger ms-2">
                                                    <form action="{{ route('deals.calls.destroy', [$deal->id, $user->id]) }}"
                                                        method="post" id="delete-form-{{ $deal->id }}">
                                                        @csrf
                                                        @method('DELETE')

                                                        <a href="#"
                                                            class="btn btn-sm align-items-center bs-pass-para mx-3"
                                                            data-bs-toggle="tooltip" title="{{ __('Delete') }}"
                                                            onclick="event.preventDefault(); document.getElementById('delete-form-{{ $deal->id }}').submit();">
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
                            @if (!$deal->activities->isEmpty())
                                @foreach ($deal->activities as $activity)
                                    <li class="list-group-item card mb-3">
                                        <div class="row align-items-center justify-content-between">
                                            <div class="mb-sm-0 col-auto mb-3">
                                                <div class="d-flex align-items-center">
                                                    <div class="theme-avtar bg-primary">
                                                        <i class="ti ti-{{ $activity->logIcon() }}"></i>
                                                    </div>
                                                    <div class="ms-3">
                                                        <span
                                                            class="text-dark text-sm">{{ __($activity->log_type) }}</span>
                                                        <h6 class="m-0">{!! $activity->getRemark() !!}</h6>
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
    <!-- Edit Product Modal -->
<div class="modal fade" id="editProductModal" tabindex="-1" aria-labelledby="editProductModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editProductModalLabel">{{ __('Edit Product') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- AJAX-loaded form will go here -->
                <div id="editProductFormContainer">
                    <p class="text-muted">{{ __('Loading...') }}</p>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
