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
        <!-- Default box -->
        <div class="card card-info">
            <div class="card-body">
                <div class="form-group">
                    <a href="{{ route('reimbursement_add', 0) }}" class="btn btn-primary">Add Payroll Communication
                        Expenses</a>
                </div>
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
                                            <th hidden>ID</th>
                                            <th>Department</th>
                                            <th>Month</th>
                                            <th>Year</th>
                                            <th>Details</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($data as $dtl)
                                            @if ($dtl->posted == false)
                                                <tr>
                                                    <td hidden>{{ $dtl->id }}</td>
                                                    <td>{{ $dtl->department }}</td>
                                                    <td>{{ $dtl->month }}</td>
                                                    <td>{{ $dtl->year }}</td>
                                                    <td>
                                                        <center>
                                                            <a href="{{ route('reimbursement_add', $dtl->id) }}"
                                                                class="btn btn-primary">Details</a>
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
                                            <th hidden>ID</th>
                                            <th>Department</th>
                                            <th>Month</th>
                                            <th>Year</th>
                                            <th>Details</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($data as $dtl)
                                            @if ($dtl->posted == true)
                                                <tr>
                                                    <td hidden>{{ $dtl->id }}</td>
                                                    <td>{{ $dtl->department }}</td>
                                                    <td>{{ $dtl->month }}</td>
                                                    <td>{{ $dtl->year }}</td>
                                                    <td>
                                                        <center>
                                                            <a href="{{ route('reimbursement_add', $dtl->id) }}"
                                                                class="btn btn-primary">Details</a>
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
                <h6>List of all Payroll Communication Expenses data sorted by Month and Year.</h6>
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
            $('#hrp_benefits_hdr').addClass('menu-open');
        });
        $(function() {
            $('#hrp_benefits').addClass('active');
        });

        $(function() {
            $('#reimbursement_report').addClass('active');
        });
    </script>
@endsection
