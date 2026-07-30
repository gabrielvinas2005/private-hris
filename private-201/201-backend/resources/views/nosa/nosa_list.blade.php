@extends('layouts.template')

@section('content')
    <!-- Content Header (Page header) -->
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Notice of Salary Adjustment</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('home') }}" onclick="removeCokie();">Home</a></li>
                        <li class="breadcrumb-item active">Notice of Salary Adjustment</li>
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
        <!-- Default box -->
        <div class="card card-info">
            <form action="{{ route('nosa_print') }}" role="form" method="POST" target="_blank">
                @csrf
                <div>
                    <div class="d-flex flex-row">
                        <div class="p2 ml-4 mt-4 mb-2">
                            <button class="btn btn-info m-0" type="submit">Preview Report</button>
                        </div>
                    </div>
                </div>
                <hr>
                <div class="form-group col-4 ml-3">
                    <label>Salary Schedule</label>
                    <select name="salary_schedule_id" class="form-control select2">
                        @foreach ($salary_schedules as $salary_schedule)
                            <option value="{{ $salary_schedule->id }}">{{ $salary_schedule->name }}</option>
                        @endforeach
                    </select>
                    <br>
                    <label>Employee:</label>
                    <select name="employee" class="form-control select2">
                        @foreach ($data as $employee)
                            <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                        @endforeach
                    </select>
                    <br>
                    <label>Specify Report Signatory:</label><br>
                    <label for="">Signatory Name:</label>
                    <input type="text" name="signatory" class="form-control"
                           value="{{ old('signatory') ?? 'MACAUMBAO U. BAUNTO,MPA,JD' }}">
                    <label for="">Signatory Position:</label>
                    <input type="text" name="position" class="form-control"
                           value="{{ old('position') ?? 'Director II' }}">
                    <br>
                    <label>Administrator:</label><br>
                    <label for="">Recommending Approval Name:</label>
                    <input type="text" name="signatory_admin" class="form-control"
                           value="{{ old('signatory_admin') ?? 'MACAUMBAO U. BAUNTO,MPA,JD' }}">
                    <label for="">Recommending Approval Position:</label>
                    <input type="text" name="position_admin" class="form-control"
                           value="{{ old('position_admin') ?? 'Administrator' }}">
                </div>
            </form>
            <div class="card-footer">
                <h6>Notice of Salary Adjustment</h6>
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
            $('#hr_rpt').addClass('active');
        });
        $(function() {
            $('#nosa').addClass('active');
        });
    </script>
@endsection
