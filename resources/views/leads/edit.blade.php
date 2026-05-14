<form method="POST" action="{{ route('leads.update', $lead->id) }}">
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
                    data-url="{{ route('generate', ['lead']) }}" data-bs-placement="top"
                    data-title="{{ __('Generate content with AI') }}">
                    <i class="fas fa-robot"></i> <span>{{ __('Generate with AI') }}</span>
                </a>
            </div>
        @endif
        {{-- end for ai module --}}
        <div class="row">
            <div class="col-6 form-group">
                <label for="subject" class="form-label">{{ __('Company Name') }}<span class="text-danger">*</span></label>
                <input type="text" name="subject" id="subject" class="form-control" required value="{{ $lead->subject }}">
            </div>
            {{-- <div class="col-6 form-group">
                <label for="user_id" class="form-label">{{ __('User') }}<span class="text-danger">*</span></label>
                <select name="user_id" id="user_id" class="form-control select" required>
                    @foreach ($users as $id => $user)
                        <option value="{{ $id }}" {{ $lead->user_id == $id ? 'selected' : '' }}>{{ $user }}</option>
                    @endforeach
                </select>
            </div> --}}
            <div class="col-6 form-group">
                <label for="name" class="form-label">{{ __('Name') }}<span class="text-danger">*</span></label>
                <input type="text" name="name" id="name" class="form-control" required value="{{ $lead->name }}">
            </div>
            <div class="col-6 form-group">
                <label for="email" class="form-label">{{ __('Email') }}<span class="text-danger">*</span></label>
                <input type="email" name="email" id="email" class="form-control" required value="{{ $lead->email }}">
            </div>
            <div class="col-6 form-group">
                <label for="phone" class="form-label">{{ __('Phone') }}<span class="text-danger">*</span></label>
                <input type="text" name="phone" id="phone" class="form-control" required value="{{ $lead->phone }}">
            </div>
            <div class="col-6 form-group">
                <label for="whatsapp" class="form-label">{{ __('Whatsapp') }}<span class="text-danger">*</span></label>
                <input type="text" name="whatsapp" id="whatsapp" class="form-control" required value="{{ $lead->whatsapp }}">
            </div>
            <div class="col-6 form-group">
                <label for="qty" class="form-label">{{ __('Qty') }}</label>
                <input type="number" name="qty" id="qty" class="form-control" value="{{ $lead->quantity }}">
            </div>
            <div class="col-6 form-group">
                <label for="payment" class="form-label">{{ __('payment') }}</label>
                <select name="payment" id="payment" class="form-control">
                    <option value="{{ $lead->payment }}">{{ $lead->payment }}</option>
                    <option value="LC">LC</option>
                    <option value="Cash">Cash</option>
                    <option value="Bank transfer">Bank transfer</option>
                    <option value="Credit Customer">Credit Customer</option>
                </select>
            </div>
            @if (\Auth::user()->type == 'company')
            <div class="col-6 form-group d-none">
                <label for="pipeline_id" class="form-label">{{ __('Pipeline') }}<span
                        class="text-danger">*</span></label>
                <select name="pipeline_id" id="pipeline_id" class="form-control select" required>
                    @foreach ($pipelines as $id => $pipeline)
                        <option value="{{ $id }}" {{ $lead->pipeline_id == $id ? 'selected' : '' }}>{{ $pipeline }}</option>
                    @endforeach
                </select>
            </div>
            @endif
            <div class="col-6 form-group">
                <label for="stage_id" class="form-label">{{ __('Stage') }}<span class="text-danger">*</span></label>
                <select name="stage_id" id="stage_id" class="form-control select" required>
                    @foreach ($stageCnt as $lead_stage)
                    <option value="{{ $lead_stage->id}}" {{ $lead->stage_id == $lead_stage->id ? 'selected' : '' }}>{{ $lead_stage->name }}</option>
                    @endforeach
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
            <div class="col-12 form-group">
                <label for="products" class="form-label">{{ __('Products') }}<span class="text-danger"></span></label>
                <select name="products[]" id="products" class="form-control select2" multiple>
                    @foreach ($products as $id => $product)
                        <option value="{{ $id }}">{{ $product }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 form-group">
                <label for="notes" class="form-label">{{ __('Notes') }}</label>
                <textarea name="notes" id="notes" class="summernote-simple" required>{{$lead->notes  }}</textarea>
            </div>
        </div>
    </div>

    <div class="modal-footer">
        <input type="button" value="{{ __('Cancel') }}" class="btn  btn-light" data-bs-dismiss="modal">
        <input type="submit" value="{{ __('Update') }}" class="btn  btn-primary">
    </div>

</form>



{{-- <script>
    var stage_id = '{{ $lead->stage_id }}';

    $(document).ready(function() {
        var pipeline_id = $('[name=pipeline_id]').val();
        getStages(pipeline_id);
    });

    $(document).on("change", "#commonModal select[name=pipeline_id]", function() {
        var currVal = $(this).val();
        console.log('current val ', currVal);
        getStages(currVal);
    });

    function getStages(id) {
        $.ajax({
            url: '{{ route('leads.json') }}',
            data: {
                pipeline_id: id,
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            type: 'POST',
            success: function(data) {
                var stage_cnt = Object.keys(data).length;
                $("#stage_id").empty();
                if (stage_cnt > 0) {
                    $.each(data, function(key, data1) {
                        var select = '';
                        if (key == '{{ $lead->stage_id }}') {
                            select = 'selected';
                        }
                        $("#stage_id").append('<option value="' + key + '" ' + select + '>' +
                            data1 + '</option>');
                    });
                }
                $("#stage_id").val(stage_id);
                $('#stage_id').select2({
                    placeholder: "{{ __('Select Stage') }}"
                });
            }
        })
    }
</script> --}}
