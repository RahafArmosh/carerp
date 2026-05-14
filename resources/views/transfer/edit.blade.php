<form action="{{ route('transfer.update', $transfer->id) }}" method="POST">
    @csrf
    @method('PUT')
    <div class="modal-body">
        {{-- start for ai module --}}
        @php
            $plan = \App\Models\Utility::getChatGPTSettings();
        @endphp
        @if ($plan->chatgpt == 1)
            <div class="text-end">
                <a href="#" data-size="md" class="btn  btn-primary btn-icon btn-sm" data-ajax-popup-over="true"
                    data-url="{{ route('generate', ['transfer']) }}" data-bs-placement="top"
                    data-title="{{ __('Generate content with AI') }}">
                    <i class="fas fa-robot"></i> <span>{{ __('Generate with AI') }}</span>
                </a>
            </div>
        @endif
        {{-- end for ai module --}}
        <div class="row">
            <div class="form-group col-lg-6 col-md-6 ">
                <label for="employee_id" class="form-label">{{ __('Employee') }}</label>
                <select id="employee_id" name="employee_id" class="form-control select" required>
                    @foreach ($employees as $employee)
                        <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-lg-6 col-md-6">
                <label for="branch_id" class="form-label">{{ __('Branch') }}</label>
                <select id="branch_id" name="branch_id" class="form-control select">
                    @foreach ($branches as $branch)
                        <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-lg-6 col-md-6">
                <label for="department_id" class="form-label">{{ __('Department') }}</label>
                <select id="department_id" name="department_id" class="form-control select">
                    @foreach ($departments as $department)
                        <option value="{{ $department->id }}">{{ $department->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-lg-6 col-md-6">
                <label for="transfer_date" class="form-label">{{ __('Transfer Date') }}</label>
                <input id="transfer_date" type="date" class="form-control" name="transfer_date">
            </div>
            <div class="form-group col-lg-12">
                <label for="description" class="form-label">{{ __('Description') }}</label>
                <textarea id="description" class="form-control" name="description" placeholder="{{ __('Enter Description') }}"></textarea>
            </div>
        </div>
    </div>

    <div class="modal-footer">
        <input type="button" value="{{ __('Cancel') }}" class="btn  btn-light" data-bs-dismiss="modal">
        <input type="submit" value="{{ __('Update') }}" class="btn  btn-primary">
    </div>
</form>
