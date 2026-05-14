@php
    $currantLang = \Auth::user()->lang;
@endphp
<form action="" id="myForm">
    @csrf
    <div class="row">
        <div class="col-12">
            <div class="form-group">
                <label for="template" class="col-form-label">{{ __('For What') }}</label><br>
                </br>
                @foreach ($templateName as $key => $value)
                    <div class="form-check form-check-inline">
                        <input class="form-check-input template_name" type="radio" name="template_name"
                            value="{{ $value->id }}" id="product_name_{{ $value->id }}"
                            data-name="{{ $value->template_name }}">
                        <label class="form-check-label" for="product_name_{{ $value->id }}">
                            {{ ucWords(str_replace('_', ' ', $value->template_name)) }}
                        </label>
                    </div>
                @endforeach
            </div>
        </div>
        <div class="col-6">
            <div class="form-group">
                <label for="language" class="col-form-label">{{ __('Language') }}</label>
                <select name="language" class="form-select" id="language">
                    @foreach (App\Models\Utility::flagOfCountry() as $key => $lang)
                        <option value="{{ $key }}" {{ $currantLang == $key ? 'selected' : '' }}>
                            {{ Str::upper($lang) }}</option>
                    @endforeach
                </select>
            </div>
        </div>


        <div class="col-6 tone">
            <div class="form-group">
                <label for="" class="col-form-label">{{ __('Tone') }}</label>
                @php
                    $tone = [
                        'funny' => 'funny',
                        'casual' => 'casual',
                        'excited' => 'excited',
                        'professional' => 'professional',
                        'witty' => 'witty',
                        'sarcastic' => 'sarcastic',
                        'feminine' => 'feminine',
                        'masculine' => 'masculine',
                        'bold' => 'bold',
                        'dramatic' => 'dramatic',
                        'gumpy' => 'gumpy',
                        'secretive' => 'secretive',
                    ];
                @endphp
                <select name="tone" class="form-control">
                    @foreach ($tone as $key => $value)
                        <option value="{{ $key }}">{{ $value }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="col-6">
            <div class="form-group">
                <label for="" class="col-form-label">{{ __('AI Creativity') }}</label>
                <select name="ai_creativity" id="ai_creativity" class="form-select">
                    <option value="1">{{ __('High') }}</option>
                    <option value="0.5">{{ __('Meduium') }}</option>
                    <option value="0">{{ __('Low') }}</option>
                </select>
            </div>
        </div>
        <div class="col-6">
            <div class="form-group">
                <label for="" class="col-form-label">{{ __('Number of Result') }}</label>
                <select name="num_of_result" id="" class="form-select">
                    @for ($i = 1; $i <= 10; $i++)
                        <option value="{{ $i }}">{{ $i }}</option>
                    @endfor
                </select>
            </div>
        </div>
        <div class="col-6">
            <div class="form-group">
                <label for="" class="col-form-label">{{ __('Maximum Result Length') }}</label>
                <input type="number" name="result_length" class="form-control" value="10">
            </div>
        </div>
        <div class="col-12" id="getkeywords">
        </div>

    </div>
</form>
<div class="response">

    <a class="btn btn-primary btn-sm float-left" href="#!" id="generate">{{ __('Generate') }}</a>
    <a href="#!" onclick="copyText()" class="btn btn-primary btn-sm float-end "><i class="ti ti-copy"></i>
        {{ __('Copy Text') }}</a>
    <a href="#!" onclick="copySelectedText()" class="btn btn-primary btn-sm float-end me-2"><i
            class="ti ti-copy"></i> {{ __('Copy Selected Text') }}</a>
    <div class="form-group mt-3">
        <textarea name="description" class="form-control" rows="5" placeholder="{{ __('Description') }}"
            id="ai-description"></textarea>
    </div>
</div>

<script>
    function copyText() {
        var selected = $('input[name="template_name"]:checked').attr('data-name');
        var copied = $("#ai-description").val();

        var input = $('input[name=' + selected + ']').length;
        if (input > 0) {
            $('input[name=' + selected + ']').val(copied)
        } else {

            if ($('textarea[name=' + selected + ']').hasClass('summernote-simple') || $('textarea[name=' + selected +
                    ']').hasClass('summernote-simple-2')) {

                $('textarea[name=' + selected + ']').summernote("code", copied);
            } else {
                $('textarea[name=' + selected + ']').val(copied)
            }
        }
        show_toastr('success', 'Result text has been copied successfully', 'success');
        $('#commonModalOver').modal('hide');
    }

    function copySelectedText() {
        var selected = $('input[name="template_name"]:checked').attr('data-name');
        var selectedText = window.getSelection().toString();
        var input = $('input[name=' + selected + ']').length;
        $('#ai-description').after("Copied to clipboard");
        if (input > 0) {
            $('input[name=' + selected + ']').val(selectedText)
        } else {
            if ($('textarea[name=' + selected + ']').hasClass('summernote-simple') || $('textarea[name=' + selected +
                    ']').hasClass('summernote-simple-2')) {
                $('textarea[name=' + selected + ']').summernote("code", selectedText);
            } else {
                $('textarea[name=' + selected + ']').val(selectedText)
            }
        }
        show_toastr('success', 'Result text has been copied successfully', 'success');
        $('#commonModalOver').modal('hide');

    }

    $('body').ready(function() {
        $("#commonModalOver input:radio:first").prop("checked", true).trigger("change");

    });
    $('body').on('change', '.template_name', function() {
        var templateId = $(this).val();
        var url =
            $.ajax({
                type: 'post',
                url: '{{ route('generate.keywords', ['__templateId']) }}'.replace('__templateId',
                    templateId),
                datType: 'json',
                data: {
                    '_token': '{{ csrf_token() }}',
                    'template_id': templateId,
                },
                success: function(data) {
                    if (data.tone == 1) {
                        $('.tone').removeClass('d-none');
                        $('.tone select').attr('name', 'tone');
                    } else {
                        $('.tone').addClass('d-none');
                        $('.d-none select').removeAttr('name');
                    }

                    $('#getkeywords').empty();
                    $('#getkeywords').append(data.template)
                },
            })
    })
    $('body').on('click', '#generate', function() {
        var form = $("#myForm");
        $.ajax({
            type: 'post',
            url: '{{ route('generate.response') }}',
            datType: 'json',
            data: form.serialize(),
            beforeSend: function(msg) {
                $("#generate").empty();
                var html = '<span class="spinner-grow spinner-grow-sm" role="status"></span>';
                $("#generate").append(html);
            },
            afterSend: function(msg) {
                $("#generate2").empty();
                var html =
                    `<a class="btn btn-primary" href="#!" id="generate">{{ __('Generate') }}</a>`;
                $("#generate2").replaceWith(html);

            },
            success: function(data) {
                $('.response').removeClass('d-none');
                $('#generate').text('Re-Generate');
                if (data.message) {
                    show_toastr('error', data.message, 'error');
                    $('#commonModalOver').modal('hide');
                } else {
                    $('#ai-description').val(data)
                }
            },
        });
    });
</script>
