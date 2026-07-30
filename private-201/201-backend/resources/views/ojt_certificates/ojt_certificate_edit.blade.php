@extends('layouts.template')

@section('content')
<!-- Content Header (Page header) -->
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>Edit OJT Information</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}" onclick="removeCokie();">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('ojt_certificates') }}">OJT List</a></li>
                    <li class="breadcrumb-item active">Edit OJT Information</li>
                </ol>
            </div>
        </div>
    </div><!-- /.container-fluid -->
</section>

<!-- Main content -->
<section class="content">
    @if($errors->any())
    @foreach ($errors->all() as $error)
    <div class="alert alert-danger alert-dismissible">
        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
        <h5><i class="icon fas fa-ban"></i> Error!</h5>
        {{ $error }}
    </div>
    @endforeach
    @endif

    @if(session()->has('success'))
    <div class="alert alert-success alert-dismissible">
        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
        <h5><i class="icon fas fa-check"></i> Success!</h5>
        {{ session()->get('success') }}
    </div>
    @endif
    <!-- Default box -->
    <div class="card card-info">
        <div class="card-header">
            <h3 class="card-title">Edit OJT Information</h3>
        </div>
        <div class="card-body">
            <form action="/ojt_certificates_edit/{{ $ojt[0]->id }}" role="form" method="post">
                @csrf
                @method('PATCH')
                <div class="form-group">
                    <div>
                        <label for="">Name</label>
                    </div>
                    <div class="input-group mb-3">
                        <input id="name" name="name" type="text"
                            class="form-control @error('name') is-invalid @enderror"
                            value="{{ old('name') ?? $ojt[0]->name }}" placeholder="Name"
                            autocomplete="off">
                    </div>
                </div>
                <div class="form-group">
                    <div>
                        <label for="">Date Start</label>
                    </div>
                    <div class="input-group mb-3">
                        <input id="date_start" name="date_start" type="date"
                            class="form-control @error('name') is-invalid @enderror"
                            value="{{ old('name') ?? $ojt[0]->date_start }}"
                            placeholder="Date Start" autocomplete="off">
                    </div>
                </div>
                <div class="form-group">
                    <div>
                        <label for="">Date End</label>
                    </div>
                    <div class="input-group mb-3">
                        <input id="date_end" name="date_end" type="date"
                            class="form-control @error('name') is-invalid @enderror"
                            value="{{ old('name') ?? $ojt[0]->date_end }}"
                            placeholder="Date End" autocomplete="off">
                    </div>
                </div>
                <div class="form-group">
                    <div>
                        <label for="">Hours Rendered</label>
                    </div>
                    <div class="input-group mb-3">
                        <input id="hours" name="hours" type="number"
                            class="form-control @error('name') is-invalid @enderror"
                            value="{{ old('name') ?? $ojt[0]->hours }}"
                            placeholder="Hours Rendered" autocomplete="off">
                    </div>
                </div>
                <br>
                <label>Specify Report Signatory:</label><br>
                <div class="form-group">
                    <div>
                        <label for="">Name:</label>
                    </div>
                    <div class="input-group mb-3">
                        <input id="signatory_name" name="signatory_name" type="text"
                            class="form-control @error('name') is-invalid @enderror" value="{{ old('name') ?? $ojt[0]->signatory_name }}"
                            placeholder="Name of Signatory" autocomplete="off">
                    </div>
                    <div>
                        <label for="">Position:</label>
                    </div>
                    <div class="input-group mb-3">
                        <input id="signatory_position" name="signatory_position" type="text"
                            class="form-control @error('name') is-invalid @enderror" value="{{ old('name') ?? $ojt[0]->signatory_position }}"
                            placeholder="Position of Signatory" autocomplete="off">
                    </div>
                </div>
                    <div class="form-group mt-5">
                    <button type="submit" class="btn btn-primary">Update OJT Information</button>
                </div>
                </div>

                
            </form>
        </div>
        <div class="card-footer">
            <h6>Update OJT Information.</h6>
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
        $('#ojt_certificates').addClass('active');
    });
</script>
@endsection