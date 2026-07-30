@extends('layouts.template')

@section('content')
    <!-- Content Header (Page header) -->
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Bank Remittance</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('home') }}" onclick="removeCokie();">Home</a></li>
                        <li class="breadcrumb-item active">Bank Remittance</li>
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

        <!-- Default box -->
        <div class="card card-info">
            <form action="{{ route('bank_remittance_print') }}" role="form" method="POST" target="_blank">
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
                    <select class="form-control" name="department" id="department">
                        <option value="0" selected disabled>Select Department</option>
                        @foreach ($departments as $department)
                            <option value="{{ $department->id }}">{{ $department->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-4 ml-3">
                    <label>Payroll Period</label>
                    <select class="form-control" name="payroll_period" id="payroll_period">
                        <option value="0" selected disabled>Select Payroll Period</option>
                        @foreach ($pay_periods as $dtl)
                            <option value="{{ $dtl->id }}">{{ $dtl->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-4 ml-3">
                    <label>Bank Loan Type</label>
                    <select class="form-control" name="deduction" id="deduction">
                        <option value="0" selected disabled>Select Deduction</option>
                        @foreach ($deductions as $data)
                            <option value="{{ $data->id }}">{{ $data->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="card m-4">
                    <div class="card-header">
                        <label>Signatory</label>
                    </div>
                    <div class="card-body">
                        <div class="form-group col-5 ml-3">
                            <label>Certified Correct</label>
                            <input type="text" name="signatory" class="form-control"
                                value="{{ old('signatory') ?? '' }}">
                        </div>
                        <div class="form-group col-5 ml-3">
                            <label>Position</label>
                            <input type="text" name="position" class="form-control"
                                value="{{ old('position') ?? '' }}">
                        </div>
                        <div class="form-group col-5 ml-3">
                            <label>Date</label>
                            <input type="date" name="date" class="form-control"
                                value="{{ old('date') ?? 'May 22, 2024' }}">
                        </div>
                    </div>
                </div>
            </form>
            <div class="card-footer">
                <h6>Bank Remittance Report</h6>
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
            $('#bank_remittance').addClass('active');
        });
    </script>
@endsection
