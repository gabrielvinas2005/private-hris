@extends('layouts.template')

@section('content')
    <!-- Content Header (Page header) -->
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Plantilla of Casual Appointments</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('home') }}" onclick="removeCokie();">Home</a></li>
                        <li class="breadcrumb-item active">Plantilla of Casual Appointments</li>
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
            <form action="{{ route('casual_appointment_print') }}" role="form" method="POST" target="_blank">
                @csrf
                <div>
                    <div class="d-flex flex-row">
                        <div class="p2 ml-4 mt-4 mb-2">
                            <button class="btn btn-info m-0" type="submit">Preview Report</button>
                        </div>
                    </div>
                </div>
                <hr>
                <div>
                    <div class="form-group col-4 ml-3">
                        <label>Department / Office:</label>
                        <select name="department" class="form-control select2">
                            @foreach ($departments as $department)
                                <option value="{{ $department->id }}">{{ $department->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-4 ml-3">
                        <label>Source of Funds:</label>
                        <input type="text" name="source_of_funds" class="form-control"
                            value="{{ old('source_of_funds') ?? 'General Fund' }}">
                    </div>
                </div>
                <div class="row">
                    <div class="form-group col-4 ml-3">
                        <strong>Acknowledgement of Appointee</strong><br>
                        <label style="font-weight:normal">Date Received:</label>
                        <input type="date" name="date_received" class="form-control"
                            value="{{ old('date_received') ?? 'February 6, 2025' }}">
                    </div>
                    <div class="form-group col-4 ml-3">
                        <strong>CSCFO Action</strong><br>
                        <label style="font-weight:normal">Date of Action:</label>
                        <input type="date" name="date_action" class="form-control"
                            value="{{ old('date_action') ?? 'February 6, 2025' }}">
                    </div>
                </div>
                <br><label style="margin-left: 15px">Specify Signatory:</label><br>
                <div class="row">
                    <div class="form-group col-4 ml-3">
                        <label for="">For HRMO:</label><br>
                        <label for="" style="font-weight:normal;">Name:</label>
                        <input type="text" name="signatory1" class="form-control"
                            value="{{ old('signatory1') ?? 'MACAUMBAO U. BAUNTO,MPA,JD' }}">
                        <label for="" style="font-weight:normal;">Date:</label>
                        <input type="date" name="date1" class="form-control">
                    </div>
                    <div class="form-group col-4 ml-3">
                        <label>For Appointing Officer / Authority:</label><br>
                        <label for="" style="font-weight:normal;">Name:</label>
                        <input type="text" name="signatory2" class="form-control"
                            value="{{ old('signatory2') ?? 'ALBERT LORENZO' }}">
                        <label for="" style="font-weight:normal;" hidden>Position:</label>
                        <input type="text" name="position2" class="form-control"
                            value="{{ old('position2') ?? 'Supervisor' }}" hidden>
                        <label for="" style="font-weight:normal">Date:</label>
                        <input type="date" name="date2" class="form-control">
                    </div>
                    <div class="form-group col-4 ml-3">
                        <label>For CSC Official:</label><br>
                        <label for="" style="font-weight:normal">Name:</label>
                        <input type="text" name="signatory3" class="form-control"
                            value="{{ old('signatory3') ?? 'ANGELITO M. GARCIA' }}">
                        <label for="" style="font-weight:normal"> Date:</label>
                        <input type="date" name="date3" class="form-control">
                    </div>
                </div>
            </form>
            <div class="card-footer">
                <h6>Plantilla of Casual Appointments</h6>
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
            $('#casual_appointment').addClass('active');
        });
    </script>
@endsection
