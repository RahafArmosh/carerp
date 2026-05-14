<form action="{{ route('deals.update', $deal->id) }}" method="POST">
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
                <input type="text" name="name" class="form-control" required
                    value="{{  $deal->name }}" />
            </div>
            <div class="col-6 form-group">
                <label for="phone" class="form-label">{{ __('Phone') }}</label>
                <input type="text" name="phone" class="form-control" 
                    value="{{  $deal->phone }}" />
            </div>
            <div class="col-6 form-group">
                <label for="price" class="form-label">{{ __('Price') }}</label>
                <input type="number" name="price" class="form-control" value="{{  $deal->price }}" />
            </div>
            <div class="col-6 form-group">
                <label for="SOID" class="form-label">{{ __('SOID') }}<span class="text-danger"></span></label>
                <input type="text" name="SOID" id="SOID" class="form-control"  value="{{ $deal->SOID }}">
            </div>
            <div class="col-6 form-group">
                <label for="payment" class="form-label">{{ __('payment') }}</label>
                <select name="payment" id="payment" class="form-control">
                    <option value="{{ $deal->payment }}">{{ $deal->payment }}</option>
                    <option value="LC">LC</option>
                    <option value="Cash">Cash</option>
                    <option value="Bank transfer">Bank transfer</option>
                </select>
            </div>
            <div class="col-6 form-group">
                <label for="pipeline_id" class="form-label">{{ __('Pipeline') }}</label>
                <select name="pipeline_id" class="form-control" required>
                    @foreach ($pipelines as $id => $pipeline)
                        <option value="{{ $id }}"
                            {{ old('pipeline_id', $deal->pipeline_id) == $id ? 'selected' : '' }}>
                            {{ $pipeline }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 form-group">
                <label for="stage_id" class="form-label">{{ __('Stage') }}</label>
                <select name="stage_id" class="form-control">
                    <option value="">{{ __('Select Stage') }}</option>
                    @foreach ($stageCnt as $id => $stage)
                        <option value="{{  $stage->id }}"
                            {{ old('stage_id', $deal->stage_id) == $stage->id ? 'selected' : '' }}>
                            {{ $stage->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            {{-- <div class="col-12 form-group">
                <label for="sources" class="form-label">{{ __('Sources') }}</label>
                <select name="sources[]" class="form-control select2" multiple  id="choices-multiple3">
                    @foreach ($sources as $id => $source)
                        <option value="{{ $id }}"
                            {{ in_array($id, old('sources', [])) ? 'selected' : '' }}>
                            {{ $source }}
                        </option>
                    @endforeach
                </select>
            </div> --}}
            {{-- <div class="col-12 form-group">
                <label for="products" class="form-label">{{ __('Products') }}</label>
                <select name="products[]" class="form-control select2" multiple  id="choices-multiple4">
                    @foreach ($products as $id => $product)
                        <option value="{{ $id }}"
                            {{ in_array($id, old('products', [])) ? 'selected' : '' }}>
                            {{ $product }}
                        </option>
                    @endforeach
                </select>
            </div> --}}
            <div class="col-12 form-group">
                <label for="notes" class="form-label">{{ __('Notes') }}</label>
                <textarea name="notes" class="summernote-simple">{{ old('notes', $deal->notes) }}</textarea>
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <input type="button" value="{{ __('Cancel') }}" class="btn btn-light" data-bs-dismiss="modal">
        <input type="submit" value="{{ __('Update') }}" class="btn btn-primary">
    </div>
</form>



<script>
    var stage_id = '{{ $deal->stage_id }}';

    $(document).ready(function() {
        $("#commonModal select[name=pipeline_id]").trigger('change');
    });

    $(document).on("change", "#commonModal select[name=pipeline_id]", function() {
        $.ajax({
            url: '{{ route('stages.json') }}',
            data: {
                pipeline_id: $(this).val(),
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            type: 'POST',
            success: function(data) {
                $('#stage_id').empty();
                $("#stage_id").append(
                    '<option value="" selected="selected">{{ __('Select Stage') }}</option>');
                $.each(data, function(key, data) {
                    var select = '';
                    if (key == '{{ $deal->stage_id }}') {
                        select = 'selected';
                    }
                    $("#stage_id").append('<option value="' + key + '" ' + select + '>' +
                        data + '</option>');
                });
                $("#stage_id").val(stage_id);
                $('#stage_id').select2({
                    placeholder: "{{ __('Select Stage') }}"
                });
            }
        })
    });
</script>
