<form action="{{ route('roles.update', $role->id) }}" method="POST">
    @method('PUT')
    @csrf
    <div class="modal-body">
        <div class="row">
            <div class="col-lg-12 col-md-12 col-sm-12">
                <div class="form-group">
                    <label for="name" class="form-label">{{ __('Name') }}</label>
                    <input type="text" name="name" class="form-control" placeholder="{{ __('Enter Role Name') }}"
                        value="{{ $role->name }}">
                    @error('name')
                        <small class="invalid-name" role="alert">
                            <strong class="text-danger">{{ $message }}</strong>
                        </small>
                    @enderror
                </div>
                <ul class="nav nav-pills mb-3" id="pills-tab" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="pills-staff-tab" data-bs-toggle="pill" href="#staff"
                            role="tab" aria-controls="pills-home" aria-selected="true">{{ __('Staff') }}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="pills-crm-tab" data-bs-toggle="pill" href="#crm" role="tab"
                            aria-controls="pills-profile" aria-selected="false">{{ __('CRM') }}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="pills-project-tab" data-bs-toggle="pill" href="#project" role="tab"
                            aria-controls="pills-contact" aria-selected="false">{{ __('Project') }}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="pills-hrmpermission-tab" data-bs-toggle="pill" href="#hrmpermission"
                            role="tab" aria-controls="pills-contact" aria-selected="false">{{ __('HRM') }}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="pills-account-tab" data-bs-toggle="pill" href="#account" role="tab"
                            aria-controls="pills-contact" aria-selected="false">{{ __('Account') }}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="pills-account-tab" data-bs-toggle="pill" href="#pos" role="tab"
                            aria-controls="pills-contact" aria-selected="false">{{ __('POS') }}</a>
                    </li>

                </ul>
                <div class="tab-content" id="pills-tabContent">
                    <div class="tab-pane fade show active" id="staff" role="tabpanel"
                        aria-labelledby="pills-home-tab">
                        @php
                            $modules = [
                                'user',
                                'role',
                                'client',
                                'product & service',
                                'constant unit',
                                'constant tax',
                                'constant category',
                                'company settings',
                            ];
                            if (\Auth::user()->type == 'company') {
                                $modules[] = 'language';
                                $modules[] = 'permission';
                            }
                        @endphp
                        <div class="col-md-12">
                            <div class="form-group">
                                @if (!empty($permissions))
                                    <h6 class="my-3">{{ __('Assign General Permission to Roles') }}</h6>
                                    <table class="table table-striped mb-0" id="">
                                        <thead>
                                            <tr>
                                                <th>
                                                    <input type="checkbox"
                                                        class="form-check-input align-middle custom_align_middle"
                                                        name="staff_checkall" id="staff_checkall">
                                                </th>
                                                <th>{{ __('Module') }} </th>
                                                <th>{{ __('Permissions') }} </th>
                                            </tr>
                                        </thead>
                                        <tbody>

                                            @foreach ($modules as $module)
                                                <tr>
                                                    <td><input type="checkbox"
                                                            class="form-check-input align-middle ischeck staff_checkall"
                                                            data-id="{{ str_replace(' ', '', str_replace('&', '', $module)) }}">
                                                    </td>
                                                    <td><label class="ischeck staff_checkall"
                                                            data-id="{{ str_replace(' ', '', str_replace('&', '', $module)) }}">{{ ucfirst($module) }}</label>
                                                    </td>
                                                    <td>
                                                        <div class="row ">
                                                            @if (in_array('view ' . $module, (array) $permissions))
                                                                @if ($key = array_search('view ' . $module, $permissions))
                                                                    <div
                                                                        class="col-md-3 custom-control custom-checkbox">
                                                                        <input type="checkbox" name="permissions[]"
                                                                            value="{{ $key }}"
                                                                            class="form-check-input staff_checkall isscheck_{{ str_replace(' ', '', str_replace('&', '', $module)) }}"
                                                                            id="permission{{ $key }}"
                                                                            {{ in_array($key, $role->permissions->pluck('id')->toArray()) ? 'checked' : '' }}>
                                                                        <label for="permission{{ $key }}"
                                                                            class="custom-control-label">View</label><br>
                                                                    </div>
                                                                @endif
                                                            @endif

                                                            @if (in_array('add ' . $module, (array) $permissions))
                                                                @if ($key = array_search('add ' . $module, $permissions))
                                                                    <div
                                                                        class="col-md-3 custom-control custom-checkbox">
                                                                        <input type="checkbox" name="permissions[]"
                                                                            value="{{ $key }}"
                                                                            class="form-check-input staff_checkall isscheck_{{ str_replace(' ', '', str_replace('&', '', $module)) }}"
                                                                            id="permission{{ $key }}"
                                                                            {{ in_array($key, $role->permissions->pluck('id')->toArray()) ? 'checked' : '' }}>
                                                                        <label for="permission{{ $key }}"
                                                                            class="custom-control-label">Add</label><br>
                                                                    </div>
                                                                @endif
                                                            @endif

                                                            @if (in_array('move ' . $module, (array) $permissions))
                                                                @if ($key = array_search('move ' . $module, $permissions))
                                                                    <div
                                                                        class="col-md-3 custom-control custom-checkbox">
                                                                        <input type="checkbox" name="permissions[]"
                                                                            value="{{ $key }}"
                                                                            class="form-check-input staff_checkall isscheck_{{ str_replace(' ', '', str_replace('&', '', $module)) }}"
                                                                            id="permission{{ $key }}"
                                                                            {{ in_array($key, $role->permissions->pluck('id')->toArray()) ? 'checked' : '' }}>
                                                                        <label for="permission{{ $key }}"
                                                                            class="custom-control-label">Move</label><br>
                                                                    </div>
                                                                @endif
                                                            @endif

                                                            @if (in_array('manage ' . $module, (array) $permissions))
                                                                @if ($key = array_search('manage ' . $module, $permissions))
                                                                    <div
                                                                        class="col-md-3 custom-control custom-checkbox">
                                                                        <input type="checkbox" name="permissions[]"
                                                                            value="{{ $key }}"
                                                                            class="form-check-input staff_checkall isscheck_{{ str_replace(' ', '', str_replace('&', '', $module)) }}"
                                                                            id="permission{{ $key }}"
                                                                            {{ in_array($key, $role->permissions->pluck('id')->toArray()) ? 'checked' : '' }}>
                                                                        <label for="permission{{ $key }}"
                                                                            class="custom-control-label">Manage</label><br>
                                                                    </div>
                                                                @endif
                                                            @endif

                                                            @if (in_array('create ' . $module, (array) $permissions))
                                                                @if ($key = array_search('create ' . $module, $permissions))
                                                                    <div
                                                                        class="col-md-3 custom-control custom-checkbox">
                                                                        <input type="checkbox" name="permissions[]"
                                                                            value="{{ $key }}"
                                                                            class="form-check-input staff_checkall isscheck_{{ str_replace(' ', '', str_replace('&', '', $module)) }}"
                                                                            id="permission{{ $key }}"
                                                                            {{ in_array($key, $role->permissions->pluck('id')->toArray()) ? 'checked' : '' }}>
                                                                        <label for="permission{{ $key }}"
                                                                            class="custom-control-label">Create</label><br>
                                                                    </div>
                                                                @endif
                                                            @endif

                                                            @if (in_array('edit ' . $module, (array) $permissions))
                                                                @if ($key = array_search('edit ' . $module, $permissions))
                                                                    <div
                                                                        class="col-md-3 custom-control custom-checkbox">
                                                                        <input type="checkbox" name="permissions[]"
                                                                            value="{{ $key }}"
                                                                            class="form-check-input staff_checkall isscheck_{{ str_replace(' ', '', str_replace('&', '', $module)) }}"
                                                                            id="permission{{ $key }}"
                                                                            {{ in_array($key, $role->permissions->pluck('id')->toArray()) ? 'checked' : '' }}>
                                                                        <label for="permission{{ $key }}"
                                                                            class="custom-control-label">Edit</label><br>
                                                                    </div>
                                                                @endif
                                                            @endif

                                                            @if (in_array('delete ' . $module, (array) $permissions))
                                                                @if ($key = array_search('delete ' . $module, $permissions))
                                                                    <div
                                                                        class="col-md-3 custom-control custom-checkbox">
                                                                        <input type="checkbox" name="permissions[]"
                                                                            value="{{ $key }}"
                                                                            class="form-check-input staff_checkall isscheck_{{ str_replace(' ', '', str_replace('&', '', $module)) }}"
                                                                            id="permission{{ $key }}"
                                                                            {{ in_array($key, $role->permissions->pluck('id')->toArray()) ? 'checked' : '' }}>
                                                                        <label for="permission{{ $key }}"
                                                                            class="custom-control-label">Delete</label><br>
                                                                    </div>
                                                                @endif
                                                            @endif

                                                            @if ($module === 'deal' && in_array('convert lead to deal', (array) $permissions))
                                                                @if ($key = array_search('convert lead to deal', $permissions))
                                                                    <div
                                                                        class="col-md-3 custom-control custom-checkbox">
                                                                        <input type="checkbox" name="permissions[]"
                                                                            value="{{ $key }}"
                                                                            class="form-check-input crm_checkall isscheck_{{ str_replace(' ', '', $module) }}"
                                                                            id="permission{{ $key }}"
                                                                            {{ in_array($key, $role->permissions->pluck('id')->toArray()) ? 'checked' : '' }}>
                                                                        <label for="permission{{ $key }}"
                                                                            class="custom-control-label">Convert Lead To Deal</label><br>
                                                                    </div>
                                                                @endif
                                                            @endif

                                                            @if ($module === 'deal' && in_array('convert lead to deal', (array) $permissions))
                                                                @if ($key = array_search('convert lead to deal', $permissions))
                                                                    <div
                                                                        class="col-md-3 custom-control custom-checkbox">
                                                                        <input type="checkbox" name="permissions[]"
                                                                            value="{{ $key }}"
                                                                            class="form-check-input crm_checkall isscheck_{{ str_replace(' ', '', $module) }}"
                                                                            id="permission{{ $key }}"
                                                                            {{ in_array($key, $role->permissions->pluck('id')->toArray()) ? 'checked' : '' }}>
                                                                        <label for="permission{{ $key }}"
                                                                            class="custom-control-label">Convert Lead To Deal</label><br>
                                                                    </div>
                                                                @endif
                                                            @endif

                                                            @if (in_array('show ' . $module, (array) $permissions))
                                                                @if ($key = array_search('show ' . $module, $permissions))
                                                                    <div
                                                                        class="col-md-3 custom-control custom-checkbox">
                                                                        <input type="checkbox" name="permissions[]"
                                                                            value="{{ $key }}"
                                                                            class="form-check-input staff_checkall isscheck_{{ str_replace(' ', '', str_replace('&', '', $module)) }}"
                                                                            id="permission{{ $key }}"
                                                                            {{ in_array($key, $role->permissions->pluck('id')->toArray()) ? 'checked' : '' }}>
                                                                        <label for="permission{{ $key }}"
                                                                            class="custom-control-label">Show</label><br>
                                                                    </div>
                                                                @endif
                                                            @endif



                                                            @if (in_array('send ' . $module, (array) $permissions))
                                                                @if ($key = array_search('send ' . $module, $permissions))
                                                                    <div
                                                                        class="col-md-3 custom-control custom-checkbox">
                                                                        <input type="checkbox" name="permissions[]"
                                                                            value="{{ $key }}"
                                                                            class="form-check-input staff_checkall isscheck_{{ str_replace(' ', '', str_replace('&', '', $module)) }}"
                                                                            id="permission{{ $key }}"
                                                                            {{ in_array($key, $role->permissions->pluck('id')->toArray()) ? 'checked' : '' }}>
                                                                        <label for="permission{{ $key }}"
                                                                            class="custom-control-label">Send</label><br>
                                                                    </div>
                                                                @endif
                                                            @endif

                                                            @if (in_array('create payment ' . $module, (array) $permissions))
                                                                @if ($key = array_search('create payment ' . $module, $permissions))
                                                                    <div
                                                                        class="col-md-3 custom-control custom-checkbox">
                                                                        <input type="checkbox" name="permissions[]"
                                                                            value="{{ $key }}"
                                                                            class="form-check-input staff_checkall isscheck_{{ str_replace(' ', '', str_replace('&', '', $module)) }}"
                                                                            id="permission{{ $key }}"
                                                                            {{ in_array($key, $role->permissions->pluck('id')->toArray()) ? 'checked' : '' }}>
                                                                        <label for="permission{{ $key }}"
                                                                            class="custom-control-label">Create
                                                                            Payment</label><br>
                                                                    </div>
                                                                @endif
                                                            @endif

                                                            @if (in_array('delete payment ' . $module, (array) $permissions))
                                                                @if ($key = array_search('delete payment ' . $module, $permissions))
                                                                    <div
                                                                        class="col-md-3 custom-control custom-checkbox">
                                                                        <input type="checkbox" name="permissions[]"
                                                                            value="{{ $key }}"
                                                                            class="form-check-input staff_checkall isscheck_{{ str_replace(' ', '', str_replace('&', '', $module)) }}"
                                                                            id="permission{{ $key }}"
                                                                            {{ in_array($key, $role->permissions->pluck('id')->toArray()) ? 'checked' : '' }}>
                                                                        <label for="permission{{ $key }}"
                                                                            class="custom-control-label">Delete
                                                                            Payment</label><br>
                                                                    </div>
                                                                @endif
                                                            @endif

                                                            @if (in_array('income ' . $module, (array) $permissions))
                                                                @if ($key = array_search('income ' . $module, $permissions))
                                                                    <div
                                                                        class="col-md-3 custom-control custom-checkbox">
                                                                        <input type="checkbox" name="permissions[]"
                                                                            value="{{ $key }}"
                                                                            class="form-check-input staff_checkall isscheck_{{ str_replace(' ', '', str_replace('&', '', $module)) }}"
                                                                            id="permission{{ $key }}"
                                                                            {{ in_array($key, $role->permissions->pluck('id')->toArray()) ? 'checked' : '' }}>
                                                                        <label for="permission{{ $key }}"
                                                                            class="custom-control-label">Income</label><br>
                                                                    </div>
                                                                @endif
                                                            @endif

                                                            @if (in_array('expense ' . $module, (array) $permissions))
                                                                @if ($key = array_search('expense ' . $module, $permissions))
                                                                    <div
                                                                        class="col-md-3 custom-control custom-checkbox">
                                                                        <input type="checkbox" name="permissions[]"
                                                                            value="{{ $key }}"
                                                                            class="form-check-input staff_checkall isscheck_{{ str_replace(' ', '', str_replace('&', '', $module)) }}"
                                                                            id="permission{{ $key }}"
                                                                            {{ in_array($key, $role->permissions->pluck('id')->toArray()) ? 'checked' : '' }}>
                                                                        <label for="permission{{ $key }}"
                                                                            class="custom-control-label">Expense</label><br>
                                                                    </div>
                                                                @endif
                                                            @endif

                                                            @if (in_array('income vs expense ' . $module, (array) $permissions))
                                                                @if ($key = array_search('income vs expense ' . $module, $permissions))
                                                                    <div
                                                                        class="col-md-3 custom-control custom-checkbox">
                                                                        <input type="checkbox" name="permissions[]"
                                                                            value="{{ $key }}"
                                                                            class="form-check-input staff_checkall isscheck_{{ str_replace(' ', '', str_replace('&', '', $module)) }}"
                                                                            id="permission{{ $key }}"
                                                                            {{ in_array($key, $role->permissions->pluck('id')->toArray()) ? 'checked' : '' }}>
                                                                        <label for="permission{{ $key }}"
                                                                            class="custom-control-label">Income VS
                                                                            Expense</label><br>
                                                                    </div>
                                                                @endif
                                                            @endif

                                                            @if (in_array('loss & profit ' . $module, (array) $permissions))
                                                                @if ($key = array_search('loss & profit ' . $module, $permissions))
                                                                    <div
                                                                        class="col-md-3 custom-control custom-checkbox">
                                                                        <input type="checkbox" name="permissions[]"
                                                                            value="{{ $key }}"
                                                                            class="form-check-input staff_checkall isscheck_{{ str_replace(' ', '', str_replace('&', '', $module)) }}"
                                                                            id="permission{{ $key }}"
                                                                            {{ in_array($key, $role->permissions->pluck('id')->toArray()) ? 'checked' : '' }}>
                                                                        <label for="permission{{ $key }}"
                                                                            class="custom-control-label">Loss &
                                                                            Profit</label><br>
                                                                    </div>
                                                                @endif
                                                            @endif

                                                            @if (in_array('tax ' . $module, (array) $permissions))
                                                                @if ($key = array_search('tax ' . $module, $permissions))
                                                                    <div
                                                                        class="col-md-3 custom-control custom-checkbox">
                                                                        <input type="checkbox" name="permissions[]"
                                                                            value="{{ $key }}"
                                                                            class="form-check-input staff_checkall isscheck_{{ str_replace(' ', '', str_replace('&', '', $module)) }}"
                                                                            id="permission{{ $key }}"
                                                                            {{ in_array($key, $role->permissions->pluck('id')->toArray()) ? 'checked' : '' }}>
                                                                        <label for="permission{{ $key }}"
                                                                            class="custom-control-label">Tax</label><br>
                                                                    </div>
                                                                @endif
                                                            @endif

                                                            @if (in_array('invoice ' . $module, (array) $permissions))
                                                                @if ($key = array_search('invoice ' . $module, $permissions))
                                                                    <div
                                                                        class="col-md-3 custom-control custom-checkbox">
                                                                        <input type="checkbox" name="permissions[]"
                                                                            value="{{ $key }}"
                                                                            class="form-check-input staff_checkall isscheck_{{ str_replace(' ', '', str_replace('&', '', $module)) }}"
                                                                            id="permission{{ $key }}"
                                                                            {{ in_array($key, $role->permissions->pluck('id')->toArray()) ? 'checked' : '' }}>
                                                                        <label for="permission{{ $key }}"
                                                                            class="custom-control-label">Invoice</label><br>
                                                                    </div>
                                                                @endif
                                                            @endif

                                                            @if (in_array('bill ' . $module, (array) $permissions))
                                                                @if ($key = array_search('bill ' . $module, $permissions))
                                                                    <div
                                                                        class="col-md-3 custom-control custom-checkbox">
                                                                        <input type="checkbox" name="permissions[]"
                                                                            value="{{ $key }}"
                                                                            class="form-check-input staff_checkall isscheck_{{ str_replace(' ', '', str_replace('&', '', $module)) }}"
                                                                            id="permission{{ $key }}"
                                                                            {{ in_array($key, $role->permissions->pluck('id')->toArray()) ? 'checked' : '' }}>
                                                                        <label for="permission{{ $key }}"
                                                                            class="custom-control-label">Bill</label><br>
                                                                    </div>
                                                                @endif
                                                            @endif

                                                            @if (in_array('duplicate ' . $module, (array) $permissions))
                                                                @if ($key = array_search('duplicate ' . $module, $permissions))
                                                                    <div
                                                                        class="col-md-3 custom-control custom-checkbox">
                                                                        <input type="checkbox" name="permissions[]"
                                                                            value="{{ $key }}"
                                                                            class="form-check-input staff_checkall isscheck_{{ str_replace(' ', '', str_replace('&', '', $module)) }}"
                                                                            id="permission{{ $key }}"
                                                                            {{ in_array($key, $role->permissions->pluck('id')->toArray()) ? 'checked' : '' }}>
                                                                        <label for="permission{{ $key }}"
                                                                            class="custom-control-label">Duplicate</label><br>
                                                                    </div>
                                                                @endif
                                                            @endif
                                                            @if (in_array('balance sheet ' . $module, (array) $permissions))
                                                                @if ($key = array_search('balance sheet ' . $module, $permissions))
                                                                    <div
                                                                        class="col-md-3 custom-control custom-checkbox">
                                                                        <input type="checkbox" name="permissions[]"
                                                                            value="{{ $key }}"
                                                                            class="form-check-input staff_checkall isscheck_{{ str_replace(' ', '', str_replace('&', '', $module)) }}"
                                                                            id="permission{{ $key }}"
                                                                            {{ in_array($key, $role->permissions->pluck('id')->toArray()) ? 'checked' : '' }}>
                                                                        <label for="permission{{ $key }}"
                                                                            class="custom-control-label">Balance
                                                                            Sheet</label><br>
                                                                    </div>
                                                                @endif
                                                            @endif

                                                            @if (in_array('ledger ' . $module, (array) $permissions))
                                                                @if ($key = array_search('ledger ' . $module, $permissions))
                                                                    <div
                                                                        class="col-md-3 custom-control custom-checkbox">
                                                                        <input type="checkbox" name="permissions[]"
                                                                            value="{{ $key }}"
                                                                            class="form-check-input staff_checkall isscheck_{{ str_replace(' ', '', str_replace('&', '', $module)) }}"
                                                                            id="permission{{ $key }}"
                                                                            {{ in_array($key, $role->permissions->pluck('id')->toArray()) ? 'checked' : '' }}>
                                                                        <label for="permission{{ $key }}"
                                                                            class="custom-control-label">Ledger</label><br>
                                                                    </div>
                                                                @endif
                                                            @endif

                                                            @if (in_array('trial balance ' . $module, (array) $permissions))
                                                                @if ($key = array_search('trial balance ' . $module, $permissions))
                                                                    <div
                                                                        class="col-md-3 custom-control custom-checkbox">
                                                                        <input type="checkbox" name="permissions[]"
                                                                            value="{{ $key }}"
                                                                            class="form-check-input staff_checkall isscheck_{{ str_replace(' ', '', str_replace('&', '', $module)) }}"
                                                                            id="permission{{ $key }}"
                                                                            {{ in_array($key, $role->permissions->pluck('id')->toArray()) ? 'checked' : '' }}>
                                                                        <label for="permission{{ $key }}"
                                                                            class="custom-control-label">Trial
                                                                            Balance</label><br>
                                                                    </div>
                                                                @endif
                                                            @endif

                                                            @if (in_array('statement ' . $module, (array) $permissions))
                                                                @if ($key = array_search('statement ' . $module, $permissions))
                                                                    <div
                                                                        class="col-md-3 custom-control custom-checkbox">
                                                                        <input type="checkbox" name="permissions[]"
                                                                            value="{{ $key }}"
                                                                            class="form-check-input account_checkall isscheck_{{ str_replace(' ', '', $module) }}"
                                                                            id="permission{{ $key }}"
                                                                            {{ in_array($key, $role->permissions->pluck('id')->toArray()) ? 'checked' : '' }}>
                                                                        <label for="permission{{ $key }}"
                                                                            class="custom-control-label">Statement</label><br>
                                                                    </div>
                                                                @endif
                                                            @endif

                                                            @if (in_array('stock ' . $module, (array) $permissions))
                                                                @if ($key = array_search('stock ' . $module, $permissions))
                                                                    <div
                                                                        class="col-md-3 custom-control custom-checkbox">
                                                                        <input type="checkbox" name="permissions[]"
                                                                            value="{{ $key }}"
                                                                            class="form-check-input account_checkall isscheck_{{ str_replace(' ', '', $module) }}"
                                                                            id="permission{{ $key }}"
                                                                            {{ in_array($key, $role->permissions->pluck('id')->toArray()) ? 'checked' : '' }}>
                                                                        <label for="permission{{ $key }}"
                                                                            class="custom-control-label">Stock</label><br>
                                                                    </div>
                                                                @endif
                                                            @endif

                                                            @if (in_array('general ledger ' . $module, (array) $permissions))
                                                                @if ($key = array_search('general ledger ' . $module, $permissions))
                                                                    <div
                                                                        class="col-md-3 custom-control custom-checkbox">
                                                                        <input type="checkbox" name="permissions[]"
                                                                            value="{{ $key }}"
                                                                            class="form-check-input account_checkall isscheck_{{ str_replace(' ', '', $module) }}"
                                                                            id="permission{{ $key }}"
                                                                            {{ in_array($key, $role->permissions->pluck('id')->toArray()) ? 'checked' : '' }}>
                                                                        <label for="permission{{ $key }}"
                                                                            class="custom-control-label">General Ledger</label><br>
                                                                    </div>
                                                                @endif
                                                            @endif

                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="crm" role="tabpanel" aria-labelledby="pills-profile-tab">
                        @php
                            $modules = [
                                'crm dashboard',
                                'lead',
                                'pipeline',
                                'lead stage',
                                'source',
                                'label',
                                'deal',
                                'crm admin',
                                'stage',
                                'task',
                                'form builder',
                                'form response',
                                'contract',
                                'contract type',
                            ];
                        @endphp
                        <div class="col-md-12">
                            <div class="form-group">
                                @if (!empty($permissions))
                                    <h6 class="my-3">{{ __('Assign CRM related Permission to Roles') }}</h6>
                                    <table class="table table-striped mb-0" id="">
                                        <thead>
                                            <tr>
                                                <th>
                                                    <input type="checkbox"
                                                        class="form-check-input align-middle custom_align_middle"
                                                        name="crm_checkall" id="crm_checkall">
                                                </th>
                                                <th>{{ __('Module') }} </th>
                                                <th>{{ __('Permissions') }} </th>
                                            </tr>
                                        </thead>
                                        <tbody>

                                            @foreach ($modules as $module)
                                                <tr>
                                                    <td><input type="checkbox"
                                                            class="form-check-input align-middle ischeck crm_checkall"
                                                            data-id="{{ str_replace(' ', '', $module) }}"></td>
                                                    <td><label class="ischeck crm_checkall"
                                                            data-id="{{ str_replace(' ', '', $module) }}">{{ ucfirst($module) }}</label>
                                                    </td>
                                                    <td>
                                                        <div class="row ">
                                                            @if (in_array('view ' . $module, (array) $permissions))
                                                                @if ($key = array_search('view ' . $module, $permissions))
                                                                    <div
                                                                        class="col-md-3 custom-control custom-checkbox">
                                                                        <input type="checkbox" name="permissions[]"
                                                                            value="{{ $key }}"
                                                                            class="form-check-input crm_checkall isscheck_{{ str_replace(' ', '', $module) }}"
                                                                            id="permission{{ $key }}"
                                                                            {{ in_array($key, $role->permissions->pluck('id')->toArray()) ? 'checked' : '' }}>
                                                                        <label for="permission{{ $key }}"
                                                                            class="custom-control-label">View</label><br>
                                                                    </div>
                                                                @endif
                                                            @endif

                                                            @if (in_array('add ' . $module, (array) $permissions))
                                                                @if ($key = array_search('add ' . $module, $permissions))
                                                                    <div
                                                                        class="col-md-3 custom-control custom-checkbox">
                                                                        <input type="checkbox" name="permissions[]"
                                                                            value="{{ $key }}"
                                                                            class="form-check-input crm_checkall isscheck_{{ str_replace(' ', '', $module) }}"
                                                                            id="permission{{ $key }}"
                                                                            {{ in_array($key, $role->permissions->pluck('id')->toArray()) ? 'checked' : '' }}>
                                                                        <label for="permission{{ $key }}"
                                                                            class="custom-control-label">Add</label><br>
                                                                    </div>
                                                                @endif
                                                            @endif

                                                            @if (in_array('move ' . $module, (array) $permissions))
                                                                @if ($key = array_search('move ' . $module, $permissions))
                                                                    <div
                                                                        class="col-md-3 custom-control custom-checkbox">
                                                                        <input type="checkbox" name="permissions[]"
                                                                            value="{{ $key }}"
                                                                            class="form-check-input crm_checkall isscheck_{{ str_replace(' ', '', $module) }}"
                                                                            id="permission{{ $key }}"
                                                                            {{ in_array($key, $role->permissions->pluck('id')->toArray()) ? 'checked' : '' }}>
                                                                        <label for="permission{{ $key }}"
                                                                            class="custom-control-label">Move</label><br>
                                                                    </div>
                                                                @endif
                                                            @endif

                                                            @if (in_array('manage ' . $module, (array) $permissions))
                                                                @if ($key = array_search('manage ' . $module, $permissions))
                                                                    <div
                                                                        class="col-md-3 custom-control custom-checkbox">
                                                                        <input type="checkbox" name="permissions[]"
                                                                            value="{{ $key }}"
                                                                            class="form-check-input crm_checkall isscheck_{{ str_replace(' ', '', $module) }}"
                                                                            id="permission{{ $key }}"
                                                                            {{ in_array($key, $role->permissions->pluck('id')->toArray()) ? 'checked' : '' }}>
                                                                        <label for="permission{{ $key }}"
                                                                            class="custom-control-label">Manage</label><br>
                                                                    </div>
                                                                @endif
                                                            @endif

                                                            @if (in_array('create ' . $module, (array) $permissions))
                                                                @if ($key = array_search('create ' . $module, $permissions))
                                                                    <div
                                                                        class="col-md-3 custom-control custom-checkbox">
                                                                        <input type="checkbox" name="permissions[]"
                                                                            value="{{ $key }}"
                                                                            class="form-check-input crm_checkall isscheck_{{ str_replace(' ', '', $module) }}"
                                                                            id="permission{{ $key }}"
                                                                            {{ in_array($key, $role->permissions->pluck('id')->toArray()) ? 'checked' : '' }}>
                                                                        <label for="permission{{ $key }}"
                                                                            class="custom-control-label">Create</label><br>
                                                                    </div>
                                                                @endif
                                                            @endif

                                                            @if (in_array('edit ' . $module, (array) $permissions))
                                                                @if ($key = array_search('edit ' . $module, $permissions))
                                                                    <div
                                                                        class="col-md-3 custom-control custom-checkbox">
                                                                        <input type="checkbox" name="permissions[]"
                                                                            value="{{ $key }}"
                                                                            class="form-check-input crm_checkall isscheck_{{ str_replace(' ', '', $module) }}"
                                                                            id="permission{{ $key }}"
                                                                            {{ in_array($key, $role->permissions->pluck('id')->toArray()) ? 'checked' : '' }}>
                                                                        <label for="permission{{ $key }}"
                                                                            class="custom-control-label">Edit</label><br>
                                                                    </div>
                                                                @endif
                                                            @endif

                                                            @if (in_array('delete ' . $module, (array) $permissions))
                                                                @if ($key = array_search('delete ' . $module, $permissions))
                                                                    <div
                                                                        class="col-md-3 custom-control custom-checkbox">
                                                                        <input type="checkbox" name="permissions[]"
                                                                            value="{{ $key }}"
                                                                            class="form-check-input crm_checkall isscheck_{{ str_replace(' ', '', $module) }}"
                                                                            id="permission{{ $key }}"
                                                                            {{ in_array($key, $role->permissions->pluck('id')->toArray()) ? 'checked' : '' }}>
                                                                        <label for="permission{{ $key }}"
                                                                            class="custom-control-label">Delete</label><br>
                                                                    </div>
                                                                @endif
                                                            @endif

                                                            @if (in_array('show ' . $module, (array) $permissions))
                                                                @if ($key = array_search('show ' . $module, $permissions))
                                                                    <div
                                                                        class="col-md-3 custom-control custom-checkbox">
                                                                        <input type="checkbox" name="permissions[]"
                                                                            value="{{ $key }}"
                                                                            class="form-check-input crm_checkall isscheck_{{ str_replace(' ', '', $module) }}"
                                                                            id="permission{{ $key }}"
                                                                            {{ in_array($key, $role->permissions->pluck('id')->toArray()) ? 'checked' : '' }}>
                                                                        <label for="permission{{ $key }}"
                                                                            class="custom-control-label">Show</label><br>
                                                                    </div>
                                                                @endif
                                                            @endif


                                                            @if (in_array('send ' . $module, (array) $permissions))
                                                                @if ($key = array_search('send ' . $module, $permissions))
                                                                    <div
                                                                        class="col-md-3 custom-control custom-checkbox">
                                                                        <input type="checkbox" name="permissions[]"
                                                                            value="{{ $key }}"
                                                                            class="form-check-input crm_checkall isscheck_{{ str_replace(' ', '', $module) }}"
                                                                            id="permission{{ $key }}"
                                                                            {{ in_array($key, $role->permissions->pluck('id')->toArray()) ? 'checked' : '' }}>
                                                                        <label for="permission{{ $key }}"
                                                                            class="custom-control-label">Send</label><br>
                                                                    </div>
                                                                @endif
                                                            @endif

                                                            @if (in_array('create payment ' . $module, (array) $permissions))
                                                                @if ($key = array_search('create payment ' . $module, $permissions))
                                                                    <div
                                                                        class="col-md-3 custom-control custom-checkbox">
                                                                        <input type="checkbox" name="permissions[]"
                                                                            value="{{ $key }}"
                                                                            class="form-check-input crm_checkall isscheck_{{ str_replace(' ', '', $module) }}"
                                                                            id="permission{{ $key }}"
                                                                            {{ in_array($key, $role->permissions->pluck('id')->toArray()) ? 'checked' : '' }}>
                                                                        <label for="permission{{ $key }}"
                                                                            class="custom-control-label">Create
                                                                            Payment</label><br>
                                                                    </div>
                                                                @endif
                                                            @endif

                                                            @if (in_array('delete payment ' . $module, (array) $permissions))
                                                                @if ($key = array_search('delete payment ' . $module, $permissions))
                                                                    <div
                                                                        class="col-md-3 custom-control custom-checkbox">
                                                                        <input type="checkbox" name="permissions[]"
                                                                            value="{{ $key }}"
                                                                            class="form-check-input crm_checkall isscheck_{{ str_replace(' ', '', $module) }}"
                                                                            id="permission{{ $key }}"
                                                                            {{ in_array($key, $role->permissions->pluck('id')->toArray()) ? 'checked' : '' }}>
                                                                        <label for="permission{{ $key }}"
                                                                            class="custom-control-label">Delete
                                                                            Payment</label><br>
                                                                    </div>
                                                                @endif
                                                            @endif

                                                            @if (in_array('income ' . $module, (array) $permissions))
                                                                @if ($key = array_search('income ' . $module, $permissions))
                                                                    <div
                                                                        class="col-md-3 custom-control custom-checkbox">
                                                                        <input type="checkbox" name="permissions[]"
                                                                            value="{{ $key }}"
                                                                            class="form-check-input crm_checkall isscheck_{{ str_replace(' ', '', $module) }}"
                                                                            id="permission{{ $key }}"
                                                                            {{ in_array($key, $role->permissions->pluck('id')->toArray()) ? 'checked' : '' }}>
                                                                        <label for="permission{{ $key }}"
                                                                            class="custom-control-label">Income</label><br>
                                                                    </div>
                                                                @endif
                                                            @endif

                                                            @if (in_array('expense ' . $module, (array) $permissions))
                                                                @if ($key = array_search('expense ' . $module, $permissions))
                                                                    <div
                                                                        class="col-md-3 custom-control custom-checkbox">
                                                                        <input type="checkbox" name="permissions[]"
                                                                            value="{{ $key }}"
                                                                            class="form-check-input crm_checkall isscheck_{{ str_replace(' ', '', $module) }}"
                                                                            id="permission{{ $key }}"
                                                                            {{ in_array($key, $role->permissions->pluck('id')->toArray()) ? 'checked' : '' }}>
                                                                        <label for="permission{{ $key }}"
                                                                            class="custom-control-label">Expense</label><br>
                                                                    </div>
                                                                @endif
                                                            @endif

                                                            @if (in_array('income vs expense ' . $module, (array) $permissions))
                                                                @if ($key = array_search('income vs expense ' . $module, $permissions))
                                                                    <div
                                                                        class="col-md-3 custom-control custom-checkbox">
                                                                        <input type="checkbox" name="permissions[]"
                                                                            value="{{ $key }}"
                                                                            class="form-check-input crm_checkall isscheck_{{ str_replace(' ', '', $module) }}"
                                                                            id="permission{{ $key }}"
                                                                            {{ in_array($key, $role->permissions->pluck('id')->toArray()) ? 'checked' : '' }}>
                                                                        <label for="permission{{ $key }}"
                                                                            class="custom-control-label">Income VS
                                                                            Expense</label><br>
                                                                    </div>
                                                                @endif
                                                            @endif

                                                            @if (in_array('loss & profit ' . $module, (array) $permissions))
                                                                @if ($key = array_search('loss & profit ' . $module, $permissions))
                                                                    <div
                                                                        class="col-md-3 custom-control custom-checkbox">
                                                                        <input type="checkbox" name="permissions[]"
                                                                            value="{{ $key }}"
                                                                            class="form-check-input crm_checkall isscheck_{{ str_replace(' ', '', $module) }}"
                                                                            id="permission{{ $key }}"
                                                                            {{ in_array($key, $role->permissions->pluck('id')->toArray()) ? 'checked' : '' }}>
                                                                        <label for="permission{{ $key }}"
                                                                            class="custom-control-label">Loss &
                                                                            Profit</label><br>
                                                                    </div>
                                                                @endif
                                                            @endif

                                                            @if (in_array('tax ' . $module, (array) $permissions))
                                                                @if ($key = array_search('tax ' . $module, $permissions))
                                                                    <div
                                                                        class="col-md-3 custom-control custom-checkbox">
                                                                        <input type="checkbox" name="permissions[]"
                                                                            value="{{ $key }}"
                                                                            class="form-check-input crm_checkall isscheck_{{ str_replace(' ', '', $module) }}"
                                                                            id="permission{{ $key }}"
                                                                            {{ in_array($key, $role->permissions->pluck('id')->toArray()) ? 'checked' : '' }}>
                                                                        <label for="permission{{ $key }}"
                                                                            class="custom-control-label">Tax</label><br>
                                                                    </div>
                                                                @endif
                                                            @endif

                                                            @if (in_array('invoice ' . $module, (array) $permissions))
                                                                @if ($key = array_search('invoice ' . $module, $permissions))
                                                                    <div
                                                                        class="col-md-3 custom-control custom-checkbox">
                                                                        <input type="checkbox" name="permissions[]"
                                                                            value="{{ $key }}"
                                                                            class="form-check-input crm_checkall isscheck_{{ str_replace(' ', '', $module) }}"
                                                                            id="permission{{ $key }}"
                                                                            {{ in_array($key, $role->permissions->pluck('id')->toArray()) ? 'checked' : '' }}>
                                                                        <label for="permission{{ $key }}"
                                                                            class="custom-control-label">Invoice</label><br>
                                                                    </div>
                                                                @endif
                                                            @endif

                                                            @if (in_array('bill ' . $module, (array) $permissions))
                                                                @if ($key = array_search('bill ' . $module, $permissions))
                                                                    <div
                                                                        class="col-md-3 custom-control custom-checkbox">
                                                                        <input type="checkbox" name="permissions[]"
                                                                            value="{{ $key }}"
                                                                            class="form-check-input crm_checkall isscheck_{{ str_replace(' ', '', $module) }}"
                                                                            id="permission{{ $key }}"
                                                                            {{ in_array($key, $role->permissions->pluck('id')->toArray()) ? 'checked' : '' }}>
                                                                        <label for="permission{{ $key }}"
                                                                            class="custom-control-label">Bill</label><br>
                                                                    </div>
                                                                @endif
                                                            @endif

                                                            @if (in_array('duplicate ' . $module, (array) $permissions))
                                                                @if ($key = array_search('duplicate ' . $module, $permissions))
                                                                    <div
                                                                        class="col-md-3 custom-control custom-checkbox">
                                                                        <input type="checkbox" name="permissions[]"
                                                                            value="{{ $key }}"
                                                                            class="form-check-input crm_checkall isscheck_{{ str_replace(' ', '', $module) }}"
                                                                            id="permission{{ $key }}"
                                                                            {{ in_array($key, $role->permissions->pluck('id')->toArray()) ? 'checked' : '' }}>
                                                                        <label for="permission{{ $key }}"
                                                                            class="custom-control-label">Duplicate</label><br>
                                                                    </div>
                                                                @endif
                                                            @endif

                                                            @if (in_array('balance sheet ' . $module, (array) $permissions))
                                                                @if ($key = array_search('balance sheet ' . $module, $permissions))
                                                                    <div
                                                                        class="col-md-3 custom-control custom-checkbox">
                                                                        <input type="checkbox" name="permissions[]"
                                                                            value="{{ $key }}"
                                                                            class="form-check-input crm_checkall isscheck_{{ str_replace(' ', '', $module) }}"
                                                                            id="permission{{ $key }}"
                                                                            {{ in_array($key, $role->permissions->pluck('id')->toArray()) ? 'checked' : '' }}>
                                                                        <label for="permission{{ $key }}"
                                                                            class="custom-control-label">Balance
                                                                            Sheet</label><br>
                                                                    </div>
                                                                @endif
                                                            @endif

                                                            @if (in_array('ledger ' . $module, (array) $permissions))
                                                                @if ($key = array_search('ledger ' . $module, $permissions))
                                                                    <div
                                                                        class="col-md-3 custom-control custom-checkbox">
                                                                        <input type="checkbox" name="permissions[]"
                                                                            value="{{ $key }}"
                                                                            class="form-check-input crm_checkall isscheck_{{ str_replace(' ', '', $module) }}"
                                                                            id="permission{{ $key }}"
                                                                            {{ in_array($key, $role->permissions->pluck('id')->toArray()) ? 'checked' : '' }}>
                                                                        <label for="permission{{ $key }}"
                                                                            class="custom-control-label">Ledger</label><br>
                                                                    </div>
                                                                @endif
                                                            @endif

                                                            @if (in_array('trial balance ' . $module, (array) $permissions))
                                                                @if ($key = array_search('trial balance ' . $module, $permissions))
                                                                    <div
                                                                        class="col-md-3 custom-control custom-checkbox">
                                                                        <input type="checkbox" name="permissions[]"
                                                                            value="{{ $key }}"
                                                                            class="form-check-input crm_checkall isscheck_{{ str_replace(' ', '', $module) }}"
                                                                            id="permission{{ $key }}"
                                                                            {{ in_array($key, $role->permissions->pluck('id')->toArray()) ? 'checked' : '' }}>
                                                                        <label for="permission{{ $key }}"
                                                                            class="custom-control-label">Trial
                                                                            Balance</label><br>
                                                                    </div>
                                                                @endif
                                                            @endif

                                                            @if (in_array('statement ' . $module, (array) $permissions))
                                                                @if ($key = array_search('statement ' . $module, $permissions))
                                                                    <div
                                                                        class="col-md-3 custom-control custom-checkbox">
                                                                        <input type="checkbox" name="permissions[]"
                                                                            value="{{ $key }}"
                                                                            class="form-check-input account_checkall isscheck_{{ str_replace(' ', '', $module) }}"
                                                                            id="permission{{ $key }}"
                                                                            {{ in_array($key, $role->permissions->pluck('id')->toArray()) ? 'checked' : '' }}>
                                                                        <label for="permission{{ $key }}"
                                                                            class="custom-control-label">Statement</label><br>
                                                                    </div>
                                                                @endif
                                                            @endif

                                                            @if (in_array('stock ' . $module, (array) $permissions))
                                                                @if ($key = array_search('stock ' . $module, $permissions))
                                                                    <div
                                                                        class="col-md-3 custom-control custom-checkbox">
                                                                        <input type="checkbox" name="permissions[]"
                                                                            value="{{ $key }}"
                                                                            class="form-check-input account_checkall isscheck_{{ str_replace(' ', '', $module) }}"
                                                                            id="permission{{ $key }}"
                                                                            {{ in_array($key, $role->permissions->pluck('id')->toArray()) ? 'checked' : '' }}>
                                                                        <label for="permission{{ $key }}"
                                                                            class="custom-control-label">Stock</label><br>
                                                                    </div>
                                                                @endif
                                                            @endif

                                                            @if (in_array('general ledger ' . $module, (array) $permissions))
                                                                @if ($key = array_search('general ledger ' . $module, $permissions))
                                                                    <div
                                                                        class="col-md-3 custom-control custom-checkbox">
                                                                        <input type="checkbox" name="permissions[]"
                                                                            value="{{ $key }}"
                                                                            class="form-check-input account_checkall isscheck_{{ str_replace(' ', '', $module) }}"
                                                                            id="permission{{ $key }}"
                                                                            {{ in_array($key, $role->permissions->pluck('id')->toArray()) ? 'checked' : '' }}>
                                                                        <label for="permission{{ $key }}"
                                                                            class="custom-control-label">General Ledger</label><br>
                                                                    </div>
                                                                @endif
                                                            @endif

                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="project" role="tabpanel" aria-labelledby="pills-contact-tab">
                        @php
                            $modules = [
                                'project dashboard',
                                'project',
                                'milestone',
                                'grant chart',
                                'project stage',
                                'timesheet',
                                'expense',
                                'project task',
                                'activity',
                                'CRM activity',
                                'project task stage',
                                'bug report',
                                'bug status',
                            ];
                        @endphp
                        <div class="col-md-12">
                            <div class="form-group">
                                @if (!empty($permissions))
                                    <h6 class="my-3">{{ __('Assign Project related Permission to Roles') }}</h6>
                                    <table class="table table-striped mb-0" id="">
                                        <thead>
                                            <tr>
                                                <th>
                                                    <input type="checkbox"
                                                        class="form-check-input align-middle custom_align_middle"
                                                        name="project_checkall" id="project_checkall">
                                                </th>
                                                <th>{{ __('Module') }} </th>
                                                <th>{{ __('Permissions') }} </th>
                                            </tr>
                                        </thead>
                                        <tbody>

                                            @foreach ($modules as $module)
                                                <tr>
                                                    <td><input type="checkbox"
                                                            class="form-check-input align-middle ischeck project_checkall"
                                                            data-id="{{ str_replace(' ', '', $module) }}"></td>
                                                    <td><label class="ischeck project_checkall"
                                                            data-id="{{ str_replace(' ', '', $module) }}">{{ ucfirst($module) }}</label>
                                                    </td>
                                                    <td>
                                                        <div class="row ">
                                                            @if (in_array('view ' . $module, (array) $permissions))
                                                                @if ($key = array_search('view ' . $module, $permissions))
                                                                    <div
                                                                        class="col-md-3 custom-control custom-checkbox">
                                                                        <input type="checkbox" name="permissions[]"
                                                                            value="{{ $key }}"
                                                                            class="form-check-input project_checkall isscheck_{{ str_replace(' ', '', $module) }}"
                                                                            id="permission{{ $key }}"
                                                                            {{ in_array($key, $role->permissions->pluck('id')->toArray()) ? 'checked' : '' }}>
                                                                        <label for="permission{{ $key }}"
                                                                            class="custom-control-label">View</label><br>
                                                                    </div>
                                                                @endif
                                                            @endif

                                                            @if (in_array('add ' . $module, (array) $permissions))
                                                                @if ($key = array_search('add ' . $module, $permissions))
                                                                    <div
                                                                        class="col-md-3 custom-control custom-checkbox">
                                                                        <input type="checkbox" name="permissions[]"
                                                                            value="{{ $key }}"
                                                                            class="form-check-input project_checkall isscheck_{{ str_replace(' ', '', $module) }}"
                                                                            id="permission{{ $key }}"
                                                                            {{ in_array($key, $role->permissions->pluck('id')->toArray()) ? 'checked' : '' }}>
                                                                        <label for="permission{{ $key }}"
                                                                            class="custom-control-label">Add</label><br>
                                                                    </div>
                                                                @endif
                                                            @endif

                                                            @if (in_array('move ' . $module, (array) $permissions))
                                                                @if ($key = array_search('move ' . $module, $permissions))
                                                                    <div
                                                                        class="col-md-3 custom-control custom-checkbox">
                                                                        <input type="checkbox" name="permissions[]"
                                                                            value="{{ $key }}"
                                                                            class="form-check-input project_checkall isscheck_{{ str_replace(' ', '', $module) }}"
                                                                            id="permission{{ $key }}"
                                                                            {{ in_array($key, $role->permissions->pluck('id')->toArray()) ? 'checked' : '' }}>
                                                                        <label for="permission{{ $key }}"
                                                                            class="custom-control-label">Move</label><br>
                                                                    </div>
                                                                @endif
                                                            @endif

                                                            @if (in_array('manage ' . $module, (array) $permissions))
                                                                @if ($key = array_search('manage ' . $module, $permissions))
                                                                    <div
                                                                        class="col-md-3 custom-control custom-checkbox">
                                                                        <input type="checkbox" name="permissions[]"
                                                                            value="{{ $key }}"
                                                                            class="form-check-input project_checkall isscheck_{{ str_replace(' ', '', $module) }}"
                                                                            id="permission{{ $key }}"
                                                                            {{ in_array($key, $role->permissions->pluck('id')->toArray()) ? 'checked' : '' }}>
                                                                        <label for="permission{{ $key }}"
                                                                            class="custom-control-label">Manage</label><br>
                                                                    </div>
                                                                @endif
                                                            @endif

                                                            @if (in_array('create ' . $module, (array) $permissions))
                                                                @if ($key = array_search('create ' . $module, $permissions))
                                                                    <div
                                                                        class="col-md-3 custom-control custom-checkbox">
                                                                        <input type="checkbox" name="permissions[]"
                                                                            value="{{ $key }}"
                                                                            class="form-check-input project_checkall isscheck_{{ str_replace(' ', '', $module) }}"
                                                                            id="permission{{ $key }}"
                                                                            {{ in_array($key, $role->permissions->pluck('id')->toArray()) ? 'checked' : '' }}>
                                                                        <label for="permission{{ $key }}"
                                                                            class="custom-control-label">Create</label><br>
                                                                    </div>
                                                                @endif
                                                            @endif

                                                            @if (in_array('edit ' . $module, (array) $permissions))
                                                                @if ($key = array_search('edit ' . $module, $permissions))
                                                                    <div
                                                                        class="col-md-3 custom-control custom-checkbox">
                                                                        <input type="checkbox" name="permissions[]"
                                                                            value="{{ $key }}"
                                                                            class="form-check-input project_checkall isscheck_{{ str_replace(' ', '', $module) }}"
                                                                            id="permission{{ $key }}"
                                                                            {{ in_array($key, $role->permissions->pluck('id')->toArray()) ? 'checked' : '' }}>
                                                                        <label for="permission{{ $key }}"
                                                                            class="custom-control-label">Edit</label><br>
                                                                    </div>
                                                                @endif
                                                            @endif

                                                            @if (in_array('delete ' . $module, (array) $permissions))
                                                                @if ($key = array_search('delete ' . $module, $permissions))
                                                                    <div
                                                                        class="col-md-3 custom-control custom-checkbox">
                                                                        <input type="checkbox" name="permissions[]"
                                                                            value="{{ $key }}"
                                                                            class="form-check-input project_checkall isscheck_{{ str_replace(' ', '', $module) }}"
                                                                            id="permission{{ $key }}"
                                                                            {{ in_array($key, $role->permissions->pluck('id')->toArray()) ? 'checked' : '' }}>
                                                                        <label for="permission{{ $key }}"
                                                                            class="custom-control-label">Delete</label><br>
                                                                    </div>
                                                                @endif
                                                            @endif

                                                            @if (in_array('show ' . $module, (array) $permissions))
                                                                @if ($key = array_search('show ' . $module, $permissions))
                                                                    <div
                                                                        class="col-md-3 custom-control custom-checkbox">
                                                                        <input type="checkbox" name="permissions[]"
                                                                            value="{{ $key }}"
                                                                            class="form-check-input project_checkall isscheck_{{ str_replace(' ', '', $module) }}"
                                                                            id="permission{{ $key }}"
                                                                            {{ in_array($key, $role->permissions->pluck('id')->toArray()) ? 'checked' : '' }}>
                                                                        <label for="permission{{ $key }}"
                                                                            class="custom-control-label">Show</label><br>
                                                                    </div>
                                                                @endif
                                                            @endif



                                                            @if (in_array('send ' . $module, (array) $permissions))
                                                                @if ($key = array_search('send ' . $module, $permissions))
                                                                    <div
                                                                        class="col-md-3 custom-control custom-checkbox">
                                                                        <input type="checkbox" name="permissions[]"
                                                                            value="{{ $key }}"
                                                                            class="form-check-input project_checkall isscheck_{{ str_replace(' ', '', $module) }}"
                                                                            id="permission{{ $key }}"
                                                                            {{ in_array($key, $role->permissions->pluck('id')->toArray()) ? 'checked' : '' }}>
                                                                        <label for="permission{{ $key }}"
                                                                            class="custom-control-label">Send</label><br>
                                                                    </div>
                                                                @endif
                                                            @endif

                                                            @if (in_array('create payment ' . $module, (array) $permissions))
                                                                @if ($key = array_search('create payment ' . $module, $permissions))
                                                                    <div
                                                                        class="col-md-3 custom-control custom-checkbox">
                                                                        <input type="checkbox" name="permissions[]"
                                                                            value="{{ $key }}"
                                                                            class="form-check-input project_checkall isscheck_{{ str_replace(' ', '', $module) }}"
                                                                            id="permission{{ $key }}"
                                                                            {{ in_array($key, $role->permissions->pluck('id')->toArray()) ? 'checked' : '' }}>
                                                                        <label for="permission{{ $key }}"
                                                                            class="custom-control-label">Create
                                                                            Payment</label><br>
                                                                    </div>
                                                                @endif
                                                            @endif

                                                            @if (in_array('delete payment ' . $module, (array) $permissions))
                                                                @if ($key = array_search('delete payment ' . $module, $permissions))
                                                                    <div
                                                                        class="col-md-3 custom-control custom-checkbox">
                                                                        <input type="checkbox" name="permissions[]"
                                                                            value="{{ $key }}"
                                                                            class="form-check-input project_checkall isscheck_{{ str_replace(' ', '', $module) }}"
                                                                            id="permission{{ $key }}"
                                                                            {{ in_array($key, $role->permissions->pluck('id')->toArray()) ? 'checked' : '' }}>
                                                                        <label for="permission{{ $key }}"
                                                                            class="custom-control-label">Delete
                                                                            Payment</label><br>
                                                                    </div>
                                                                @endif
                                                            @endif

                                                            @if (in_array('income ' . $module, (array) $permissions))
                                                                @if ($key = array_search('income ' . $module, $permissions))
                                                                    <div
                                                                        class="col-md-3 custom-control custom-checkbox">
                                                                        <input type="checkbox" name="permissions[]"
                                                                            value="{{ $key }}"
                                                                            class="form-check-input project_checkall isscheck_{{ str_replace(' ', '', $module) }}"
                                                                            id="permission{{ $key }}"
                                                                            {{ in_array($key, $role->permissions->pluck('id')->toArray()) ? 'checked' : '' }}>
                                                                        <label for="permission{{ $key }}"
                                                                            class="custom-control-label">Income</label><br>
                                                                    </div>
                                                                @endif
                                                            @endif

                                                            @if (in_array('expense ' . $module, (array) $permissions))
                                                                @if ($key = array_search('expense ' . $module, $permissions))
                                                                    <div
                                                                        class="col-md-3 custom-control custom-checkbox">
                                                                        <input type="checkbox" name="permissions[]"
                                                                            value="{{ $key }}"
                                                                            class="form-check-input project_checkall isscheck_{{ str_replace(' ', '', $module) }}"
                                                                            id="permission{{ $key }}"
                                                                            {{ in_array($key, $role->permissions->pluck('id')->toArray()) ? 'checked' : '' }}>
                                                                        <label for="permission{{ $key }}"
                                                                            class="custom-control-label">Expense</label><br>
                                                                    </div>
                                                                @endif
                                                            @endif

                                                            @if (in_array('income vs expense ' . $module, (array) $permissions))
                                                                @if ($key = array_search('income vs expense ' . $module, $permissions))
                                                                    <div
                                                                        class="col-md-3 custom-control custom-checkbox">
                                                                        <input type="checkbox" name="permissions[]"
                                                                            value="{{ $key }}"
                                                                            class="form-check-input project_checkall isscheck_{{ str_replace(' ', '', $module) }}"
                                                                            id="permission{{ $key }}"
                                                                            {{ in_array($key, $role->permissions->pluck('id')->toArray()) ? 'checked' : '' }}>
                                                                        <label for="permission{{ $key }}"
                                                                            class="custom-control-label">Income VS
                                                                            Expense</label><br>
                                                                    </div>
                                                                @endif
                                                            @endif

                                                            @if (in_array('loss & profit ' . $module, (array) $permissions))
                                                                @if ($key = array_search('loss & profit ' . $module, $permissions))
                                                                    <div
                                                                        class="col-md-3 custom-control custom-checkbox">
                                                                        <input type="checkbox" name="permissions[]"
                                                                            value="{{ $key }}"
                                                                            class="form-check-input project_checkall isscheck_{{ str_replace(' ', '', $module) }}"
                                                                            id="permission{{ $key }}"
                                                                            {{ in_array($key, $role->permissions->pluck('id')->toArray()) ? 'checked' : '' }}>
                                                                        <label for="permission{{ $key }}"
                                                                            class="custom-control-label">Loss &
                                                                            Profit</label><br>
                                                                    </div>
                                                                @endif
                                                            @endif

                                                            @if (in_array('tax ' . $module, (array) $permissions))
                                                                @if ($key = array_search('tax ' . $module, $permissions))
                                                                    <div
                                                                        class="col-md-3 custom-control custom-checkbox">
                                                                        <input type="checkbox" name="permissions[]"
                                                                            value="{{ $key }}"
                                                                            class="form-check-input project_checkall isscheck_{{ str_replace(' ', '', $module) }}"
                                                                            id="permission{{ $key }}"
                                                                            {{ in_array($key, $role->permissions->pluck('id')->toArray()) ? 'checked' : '' }}>
                                                                        <label for="permission{{ $key }}"
                                                                            class="custom-control-label">Tax</label><br>
                                                                    </div>
                                                                @endif
                                                            @endif

                                                            @if (in_array('invoice ' . $module, (array) $permissions))
                                                                @if ($key = array_search('invoice ' . $module, $permissions))
                                                                    <div
                                                                        class="col-md-3 custom-control custom-checkbox">
                                                                        <input type="checkbox" name="permissions[]"
                                                                            value="{{ $key }}"
                                                                            class="form-check-input project_checkall isscheck_{{ str_replace(' ', '', $module) }}"
                                                                            id="permission{{ $key }}"
                                                                            {{ in_array($key, $role->permissions->pluck('id')->toArray()) ? 'checked' : '' }}>
                                                                        <label for="permission{{ $key }}"
                                                                            class="custom-control-label">Invoice</label><br>
                                                                    </div>
                                                                @endif
                                                            @endif

                                                            @if (in_array('bill ' . $module, (array) $permissions))
                                                                @if ($key = array_search('bill ' . $module, $permissions))
                                                                    <div
                                                                        class="col-md-3 custom-control custom-checkbox">
                                                                        <input type="checkbox" name="permissions[]"
                                                                            value="{{ $key }}"
                                                                            class="form-check-input project_checkall isscheck_{{ str_replace(' ', '', $module) }}"
                                                                            id="permission{{ $key }}"
                                                                            {{ in_array($key, $role->permissions->pluck('id')->toArray()) ? 'checked' : '' }}>
                                                                        <label for="permission{{ $key }}"
                                                                            class="custom-control-label">Bill</label><br>
                                                                    </div>
                                                                @endif
                                                            @endif

                                                            @if (in_array('duplicate ' . $module, (array) $permissions))
                                                                @if ($key = array_search('duplicate ' . $module, $permissions))
                                                                    <div
                                                                        class="col-md-3 custom-control custom-checkbox">
                                                                        <input type="checkbox" name="permissions[]"
                                                                            value="{{ $key }}"
                                                                            class="form-check-input project_checkall isscheck_{{ str_replace(' ', '', $module) }}"
                                                                            id="permission{{ $key }}"
                                                                            {{ in_array($key, $role->permissions->pluck('id')->toArray()) ? 'checked' : '' }}>
                                                                        <label for="permission{{ $key }}"
                                                                            class="custom-control-label">Duplicate</label><br>
                                                                    </div>
                                                                @endif
                                                            @endif

                                                            @if (in_array('balance sheet ' . $module, (array) $permissions))
                                                                @if ($key = array_search('balance sheet ' . $module, $permissions))
                                                                    <div
                                                                        class="col-md-3 custom-control custom-checkbox">
                                                                        <input type="checkbox" name="permissions[]"
                                                                            value="{{ $key }}"
                                                                            class="form-check-input project_checkall isscheck_{{ str_replace(' ', '', $module) }}"
                                                                            id="permission{{ $key }}"
                                                                            {{ in_array($key, $role->permissions->pluck('id')->toArray()) ? 'checked' : '' }}>
                                                                        <label for="permission{{ $key }}"
                                                                            class="custom-control-label">Balance
                                                                            Sheet</label><br>
                                                                    </div>
                                                                @endif
                                                            @endif

                                                            @if (in_array('ledger ' . $module, (array) $permissions))
                                                                @if ($key = array_search('ledger ' . $module, $permissions))
                                                                    <div
                                                                        class="col-md-3 custom-control custom-checkbox">
                                                                        <input type="checkbox" name="permissions[]"
                                                                            value="{{ $key }}"
                                                                            class="form-check-input project_checkall isscheck_{{ str_replace(' ', '', $module) }}"
                                                                            id="permission{{ $key }}"
                                                                            {{ in_array($key, $role->permissions->pluck('id')->toArray()) ? 'checked' : '' }}>
                                                                        <label for="permission{{ $key }}"
                                                                            class="custom-control-label">Ledger</label><br>
                                                                    </div>
                                                                @endif
                                                            @endif

                                                            @if (in_array('trial balance ' . $module, (array) $permissions))
                                                                @if ($key = array_search('trial balance ' . $module, $permissions))
                                                                    <div
                                                                        class="col-md-3 custom-control custom-checkbox">
                                                                        <input type="checkbox" name="permissions[]"
                                                                            value="{{ $key }}"
                                                                            class="form-check-input project_checkall isscheck_{{ str_replace(' ', '', $module) }}"
                                                                            id="permission{{ $key }}"
                                                                            {{ in_array($key, $role->permissions->pluck('id')->toArray()) ? 'checked' : '' }}>
                                                                        <label for="permission{{ $key }}"
                                                                            class="custom-control-label">Trial
                                                                            Balance</label><br>
                                                                    </div>
                                                                @endif
                                                            @endif

                                                            @if (in_array('statement ' . $module, (array) $permissions))
                                                                @if ($key = array_search('statement ' . $module, $permissions))
                                                                    <div
                                                                        class="col-md-3 custom-control custom-checkbox">
                                                                        <input type="checkbox" name="permissions[]"
                                                                            value="{{ $key }}"
                                                                            class="form-check-input account_checkall isscheck_{{ str_replace(' ', '', $module) }}"
                                                                            id="permission{{ $key }}"
                                                                            {{ in_array($key, $role->permissions->pluck('id')->toArray()) ? 'checked' : '' }}>
                                                                        <label for="permission{{ $key }}"
                                                                            class="custom-control-label">Statement</label><br>
                                                                    </div>
                                                                @endif
                                                            @endif

                                                            @if (in_array('stock ' . $module, (array) $permissions))
                                                                @if ($key = array_search('stock ' . $module, $permissions))
                                                                    <div
                                                                        class="col-md-3 custom-control custom-checkbox">
                                                                        <input type="checkbox" name="permissions[]"
                                                                            value="{{ $key }}"
                                                                            class="form-check-input account_checkall isscheck_{{ str_replace(' ', '', $module) }}"
                                                                            id="permission{{ $key }}"
                                                                            {{ in_array($key, $role->permissions->pluck('id')->toArray()) ? 'checked' : '' }}>
                                                                        <label for="permission{{ $key }}"
                                                                            class="custom-control-label">Stock</label><br>
                                                                    </div>
                                                                @endif
                                                            @endif

                                                            @if (in_array('general ledger ' . $module, (array) $permissions))
                                                                @if ($key = array_search('general ledger ' . $module, $permissions))
                                                                    <div
                                                                        class="col-md-3 custom-control custom-checkbox">
                                                                        <input type="checkbox" name="permissions[]"
                                                                            value="{{ $key }}"
                                                                            class="form-check-input account_checkall isscheck_{{ str_replace(' ', '', $module) }}"
                                                                            id="permission{{ $key }}"
                                                                            {{ in_array($key, $role->permissions->pluck('id')->toArray()) ? 'checked' : '' }}>
                                                                        <label for="permission{{ $key }}"
                                                                            class="custom-control-label">General Ledger</label><br>
                                                                    </div>
                                                                @endif
                                                            @endif

                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="hrmpermission" role="tabpanel"
                        aria-labelledby="pills-contact-tab">
                        @php
                            $modules = [
                                'hrm dashboard',
                                'employee',
                                'employee profile',
                                'department',
                                'designation',
                                'task master',
                                'daily task log',
                                'branch',
                                'document type',
                                'document',
                                'payslip type',
                                'allowance',
                                'commission',
                                'allowance option',
                                'loan option',
                                'deduction option',
                                'loan',
                                'saturation deduction',
                                'other payment',
                                'overtime',
                                'set salary',
                                'pay slip',
                                'company policy',
                                'appraisal',
                                'goal tracking',
                                'goal type',
                                'indicator',
                                'event',
                                'meeting',
                                'training',
                                'trainer',
                                'training type',
                                'award',
                                'award type',
                                'resignation',
                                'travel',
                                'promotion',
                                'complaint',
                                'warning',
                                'termination',
                                'termination type',
                                'job application',
                                'job application note',
                                'job onBoard',
                                'job category',
                                'job',
                                'job stage',
                                'custom question',
                                'interview schedule',
                                'estimation',
                                'holiday',
                                'transfer',
                                'announcement',
                                'leave',
                                'leave type',
                                'attendance',
                            ];
                        @endphp
                        <div class="col-md-12">
                            <div class="form-group">
                                @if (!empty($permissions))
                                    <h6 class="my-3">{{ __('Assign HRM related Permission to Roles') }}</h6>
                                    <table class="table table-striped mb-0" id="">
                                        <thead>
                                            <tr>
                                                <th>
                                                    <input type="checkbox"
                                                        class="form-check-input align-middle custom_align_middle"
                                                        name="hrm_checkall" id="hrm_checkall">
                                                </th>
                                                <th>{{ __('Module') }} </th>
                                                <th>{{ __('Permissions') }} </th>
                                            </tr>
                                        </thead>
                                        <tbody>

                                            @foreach ($modules as $module)
                                                <tr>
                                                    <td><input type="checkbox"
                                                            class="form-check-input align-middle ischeck hrm_checkall"
                                                            data-id="{{ str_replace(' ', '', $module) }}"></td>
                                                    <td><label class="ischeck hrm_checkall"
                                                            data-id="{{ str_replace(' ', '', $module) }}">{{ ucfirst($module) }}</label>
                                                    </td>
                                                    <td>
                                                        <div class="row ">
                                                            @if (in_array('view ' . $module, (array) $permissions))
                                                                @if ($key = array_search('view ' . $module, $permissions))
                                                                    <div
                                                                        class="col-md-3 custom-control custom-checkbox">
                                                                        <input type="checkbox" name="permissions[]"
                                                                            value="{{ $key }}"
                                                                            class="form-check-input hrm_checkall isscheck_{{ str_replace(' ', '', $module) }}"
                                                                            id="permission{{ $key }}"
                                                                            {{ in_array($key, $role->permissions->pluck('id')->toArray()) ? 'checked' : '' }}>
                                                                        <label for="permission{{ $key }}"
                                                                            class="custom-control-label">View</label><br>
                                                                    </div>
                                                                @endif
                                                            @endif

                                                            @if (in_array('add ' . $module, (array) $permissions))
                                                                @if ($key = array_search('add ' . $module, $permissions))
                                                                    <div
                                                                        class="col-md-3 custom-control custom-checkbox">
                                                                        <input type="checkbox" name="permissions[]"
                                                                            value="{{ $key }}"
                                                                            class="form-check-input hrm_checkall isscheck_{{ str_replace(' ', '', $module) }}"
                                                                            id="permission{{ $key }}"
                                                                            {{ in_array($key, $role->permissions->pluck('id')->toArray()) ? 'checked' : '' }}>
                                                                        <label for="permission{{ $key }}"
                                                                            class="custom-control-label">Add</label><br>
                                                                    </div>
                                                                @endif
                                                            @endif

                                                            @if (in_array('move ' . $module, (array) $permissions))
                                                                @if ($key = array_search('move ' . $module, $permissions))
                                                                    <div
                                                                        class="col-md-3 custom-control custom-checkbox">
                                                                        <input type="checkbox" name="permissions[]"
                                                                            value="{{ $key }}"
                                                                            class="form-check-input hrm_checkall isscheck_{{ str_replace(' ', '', $module) }}"
                                                                            id="permission{{ $key }}"
                                                                            {{ in_array($key, $role->permissions->pluck('id')->toArray()) ? 'checked' : '' }}>
                                                                        <label for="permission{{ $key }}"
                                                                            class="custom-control-label">Move</label><br>
                                                                    </div>
                                                                @endif
                                                            @endif

                                                            @if (in_array('manage ' . $module, (array) $permissions))
                                                                @if ($key = array_search('manage ' . $module, $permissions))
                                                                    <div
                                                                        class="col-md-3 custom-control custom-checkbox">
                                                                        <input type="checkbox" name="permissions[]"
                                                                            value="{{ $key }}"
                                                                            class="form-check-input hrm_checkall isscheck_{{ str_replace(' ', '', $module) }}"
                                                                            id="permission{{ $key }}"
                                                                            {{ in_array($key, $role->permissions->pluck('id')->toArray()) ? 'checked' : '' }}>
                                                                        <label for="permission{{ $key }}"
                                                                            class="custom-control-label">Manage</label><br>
                                                                    </div>
                                                                @endif
                                                            @endif

                                                            @if (in_array('create ' . $module, (array) $permissions))
                                                                @if ($key = array_search('create ' . $module, $permissions))
                                                                    <div
                                                                        class="col-md-3 custom-control custom-checkbox">
                                                                        <input type="checkbox" name="permissions[]"
                                                                            value="{{ $key }}"
                                                                            class="form-check-input hrm_checkall isscheck_{{ str_replace(' ', '', $module) }}"
                                                                            id="permission{{ $key }}"
                                                                            {{ in_array($key, $role->permissions->pluck('id')->toArray()) ? 'checked' : '' }}>
                                                                        <label for="permission{{ $key }}"
                                                                            class="custom-control-label">Create</label><br>
                                                                    </div>
                                                                @endif
                                                            @endif

                                                            @if (in_array('edit ' . $module, (array) $permissions))
                                                                @if ($key = array_search('edit ' . $module, $permissions))
                                                                    <div
                                                                        class="col-md-3 custom-control custom-checkbox">
                                                                        <input type="checkbox" name="permissions[]"
                                                                            value="{{ $key }}"
                                                                            class="form-check-input hrm_checkall isscheck_{{ str_replace(' ', '', $module) }}"
                                                                            id="permission{{ $key }}"
                                                                            {{ in_array($key, $role->permissions->pluck('id')->toArray()) ? 'checked' : '' }}>
                                                                        <label for="permission{{ $key }}"
                                                                            class="custom-control-label">Edit</label><br>
                                                                    </div>
                                                                @endif
                                                            @endif

                                                            @if (in_array('delete ' . $module, (array) $permissions))
                                                                @if ($key = array_search('delete ' . $module, $permissions))
                                                                    <div
                                                                        class="col-md-3 custom-control custom-checkbox">
                                                                        <input type="checkbox" name="permissions[]"
                                                                            value="{{ $key }}"
                                                                            class="form-check-input hrm_checkall isscheck_{{ str_replace(' ', '', $module) }}"
                                                                            id="permission{{ $key }}"
                                                                            {{ in_array($key, $role->permissions->pluck('id')->toArray()) ? 'checked' : '' }}>
                                                                        <label for="permission{{ $key }}"
                                                                            class="custom-control-label">Delete</label><br>
                                                                    </div>
                                                                @endif
                                                            @endif

                                                            @if (in_array('show ' . $module, (array) $permissions))
                                                                @if ($key = array_search('show ' . $module, $permissions))
                                                                    <div
                                                                        class="col-md-3 custom-control custom-checkbox">
                                                                        <input type="checkbox" name="permissions[]"
                                                                            value="{{ $key }}"
                                                                            class="form-check-input hrm_checkall isscheck_{{ str_replace(' ', '', $module) }}"
                                                                            id="permission{{ $key }}"
                                                                            {{ in_array($key, $role->permissions->pluck('id')->toArray()) ? 'checked' : '' }}>
                                                                        <label for="permission{{ $key }}"
                                                                            class="custom-control-label">Show</label><br>
                                                                    </div>
                                                                @endif
                                                            @endif



                                                            @if (in_array('send ' . $module, (array) $permissions))
                                                                @if ($key = array_search('send ' . $module, $permissions))
                                                                    <div
                                                                        class="col-md-3 custom-control custom-checkbox">
                                                                        <input type="checkbox" name="permissions[]"
                                                                            value="{{ $key }}"
                                                                            class="form-check-input hrm_checkall isscheck_{{ str_replace(' ', '', $module) }}"
                                                                            id="permission{{ $key }}"
                                                                            {{ in_array($key, $role->permissions->pluck('id')->toArray()) ? 'checked' : '' }}>
                                                                        <label for="permission{{ $key }}"
                                                                            class="custom-control-label">Send</label><br>
                                                                    </div>
                                                                @endif
                                                            @endif

                                                            @if (in_array('create payment ' . $module, (array) $permissions))
                                                                @if ($key = array_search('create payment ' . $module, $permissions))
                                                                    <div
                                                                        class="col-md-3 custom-control custom-checkbox">
                                                                        <input type="checkbox" name="permissions[]"
                                                                            value="{{ $key }}"
                                                                            class="form-check-input hrm_checkall isscheck_{{ str_replace(' ', '', $module) }}"
                                                                            id="permission{{ $key }}"
                                                                            {{ in_array($key, $role->permissions->pluck('id')->toArray()) ? 'checked' : '' }}>
                                                                        <label for="permission{{ $key }}"
                                                                            class="custom-control-label">Create
                                                                            Payment</label><br>
                                                                    </div>
                                                                @endif
                                                            @endif

                                                            @if (in_array('delete payment ' . $module, (array) $permissions))
                                                                @if ($key = array_search('delete payment ' . $module, $permissions))
                                                                    <div
                                                                        class="col-md-3 custom-control custom-checkbox">
                                                                        <input type="checkbox" name="permissions[]"
                                                                            value="{{ $key }}"
                                                                            class="form-check-input hrm_checkall isscheck_{{ str_replace(' ', '', $module) }}"
                                                                            id="permission{{ $key }}"
                                                                            {{ in_array($key, $role->permissions->pluck('id')->toArray()) ? 'checked' : '' }}>
                                                                        <label for="permission{{ $key }}"
                                                                            class="custom-control-label">Delete
                                                                            Payment</label><br>
                                                                    </div>
                                                                @endif
                                                            @endif

                                                            @if (in_array('income ' . $module, (array) $permissions))
                                                                @if ($key = array_search('income ' . $module, $permissions))
                                                                    <div
                                                                        class="col-md-3 custom-control custom-checkbox">
                                                                        <input type="checkbox" name="permissions[]"
                                                                            value="{{ $key }}"
                                                                            class="form-check-input hrm_checkall isscheck_{{ str_replace(' ', '', $module) }}"
                                                                            id="permission{{ $key }}"
                                                                            {{ in_array($key, $role->permissions->pluck('id')->toArray()) ? 'checked' : '' }}>
                                                                        <label for="permission{{ $key }}"
                                                                            class="custom-control-label">Income</label><br>
                                                                    </div>
                                                                @endif
                                                            @endif

                                                            @if (in_array('expense ' . $module, (array) $permissions))
                                                                @if ($key = array_search('expense ' . $module, $permissions))
                                                                    <div
                                                                        class="col-md-3 custom-control custom-checkbox">
                                                                        <input type="checkbox" name="permissions[]"
                                                                            value="{{ $key }}"
                                                                            class="form-check-input hrm_checkall isscheck_{{ str_replace(' ', '', $module) }}"
                                                                            id="permission{{ $key }}"
                                                                            {{ in_array($key, $role->permissions->pluck('id')->toArray()) ? 'checked' : '' }}>
                                                                        <label for="permission{{ $key }}"
                                                                            class="custom-control-label">Expense</label><br>
                                                                    </div>
                                                                @endif
                                                            @endif

                                                            @if (in_array('income vs expense ' . $module, (array) $permissions))
                                                                @if ($key = array_search('income vs expense ' . $module, $permissions))
                                                                    <div
                                                                        class="col-md-3 custom-control custom-checkbox">
                                                                        <input type="checkbox" name="permissions[]"
                                                                            value="{{ $key }}"
                                                                            class="form-check-input hrm_checkall isscheck_{{ str_replace(' ', '', $module) }}"
                                                                            id="permission{{ $key }}"
                                                                            {{ in_array($key, $role->permissions->pluck('id')->toArray()) ? 'checked' : '' }}>
                                                                        <label for="permission{{ $key }}"
                                                                            class="custom-control-label">Income VS
                                                                            Expense</label><br>
                                                                    </div>
                                                                @endif
                                                            @endif

                                                            @if (in_array('loss & profit ' . $module, (array) $permissions))
                                                                @if ($key = array_search('loss & profit ' . $module, $permissions))
                                                                    <div
                                                                        class="col-md-3 custom-control custom-checkbox">
                                                                        <input type="checkbox" name="permissions[]"
                                                                            value="{{ $key }}"
                                                                            class="form-check-input hrm_checkall isscheck_{{ str_replace(' ', '', $module) }}"
                                                                            id="permission{{ $key }}"
                                                                            {{ in_array($key, $role->permissions->pluck('id')->toArray()) ? 'checked' : '' }}>
                                                                        <label for="permission{{ $key }}"
                                                                            class="custom-control-label">Loss &
                                                                            Profit</label><br>
                                                                    </div>
                                                                @endif
                                                            @endif

                                                            @if (in_array('tax ' . $module, (array) $permissions))
                                                                @if ($key = array_search('tax ' . $module, $permissions))
                                                                    <div
                                                                        class="col-md-3 custom-control custom-checkbox">
                                                                        <input type="checkbox" name="permissions[]"
                                                                            value="{{ $key }}"
                                                                            class="form-check-input hrm_checkall isscheck_{{ str_replace(' ', '', $module) }}"
                                                                            id="permission{{ $key }}"
                                                                            {{ in_array($key, $role->permissions->pluck('id')->toArray()) ? 'checked' : '' }}>
                                                                        <label for="permission{{ $key }}"
                                                                            class="custom-control-label">Tax</label><br>
                                                                    </div>
                                                                @endif
                                                            @endif

                                                            @if (in_array('invoice ' . $module, (array) $permissions))
                                                                @if ($key = array_search('invoice ' . $module, $permissions))
                                                                    <div
                                                                        class="col-md-3 custom-control custom-checkbox">
                                                                        <input type="checkbox" name="permissions[]"
                                                                            value="{{ $key }}"
                                                                            class="form-check-input hrm_checkall isscheck_{{ str_replace(' ', '', $module) }}"
                                                                            id="permission{{ $key }}"
                                                                            {{ in_array($key, $role->permissions->pluck('id')->toArray()) ? 'checked' : '' }}>
                                                                        <label for="permission{{ $key }}"
                                                                            class="custom-control-label">Invoice</label><br>
                                                                    </div>
                                                                @endif
                                                            @endif

                                                            @if (in_array('bill ' . $module, (array) $permissions))
                                                                @if ($key = array_search('bill ' . $module, $permissions))
                                                                    <div
                                                                        class="col-md-3 custom-control custom-checkbox">
                                                                        <input type="checkbox" name="permissions[]"
                                                                            value="{{ $key }}"
                                                                            class="form-check-input hrm_checkall isscheck_{{ str_replace(' ', '', $module) }}"
                                                                            id="permission{{ $key }}"
                                                                            {{ in_array($key, $role->permissions->pluck('id')->toArray()) ? 'checked' : '' }}>
                                                                        <label for="permission{{ $key }}"
                                                                            class="custom-control-label">Bill</label><br>
                                                                    </div>
                                                                @endif
                                                            @endif

                                                            @if (in_array('duplicate ' . $module, (array) $permissions))
                                                                @if ($key = array_search('duplicate ' . $module, $permissions))
                                                                    <div
                                                                        class="col-md-3 custom-control custom-checkbox">
                                                                        <input type="checkbox" name="permissions[]"
                                                                            value="{{ $key }}"
                                                                            class="form-check-input hrm_checkall isscheck_{{ str_replace(' ', '', $module) }}"
                                                                            id="permission{{ $key }}"
                                                                            {{ in_array($key, $role->permissions->pluck('id')->toArray()) ? 'checked' : '' }}>
                                                                        <label for="permission{{ $key }}"
                                                                            class="custom-control-label">Duplicate</label><br>
                                                                    </div>
                                                                @endif
                                                            @endif

                                                            @if (in_array('balance sheet ' . $module, (array) $permissions))
                                                                @if ($key = array_search('balance sheet ' . $module, $permissions))
                                                                    <div
                                                                        class="col-md-3 custom-control custom-checkbox">
                                                                        <input type="checkbox" name="permissions[]"
                                                                            value="{{ $key }}"
                                                                            class="form-check-input hrm_checkall isscheck_{{ str_replace(' ', '', $module) }}"
                                                                            id="permission{{ $key }}"
                                                                            {{ in_array($key, $role->permissions->pluck('id')->toArray()) ? 'checked' : '' }}>
                                                                        <label for="permission{{ $key }}"
                                                                            class="custom-control-label">Balance
                                                                            Sheet</label><br>
                                                                    </div>
                                                                @endif
                                                            @endif

                                                            @if (in_array('ledger ' . $module, (array) $permissions))
                                                                @if ($key = array_search('ledger ' . $module, $permissions))
                                                                    <div
                                                                        class="col-md-3 custom-control custom-checkbox">
                                                                        <input type="checkbox" name="permissions[]"
                                                                            value="{{ $key }}"
                                                                            class="form-check-input hrm_checkall isscheck_{{ str_replace(' ', '', $module) }}"
                                                                            id="permission{{ $key }}"
                                                                            {{ in_array($key, $role->permissions->pluck('id')->toArray()) ? 'checked' : '' }}>
                                                                        <label for="permission{{ $key }}"
                                                                            class="custom-control-label">Ledger</label><br>
                                                                    </div>
                                                                @endif
                                                            @endif

                                                            @if (in_array('trial balance ' . $module, (array) $permissions))
                                                                @if ($key = array_search('trial balance ' . $module, $permissions))
                                                                    <div
                                                                        class="col-md-3 custom-control custom-checkbox">
                                                                        <input type="checkbox" name="permissions[]"
                                                                            value="{{ $key }}"
                                                                            class="form-check-input hrm_checkall isscheck_{{ str_replace(' ', '', $module) }}"
                                                                            id="permission{{ $key }}"
                                                                            {{ in_array($key, $role->permissions->pluck('id')->toArray()) ? 'checked' : '' }}>
                                                                        <label for="permission{{ $key }}"
                                                                            class="custom-control-label">Trial
                                                                            Balance</label><br>
                                                                    </div>
                                                                @endif
                                                            @endif

                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="account" role="tabpanel" aria-labelledby="pills-contact-tab">
                        @php
                            $modules = [
                                'account dashboard',
                                'proposal',
                                'invoice',
                                'bill',
                                'revenue',
                                'payment',
                                'proposal product',
                                'invoice product',
                                'bill product',
                                'goal',
                                'credit note',
                                'debit note',
                                'bank account',
                                'bank transfer',
                                'transaction',
                                'customer',
                                'vender',
                                'constant custom field',
                                'assets',
                                'chart of account',
                                'journal entry',
                                'report',
                                'pro',
                                'asn',
                                'grn',
                                'sale order',
                                'advance sale order',
                                'picking',
                                'packing',
                            ];
                        @endphp
                        <div class="col-md-12">
                            <div class="form-group">
                                @if (!empty($permissions))
                                    <h6 class="my-3">{{ __('Assign Account related Permission to Roles') }}</h6>
                                    <table class="table table-striped mb-0" id="">
                                        <thead>
                                            <tr>
                                                <th>
                                                    <input type="checkbox"
                                                        class="form-check-input align-middle custom_align_middle"
                                                        name="account_checkall" id="account_checkall">
                                                </th>
                                                <th>{{ __('Module') }} </th>
                                                <th>{{ __('Permissions') }} </th>
                                            </tr>
                                        </thead>
                                        <tbody>

                                            @foreach ($modules as $module)
                                                <tr>
                                                    <td><input type="checkbox"
                                                            class="form-check-input align-middle ischeck account_checkall"
                                                            data-id="{{ str_replace(' ', '', $module) }}"></td>
                                                    <td><label class="ischeck account_checkall"
                                                            data-id="{{ str_replace(' ', '', $module) }}">{{ ucfirst($module) }}</label>
                                                    </td>
                                                    <td>
                                                        <div class="row ">
                                                            @if (in_array('view ' . $module, (array) $permissions))
                                                                @if ($key = array_search('view ' . $module, $permissions))
                                                                    <div
                                                                        class="col-md-3 custom-control custom-checkbox">
                                                                        <input type="checkbox" name="permissions[]"
                                                                            value="{{ $key }}"
                                                                            class="form-check-input account_checkall isscheck_{{ str_replace(' ', '', $module) }}"
                                                                            id="permission{{ $key }}"
                                                                            {{ in_array($key, $role->permissions->pluck('id')->toArray()) ? 'checked' : '' }}>
                                                                        <label for="permission{{ $key }}"
                                                                            class="custom-control-label">View</label><br>
                                                                    </div>
                                                                @endif
                                                            @endif

                                                            @if (in_array('add ' . $module, (array) $permissions))
                                                                @if ($key = array_search('add ' . $module, $permissions))
                                                                    <div
                                                                        class="col-md-3 custom-control custom-checkbox">
                                                                        <input type="checkbox" name="permissions[]"
                                                                            value="{{ $key }}"
                                                                            class="form-check-input account_checkall isscheck_{{ str_replace(' ', '', $module) }}"
                                                                            id="permission{{ $key }}"
                                                                            {{ in_array($key, $role->permissions->pluck('id')->toArray()) ? 'checked' : '' }}>
                                                                        <label for="permission{{ $key }}"
                                                                            class="custom-control-label">Add</label><br>
                                                                    </div>
                                                                @endif
                                                            @endif

                                                            @if (in_array('move ' . $module, (array) $permissions))
                                                                @if ($key = array_search('move ' . $module, $permissions))
                                                                    <div
                                                                        class="col-md-3 custom-control custom-checkbox">
                                                                        <input type="checkbox" name="permissions[]"
                                                                            value="{{ $key }}"
                                                                            class="form-check-input account_checkall isscheck_{{ str_replace(' ', '', $module) }}"
                                                                            id="permission{{ $key }}"
                                                                            {{ in_array($key, $role->permissions->pluck('id')->toArray()) ? 'checked' : '' }}>
                                                                        <label for="permission{{ $key }}"
                                                                            class="custom-control-label">Move</label><br>
                                                                    </div>
                                                                @endif
                                                            @endif

                                                            @if (in_array('manage ' . $module, (array) $permissions))
                                                                @if ($key = array_search('manage ' . $module, $permissions))
                                                                    <div
                                                                        class="col-md-3 custom-control custom-checkbox">
                                                                        <input type="checkbox" name="permissions[]"
                                                                            value="{{ $key }}"
                                                                            class="form-check-input account_checkall isscheck_{{ str_replace(' ', '', $module) }}"
                                                                            id="permission{{ $key }}"
                                                                            {{ in_array($key, $role->permissions->pluck('id')->toArray()) ? 'checked' : '' }}>
                                                                        <label for="permission{{ $key }}"
                                                                            class="custom-control-label">Manage</label><br>
                                                                    </div>
                                                                @endif
                                                            @endif

                                                            @if (in_array('create ' . $module, (array) $permissions))
                                                                @if ($key = array_search('create ' . $module, $permissions))
                                                                    <div
                                                                        class="col-md-3 custom-control custom-checkbox">
                                                                        <input type="checkbox" name="permissions[]"
                                                                            value="{{ $key }}"
                                                                            class="form-check-input account_checkall isscheck_{{ str_replace(' ', '', $module) }}"
                                                                            id="permission{{ $key }}"
                                                                            {{ in_array($key, $role->permissions->pluck('id')->toArray()) ? 'checked' : '' }}>
                                                                        <label for="permission{{ $key }}"
                                                                            class="custom-control-label">Create</label><br>
                                                                    </div>
                                                                @endif
                                                            @endif

                                                            @if (in_array('edit ' . $module, (array) $permissions))
                                                                @if ($key = array_search('edit ' . $module, $permissions))
                                                                    <div
                                                                        class="col-md-3 custom-control custom-checkbox">
                                                                        <input type="checkbox" name="permissions[]"
                                                                            value="{{ $key }}"
                                                                            class="form-check-input account_checkall isscheck_{{ str_replace(' ', '', $module) }}"
                                                                            id="permission{{ $key }}"
                                                                            {{ in_array($key, $role->permissions->pluck('id')->toArray()) ? 'checked' : '' }}>
                                                                        <label for="permission{{ $key }}"
                                                                            class="custom-control-label">Edit</label><br>
                                                                    </div>
                                                                @endif
                                                            @endif

                                                            @if (in_array('delete ' . $module, (array) $permissions))
                                                                @if ($key = array_search('delete ' . $module, $permissions))
                                                                    <div
                                                                        class="col-md-3 custom-control custom-checkbox">
                                                                        <input type="checkbox" name="permissions[]"
                                                                            value="{{ $key }}"
                                                                            class="form-check-input account_checkall isscheck_{{ str_replace(' ', '', $module) }}"
                                                                            id="permission{{ $key }}"
                                                                            {{ in_array($key, $role->permissions->pluck('id')->toArray()) ? 'checked' : '' }}>
                                                                        <label for="permission{{ $key }}"
                                                                            class="custom-control-label">Delete</label><br>
                                                                    </div>
                                                                @endif
                                                            @endif

                                                            @if (in_array('show ' . $module, (array) $permissions))
                                                                @if ($key = array_search('show ' . $module, $permissions))
                                                                    <div
                                                                        class="col-md-3 custom-control custom-checkbox">
                                                                        <input type="checkbox" name="permissions[]"
                                                                            value="{{ $key }}"
                                                                            class="form-check-input account_checkall isscheck_{{ str_replace(' ', '', $module) }}"
                                                                            id="permission{{ $key }}"
                                                                            {{ in_array($key, $role->permissions->pluck('id')->toArray()) ? 'checked' : '' }}>
                                                                        <label for="permission{{ $key }}"
                                                                            class="custom-control-label">Show</label><br>
                                                                    </div>
                                                                @endif
                                                            @endif

                                                            @if (in_array('send ' . $module, (array) $permissions))
                                                                @if ($key = array_search('send ' . $module, $permissions))
                                                                    <div
                                                                        class="col-md-3 custom-control custom-checkbox">
                                                                        <input type="checkbox" name="permissions[]"
                                                                            value="{{ $key }}"
                                                                            class="form-check-input account_checkall isscheck_{{ str_replace(' ', '', $module) }}"
                                                                            id="permission{{ $key }}"
                                                                            {{ in_array($key, $role->permissions->pluck('id')->toArray()) ? 'checked' : '' }}>
                                                                        <label for="permission{{ $key }}"
                                                                            class="custom-control-label">Send</label><br>
                                                                    </div>
                                                                @endif
                                                            @endif

                                                            @if (in_array('create payment ' . $module, (array) $permissions))
                                                                @if ($key = array_search('create payment ' . $module, $permissions))
                                                                    <div
                                                                        class="col-md-3 custom-control custom-checkbox">
                                                                        <input type="checkbox" name="permissions[]"
                                                                            value="{{ $key }}"
                                                                            class="form-check-input account_checkall isscheck_{{ str_replace(' ', '', $module) }}"
                                                                            id="permission{{ $key }}"
                                                                            {{ in_array($key, $role->permissions->pluck('id')->toArray()) ? 'checked' : '' }}>
                                                                        <label for="permission{{ $key }}"
                                                                            class="custom-control-label">Create
                                                                            Payment</label><br>
                                                                    </div>
                                                                @endif
                                                            @endif

                                                            @if (in_array('delete payment ' . $module, (array) $permissions))
                                                                @if ($key = array_search('delete payment ' . $module, $permissions))
                                                                    <div
                                                                        class="col-md-3 custom-control custom-checkbox">
                                                                        <input type="checkbox" name="permissions[]"
                                                                            value="{{ $key }}"
                                                                            class="form-check-input account_checkall isscheck_{{ str_replace(' ', '', $module) }}"
                                                                            id="permission{{ $key }}"
                                                                            {{ in_array($key, $role->permissions->pluck('id')->toArray()) ? 'checked' : '' }}>
                                                                        <label for="permission{{ $key }}"
                                                                            class="custom-control-label">Delete
                                                                            Payment</label><br>
                                                                    </div>
                                                                @endif
                                                            @endif

                                                            @if (in_array('income ' . $module, (array) $permissions))
                                                                @if ($key = array_search('income ' . $module, $permissions))
                                                                    <div
                                                                        class="col-md-3 custom-control custom-checkbox">
                                                                        <input type="checkbox" name="permissions[]"
                                                                            value="{{ $key }}"
                                                                            class="form-check-input account_checkall isscheck_{{ str_replace(' ', '', $module) }}"
                                                                            id="permission{{ $key }}"
                                                                            {{ in_array($key, $role->permissions->pluck('id')->toArray()) ? 'checked' : '' }}>
                                                                        <label for="permission{{ $key }}"
                                                                            class="custom-control-label">Income</label><br>
                                                                    </div>
                                                                @endif
                                                            @endif

                                                            @if (in_array('expense ' . $module, (array) $permissions))
                                                                @if ($key = array_search('expense ' . $module, $permissions))
                                                                    <div
                                                                        class="col-md-3 custom-control custom-checkbox">
                                                                        <input type="checkbox" name="permissions[]"
                                                                            value="{{ $key }}"
                                                                            class="form-check-input account_checkall isscheck_{{ str_replace(' ', '', $module) }}"
                                                                            id="permission{{ $key }}"
                                                                            {{ in_array($key, $role->permissions->pluck('id')->toArray()) ? 'checked' : '' }}>
                                                                        <label for="permission{{ $key }}"
                                                                            class="custom-control-label">Expense</label><br>
                                                                    </div>
                                                                @endif
                                                            @endif

                                                            @if (in_array('income vs expense ' . $module, (array) $permissions))
                                                                @if ($key = array_search('income vs expense ' . $module, $permissions))
                                                                    <div
                                                                        class="col-md-3 custom-control custom-checkbox">
                                                                        <input type="checkbox" name="permissions[]"
                                                                            value="{{ $key }}"
                                                                            class="form-check-input account_checkall isscheck_{{ str_replace(' ', '', $module) }}"
                                                                            id="permission{{ $key }}"
                                                                            {{ in_array($key, $role->permissions->pluck('id')->toArray()) ? 'checked' : '' }}>
                                                                        <label for="permission{{ $key }}"
                                                                            class="custom-control-label">Income VS
                                                                            Expense</label><br>
                                                                    </div>
                                                                @endif
                                                            @endif

                                                            @if (in_array('loss & profit ' . $module, (array) $permissions))
                                                                @if ($key = array_search('loss & profit ' . $module, $permissions))
                                                                    <div
                                                                        class="col-md-3 custom-control custom-checkbox">
                                                                        <input type="checkbox" name="permissions[]"
                                                                            value="{{ $key }}"
                                                                            class="form-check-input account_checkall isscheck_{{ str_replace(' ', '', $module) }}"
                                                                            id="permission{{ $key }}"
                                                                            {{ in_array($key, $role->permissions->pluck('id')->toArray()) ? 'checked' : '' }}>
                                                                        <label for="permission{{ $key }}"
                                                                            class="custom-control-label">Loss &
                                                                            Profit</label><br>
                                                                    </div>
                                                                @endif
                                                            @endif

                                                            @if (in_array('tax ' . $module, (array) $permissions))
                                                                @if ($key = array_search('tax ' . $module, $permissions))
                                                                    <div
                                                                        class="col-md-3 custom-control custom-checkbox">
                                                                        <input type="checkbox" name="permissions[]"
                                                                            value="{{ $key }}"
                                                                            class="form-check-input account_checkall isscheck_{{ str_replace(' ', '', $module) }}"
                                                                            id="permission{{ $key }}"
                                                                            {{ in_array($key, $role->permissions->pluck('id')->toArray()) ? 'checked' : '' }}>
                                                                        <label for="permission{{ $key }}"
                                                                            class="custom-control-label">Tax</label><br>
                                                                    </div>
                                                                @endif
                                                            @endif

                                                            @if (in_array('invoice ' . $module, (array) $permissions))
                                                                @if ($key = array_search('invoice ' . $module, $permissions))
                                                                    <div
                                                                        class="col-md-3 custom-control custom-checkbox">
                                                                        <input type="checkbox" name="permissions[]"
                                                                            value="{{ $key }}"
                                                                            class="form-check-input account_checkall isscheck_{{ str_replace(' ', '', $module) }}"
                                                                            id="permission{{ $key }}"
                                                                            {{ in_array($key, $role->permissions->pluck('id')->toArray()) ? 'checked' : '' }}>
                                                                        <label for="permission{{ $key }}"
                                                                            class="custom-control-label">Invoice</label><br>
                                                                    </div>
                                                                @endif
                                                            @endif

                                                            @if (in_array('bill ' . $module, (array) $permissions))
                                                                @if ($key = array_search('bill ' . $module, $permissions))
                                                                    <div
                                                                        class="col-md-3 custom-control custom-checkbox">
                                                                        <input type="checkbox" name="permissions[]"
                                                                            value="{{ $key }}"
                                                                            class="form-check-input account_checkall isscheck_{{ str_replace(' ', '', $module) }}"
                                                                            id="permission{{ $key }}"
                                                                            {{ in_array($key, $role->permissions->pluck('id')->toArray()) ? 'checked' : '' }}>
                                                                        <label for="permission{{ $key }}"
                                                                            class="custom-control-label">Bill</label><br>
                                                                    </div>
                                                                @endif
                                                            @endif

                                                            @if (in_array('duplicate ' . $module, (array) $permissions))
                                                                @if ($key = array_search('duplicate ' . $module, $permissions))
                                                                    <div
                                                                        class="col-md-3 custom-control custom-checkbox">
                                                                        <input type="checkbox" name="permissions[]"
                                                                            value="{{ $key }}"
                                                                            class="form-check-input account_checkall isscheck_{{ str_replace(' ', '', $module) }}"
                                                                            id="permission{{ $key }}"
                                                                            {{ in_array($key, $role->permissions->pluck('id')->toArray()) ? 'checked' : '' }}>
                                                                        <label for="permission{{ $key }}"
                                                                            class="custom-control-label">Duplicate</label><br>
                                                                    </div>
                                                                @endif
                                                            @endif

                                                            @if (in_array('balance sheet ' . $module, (array) $permissions))
                                                                @if ($key = array_search('balance sheet ' . $module, $permissions))
                                                                    <div
                                                                        class="col-md-3 custom-control custom-checkbox">
                                                                        <input type="checkbox" name="permissions[]"
                                                                            value="{{ $key }}"
                                                                            class="form-check-input account_checkall isscheck_{{ str_replace(' ', '', $module) }}"
                                                                            id="permission{{ $key }}"
                                                                            {{ in_array($key, $role->permissions->pluck('id')->toArray()) ? 'checked' : '' }}>
                                                                        <label for="permission{{ $key }}"
                                                                            class="custom-control-label">Balance
                                                                            Sheet</label><br>
                                                                    </div>
                                                                @endif
                                                            @endif

                                                            @if (in_array('ledger ' . $module, (array) $permissions))
                                                                @if ($key = array_search('ledger ' . $module, $permissions))
                                                                    <div
                                                                        class="col-md-3 custom-control custom-checkbox">
                                                                        <input type="checkbox" name="permissions[]"
                                                                            value="{{ $key }}"
                                                                            class="form-check-input account_checkall isscheck_{{ str_replace(' ', '', $module) }}"
                                                                            id="permission{{ $key }}"
                                                                            {{ in_array($key, $role->permissions->pluck('id')->toArray()) ? 'checked' : '' }}>
                                                                        <label for="permission{{ $key }}"
                                                                            class="custom-control-label">Ledger</label><br>
                                                                    </div>
                                                                @endif
                                                            @endif

                                                            @if (in_array('trial balance ' . $module, (array) $permissions))
                                                                @if ($key = array_search('trial balance ' . $module, $permissions))
                                                                    <div
                                                                        class="col-md-3 custom-control custom-checkbox">
                                                                        <input type="checkbox" name="permissions[]"
                                                                            value="{{ $key }}"
                                                                            class="form-check-input account_checkall isscheck_{{ str_replace(' ', '', $module) }}"
                                                                            id="permission{{ $key }}"
                                                                            {{ in_array($key, $role->permissions->pluck('id')->toArray()) ? 'checked' : '' }}>
                                                                        <label for="permission{{ $key }}"
                                                                            class="custom-control-label">Trial
                                                                            Balance</label><br>
                                                                    </div>
                                                                @endif
                                                            @endif

                                                            @if (in_array('statement ' . $module, (array) $permissions))
                                                                @if ($key = array_search('statement ' . $module, $permissions))
                                                                    <div class="col-md-3 custom-control custom-checkbox">
                                                                        <input type="checkbox" name="permissions[]"
                                                                            value="{{ $key }}"
                                                                            class="form-check-input account_checkall isscheck_{{ str_replace(' ', '', $module) }}"
                                                                            id="permission{{ $key }}"
                                                                            {{ in_array($key, $role->permissions->pluck('id')->toArray()) ? 'checked' : '' }}>
                                                                        <label for="permission{{ $key }}"
                                                                            class="custom-control-label">Statement</label><br>
                                                                    </div>
                                                                @endif
                                                            @endif

                                                            @if (in_array('stock ' . $module, (array) $permissions))
                                                                @if ($key = array_search('stock ' . $module, $permissions))
                                                                    <div class="col-md-3 custom-control custom-checkbox">
                                                                        <input type="checkbox" name="permissions[]"
                                                                            value="{{ $key }}"
                                                                            class="form-check-input account_checkall isscheck_{{ str_replace(' ', '', $module) }}"
                                                                            id="permission{{ $key }}"
                                                                            {{ in_array($key, $role->permissions->pluck('id')->toArray()) ? 'checked' : '' }}>
                                                                        <label for="permission{{ $key }}"
                                                                            class="custom-control-label">Stock</label><br>
                                                                    </div>
                                                                @endif
                                                            @endif

                                                            @if (in_array('general ledger ' . $module, (array) $permissions))
                                                                @if ($key = array_search('general ledger ' . $module, $permissions))
                                                                    <div class="col-md-3 custom-control custom-checkbox">
                                                                        <input type="checkbox" name="permissions[]"
                                                                            value="{{ $key }}"
                                                                            class="form-check-input account_checkall isscheck_{{ str_replace(' ', '', $module) }}"
                                                                            id="permission{{ $key }}"
                                                                            {{ in_array($key, $role->permissions->pluck('id')->toArray()) ? 'checked' : '' }}>
                                                                        <label for="permission{{ $key }}"
                                                                            class="custom-control-label">General Ledger</label><br>
                                                                    </div>
                                                                @endif
                                                            @endif

                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                            
                                            {{-- Explicit "Create Barcode" permission as separate entry --}}
                                            @if (in_array('create barcode', (array) $permissions))
                                                @if ($key = array_search('create barcode', $permissions))
                                                    <tr>
                                                        <td><input type="checkbox"
                                                                class="form-check-input align-middle ischeck pos_checkall"
                                                                data-id="createbarcode"></td>
                                                        <td><label class="ischeck pos_checkall"
                                                                data-id="createbarcode">{{ __('Create Barcode') }}</label>
                                                        </td>
                                                        <td>
                                                            <div class="row">
                                                                <div class="col-md-3 custom-control custom-checkbox">
                                                                    <input type="checkbox" name="permissions[]"
                                                                        value="{{ $key }}"
                                                                        class="form-check-input pos_checkall isscheck_createbarcode"
                                                                        id="permission{{ $key }}"
                                                                        {{ in_array($key, $role->permissions->pluck('id')->toArray()) ? 'checked' : '' }}>
                                                                    <label for="permission{{ $key }}"
                                                                        class="custom-control-label">{{ __('Create Barcode') }}</label><br>
                                                                </div>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                @endif
                                            @endif
                                        </tbody>
                                    </table>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="pos" role="tabpanel"
                        aria-labelledby="pills-contact-tab">
                        @php
                            $modules = [
                                'warehouse',
                                'purchase',
                                'pos',
                                'barcode',
                                'voucher',
                                'pos refund',
                                'price list',
                                'combo',
                                'stock',
                                'stock movement',
                                'transfer',
                                'payment method',
                            ];
                        @endphp
                        <div class="col-md-12">
                            <div class="form-group">
                                @if (!empty($permissions))
                                    <h6 class="my-3">{{ __('Assign POS related Permission to Roles') }}</h6>
                                    <table class="table table-striped mb-0" id="">
                                        <thead>
                                            <tr>
                                                <th>
                                                    <input type="checkbox"
                                                        class="form-check-input align-middle custom_align_middle"
                                                        name="pos_checkall" id="pos_checkall">
                                                </th>
                                                <th>{{ __('Module') }} </th>
                                                <th>{{ __('Permissions') }} </th>
                                            </tr>
                                        </thead>
                                        <tbody>

                                            @foreach ($modules as $module)
                                                <tr>

                                                    <td><input type="checkbox"
                                                            class="form-check-input align-middle ischeck pos_checkall"
                                                            data-id="{{ str_replace(' ', '', $module) }}"></td>
                                                    <td><label class="ischeck pos_checkall"
                                                            data-id="{{ str_replace(' ', '', $module) }}">{{ ucfirst($module) }}</label>
                                                    </td>
                                                    <td>

                                                        <div class="row ">
                                                            @if (in_array('view ' . $module, (array) $permissions))
                                                                @if ($key = array_search('view ' . $module, $permissions))
                                                                    <div
                                                                        class="col-md-3 custom-control custom-checkbox">
                                                                        <input type="checkbox" name="permissions[]"
                                                                            value="{{ $key }}"
                                                                            class="form-check-input pos_checkall isscheck_{{ str_replace(' ', '', $module) }}"
                                                                            id="permission{{ $key }}"
                                                                            {{ in_array($key, $role->permissions->pluck('id')->toArray()) ? 'checked' : '' }}>
                                                                        <label for="permission{{ $key }}"
                                                                            class="custom-control-label">View</label><br>
                                                                    </div>
                                                                @endif
                                                            @endif

                                                            @if (in_array('add ' . $module, (array) $permissions))
                                                                @if ($key = array_search('add ' . $module, $permissions))
                                                                    <div
                                                                        class="col-md-3 custom-control custom-checkbox">
                                                                        <input type="checkbox" name="permissions[]"
                                                                            value="{{ $key }}"
                                                                            class="form-check-input pos_checkall isscheck_{{ str_replace(' ', '', $module) }}"
                                                                            id="permission{{ $key }}"
                                                                            {{ in_array($key, $role->permissions->pluck('id')->toArray()) ? 'checked' : '' }}>
                                                                        <label for="permission{{ $key }}"
                                                                            class="custom-control-label">Add</label><br>
                                                                    </div>
                                                                @endif
                                                            @endif

                                                            @if (in_array('manage ' . $module, (array) $permissions))
                                                                @if ($key = array_search('manage ' . $module, $permissions))
                                                                    <div
                                                                        class="col-md-3 custom-control custom-checkbox">
                                                                        <input type="checkbox" name="permissions[]"
                                                                            value="{{ $key }}"
                                                                            class="form-check-input pos_checkall isscheck_{{ str_replace(' ', '', $module) }}"
                                                                            id="permission{{ $key }}"
                                                                            {{ in_array($key, $role->permissions->pluck('id')->toArray()) ? 'checked' : '' }}>
                                                                        <label for="permission{{ $key }}"
                                                                            class="custom-control-label">Manage</label><br>
                                                                    </div>
                                                                @endif
                                                            @endif

                                                            @if (in_array('create ' . $module, (array) $permissions))
                                                                @if ($key = array_search('create ' . $module, $permissions))
                                                                    <div
                                                                        class="col-md-3 custom-control custom-checkbox">
                                                                        <input type="checkbox" name="permissions[]"
                                                                            value="{{ $key }}"
                                                                            class="form-check-input pos_checkall isscheck_{{ str_replace(' ', '', $module) }}"
                                                                            id="permission{{ $key }}"
                                                                            {{ in_array($key, $role->permissions->pluck('id')->toArray()) ? 'checked' : '' }}>
                                                                        <label for="permission{{ $key }}"
                                                                            class="custom-control-label">Create</label><br>
                                                                    </div>
                                                                @endif
                                                            @endif

                                                            @if (in_array('edit ' . $module, (array) $permissions))
                                                                @if ($key = array_search('edit ' . $module, $permissions))
                                                                    <div
                                                                        class="col-md-3 custom-control custom-checkbox">
                                                                        <input type="checkbox" name="permissions[]"
                                                                            value="{{ $key }}"
                                                                            class="form-check-input pos_checkall isscheck_{{ str_replace(' ', '', $module) }}"
                                                                            id="permission{{ $key }}"
                                                                            {{ in_array($key, $role->permissions->pluck('id')->toArray()) ? 'checked' : '' }}>
                                                                        <label for="permission{{ $key }}"
                                                                            class="custom-control-label">Edit</label><br>
                                                                    </div>
                                                                @endif
                                                            @endif

                                                            @if (in_array('update ' . $module, (array) $permissions))
                                                                @if ($key = array_search('update ' . $module, $permissions))
                                                                    <div
                                                                        class="col-md-3 custom-control custom-checkbox">
                                                                        <input type="checkbox" name="permissions[]"
                                                                            value="{{ $key }}"
                                                                            class="form-check-input pos_checkall isscheck_{{ str_replace(' ', '', $module) }}"
                                                                            id="permission{{ $key }}"
                                                                            {{ in_array($key, $role->permissions->pluck('id')->toArray()) ? 'checked' : '' }}>
                                                                        <label for="permission{{ $key }}"
                                                                            class="custom-control-label">Update</label><br>
                                                                    </div>
                                                                @endif
                                                            @endif

                                                            @if (in_array('delete ' . $module, (array) $permissions))
                                                                @if ($key = array_search('delete ' . $module, $permissions))
                                                                    <div
                                                                        class="col-md-3 custom-control custom-checkbox">
                                                                        <input type="checkbox" name="permissions[]"
                                                                            value="{{ $key }}"
                                                                            class="form-check-input pos_checkall isscheck_{{ str_replace(' ', '', $module) }}"
                                                                            id="permission{{ $key }}"
                                                                            {{ in_array($key, $role->permissions->pluck('id')->toArray()) ? 'checked' : '' }}>
                                                                        <label for="permission{{ $key }}"
                                                                            class="custom-control-label">Delete</label><br>
                                                                    </div>
                                                                @endif
                                                            @endif

                                                            @if (in_array('show ' . $module, (array) $permissions))
                                                                @if ($key = array_search('show ' . $module, $permissions))
                                                                    <div
                                                                        class="col-md-3 custom-control custom-checkbox">
                                                                        <input type="checkbox" name="permissions[]"
                                                                            value="{{ $key }}"
                                                                            class="form-check-input pos_checkall isscheck_{{ str_replace(' ', '', $module) }}"
                                                                            id="permission{{ $key }}"
                                                                            {{ in_array($key, $role->permissions->pluck('id')->toArray()) ? 'checked' : '' }}>
                                                                        <label for="permission{{ $key }}"
                                                                            class="custom-control-label">Show</label><br>
                                                                    </div>
                                                                @endif
                                                            @endif

                                                            @if (in_array('send ' . $module, (array) $permissions))
                                                                @if ($key = array_search('send ' . $module, $permissions))
                                                                    <div
                                                                        class="col-md-3 custom-control custom-checkbox">
                                                                        <input type="checkbox" name="permissions[]"
                                                                            value="{{ $key }}"
                                                                            class="form-check-input pos_checkall isscheck_{{ str_replace(' ', '', $module) }}"
                                                                            id="permission{{ $key }}"
                                                                            {{ in_array($key, $role->permissions->pluck('id')->toArray()) ? 'checked' : '' }}>
                                                                        <label for="permission{{ $key }}"
                                                                            class="custom-control-label">Send</label><br>
                                                                    </div>
                                                                @endif
                                                            @endif

                                                            @if (in_array('create payment ' . $module, (array) $permissions))
                                                                @if ($key = array_search('create payment ' . $module, $permissions))
                                                                    <div
                                                                        class="col-md-3 custom-control custom-checkbox">
                                                                        <input type="checkbox" name="permissions[]"
                                                                            value="{{ $key }}"
                                                                            class="form-check-input pos_checkall isscheck_{{ str_replace(' ', '', $module) }}"
                                                                            id="permission{{ $key }}"
                                                                            {{ in_array($key, $role->permissions->pluck('id')->toArray()) ? 'checked' : '' }}>
                                                                        <label for="permission{{ $key }}"
                                                                            class="custom-control-label">Create
                                                                            Payment</label><br>
                                                                    </div>
                                                                @endif
                                                            @endif

                                                            @if (in_array('delete payment ' . $module, (array) $permissions))
                                                                @if ($key = array_search('delete payment ' . $module, $permissions))
                                                                    <div
                                                                        class="col-md-3 custom-control custom-checkbox">
                                                                        <input type="checkbox" name="permissions[]"
                                                                            value="{{ $key }}"
                                                                            class="form-check-input pos_checkall isscheck_{{ str_replace(' ', '', $module) }}"
                                                                            id="permission{{ $key }}"
                                                                            {{ in_array($key, $role->permissions->pluck('id')->toArray()) ? 'checked' : '' }}>
                                                                        <label for="permission{{ $key }}"
                                                                            class="custom-control-label">Delete
                                                                            Payment</label><br>
                                                                    </div>
                                                                @endif
                                                            @endif


                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                            
                                            {{-- Explicit "Create Barcode" permission as separate entry --}}
                                            @if (in_array('create barcode', (array) $permissions))
                                                @if ($key = array_search('create barcode', $permissions))
                                                    <tr>
                                                        <td><input type="checkbox"
                                                                class="form-check-input align-middle ischeck pos_checkall"
                                                                data-id="createbarcode"></td>
                                                        <td><label class="ischeck pos_checkall"
                                                                data-id="createbarcode">{{ __('Create Barcode') }}</label>
                                                        </td>
                                                        <td>
                                                            <div class="row">
                                                                <div class="col-md-3 custom-control custom-checkbox">
                                                                    <input type="checkbox" name="permissions[]"
                                                                        value="{{ $key }}"
                                                                        class="form-check-input pos_checkall isscheck_createbarcode"
                                                                        id="permission{{ $key }}"
                                                                        {{ in_array($key, $role->permissions->pluck('id')->toArray()) ? 'checked' : '' }}>
                                                                    <label for="permission{{ $key }}"
                                                                        class="custom-control-label">{{ __('Create Barcode') }}</label><br>
                                                                </div>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                @endif
                                            @endif
                                        </tbody>
                                    </table>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
    <div class="modal-footer">
        <input type="button" value="{{ __('Cancel') }}" class="btn  btn-light" data-bs-dismiss="modal">
        <input type="submit" value="{{ __('Update') }}" class="btn  btn-primary">
    </div>

</form>

<script>
    $(document).ready(function() {
        $("#staff_checkall").click(function() {
            $('.staff_checkall').not(this).prop('checked', this.checked);
        });
        $("#crm_checkall").click(function() {
            $('.crm_checkall').not(this).prop('checked', this.checked);
        });
        $("#project_checkall").click(function() {
            $('.project_checkall').not(this).prop('checked', this.checked);
        });
        $("#hrm_checkall").click(function() {
            $('.hrm_checkall').not(this).prop('checked', this.checked);
        });
        $("#account_checkall").click(function() {
            $('.account_checkall').not(this).prop('checked', this.checked);
        });
        $("#pos_checkall").click(function() {
            $('.pos_checkall').not(this).prop('checked', this.checked);
        });
        $(".ischeck").click(function() {
            var ischeck = $(this).data('id');
            $('.isscheck_' + ischeck).prop('checked', this.checked);
        });
    });
</script>
