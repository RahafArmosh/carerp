<div class="card">
    <div class="card-header">
        <h5>{{ __('Price Rules Import') }}</h5>
    </div>
    <div class="card-body">

        {{-- Download Template --}}
        <div class="mb-3">
            <a href="{{ route('price-rules.download') }}" class="btn btn-success">
                <i class="ti ti-file-export"></i> {{ __('Download Excel Template') }}
            </a>
        </div>

        {{-- Upload Template --}}
        <form action="{{ route('price-rules.upload') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="mb-3">
                <label for="file" class="form-label">{{ __('Upload Filled Template') }}</label>
                <input type="file" name="file" id="file" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary">{{ __('Upload and Import') }}</button>
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
