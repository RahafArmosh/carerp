<div class="card bg-none card-box">
    @if (isset($product))
        <form action="{{ route('estimations.products.update', [$estimation->id, $product->id]) }}" method="post"
            class="">
        @else
            <form action="{{ route('estimations.products.store', $estimation->id) }}" method="post" class="">
    @endif
    @csrf
    @method('PUT')
    <div class="row">
        <div class="col-6 form-group">
            <label for="product_id" class="form-label">Product</label>
            <select name="product_id" class="form-control select2" required>
                <option value=""></option>
                @foreach ($products as $product)
                    <option value="{{ $product->id }}"></option>
                @endforeach
            </select>
        </div>
        <div class="col-6 form-group">
            <label for="quantity" class="form-label">Quantity</label>
            <input type="number" name="quantity" class="form-control" required min="1"
                value="{{ isset($product) ? null : 1 }}">
        </div>
        <div class="col-12 form-group">
            <label for="description" class="form-label">Description</label>
            <textarea name="description" class="form-control"></textarea>
        </div>
        <div class="form-group col-md-12 text-end">
            @if (isset($product))
                <input type="submit" value="Update" class="btn-create badge-blue">
            @else
                <input type="submit" value="Add" class="btn-create badge-blue">
            @endif
            <input type="button" value="Cancel" class="btn-create bg-gray" data-dismiss="modal">
        </div>
    </div>
    </form>
</div>
