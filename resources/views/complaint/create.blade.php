<form action="{{ url('complaint') }}" method="post">
    @csrf
    <div class="modal-body">
        {{-- start for ai module --}}
        @php
            $plan = \App\Models\Utility::getChatGPTSettings();
        @endphp
        @if ($plan->chatgpt == 1)
            <div class="text-end">
                <a href="#" data-size="md" class="btn  btn-primary btn-icon btn-sm" data-ajax-popup-over="true"
                    data-url="{{ route('generate', ['complaint']) }}" data-bs-placement="top"
                    data-title="{{ __('Generate content with AI') }}">
                    <i class="fas fa-robot"></i> <span>{{ __('Generate with AI') }}</span>
                </a>
            </div>
        @endif
        {{-- end for ai module --}}
        <div class="row">
            @if (\Auth::user()->type != 'employee')
                <div class="form-group col-md-6 col-lg-6 ">
                    <label for="complaint_from" class="form-label">{{ __('Complaint From') }}</label>
                    <select name="complaint_from" id="complaint_from" class="form-control select" required>
                        @foreach ($employees as $employee)
                            <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            <div class="form-group col-md-6 col-lg-6">
                <label for="complaint_against" class="form-label">{{ __('Complaint Against') }}</label>
                <select name="complaint_against" id="complaint_against" class="form-control select">
                    @foreach ($employees as $employee)
                        <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-md-6 col-lg-6">
                <label for="title" class="form-label">{{ __('Title') }}</label>
                <input type="text" name="title" id="title" class="form-control">
            </div>
            <div class="form-group col-md-6 col-lg-6">
                <label for="complaint_date" class="form-label">{{ __('Complaint Date') }}</label>
                <input type="date" name="complaint_date" id="complaint_date" class="form-control">
            </div>
            <div class="form-group col-md-12">
                <label for="description" class="form-label">{{ __('Description') }}</label>
                <textarea name="description" id="description" class="form-control" placeholder="{{ __('Enter Description') }}"></textarea>
            </div>

        </div>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn  btn-light" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
        <button type="submit" class="btn  btn-primary">{{ __('Create') }}</button>
    </div>
</form>
