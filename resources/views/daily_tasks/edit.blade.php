@extends('layouts.admin')

@section('page-title')
    {{ __('Edit daily task log') }}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('daily-tasks.index') }}">{{ __('Task') }}</a></li>
    <li class="breadcrumb-item">{{ __('Edit') }}</li>
@endsection

@section('content')
    <div class="row">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-body">
                    <form method="post" action="{{ route('daily-tasks.update', $log) }}" id="daily-task-form">
                        @csrf
                        @method('PUT')
                        @if ($isCompanyAdmin)
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">{{ __('Employee') }} <span
                                            class="text-danger">*</span></label>
                                    <select name="employee_id" id="employee_id_select" class="form-control" required>
                                        @foreach ($employees as $eid => $ename)
                                            <option value="{{ $eid }}"
                                                {{ (string) old('employee_id', $log->employee_id) === (string) $eid ? 'selected' : '' }}>
                                                {{ $ename }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        @else
                            <input type="hidden" name="employee_id" value="{{ $log->employee_id }}">
                        @endif

                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label">{{ __('Date') }} <span class="text-danger">*</span></label>
                                <input type="date" name="log_date" class="form-control" required
                                    value="{{ old('log_date', $log->log_date ? $log->log_date->format('Y-m-d') : '') }}">
                            </div>
                            <div class="col-md-8">
                                <label class="form-label">{{ __('Day notes') }}</label>
                                <input type="text" name="day_notes" class="form-control"
                                    value="{{ old('day_notes', $log->day_notes) }}">
                            </div>
                        </div>

                        <h6 class="mb-2">{{ __('Tasks') }}</h6>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th style="min-width:220px">{{ __('List of tasks') }}</th>
                                        <th style="width:100px">{{ __('Hours') }}</th>
                                        <th style="width:100px">{{ __('Min') }}</th>
                                        <th>{{ __('Note') }}</th>
                                        <th style="width:50px"></th>
                                    </tr>
                                </thead>
                                <tbody id="task-rows">
                                    @php
                                        $rows = old(
                                            'tasks',
                                            $log->tasks
                                                ->map(function ($t) {
                                                    return [
                                                        'task_master_id' => $t->task_master_id,
                                                        'task_name' => $t->task_name,
                                                        'hours' => $t->hours,
                                                        'minutes' => $t->minutes,
                                                        'notes' => $t->notes,
                                                    ];
                                                })
                                                ->values()
                                                ->toArray(),
                                        );
                                        if (empty($rows)) {
                                            $rows = [
                                                [
                                                    'task_master_id' => '',
                                                    'task_name' => '',
                                                    'hours' => 0,
                                                    'minutes' => 0,
                                                    'notes' => '',
                                                ],
                                            ];
                                        }
                                    @endphp
                                    @foreach ($rows as $i => $row)
                                        @php
                                            $isOtherSelected = empty($row['task_master_id']) && !empty(trim((string) ($row['task_name'] ?? '')));
                                        @endphp
                                        <tr class="task-row">
                                            <td>
                                                <select name="tasks[{{ $i }}][task_master_id]"
                                                    class="form-control task-master-select">
                                                    <option value="">{{ __('Select task') }}</option>
                                                    @foreach ($taskMasters as $tid => $tname)
                                                        <option value="{{ $tid }}"
                                                            {{ (string) ($row['task_master_id'] ?? '') === (string) $tid ? 'selected' : '' }}>
                                                            {{ $tname }}</option>
                                                    @endforeach
                                                    <option value="other" {{ $isOtherSelected ? 'selected' : '' }}>{{ __('Other') }}</option>
                                                </select>
                                                <input type="text" name="tasks[{{ $i }}][task_name]"
                                                    class="form-control mt-1 other-task-input {{ $isOtherSelected ? '' : 'd-none' }}"
                                                    placeholder="{{ __('Enter other task') }}"
                                                    value="{{ $row['task_name'] ?? '' }}">
                                            </td>
                                            <td>
                                                <input type="number" name="tasks[{{ $i }}][hours]" class="form-control"
                                                    min="0" max="24" value="{{ $row['hours'] ?? 0 }}">
                                            </td>
                                            <td>
                                                <input type="number" name="tasks[{{ $i }}][minutes]" class="form-control"
                                                    min="0" max="59" value="{{ $row['minutes'] ?? 0 }}">
                                            </td>
                                            <td>
                                                <input type="text" name="tasks[{{ $i }}][notes]" class="form-control"
                                                    value="{{ $row['notes'] ?? '' }}">
                                            </td>
                                            <td class="text-center">
                                                <button type="button" class="btn btn-sm btn-outline-danger remove-row"
                                                    title="{{ __('Remove') }}">&times;</button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <button type="button" class="btn btn-sm btn-secondary mb-3" id="add-task-row">{{ __('Add row') }}</button>

                        <div class="float-end">
                            <a href="{{ route('daily-tasks.show', $log) }}" class="btn btn-light">{{ __('Cancel') }}</a>
                            <button type="submit" class="btn btn-primary">{{ __('Update') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script-page')
    <script>
        (function() {
            const selectTaskLabel = @json(__('Select task'));
            const otherLabel = @json(__('Other'));
            const otherValue = 'other';
            const otherNamePlaceholder = @json(__('Enter other task'));
            const removeTitle = @json(__('Remove'));
            window.dailyTaskMasters = @json($taskMasters);
            const taskMastersUrl = @json(route('daily-tasks.task-masters'));

            let nextIndex = {{ count($rows) }};
            const tbody = document.getElementById('task-rows');

            function refillSelectOptions(selectEl, tasks) {
                const previous = selectEl.value;
                selectEl.innerHTML = '';
                const o0 = document.createElement('option');
                o0.value = '';
                o0.textContent = selectTaskLabel;
                selectEl.appendChild(o0);
                Object.entries(tasks).forEach(function(entry) {
                    const id = entry[0];
                    const title = entry[1];
                    const o = document.createElement('option');
                    o.value = id;
                    o.textContent = title;
                    selectEl.appendChild(o);
                });
                const otherOption = document.createElement('option');
                otherOption.value = otherValue;
                otherOption.textContent = otherLabel;
                selectEl.appendChild(otherOption);

                if (previous === otherValue || Object.prototype.hasOwnProperty.call(tasks, previous)) {
                    selectEl.value = previous;
                } else {
                    selectEl.value = '';
                }
            }

            function toggleOtherInputForRow(row) {
                const sel = row.querySelector('.task-master-select');
                const input = row.querySelector('.other-task-input');
                if (!sel || !input) {
                    return;
                }
                const isOther = sel.value === otherValue;
                input.classList.toggle('d-none', !isOther);
                if (!isOther) {
                    input.value = '';
                }
            }

            function refreshAllTaskMasterSelects(tasks) {
                window.dailyTaskMasters = tasks;
                document.querySelectorAll('#task-rows .task-row').forEach(function(row) {
                    const sel = row.querySelector('.task-master-select');
                    if (!sel) {
                        return;
                    }
                    refillSelectOptions(sel, tasks);
                    toggleOtherInputForRow(row);
                });
            }

            function appendTaskRow() {
                const tasks = window.dailyTaskMasters || {};
                const tr = document.createElement('tr');
                tr.className = 'task-row';
                const td1 = document.createElement('td');
                const sel = document.createElement('select');
                sel.name = 'tasks[' + nextIndex + '][task_master_id]';
                sel.className = 'form-control task-master-select';
                const o0 = document.createElement('option');
                o0.value = '';
                o0.textContent = selectTaskLabel;
                sel.appendChild(o0);
                Object.entries(tasks).forEach(function(entry) {
                    const id = entry[0];
                    const title = entry[1];
                    const o = document.createElement('option');
                    o.value = id;
                    o.textContent = title;
                    sel.appendChild(o);
                });
                const otherOption = document.createElement('option');
                otherOption.value = otherValue;
                otherOption.textContent = otherLabel;
                sel.appendChild(otherOption);
                td1.appendChild(sel);
                const nameInput = document.createElement('input');
                nameInput.type = 'text';
                nameInput.name = 'tasks[' + nextIndex + '][task_name]';
                nameInput.className = 'form-control mt-1 other-task-input d-none';
                nameInput.placeholder = otherNamePlaceholder;
                td1.appendChild(nameInput);
                tr.appendChild(td1);
                const tdH = document.createElement('td');
                const h = document.createElement('input');
                h.type = 'number';
                h.name = 'tasks[' + nextIndex + '][hours]';
                h.className = 'form-control';
                h.min = 0;
                h.max = 24;
                h.value = 0;
                tdH.appendChild(h);
                tr.appendChild(tdH);
                const tdM = document.createElement('td');
                const m = document.createElement('input');
                m.type = 'number';
                m.name = 'tasks[' + nextIndex + '][minutes]';
                m.className = 'form-control';
                m.min = 0;
                m.max = 59;
                m.value = 0;
                tdM.appendChild(m);
                tr.appendChild(tdM);
                const tdN = document.createElement('td');
                const n = document.createElement('input');
                n.type = 'text';
                n.name = 'tasks[' + nextIndex + '][notes]';
                n.className = 'form-control';
                tdN.appendChild(n);
                tr.appendChild(tdN);
                const tdX = document.createElement('td');
                tdX.className = 'text-center';
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'btn btn-sm btn-outline-danger remove-row';
                btn.title = removeTitle;
                btn.innerHTML = '&times;';
                tdX.appendChild(btn);
                tr.appendChild(tdX);
                tbody.appendChild(tr);
                nextIndex++;
            }

            document.getElementById('add-task-row').addEventListener('click', appendTaskRow);

            tbody.addEventListener('click', function(e) {
                if (e.target.classList.contains('remove-row')) {
                    const row = e.target.closest('tr');
                    if (tbody.querySelectorAll('tr').length > 1) {
                        row.remove();
                    }
                }
            });
            tbody.addEventListener('change', function(e) {
                if (e.target.classList.contains('task-master-select')) {
                    const row = e.target.closest('.task-row');
                    if (row) {
                        toggleOtherInputForRow(row);
                    }
                }
            });

            document.querySelectorAll('#task-rows .task-row').forEach(function(row) {
                toggleOtherInputForRow(row);
            });

            document.getElementById('daily-task-form').addEventListener('submit', function() {
                document.querySelectorAll('#task-rows .task-row').forEach(function(row) {
                    const sel = row.querySelector('.task-master-select');
                    if (!sel) {
                        return;
                    }
                    if (sel.value === otherValue) {
                        sel.value = '';
                    } else {
                        const input = row.querySelector('.other-task-input');
                        if (input) {
                            input.value = '';
                        }
                    }
                });
            });

            const empSel = document.getElementById('employee_id_select');
            if (empSel) {
                empSel.addEventListener('change', function() {
                    const employeeId = this.value;
                    if (!employeeId) {
                        return;
                    }
                    const url = taskMastersUrl + (taskMastersUrl.indexOf('?') >= 0 ? '&' : '?') + 'employee_id=' +
                        encodeURIComponent(employeeId);
                    fetch(url, {
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            credentials: 'same-origin'
                        })
                        .then(function(r) {
                            if (!r.ok) {
                                throw new Error('Network');
                            }
                            return r.json();
                        })
                        .then(function(data) {
                            refreshAllTaskMasterSelects(data.tasks || {});
                        })
                        .catch(function() {
                            alert(@json(__('Could not load tasks for this employee.')));
                        });
                });
            }
        })();
    </script>
@endpush
