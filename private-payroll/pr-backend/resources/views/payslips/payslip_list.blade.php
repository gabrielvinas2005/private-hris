@extends('layouts.template')

@section('content')
    <!-- Content Header (Page header) -->
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Payslip List</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('home') }}" onclick="removeCokie();">Home</a></li>
                        <li class="breadcrumb-item active">Payslip List</li>
                    </ol>
                </div>
            </div>
        </div><!-- /.container-fluid -->
    </section>

    <!-- Main content -->
    <section class="content">
        <!-- Default box -->
        <div class="card card-info">
            @if ($payslip_records[0]->employee_id == 0)
                <div class="card-body">
                    <h5>Link Employee Record to user to display Payslip Informations</h5>
                </div>
            @endif
            <div class="card-body" {{ $payslip_records[0]->employee_id == 0 ? 'hidden' : '' }}>
                <div class="card">
                    <div class="card-body">
                        <table id="example1" class="table table-bordered table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Payroll Interval</th>
                                    <th>Cut-off</th>
                                    <th>Payroll Start</th>
                                    <th>Payroll End</th>
                                    <th>Payslip</th>
                                    <th>Print</th>
                                </tr>
                            </thead>
                            <tbody id="tableTimeData">
                                @if ($payslip_records[0]->payroll_interval != null)
                                    @foreach ($payslip_records as $dtr)
                                        <tr>
                                            <td>{{ $dtr->payroll_interval }}</td>
                                            <td>{{ $dtr->cut_off }}</td>
                                            <td>{{ date('m-d-Y', strtotime($dtr->payroll_start_date)) }}</td>
                                            <td>{{ date('m-d-Y', strtotime($dtr->payroll_end_date)) }}</td>
                                            <td>
                                                <center>
                                                    <a href="{{ route('payslip_view', [$dtr->employee_id, $dtr->id]) }}"
                                                       class="btn btn-warning">Payslip</a>
                                                </center>
                                            </td>
                                            <td>
                                                <center>
                                                    <a href="{{ route('payslip_print', [$dtr->employee_id, $dtr->id]) }}"
                                                       class="btn btn-info" target="_blank">Print</a>
                                                </center>
                                            </td>
                                        </tr>
                                    @endforeach
                                @endif
                            </tbody>
                        </table>
                    </div>
                    <!-- /.card-body -->
                </div>
                <!-- /.card -->
            </div>
            <div class="card-footer">
                <h6>List of all Payslip by cut-off.</h6>
            </div>
        </div>
        <!-- /.card -->

    </section>
    <!-- /.content -->

@endsection

@section('scripts')
    <script>
        $(function() {
            $('#payslip').addClass('active');
        });
    </script>
@endsection
