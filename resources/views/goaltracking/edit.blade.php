<!-- First Form -->
<form action="{{ route('goaltracking.update', $goalTracking->id) }}" method="post">
    @csrf
    @method('PUT')
    <div class="modal-body">
        <!-- start for ai module-->
        @php
            $plan = \App\Models\Utility::getChatGPTSettings();
        @endphp
        @if ($plan->chatgpt == 1)
            <div class="text-end">
                <a href="#" data-size="md" class="btn  btn-primary btn-icon btn-sm" data-ajax-popup-over="true"
                    data-url="{{ route('generate', ['goal tracking']) }}" data-bs-placement="top"
                    data-title="{{ __('Generate content with AI') }}">
                    <i class="fas fa-robot"></i> <span>{{ __('Generate with AI') }}</span>
                </a>
            </div>
        @endif
        <!-- end for ai module-->

        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="branch" class="form-label">{{ __('Branch') }}</label>
                    <select name="branch" class="form-control select" required>
                        @foreach ($brances as $branch)
                            <option value="{{ $branch }}"
                                {{ $goalTracking->branch == $branch ? 'selected' : '' }}>{{ $branch }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="goal_type" class="form-label">{{ __('GoalTypes') }}</label>
                    <select name="goal_type" class="form-control select" required>
                        @foreach ($goalTypes as $goalType)
                            <option value="{{ $goalType }}"
                                {{ $goalTracking->goal_type == $goalType ? 'selected' : '' }}>{{ $goalType }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="start_date" class="form-label">{{ __('Start Date') }}</label>
                    <input type="date" name="start_date" class="form-control"
                        value="{{ $goalTracking->start_date }}">
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="end_date" class="form-label">{{ __('End Date') }}</label>
                    <input type="date" name="end_date" class="form-control" value="{{ $goalTracking->end_date }}">
                </div>
            </div>
            <div class="col-md-12">
                <div class="form-group">
                    <label for="subject" class="form-label">{{ __('Subject') }}</label>
                    <input type="text" name="subject" class="form-control" value="{{ $goalTracking->subject }}">
                </div>
            </div>
            <div class="col-md-12">
                <div class="form-group">
                    <label for="target_achievement" class="form-label">{{ __('Target Achievement') }}</label>
                    <input type="text" name="target_achievement" class="form-control"
                        value="{{ $goalTracking->target_achievement }}">
                </div>
            </div>
            <div class="col-md-12">
                <div class="form-group">
                    <label for="description" class="form-label">{{ __('Description') }}</label>
                    <textarea name="description" class="form-control">{{ $goalTracking->description }}</textarea>
                </div>
            </div>
            <div class="col-md-12">
                <div class="form-group">
                    <label for="status" class="form-label">{{ __('Status') }}</label>
                    <select name="status" class="form-control select">
                        @foreach ($status as $stat)
                            <option value="{{ $stat }}"
                                {{ $goalTracking->status == $stat ? 'selected' : '' }}>{{ $stat }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-md-12">
                <fieldset id='demo1' class="rating">
                    <input class="stars" type="radio" id="rating-5" name="rating" value="5"
                        {{ $goalTracking->rating == 5 ? 'checked' : '' }}>
                    <label class="full" for="rating-5" title="Awesome - 5 stars"></label>
                    <input class="stars" type="radio" id="rating-4" name="rating" value="4"
                        {{ $goalTracking->rating == 4 ? 'checked' : '' }}>
                    <label class="full" for="rating-4" title="Pretty good - 4 stars"></label>
                    <input class="stars" type="radio" id="rating-3" name="rating" value="3"
                        {{ $goalTracking->rating == 3 ? 'checked' : '' }}>
                    <label class="full" for="rating-3" title="Meh - 3 stars"></label>
                    <input class="stars" type="radio" id="rating-2" name="rating" value="2"
                        {{ $goalTracking->rating == 2 ? 'checked' : '' }}>
                    <label class="full" for="rating-2" title="Kinda bad - 2 stars"></label>
                    <input class="stars" type="radio" id="technical-1" name="rating" value="1"
                        {{ $goalTracking->rating == 1 ? 'checked' : '' }}>
                    <label class="full" for="technical-1" title="Sucks big time - 1 star"></label>
                </fieldset>
            </div>
            <div class="col-md-12">
                <div class="form-group">
                    <input type="range" class="slider w-100 mb-0 " name="progress" id="myRange"
                        value="{{ $goalTracking->progress }}" min="1" max="100"
                        oninput="ageOutputId.value = myRange.value">
                    <output name="ageOutputName" id="ageOutputId">{{ $goalTracking->progress }}</output>
                    %
                </div>
            </div>


        </div>
    </div>
    <div class="modal-footer">
        <input type="button" value="{{ __('Cancel') }}" class="btn  btn-light" data-bs-dismiss="modal">
        <input type="submit" value="{{ __('Update') }}" class="btn  btn-primary">
    </div>
</form>
