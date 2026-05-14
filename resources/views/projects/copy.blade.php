<form action="{{ route('project.copy.store', $project->id) }}" method="POST">
    @csrf
    <div class="modal-body">
        <div class="row">
            <div class="col-12">
                <div class="form-group m-2">
                    <div class="form-check">
                        <input type="checkbox" name="project" value="all" class="form-check-input" id="all">
                        <label for="all" class="form-check-label">{{ __('All') }}</label>
                    </div>
                </div>
                {{-- project task --}}
                <div class="form-group m-2">
                    <div class="form-check">
                        <input type="checkbox" name="task[]" value="task" class="form-check-input checkbox" id="task">
                        <label for="task" class="form-check-label">{{ __('Task') }}</label>
                    </div>
                </div>
                <div class="row mx-4">
                    <div class="col-4 form-group">
                        <div class="form-check">
                            <input type="checkbox" name="task[]" value="sub_task" class="form-check-input checkbox task" id="sub_task">
                            <label for="sub_task" class="form-check-label">{{ __('Sub Task') }}</label>
                        </div>
                    </div>
                    <div class="col-4 form-group">
                        <div class="form-check">
                            <input type="checkbox" name="task[]" value="task_comment" class="form-check-input checkbox task" id="task_comment">
                            <label for="task_comment" class="form-check-label">{{ __('Comment') }}</label>
                        </div>
                    </div>
                    <div class="col-4 form-group">
                        <div class="form-check">
                            <input type="checkbox" name="task[]" value="task_files" class="form-check-input checkbox task" id="task_files">
                            <label for="task_files" class="form-check-label">{{ __('Files') }}</label>
                        </div>
                    </div>
                </div>
                {{-- project bug --}}
                <div class="form-group m-2">
                    <div class="form-check">
                        <input type="checkbox" name="bug[]" value="bug" class="form-check-input checkbox" id="bug">
                        <label for="bug" class="form-check-label">{{ __('Bug') }}</label>
                    </div>
                </div>
                <div class="row mx-4">
                    <div class="col-6 form-group">
                        <div class="form-check">
                            <input type="checkbox" name="bug[]" value="bug_comment" class="form-check-input checkbox bug" id="bug_comment">
                            <label for="bug_comment" class="form-check-label">{{ __('Comment') }}</label>
                        </div>
                    </div>
                    <div class="col-6 form-group">
                        <div class="form-check">
                            <input type="checkbox" name="bug[]" value="bug_files" class="form-check-input checkbox bug" id="bug_files">
                            <label for="bug_files" class="form-check-label">{{ __('Files') }}</label>
                        </div>
                    </div>
                </div>
                <div class="form-group m-2">
                    <div class="form-check">
                        <input type="checkbox" name="user[]" value="user" class="form-check-input checkbox" id="user">
                        <label for="user" class="form-check-label">{{ __('Team Member') }}</label>
                    </div>
                </div>
                <div class="form-group m-2">
                    <div class="form-check">
                        <input type="checkbox" name="client[]" value="client" class="form-check-input checkbox" id="client">
                        <label for="client" class="form-check-label">{{ __('Client') }}</label>
                    </div>
                </div>
                <div class="form-group m-2">
                    <div class="form-check">
                        <input type="checkbox" name="milestone[]" value="milestone" class="form-check-input checkbox" id="milestone">
                        <label for="milestone" class="form-check-label">{{ __('Milestone') }}</label>
                    </div>
                </div>
                <div class="form-group m-2">
                    <div class="form-check">
                        <input type="checkbox" name="project_file[]" value="project_file" class="form-check-input checkbox" id="project_file">
                        <label for="project_file" class="form-check-label">{{ __('Project File') }}</label>
                    </div>
                </div>
                <div class="form-group m-2">
                    <div class="form-check">
                        <input type="checkbox" name="activity[]" value="activity" class="form-check-input checkbox" id="activity">
                        <label for="activity" class="form-check-label">{{ __('Activity') }}</label>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('Close') }}</button>
        <button type="submit" class="btn btn-primary">{{ __('Copy') }}</button>
    </div>
</form>

<script>
    $(document).ready(function(){
        $('#all').on('click',function(){
            if(this.checked){
                $('.checkbox').each(function(){
                    this.checked = true;
                });
            }else{
                $('.checkbox').each(function(){
                    this.checked = false;
                });
            }
        });
    });
</script>

<script>
    $(document).ready(function(){
        $("#sub_task").click(function(){
            $("#task").prop("checked", true);
        });
        $("#task_comment").click(function(){
            $("#task").prop("checked", true);
        });
        $("#task_files").click(function(){
            $("#task").prop("checked", true);
        });
        $("#bug_comment").click(function(){
            $("#bug").prop("checked", true);
        });
        $("#bug_files").click(function(){
            $("#bug").prop("checked", true);
        });

        $('#task').on('click',function(){
            $('.task').each(function(){
                this.checked = false;
            });
        });
        $('#bug').on('click',function(){
            $('.bug').each(function(){
                this.checked = false;
            });
        });
    });
</script>
