@extends('layouts.admin')
@section('page-title')
    {{ __('Manage Leads') }} @if ($pipeline)
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
    <link rel="stylesheet" href="{{ asset('assets/css/plugins/dragula.min.css') }}" id="main-style-link">
    <style>
        .kanban-scroll-container {
            overflow-x: auto;
            overflow-y: auto;
            height: 80vh;
            /* Adjust to fit screen or parent layout */
            padding: 1rem;
        }

        .kanban-wrapper {
            display: flex;
            flex-wrap: nowrap;
            gap: 1rem;
            height: 50%;
        }

        .kanban-stage {
            flex: 0 0 300px;
            /* or any fixed width */
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .kanban-stage .card {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .kanban-box {
            overflow-y: auto;
            flex: 1;
            padding-right: 0.5rem;
            /* To prevent content clipping by scrollbar */
        }

        /* Ensure scroll-leads is scrollable and shows a scrollbar */
        .scroll-leads {
            max-height: 60vh;
            height: 60vh;
            overflow-y: auto;
        }
    </style>
@endpush

@push('script-page')
    <script src="{{ asset('css/summernote/summernote-bs4.js') }}"></script>
    <script src="{{ asset('assets/js/plugins/dragula.min.js') }}"></script>
    <script>
        ! function(a) {
            "use strict";
            var t = function() {
                this.$body = a("body")
            };
            t.prototype.init = function() {
                a('[data-plugin="dragula"]').each(function() {
                    var t = a(this).data("containers"),
                        n = [];
                    if (t)
                        for (var i = 0; i < t.length; i++) n.push(a("#" + t[i])[0]);
                    else n = [a(this)[0]];
                    var r = a(this).data("handleclass");
                    r ? dragula(n, {
                        moves: function(a, t, n) {
                            return n.classList.contains(r)
                        }
                    }) : dragula(n).on('drop', function(el, target, source, sibling) {

                        var order = [];
                        $("#" + target.id + " > div").each(function() {
                            order[$(this).index()] = $(this).attr('data-id');
                        });

                        var id = $(el).attr('data-id');

                        var old_status = $("#" + source.id).data('status');
                        var new_status = $("#" + target.id).data('status');
                        var stage_id = $(target).attr('data-id');
                        var pipeline_id = '{{ $pipeline->id }}';

                        $("#" + source.id).parent().find('.count').text($("#" + source.id + " > div")
                            .length);
                        $("#" + target.id).parent().find('.count').text($("#" + target.id + " > div")
                            .length);
                        $.ajax({
                            url: '{{ route('leads.order') }}',
                            type: 'POST',
                            data: {
                                lead_id: id,
                                stage_id: stage_id,
                                order: order,
                                new_status: new_status,
                                old_status: old_status,
                                pipeline_id: pipeline_id,
                                "_token": $('meta[name="csrf-token"]').attr('content')
                            },
                            success: function(data) {},
                            error: function(data) {
                                data = data.responseJSON;
                                show_toastr('error', data.error, 'error')
                            }
                        });
                    });
                })
            }, a.Dragula = new t, a.Dragula.Constructor = t
        }(window.jQuery),
        function(a) {
            "use strict";

            a.Dragula.init()

        }(window.jQuery);
    </script>
    <script>
        function submitForm() {
            document.getElementById('report_Gledger').submit();
        }
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.whatsapp-link').forEach(function(link) {
                link.addEventListener('click', function(e) {
                    e.preventDefault();

                    const whatsappUrl = this.href;
                    const leadId = this.dataset.leadId;

                    if (!whatsappUrl) {
                        console.error('WhatsApp URL not found');
                        return;
                    }

                    fetch(`/leads/${leadId}/update-stage`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        }
                    }).then(response => {
                        window.open(whatsappUrl, '_blank');
                    }).catch(error => {
                        console.error('Failed to update lead stage', error);
                        // Still open WhatsApp even if stage update fails
                        window.open(whatsappUrl, '_blank');
                    });
                });
            });
        });
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const scrollContainers = document.querySelectorAll('.scroll-leads');
            console.log(scrollContainers)

            scrollContainers.forEach(container => {
                container.addEventListener('scroll', function() {
                    const scrollTop = container.scrollTop;
                    const scrollHeight = container.scrollHeight;
                    const clientHeight = container.clientHeight;

                    if (scrollTop + clientHeight >= scrollHeight - 50) {
                        loadMoreLeads(container);
                    }
                });
            });

            const loadingStates = {};

            function loadMoreLeads(container) {
                const stageId = container.getAttribute('data-id');
                const nextPage = parseInt(container.getAttribute('data-page')) + 1;
                const baseUrl = container.getAttribute('data-next-url');

                if (loadingStates[stageId]) return;

                loadingStates[stageId] = true;

                fetch(baseUrl + '?page=' + nextPage)
                    .then(response => response.json())
                    .then(data => {
                        if (data.html && data.html.trim() !== '') {
                            container.insertAdjacentHTML('beforeend', data.html);
                            container.setAttribute('data-page', nextPage);
                            if (!data.next_page_url) {
                                container.removeAttribute('data-next-url');
                            }
                        } else {
                            container.removeAttribute('data-next-url');
                        }
                    })
                    .catch(error => {
                        console.error('Load failed', error);
                    })
                    .finally(() => {
                        loadingStates[stageId] = false;
                    });
            }
        });
    </script>
    <script>
        $(document).on("change", "#default_pipeline_id", function() {
            $('#change-pipeline').submit();
        });
    </script>
@endpush
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item">{{ __('Lead') }}</li>
@endsection
@section('action-btn')
    <div class="float-end">
        <form method="POST" action="{{ route('leads.change.pipeline') }}" id="change-pipeline" class="btn btn-sm">
            @csrf
            <select name="default_pipeline_id" id="default_pipeline_id" class="form-control select me-4">
                @foreach ($pipelines as $key => $value)
                    <option value="{{ $key }}" {{ $key == $pipeline->id ? 'selected' : '' }}>{{ $value }}
                    </option>
                @endforeach
            </select>
        </form>


        <a href="{{ route('leads.list') }}" data-size="lg" data-bs-toggle="tooltip" title="{{ __('List View') }}"
            class="btn btn-sm btn-primary">
            <i class="ti ti-list"></i>
        </a>
        <a href="#" data-size="md" data-bs-toggle="tooltip" title="{{ __('Import') }}"
            data-url="{{ route('leads.file.import') }}" data-ajax-popup="true"
            data-title="{{ __('Import Lead CSV file') }}" class="btn btn-sm btn-primary">
            <i class="ti ti-file-import"></i>
        </a>
        <a href="{{ route('leads.export') }}" data-bs-toggle="tooltip" title="{{ __('Export') }}"
            class="btn btn-sm btn-primary">
            <i class="ti ti-file-export"></i>
        </a>
        <a href="#" data-size="lg" data-url="{{ route('leads.create') }}" data-ajax-popup="true"
            data-bs-toggle="tooltip" title="{{ __('Create New Lead') }}" data-title="{{ __('Create Lead') }}"
            class="btn btn-sm btn-primary">
            <i class="ti ti-plus"></i>
        </a>
    </div>
@endsection

@section('content')
    <div class="row">
        @if (auth()->user()->hasAnyRole(['manager', 'company', 'crm admin']) || Gate::check('manage crm admin'))
            <div class="col-sm-12">
                <div class="mt-2 " id="multiCollapseExample1">
                    <div class="card">
                        <div class="card-body">
                            <form action="{{ route('leads.list') }}" method="GET" id="report_Gledger">
                                <div class="row align-items-center justify-content-end">
                                    <div class="col">
                                        <div class="row">
                                            <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12">
                                                <div class="btn-box">
                                                    <label for="user" class="form-label">{{ __('Users') }}</label>
                                                    <select id="user" name="user_id" class="form-control select2">
                                                        <option value="">{{ __('Select User') }}</option>
                                                        @foreach ($users as $userId => $userName)
                                                            <option value="{{ $userId }}"
                                                                {{ isset($_GET['user']) && $_GET['user'] == $userId ? 'selected' : '' }}>
                                                                {{ $userName }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <div class="row">
                                            <div class="col-auto mt-4">
                                                <a href="#" class="btn btn-sm btn-primary"
                                                    onclick="submitForm(); return false;" data-bs-toggle="tooltip"
                                                    title="{{ __('Apply') }}" data-original-title="{{ __('apply') }}">
                                                    <span class="btn-inner--icon"><i class="ti ti-search"></i></span>
                                                </a>
                                                <a href="{{ route('leads.list') }}" class="btn btn-sm btn-danger"
                                                    data-bs-toggle="tooltip" title="{{ __('Reset') }}"
                                                    data-original-title="{{ __('Reset') }}">
                                                    <span class="btn-inner--icon"><i
                                                            class="ti ti-trash-off text-white-off"></i></span>
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
        @endif
        <div class="col-sm-12">
            @php
                $lead_stages = $pipeline->leadStages;
                $json = [];
                foreach ($lead_stages as $lead_stage) {
                    $json[] = 'task-list-' . $lead_stage->id;
                }
            @endphp
            <div class="kanban-scroll-container">
                <div class="kanban-wrapper d-flex" data-containers='{!! json_encode($json) !!}' data-plugin="dragula">

                    @foreach ($lead_stages as $lead_stage)
                        @php($leads = $lead_stage->lead()->paginate(10))
                        <div class="col">
                            <div class="card">
                                <div class="card-header">
                                    <div class="float-end">
                                        <span class="btn btn-sm btn-primary btn-icon count">
                                            {{ $lead_stage->lead()->count() }}
                                        </span>
                                    </div>
                                    <h4 class="mb-0">{{ $lead_stage->name }}</h4>
                                </div>
                                <div class="scroll-leads" data-id="{{ $lead_stage->id }}" data-page="1"
                                    data-next-url="{{ route('leads.stage.more', ['stage' => $lead_stage->id]) }}"
                                    style=" overflow-y: auto;">

                                    @foreach ($leads as $lead)
                                        @include('leads._lead_cards', compact('lead'))
                                    @endforeach

                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

        </div>
    </div>
@endsection
