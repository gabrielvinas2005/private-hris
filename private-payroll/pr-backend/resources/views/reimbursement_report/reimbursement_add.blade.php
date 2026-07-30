@extends('layouts.template')

@section('content')
    <!-- Content Header (Page header) -->
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Add Payroll Communication Macco</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('home') }}" onclick="removeCokie();">Home</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('reimbursement_report') }}">Payroll Communication Macco
                                List</a></li>
                        <li class="breadcrumb-item active">Add Payroll Communication Macco</li>
                    </ol>
                </div>
            </div>
        </div><!-- /.container-fluid -->
    </section>

    <!-- Main content -->
    <section class="content">
        @if (session()->has('error'))
            <div class="alert alert-danger alert-dismissible">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                <h5><i class="icon fas fa-ban"></i> Error!</h5>
                {{ session()->get('error') }}
            </div>
        @endif

        @if ($errors->any())
            @foreach ($errors->all() as $error)
                <div class="alert alert-danger alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                    <h5><i class="icon fas fa-ban"></i> Error!</h5>
                    {{ $error }}
                </div>
            @endforeach
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
                <div class="col-md-4">
                    <form class="pr-3" action="{{ route('reimbursement_add', $data[0]->id ?? 0) }}" role="form"
                        method="post">
                        @csrf
                        <div class="card">
                            <div class="card-body">
                                <input id="reimbursement_headers_id" type="number" value="{{ $data[0]->id ?? 0 }}" hidden>
                                <input id="rata_year_id" type="number" value="{{ $data[0]->year ?? 0 }}" hidden>
                                <div class="form-group">
                                    <label>Department</label>
                                    <select name="department_id" class="form-control select2">
                                        @foreach ($departments as $department)
                                            <option value="{{ $department->id }}"
                                                {{ $department->id == $data[0]->department_id ? 'selected' : '' }}>
                                                {{ $department->name ?? '' }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Month</label>
                                    <select id="month_id" name="month_id" class="form-control select2">
                                        <option value="1" {{ $data[0]->month_id == 1 ? 'selected' : '' }}>January
                                        </option>
                                        <option value="2" {{ $data[0]->month_id == 2 ? 'selected' : '' }}>February
                                        </option>
                                        <option value="3" {{ $data[0]->month_id == 3 ? 'selected' : '' }}>March
                                        </option>
                                        <option value="4" {{ $data[0]->month_id == 4 ? 'selected' : '' }}>April
                                        </option>
                                        <option value="5" {{ $data[0]->month_id == 5 ? 'selected' : '' }}>May</option>
                                        <option value="6" {{ $data[0]->month_id == 6 ? 'selected' : '' }}>June
                                        </option>
                                        <option value="7" {{ $data[0]->month_id == 7 ? 'selected' : '' }}>July
                                        </option>
                                        <option value="8" {{ $data[0]->month_id == 8 ? 'selected' : '' }}>August
                                        </option>
                                        <option value="9" {{ $data[0]->month_id == 9 ? 'selected' : '' }}>September
                                        </option>
                                        <option value="10" {{ $data[0]->month_id == 10 ? 'selected' : '' }}>October
                                        </option>
                                        <option value="11" {{ $data[0]->month_id == 11 ? 'selected' : '' }}>November
                                        </option>
                                        <option value="12" {{ $data[0]->month_id == 12 ? 'selected' : '' }}>December
                                        </option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Year</label>
                                    <select name="year" id="year" class="form-control select2">
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-info">Process Payroll Expenses</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card-body" {{ $data[0]->id == 0 ? 'hidden' : '' }}>
                <div class="card">
                    <div class="form-group mr-4 mt-4 mb-2">
                        <button type="button" class="btn btn-primary float-right" data-toggle="modal"
                            data-target="#modal-employee-add">Add Employees</button>
                    </div>

                    @php($type = $data[0]->posted == true ? 2 : 1)

                    <form action="{{ route('reimbursement_process', [$data[0]->id, $type]) }}" method="POST"
                        role="form">
                        @csrf
                        <div class="card-body">
                            <table id="example1" class="table table-bordered table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th hidden>ID</th>
                                        <th hidden>Employee ID</th>
                                        <th>Employee Name</th>
                                        <th>Position</th>
                                        <th>Prepaid Invoice No</th>
                                        <th>Prepaid Amount</th>
                                        <th>Postpaid Invoice No</th>
                                        <th>Postpaid Amount</th>
                                        <th>Remove</th>
                                    </tr>
                                </thead>
                                <tbody id="ReimbursementEmployeeTable">
                                    @foreach ($data as $datas)
                                        @if ($datas->reimbursement_dtl != 0)
                                            <tr>
                                                <td hidden>
                                                    <input name="id[]" type="number"
                                                        value="{{ $datas->reimbursement_dtl }}">
                                                </td>
                                                <td hidden>
                                                    <input name="employee_id[]" type="number"
                                                        value="{{ $datas->employee_id ?? '' }}">
                                                </td>

                                                <td>{{ $datas->full_name ?? '' }}</td>
                                                <td>{{ $datas->position ?? '' }}</td>

                                                <td>
                                                    <input name="prepaid_invoice_no[]" type="number" step="0.01"
                                                        class="form-control" value="{{ $datas->prepaid_invoice_no }}">
                                                </td>
                                                <td>
                                                    <input name="prepaid_amount[]" type="number" step="0.01"
                                                        class="form-control" value="{{ $datas->prepaid_amount }}">
                                                </td>
                                                <td>
                                                    <input name="postpaid_invoice_no[]" type="number" step="0.01"
                                                        class="form-control" value="{{ $datas->postpaid_invoice_no }}">
                                                </td>
                                                <td>
                                                    <input name="postpaid_amount[]" type="number" step="0.01"
                                                        class="form-control" value="{{ $datas->postpaid_amount }}">
                                                </td>

                                                <td>
                                                    @if ($data[0]->posted != 1)
                                                        <center>
                                                            <a href="#"
                                                                class="btn btn-danger btnRemoveEmployee">Remove</a>
                                                        </center>
                                                    @else
                                                        <center>
                                                            <a href="#" class="btn btn-default">Remove</a>
                                                        </center>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endif
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="m-4">
                            @if ($data[0]->id > 0)
                                @if ($data[0]->posted == false)
                                    <button id="formSubmitApproved" type="submit" class="btn btn-warning">
                                        Approve Reimbursement Expenses
                                    </button>
                                @else
                                    <button id="formSubmitDisapproved" type="submit" class="btn btn-danger">
                                        Disapprove Reimbursement Expenses
                                    </button>
                                @endif
                            @endif
                        </div>
                    </form>
                </div>
            </div>
            <div class="card-footer">
                <h6>List of all Reimbursement Expenses Summary sorted by Employee Name.</h6>
            </div>
        </div>
        <!-- /.card -->
        </form>
        <div class="card-footer">
            <h6>Payroll Communication Macco</h6>
        </div>
        </div>

        <!-- /.card -->
        {{-- Modal Start --}}
        <div class="modal fade" id="modal-employee-add">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Employee List</h4>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <form action="{{ route('reimbursement_employee_add', $data[0]->id) }}" role="form"
                        method="post">
                        @csrf
                        <div class="modal-body">
                            <div class="card mt-4 p-3">
                                <h5>Select Employees</h5>
                                <div class="form-group">
                                    <label>Filter</label>
                                    <div class="col-lg-12" style="text-align: right;">
                                        <input id="myInput" type="text" class="form-control"
                                            placeholder="Search..">
                                    </div>
                                    <div class="custom-control custom-checkbox mt-4 ml-4">
                                        <input name="selectAll" class="custom-control-input" type="checkbox"
                                            id="selectAll">
                                        <label for="selectAll" class="custom-control-label">Select All Employees</label>
                                    </div>
                                </div>
                                <div class="card-body table-responsive p-0 mt-3" style="height: 500px;">
                                    <table class="table table-head-fixed text-nowrap table-hover">
                                        <thead>
                                            <tr>
                                                <th hidden>ID</th>
                                                <th>Select</th>
                                                <th>Employee No</th>
                                                <th>Name</th>
                                                <th>Position</th>
                                            </tr>
                                        </thead>
                                        <tbody id="tableEmployee">
                                            @foreach ($employees as $emp)
                                                <tr>
                                                    <td hidden>
                                                        <input name="id[]" type="text"
                                                            value="{{ $emp->id }}">
                                                    </td>
                                                    <td>
                                                        <center>
                                                            <input class="empSelect" name="select[]" type="checkbox"
                                                                value="{{ $emp->id ?? '' }}">
                                                        </center>
                                                    </td>
                                                    <td>
                                                        {{ $emp->employee_no ?? '' }}
                                                    </td>
                                                    <td>
                                                        {{ $emp->full_name ?? '' }}
                                                    </td>
                                                    <td>
                                                        {{ $emp->position ?? '' }}
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer justify-content-between">
                            <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-primary">Add</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

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
            $('#hrp_benefits_hdr').addClass('menu-open');
        });
        $(function() {
            $('#hrp_benefits').addClass('active');
        });

        $(function() {
            $('#reimbursement_report').addClass('active');
        });
    </script>
    <script>
        $("#year").each(function() {
            var year = new Date().getFullYear();
            var rata_year = $("#rata_year_id").val();
            var current = year;

            if (rata_year == 0) {
                current = year;
            } else {
                current = rata_year;
            }

            year -= 3;

            for (var i = 0; i < 8; i++) {
                if (year + i == current)
                    $(this).append(
                        '<option selected value="' +
                        (year + i) +
                        '">' +
                        (year + i) +
                        "</option>"
                    );
                else
                    $(this).append(
                        '<option value="' +
                        (year + i) +
                        '">' +
                        (year + i) +
                        "</option>"
                    );
            }
        });
    </script>
    <script>
        $(document).ready(function() {
            $("#myInput").on("keyup", function() {
                var value = $(this).val().toLowerCase();
                $("#tableEmployee tr").filter(function() {
                    $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
                });
            });
        });
    </script>
    <script>
        $('#selectAll').click(function() {

            var checked = $(this).prop('checked');

            if (checked) {
                $('.empSelect').prop('checked', true);
            } else {
                $('.empSelect').prop('checked', false);
            }
        });
    </script>

    <!-- Loyalty Award scripts -->
    <script src="{{ asset('build/js/ReimbursementExpenses.js') }}"></script>
@endsection
