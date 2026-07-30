@extends('layouts.template')

@section('content')
    <!-- Content Header (Page header) -->
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Employee Payslip</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('home') }}" onclick="removeCokie();">Home</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('payslips', Auth::user()->id) }}">Payslip List</a></li>
                        <li class="breadcrumb-item active">Employee Payslip</li>
                    </ol>
                </div>
            </div>
        </div><!-- /.container-fluid -->
    </section>

    <!-- Main content -->
    <section class="content">
        <!-- Default box -->
        <div class="card card-info">
            <div class="card-header">
                <h3 class="card-title">Payslip Information</h3>
            </div>
            <!-- Payslip -->
            <div class="card-body col-12">
                <div class="row">
                    <div class="col-md-3 col-sm-6" style="height: 100%;">
                        <div class="card">
                            <div class="card-body card-info card-outline">
                                <div class="text-center empty-text">
                                    <img class="profile-user-img img-fluid img-circle"
                                         src="data:image/jpeg;base64,{{ $payrolls[0]->photo }}"
                                         onerror=this.src="../../dist/img/employee_profile.png" alt="User profile picture">
                                    <h4 class="p-0 mb-0 mt-2" style="color: #455A64;">{{ $payrolls[0]->name }}</h4>
                                    <p class="m-0 pb-0" style="color: steelblue;">{{ $payrolls[0]->employee_no }}</p>
                                </div>
                            </div>
                            <div style="height: 100%;">
                                <hr class="m-0 pb-2">
                                <div class="row pl-4">
                                    <div>
                                        <span style="color: #263238;">Employment Type:</span>
                                        <p style="color: steelblue;">{{ $payrolls[0]->employment_type }}</p>
                                    </div>

                                </div>
                                <div class="row pl-4">
                                    <div>
                                        <span style="color: #263238;">Department:</span>
                                        <p style="color: steelblue;">{{ $payrolls[0]->department }}</p>
                                    </div>
                                </div>
                                <div class="row pl-4">
                                    <div>
                                        <span style="color: #263238;">Position:</span>
                                        <p style="color: steelblue;">{{ $payrolls[0]->position }}</p>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                    <div class="col-md-9 col-sm-6">
                        <div class="card">
                            <div class="card-body card-info card-outline" style="padding: 20px 20px 0px 20px;">
                                <div class="text-left empty-text">
                                    <div>
                                        <label for="">Payroll Period :</label>
                                    </div>
                                    <div class="input-group mb-3">
                                        <h5>{{ $payroll_period }}</h5>
                                    </div>
                                </div>
                            </div>
                            <div style="height: 100%;">
                                <hr class="m-0 pb-2">
                                <div class="row" style="padding: 10px;">
                                    <div class="col-md-6" style="padding-right: 25px;">
                                        <div class="row">
                                            <div class="col-6">
                                                <span style="color: #263238;">Salary:</span>
                                            </div>
                                            <div class="col-6" style="text-align: right;">
                                                <span
                                                      style="color: steelblue;">{{ number_format($payrolls[0]->salary, 2, '.', ',') }}</span>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-6">
                                                <span style="color: #263238;">Overtime:</span>
                                            </div>
                                            <div class="col-6" style="text-align: right;">
                                                <span
                                                      style="color: steelblue;">{{ number_format($payrolls[0]->ot_pay, 2, '.', ',') }}</span>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-6">
                                                <span style="color: #263238;">Night Differential:</span>
                                            </div>
                                            <div class="col-6" style="text-align: right;">
                                                <span
                                                      style="color: steelblue;">{{ number_format($payrolls[0]->nd_pay, 2, '.', ',') }}</span>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-6">
                                                <span style="color: #263238;">Holiday:</span>
                                            </div>
                                            <div class="col-6" style="text-align: right;">
                                                <span
                                                      style="color: steelblue;">{{ number_format($payrolls[0]->holiday_pay, 2, '.', ',') }}</span>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-6">
                                                <span style="color: #263238;">Other Income:</span>
                                            </div>
                                            <div class="col-6" style="text-align: right;">
                                                <span
                                                      style="color: steelblue;">{{ number_format($payrolls[0]->total_income, 2, '.', ',') }}</span>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-6">
                                                <span style="color: #263238;">Late Amount:</span>
                                            </div>
                                            <div class="col-6" style="text-align: right;">
                                                <span
                                                      style="color: steelblue;">{{ number_format($payrolls[0]->late_amount, 2, '.', ',') }}</span>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-6">
                                                <span style="color: #263238;">Undertime:</span>
                                            </div>
                                            <div class="col-6" style="text-align: right;">
                                                <span
                                                      style="color: steelblue;">{{ number_format($payrolls[0]->ut_amount, 2, '.', ',') }}</span>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-6">
                                                <span style="color: #263238;">Absent:</span>
                                            </div>
                                            <div class="col-6" style="text-align: right;">
                                                <span
                                                      style="color: steelblue;">{{ number_format($payrolls[0]->absent_amount, 2, '.', ',') }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6" style="padding-left: 25px;">
                                        <div class="row">
                                            <div class="col-6">
                                                <span style="color: #263238;">GSIS:</span>
                                            </div>
                                            <div class="col-6" style="text-align: right;">
                                                <span
                                                      style="color: steelblue;">{{ number_format($payrolls[0]->gsis, 2, '.', ',') }}</span>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-6">
                                                <span style="color: #263238;">SSS:</span>
                                            </div>
                                            <div class="col-6" style="text-align: right;">
                                                <span
                                                      style="color: steelblue;">{{ number_format($payrolls[0]->sss, 2, '.', ',') }}</span>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-6">
                                                <span style="color: #263238;">Pag-ibig:</span>
                                            </div>
                                            <div class="col-6" style="text-align: right;">
                                                <span
                                                      style="color: steelblue;">{{ number_format($payrolls[0]->pagibig, 2, '.', ',') }}</span>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-6">
                                                <span style="color: #263238;">PhilHealth:</span>
                                            </div>
                                            <div class="col-6" style="text-align: right;">
                                                <span
                                                      style="color: steelblue;">{{ number_format($payrolls[0]->philhealth, 2, '.', ',') }}</span>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-6">
                                                <span style="color: #263238;">Tax:</span>
                                            </div>
                                            <div class="col-6" style="text-align: right;">
                                                <span
                                                      style="color: steelblue;">{{ number_format($payrolls[0]->tax, 2, '.', ',') }}</span>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-6">
                                                <span style="color: #263238;">Other Deduction:</span>
                                            </div>
                                            <div class="col-6" style="text-align: right;">
                                                <span
                                                      style="color: steelblue;">{{ number_format($payrolls[0]->total_deduction, 2, '.', ',') }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div style="height: 100%;">
                                <hr class="m-0 pb-2">
                                <div class="row" style="padding: 0px 10px 30px 10px;">
                                    <div class="col-md-6">

                                        <div class="row">
                                            <div class="col-6">
                                                <span style="color: #263238; font-weight:bold">Gross Pay:</span>
                                            </div>
                                            <div class="col-6" style="text-align: right;">
                                                <span
                                                      style="color: steelblue; font-weight:bold">{{ number_format($payrolls[0]->gross_amount, 2, '.', ',') }}</span>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-6">
                                                <span style="color: #263238; font-weight:bold">Net Pay:</span>
                                            </div>
                                            <div class="col-6" style="text-align: right;">
                                                <span
                                                      style="color: steelblue; font-weight:bold">{{ number_format($payrolls[0]->net_pay, 2, '.', ',') }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <h6>Display Daily Time data.</h6>
            </div>
        </div>
        <!-- /.card -->

    </section>
    <!-- /.content -->

    <!-- Set menu to collapse and active -->
@endsection
