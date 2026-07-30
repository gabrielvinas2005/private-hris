@extends('layouts.template')

@section('content')
<!-- Content Header (Page header) -->
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>Employee Certificate of Compensations</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}" onclick="removeCokie();">Home</a></li>
                    <li class="breadcrumb-item active">Employee Certificate of Compensations</li>
                </ol>
            </div>
        </div>
    </div><!-- /.container-fluid -->
</section>

<!-- Main content -->
<section class="content">

    @if (session()->has('error'))
    <div class="alert alert-warning alert-dismissible">
        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
        <h5><i class="icon fas fa-check"></i> Warning!</h5>
        {{ session()->get('error') }}
    </div>
    @endif

    <button id="employee" class="btn btn-primary m-3" hidden>Print Employees (Native PHP)</button>
    <!-- Default box -->
    <div class="card card-info">

        <form action="{{ route('employee_certificates_compensation_print') }}" role="form" method="POST"
            target="_blank">
            @csrf
            <div>
                <div class="d-flex flex-row">
                    <div class="p2 ml-4 mt-4 mb-2">
                        <button class="btn btn-info m-0" type="submit" target="_blank">Preview Report</button>
                    </div>
                </div>
            </div>
            <hr>
            <div class="form-group col-4 ml-3">
                <label>Employee:</label>
                <select name="employee" id="employee_select" class="form-control select2">
                    @foreach ($data as $employee)
                    <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                    @endforeach
                </select>

                <!-- Income and Bonus tables -->
                <div class="row" hidden>
                    <div class="col-md-6 mt-4">
                        <div class="card card-warning">
                            <div class="card-header">
                                <h3 class="card-title">Income</h3>
                            </div>
                            <!-- /.card-header -->
                            <div class="card-body table-responsive p-0" style="height: 300px;">
                                <table class="table table-head-fixed text-nowrap table-hover">
                                    <thead>
                                        <tr>
                                            <th width="150px"><input id="selectAll" type="checkbox">&nbsp;&nbsp; Select
                                            </th>
                                            <th>Item</th>
                                            <th>Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tableEmployeeIncomes">

                                    </tbody>
                                </table>
                            </div>
                            <!-- /.card-body -->
                        </div>
                    </div>
                    <div class="col-md-6 mt-4">
                        <div class="card card-danger">
                            <div class="card-header">
                                <h3 class="card-title">Bonus</h3>
                            </div>
                            <!-- /.card-header -->
                            <div class="card-body table-responsive p-0" style="height: 300px;">
                                <table class="table table-head-fixed text-nowrap table-hover">
                                    <thead>
                                        <tr>
                                            <th width="150px"><input id="selectAll2" type="checkbox">&nbsp;&nbsp;
                                                Select
                                            </th>
                                            <th>Item</th>
                                            <th>Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tableEmployeeBonus">

                                    </tbody>
                                </table>
                            </div>
                            <!-- /.card-body -->
                        </div>
                    </div>
                </div>

                <br>
                <label>Specify Report Signatory:</label><br>
                <label for="">Name:</label>
                <input type="text" name="signatory" class="form-control"
                    value="{{ old('signatory') ?? 'MACAUMBAO U. BAUNTO,MPA,JD' }}">
                <label for="">Position:</label>
                <input type="text" name="position" class="form-control"
                    value="{{ old('position') ?? 'Director II' }}">
            </div>
            <div class="card-body" hidden>
                <div>
                    <table class="table table-bordered table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Select</th>
                                <th>Photo</th>
                                <th>Employee No.</th>
                                <th>Name</th>
                                <th>Employment Type</th>
                                <th>Position</th>
                                <th>Branch</th>
                                <th>Department</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($data as $employee)
                            <tr>
                                <td>
                                    <center>
                                        <input type="checkbox" name="select[]" value="{{ $employee->id }}">
                                    </center>
                                </td>
                                <td>
                                    <center>
                                        <img src="data:image/jpeg;base64,{{ $employee->photo }}"
                                            onerror=this.src="../../dist/img/profile.png"
                                            class="img-circle elevation-2 mt-1" width="30px" height="30px"
                                            alt="User Image">
                                    </center>
                                </td>
                                <td>{{ $employee->employee_no }}</td>
                                <td>{{ $employee->name }}</td>
                                <td>{{ $employee->employment_type }}</td>
                                <td>{{ $employee->position }}</td>
                                <td>{{ $employee->branch }}</td>
                                <td>{{ $employee->department }}</td>
                            </tr>
                            @endforeach
                    </table>
                </div>
            </div>
        </form>
        <div class="card-footer">
            <h6>Employment Certificate of Compensation</h6>
        </div>
    </div>
    <!-- /.card -->

</section>
<!-- /.content -->

<!-- Set menu to collapse and active -->
@endsection

@section('scripts')
<script>
    $(function() {
        $('#hrm').addClass('menu-open');
    });
    $(function() {
        $('#hrm_link').addClass('active');
    });
    $(function() {
        $('#hr_rpt_hdr').addClass('menu-open');
    });
    $(function() {
        $('#hr_rpt_certificates_hdr').addClass('menu-open');
    });
    $(function() {
        $('#hr_rpt').addClass('active');
    });
    $(function() {
        $('#employee_compensation_certificates').addClass('active');
    });

    $('#selectAll').click(function() {

        var checked = $(this).prop('checked');

        if (checked) {
            $('.selectAll').prop('checked', true);
        } else {
            $('.selectAll').prop('checked', false);
        }
    });

    $('#selectAll2').click(function() {

        var checked = $(this).prop('checked');

        if (checked) {
            $('.selectAll').prop('checked', true);
        } else {
            $('.selectAll').prop('checked', false);
        }
    });
</script>
<script src="{{ asset('build/js/EmployeeCertificateCompensation.js') }}"></script>
<script>
    $(document).ready(function() {
        $("#employee").click(function() {
            window.open("./methods/employee.php");
        });
    });
</script>
@endsection