@if (Gate::check('edit product & service') || Gate::check('delete product & service') || Gate::check('sub products') || Gate::check('manage product & service'))
    @can('manage product & service')
        <div class="action-btn bg-secondary ms-2">
            <a href="{{ route('productservice.show', $product->id) }}" class="mx-3 btn btn-sm align-items-center"
                data-bs-toggle="tooltip" title="{{ __('View product & images') }}">
                <i class="ti ti-photo text-white"></i>
            </a>
        </div>
        <div class="action-btn bg-success ms-2">
            <a href="{{ route('productservice.brochure.pdf', $product->id) }}" class="mx-3 btn btn-sm align-items-center"
                data-bs-toggle="tooltip" title="{{ __('Download brochure PDF') }}" target="_blank" rel="noopener">
                <i class="ti ti-download text-white"></i>
            </a>
        </div>
    @endcan
    <div class="action-btn bg-warning ms-2">
        <a href="#" class="mx-3 btn btn-sm align-items-center"
            data-url="{{ route('productservice.detail', $product->id) }}" data-ajax-popup="true" data-bs-toggle="tooltip"
            title="{{ __('Warehouse Details') }}" data-title="{{ __('Warehouse Details') }}">
            <i class="ti ti-eye text-white"></i>
        </a>
    </div>

    @can('edit product & service')
        <div class="action-btn bg-info ms-2">
            <a href="#" class="mx-3 btn btn-sm align-items-center"
                data-url="{{ route('productservice.edit', $product->id) }}" data-ajax-popup="true" data-size="lg"
                data-bs-toggle="tooltip" title="{{ __('Edit') }}" data-title="{{ __('Edit Product') }}">
                <i class="ti ti-pencil text-white"></i>
            </a>
        </div>
    @endcan

    @can('delete product & service')
        <div class="action-btn bg-danger ms-2">
            <form method="POST" action="{{ route('productservice.destroy', $product->id) }}"
                id="delete-form-{{ $product->id }}">
                @csrf
                @method('DELETE')
                <a href="#" class="mx-3 btn btn-sm align-items-center bs-pass-para" data-bs-toggle="tooltip"
                    title="{{ __('Delete') }}">
                    <i class="ti ti-trash text-white"></i>
                </a>
            </form>
        </div>
    @endcan

    @can('sub products')
        <div class="action-btn bg-primary ms-2">
            <a href="{{ route('subProducts', $product->id) }}" class="mx-3 btn btn-sm align-items-center"
                data-bs-toggle="tooltip" title="{{ __('subProduct') }}" data-title="{{ __('view sub-products') }}">
                <i class="ti ti-basket text-white"></i>
            </a>
        </div>
    @endcan

    @if (count(\App\Models\SubProduct::where('product_id', $product->id)->get()) > 0)
        <div class="action-btn bg-dark ms-2">
            <a href="{{ route('subProductsedit', $product->id) }}" class="mx-3 btn btn-sm align-items-center"
                data-bs-toggle="tooltip" title="{{ __('subProduct') }}" data-title="{{ __('edit sub-products') }}">
                <i class="ti ti-edit-circle text-white"></i>
            </a>
        </div>
    @endif
@endif
