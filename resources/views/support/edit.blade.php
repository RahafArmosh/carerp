<form action="{{ route('support.update', $support->id) }}" method="POST" enctype="multipart/form-data">
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
                    data-url="{{ route('generate', ['support']) }}" data-bs-placement="top"
                    data-title="{{ __('Generate content with AI') }}">
                    <i class="fas fa-robot"></i> <span>{{ __('Generate with AI') }}</span>
                </a>
            </div>
        @endif
        {{-- end for ai module --}}
        <div class="row">
            <div class="form-group col-md-12">
                <label for="subject" class="form-label">{{ __('Subject') }}</label>
                <input type="text" name="subject" value="{{ $support->subject }}" class="form-control" required>
            </div>
            @if (\Auth::user()->type != 'client')
                <div class="form-group col-md-6">
                    <label for="user" class="form-label">{{ __('Support for User') }}</label>
                    <select name="user" class="form-control select">
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}" {{ $user->id == $support->user ? 'selected' : '' }}>
                                {{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            <div class="form-group col-md-6">
                <label for="priority" class="form-label">{{ __('Priority') }}</label>
                <select name="priority" class="form-control select">
                    @foreach ($priority as $key => $value)
                        <option value="{{ $key }}" {{ $key == $support->priority ? 'selected' : '' }}>
                            {{ $value }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-md-6">
                <label for="status" class="form-label">{{ __('Status') }}</label>
                <select name="status" class="form-control select">
                    @foreach ($status as $key => $value)
                        <option value="{{ $key }}" {{ $key == $support->status ? 'selected' : '' }}>
                            {{ $value }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-md-6">
                <label for="end_date" class="form-label">{{ __('End Date') }}</label>
                <input type="date" name="end_date" value="{{ $support->end_date }}" class="form-control" required>
            </div>
            <div class="form-group col-md-12">
                <label for="description" class="form-label">{{ __('Description') }}</label>
                <textarea name="description" class="form-control" rows="3">{{ $support->description }}</textarea>
            </div>
            <div class="form-group col-md-6">
                <label for="attachment" class="form-label">{{ __('Attachment') }}</label>
                <input type="file" name="attachment" id="attachment" class="form-control"
                    data-filename="attachment_create">
                @if ($support->attachment)
                    <img id="image" class="mt-2"
                        src="{{ asset(Storage::url('uploads/supports')) . '/' . $support->attachment }}"
                        style="width:25%;">
                @endif
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <input type="button" value="{{ __('Cancel') }}" class="btn  btn-light" data-bs-dismiss="modal">
        <input type="submit" value="{{ __('Update') }}" class="btn  btn-primary">
    </div>
</form>



<script>
    document.getElementById('attachment').onchange = function() {
        var src = URL.createObjectURL(this.files[0])
        document.getElementById('image').src = src
    }
</script>
