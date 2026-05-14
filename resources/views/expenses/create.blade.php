<form action="{{ route('projects.expenses.store', $project->id) }}" method="POST" id="create_expense" enctype="multipart/form-data">
    @csrf
    <div class="modal-body">
        <div class="row">
            <div class="col-12 col-md-12">
                <div class="form-group">
                    <label for="name" class="form-label">{{ __('Name') }}</label>
                    <input type="text" name="name" id="name" class="form-control" required>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="form-group">
                    <label for="date" class="form-label">{{ __('Date') }}</label>
                    <input type="date" name="date" id="date" class="form-control">
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="form-group">
                    <label for="amount" class="form-label">{{ __('Amount') }}</label>
                    <div class="price-input input-group search-form">
                        <span class="input-group-text bg-transparent">{{ \Auth::user()->currencySymbol() }}</span>
                        <input type="number" name="amount" id="amount" class="form-control" required min="0">
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="form-group">
                    <label for="task_id" class="form-label">{{ __('Task') }}</label>
                    <select name="task_id" id="task_id" class="form-control select">
                        <option value="0" disabled selected>{{ __('Choose Task') }}</option>
                        @foreach($project->tasks as $task)
                            <option value="{{ $task->id }}">{{ $task->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-12 col-md-12">
                <div class="form-group">
                    <label for="description" class="form-label">{{ __('Description') }}</label>
                    <small class="form-text text-muted mb-2 mt-0">{{ __('This textarea will autosize while you type') }}</small>
                    <textarea name="description" id="description" class="form-control" rows="1" data-toggle="autosize"></textarea>
                </div>
            </div>
            <div class="col-12 col-md-12">
                <label for="attachment" class="form-label">{{ __('Attachment') }}</label>
                <div class="choose-file form-group">
                    <label for="attachment" class="form-label">
                        <div>{{ __('Choose file here') }}</div>
                        <input type="file" name="attachment" id="attachment" class="form-control" data-filename="attachment_create">
                    </label>
                    <p class="attachment_create"></p>
                </div>
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <input type="button" value="{{ __('Cancel') }}" class="btn btn-light" data-bs-dismiss="modal">
        <input type="submit" value="{{ __('Create') }}" class="btn btn-primary">
    </div>
</form>
