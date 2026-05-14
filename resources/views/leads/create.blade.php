<form method="POST" action="{{ url('leads') }}">
    @csrf
    <div class="modal-body">
        {{-- start for ai module --}}
        @php
            $plan = \App\Models\Utility::getChatGPTSettings();
        @endphp
        @if ($plan->chatgpt == 1)
            <div class="text-end">
                <a href="#" data-size="md" class="btn btn-primary btn-icon btn-sm" data-ajax-popup-over="true"
                    data-url="{{ route('generate', ['lead']) }}" data-bs-placement="top"
                    data-title="{{ __('Generate content with AI') }}">
                    <i class="fas fa-robot"></i> <span>{{ __('Generate with AI') }}</span>
                </a>
            </div>
        @endif
        {{-- end for ai module --}}
        <div class="row">
            <div class="col-6 form-group">
                <label for="subject" class="form-label">{{ __('Company Name') }}</label>
                <input type="text" name="subject" id="subject" class="form-control" required>
            </div>
            <div class="col-6 form-group">
                <label for="user_id" class="form-label">{{ __('User') }}</label>
                <select name="user_id" id="user_id" class="form-control select" required>
                    @foreach ($users as $id => $user)
                        <option value="{{ $id }}">{{ $user }}</option>
                    @endforeach
                </select>
                @if (count($users) == 1)
                    <div class="text-muted text-xs">
                        {{ __('Please create new users') }} <a
                            href="{{ route('users.index') }}">{{ __('here') }}</a>.
                    </div>
                @endif
            </div>
            <div class="col-6 form-group">
                <label for="name" class="form-label">{{ __('Name') }}</label>
                <input type="text" name="name" id="name" class="form-control" required>
            </div>
            <div class="col-6 form-group">
                <label for="email" class="form-label">{{ __('Email') }}</label>
                <input type="email" name="email" id="email" class="form-control" required>
            </div>
            <div class="col-6 form-group">
                <label for="phone" class="form-label">{{ __('Phone') }}</label>
                <input type="text" name="phone" id="phone" class="form-control" required>
            </div>
            <div class="col-6 form-group">
                <label for="whatsapp" class="form-label">{{ __('Whatsapp') }}</label>
                <input type="text" name="whatsapp" id="whatsapp" class="form-control" required>
            </div>
            <div class="col-6 form-group">
                <label for="qty" class="form-label">{{ __('Qty') }}</label>
                <input type="number" name="qty" id="qty" class="form-control">
            </div>
            <div class="col-6 form-group">
                <label for="payment" class="form-label">{{ __('payment') }}</label>
                <select name="payment" id="payment" class="form-control">
                    <option value=""></option>
                    <option value="LC">LC</option>
                    <option value="Cash">Cash</option>
                    <option value="Bank transfer">Bank transfer</option>
                    <option value="Credit Customer">Credit Customer</option>
                </select>
            </div>
            <div class="col-12 form-group">
                <label for="sources" class="form-label">{{ __('Sources') }}<span class="text-danger"></span></label>
                <select name="sources[]" id="sources" class="form-control select2" multiple>
                    @foreach ($sources as $id => $source)
                        <option value="{{ $id }}">{{ $source }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 form-group">
                <label for="stage_id" class="form-label">{{ __('Stage') }}<span class="text-danger">*</span></label>
                <select name="stage_id" id="stage_id" class="form-control select" required>
                    @foreach ($stageCnt as $lead_stage)
                    <option value="{{ $lead_stage->id}}">{{ $lead_stage->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 form-group">
                <label for="notes" class="form-label">{{ __('Notes') }}</label>
                <textarea name="notes" id="notes" class="summernote-simple"></textarea>
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <input type="button" value="{{ __('Cancel') }}" class="btn btn-light" data-bs-dismiss="modal">
        <input type="submit" value="{{ __('Create') }}" class="btn btn-primary">
    </div>
</form>
