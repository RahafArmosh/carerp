<form action="{{ url('job-application') }}" method="post" enctype="multipart/form-data">
    <div class="modal-body">
        <div class="row">
            <div class="form-group col-md-12">
                <label for="job" class="form-label">{{ __('Job') }}</label>
                <select name="job" id="jobs" class="form-control select2">
                    @foreach ($jobs as $job)
                        <option value="{{ $job->id }}">{{ $job->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-md-6">
                <label for="name" class="form-label">{{ __('Name') }}</label>
                <input type="text" name="name" class="form-control name">
            </div>
            <div class="form-group col-md-6">
                <label for="email" class="form-label">{{ __('Email') }}</label>
                <input type="text" name="email" class="form-control">
            </div>
            <div class="form-group col-md-6">
                <label for="phone" class="form-label">{{ __('Phone') }}</label>
                <input type="text" name="phone" class="form-control">
            </div>
            <div class="form-group col-md-6 dob d-none">
                <label for="dob" class="form-label">{{ __('Date of Birth') }}</label>
                <input type="date" name="dob" class="form-control">
            </div>
            <div class="form-group col-md-6 gender d-none">
                <label for="gender" class="form-label">{{ __('Gender') }}</label>
                <div class="d-flex radio-check">
                    <div class="form-check form-check-inline form-group">
                        <input type="radio" id="g_male" value="Male" name="gender" class="form-check-input">
                        <label class="form-check-label" for="g_male">{{ __('Male') }}</label>
                    </div>
                    <div class="form-check form-check-inline form-group">
                        <input type="radio" id="g_female" value="Female" name="gender" class="form-check-input">
                        <label class="form-check-label" for="g_female">{{ __('Female') }}</label>
                    </div>
                </div>
            </div>
            <div class="form-group col-md-6 country d-none">
                <label for="country" class="form-label">{{ __('Country') }}</label>
                <input type="text" name="country" class="form-control">
            </div>
            <div class="form-group col-md-6 country d-none">
                <label for="state" class="form-label">{{ __('State') }}</label>
                <input type="text" name="state" class="form-control">
            </div>
            <div class="form-group col-md-6 country d-none">
                <label for="city" class="form-label">{{ __('City') }}</label>
                <input type="text" name="city" class="form-control">
            </div>

            <div class="form-group col-md-6 profile d-none">
                <label for="profile" class="form-label">{{ __('Profile') }}</label>
                <div class="choose-file form-group">
                    <label for="profile" class="form-label">
                        <div>{{ __('Choose file here') }}</div>
                        <input type="file" class="form-control" name="profile" id="profile"
                            data-filename="profile_create">
                    </label>
                    <p class="profile_create"></p>
                </div>
            </div>
            <div class="form-group col-md-6 resume d-none">
                <label for="resume" class="form-label">{{ __('CV / Resume') }}</label>
                <div class="choose-file form-group">
                    <label for="resume" class="form-label">
                        <div>{{ __('Choose file here') }}</div>
                        <input type="file" class="form-control" name="resume" id="resume"
                            data-filename="resume_create">
                    </label>
                    <p class="resume_create"></p>
                </div>
            </div>
            <div class="form-group col-md-12 letter d-none">
                <label for="cover_letter" class="form-label">{{ __('Cover Letter') }}</label>
                <textarea id="cover_letter" name="cover_letter" class="form-control"></textarea>
            </div>

            @foreach ($questions as $question)
                <div class="form-group col-md-12  question question_{{ $question->id }} d-none">
                    <label for="{{ $question->question }}" class="form-label">{{ $question->question }}</label>
                    <input type="text" class="form-control" name="question[{{ $question->question }}]"
                        {{ $question->is_required == 'yes' ? '' : '' }}>
                </div>
            @endforeach

        </div>
    </div>
    <div class="modal-footer">
        <input type="button" value="{{ __('Cancel') }}" class="btn  btn-light" data-bs-dismiss="modal">
        <input type="submit" value="{{ __('Create') }}" class="btn  btn-primary">
    </div>
</form>
