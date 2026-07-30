@extends('layouts.template')

@section('content')
<!-- Content Header (Page header) -->
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>Payroll Process</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}" onclick="removeCokie();">Home</a></li>
                    <li class="breadcrumb-item active">Payroll Process</li>
                </ol>
            </div>
        </div>
    </div><!-- /.container-fluid -->
</section>

<!-- Main content -->
<section class="content">
    <!-- Default box -->
    <div class="card card-info">
        <div class="card-body">
            <div class="card-header p-0 border-bottom-0" id="cpm_tabs">
                <ul class="nav nav-tabs" id="custom-tabs-four-tab" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link nav-link-tab active" id="head_pendingLeaveTab" data-toggle="pill"
                            href="#head_pendingLeaveTabPane" role="tab" aria-controls="head_pendingLeaveTabPane"
                            aria-selected="true">For Posting</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link nav-link-tab" id="head_approveLeaveTab" data-toggle="pill"
                            href="#head_approvedLeaveTabPane" role="tab" aria-controls="head_approvedLeaveTabPane"
                            aria-selected="false">Posted</a>
                    </li>
                </ul>
            </div>
            <div class="card-body">
                <div class="tab-content" id="custom-tabs-four-tabContent">
                    <div class="tab-pane fade show active" id="head_pendingLeaveTabPane" role="tabpanel"
                        aria-labelledby="head_pendingLeaveTab">
                        <div class="col-lg-12 col-sm-12">
                            <table id="example1" class="table table-bordered table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th hidden>Release Date</th>
                                        <th>Payroll</th>
                                        <th>Start Date</th>
                                        <th>End Date</th>
                                        <th>Release Date</th>
                                        <th>Details</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($payrolls as $payroll)
                                    @if($payroll->posted == false)
                                    <tr>
                                        <td hidden>{{ $payroll->release_date }}</td>
                                        <td>{{ $payroll->payroll }}</td>
                                        <td>{{ date('F d, Y',strtotime($payroll->attendance_start_date)) }}</td>
                                        <td>{{ date('F d, Y',strtotime($payroll->attendance_end_date)) }}</td>
                                        <td>{{ date('F d, Y',strtotime($payroll->release_date)) }}</td>
                                        <td>
                                            <center>
                                                <a href="{{ route('payroll_process_summary',$payroll->id) }}"
                                                    class="btn btn-info">Details</a>
                                            </center>
                                        </td>
                                    </tr>
                                    @endif
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="head_approvedLeaveTabPane" role="tabpanel"
                        aria-labelledby="head_approveLeaveTab">
                        <div class="col-lg-12 col-sm-12">
                            <table id="example4" class="table table-bordered table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th hidden>Release Date</th>
                                        <th>Payroll</th>
                                        <th>Start Date</th>
                                        <th>End Date</th>
                                        <th>Release Date</th>
                                        <th>Details</th>
                                        <th>Print ORS</th>
                                        <th>Print DV</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($payrolls as $payroll)
                                    @if($payroll->posted == true)
                                    <tr>
                                        <td hidden>{{ $payroll->release_date }}</td>
                                        <td>{{ $payroll->payroll }}</td>
                                        <td>{{ date('F d, Y',strtotime($payroll->attendance_start_date)) }}</td>
                                        <td>{{ date('F d, Y',strtotime($payroll->attendance_end_date)) }}</td>
                                        <td>{{ date('F d, Y',strtotime($payroll->release_date)) }}</td>
                                        <td>
                                            <center>
                                                <a href="{{ route('payroll_process_summary',$payroll->id) }}"
                                                    class="btn btn-info">Details</a>
                                            </center>
                                        </td>
                                        <td>
                                            <center>
                                                <a href="{{ route('ors_payroll_report',$payroll->id) }}"
                                                    class="btn btn-info" target="_blank">Print</a>
                                            </center>
                                        </td>
                                        <td>
                                            <center>
                                                <a href="{{ route('dv_payroll_report',$payroll->id) }}"
                                                    class="btn btn-info" target="_blank">Print</a>
                                            </center>
                                        </td>
                                    </tr>
                                    @endif
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <!-- /.card -->
        </div>
        <div class="card-footer">
            <h6>List of all Payroll data sorted by name.</h6>
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
        $('#payroll_process').addClass('active');
    });
</script>
@endsection