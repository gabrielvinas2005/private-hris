@extends('layouts.template')

@section('content')
    <!-- Content Header (Page header) -->
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Payroll Summary</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('home') }}" onclick="removeCokie();">Home</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('payroll_process') }}" onclick="removeCokie();">Payroll
                                Process</a></li>
                        <li class="breadcrumb-item active">Payroll Summary</li>
                    </ol>
                </div>
            </div>
        </div><!-- /.container-fluid -->
    </section>

    <!-- Main content -->
    <section class="content">
        @if ($errors->any())
            @foreach ($errors->all() as $error)
                <div class="alert alert-danger alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                    <h5><i class="icon fas fa-ban"></i> Error!</h5>
                    {{ $error }}
                </div>
            @endforeach
        @endif

        @if (session()->has('error'))
            <div class="alert alert-danger alert-dismissible">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                <h5><i class="icon fas fa-ban"></i> Error!</h5>
                {{ session()->get('error') }}
            </div>
        @endif

        @if (session()->has('success'))
            <div class="alert alert-success alert-dismissible">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                <h5><i class="icon fas fa-check"></i> Success!</h5>
                {{ session()->get('success') }}
            </div>
        @endif

        <!-- Default box -->
        <div class="card card-info">
            <div class="ml-4 mt-4">
                <div class="row p-3">
                    <form class="pr-3" action="{{ route('payroll_processing', $id) }}" role="form" method="post"
                        {{ $data[0]->posted == false ? '' : 'hidden' }}>
                        @csrf
                        <button type="submit" class="btn btn-info">Process Payroll</button>
                    </form>
                    @if ($data[0]->posted == false)
                        <form action="{{ route('payroll_posting', [$id, 1]) }}" role="form" method="post">
                            @csrf
                            <button type="submit" class="btn btn-warning">Post Payroll</button>
                        </form>
                    @else
                        <form action="{{ route('payroll_posting', [$id, 2]) }}" role="form" method="post">
                            @csrf
                            <button type="submit" style="background-color: #E55451;" class="btn">Un-post
                                Payroll</button>
                        </form>
                    @endif
                </div>
            </div>
            <div class="mt-2 ml-4 mr-4" {{ count($payrolls_less_netpay) == 0 ? 'hidden' : '' }}>
                <label><span style="color: #E55451;">WARNING:</span> There are employees with less than 5,000 pesos net pay
                    highlighted as red row.</label>
            </div>
            <div class="ml-4 mr-4" {{ count($payrolls_adjust) == 0 ? 'hidden' : '' }}>
                <label>There are employees that other deduction were adjusted to the next payroll. <a href=""
                        data-toggle="modal" data-target="#modal-employee-add"><u>Click here
                            to display details.</u></a></label>
            </div>
            <div class="ml-4 mr-4" {{ count($leave_earned_details) == 0 ? 'hidden' : '' }}>
                <label>Leave Earned Details for <a href="" data-toggle="modal"
                        data-target="#modal-employee-leave_earned"><u>{{ $payroll_period }}</u></a></label>
            </div>
            <div class="mb-4 ml-4 mr-4" {{ count($leave_earned_details) == 0 ? 'hidden' : '' }}>
                <label>Employee Tax Amount Manual Adjustments <a href="" data-toggle="modal"
                        data-target="#modal-employee-tax-amount"><u>{{ $payroll_period }}</u></a></label>
            </div>
            <div class="card-body">
                <div class="card">
                    <div class="card-body">
                        <h4>{{ $payroll_period }}</h4>
                        <table id="example4" class="table table-bordered table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Photo</th>
                                    <th>Name</th>
                                    <th>Department</th>
                                    <th>Position</th>
                                    <th>Salary</th>
                                    <th hidden>OT Pay</th>
                                    <th hidden>ND Pay</th>
                                    <th>Holiday Pay</th>
                                    <th>Other Income</th>
                                    <th>Gross Pay</th>
                                    <th>Late</th>
                                    <th>Undertime</th>
                                    <th>Absent</th>
                                    <th>LWOP</th>
                                    <th>GSIS</th>
                                    <th>SSS</th>
                                    <th>Pag-Ibig</th>
                                    <th>PhilHealth</th>
                                    <th>Tax</th>
                                    <th>Total Deductions</th>
                                    <th>Net Pay</th>
                                    <th>On Hold</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($payrolls as $payroll)
                                    @if ($payroll->net_pay < 5000 || $payroll->is_hold == 1)
                                        <tr style="background-color: #E55451;">
                                        @else
                                        <tr>
                                    @endif

                                    <td>
                                        <center>
                                            <img src="data:image/jpeg;base64,{{ $payroll->photo }}"
                                                onerror=this.src="../../dist/img/profile.png"
                                                class="img-circle elevation-2 mt-1" width="30px" height="30px"
                                                alt="User Image">
                                        </center>
                                    </td>
                                    <td>{{ $payroll->name }}</td>
                                    <td>{{ $payroll->department }}</td>
                                    <td>{{ $payroll->position }}</td>
                                    <td>{{ number_format($payroll->salary, 2, '.', ',') }}</td>
                                    <td hidden>{{ number_format($payroll->ot_pay, 2, '.', ',') }}</td>
                                    <td hidden>{{ number_format($payroll->nd_pay, 2, '.', ',') }}</td>
                                    <td>{{ number_format($payroll->holiday_pay, 2, '.', ',') }}</td>
                                    <td>
                                        @if ($payroll->is_hold == 1)
                                            0.00
                                        @else
                                            {{ number_format($payroll->total_income, 2, '.', ',') }}
                                        @endif
                                    </td>
                                    <td>
                                        @if ($payroll->is_hold == 1)
                                            0.00
                                        @else
                                            {{ number_format($payroll->gross_amount, 2, '.', ',') }}
                                        @endif
                                    </td>
                                    <td>{{ number_format($payroll->late_amount, 2, '.', ',') }}</td>
                                    <td>{{ number_format($payroll->ut_amount, 2, '.', ',') }}</td>
                                    <td>{{ number_format($payroll->absent_amount - $payroll->lwop_amount, 2, '.', ',') }}
                                    </td>
                                    <td>{{ number_format($payroll->lwop_amount, 2, '.', ',') }}</td>
                                    <td>{{ number_format($payroll->gsis, 2, '.', ',') }}</td>
                                    <td>{{ number_format($payroll->sss, 2, '.', ',') }}</td>
                                    <td>
                                        @if ($payroll->is_hold == 1)
                                            0.00
                                        @else
                                            {{ number_format($payroll->pagibig, 2, '.', ',') }}
                                        @endif
                                    </td>
                                    <td>{{ number_format($payroll->philhealth, 2, '.', ',') }}</td>
                                    <td>{{ number_format($payroll->tax, 2, '.', ',') }}</td>
                                    <td>{{ number_format($payroll->total_deduction + $payroll->pending_amount_payment, 2, '.', ',') }}
                                    </td>
                                    <td>{{ number_format($payroll->net_pay, 2, '.', ',') }}</td>
                                    <td>
                                        @if ($payroll->is_hold == 1)
                                            {{ $payroll->hold_remarks }}
                                        @else
                                            No
                                        @endif
                                    </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <!-- /.card-body -->
                </div>
                <!-- /.card -->
            </div>
            <div class="card-footer">
                <h6>List of all Payroll Summary sorted by Employee Name.</h6>
            </div>
        </div>
        <!-- /.card -->

        {{-- Modal Start --}}
        <div class="modal fade" id="modal-employee-add">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Employee List</h4>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <form action="#" role="form" method="post">
                        @csrf
                        <div class="modal-body">
                            <div class="card mt-4 p-3">
                                <h5>Deductions to be adjusted next payroll.</h5>
                                <div class="card-body table-responsive p-0 mt-3" style="height: 500px;">
                                    <table class="table table-head-fixed text-nowrap table-hover">
                                        <thead>
                                            <tr>
                                                <th>Employee</th>
                                                <th>Branch</th>
                                                <th>Department</th>
                                                <th>Position</th>
                                                <th>Deduction</th>
                                                <th>Amount</th>
                                            </tr>
                                        </thead>
                                        <tbody id="adjustEmployeeTable">
                                            @foreach ($payrolls_adjust as $dtl)
                                                <tr>
                                                    <td>{{ $dtl->name }}</td>
                                                    <td>{{ $dtl->branch }}</td>
                                                    <td>{{ $dtl->department }}</td>
                                                    <td>{{ $dtl->position }}</td>
                                                    <td>{{ $dtl->deduction }}</td>
                                                    <td>{{ number_format($dtl->amount, 2, '.', ',') }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer justify-content-between">
                            <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-primary" hidden>Adjust Payroll</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        {{-- Modal End --}}

        {{-- Modal Start --}}
        <div class="modal fade" id="modal-employee-leave_earned">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Employee List</h4>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="card mt-4 p-3">
                            <h5>Employee Leave Earned Details</h5>
                            <div class="card-body table-responsive p-0 mt-3" style="height: 500px;">
                                <table class="table table-head-fixed text-nowrap table-hover">
                                    <thead>
                                        <tr>
                                            <th>Employee No.</th>
                                            <th>Name</th>
                                            <th>Position</th>
                                            <th>Days</th>
                                            <th>Vacation Leave</th>
                                            <th>Sick Leave</th>
                                        </tr>
                                    </thead>
                                    <tbody id="EmployeeLeaveEarnedTable">
                                        @foreach ($leave_earned_details as $dtl)
                                            <tr>
                                                <td>{{ $dtl->employee_no }}</td>
                                                <td>{{ $dtl->name }}</td>
                                                <td>{{ $dtl->position }}</td>
                                                <td>{{ $dtl->days_present }}</td>
                                                <td>{{ number_format($dtl->vl_earned, 3, '.', ',') }}</td>
                                                <td>{{ number_format($dtl->sl_earned, 3, '.', ',') }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer justify-content-between">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
        {{-- Modal End --}}

        {{-- Modal Start --}}
        <div class="modal fade" id="modal-employee-tax-amount">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Employee List</h4>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form action="{{ route('tax_amount_adjustment', $id) }}" method="POST" role="form">
                            @csrf
                            <div class="card mt-4 p-3">
                                <h5>Employee Tax Amount Details</h5>
                                <div class="card-body table-responsive p-0 mt-3"
                                    style="min-height: 300px;max-height: 500px;">
                                    <table class="table table-head-fixed text-nowrap table-hover">
                                        <thead>
                                            <tr>
                                                <th hidden>Employee ID</th>
                                                <th>Name</th>
                                                <th>Tax Amount</th>
                                            </tr>
                                        </thead>
                                        <tbody id="EmployeeLeaveEarnedTable">
                                            @foreach ($employee_for_tax_adjustments as $dtl)
                                                <tr>
                                                    <td hidden>
                                                        <input name="employee_id[]" type="number"
                                                            value="{{ $dtl->employee_id }}">
                                                    </td>
                                                    <td>{{ $dtl->name }}</td>
                                                    <td>
                                                        <input name="tax_amount[]" type="number" step="0.01"
                                                            class="form-control" value="{{ $dtl->tax }}">
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="form-group" style="text-align: right">
                                <button type="submit" class="btn btn-primary">Save Adjusted Amount</button>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer justify-content-between">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
        {{-- Modal End --}}

    </section>
    <!-- /.content -->

    <!-- Set menu to collapse and active -->
@endsection

@section('scripts')
    <script>
        $(function() {
            $('#hrp').addClass('menu-open');
        });
        $(function() {
            $('#hrp_link').addClass('active');
        });
        $(function() {
            $('#payroll_process').addClass('active');
        });
    </script>
@endsection
