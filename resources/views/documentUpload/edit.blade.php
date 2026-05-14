<form action="{{ route('document-upload.update', $ducumentUpload->id) }}" method="POST" enctype="multipart/form-data">
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
                    data-url="{{ route('generate', ['document']) }}" data-bs-placement="top"
                    data-title="{{ __('Generate content with AI') }}">
                    <i class="fas fa-robot"></i> <span>{{ __('Generate with AI') }}</span>
                </a>
            </div>
        @endif
        {{-- end for ai module --}}
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="name" class="form-label">{{ __('Name') }}</label>
                    <input type="text" class="form-control" name="name" value="{{ $ducumentUpload->name }}"
                        required>
                </div>
            </div>

            <div class="col-md-6">
                <div class="form-group">
                    <label for="role" class="form-label">{{ __('Role') }}</label>
                    <select class="form-control select" name="role">
                        @foreach ($roles as $role)
                            <option value="{{ $role }}" @if ($role == $ducumentUpload->role) selected @endif>
                                {{ $role }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-md-12">
                <div class="form-group">
                    <label for="description" class="form-label">{{ __('Description') }}</label>
                    <textarea class="form-control" name="description" rows="3">{{ $ducumentUpload->description }}</textarea>
                </div>
            </div>
            <div class="col-md-6 form-group">
                <label for="document" class="form-label">{{ __('Document') }}</label>
                <div class="choose-file">
                    <label for="document" class="form-label">
                        <input type="file" class="form-control" name="document" id="document"
                            data-filename="document_create" required>
                        <img id="image"
                            src="{{ asset(Storage::url('uploads/documentUpload') . '/' . $ducumentUpload->document) }}"
                            class="mt-3" style="width:25%;">
                    </label>
                </div>
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <input type="button" value="{{ __('Cancel') }}" class="btn btn-light" data-bs-dismiss="modal">
        <input type="submit" value="{{ __('Update') }}" class="btn btn-primary">
    </div>
</form>


<script>
    document.getElementById('document').onchange = function() {
        var src = URL.createObjectURL(this.files[0])
        document.getElementById('image').src = src
    }
</script>
