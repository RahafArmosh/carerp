@if($customFields)
    @foreach($customFields as $customField)
        <div class="form-group">
            <label for="customField-{{ $customField->id }}" class="form-label">{{ __($customField->name) }}</label>
            <div class="input-group">
                @if($customField->type == 'text')
                    <input type="text" id="customField-{{ $customField->id }}" name="customField[{{ $customField->id }}]" class="form-control">
                @elseif($customField->type == 'email')
                    <input type="email" id="customField-{{ $customField->id }}" name="customField[{{ $customField->id }}]" class="form-control">
                @elseif($customField->type == 'number')
                    <input type="number" id="customField-{{ $customField->id }}" name="customField[{{ $customField->id }}]" class="form-control">
                @elseif($customField->type == 'date')
                    <input type="date" id="customField-{{ $customField->id }}" name="customField[{{ $customField->id }}]" class="form-control">
                @elseif($customField->type == 'textarea')
                    <textarea id="customField-{{ $customField->id }}" name="customField[{{ $customField->id }}]" class="form-control"></textarea>
                @elseif($customField->type == 'dropdown')
                    @php
                        $options = json_decode($customField->options, true);
                    @endphp
                    <select id="customField-{{ $customField->id }}" name="customField[{{ $customField->id }}]" class="form-control">
                        @foreach($options as $option)
                            <option value="{{ $option }}">{{ $option }}</option>
                        @endforeach
                    </select>
                @endif
            </div>
        </div>
    @endforeach
@endif
