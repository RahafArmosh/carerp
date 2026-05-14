<form action="indicator" method="post">
    @csrf
    <div class="modal-body">
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="branch" class="form-label">Branch</label>
                    <select name="branch" id="branch" class="form-control select" required>
                        @foreach ($branches as $branch)
                            <option value="{{ $branch }}">{{ $branch }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="department" class="form-label">Department</label>
                    <select name="department" id="department" class="form-control select" required>
                        @foreach ($departments as $department)
                            <option value="{{ $department }}">{{ $department }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="designation" class="form-label">Designation</label>
                    <select name="designation" id="designation_id" class="select form-control select2-multiple"
                        data-toggle="select2" data-placeholder="Select Designation ..." required>
                        <!-- Populate options using JavaScript or an AJAX call -->
                    </select>
                </div>
            </div>
        </div>

        @foreach ($performance as $performances)
            <div class="row">
                <div class="col-md-12 mt-3">
                    <h6>{{ $performances->name }}</h6>
                    <hr class="mt-0">
                </div>

                @foreach ($performances->types as $types)
                    <div class="col-6">
                        {{ $types->name }}
                    </div>
                    <div class="col-6">
                        <fieldset id="demo1" class="rating">
                            <input class="stars" type="radio" id="technical-5-{{ $types->id }}"
                                name="rating[{{ $types->id }}]" value="5" />
                            <label class="full" for="technical-5-{{ $types->id }}"
                                title="Awesome - 5 stars"></label>
                            <input class="stars" type="radio" id="technical-4-{{ $types->id }}"
                                name="rating[{{ $types->id }}]" value="4" />
                            <label class="full" for="technical-4-{{ $types->id }}"
                                title="Pretty good - 4 stars"></label>
                            <input class="stars" type="radio" id="technical-3-{{ $types->id }}"
                                name="rating[{{ $types->id }}]}" value="3" />
                            <label class="full" for="technical-3-{{ $types->id }}" title="Meh - 3 stars"></label>
                            <input class="stars" type="radio" id="technical-2-{{ $types->id }}"
                                name="rating[{{ $types->id }}]}" value="2" />
                            <label class="full" for="technical-2-{{ $types->id }}"
                                title="Kinda bad - 2 stars"></label>
                            <input class="stars" type="radio" id="technical-1-{{ $types->id }}"
                                name="rating[{{ $types->id }}]}" value="1" />
                            <label class="full" for="technical-1-{{ $types->id }}"
                                title="Sucks big time - 1 star"></label>
                        </fieldset>
                    </div>
                @endforeach
            </div>
        @endforeach
    </div>
    <div class="modal-footer">
        <input type="button" value="Cancel" class="btn btn-light" data-bs-dismiss="modal">
        <input type="submit" value="Create" class="btn btn-primary">
    </div>
</form>
