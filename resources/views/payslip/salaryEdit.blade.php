<div class="col-form-label">
    <div class="row px-3">
        <div class="col-md-4 mb-3">
            <h6 class="emp-title mb-0">{{ __('Employee') }}</h6>
            <h6 class="emp-title black-text">
                {{ !empty($payslip->employees) ? \Auth::user()->employeeIdFormat($payslip->employees->employee_id) : '' }}
            </h6>
        </div>
        <div class="col-md-4 mb-3">
            <h6 class="emp-title mb-0">{{ __('Basic Salary') }}</h6>
            <h6 class="emp-title black-text">{{ \Auth::user()->priceFormat($payslip->basic_salary) }}</h6>
        </div>
        <div class="col-md-4 mb-3">
            <h6 class="emp-title mb-0">{{ __('Payroll Month') }}</h6>
            <h6 class="emp-title black-text">{{ \Auth::user()->dateFormat($payslip->salary_month) }}</h6>
        </div>

        <div class="col-lg-12 our-system">
            <form action="{{ route('payslip.updateemployee', ['employee_id' => $payslip->employee_id]) }}"
                method="POST">
                @csrf
                <input type="hidden" name="payslip_id" value="{{ $payslip->id }}" class="form-control">
                <div class="row">

                    <ul class="nav nav-pills mb-3" id="pills-tab" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" id="pills-home-tab" data-bs-toggle="pill" href="#allowance"
                                role="tab" aria-controls="pills-home" aria-selected="true">{{ __('Allowance') }}</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="pills-profile-tab" data-bs-toggle="pill" href="#commission"
                                role="tab" aria-controls="pills-profile"
                                aria-selected="false">{{ __('Commission') }}</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="pills-contact-tab" data-bs-toggle="pill" href="#loan"
                                role="tab" aria-controls="pills-contact"
                                aria-selected="false">{{ __('Loan') }}</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="pills-contact-tab" data-bs-toggle="pill" href="#deduction"
                                role="tab" aria-controls="pills-contact"
                                aria-selected="false">{{ __('Saturation Deduction') }}</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="pills-contact-tab" data-bs-toggle="pill" href="#payment"
                                role="tab" aria-controls="pills-contact"
                                aria-selected="false">{{ __('Other Payment') }}</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="pills-contact-tab" data-bs-toggle="pill" href="#overtime"
                                role="tab" aria-controls="pills-contact"
                                aria-selected="false">{{ __('Overtime') }}</a>
                        </li>
                    </ul>
                    {{-- @dd($payslip->allowance) --}}
                    <div class="tab-content pt-4">
                        <div id="allowance" class="tab-pane in active">
                            <div class="row">
                                <div class="col-lg-12">
                                    <div class="card bg-none mb-0">
                                        <div class="row px-3">
                                            @php
                                                $allowances = json_decode($payslip->allowance);
                                            @endphp
                                            @foreach ($allowances as $allownace)
                                                <div class="col-md-12 form-group">
                                                    <label for="title"
                                                        class="col-form-label">{{ $allownace->title }}</label>
                                                    <input type="text" name="allowance[]"
                                                        value="{{ $allownace->amount }}" class="form-control">
                                                    <input type="hidden" name="allowance_id[]"
                                                        value="{{ $allownace->id }}" class="form-control">
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div id="commission" class="tab-pane">
                            <div class="row">
                                <div class="col-lg-12">
                                    <div class="card bg-none mb-0">
                                        <div class="row px-3">
                                            @php
                                                $commissions = json_decode($payslip->commission);
                                            @endphp
                                            @foreach ($commissions as $commission)
                                                <div class="col-md-12 form-group">
                                                    <label for="title"
                                                        class="col-form-label">{{ $commission->title }}</label>
                                                    <input type="text" name="commission[]"
                                                        value="{{ $commission->amount }}" class="form-control">
                                                    <input type="hidden" name="commission_id[]"
                                                        value="{{ $commission->id }}" class="form-control">
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div id="loan" class="tab-pane">
                            <div class="row">
                                <div class="col-lg-12">
                                    <div class="card bg-none mb-0">
                                        <div class="row px-3">
                                            @php
                                                $loans = json_decode($payslip->loan);
                                            @endphp
                                            @foreach ($loans as $loan)
                                                <div class="col-md-12 form-group">
                                                    <label for="title"
                                                        class="col-form-label">{{ $loan->title }}</label>
                                                    <input type="text" name="loan[]" value="{{ $loan->amount }}"
                                                        class="form-control">
                                                    <input type="hidden" name="loan_id[]"
                                                        value="{{ $loan->id }}" class="form-control">
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div id="deduction" class="tab-pane">
                            <div class="row">
                                <div class="col-lg-12">
                                    <div class="card bg-none mb-0">
                                        <div class="row px-3">
                                            @php
                                                $saturation_deductions = json_decode($payslip->saturation_deduction);
                                            @endphp
                                            @foreach ($saturation_deductions as $deduction)
                                                <div class="col-md-12 form-group">
                                                    <label for="title"
                                                        class="col-form-label">{{ $deduction->title }}</label>
                                                    <input type="text" name="saturation_deductions[]"
                                                        value="{{ $deduction->amount }}" class="form-control">
                                                    <input type="hidden" name="saturation_deductions_id[]"
                                                        value="{{ $deduction->id }}" class="form-control">
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div id="payment" class="tab-pane">
                            <div class="row">
                                <div class="col-lg-12">
                                    <div class="card bg-none mb-0">
                                        <div class="row px-3">
                                            @php
                                                $other_payments = json_decode($payslip->other_payment);
                                            @endphp
                                            @foreach ($other_payments as $payment)
                                                <div class="col-md-12 form-group">
                                                    <label for="title"
                                                        class="col-form-label">{{ $payment->title }}</label>
                                                    <input type="text" name="other_payment[]"
                                                        value="{{ $payment->amount }}" class="form-control">
                                                    <input type="hidden" name="other_payment_id[]"
                                                        value="{{ $payment->id }}" class="form-control">
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div id="overtime" class="tab-pane">
                            <div class="row">
                                <div class="col-lg-12">
                                    <div class="card bg-none mb-0">
                                        <div class="row px-3">
                                            @php
                                                $overtimes = json_decode($payslip->overtime);

                                            @endphp
                                            @foreach ($overtimes as $overtime)
                                                <div class="col-md-6 form-group">
                                                    <label for="rate"
                                                        class="col-form-label">{{ $overtime->title }} Rate</label>
                                                    <input type="text" name="rate[]"
                                                        value="{{ $overtime->rate }}" class="form-control">
                                                    <input type="hidden" name="rate_id[]"
                                                        value="{{ $overtime->id }}" class="form-control">
                                                </div>
                                                <div class="col-md-6 form-group">
                                                    <label for="hours"
                                                        class="col-form-label">{{ $overtime->title }} Hours</label>
                                                    <input type="text" name="hours[]"
                                                        value="{{ $overtime->hours }}" class="form-control">
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <input type="button" value="Cancel" class="btn btn-light" data-bs-dismiss="modal">
                    <input type="submit" value="{{ __('Update') }}" class="btn btn-primary">
                </div>
            </form>
        </div>
    </div>
</div>
