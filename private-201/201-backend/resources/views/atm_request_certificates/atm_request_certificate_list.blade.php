@extends('layouts.template')

@section('content')
    <!-- Content Header (Page header) -->
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>ATM Request Certificate</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('home') }}" onclick="removeCokie();">Home</a></li>
                        <li class="breadcrumb-item active">ATM Request Certificate</li>
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

            <form action="{{ route('atm_request_certificates_print') }}" role="form" method="POST" target="_blank">
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
                    <select name="employee" class="form-control select2">
                        @foreach ($data as $employee)
                            <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                        @endforeach
                    </select>
                    <br>
                    <label>Specify Report Signatory:</label><br>
                    <label for="">Name:</label>
                    <input type="text" name="signatory" class="form-control"
                           value="{{ old('signatory') ?? 'MA. MESALINA N. BUENSALIDA' }}">
                    <label for="">Position:</label>
                    <input type="text" name="position" class="form-control"
                           value="{{ old('position') ?? 'Administrative Officer V-Cashier' }}">
                    <label for="">Name:</label>
                    <input type="text" name="signatory2" class="form-control"
                           value="{{ old('signatory2') ?? 'LUIGI LORENZO N. RAZAL' }}">
                    <label for="">Position:</label>
                    <input type="text" name="position2" class="form-control"
                           value="{{ old('position2') ?? 'Administrative Officer V-HRMDO' }}">
                    <label for="">Name:</label>
                    <input type="text" name="signatory3" class="form-control"
                           value="{{ old('signatory3') ?? 'RAMON C. ARIMADO' }}">
                    <label for="">Position:</label>
                    <input type="text" name="position3" class="form-control"
                           value="{{ old('position3') ?? 'Chief Administrative Officer' }}">
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
                    {{ $data->links() }}
                </div>
            </form>
            <div class="card-footer">
                <h6>ATM Request Certificate</h6>
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
            $('#atm_request_certificates').addClass('active');
        });
    </script>
    <script>
        $(document).ready(function() {
            $("#employee").click(function() {
                window.open("./methods/employee.php");
            });
        });
    </script>
@endsection
