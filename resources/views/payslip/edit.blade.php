@extends('layouts.admin')
@section('content')
    <div class="main-content">
        <section class="section">
            <div class="section-header">
                <h1>{{ __('Employee Salary Pay Slip') }}</h1>
                <div class="section-header-breadcrumb">
                    <div class="breadcrumb-item active"><a href="#">Home</a></div>
                    <div class="breadcrumb-item">{{ __('Employee Salary Pay Slip') }}</div>
                </div>
            </div>
            @csrf
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <div class="d-flex justify-content-between w-100">
                                <h4>{{ __('Employee Salary Pay Slip') }}</h4>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="setting-tab">
                                <ul class="nav nav-pills mb-3" id="myTab3" role="tablist">
                                    <li class="nav-item">
                                        <a class="nav-link active" id="home-tab3" data-toggle="tab" href="#salary"
                                            role="tab" aria-controls="" aria-selected="true">{{ __('Salary') }}</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" id="profile-tab3" data-toggle="tab" href="#allowance"
                                            role="tab" aria-controls="" aria-selected="false">{{ __('Allowance') }}</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" id="contact-tab3" data-toggle="tab" href="#commission"
                                            role="tab" aria-controls="" aria-selected="false">{{ __('Commission') }}</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" id="contact-tab4" data-toggle="tab" href="#loan"
                                            role="tab" aria-controls="" aria-selected="false">{{ __('Loan') }}</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" id="contact-tab4" data-toggle="tab" href="#saturation-deduction"
                                            role="tab" aria-controls=""
                                            aria-selected="false">{{ __('Saturation Deduction') }}</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" id="contact-tab4" data-toggle="tab" href="#other-payment"
                                            role="tab" aria-controls=""
                                            aria-selected="false">{{ __('Other Payment') }}</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" id="contact-tab4" data-toggle="tab" href="#overtime"
                                            role="tab" aria-controls="" aria-selected="false">{{ __('Overtime') }}</a>
                                    </li>
                                </ul>
                                <div class="tab-content" id="myTabContent2">
                                    <div class="tab-pane fade show active" id="salary" role="tabpanel"
                                        aria-labelledby="salary-tab3">
                                        <div class="company-setting-wrap">
                                            <form action="{{ route('employee.update', $employee->id) }}" method="POST"
                                                enctype="multipart/form-data">
                                                @csrf
                                                @method('PUT')
                                                <div class="row">
                                                    <div class="col-12 col-md-6">
                                                        <div class="form-group">
                                                            <label for="salary_type">{{ __('Payslip Type*') }}</label>
                                                            <select name="salary_type" id="salary_type" class="form-control"
                                                                required>
                                                                @foreach ($payslip_type as $type)
                                                                    <option value="{{ $type }}">
                                                                        {{ $type }}</option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="col-12 col-md-6">
                                                        <div class="form-group">
                                                            <label for="salary">{{ __('Salary') }}</label>
                                                            <input type="number" id="salary" name="salary"
                                                                class="form-control" required>
                                                        </div>
                                                    </div>
                                                </div>


                                                <div class="row">
                                                    <div class="col-12 text-end mt-1">
                                                        <button type="submit" class="btn btn-primary">
                                                            <i class="ti ti-plus"></i> {{ __('Save Change') }}
                                                        </button>
                                                    </div>
                                                </div>

                                            </form>

                                        </div>

                                    </div>
                                    <div class="tab-pane fade" id="allowance" role="tabpanel"
                                        aria-labelledby="allowance-tab3">
                                        <div class="company-setting-wrap">
                                            <form action="{{ url('allowance') }}" method="post">
                                                @csrf
                                                <input type="hidden" name="employee_id" value="{{ $employee->id }}">
                                                <div class="row">
                                                    <div class="col-12 col-md-6">
                                                        <div class="form-group">
                                                            <label
                                                                for="allowance_option">{{ __('Allowance Options*') }}</label>
                                                            <select name="allowance_option" id="allowance_option"
                                                                class="form-control" required>
                                                                @foreach ($allowance_options as $key => $option)
                                                                    <option value="{{ $key }}">
                                                                        {{ $option }}</option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="col-12 col-md-6">
                                                        <div class="form-group">
                                                            <label for="title">{{ __('Title') }}</label>
                                                            <input type="text" id="title" name="title"
                                                                class="form-control" required>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="row">
                                                    <div class="col-12 col-md-6">
                                                        <div class="form-group">
                                                            <label for="amount">{{ __('Amount') }}</label>
                                                            <input type="number" id="amount" name="amount"
                                                                class="form-control" required>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="row">
                                                    <div class="col-12 text-end mt-1">
                                                        <button type="submit" class="btn btn-primary">
                                                            <i class="ti ti-plus"></i> Save Change
                                                        </button>
                                                    </div>
                                                </div>
                                            </form>

                                            <hr>
                                            <div class="table-responsive">
                                                <table class="table table-striped mb-0" id="allowance-dataTable">
                                                    <thead>
                                                        <tr>
                                                            <th>{{ __('Employee Name') }}</th>
                                                            <th>{{ __('Allownace Option') }}</th>
                                                            <th>{{ __('Title') }}</th>
                                                            <th>{{ __('Amount') }}</th>
                                                            <th class="text-end" width="200px">{{ __('Action') }}
                                                            </th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach ($allowances as $allowance)
                                                            <tr>
                                                                <td>{{ $allowance->employee()->name }}</td>
                                                                <td>{{ $allowance->allowance_option()->name }}</td>
                                                                <td>{{ $allowance->title }}</td>
                                                                <td>{{ $allowance->amount }}</td>
                                                                <td class="text-end">
                                                                    @can('edit allowance')
                                                                        <a href="#"
                                                                            data-url="{{ URL::to('allowance/' . $allowance->id . '/edit') }}"
                                                                            data-size="lg" data-ajax-popup="true"
                                                                            data-title="{{ __('Edit Allowance') }}"
                                                                            class="btn btn-outline-primary btn-sm mr-1"
                                                                            data-toggle="tooltip"
                                                                            data-original-title="{{ __('Edit') }}"><i
                                                                                class="ti ti-pencil text-white"></i>
                                                                            <span>{{ __('Edit') }}</span></a>
                                                                    @endcan
                                                                    @can('delete allowance')
                                                                        <a href="#"
                                                                            class="btn btn-outline-danger btn-sm"
                                                                            data-toggle="tooltip"
                                                                            data-original-title="{{ __('Delete') }}"
                                                                            data-confirm="{{ __('Are You Sure?') . '|' . __('This action can not be undone. Do you want to continue?') }}"
                                                                            data-confirm-yes="document.getElementById('delete-form-{{ $allowance->id }}').submit();"><i
                                                                                class="ti ti-trash"></i>
                                                                            <span>{{ __('Delete') }}</span></a>
                                                                        <form method="POST"
                                                                            action="{{ route('allowance.destroy', $allowance->id) }}"
                                                                            id="delete-form-{{ $allowance->id }}">
                                                                            @csrf
                                                                            @method('DELETE')
                                                                        </form>
                                                                    @endcan
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="tab-pane fade" id="commission" role="tabpanel"
                                        aria-labelledby="commission-tab3">
                                        <div class="email-setting-wrap">
                                            <form method="POST" action="{{ url('commission') }}">
                                                @csrf
                                                <input type="hidden" name="employee_id" value="{{ $employee->id }}">
                                                <div class="row">
                                                    <div class="col-12 col-md-6">
                                                        <div class="form-group">
                                                            <label for="title">{{ __('Title') }}</label>
                                                            <input type="text" id="title" name="title"
                                                                class="form-control" required>
                                                        </div>
                                                    </div>
                                                    <div class="col-12 col-md-6">
                                                        <div class="form-group">
                                                            <label for="amount">{{ __('Amount') }}</label>
                                                            <input type="number" id="amount" name="amount"
                                                                class="form-control" required>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="row">
                                                    <div class="col-12 text-end mt-1">
                                                        <button type="submit" class="btn btn-primary">
                                                            <i class="ti ti-plus"></i> {{ __('Save Change') }}
                                                        </button>
                                                    </div>
                                                </div>

                                            </form>

                                            <hr>
                                            <div class="table-responsive">
                                                <table class="table table-striped mb-0" id="commission-dataTable">
                                                    <thead>
                                                        <tr>
                                                            <th>{{ __('Employee Name') }}</th>
                                                            <th>{{ __('Title') }}</th>
                                                            <th>{{ __('Amount') }}</th>
                                                            <th class="text-end" width="200px">{{ __('Action') }}
                                                            </th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach ($commissions as $commission)
                                                            <tr>
                                                                <td>{{ $commission->employee()->name }}</td>
                                                                <td>{{ $commission->title }}</td>
                                                                <td>{{ $commission->amount }}</td>
                                                                <td class="text-end">
                                                                    @can('edit allowance')
                                                                        <a href="#"
                                                                            data-url="{{ URL::to('commission/' . $commission->id . '/edit') }}"
                                                                            data-size="lg" data-ajax-popup="true"
                                                                            data-title="{{ __('Edit Allowance') }}"
                                                                            class="btn btn-outline-primary btn-sm mr-1"
                                                                            data-toggle="tooltip"
                                                                            data-original-title="{{ __('Edit') }}"><i
                                                                                class="ti ti-pencil text-white"></i>
                                                                            <span>{{ __('Edit') }}</span></a>
                                                                    @endcan
                                                                    @can('delete allowance')
                                                                        <a href="#"
                                                                            class="btn btn-outline-danger btn-sm"
                                                                            data-toggle="tooltip"
                                                                            data-original-title="{{ __('Delete') }}"
                                                                            data-confirm="{{ __('Are You Sure?') . '|' . __('This action can not be undone. Do you want to continue?') }}"
                                                                            data-confirm-yes="document.getElementById('delete-form-{{ $commission->id }}').submit();"><i
                                                                                class="ti ti-trash"></i>
                                                                            <span>{{ __('Delete') }}</span></a>
                                                                        <form method="POST"
                                                                            action="{{ route('commission.destroy', $commission->id) }}"
                                                                            id="delete-form-{{ $commission->id }}">
                                                                            @csrf
                                                                            @method('DELETE')
                                                                        </form>
                                                                    @endcan
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="tab-pane fade" id="loan" role="tabpanel"
                                        aria-labelledby="loan-tab4">
                                        <div class="email-setting-wrap">
                                            <form method="POST" action="{{ url('loan') }}">
                                                @csrf
                                                <input type="hidden" name="employee_id" value="{{ $employee->id }}">
                                                <div class="row">
                                                    <div class="col-12 col-md-4">
                                                        <div class="form-group">
                                                            <label for="loan_option">Loan Options*</label>
                                                            <select name="loan_option" id="loan_option"
                                                                class="form-control" required>
                                                                @foreach ($loan_options as $option)
                                                                    <option value="{{ $option->value }}"
                                                                        {{ $option->selected ? 'selected' : '' }}>
                                                                        {{ $option->label }}</option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="col-12 col-md-4">
                                                        <div class="form-group">
                                                            <label for="title">Title</label>
                                                            <input type="text" id="title" name="title"
                                                                class="form-control" required>
                                                        </div>
                                                    </div>
                                                    <div class="col-12 col-md-4">
                                                        <div class="form-group">
                                                            <label for="amount">Loan Amount</label>
                                                            <input type="number" id="amount" name="amount"
                                                                class="form-control" required>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="row">
                                                    <div class="col-12 col-md-6">
                                                        <div class="form-group">
                                                            <label for="start_date">Start Date</label>
                                                            <input type="text" id="start_date" name="start_date"
                                                                class="form-control datepicker" required>
                                                        </div>
                                                    </div>
                                                    <div class="col-12 col-md-6">
                                                        <div class="form-group">
                                                            <label for="end_date">End Date</label>
                                                            <input type="text" id="end_date" name="end_date"
                                                                class="form-control datepicker" required>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="row">
                                                    <div class="col-12 col-md-12">
                                                        <div class="form-group">
                                                            <label for="reason">Reason</label>
                                                            <textarea id="reason" name="reason" class="form-control" required></textarea>
                                                        </div>
                                                    </div>
                                                </div>


                                                <div class="row">
                                                    <div class="col-12 text-end mt-1">
                                                        <button type="submit" class="btn btn-primary">
                                                            <i class="ti ti-plus"></i> Save Change
                                                        </button>

                                                    </div>
                                                </div>
                                            </form>

                                            <hr>
                                            <div class="table-responsive">
                                                <table class="table table-striped mb-0" id="loan-dataTable">
                                                    <thead>
                                                        <tr>
                                                            <th>{{ __('employee') }}</th>
                                                            <th>{{ __('Loan Options') }}</th>
                                                            <th>{{ __('Title') }}</th>
                                                            <th>{{ __('Loan Amount') }}</th>
                                                            <th>{{ __('Start Date') }}</th>
                                                            <th>{{ __('End Date') }}</th>
                                                            <th class="text-end" width="200px">{{ __('Action') }}
                                                            </th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach ($loans as $loan)
                                                            <tr>
                                                                <td>{{ $loan->employee()->name }}</td>
                                                                <td>{{ $loan->loan_option()->name }}</td>
                                                                <td>{{ $loan->title }}</td>
                                                                <td>{{ $loan->amount }}</td>
                                                                <td>{{ $loan->start_date }}</td>
                                                                <td>{{ $loan->end_date }}</td>
                                                                <td class="text-end">
                                                                    @can('edit loan')
                                                                        <a href="#"
                                                                            data-url="{{ URL::to('loan/' . $loan->id . '/edit') }}"
                                                                            data-size="lg" data-ajax-popup="true"
                                                                            data-title="{{ __('Edit Allowance') }}"
                                                                            class="btn btn-outline-primary btn-sm mr-1"
                                                                            data-toggle="tooltip"
                                                                            data-original-title="{{ __('Edit') }}"><i
                                                                                class="ti ti-pencil text-white"></i>
                                                                            <span>{{ __('Edit') }}</span></a>
                                                                    @endcan
                                                                    @can('delete loan')
                                                                        <a href="#"
                                                                            class="btn btn-outline-danger btn-sm"
                                                                            data-toggle="tooltip"
                                                                            data-original-title="{{ __('Delete') }}"
                                                                            data-confirm="{{ __('Are You Sure?') . '|' . __('This action can not be undone. Do you want to continue?') }}"
                                                                            data-confirm-yes="document.getElementById('delete-form-{{ $loan->id }}').submit();"><i
                                                                                class="ti ti-trash"></i>
                                                                            <span>{{ __('Delete') }}</span></a>
                                                                        <form method="POST"
                                                                            action="{{ route('loan.destroy', $loan->id) }}"
                                                                            id="delete-form-{{ $loan->id }}">
                                                                            @csrf
                                                                            @method('DELETE')
                                                                        </form>
                                                                    @endcan
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="tab-pane fade" id="saturation-deduction" role="tabpanel"
                                        aria-labelledby="saturation-deduction-tab3">
                                        <div class="email-setting-wrap">
                                            <form method="POST" action="{{ url('saturationdeduction') }}">
                                                @csrf
                                                <input type="hidden" name="employee_id" value="{{ $employee->id }}">
                                                <div class="row">
                                                    <div class="col-12 col-md-6">
                                                        <div class="form-group">
                                                            <label for="deduction_option">Deduction Options*</label>
                                                            <select name="deduction_option" id="deduction_option"
                                                                class="form-control" required>
                                                                <!-- Option values will be filled dynamically based on your $deduction_options variable -->
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="col-12 col-md-6">
                                                        <div class="form-group">
                                                            <label for="title">Title</label>
                                                            <input type="text" name="title" id="title"
                                                                class="form-control" required>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="row">
                                                    <div class="col-12 col-md-6">
                                                        <div class="form-group">
                                                            <label for="amount">Amount</label>
                                                            <input type="number" name="amount" id="amount"
                                                                class="form-control" required>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="row">
                                                    <div class="col-12 text-end mt-1">
                                                        <button type="submit" class="btn btn-primary">
                                                            <i class="ti ti-plus"></i> Save Change
                                                        </button>
                                                    </div>
                                                </div>
                                            </form>

                                            <hr>
                                            <div class="table-responsive">
                                                <table class="table table-striped mb-0"
                                                    id="saturation-deduction-dataTable">
                                                    <thead>
                                                        <tr>
                                                            <th>{{ __('Employee Name') }}</th>
                                                            <th>{{ __('Deduction Option') }}</th>
                                                            <th>{{ __('Title') }}</th>
                                                            <th>{{ __('Amount') }}</th>
                                                            <th class="text-end" width="200px">{{ __('Action') }}
                                                            </th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach ($saturationdeductions as $saturationdeduction)
                                                            <tr>

                                                                <td>{{ $saturationdeduction->employee()->name }}</td>
                                                                <td>{{ $saturationdeduction->deduction_option()->name }}
                                                                </td>
                                                                <td>{{ $saturationdeduction->title }}</td>
                                                                <td>{{ $saturationdeduction->amount }}</td>
                                                                <td class="text-end">

                                                                    @can('edit saturation deduction')
                                                                        <a href="#"
                                                                            data-url="{{ URL::to('saturationdeduction/' . $saturationdeduction->id . '/edit') }}"
                                                                            data-size="lg" data-ajax-popup="true"
                                                                            data-title="{{ __('Edit Allowance') }}"
                                                                            class="btn btn-outline-primary btn-sm mr-1"
                                                                            data-toggle="tooltip"
                                                                            data-original-title="{{ __('Edit') }}"><i
                                                                                class="ti ti-pencil text-white"></i>
                                                                            <span>{{ __('Edit') }}</span></a>
                                                                    @endcan
                                                                    @can('delete saturation deduction')
                                                                        <a href="#"
                                                                            class="btn btn-outline-danger btn-sm"
                                                                            data-toggle="tooltip"
                                                                            data-original-title="{{ __('Delete') }}"
                                                                            data-confirm="{{ __('Are You Sure?') . '|' . __('This action can not be undone. Do you want to continue?') }}"
                                                                            data-confirm-yes="document.getElementById('delete-form-{{ $saturationdeduction->id }}').submit();"><i
                                                                                class="ti ti-trash"></i>
                                                                            <span>{{ __('Delete') }}</span></a>
                                                                        <form method="POST"
                                                                            action="{{ route('saturationdeduction.destroy', $saturationdeduction->id) }}"
                                                                            id="delete-form-{{ $saturationdeduction->id }}">
                                                                            @csrf
                                                                            @method('DELETE')
                                                                        </form>
                                                                    @endcan
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="tab-pane fade" id="other-payment" role="tabpanel"
                                        aria-labelledby="other-payment-tab4">
                                        <div class="email-setting-wrap">
                                            <form method="POST" action="{{ url('otherpayment') }}">
                                                @csrf
                                                <input type="hidden" name="employee_id" value="{{ $employee->id }}">
                                                <div class="row">
                                                    <div class="col-12 col-md-6">
                                                        <div class="form-group">
                                                            <label for="title">{{ __('Title') }}</label>
                                                            <input type="text" name="title" id="title"
                                                                class="form-control" required>
                                                        </div>
                                                    </div>
                                                    <div class="col-12 col-md-6">
                                                        <div class="form-group">
                                                            <label for="amount">{{ __('Amount') }}</label>
                                                            <input type="number" name="amount" id="amount"
                                                                class="form-control" required>
                                                        </div>
                                                    </div>
                                                </div>


                                                <div class="row">
                                                    <div class="col-12 text-end mt-1">
                                                        <button type="submit" class="btn btn-primary">
                                                            <i class="ti ti-plus"></i> {{ __('Save Change') }}
                                                        </button>
                                                    </div>
                                                </div>
                                            </form>

                                            <hr>
                                            <div class="table-responsive">
                                                <table class="table table-striped mb-0" id="other-payment-dataTable">
                                                    <thead>
                                                        <tr>
                                                            <th>{{ __('employee') }}</th>
                                                            <th>{{ __('Title') }}</th>
                                                            <th>{{ __('Amount') }}</th>
                                                            <th class="text-end" width="200px">{{ __('Action') }}
                                                            </th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach ($otherpayments as $otherpayment)
                                                            <tr>
                                                                <td>{{ $otherpayment->employee()->name }}</td>
                                                                <td>{{ $otherpayment->title }}</td>
                                                                <td>{{ $otherpayment->amount }}</td>
                                                                <td class="text-end">
                                                                    @can('edit other payment')
                                                                        <a href="#"
                                                                            data-url="{{ URL::to('otherpayment/' . $otherpayment->id . '/edit') }}"
                                                                            data-size="lg" data-ajax-popup="true"
                                                                            data-title="{{ __('Edit Allowance') }}"
                                                                            class="btn btn-outline-primary btn-sm mr-1"
                                                                            data-toggle="tooltip"
                                                                            data-original-title="{{ __('Edit') }}"><i
                                                                                class="ti ti-pencil text-white"></i>
                                                                            <span>{{ __('Edit') }}</span></a>
                                                                    @endcan
                                                                    @can('delete other payment')
                                                                        <a href="#"
                                                                            class="btn btn-outline-danger btn-sm"
                                                                            data-toggle="tooltip"
                                                                            data-original-title="{{ __('Delete') }}"
                                                                            data-confirm="{{ __('Are You Sure?') . '|' . __('This action can not be undone. Do you want to continue?') }}"
                                                                            data-confirm-yes="document.getElementById('delete-form-{{ $otherpayment->id }}').submit();"><i
                                                                                class="ti ti-trash"></i>
                                                                            <span>{{ __('Delete') }}</span></a>
                                                                        <form method="POST"
                                                                            action="{{ route('otherpayment.destroy', $otherpayment->id) }}"
                                                                            id="delete-form-{{ $otherpayment->id }}">
                                                                            @csrf
                                                                            @method('DELETE')
                                                                        </form>
                                                                    @endcan
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="tab-pane fade" id="overtime" role="tabpanel"
                                        aria-labelledby="overtime-tab4">
                                        <div class="email-setting-wrap">
                                            <form method="POST" action="{{ route('overtime.store') }}">
                                                @csrf
                                                <input type="hidden" name="employee_id" value="{{ $employee->id }}">
                                                <div class="row">
                                                    <div class="col-12 col-md-6">
                                                        <div class="form-group">
                                                            <label for="title">Overtime Title*</label>
                                                            <input type="text" name="title" id="title"
                                                                class="form-control" required>
                                                        </div>
                                                    </div>
                                                    <div class="col-12 col-md-6">
                                                        <div class="form-group">
                                                            <label for="number_of_days">Number of days</label>
                                                            <input type="number" name="number_of_days"
                                                                id="number_of_days" class="form-control" required>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-12 col-md-6">
                                                        <div class="form-group">
                                                            <label for="hours">Hours</label>
                                                            <input type="number" name="hours" id="hours"
                                                                class="form-control" required>
                                                        </div>
                                                    </div>
                                                    <div class="col-12 col-md-6">
                                                        <div class="form-group">
                                                            <label for="rate">Rate</label>
                                                            <input type="number" name="rate" id="rate"
                                                                class="form-control" required>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="row">
                                                    <div class="col-12 text-end mt-1">
                                                        <button type="submit" class="btn btn-primary">
                                                            <i class="ti ti-plus"></i> Save Change
                                                        </button>
                                                    </div>
                                                </div>
                                            </form>


                                            <hr>
                                            <div class="table-responsive">
                                                <table class="table table-striped mb-0" id="overtime-dataTable">
                                                    <thead>
                                                        <tr>
                                                            <th>{{ __('Employee Name') }}</th>
                                                            <th>{{ __('Overtime Title') }}</th>
                                                            <th>{{ __('Number of days') }}</th>
                                                            <th>{{ __('Hours') }}</th>
                                                            <th>{{ __('Rate') }}</th>

                                                            <th class="text-end" width="200px">{{ __('Action') }}
                                                            </th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach ($overtimes as $overtime)
                                                            <tr>
                                                                <td>{{ $overtime->employee()->name }}</td>
                                                                <td>{{ $overtime->title }}</td>
                                                                <td>{{ $overtime->number_of_days }}</td>
                                                                <td>{{ $overtime->hours }}</td>
                                                                <td>{{ $overtime->rate }}</td>

                                                                <td class="text-end">
                                                                    @can('adit allowance')
                                                                        <a href="#"
                                                                            data-url="{{ URL::to('overtime/' . $overtime->id . '/edit') }}"
                                                                            data-size="lg" data-ajax-popup="true"
                                                                            data-title="{{ __('Edit Allowance') }}"
                                                                            class="btn btn-outline-primary btn-sm mr-1"
                                                                            data-toggle="tooltip"
                                                                            data-original-title="{{ __('Edit') }}"><i
                                                                                class="ti ti-pencil text-white"></i>
                                                                            <span>{{ __('Edit') }}</span></a>
                                                                    @endcan
                                                                    @can('delete allowance')
                                                                        <a href="#"
                                                                            class="btn btn-outline-danger btn-sm"
                                                                            data-toggle="tooltip"
                                                                            data-original-title="{{ __('Delete') }}"
                                                                            data-confirm="{{ __('Are You Sure?') . '|' . __('This action can not be undone. Do you want to continue?') }}"
                                                                            data-confirm-yes="document.getElementById('delete-form-{{ $overtime->id }}').submit();"><i
                                                                                class="ti ti-trash"></i>
                                                                            <span>{{ __('Delete') }}</span></a>
                                                                        <form method="POST"
                                                                            action="{{ route('overtime.destroy', $overtime->id) }}"
                                                                            id="delete-form-{{ $overtime->id }}">
                                                                            @csrf
                                                                            @method('DELETE')
                                                                        </form>
                                                                    @endcan
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </section>
    </div>
@endsection

@push('script-page')
    <script type="text/javascript">
        $(document).ready(function() {
            var d_id = $('#department_id').val();
            var designation_id = '{{ $employee->designation_id }}';
            getDesignation(d_id);


            $("#allowance-dataTable").dataTable({
                "columnDefs": [{
                    "sortable": false,
                    "targets": [1]
                }]
            });

            $("#commission-dataTable").dataTable({
                "columnDefs": [{
                    "sortable": false,
                    "targets": [1]
                }]
            });

            $("#loan-dataTable").dataTable({
                "columnDefs": [{
                    "sortable": false,
                    "targets": [1]
                }]
            });

            $("#saturation-deduction-dataTable").dataTable({
                "columnDefs": [{
                    "sortable": false,
                    "targets": [1]
                }]
            });

            $("#other-payment-dataTable").dataTable({
                "columnDefs": [{
                    "sortable": false,
                    "targets": [1]
                }]
            });

            $("#overtime-dataTable").dataTable({
                "columnDefs": [{
                    "sortable": false,
                    "targets": [1]
                }]
            });


        });

        $(document).on('change', 'select[name=department_id]', function() {
            var department_id = $(this).val();
            getDesignation(department_id);
        });

        function getDesignation(did) {
            $.ajax({
                url: '{{ route('employee.json') }}',
                type: 'POST',
                data: {
                    "department_id": did,
                    "_token": "{{ csrf_token() }}",
                },
                success: function(data) {
                    $('#designation_id').empty();
                    $('#designation_id').append('<option value="">Select any Designation</option>');
                    $.each(data, function(key, value) {
                        var select = '';
                        if (key == '{{ $employee->designation_id }}') {
                            select = 'selected';
                        }

                        $('#designation_id').append('<option value="' + key + '"  ' + select + '>' +
                            value + '</option>');
                    });
                }
            });
        }
    </script>
@endpush
