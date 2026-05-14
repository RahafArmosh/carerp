<!-- Include CSS for country dropdown -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/country-select-js@2.0.1/build/css/countrySelect.min.css" />

<!-- Include jQuery (required) -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- Include country-select-js -->
<script src="https://cdn.jsdelivr.net/npm/country-select-js@2.0.1/build/js/countrySelect.min.js"></script>
<script>
    $(document).ready(function () {
        $('#country').countrySelect({
            defaultCountry: "{{ strtolower($campaign->target_country ?? 'us') }}"
        });
    });
</script>
<form method="POST" action="{{ route('campaigns.update', $campaign) }}">
    @csrf
    @method('PUT')
    <div class="modal-body">
        <div class="form-group">
            <label>Campaign Name</label>
            <input type="text" name="name" class="form-control" value="{{ old('name', $campaign->name ?? '') }}"
                required>
        </div>

        <div class="form-group">
            <label>Start Date</label>
            <input type="date" name="start_date" class="form-control"
                value="{{ old('start_date', $campaign->start_date ?? '') }}">
        </div>

        <div class="form-group">
            <label>End Date</label>
            <input type="date" name="end_date" class="form-control"
                value="{{ old('end_date', $campaign->end_date ?? '') }}">
        </div>

        <div class="form-group">
            <label>Status</label>
            <select name="status" class="form-control" required>
                <option value="active" {{ old('status', $campaign->status ?? '') === 'active' ? 'selected' : '' }}>
                    Active</option>
                <option value="inactive" {{ old('status', $campaign->status ?? '') === 'inactive' ? 'selected' : '' }}>
                    Inactive</option>
            </select>
        </div>

        <div class="form-group">
            <label>Assign to User</label>
            <select name="assigned_to" class="form-control" required>
                @foreach ($users as $user)
                    <option value="{{ $user->id }}"
                        {{ old('assigned_to', $campaign->assigned_to ?? '') == $user->id ? 'selected' : '' }}>
                        {{ $user->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="form-group">
            <label>Target Country</label>
            <input id="country" type="text" name="target_country" class="form-control"
                   value="{{ old('target_country', $campaign->target_country ?? '') }}">
        </div>

        <div class="form-group">
            <label>Source</label>
            <select name="source_id" id="source_id" class="form-control select2" multiple>
                @foreach ($sources as $id => $source)
                    <option value="{{ $id }}" {{ $campaign->source_id == $id ? 'selected' : '' }} >{{ $source }}</option>
                @endforeach
            </select>
            {{-- <input type="text" name="source" class="form-control"
                value="{{ old('source', $campaign->source ?? '') }}"> --}}
        </div>

        <div class="form-group">
            <label>URL</label>
            <input type="text" name="url" class="form-control" value="{{ old('url', $campaign->url ?? '') }}">
        </div>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
        <button type="submit" class="btn btn-primary">{{ __('Update') }}</button>
    </div>
</form>
