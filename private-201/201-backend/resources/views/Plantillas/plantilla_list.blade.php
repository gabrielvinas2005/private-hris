@extends('layouts.template')

@section('content')
    <!-- Content Header (Page header) -->
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Plantilla List</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('home') }}" onclick="removeCokie();">Home</a></li>
                        <li class="breadcrumb-item active">Plantilla List</li>
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
                <a href="{{ route('plantilla_add') }}" class="btn btn-info">Add Plantilla</a>
            </div>
            <div class="card-body">
                <div class="card">
                    <div class="card-body">
                        <table id="example1" class="table table-bordered table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Code</th>
                                    <th>Position</th>
                                    <th>Salary Grade</th>
                                    <th>Salary Step</th>
                                    <th>Department</th>
                                    <th>Unit</th>
                                    <th>Publication Date From</th>
                                    <th>Publication Date To</th>
                                    <th>Status</th>
                                    <th>Active</th>
                                    <th>Edit</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($data as $plantilla)
                                    <tr>
                                        <td>{{ $plantilla->code }}</td>
                                        <td>{{ $plantilla->position }}</td>
                                        <td>{{ $plantilla->grade }}</td>
                                        <td>{{ $plantilla->step }}</td>
                                        <td>{{ $plantilla->department }}</td>
                                        <td>{{ $plantilla->unit }}</td>
                                        <td>{{ $plantilla->publication_from == null ? '' : date('m-d-Y', strtotime($plantilla->publication_from)) }}
                                        </td>
                                        <td>{{ $plantilla->publication_to == null ? '' : date('m-d-Y', strtotime($plantilla->publication_to)) }}
                                        </td>
                                        <td>{{ $plantilla->status }}</td>
                                        <td>
                                            <b>
                                                {{ $plantilla->active == true ? 'YES' : 'NO' }}
                                            </b>
                                        </td>
                                        <td>
                                            <center><a href="{{ url('plantilla_edit/' . $plantilla->id) }}"
                                                   class="btn btn-info">Edit</a></center>
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
                <h6>List of all Plantilla maintenance data sorted by name.</h6>
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
            $('#cpm').addClass('menu-open');
        });
        $(function() {
            $('#cpm_link').addClass('active');
        });
        $(function() {
            $('#plantilla_setup').addClass('active');
        });
        $(function() {
            $('#hr_setup_hdr').addClass('menu-open');
        });
        $(function() {
            $('#hr_setup').addClass('active');
        });
    </script>
@endsection
