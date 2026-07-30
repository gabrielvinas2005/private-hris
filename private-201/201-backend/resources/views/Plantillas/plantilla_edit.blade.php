@extends('layouts.template')

@section('content')
    <!-- Content Header (Page header) -->
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Edit Plantilla</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('home') }}" onclick="removeCokie();">Home</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('plantillas') }}">Plantilla List</a></li>
                        <li class="breadcrumb-item active">Edit Plantilla</li>
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

        @if (session()->has('success'))
            <div class="alert alert-success alert-dismissible">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                <h5><i class="icon fas fa-check"></i> Success!</h5>
                {{ session()->get('success') }}
            </div>
        @endif
        <!-- Default box -->
        <div class="card card-info">
            <div class="card-header">
                <h3 class="card-title">Edit Plantilla Information</h3>
            </div>
            <div class="card-body">
                <form action="/plantilla_edit/{{ $plantilla[0]->id }}" role="form" method="post">
                    @csrf
                    @method('PATCH')
                    <div class="form-group">
                        <div>
                            <label for="">Code</label>
                        </div>
                        <div class="input-group mb-3">
                            <input id="code" name="code" type="text"
                                class="form-control @error('name') is-invalid @enderror"
                                value="{{ old('name') ?? $plantilla[0]->code }}" placeholder="Item Code" autocomplete="off">
                        </div>
                    </div>
                    <div class="form-group">
                        <div>
                            <label for="">Position</label>
                        </div>
                        <div class="mb-3">
                            <select name="position_id" id="position"
                                class="form-control @error('position') is-invalid @enderror">
                                @foreach ($position as $pos)
                                    <option value="{{ $pos->id }}"
                                        {{ $pos->id == $plantilla[0]->position_id ? 'selected' : '' }}>
                                        {{ $pos->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <div>
                            <label for="">Salary Grade</label>
                        </div>
                        <div class="mb-3">
                            <select name="salary_grade_id" id="grade"
                                class="form-control @error('grade') is-invalid @enderror">
                                @foreach ($grade as $grades)
                                    <option value="{{ $grades->id }}"
                                        {{ $grades->id == $plantilla[0]->grade_id ? 'selected' : '' }}>
                                        {{ $grades->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <div>
                            <label for="">Salary Step</label>
                        </div>
                        <div class="mb-3">
                            <select name="salary_step_id" id="step"
                                class="form-control @error('step') is-invalid @enderror">
                                @foreach ($step as $steps)
                                    <option value="{{ $steps->id }}"
                                        {{ $steps->id == $plantilla[0]->step_id ? 'selected' : '' }}>
                                        {{ $steps->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <div>
                            <label for="">Department</label>
                        </div>
                        <div class="mb-3">
                            <select name="department_id" id="department"
                                class="form-control @error('step') is-invalid @enderror">
                                @foreach ($department as $departments)
                                    <option value="{{ $departments->id }}"
                                        {{ $departments->id == $plantilla[0]->department_id ? 'selected' : '' }}>
                                        {{ $departments->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <div>
                            <label for="">Unit</label>
                        </div>
                        <div class="input-group mb-3">
                            <input id="unit" name="unit" type="text"
                                class="form-control @error('name') is-invalid @enderror"
                                value="{{ old('name') ?? $plantilla[0]->unit }}" placeholder="Plantilla Unit"
                                autocomplete="off">
                        </div>
                    </div>
                    <div class="form-group">
                        <div>
                            <label for="">Publication Date From</label>
                        </div>
                        <div class="input-group mb-3">
                            <input id="publication_from" name="publication_from" type="date"
                                class="form-control @error('name') is-invalid @enderror"
                                value="{{ old('name') ?? $plantilla[0]->publication_from }}"
                                placeholder="Plantilla Publication From" autocomplete="off">
                        </div>
                    </div>
                    <div class="form-group">
                        <div>
                            <label for="">Publication Date To</label>
                        </div>
                        <div class="input-group mb-3">
                            <input id="publication_to" name="publication_to" type="date"
                                class="form-control @error('name') is-invalid @enderror"
                                value="{{ old('name') ?? $plantilla[0]->publication_to }}"
                                placeholder="Plantilla Publication To" autocomplete="off">
                        </div>
                    </div>
                    <div class="form-group">
                        <div>
                            <label for="">Status</label>
                        </div>
                        @if ($plantilla[0]->employee_id == 0)
                            <div class="input-group mb-3">
                                <input id="status" name="status" type="text"
                                    class="form-control @error('name') is-invalid @enderror" value="Vacant"
                                    placeholder="Plantilla Status" autocomplete="off" readonly>
                            </div>
                        @else
                            <div class="input-group mb-3">
                                <input id="status" name="status" type="text"
                                    class="form-control @error('name') is-invalid @enderror" value="Occupied"
                                    placeholder="Plantilla Status" autocomplete="off" readonly>
                            </div>
                        @endif
                    </div>
                    <div class="custom-control custom-checkbox mb-2">
                        <input name="active" class="custom-control-input" type="checkbox" id="customCheckbox9"
                            {{ $plantilla[0]->active == true ? 'checked' : '' }}>
                        <label for="customCheckbox9" class="custom-control-label">Set as Active</label>
                    </div>

                    <div class="col-md-12">
                        <div class="card card-primary card-outline">
                            <div class="card-header p-2">
                                <ul class="nav nav-pills">
                                    <li class="nav-item"><a class="nav-link active" href="#tab1"
                                            data-toggle="tab">Educational Attainment</a></li>
                                    <li class="nav-item"><a class="nav-link" href="#tab2" data-toggle="tab">Work
                                            Experience</a></li>
                                    <li class="nav-item"><a class="nav-link" href="#tab3"
                                            data-toggle="tab">Eligibility</a>
                                    </li>
                                    <li class="nav-item"><a class="nav-link" href="#tab4"
                                            data-toggle="tab">Trainings</a>
                                    </li>
                                    <li class="nav-item"><a class="nav-link" href="#tab5"
                                            data-toggle="tab">Competencies</a>
                                    </li>
                                    <li class="nav-item"><a class="nav-link" href="#tab6"
                                            data-toggle="tab">Remarks</a>
                                    </li>
                                </ul>
                            </div><!-- /.card-header -->
                            <div class="card-body">
                                <div class="tab-content">
                                    <!-- /.tab-pane -->
                                    <div class="active tab-pane" id="tab1">
                                        <div class="col-md-12 mt-3">
                                            <div class="card card-success">
                                                <!-- /.card-header -->
                                                <div id="tableEducations" class="card-body table-responsive p-0"
                                                    style="height: 300px;">
                                                    <table width="3500"
                                                        class="table table-head-fixed text-nowrap table-hover">
                                                        <thead>
                                                            <tr>
                                                                <th hidden>ID</th>
                                                                <th width="520px">Academic Level</th>
                                                                <th width="820px">Program</th>
                                                                <th>
                                                                    <span class="fa fa-plus"
                                                                        onclick="AddRow('tableEducations')"></span>
                                                                </th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach ($educations as $educ)
                                                                <tr>
                                                                    <td hidden>
                                                                        <input name="education_id[]" type="number"
                                                                            value="{{ $educ->id }}"
                                                                            class="form-control">
                                                                    </td>
                                                                    <td>
                                                                        <div>
                                                                            <select name="academic_level_id[]"
                                                                                class="form-control">
                                                                                <option value="0"
                                                                                    {{ $educ->academic_level_id == 0 ? 'selected' : '' }}>
                                                                                    Elementary</option>
                                                                                <option value="1"
                                                                                    {{ $educ->academic_level_id == 1 ? 'selected' : '' }}>
                                                                                    Secondary</option>
                                                                                <option value="2"
                                                                                    {{ $educ->academic_level_id == 2 ? 'selected' : '' }}>
                                                                                    Vocational Course
                                                                                </option>
                                                                                <option value="3"
                                                                                    {{ $educ->academic_level_id == 3 ? 'selected' : '' }}>
                                                                                    College</option>
                                                                                <option value="4"
                                                                                    {{ $educ->academic_level_id == 4 ? 'selected' : '' }}>
                                                                                    Graduate Studies
                                                                                </option>
                                                                            </select>
                                                                        </div>
                                                                    </td>
                                                                    <td>
                                                                        <div>
                                                                            <input name="program[]" type="text"
                                                                                value="{{ $educ->program }}"
                                                                                class="form-control">
                                                                        </div>
                                                                    </td>
                                                                    <th>
                                                                        <center>
                                                                            <a data-toggle="modal" id="smallButton"
                                                                                data-target="#smallModal"
                                                                                data-attr="{{ route('plantilla_delete', ['type_id' => 2, 'id' => $educ->id]) }}"
                                                                                title="Delete" style="color:#E55451;"><i
                                                                                    class="fa fa-trash"></i></a>
                                                                        </center>
                                                                    </th>
                                                                </tr>
                                                            @endforeach
                                                            <tr class="hide">
                                                                <td hidden>
                                                                    <input name="education_id[]" type="number"
                                                                        class="form-control">
                                                                </td>
                                                                <td>
                                                                    <div>
                                                                        <select name="academic_level_id[]"
                                                                            class="form-control">
                                                                            <option value="0">Elementary</option>
                                                                            <option value="1">High School</option>
                                                                            <option value="2">College</option>
                                                                            <option value="3">Masters Studies</option>
                                                                            <option value="4">Doctorate</option>
                                                                            <option value="5">Vocational Course
                                                                            </option>
                                                                        </select>
                                                                    </div>
                                                                </td>
                                                                <td>
                                                                    <div>
                                                                        <input name="program[]" type="text"
                                                                            class="form-control">
                                                                    </div>
                                                                </td>
                                                                <th>
                                                                    <center>
                                                                        <a class="table-remove" style="color:#E55451;"><i
                                                                                class="fa fa-trash"></i></a>
                                                                    </center>
                                                                </th>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                                <!-- /.card-body -->
                                            </div>
                                        </div>
                                    </div>
                                    <div class="tab-pane" id="tab2">
                                        <div class="col-md-12 mt-3">
                                            <div class="card card-success">
                                                <!-- /.card-header -->
                                                <div id="tableEmploymentRecord" class="card-body table-responsive p-0"
                                                    style="height: 300px;">
                                                    <table class="table table-head-fixed text-nowrap table-hover">
                                                        <thead>
                                                            <tr>
                                                                <th hidden>ID</th>
                                                                <th width="180px">Position</th>
                                                                <th width="320px">Years</th>
                                                                <th>
                                                                    <span class="fa fa-plus"
                                                                        onclick="AddRow('tableEmploymentRecord')"></span>
                                                                </th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach ($employments as $emp)
                                                                <tr>
                                                                    <td hidden>
                                                                        <input name="employment_record_id[]"
                                                                            type="number" value="{{ $emp->id }}"
                                                                            step="any" class="form-control">
                                                                    </td>
                                                                    <td>
                                                                        <div>
                                                                            <input name="position[]" type="text"
                                                                                value="{{ $emp->position }}"
                                                                                class="form-control">
                                                                        </div>
                                                                    </td>
                                                                    <td>
                                                                        <div>
                                                                            <input name="years[]" type="number"
                                                                                step="any"
                                                                                value="{{ $emp->years }}"
                                                                                class="form-control">
                                                                        </div>
                                                                    </td>
                                                                    <th>
                                                                        <center>
                                                                            <a data-toggle="modal" id="smallButton"
                                                                                data-target="#smallModal"
                                                                                data-attr="{{ route('plantilla_delete', ['type_id' => 4, 'id' => $emp->id]) }}"
                                                                                title="Delete" style="color:#E55451;"><i
                                                                                    class="fa fa-trash"></i></a>
                                                                        </center>
                                                                    </th>
                                                                </tr>
                                                            @endforeach
                                                            <tr class="hide">
                                                                <td hidden>
                                                                    <input name="employment_record_id[]" type="number"
                                                                        step="any" class="form-control">
                                                                </td>
                                                                <td>
                                                                    <div>
                                                                        <input name="position[]" type="text"
                                                                            class="form-control">
                                                                    </div>
                                                                </td>
                                                                <td>
                                                                    <div>
                                                                        <input name="years[]" type="number"
                                                                            step="any" class="form-control">
                                                                    </div>
                                                                </td>
                                                                <th>
                                                                    <center>
                                                                        <a class="table-remove" style="color:#E55451;"><i
                                                                                class="fa fa-trash"></i></a>
                                                                    </center>
                                                                </th>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                                <!-- /.card-body -->
                                            </div>
                                        </div>
                                    </div>
                                    <div class="tab-pane" id="tab3">
                                        <div class="col-md-12 mt-3">
                                            <div class="card card-success">
                                                <!-- /.card-header -->
                                                <div id="tableExamination" class="card-body table-responsive p-0"
                                                    style="height: 300px;">
                                                    <table class="table table-head-fixed text-nowrap table-hover">
                                                        <thead>
                                                            <tr>
                                                                <th hidden>ID</th>
                                                                <th>Examinations</th>
                                                                <th>
                                                                    <span class="fa fa-plus"
                                                                        onclick="AddRow('tableExamination')"></span>
                                                                </th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach ($examinations as $exam)
                                                                <tr>
                                                                    <td hidden>
                                                                        <input name="examination_id[]"
                                                                            value="{{ $exam->id }}"
                                                                            class="form-control">
                                                                    </td>
                                                                    <td>
                                                                        <select name="eligibility_id[]"
                                                                            class="form-control">
                                                                            @foreach ($eligibilities as $eligibility)
                                                                                <option value="{{ $eligibility->id }}"
                                                                                    {{ $eligibility->id == $exam->examination_id ? 'selected' : '' }}>
                                                                                    {{ $eligibility->name }}
                                                                                </option>
                                                                            @endforeach
                                                                        </select>
                                                                    </td>
                                                                    <th>
                                                                        <center>
                                                                            <a data-toggle="modal" id="smallButton"
                                                                                data-target="#smallModal"
                                                                                data-attr="{{ route('plantilla_delete', ['type_id' => 5, 'id' => $exam->id]) }}"
                                                                                title="Delete" style="color:#E55451;"><i
                                                                                    class="fa fa-trash"></i></a>
                                                                        </center>
                                                                    </th>
                                                                </tr>
                                                            @endforeach
                                                            <tr class="hide">
                                                                <td hidden>
                                                                    <input name="examination_id[]" class="form-control"
                                                                        value="0">
                                                                </td>
                                                                <td>
                                                                    <select name="eligibility_id[]" class="form-control">
                                                                        <option value="0" selected></option>
                                                                        @foreach ($eligibilities as $eligibility)
                                                                            <option value="{{ $eligibility->id }}">
                                                                                {{ $eligibility->name }}
                                                                            </option>
                                                                        @endforeach
                                                                    </select>
                                                                </td>
                                                                <th>
                                                                    <center>
                                                                        <a class="table-remove" style="color:#E55451;"><i
                                                                                class="fa fa-trash"></i></a>
                                                                    </center>
                                                                </th>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                                <!-- /.card-body -->
                                            </div>
                                        </div>
                                    </div>
                                    <div class="tab-pane" id="tab4">
                                        <div class="col-md-12 mt-3">
                                            <div class="card card-success">
                                                <!-- /.card-header -->
                                                <div id="tableTraining" class="card-body table-responsive p-0"
                                                    style="height: 300px;">
                                                    <table class="table table-head-fixed text-nowrap table-hover">
                                                        <thead>
                                                            <tr>
                                                                <th hidden>ID</th>
                                                                <th>Training/Seminar</th>
                                                                <th>Hours</th>
                                                                <th>
                                                                    <span class="fa fa-plus"
                                                                        onclick="AddRow('tableTraining')"></span>
                                                                </th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach ($trainings as $training)
                                                                <tr>
                                                                    <td hidden>
                                                                        <input name="training_id[]"
                                                                            value="{{ $training->id }}"
                                                                            class="form-control">
                                                                    </td>
                                                                    <td>
                                                                        <div>
                                                                            <input name="training[]" type="text"
                                                                                value="{{ $training->training }}"
                                                                                class="form-control">
                                                                        </div>
                                                                    </td>
                                                                    <td>
                                                                        <div>
                                                                            <input name="hours[]" step="any"
                                                                                type="number"
                                                                                value="{{ $training->hours }}"
                                                                                class="form-control">
                                                                        </div>
                                                                    </td>
                                                                    <th>
                                                                        <center>
                                                                            <a data-toggle="modal" id="smallButton"
                                                                                data-target="#smallModal"
                                                                                data-attr="{{ route('plantilla_delete', ['type_id' => 6, 'id' => $training->id]) }}"
                                                                                title="Delete" style="color:#E55451;"><i
                                                                                    class="fa fa-trash"></i></a>
                                                                        </center>
                                                                    </th>
                                                                </tr>
                                                            @endforeach
                                                            <tr class="hide">
                                                                <td hidden>
                                                                    <input name="training_id[]" class="form-control">
                                                                </td>
                                                                <td>
                                                                    <div>
                                                                        <input name="training[]" type="text"
                                                                            class="form-control">
                                                                    </div>
                                                                </td>
                                                                <td>
                                                                    <div>
                                                                        <input name="hours[]" step="any"
                                                                            type="number" class="form-control">
                                                                    </div>
                                                                </td>
                                                                <th>
                                                                    <center>
                                                                        <a class="table-remove" style="color:#E55451;"><i
                                                                                class="fa fa-trash"></i></a>
                                                                    </center>
                                                                </th>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                                <!-- /.card-body -->
                                            </div>
                                        </div>
                                    </div>
                                    <div class="tab-pane" id="tab5">
                                        <div class="col-md-12 mt-3">
                                            <div class="card-header p-2">
                                                <div role="tabpanel">
                                                    <ul class="nav nav-pills">
                                                        <li class="nav-item"><a href="#competencytabAll"
                                                                class="nav-link active" data-toggle="tab">All</a>
                                                        </li>
                                                        @foreach ($grouped_arr as $competencies)
                                                            <li class="nav-item"><a
                                                                    href="#competencytab{{ $competencies[0]->id }}"
                                                                    name="competency_id" class="nav-link"
                                                                    data-toggle="tab">{{ $competencies[0]->name }}</a>
                                                            </li>
                                                        @endforeach
                                                    </ul>
                                                </div>
                                                <div class="tab-content">
                                                    <div class="active tab-pane" id="competencytabAll">
                                                        <div class="col-md-12 mt-3">
                                                            <div class="card card-success">
                                                                <!-- /.card-header -->
                                                                <div id="tableCompetencies"
                                                                    class="card-body table-responsive p-0"
                                                                    style="height: 300px;">
                                                                    <table
                                                                        class="table table-head-fixed text-nowrap table-hover">
                                                                        <thead>
                                                                            <tr>
                                                                                <th hidden>ID</th>
                                                                                <th>
                                                                                    <input id="selectAll0"
                                                                                        class="empCheck" type="checkbox">
                                                                                </th>
                                                                                <th>Competency</th>
                                                                                <th>Competency Code</th>
                                                                                <th>Competency Name</th>
                                                                                <th>Required Level</th>
                                                                                <th>Competency Description</th>
                                                                            </tr>
                                                                        </thead>
                                                                        <tbody>
                                                                            @foreach ($competency as $competency1)
                                                                                @if ($competency1->assign == 1)
                                                                                    <tr>
                                                                                        <td hidden>
                                                                                            <input
                                                                                                name="subcompetency_id[]"
                                                                                                value="{{ $competency1->subcompetency_id }}"
                                                                                                class="form-control">
                                                                                        </td>
                                                                                        <td>
                                                                                            <input
                                                                                                id="all_check_{{ $competency1->subcompetency_id }}"
                                                                                                class="empSelect selectAll0"
                                                                                                name="subcomp_id[]"
                                                                                                type="checkbox"
                                                                                                value="{{ $competency1->subcompetency_id }}"
                                                                                                {{ $competency1->assign == 1 ? 'checked' : '' }}
                                                                                                onchange="check_{{ $competency1->subcompetency_id }}.checked = this.checked; return true;">
                                                                                        </td>
                                                                                        <td>
                                                                                            <div>
                                                                                                <input name="name[]"
                                                                                                    type="text"
                                                                                                    value="{{ $competency1->name }}"
                                                                                                    class="form-control"
                                                                                                    readonly>
                                                                                            </div>
                                                                                        </td>
                                                                                        <td>
                                                                                            <div>
                                                                                                <input
                                                                                                    name="subcompetency_code[]"
                                                                                                    type="text"
                                                                                                    value="{{ $competency1->subcompetency_code }}"
                                                                                                    class="form-control"
                                                                                                    readonly>
                                                                                            </div>
                                                                                        </td>
                                                                                        <td>
                                                                                            <div>
                                                                                                <input
                                                                                                    name="subcompetency_name[]"
                                                                                                    type="text"
                                                                                                    value="{{ $competency1->subcompetency_name }}"
                                                                                                    class="form-control"
                                                                                                    readonly>
                                                                                            </div>
                                                                                        </td>
                                                                                        <td>
                                                                                            <div>
                                                                                                <input
                                                                                                    id="all_level_{{ $competency1->subcompetency_id }}"
                                                                                                    name="level[]"
                                                                                                    type="number"
                                                                                                    value="{{ $competency1->level }}"
                                                                                                    min="1"
                                                                                                    max="4"
                                                                                                    maxlength="1"
                                                                                                    class="form-control prevent-negative level-input"
                                                                                                    oninput="level_{{ $competency1->subcompetency_id }}.value = all_level_{{ $competency1->subcompetency_id }}.value; return true;">
                                                                                            </div>
                                                                                        </td>
                                                                                        <td>
                                                                                            <div>
                                                                                                <input
                                                                                                    name="subcompetency_description[]"
                                                                                                    type="text"
                                                                                                    value="{{ $competency1->subcompetency_description }}"
                                                                                                    class="form-control"
                                                                                                    readonly>
                                                                                            </div>
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
                                                    @foreach ($grouped_arr as $competencies2)
                                                        <div name="competency_id2" value="{{ $competencies2[0]->id }}"
                                                            class="tab-pane"
                                                            id="competencytab{{ $competencies2[0]->id }}">
                                                            <div class="col-md-12 mt-3">
                                                                <div class="card card-success">
                                                                    <div id="tableCompetencies"
                                                                        class="card-body table-responsive p-0"
                                                                        style="height: 300px;">
                                                                        <table
                                                                            class="table table-head-fixed text-nowrap table-hover">
                                                                            <thead>
                                                                                <tr>
                                                                                    <th hidden>ID</th>
                                                                                    <th><input
                                                                                            id="selectAll{{ $competencies2[0]->id }}"
                                                                                            class="empCheck"
                                                                                            type="checkbox">
                                                                                    </th>
                                                                                    <th>Competency Code</th>
                                                                                    <th>Competency Name</th>
                                                                                    <th>Required Level</th>
                                                                                    <th>Competency Description</th>
                                                                                </tr>
                                                                            </thead>
                                                                            <tbody>
                                                                                @foreach ($grouped_arr[$competencies2[0]->name] as $competency1)
                                                                                    <tr>
                                                                                        <td hidden>
                                                                                            <input
                                                                                                name="subcompetency_id[]"
                                                                                                value="{{ $competency1->subcompetency_id }}"
                                                                                                class="form-control">
                                                                                        </td>
                                                                                        <td>
                                                                                            <input
                                                                                                id="check_{{ $competency1->subcompetency_id }}"
                                                                                                class="empSelect selectAll{{ $competencies2[0]->id }}"
                                                                                                name="subcomp_id[]"
                                                                                                type="checkbox"
                                                                                                value="{{ $competency1->subcompetency_id }}"
                                                                                                {{ $competency1->assign == 1 ? 'checked' : '' }}
                                                                                                onchange="all_check_{{ $competency1->subcompetency_id }}.checked = this.checked; return true;">
                                                                                        </td>
                                                                                        <td>
                                                                                            <div>
                                                                                                <input
                                                                                                    name="subcompetency_code[]"
                                                                                                    type="text"
                                                                                                    value="{{ $competency1->subcompetency_code }}"
                                                                                                    class="form-control"
                                                                                                    readonly>
                                                                                            </div>
                                                                                        </td>
                                                                                        <td>
                                                                                            <div>
                                                                                                <input
                                                                                                    name="subcompetency_name[]"
                                                                                                    type="text"
                                                                                                    value="{{ $competency1->subcompetency_name }}"
                                                                                                    class="form-control"
                                                                                                    readonly>
                                                                                            </div>
                                                                                        </td>
                                                                                        <td>
                                                                                            <div>
                                                                                                <input
                                                                                                    id="level_{{ $competency1->subcompetency_id }}"
                                                                                                    name="level[]"
                                                                                                    type="number"
                                                                                                    value="{{ $competency1->level }}"
                                                                                                    min="1"
                                                                                                    max="4"
                                                                                                    maxlength="1"
                                                                                                    class="form-control prevent-negative level-input"
                                                                                                    oninput="all_level_{{ $competency1->subcompetency_id }}.value = level_{{ $competency1->subcompetency_id }}.value; return true;">
                                                                                            </div>
                                                                                        </td>
                                                                                        <td>
                                                                                            <div>
                                                                                                <input
                                                                                                    name="subcompetency_description[]"
                                                                                                    type="text"
                                                                                                    value="{{ $competency1->subcompetency_description }}"
                                                                                                    class="form-control"
                                                                                                    readonly>
                                                                                            </div>
                                                                                        </td>
                                                                                    </tr>
                                                                                @endforeach
                                                                            </tbody>
                                                                        </table>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                        <div class="tab-pane" id="tab6">
                                            <div class="col-md-12 mt-3">
                                                <div class="card card-success">
                                                    <!-- /.card-header -->
                                                    <div id="tableremarks" class="card-body table-responsive p-0"
                                                        style="height: 300px;">
                                                        <table class="table table-head-fixed text-nowrap table-hover">
                                                            <thead>
                                                                <tr>
                                                                    <th hidden>ID</th>
                                                                    <th>Requirements</th>
                                                                    <th>
                                                                        <span class="fa fa-plus"
                                                                            onclick="AddRow('tableremarks')"></span>
                                                                    </th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                @foreach ($remarks as $remark)
                                                                    <tr>
                                                                        <td hidden>
                                                                            <input name="remark_id[]"
                                                                                value="{{ $remark->id }}"
                                                                                class="form-control">
                                                                        </td>
                                                                        <td>
                                                                            <div>
                                                                                <input name="remark[]" type="text"
                                                                                    value="{{ $remark->requirement }}"
                                                                                    class="form-control"
                                                                                    @if (in_array($remark->requirement, [
                                                                                            'Fully Accomplished Personal Data Sheet(PDS) with recent passport-sized picture',
                                                                                            'Performance rating in the last rating period (If applicable)',
                                                                                            'Photocopy of Certificate of Eligibility/rating/License; and',
                                                                                            'Photocopy of Transcript of Records',
                                                                                        ])) readonly @endif>
                                                                            </div>
                                                                        </td>
                                                                        <th>
                                                                            <center>
                                                                                @if (in_array($remark->requirement, [
                                                                                        'Fully Accomplished Personal Data Sheet(PDS) with recent passport-sized picture',
                                                                                        'Performance rating in the last rating period (If applicable)',
                                                                                        'Photocopy of Certificate of Eligibility/rating/License; and',
                                                                                        'Photocopy of Transcript of Records',
                                                                                    ]))
                                                                                    <i class="fa fa-lock"
                                                                                        style="color:gray;"
                                                                                        title="Permanent Remark"></i>
                                                                                @else
                                                                                    <a data-toggle="modal"
                                                                                        id="smallButton"
                                                                                        data-target="#smallModal"
                                                                                        data-attr="{{ route('plantilla_delete', ['type_id' => 6, 'id' => $remark->id]) }}"
                                                                                        title="Delete"
                                                                                        style="color:#E55451;">
                                                                                        <i class="fa fa-trash"></i>
                                                                                    </a>
                                                                                @endif
                                                                            </center>
                                                                        </th>
                                                                    </tr>
                                                                @endforeach
                                                                <tr class="hide">
                                                                    <td hidden>
                                                                        <input name="remark_id[]" class="form-control">
                                                                    </td>
                                                                    <td>
                                                                        <div>
                                                                            <input name="remark[]" type="text"
                                                                                class="form-control">
                                                                        </div>
                                                                    </td>
                                                                    <th>
                                                                        <center>
                                                                            <a class="table-remove"
                                                                                style="color:#E55451;"><i
                                                                                    class="fa fa-trash"></i></a>
                                                                        </center>
                                                                    </th>
                                                                </tr>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                    <!-- /.card-body -->
                                                </div>
                                            </div>
                                        </div>
                                        <!-- /.tab-pane -->
                                    </div>
                                    <!-- /.tab-content -->
                                </div><!-- /.card-body -->
                            </div>
                            <!-- /.card -->
                        </div>
                        <div class="form-group mt-5">
                            <button type="submit" class="btn btn-primary">Update Plantilla</button>
                        </div>
                    </div>


                </form>
            </div>
            <div class="card-footer">
                <h6>Update Plantilla maintenance data.</h6>
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

        $('.empCheck').click(function() {

            var checked = $(this).prop('checked');
            const activeCheck = $(this).prop('id')
            if (checked) {
                $(`.${activeCheck}`).prop('checked', true);
            } else {
                $(`.${activeCheck}`).prop('checked', false);
            }
        });
    </script>
<script src="{{ asset('build/js/Plantilla.js') }}"></script>
@endsection
