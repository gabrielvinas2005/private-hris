@extends('layouts.template')

@section('content')
    <!-- Content Header (Page header) -->
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Acceptance of Resignation</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('home') }}" onclick="removeCokie();">Home</a></li>
                        <li class="breadcrumb-item active">Acceptance of Resignation</li>
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
            <form action="{{ route('acceptance_of_resignation_print') }}" role="form" method="POST" target="_blank">
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
                    <label>Employee Name</label>
                    <select name="employee" class="form-control select2">
                        @foreach ($data as $employee)
                            <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                        @endforeach
                    </select>
                    <br>
                    <label>Date Filed:</label>
                    <input type="date" name="date" class="form-control" value="{{ old('date') ?? date('Y-m-d') }}">
                    <br>
                    <label>Specify Signatory:</label><br>
                    <label for="">Name:</label>
                    <input type="text" name="signatory" class="form-control"
                        value="{{ old('signatory') ?? 'HON. BERNARD S. WACLIN' }}">
                    <label for="">Position:</label>
                    <input type="text" name="position1" class="form-control"
                        value="{{ old('position1') ?? 'Appointing Officer/Authority' }}">

                    <br>
                    <label>Received by:</label><br>
                    <label for="">Name:</label>
                    <input type="text" name="received_signatory" class="form-control"
                        value="{{ old('received_signatory') ?? 'JUAN DELA CRUZ' }}">
                    <label for="">Date:</label>
                    <input type="date" name="received_date" class="form-control"
                        value="{{ old('received_date') ?? date('Y-m-d') }}">
                </div>
            </form>
            <div class="card-footer">
                <h6>Acceptance of Resignation</h6>
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
            $('#acceptance_of_resignation').addClass('active');
        });
    </script>
@endsection
