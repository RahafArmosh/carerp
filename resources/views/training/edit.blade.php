<form action="{{ route('training.update', $training->id) }}" method="post">
    @csrf
    @method('PUT')
    <div class="modal-body">
        <!-- start for ai module-->
        @php
            $plan = \App\Models\Utility::getChatGPTSettings();
        @endphp
        @if ($plan->chatgpt == 1)
            <div class="text-end">
                <a href="#" data-size="md" class="btn  btn-primary btn-icon btn-sm" data-ajax-popup-over="true"
                    data-url="{{ route('generate', ['training']) }}" data-bs-placement="top"
                    data-title="{{ __('Generate content with AI') }}">
                    <i class="fas fa-robot"></i> <span>{{ __('Generate with AI') }}</span>
                </a>
            </div>
        @endif
        <!-- end for ai module-->
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="branch" class="form-label">{{ __('Branch') }}</label>
                    <select name="branch" class="form-control select" required>
                        @foreach ($branches as $branch)
                            <option value="{{ $branch }}" {{ $training->branch == $branch ? 'selected' : '' }}>
                                {{ $branch }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="trainer_option" class="form-label">{{ __('Trainer Option') }}</label>
                    <select name="trainer_option" class="form-control select" required>
                        @foreach ($options as $option)
                            <option value="{{ $option }}"
                                {{ $training->trainer_option == $option ? 'selected' : '' }}>{{ $option }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="training_type" class="form-label">{{ __('Training Type') }}</label>
                    <select name="training_type" class="form-control select" required>
                        @foreach ($trainingTypes as $trainingType)
                            <option value="{{ $trainingType }}"
                                {{ $training->training_type == $trainingType ? 'selected' : '' }}>{{ $trainingType }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="trainer" class="form-label">{{ __('Trainer') }}</label>
                    <select name="trainer" class="form-control select" required>
                        @foreach ($trainers as $trainer)
                            <option value="{{ $trainer }}"
                                {{ $training->trainer == $trainer ? 'selected' : '' }}>{{ $trainer }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="training_cost" class="form-label">{{ __('Training Cost') }}</label>
                    <input type="number" name="training_cost" class="form-control" step="0.01"
                        value="{{ $training->training_cost }}">
                </div>
            </div>
            <div class="col-md-12">
                <div class="form-group">
                    <label for="employee" class="form-label">{{ __('Employee') }}</label>
                    <select name="employee" class="form-control select" required>
                        @foreach ($employees as $employee)
                            <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="start_date" class="form-label">{{ __('Start Date') }}</label>
                    <input type="date" name="start_date" class="form-control">
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="end_date" class="form-label">{{ __('End Date') }}</label>
                    <input type="date" name="end_date" class="form-control">
                </div>
            </div>
            <div class="form-group col-lg-12">
                <label for="description" class="form-label">{{ __('Description') }}</label>
                <textarea name="description" class="form-control" placeholder="{{ __('Description') }}"></textarea>
            </div>

        </div>
    </div>
    <div class="modal-footer">
        <input type="button" value="{{ __('Cancel') }}" class="btn  btn-light" data-bs-dismiss="modal">
        <input type="submit" value="{{ __('Update') }}" class="btn  btn-primary">
    </div>
</form>
