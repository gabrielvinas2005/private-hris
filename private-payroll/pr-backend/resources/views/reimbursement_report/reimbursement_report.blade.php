@extends('layouts.template')

@section('content')
    <!-- Content Header (Page header) -->
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Payroll Communication Macco</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('home') }}" onclick="removeCokie();">Home</a></li>
                        <li class="breadcrumb-item active">Payroll Communication Macco</li>
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
            <div class="alert alert-warning alert-dismissible">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                <h5><i class="icon fas fa-check"></i> Warning!</h5>
                {{ session()->get('error') }}
            </div>
        @endif

        @if (session('warning'))
            <div class="alert alert-warning alert-dismissible">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                <h5><i class="icon fas fa-check"></i> Warning!</h5>
                {{ session('warning') }}
            </div>
        @endif


        <!-- Default box -->
        <div class="card card-info">
            <form action="{{ route('reimbursement_report_print') }}" role="form" method="POST" target="_blank">
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
                    <label>Department</label>
                    <select class="form-control" name="department_id" id="department_id">
                        <option value="0" selected disabled>Select Department</option>
                        @foreach ($departments as $department)
                            <option value="{{ $department->id }}">{{ $department->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-4 ml-3">
                    <label>Month</label>
                    <select class="form-control" name="month_id" id="month_id">
                        <option value="0" selected disabled>Select Month</option>
                        @foreach (range(1, 12) as $month)
                            <option value="{{ $month }}">
                                {{ DateTime::createFromFormat('!m', $month)->format('F') }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-4 ml-3">
                    <label>Year</label>
                    <select class="form-control" name="year" id="year">
                        <option value="0" selected disabled>Select Year</option>
                        @for ($years = now()->year; $years >= 2015; $years--)
                            <option value="{{ $years }}">{{ $years }}</option>
                        @endfor
                    </select>
                </div>
                <br>
                <label style="margin-left: 15px">Specify Signatories:</label><br><br>

                <div class="row">
                    <div class="form-group col-4 ml-3">
                        <label>Municipal Assessor:</label><br>
                        <label for="" style="font-weight: normal;">Name:</label>
                        <input type="text" name="signatory1" class="form-control"
                            value="{{ old('signatory1') ?? 'MACAUMBAO U. BAUNTO,MPA,JD' }}">
                    </div>
                    <div class="form-group col-4 ml-3">
                        <label>OIC-Municipal Accountant:</label><br>
                        <label for="" style="font-weight: normal;">Name:</label>
                        <input type="text" name="signatory2" class="form-control"
                            value="{{ old('signatory2') ?? 'ALBERT LORENZO' }}">
                    </div>
                    <div class="form-group col-4 ml-3">
                        <label>Municipal Treasurer:</label><br>
                        <label for="" style="font-weight: normal;">Name:</label>
                        <input type="text" name="signatory3" class="form-control"
                            value="{{ old('signatory3') ?? 'ANGELITO M. GARCIA' }}">
                    </div>
                    <div class="form-group col-4 ml-3">
                        <label>Municipal Administrator:</label><br>
                        <label for="" style="font-weight: normal;">Name:</label>
                        <input type="text" name="signatory4" class="form-control"
                            value="{{ old('signatory4') ?? 'ANGELITO M. GARCIA' }}">
                    </div>
                    <div class="form-group col-4 ml-3">
                        <label>Assistant Municipal Treasurer:</label><br>
                        <label for="" style="font-weight: normal;">Name:</label>
                        <input type="text" name="signatory5" class="form-control"
                            value="{{ old('signatory5') ?? 'ANGELITO M. GARCIA' }}">
                    </div>
                </div>
            </form>
            <div class="card-footer">
                <h6>Payroll Communication Macco</h6>
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
            $('#reimbursement_report').addClass('active');
        });
    </script>
@endsection
