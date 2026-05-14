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
    <link rel="stylesheet" href="https://cdn.datatables.net/2.3.0/css/dataTables.dataTables.css" />
@endpush
@push('script-page')
    <script>
        function updateLeadStage(leadId) {
            fetch(`/leads/${leadId}/update-stage`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            }).then(response => {
                console.log('Lead stage updated');
            }).catch(error => {
                console.error('Stage update failed:', error);
            });
        }
    </script>
    <script src="{{ asset('css/summernote/summernote-bs4.js') }}"></script>
    <script src="https://cdn.datatables.net/2.3.0/js/dataTables.js"></script>
    <script src="https://cdn.datatables.net/select/3.0.0/js/dataTables.select.js"></script>
    <script src="https://cdn.datatables.net/select/3.0.0/js/select.dataTables.js"></script>
    <script src="https://cdn.datatables.net/fixedcolumns/5.0.4/js/dataTables.fixedColumns.js"></script>
    <script src="https://cdn.datatables.net/fixedcolumns/5.0.4/js/fixedColumns.dataTables.js"></script>
    <script>
        document.getElementById('exportButton').addEventListener('click', function() {
            const selectedUser = $('select[name="user_id"]').val();
            const selectedLeads = document.querySelectorAll('.lead-checkbox:checked');
            const selectedIds = Array.from(selectedLeads).map(lead => lead.value);
            const fromDate = document.getElementById('from_date').value;
            const toDate = document.getElementById('to_date').value;
            const stageId = document.getElementById('stage_id').value;
            const pipelineId = document.getElementById('default_pipeline_id').value;

            let url = "{{ route('leads.export') }}";
            let params = [];

            if (selectedUser) {
                params.push('user=' + encodeURIComponent(selectedUser));
            }

            if (selectedIds.length > 0) {
                selectedIds.forEach(id => {
                    params.push('leads_ids[]=' + encodeURIComponent(id));
                });
            }

            if (fromDate) {
                params.push('from_date=' + encodeURIComponent(fromDate));
            }

            if (toDate) {
                params.push('to_date=' + encodeURIComponent(toDate));
            }

            if (stageId) {
                params.push('stage_id=' + encodeURIComponent(stageId));
            }

            if (pipelineId) {
                params.push('default_pipeline_id=' + encodeURIComponent(pipelineId));
            }

            if (params.length > 0) {
                url += '?' + params.join('&');
            }

            window.location.href = url;
        });
    </script>
    <script>
        // Initialize Select2 for the assign modal when it is shown so dropdownParent is available
        $('#assignModal').on('shown.bs.modal', function() {
            var $select = $('#user_ids');
            if ($select.length) {
                if ($select.hasClass('select2-hidden-accessible')) {
                    $select.select2('destroy');
                }
                $select.select2({
                    dropdownParent: $(this),
                    width: '100%',
                    allowClear: true,
                    matcher: function(params, data) {
                        // If no search term, return all options
                        if (!params.term || params.term.trim() === "") {
                            return data;
                        }

                        // Split search query into keywords
                        const keywords = params.term.toLowerCase().split(" ");
                        const text = data.text.toLowerCase();

                        // Check if ALL keywords exist in the option's text (order ignored)
                        const isMatch = keywords.every((keyword) => text.includes(keyword));

                        return isMatch ? data : null;
                    },
                });
            }
        });

        // Destroy Select2 when modal hides to avoid duplicate instances
        $('#assignModal').on('hidden.bs.modal', function() {
            var $select = $('#user_ids');
            if ($select.length && $select.hasClass('select2-hidden-accessible')) {
                $select.select2('destroy');
            }
        });
        document.getElementById('assign-to-btn').addEventListener('click', function(e) {
            const selectedCheckboxes = Array.from(document.querySelectorAll('.lead-checkbox:checked'));

            if (selectedCheckboxes.length === 0) {
                // Prevent the modal from opening
                e.preventDefault();
                e.stopImmediatePropagation();
                Swal.fire({
                    icon: 'warning',
                    title: 'No Leads Selected',
                    text: 'Please select at least one lead to assign.'
                });
                return false;
            }

            const form = document.getElementById('assignForm');
            // remove any previously added hidden inputs
            form.querySelectorAll('input[name="lead_ids[]"]').forEach(i => i.remove());

            selectedCheckboxes.forEach(function(checkbox) {
                const hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = 'lead_ids[]';
                hiddenInput.value = checkbox.value;
                form.appendChild(hiddenInput);
            });

            // Show modal programmatically after validation and after inputs appended
            var assignModalEl = document.getElementById('assignModal');
            if (assignModalEl) {
                var bsModal = new bootstrap.Modal(assignModalEl);
                bsModal.show();
            }
        });
    </script>
    <script>
        document.getElementById('delete-selected-btn').addEventListener('click', function(e) {
            const form = document.getElementById('delete-leads-form');
            // Remove any previously added hidden fields
            const oldInputs = form.querySelectorAll('input[name="lead_ids[]"]');
            oldInputs.forEach(input => input.remove());

            // Get all checked leads
            const selectedLeads = document.querySelectorAll('.lead-checkbox:checked');
            if (selectedLeads.length === 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'No Leads Selected',
                    text: 'Please select at least one lead to delete.',
                });
                return;
            }

            // Append selected IDs as hidden inputs
            selectedLeads.forEach(function(checkbox) {
                const hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = 'lead_ids[]';
                hiddenInput.value = checkbox.value;
                form.appendChild(hiddenInput);
            });

            // SweetAlert2 mixin for confirm/cancel
            const swalWithBootstrapButtons = Swal.mixin({
                customClass: {
                    confirmButton: 'btn btn-danger',
                    cancelButton: 'btn btn-secondary'
                },
                buttonsStyling: false
            });

            swalWithBootstrapButtons.fire({
                title: 'Are you sure?',
                text: 'You won\'t be able to revert this!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, delete!',
                cancelButtonText: 'Cancel',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });
    </script>
    <script>
        document.getElementById('openAssignStageModal').addEventListener('click', function() {
            const selectedIds = Array.from(document.querySelectorAll('.lead-checkbox:checked'))
                .map(cb => cb.value);

            const leadIdsContainer = document.getElementById('assign-stage-hidden-inputs');
            leadIdsContainer.innerHTML = ''; // Clear old inputs

            if (selectedIds.length === 0) {
                alert('Please select at least one lead.');
                return;
            }

            selectedIds.forEach(id => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'lead_ids[]'; // Use 'lead_ids[]' so it sends as an array
                input.value = id;
                leadIdsContainer.appendChild(input);
            });
        });
    </script>
    <script>
        document.getElementById('openAssignSourceModal').addEventListener('click', function() {
            const selectedIds = Array.from(document.querySelectorAll('.lead-checkbox:checked'))
                .map(cb => cb.value);

            const leadIdsContainer = document.getElementById('assign-source-hidden-inputs');
            leadIdsContainer.innerHTML = ''; // Clear old inputs

            if (selectedIds.length === 0) {
                alert('Please select at least one lead.');
                return;
            }

            selectedIds.forEach(id => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'lead_ids[]'; // Use 'lead_ids[]' so it sends as an array
                input.value = id;
                leadIdsContainer.appendChild(input);
            });
        });
    </script>
    <script>
        $(document).on('click', '.whatsapp-link', function(e) {
            e.preventDefault();

            const leadId = $(this).data('id');
            const whatsappUrl = $(this).attr('href');

            if (!whatsappUrl) {
                console.error('WhatsApp URL not found');
                return;
            }

            $.post(`/leads/${leadId}/update-stage`, {
                _token: $('meta[name="csrf-token"]').attr('content')
            }).done(function() {
                window.open(whatsappUrl, '_blank');
            }).fail(function() {
                // Still open WhatsApp even if stage update fails
                window.open(whatsappUrl, '_blank');
            });
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
@endpush
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item">{{ __('Lead') }}</li>
@endsection
@section('action-btn')
    <div class="float-end">
        <a href="{{ route('leads.index') }}" data-bs-toggle="tooltip" title="{{ __('Kanban View') }}"
            class="btn btn-sm btn-primary">
            <i class="ti ti-layout-grid"></i>
        </a>
        <a href="#" data-size="md" data-bs-toggle="tooltip" title="{{ __('Import') }}"
            data-url="{{ route('leads.file.import') }}" data-ajax-popup="true"
            data-title="{{ __('Import Lead CSV file') }}" class="btn btn-sm btn-primary">
            <i class="ti ti-file-import"></i>
        </a>
        <a href="#" id="exportButton" data-bs-toggle="tooltip" title="{{ __('Export') }}"
            class="btn btn-sm btn-primary">
            <i class="ti ti-file-export"></i>
        </a>
        <a href="#" data-size="lg" data-url="{{ route('leads.create') }}" data-ajax-popup="true"
            data-bs-toggle="tooltip" title="{{ __('Create New User') }}" class="btn btn-sm btn-primary">
            <i class="ti ti-plus"></i> Create
        </a>
    </div>
@endsection

@section('content')
    @if ($pipeline)
        <div class="row">
            @if (auth()->user()->hasAnyRole(['manager', 'company', 'crm admin']) || Gate::check('manage crm admin'))
                <div class="col-sm-12">
                    <div class="mt-2" id="multiCollapseExample1">
                        <div class="card">
                            <div class="card-body">
                                <form id="filter-leads-form">
                                    <div class="row align-items-center justify-content-end">
                                        <div class="col-xl-2 col-lg-2 col-md-6 col-sm-12 col-12">
                                            <div class="btn-box">
                                                <label for="user" class="form-label">{{ __('Users') }}</label>
                                                <select name="user_id" class="form-control select2"
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

                                        <div class="col-xl-2 col-lg-2 col-md-6 col-sm-12 col-12">
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

                                        <div class="col-xl-2 col-lg-2 col-md-6 col-sm-12 col-12">
                                            <div class="btn-box">
                                                <label for="stage_id" class="form-label">{{ __('Stage') }}</label>
                                                <select name="stage_id" id="stage_id" class="form-control select2"
                                                    data-placeholder="{{ __('Select Stage') }}">
                                                    <option value=""></option>
                                                    @if (isset($pipeline) && $pipeline)
                                                        @foreach ($pipeline->leadStages as $stage)
                                                            <option value="{{ $stage->id }}"
                                                                {{ isset($_GET['stage_id']) && $_GET['stage_id'] == $stage->id ? 'selected' : '' }}>
                                                                {{ $stage->name }}
                                                            </option>
                                                        @endforeach
                                                    @endif
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-xl-2 col-lg-2 col-md-6 col-sm-12 col-12">
                                            <div class="btn-box">
                                                <label for="from_date" class="form-label">{{ __('From Date') }}</label>
                                                <input type="date" name="from_date" id="from_date" class="form-control"
                                                    value="{{ isset($_GET['from_date']) ? $_GET['from_date'] : '' }}">
                                            </div>
                                        </div>

                                        <div class="col-xl-2 col-lg-2 col-md-6 col-sm-12 col-12">
                                            <div class="btn-box">
                                                <label for="to_date" class="form-label">{{ __('To Date') }}</label>
                                                <input type="date" name="to_date" id="to_date" class="form-control"
                                                    value="{{ isset($_GET['to_date']) ? $_GET['to_date'] : '' }}">
                                            </div>
                                        </div>

                                        <div class="col mt-4 d-flex justify-content-end">
                                            <button type="submit" class="btn btn-sm btn-primary" data-bs-toggle="tooltip"
                                                title="{{ __('Apply') }}" data-original-title="{{ __('apply') }}">
                                                <span class="btn-inner--icon"><i class="ti ti-search"></i></span>
                                            </button>
                                            <a href="{{ route('leads.list') }}" class="btn btn-sm btn-danger"
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
            <div class="col-xl-12">
                <div class="card">
                    @if (auth()->user()->hasAnyRole(['manager', 'company', 'crm admin']) || Gate::check('manage crm admin'))
                        <div class="d-grid-button-group"
                            style="
                    display: grid;
                    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                    gap: 20px;
                    margin-bottom: 20px;
                ">
                            <button class="btn btn-primary mb-3" id="assign-to-btn"
                                style="
                    width: 200px;
                    margin: 20px;
                ">
                                Assign To
                            </button>
                            <form id="delete-leads-form" method="POST" action="{{ route('leads.bulkDelete') }}">
                                @csrf
                                <button type="button" class="btn btn-danger mb-3" id="delete-selected-btn"
                                    style="
                                        width: 200px;
                                        margin: 20px;
                                    ">
                                    Delete Selected
                                </button>

                            </form>
                            <button type="button" class="btn btn-warning mb-3" id="openAssignStageModal"
                                data-bs-toggle="modal" data-bs-target="#assignStageModal"
                                style="
                    width: 200px;
                    margin: 20px;
                ">
                                Assign Stage
                            </button>
                            <button type="button" class="btn btn-info mb-3" id="openAssignSourceModal"
                                data-bs-toggle="modal" data-bs-target="#assignSourceModal"
                                style="
                    width: 200px;
                    margin: 20px;
                ">
                                Assign Source
                            </button>
                        </div>
                    @endif
                    <div class="card-body table-border-style">
                        <div class="table-responsive">
                            <table class="table" id="leads-list-table">
                                <thead>
                                    <tr>
                                        <th><input type="checkbox" id="select-all"></th>
                                        <th class="long-cell">{{ __('Name') }}</th>
                                        <th class="long-cell">{{ __('Company Name') }}</th>
                                        <th>{{ __('Stage') }}</th>
                                        <th>{{ __('Date') }}</th>
                                        <th>{{ __('Qty') }}</th>
                                        <th>{{ __('Payment') }}</th>
                                        <th class="long-cell">{{ __('Note') }}</th>
                                        <th>{{ __('Source') }}</th>
                                        <th class="long-cell">{{ __('URL') }}</th>
                                        <th>{{ __('Whatsapp') }}</th>
                                        <th>{{ __('Users') }}</th>
                                        <th>{{ __('Action') }}</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
    <!-- Assign Modal -->
    <div class="modal fade" id="assignModal" tabindex="-1" aria-labelledby="assignModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form id="assignForm" method="POST" action="{{ route('leads.assign') }}">
                @csrf
                <input type="hidden" name="lead_ids" id="lead_ids">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="assignModalLabel">Assign Leads to User</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="">
                            <label for="user_ids" class="form-label">Select User</label>
                            <select name="user_ids[]" id="user_ids" class="form-control select2"
                                data-placeholder="{{ __('Select Users') }}" required multiple>
                                <option value=""></option>
                                @foreach ($users as $id => $user)
                                    <option value="{{ $id }}">{{ $user }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">Assign</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <!-- Assign Stage Modal -->
    @php
        $lead_stages = $pipeline->leadStages;
    @endphp
    <div class="modal fade" id="assignStageModal" tabindex="-1" aria-labelledby="assignStageModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <form id="assign-stage-form" method="POST" action="{{ route('leads.assignStage') }}">
                @csrf
                <div id="assign-stage-hidden-inputs"></div>
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Assign Stage to Selected Leads</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="stage_id" class="form-label">Select Stage:</label>
                            <select class="form-select" name="stage_id" id="stage_id" required>
                                <option value="">-- Choose Stage --</option>
                                @foreach ($lead_stages as $stage)
                                    <option value="{{ $stage->id }}">{{ $stage->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-warning">Assign</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <!-- Assign Source Modal -->
    <div class="modal fade" id="assignSourceModal" tabindex="-1" aria-labelledby="assignSourceModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <form id="assign-source-form" method="POST" action="{{ route('leads.assignSource') }}">
                @csrf
                <div id="assign-source-hidden-inputs"></div> <!-- Hidden inputs for selected leads -->
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Assign Source to Selected Leads</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="source_id" class="form-label">Select Source:</label>
                            <select class="form-select" name="source_id" id="source_id" required>
                                <option value="">-- Choose Source --</option>
                                @foreach ($sources as $id => $source)
                                    <option value="{{ $id }}">{{ $source }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-info">Assign</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    @push('old-datatable-js')
        <script>
            // Build initial URL with filters from query string
            let initialUrl = '{{ route('leads.list') }}';
            @if (isset($_GET['user_id']) ||
                    isset($_GET['default_pipeline_id']) ||
                    isset($_GET['stage_id']) ||
                    isset($_GET['from_date']) ||
                    isset($_GET['to_date']))
                let params = [];
                @if (isset($_GET['user_id']))
                    params.push('user_id={{ $_GET['user_id'] }}');
                @endif
                @if (isset($_GET['default_pipeline_id']))
                    params.push('default_pipeline_id={{ $_GET['default_pipeline_id'] }}');
                @endif
                @if (isset($_GET['stage_id']))
                    params.push('stage_id={{ $_GET['stage_id'] }}');
                @endif
                @if (isset($_GET['from_date']))
                    params.push('from_date={{ $_GET['from_date'] }}');
                @endif
                @if (isset($_GET['to_date']))
                    params.push('to_date={{ $_GET['to_date'] }}');
                @endif
                if (params.length > 0) {
                    initialUrl += '?' + params.join('&');
                }
            @endif

            const leadsTable = new DataTable('#leads-list-table', {
                serverSide: true,
                ajax: initialUrl,
                columns: [{
                        data: 'checkbox',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'name',
                        className: 'long-cell'
                    },
                    {
                        data: 'subject',
                        className: 'long-cell'
                    },
                    {
                        data: 'stage'
                    },
                    {
                        data: 'date'
                    },
                    {
                        data: 'qty'
                    },
                    {
                        data: 'payment'
                    },
                    {
                        data: 'notes',
                        className: 'long-cell'
                    },
                    {
                        data: 'source'
                    },
                    {
                        data: 'source_url',
                        className: 'long-cell'
                    },
                    {
                        data: 'whatsapp'
                    },
                    {
                        data: 'users'
                    },
                    {
                        data: 'action',
                        orderable: false,
                        searchable: false
                    },
                ],
                columnDefs: [{
                        targets: 0,
                        orderable: false,
                        searchable: false
                    },
                    {
                        width: 80,
                        targets: 5
                    },
                    {
                        className: 'dt-left',
                        targets: '_all'
                    }
                ],
                autoWidth: false,
                order: [
                    [1]
                ],
                select: {
                    style: 'os',
                    selector: 'td:first-child'
                },
                info: true,
                paging: true,
                pageLength: 100,
                scrollY: 800,
                scrollX: true,
                scrollCollapse: true,
                language: {
                    searchPlaceholder: "Search here...",
                    search: "",
                }
            });
            $('#filter-leads-form').on('submit', function(e) {
                e.preventDefault();
                // Get filter values
                let userId = $('select[name="user_id"]').val();
                let pipelineId = $('select[name="default_pipeline_id"]').val();
                let stageId = $('select[name="stage_id"]').val();
                let fromDate = $('input[name="from_date"]').val();
                let toDate = $('input[name="to_date"]').val();

                // Build query string
                let params = [];
                if (userId) params.push('user_id=' + encodeURIComponent(userId));
                if (pipelineId) params.push('default_pipeline_id=' + encodeURIComponent(pipelineId));
                if (stageId) params.push('stage_id=' + encodeURIComponent(stageId));
                if (fromDate) params.push('from_date=' + encodeURIComponent(fromDate));
                if (toDate) params.push('to_date=' + encodeURIComponent(toDate));

                // Pass filters to DataTables and reload
                let url = '{{ route('leads.list') }}';
                if (params.length > 0) {
                    url += '?' + params.join('&');
                }
                leadsTable.ajax.url(url).load();
            });
            // Handle "select all" checkbox
            document.getElementById('select-all').addEventListener('change', function() {
                const isChecked = this.checked;
                document.querySelectorAll('.lead-checkbox').forEach(cb => {
                    cb.checked = isChecked;
                });
            });

            // Optional: Update "select all" checkbox state if any single box is unchecked
            document.querySelectorAll('.lead-checkbox').forEach(cb => {
                cb.addEventListener('change', function() {
                    const allCheckboxes = document.querySelectorAll('.lead-checkbox');
                    const allChecked = Array.from(allCheckboxes).every(checkbox => checkbox.checked);
                    console.log(allChecked)
                    document.getElementById('select-all').checked = allChecked;
                });
            });

            // Initialize Bootstrap tooltips for notes column after each table draw
            leadsTable.on('draw', function() {
                var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                tooltipTriggerList.forEach(function(tooltipTriggerEl) {
                    new bootstrap.Tooltip(tooltipTriggerEl);
                });
            });
        </script>
    @endpush
@endsection
