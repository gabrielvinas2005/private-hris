@extends('layouts.template')

@section('content')
    <!-- Content Header (Page header) -->
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Payroll Summary with Detailed Deduction</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('home') }}" onclick="removeCokie();">Home</a></li>
                        <li class="breadcrumb-item active">Payroll Summary with Detailed Deduction</li>
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

        <div class="card card-info">
            <form action="{{ route('payroll_summary_with_details_print') }}" role="form" method="GET" target="_blank">
                @csrf
                <div class="d-flex flex-row p-3">
                    <!-- Preview Report Form -->
                    <button class="btn btn-info mr-2" type="submit">Preview Report</button>
                </div>
                <hr>
                <div class="form-group col-4 ml-3">
                    <label>Payroll Interval:</label>
                    <select id="payroll_interval_id" name="payroll_interval_id" class="form-control select2">
                        <option value=""></option>
                        @foreach ($payroll_intervals as $pi)
                            <option value="{{ $pi->id }}">{{ $pi->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-4 ml-3">
                    <label>Payroll Period:</label>
                    <select id="payroll_period_id" name="payroll_period_id" class="form-control select2">
                        <option value=""></option>
                    </select>
                </div>
                <div class="form-group col-4 ml-3">
                    <label>Branch:</label>
                    <select id="branch_id" name="branch_id" class="form-control select2">
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-4 ml-3">
                    <label>Office:</label>
                    <select id="department_id" name="department_id" class="form-control select2">
                        @foreach ($departments as $department)
                            <option value="{{ $department->id }}">{{ $department->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-4 ml-3" hidden>
                    <label>Document Number:</label>
                    <input id="document_no" name="document_no" type="text" class="form-control" value="">
                </div>
                <div class="form-group col-4 ml-3" hidden>
                    <label>Revision:</label>
                    <input id="revision" name="revision" type="text" class="form-control" value="">
                </div>

                <div class="card m-4">
                    <div class="card-header">
                        <label>Signatories</label>
                    </div>
                    <div class="card-body">
                        <div class="form-group col-md-4">
                            <label>CERTIFIED: Services have been duly rendered as stated above.:</label>
                        </div>
                        <div class="form-group col-md-4">
                            <label>Name:</label>
                            <input name="signatory_1" type="text" class="form-control" value="">
                            <label>Position:</label>
                            <input name="signatory_position_1" type="text" class="form-control" value="">
                        </div>
                        <div class="form-group col-md-4">
                            <label>CERTIFIED:</label>
                        </div>
                        <div class="form-group col-md-4">
                            <label>Name:</label>
                            <input name="signatory_2" type="text" class="form-control" value="">
                            <label>Position:</label>
                            <input name="signatory_position_2" type="text" class="form-control" value="">
                        </div>
                        <div class="form-group col-md-4">
                            <label>CERTIFIED: Funds available.</label>
                        </div>
                        <div class="form-group col-md-4">
                            <label>Name:</label>
                            <input name="signatory_3" type="text" class="form-control" value="">
                            <label>Position:</label>
                            <input name="signatory_position_3" type="text" class="form-control" value="">
                        </div>
                        <div class="form-group col-md-4">
                            <label>APPROVED FOR PAYMENT:</label>
                        </div>
                        <div class="form-group col-md-4">
                            <label>Name:</label>
                            <input name="signatory_4" type="text" class="form-control" value="">
                            <label>Position:</label>
                            <input name="signatory_position_4" type="text" class="form-control" value="">
                        </div>
                        <div class="form-group col-md-4">
                            <label>CERTIFIED: each employee whose name appears above has been paid the amount opposite
                                his/her name.</label>
                        </div>
                        <div class="form-group col-md-4">
                            <label>Name:</label>
                            <input name="signatory_5" type="text" class="form-control" value="">
                            <label>Position:</label>
                            <input name="signatory_position_5" type="text" class="form-control" value="">
                        </div>
                    </div>
                </div>

                <div class="card-footer">
                    <h6>Payroll Summary</h6>
                </div>
            </form>
        </div>
        <!-- /.card -->

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
            $('#hrp_report_hdr').addClass('menu-open');
        });
        $(function() {
            $('#hrp_report').addClass('active');
        });
        $(function() {
            $('#payroll_summary_detail').addClass('active');
        });
    </script>
    <script src="{{ asset('build/js/PayrollSummary.js') }}"></script>
@endsection
