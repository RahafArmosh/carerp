<form action="{{ route('leads.products.update', $lead->id) }}" method="POST">
    @csrf
    @method('PUT')

    <div class="modal-body repeater">
        <div id="product-list" data-repeater-list="products">
            <div class="product-row row mb-3" data-repeater-item>
                <div class="col-md-5">
                    <label>Product</label>
                    <select name="id" class="form-control select2" required>
                        <option value="">Select Product</option>
                        @foreach ($products as $id => $product)
                            <option value="{{ $id }}">{{ $product }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label>Price</label>
                    <input type="number" name="price" class="form-control" min="1" required>
                </div>
                <div class="col-md-3">
                    <label>Quantity</label>
                    <input type="number" name="quantity" class="form-control" min="1" required>
                </div>
                <div class="col-md-1 d-flex align-items-end">
                    <button type="button" data-repeater-delete class="btn btn-danger">×</button>
                </div>
            </div>
        </div>

        <div class="mb-3 text-end">
            <button type="button" id="add-product-row" data-repeater-create class="btn btn-outline-primary">+ Add
                Product</button>
        </div>
    </div>

    <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary">Save</button>
    </div>
</form>

<script>
    var selector = "body";

    if ($(selector + " .repeater").length) {
        var $repeater = $(selector + ' .repeater').repeater({
            initEmpty: false,
            defaultValues: {
                'status': 1
            },
            show: function() {
                $(this).slideDown();
            },
            function(setIndexes) {
                $dragAndDrop.on('drop', setIndexes);
            },
            isFirstItemUndeletable: true
        });
        var value = $(selector +
            " .repeater").attr('data-value');
        if (typeof value != 'undefined' && value.length != 0) {
            value = JSON.parse(value);
            $repeater.setList(value);
            console.log(value);
        }
    }
    // Reinitialize select2 for all elements
    $(".select2").select2({
        dropdownParent: $("#commonModal"),
        width: "100%",
        matcher: function(params, data) {
            // If no search term, return all options
            if (!params.term || params.term.trim() === "") {
                return data;
            }

            // Split search query into keywords
            const keywords = params.term.toLowerCase().split(" ");
            const text = data.text.toLowerCase();

            // Check if ALL keywords exist in the option's text (order ignored)
            const isMatch = keywords.every((keyword) => text.includes(keyword));

            return isMatch ? data : null;
        },
    });
    // Reinitialize Select2 when a new repeater row is added
    $(document).on("click", "[data-repeater-create]", function() {
        setTimeout(function() {
            $(".select2").select2({
                dropdownParent: $("#commonModal"),
                width: "100%",
                matcher: function(params, data) {
                    // If no search term, return all options
                    if (!params.term || params.term.trim() === "") {
                        return data;
                    }

                    // Split search query into keywords
                    const keywords = params.term.toLowerCase().split(" ");
                    const text = data.text.toLowerCase();

                    // Check if ALL keywords exist in the option's text (order ignored)
                    const isMatch = keywords.every((keyword) => text.includes(keyword));

                    return isMatch ? data : null;
                },
            });
        }, 100);
    });
</script>
