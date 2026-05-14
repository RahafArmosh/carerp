<form action="{{ route('announcement.update', $announcement->id) }}" method="POST">
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
                    data-url="{{ route('generate', ['announcement']) }}" data-bs-placement="top"
                    data-title="{{ __('Generate content with AI') }}">
                    <i class="fas fa-robot"></i> <span>{{ __('Generate with AI') }}</span>
                </a>
            </div>
        @endif
        {{-- end for ai module --}}
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="title" class="form-label">{{ __('Announcement Title') }}</label>
                    <input type="text" name="title" id="title" class="form-control"
                        placeholder="{{ __('Enter Announcement Title') }}" value="{{ $announcement->title }}">
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="branch_id" class="form-label">{{ __('Branch') }}</label>
                    <select name="branch_id" id="branch_id" class="form-control select">
                        @foreach ($branch as $item)
                            <option value="{{ $item->id }}"
                                {{ $announcement->branch_id == $item->id ? 'selected' : '' }}>{{ $item->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="department_id" class="form-label">{{ __('Department') }}</label>
                    <select name="department_id" id="department_id" class="form-control select">
                        @foreach ($departments as $dept)
                            <option value="{{ $dept->id }}"
                                {{ $announcement->department_id == $dept->id ? 'selected' : '' }}>{{ $dept->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="start_date" class="form-label">{{ __('Announcement start Date') }}</label>
                    <input type="date" name="start_date" id="start_date" class="form-control"
                        value="{{ $announcement->start_date }}">
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="end_date" class="form-label">{{ __('Announcement End Date') }}</label>
                    <input type="date" name="end_date" id="end_date" class="form-control"
                        value="{{ $announcement->end_date }}">
                </div>
            </div>
            <div class="col-md-12">
                <div class="form-group">
                    <label for="description" class="form-label">{{ __('Announcement Description') }}</label>
                    <textarea name="description" id="description" class="form-control" placeholder="{{ __('Enter Announcement Title') }}">{{ $announcement->description }}</textarea>
                </div>
            </div>
        </div>

    </div>
    <div class="modal-footer">
        <input type="button" value="{{ __('Cancel') }}" class="btn btn-light" data-bs-dismiss="modal">
        <input type="submit" value="{{ __('Update') }}" class="btn  btn-primary">
    </div>
</form>
