<div class="card">
    <div class="card-body">


        {{-- Upload Template --}}
        
        Are You Sure ?
        <br>
        <br>
        <form action="{{ route('quotations.convert_to_sale_order' , $quotation->id) }}" method="POST" >
            @csrf
            {{-- <div class="mb-3">
                <label for="trn" class="form-label">{{ __('Enter The TRN For The Customer') }}</label>
                <input type="text" name="trn" id="trn" class="form-control" required>
            </div> --}}
            <button type="submit" class="btn btn-primary">{{ __('Convert') }}</button>
        </form>

        @if(session('success'))
            <div class="alert alert-success mt-3">{{ session('success') }}</div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger mt-3">{{ session('error') }}</div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger mt-3">
                <ul class="mb-0">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

    </div>
</div>
