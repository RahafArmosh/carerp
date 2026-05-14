<form action="{{ url('deals') }}" method="POST">
    @csrf
    <div class="modal-body">
        {{-- start for ai module --}}
        @php
            $plan = \App\Models\Utility::getChatGPTSettings();
        @endphp
        @if ($plan->chatgpt == 1)
            <div class="text-end">
                <a href="#" data-size="md" class="btn  btn-primary btn-icon btn-sm" data-ajax-popup-over="true"
                    data-url="{{ route('generate', ['deal']) }}" data-bs-placement="top"
                    data-title="{{ __('Generate content with AI') }}">
                    <i class="fas fa-robot"></i> <span>{{ __('Generate with AI') }}</span>
                </a>
            </div>
        @endif
        {{-- end for ai module --}}
        <div class="row">
            <div class="col-6 form-group">
                <label for="name" class="form-label">{{ __('Deal Name') }}</label>
                <input type="text" name="name" class="form-control" required />
            </div>
            <div class="col-6 form-group">
                <label for="phone" class="form-label">{{ __('Phone') }}</label>
                <input type="text" name="phone" class="form-control" required />
            </div>
            <div class="col-6 form-group">
                <label for="price" class="form-label">{{ __('Price') }}</label>
                <input type="number" name="price" class="form-control" min="0" />
            </div>
            <div class="col-6 form-group">
                <label for="SOID" class="form-label">{{ __('SOID') }}</label>
                <input type="text" name="SOID" id="SOID" class="form-control">
            </div>
            <div class="col-6 form-group">
                <label for="clients" class="form-label">{{ __('Clients') }}</label>
                <select name="clients[]" class="form-control select2" multiple required id="choices-multiple1">
                    @foreach ($clients as $id => $client)
                        <option value="{{ $id }}">{{ $client }}</option>
                    @endforeach
                </select>
                @if (count($clients) <= 0 && Auth::user()->type == 'Owner')
                    <div class="text-muted text-xs">
                        {{ __('Please create new clients') }} <a
                            href="{{ route('clients.index') }}">{{ __('here') }}</a>.
                    </div>
                @endif
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <input type="button" value="{{ __('Cancel') }}" class="btn  btn-light" data-bs-dismiss="modal">
        <input type="submit" value="{{ __('Create') }}" class="btn  btn-primary">
    </div>
</form>
