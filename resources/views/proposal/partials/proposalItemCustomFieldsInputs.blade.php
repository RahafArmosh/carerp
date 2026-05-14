@php
    /** @var \Illuminate\Support\Collection|\App\Models\CustomField[] $fields */
    $fields = $fields ?? collect();
    // Important: inside jquery.repeater items, nested array names like foo[1]
    // can be lost / not grouped per item. We use a prefix-based name pattern
    // and parse it server-side per line item.
    $inputNamePrefix = $inputNamePrefix ?? 'proposal_item_custom_fields_';
@endphp

@if($fields->isNotEmpty())
    <div class="row g-2">
        @foreach($fields as $field)
            @php
                $fieldName = $inputNamePrefix . $field->id;
                $options = [];
                if ($field->type === 'dropdown' && !empty($field->options)) {
                    $decoded = json_decode($field->options, true);
                    if (is_array($decoded)) {
                        $options = $decoded;
                    }
                }
            @endphp
            <div class="col-md-4">
                <label class="form-label" style="font-size:12px; margin-bottom:4px;">{{ __($field->name) }}</label>

                @if($field->type === 'text')
                    <input type="text" class="form-control" name="{{ $fieldName }}">
                @elseif($field->type === 'email')
                    <input type="email" class="form-control" name="{{ $fieldName }}">
                @elseif($field->type === 'number')
                    <input type="number" class="form-control" name="{{ $fieldName }}">
                @elseif($field->type === 'date')
                    <input type="date" class="form-control" name="{{ $fieldName }}">
                @elseif($field->type === 'textarea')
                    <textarea class="form-control" name="{{ $fieldName }}" rows="2"></textarea>
                @elseif($field->type === 'dropdown')
                    <select class="form-control" name="{{ $fieldName }}">
                        <option value="">{{ __('Select') }}</option>
                        @foreach($options as $opt)
                            <option value="{{ $opt }}">{{ $opt }}</option>
                        @endforeach
                    </select>
                @else
                    <input type="text" class="form-control" name="{{ $fieldName }}">
                @endif
            </div>
        @endforeach
    </div>
@endif

