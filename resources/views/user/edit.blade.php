<form action="{{ route('users.update', $user->id) }}" method="POST">
    @csrf
    @method('PUT')
    <div class="modal-body">
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="name" class="form-label">{{ __('Name') }}</label>
                    <input id="name" type="text" class="form-control font-style" name="name"
                        placeholder="{{ __('Enter User Name') }}" value="{{ old('name', $user->name) }}" required>
                    @error('name')
                        <small class="invalid-name" role="alert">
                            <strong class="text-danger">{{ $message }}</strong>
                        </small>
                    @enderror
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="email" class="form-label">{{ __('Email') }}</label>
                    <input id="email" type="email" class="form-control" name="email"
                        placeholder="{{ __('Enter User Email') }}" value="{{ old('email', $user->email) }}" required>
                    @error('email')
                        <small class="invalid-email" role="alert">
                            <strong class="text-danger">{{ $message }}</strong>
                        </small>
                    @enderror
                </div>
            </div>
            <div class="col-6 form-group">
                <label for="manager_id" class="form-label">{{ __('Manager') }}<span class="text-danger">*</span></label>
                <select name="manager_id" id="manager_id" class="form-control select2">
                    @foreach ($users as $id => $userT)
                        <option value="{{ $id }}" {{ ($user->manager_id !== null && $user->manager_id == $id) ? 'selected' : '' }}>{{ $userT }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 form-group">
                <label for="default_pipeline" class="form-label">{{ __('Pipeline') }}<span class="text-danger">*</span></label>
                <select name="default_pipeline" id="default_pipeline" class="form-control select2">
                    @foreach ($pipeline as $id => $value)
                        <option value="{{ $value->id }}" {{ ($user->default_pipeline !== null && $user->default_pipeline == $value->id) ? 'selected' : '' }}>{{ $value->name }}</option>
                    @endforeach
                </select>
            </div>
            @if (\Auth::user()->type != 'super admin')
                <div class="form-group col-md-12">
                    <label for="role" class="form-label">{{ __('User Role') }}</label>
                    <select id="role" name="role" class="form-control select" required>
                        @foreach ($roles as $key => $value)
                            <option value="{{ $key }}" {{ ($user->type == $value || old('type') == $value) ? 'selected' : '' }}>
                                {{ $value }}
                            </option>
                        @endforeach
                    </select>
                    @error('role')
                        <small class="invalid-role" role="alert">
                            <strong class="text-danger">{{ $message }}</strong>
                        </small>
                    @enderror
                </div>
            @endif
            <div class="form-group col-md-12">
                <label for="warehouses" class="form-label">{{ __('Assigned Warehouses') }}</label>
                <small class="text-muted d-block mb-2">{{ __('Select warehouses this user can access in POS. Leave empty to allow access to all warehouses.') }}</small>
                <select id="warehouses" name="warehouses[]" class="form-control select2" multiple>
                    @foreach ($warehouses as $warehouseId => $warehouseName)
                        <option value="{{ $warehouseId }}" {{ $user->warehouses->contains($warehouseId) ? 'selected' : '' }}>
                            {{ $warehouseName }}
                        </option>
                    @endforeach
                </select>
            </div>
            @if (!$customFields->isEmpty())
                <div class="col-md-6">
                    <div class="tab-pane fade show" id="tab-2" role="tabpanel">
                        @include('customFields.formBuilder')
                    </div>
                </div>
            @endif
        </div>
    </div>
    <div class="modal-footer">
        <input type="button" value="{{ __('Cancel') }}" class="btn btn-light" data-bs-dismiss="modal">
        <input type="submit" value="{{ __('Update') }}" class="btn btn-primary">
    </div>
</form>
