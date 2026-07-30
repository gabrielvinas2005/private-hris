@extends('layouts.template')

@section('content')
    <!-- Content Header (Page header) -->
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>List of OJT for Certification</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('home') }}" onclick="removeCokie();">Home</a></li>
                        <li class="breadcrumb-item active">List of OJT for Certification</li>
                    </ol>
                </div>
            </div>
        </div><!-- /.container-fluid -->
    </section>

    <!-- Main content -->
    <section class="content">
        <!-- Default box -->
        <div class="card card-info">
            <div class="ml-4 mt-4">
                <a href="{{ route('ojt_certificates_add') }}" class="btn btn-info">Add OJT for Certification</a>
            </div>
            <div class="card-body">
                <div class="card">
                    <div class="card-body">
                        <table id="example1" class="table table-bordered table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Date Start</th>
                                    <th>Date End</th>
                                    <th>Hours Rendered</th>
                                    <th>Edit</th>
                                    <th>Print</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($data as $ojt)
                                    <tr>
                                        <td>{{ $ojt->name }}</td>
                                        <td>{{ $ojt->date_start }}</td>
                                        <td>{{ $ojt->date_end }}</td>
                                        <td>{{ $ojt->hours }}</td>
                                        <td>
                                            <center><a href="{{ url('ojt_certificates_edit/' . $ojt->id) }}"
                                                   class="btn btn-info">Edit</a></center>
                                        </td>
                                        <td>
                                            <center><a href="{{ url('ojt_certificates_print/' . $ojt->id) }}"
                                                   class="btn btn-info" target="_blank">Print</a></center>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <!-- /.card-body -->
                </div>
                <!-- /.card -->
            </div>
            <div class="card-footer">
                <h6>List of all OJTs for certification sorted by name.</h6>
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
            $('#ojt_certificates').addClass('active');
        });
    </script>
@endsection
