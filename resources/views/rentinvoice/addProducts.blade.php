@extends('layouts.admin')
@section('page-title')
{{__('Invoice Create')}}
@endsection
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{route('dashboard')}}">{{__('Dashboard')}}</a></li>
<li class="breadcrumb-item"><a href="{{route('invoice.index')}}">{{__('Invoice')}}</a></li>
<li class="breadcrumb-item">{{__('Invoice Create')}}</li>
@endsection
@push('script-page')
<script src="{{asset('js/jquery-ui.min.js')}}"></script>
<script src="{{asset('js/jquery.repeater.min.js')}}"></script>
<script src="{{ asset('js/jquery-searchbox.js') }}"></script>
<script>
    var selector = "body";
        var taxes = '';
        var tax = [];


        $(document).ready(function () {
            var taxValue = <?php echo json_encode($totalTaxPrice); ?>;
            var totalAmount = <?php echo json_encode($total_amount); ?>;

            $('.totalTax').html((parseInt(totalAmount) * (parseInt(taxValue) / 100)).toFixed(2));
            $('.totalAmount').html((parseInt(totalAmount)+(parseInt(totalAmount) * (parseInt(taxValue) / 100))).toFixed(2));


        });
</script>

<script>
    $(document).on('keyup change', '.price', function () {
            var el = $(this).parent().parent().parent().parent();
            var price = $(this).val();


            // var totalItemPrice =  price;

            var totalItemTaxPrice = 0;


            var totalItemPrice = 0;
            var priceInput = $('.price');
            for (var j = 0; j < priceInput.length; j++) {
                totalItemPrice += (parseFloat(priceInput[j].value));
            }


            $('.subTotal').html((totalItemPrice).toFixed(2));
            var taxValue = <?php echo json_encode($totalTaxPrice); ?>;
            $('.totalTax').html((parseInt(totalItemPrice) * (parseInt(taxValue) / 100)).toFixed(2));
            $('.totalAmount').html((parseInt(totalItemPrice)+(parseInt(totalItemPrice) * (parseInt(taxValue) / 100))).toFixed(2));


        });
</script>
<script>
    // Example using jQuery
        $(document).on('click', '.delete-btn', function (e) {
            e.preventDefault();
            var form = $(this).closest('form.delete-form');
            form.submit();
        });
</script>
@endpush
@section('content')
<div class="row">
    {{ Form::open(['route' => ['sub-product-invoice.update', $invoice->id], 'method' => 'POST', 'class' => 'w-100']) }}
    <div class="col-12">
        <h5 class="d-inline-block mb-4">{{__('Product & Services')}}</h5>
        <div class="card repeater">
            <div class="item-section py-2">
                <div class="row justify-content-between align-items-center">
                    <div class="col-md-12 d-flex align-items-center justify-content-between justify-content-md-end">
                        <div class="all-button-box me-2">
                            <a href="#" data-size="lg" data-url="{{ route('sub-product-invoice.create',$invoice->id) }}"
                                data-ajax-popup="true" data-bs-toggle="tooltip" title="{{__('Create New Product')}}"
                                class="btn btn-sm btn-primary">
                                <i class="ti ti-plus"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body table-border-style">
                <div class="table-responsive">
                    <table class="table mb-0" data-repeater-list="items" id="sortable-table">
                        <thead>
                            <tr>
                                <th width="20%">{{__('Product')}}</th>
                                <th>{{__('Name')}}</th>
                                <th>{{__('Number')}}</th>
                                <th>{{__('Sale Price')}}</th>
                                <th>{{__('Action')}}</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody class="ui-sortable" data-repeater-item>
                            @foreach ($subProducts as $index => $subProduct)
                            <tr>
                                <td width="25%" class="form-group">

                                    <div class="form-group  col-md-6">
                                        <label for="product_id" class="form-label">{{ __('Product') }}<span
                                                class="text-danger">*</span></label>
                                        <select name="items[{{ $index }}][product_id]" class="form-control select"
                                            required>
                                            <option value="{{ $subProduct->productService->id }}">{{
                                                $subProduct->productService->name }}</option>
                                            @foreach($product_services as $productId => $productName)
                                            <option value="{{ $productId }}">{{ $productName }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                </td>
                                <td>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="name" class="form-label">{{ __('Name') }}<span
                                                    class="text-danger">*</span></label>
                                            <input type="text" name="items[{{ $index }}][name]" class="form-control"
                                                required value="{{ $subProduct->name }}">
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="number" class="form-label">{{ __('Number') }}<span
                                                    class="text-danger">*</span></label>
                                            <input type="text" name="items[{{ $index }}][number]" class="form-control"
                                                required value="{{ $subProduct->number }}">
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    @php
                                    $plan= \App\Models\InvoiceProduct::where('sub_product_id',
                                    $subProduct->id)->first();
                                    @endphp
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="sale_price" class="form-label">{{ __('Sale Price') }}<span
                                                    class="text-danger">*</span></label>
                                            <input type="number" name="items[{{ $index }}][sale_price]"
                                                class="form-control price" required step="0.01"
                                                value="{{ $plan->price }}">
                                        </div>
                                    </div>
                                </td>


                                <td class="Action">

                                    <div class="action-btn bg-danger ms-2">
                                        {!! Form::open(['route' => ['sub-product-invoice.delete', $subProduct->id,
                                        'invoice_id' => $invoice->id], 'id' => 'delete-form-'.$subProduct->id, 'method'
                                        => 'POST', 'class' => 'delete-form']) !!}
                                        <a href="#" class="mx-3 btn btn-sm align-items-center bs-pass-para"
                                            data-bs-toggle="tooltip" title="{{__('Delete')}}">
                                            <i class="ti ti-trash text-white"></i>
                                        </a>
                                        {!! Form::close() !!}
                                    </div>


                                </td>
                            </tr>
                            <tr>
                                <td>
                                    @if(!$customFields->isEmpty())
                                    <div class="col-lg-6 col-md-6 col-sm-6">
                                        <div class="tab-pane fade show" id="tab-{{ $index }}" role="tabpanel">
                                            @include('customFields.formBuilder')
                                        </div>
                                    </div>
                                    @endif
                                </td>
                                <td colspan="2" class="form-group">
                                    {{ Form::textarea('description', null, ['class'=>'form-control
                                    pro_description','rows'=>'1','placeholder'=>__('Description')]) }}
                                </td>
                            </tr>
                            <input type="hidden" id="sub_product_id" name="items[{{ $index }}][sub_product_id]"
                                value="{{ $subProduct->id}}">
                            @endforeach
                        </tbody>

                        <tfoot>
                            <tr>
                                <td>&nbsp;</td>
                                <td>&nbsp;</td>
                                <td>&nbsp;</td>
                                <td></td>
                                <td>
                                    <div class="form-group">
                                        <div class="input-group">
                                            <div class="taxes">{{ $totalTaxName }}</div>
                                            {{ Form::hidden('tax','', ['class' => 'form-control tax']) }}
                                            {{ Form::hidden('itemTaxPrice','', ['class' => 'form-control itemTaxPrice'])
                                            }}
                                            {{ Form::hidden('itemTaxRate', '', ['class' => 'form-control itemTaxRate'])
                                            }}
                                        </div>
                                    </div>
                                </td>
                                <td class="text-end tax_val">{{ $totalTaxPrice }}</td>
                                <td></td>
                            </tr>
                            <tr>
                                <td>&nbsp;</td>
                                <td>&nbsp;</td>
                                <td>&nbsp;</td>
                                <td></td>
                                <td><strong>{{__('Sub Total')}} ({{\Auth::user()->currencySymbol()}})</strong></td>
                                <td class="text-end subTotal">{{ $total_amount }}</td>
                                <td></td>
                            </tr>
                            <tr>
                                <td>&nbsp;</td>
                                <td>&nbsp;</td>
                                <td>&nbsp;</td>
                                <td></td>
                                <td><strong>{{__('Discount')}} ({{\Auth::user()->currencySymbol()}})</strong></td>
                                <td class="text-end totalDiscount">0.00</td>
                                <td></td>
                            </tr>
                            <tr>
                                <td>&nbsp;</td>
                                <td>&nbsp;</td>
                                <td>&nbsp;</td>
                                <td></td>
                                <td><strong>{{__('Tax')}} ({{\Auth::user()->currencySymbol()}})</strong></td>
                                <td class="text-end totalTax">0.00</td>
                                <td></td>
                            </tr>
                            <tr>
                                <td>&nbsp;</td>
                                <td>&nbsp;</td>
                                <td>&nbsp;</td>
                                <td>&nbsp;</td>
                                <td class="blue-text"><strong>{{__('Total Amount')}}
                                        ({{\Auth::user()->currencySymbol()}})</strong></td>
                                <td class="blue-text text-end totalAmount">0.00</td>

                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>


    <div class="modal-footer">
        <input type="button" value="{{__('Cancel')}}" onclick="location.href = '{{route('invoice.index')}}';"
            class="btn btn-light">
        <input type="submit" value="{{__('Update')}}" class="btn  btn-primary">
    </div>
    {{ Form::close() }}
</div>
@endsection