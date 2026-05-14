<form method="POST" action="{{ route('product-category.update', $category->id) }}">
    @csrf
    @method('PUT')
    <div class="modal-body">

        <div class="row">
            <div class="form-group col-md-12">
                <label for="name" class="form-label">{{ __('Category Name') }}</label>
                <input type="text" id="name" name="name" class="form-control font-style" required="required"
                    value="{{ $category->name }}">

            </div>

            <div class="form-group col-md-12 d-block">
                <label for="type" class="form-label">{{ __('Category Type') }}</label>
                <select id="type" name="type" class="form-control select cattype" required="required">
                    @foreach ($types as $key => $value)
                    <option value="{{ $key }}" {{ $category->type == $key ? 'selected' : '' }}>{{ $value }}</option>
                    @endforeach
                </select>
            </div>


            {{-- <div class="form-group col-md-12 account {{$category->type =='product & service'? 'd-none':''}}">
                {{Form::label('chart_account_id',__('Account'),['class'=>'form-label'])}}
                <select class="form-control select" name="chart_account" id="chart_account">
                </select>

            </div> --}}

            <div class="form-group col-md-12">
                <label for="sale_account_id" class="form-label">{{ __('Income Account') }}</label>
                <select id="sale_account_id" name="sale_account_id" class="form-control select select2"
                    required="required">
                    @if ($category->sale_account_id != 0)
                    <option value="{{ $category->sale_account_id }}">
                        {{ \App\Models\ChartOfAccount::where('id', $category->sale_account_id)->first()->name }}
                    </option>
                    @endif
                    @foreach ($incomeChartAccounts as $key => $value)
                    <option value="{{ $key }}">{{ $value }}</option>
                    @endforeach
                </select>

            </div>

            <div class="form-group col-md-12">
                <label for="purchase_account_id" class="form-label">{{ __('Purchase Account') }}</label>
                <select class="form-control select select2" name="purchase_account_id" id="purchase_account_id">
                    @if ($category->purchase_account_id != 0)
                    <option value="{{ $category->purchase_account_id }}">
                        {{ \App\Models\ChartOfAccount::where('id', $category->purchase_account_id)->first()->name }}
                    </option>
                    @endif
                    @foreach ($chart_accounts as $id => $codeName)
                    <option value="{{ $id }}">{{ $codeName }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-md-12">
                <label for="expense_account_id" class="form-label">{{ __('Expense Account') }}</label>
                <select name="expense_account_id" id="expense_account_id" class="form-control select select2" required>
                    @if ($category->expense_account_id != 0)
                    <option value="{{ $category->expense_account_id }}">
                        {{ \App\Models\ChartOfAccount::where('id', $category->expense_account_id)->first()->name }}
                    </option>
                    @endif
                    @foreach ($expenseChartAccounts as $key => $value)
                    <option value="{{ $key }}">{{ $value }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group col-md-6">
                <label for="is_manufacturer" class="form-label">{{ __('Manufacturer') }}</label>
                <div class="form-check">
                    <input type="checkbox" id="is_manufacturer" name="is_manufacturer" class="form-check-input" value="1" {{ $category->is_manufacturer ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_manufacturer">
                        {{ __('Mark as Manufacturer') }}
                    </label>
                </div>
            </div>

            <div class="form-group col-md-12">
                <label for="cost_calculation_method" class="form-label">{{ __('Cost Calculation Method') }}</label>
                <select id="cost_calculation_method" name="cost_calculation_method" class="form-control select" required>
                    <option value="avg" {{ ($category->cost_calculation_method ?? 'avg') == 'avg' ? 'selected' : '' }}>{{ __('Average Cost') }}</option>
                    <option value="actual" {{ ($category->cost_calculation_method ?? 'avg') == 'actual' ? 'selected' : '' }}>{{ __('Actual Cost') }}</option>
                </select>
                <small class="text-muted">{{ __('Average Cost: Uses weighted average calculation. Actual Cost: Uses purchase price from bill.') }}</small>
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <input type="button" value="{{ __('Cancel') }}" class="btn  btn-light" data-bs-dismiss="modal">
        <input type="submit" value="{{ __('Update') }}" class="btn  btn-primary">
    </div>
</form>


<script>
    //hide & show chartofaccount

    function debounce(func, wait, immediate) {
        var timeout;
        return function() {
            var context = this,
                args = arguments;
            var later = function() {
                timeout = null;
                if (!immediate) func.apply(context, args);
            };
            var callNow = immediate && !timeout;
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
            if (callNow) func.apply(context, args);
        };
    }
    $(document).on('change', '.cattype', debounce(function() {
        var type = $(this).val();
        if (type == 'product' || type == 'Qty product') {
            $('.account').removeClass('d-none').addClass('d-block');
            $('#expense_account_id').attr('required', 'required');
        } else {
            $('.account').addClass('d-none').removeClass('d-block');
            $('#expense_account_id').removeAttr('required');
        }
    }, 300));

    $(document).on('change', '#type', function() {
        var type = $(this).val();

        $.ajax({
            url: '{{ route('productServiceCategory.getaccount') }}',
            type: 'POST',
            data: {
                "type": type,
                "_token": "{{ csrf_token() }}",
            },
            success: function(data) {
                $('#chart_account').empty();
                $('#chart_account').append(
                    '<option value="">{{ __(' --- Select Account ---') }}</option>');
                $.each(data, function(key, value) {
                    var select = '';
                    if (key == '{{ $category->chart_account_id }}') {
                        select = 'selected';
                    }
                    $('#chart_account').append('<option value="' + key + '"  ' + select +
                        '>' + value + '</option>');

                });
            }
        });
    });
    $(document).ready(function() {
        $('#type').trigger('change')
    })

</script>