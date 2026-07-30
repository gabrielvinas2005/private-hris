@extends('layouts.template')

@section('content')
<!-- Content Header (Page header) -->
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>Appointment Certificate</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}" onclick="removeCokie();">Home</a></li>
                    <li class="breadcrumb-item active">Appointment Certificate</li>
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
        <form action="{{ route('appointment_certificate_print') }}" role="form" method="POST" target="_blank">
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
                <label>Employee Appointment:</label>
                <select name="employee" class="form-control select2">
                    @foreach($data as $employee)
                    <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                    @endforeach
                </select>
                <br>
                <label>Specify Report Signatory:</label><br>
                <label for="">Name:</label>
                <input type="text" name="signatory" class="form-control" value="{{ old('signatory') ?? 'MACAUMBAO U. BAUNTO,MPA,JD' }}">
                <label for="">Position:</label>
                <input type="text" name="position" class="form-control" value="{{ old('position') ?? 'Director II'}}">
                <label for="">Vice:</label>
                <input type="text" name="vice" class="form-control">
                <label for="">Who:</label>
                <input type="text" name="who" class="form-control">
                <br>
                <label for="">Note:</label>
                <textarea name="note" rows="3" style="resize: none;" class="form-control">This appointment shall take effect on the date of signing by the appointing officer/authority.</textarea>
                <label for="">Date:</label>
                <input type="date" name="cs_date" class="form-control" value="12/17/2013">
                <br>
                <label for="">Highest Ranking HRMO:</label>
                <input type="text" name="hrmo" class="form-control">
                <label for="">Chairperson, HRMPSB/Placement Committee:</label>
                <input type="text" name="hrmpsb" class="form-control">
                <br>
                <label for="">Publish at:</label>
                <input type="text" name="publish_at" class="form-control">
                <label for="">Publish From</label>
                <input type="text" name="publish_from" class="form-control">
                <label for="">Publish To</label>
                <input type="text" name="publish_to" class="form-control">
                <br>
                <label for="">Posted at:</label>
                <input type="text" name="posted_at" class="form-control">
                <label for="">Posted From</label>
                <input type="text" name="posted_from" class="form-control">
                <label for="">Posted to</label>
                <input type="text" name="posted_to" class="form-control">
                <br>
                <label for="">Started On:</label>
                <input type="date" name="started_on" class="form-control">
                <label for="">Delibertion On:</label>
                <input type="date" name="deliberation_on" class="form-control">
            </div>
        </form>
        <div class="card-footer">
            <h6>Appointment Certificate</h6>
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
        $('#appointment_certificate').addClass('active');
    });
</script>
@endsection