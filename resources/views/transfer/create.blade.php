<form action="{{ url('transfer') }}" method="POST">
    @csrf
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
            <div class="form-group col-lg-6 col-md-6">
                <label for="employee_id" class="form-label">{{ __('Employee') }}</label>
                <select id="employee_id" name="employee_id" class="form-control select" required>
                    @foreach ($employees as $id => $employee)
                        <option value="{{ $id }}">{{ $employee }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-lg-6 col-md-6">
                <label for="branch_id" class="form-label">{{ __('Branch') }}</label>
                <select id="branch_id" name="branch_id" class="form-control select">
                    @foreach ($branches as $id => $branch)
                        <option value="{{ $id }}">{{ $branch }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-lg-6 col-md-6">
                <label for="department_id" class="form-label">{{ __('Department') }}</label>
                <select id="department_id" name="department_id" class="form-control select">
                    @foreach ($departments as $id => $department)
                        <option value="{{ $id }}">{{ $department }}</option>
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
        <input type="submit" value="{{ __('Create') }}" class="btn  btn-primary">
    </div>
</form>
