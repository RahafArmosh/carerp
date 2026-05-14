<form method="POST" action="{{ route('sub-products.alternatives.store', $part->sku) }}" style="padding: 40px;">
    @csrf

 <div class="form-group">
    <label>{{ __('Alternative Part') }}</label>
    <select name="alternative_part_number" class="form-control select2" required>
        @foreach($parts as $p)
            <option value="{{ $p->sku }}">
                {{ $p->sku }} - {{ $p->name ?? '-' }}
            </option>
        @endforeach
    </select>
</div>


    <div class="form-group mt-3">
        <label>{{ __('Priority') }}</label>
        <input type="number" name="priority" class="form-control" value="1" min="1">
    </div>
    
    <div class="form-group mt-3">
        <div class="form-check">
            <input type="checkbox" name="bothway" value="1" class="form-check-input" id="bothway">
            <label class="form-check-label" for="bothway">{{ __('Create Both Ways') }}</label>
        </div>
    </div>


    <div class="text-end mt-4">
        <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
    </div>
</form>
