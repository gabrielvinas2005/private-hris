@extends('layouts.template')

@section('content')
<!-- Content Header (Page header) -->
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>Personal Data Sheet</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}" onclick="removeCokie();">Home</a></li>
                    <li class="breadcrumb-item active">Personal Data Sheet</li>
                </ol>
            </div>
        </div>
    </div><!-- /.container-fluid -->
</section>

<!-- Main content -->
<section class="content">

    @if(session()->has('error'))
    <div class="alert alert-warning alert-dismissible">
        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
        <h5><i class="icon fas fa-check"></i> Warning!</h5>
        {{ session()->get('error') }}
    </div>
    @endif

    <!-- Default box -->
    <div class="card card-info">
        <form action="{{ route('pds_print') }}" role="form" method="POST" target="_blank">
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
                <label>Employee:</label>
                <select name="employee" class="form-control select2">
                    @foreach($data as $employee)
                    <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                    @endforeach
                </select>
            </div>
        </form>
        <div class="card-footer">
            <h6>Personal Data Sheet</h6>
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
        $('#pds').addClass('active');
    });
</script>
@endsection