@extends('layouts.template')

@section('content')
    <!-- Content Header (Page header) -->
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Loyalty Award</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('home') }}" onclick="removeCokie();">Home</a></li>
                        <li class="breadcrumb-item active">Loyalty Award</li>
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

            <form action="{{ route('loyalty_award_report_print') }}" role="form" method="POST" target="_blank">
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
                    <label>Payroll Period:</label>
                    <select name="payroll_period_id" id="payroll_period_id" class="form-control">
                        @foreach ($PayrollPeriodType as $pi)
                            <option value="{{ $pi->id }}">{{ $pi->name }}</option>
                        @endforeach
                    </select>
                    <br>
                    <label>Branch:</label>
                    <select name="branch_id" id="branch_id" class="form-control select2">
                        <option value="0" selected disabled>Select Branch</option>
                        @foreach ($Branch as $br)
                            <option value="{{ $br->id }}">{{ $br->name }}</option>
                        @endforeach
                    </select>
                    <br>
                    <label>Office:</label>
                    <select id="department_id" name="department_id" class="form-control select2">
                        <option value="0">All</option>
                    </select>
                </div>
                <div class="row">
                    <div class="form-group col-4 ml-3">
                        <label>Specify Report Signatory 1:</label><br>
                        <label for="">Name:</label>
                        <input type="text" id="signatory_1" name="signatory_1" class="form-control" value="">
                        <label for="">Position:</label>
                        <input type="text" id="signatory_position_1" name="signatory_position_1" class="form-control"
                            value="">
                        <label for="">Description:</label>
                        <textarea id="description_1" name="description_1" class="form-control"></textarea>
                    </div>
                    <div class="form-group col-4 ml-3">
                        <label>Specify Report Signatory 2:</label><br>
                        <label for="">Name:</label>
                        <input type="text" id="signatory_2" name="signatory_2" class="form-control" value="">
                        <label for="">Position:</label>
                        <input type="text" id="signatory_position_2" name="signatory_position_2" class="form-control"
                            value="">
                        <label for="">Description:</label>
                        <textarea id="description_2" name="description_2" class="form-control"></textarea>
                    </div>
                    <div class="form-group col-4 ml-3">
                        <label>Specify Report Signatory 3:</label><br>
                        <label for="">Name:</label>
                        <input type="text" id="signatory_3" name="signatory_3" class="form-control" value="">
                        <label for="">Position:</label>
                        <input type="text" id="signatory_position_3" name="signatory_position_3" class="form-control"
                            value="">
                        <label for="">Description:</label>
                        <textarea id="description_3" name="description_3" class="form-control"></textarea>
                    </div>
                    <div class="form-group col-4 ml-3">
                        <label>Specify Report Signatory 4:</label><br>
                        <label for="">Name:</label>
                        <input type="text" id="signatory_4" name="signatory_4" class="form-control" value="">
                        <label for="">Position:</label>
                        <input type="text" id="signatory_position_4" name="signatory_position_4" class="form-control"
                            value="">
                        <label for="">Description:</label>
                        <textarea id="description_4" name="description_4" class="form-control"></textarea>
                    </div>
                    <div class="form-group col-4 ml-3">
                        <label>Specify Report Signatory 5:</label><br>
                        <label for="">Name:</label>
                        <input type="text" id="signatory_5" name="signatory_5" class="form-control" value="">
                        <label for="">Position:</label>
                        <input type="text" id="signatory_position_5" name="signatory_position_5" class="form-control"
                            value="">
                        <label for="">Description:</label>
                        <textarea id="description_5" name="description_5" class="form-control"></textarea>
                    </div>
                </div>

            </form>
            <div class="card-footer">
                <h6>Loyalty Award</h6>
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
            $('#hrp_report_hdr').addClass('menu-open');
        });
        $(function() {
            $('#hrp_report').addClass('active');
        });
        $(function() {
            $('#loyalty_award_report').addClass('active');
        });

        $("#branch_id").on("change", function() {
            var id = $(this).val();
            var payroll_period_id = document.getElementById("payroll_period_id").value;

            if (id == 0) {
                $("#department_id").val(0);
                $("#department_id").attr("disabled", true);
                $("#document_no").val("");
                $("#revision").val("");
            } else {
                $.ajax({
                    type: "GET",
                    url: "/get_departments/" + id + "/" + payroll_period_id,
                    fail: function() {
                        alert("request failed");
                    },
                    success: function(data) {
                        var data = JSON.parse(data);
                        var is_main_branch = 0;
                        var key = "payroll_summary";

                        $("#department_id").empty();
                        $("#department_id").append(
                            "<option value='0' selected>All</option>"
                        );

                        data.forEach(element => {
                            $("#department_id").append(
                                $("<option></option>")
                                .attr("value", element["id"])
                                .text(element["name"])
                            );

                            is_main_branch = element["is_main_branch"];
                        });
                    }
                });
            }

            // alert(id);

            $.ajax({
                type: "GET",
                url: "/get_loyalty_award_signatory/" + id,
                fail: function() {
                    alert("request failed");
                },
                success: function(data) {
                    var data = JSON.parse(data);

                    $('#signatory_1').val('');
                    $('#signatory_position_1').val('');
                    $('#description_1').val('');
                    $('#signatory_2').val('');
                    $('#signatory_position_2').val('');
                    $('#description_2').val('');
                    $('#signatory_3').val('');
                    $('#signatory_position_3').val('');
                    $('#description_3').val('');
                    $('#signatory_4').val('');
                    $('#signatory_position_4').val('');
                    $('#description_4').val('');
                    // alert(data);
                    data.forEach(element => {
                        $('#signatory_1').val(element["signatory_1"]);
                        $('#signatory_position_1').val(element["signatory_position_1"]);
                        $('#description_1').val(element["description_1"]);
                        $('#signatory_2').val(element["signatory_2"]);
                        $('#signatory_position_2').val(element["signatory_position_2"]);
                        $('#description_2').val(element["description_2"]);
                        $('#signatory_3').val(element["signatory_3"]);
                        $('#signatory_position_3').val(element["signatory_position_3"]);
                        $('#description_3').val(element["description_3"]);
                        $('#signatory_4').val(element["signatory_4"]);
                        $('#signatory_position_4').val(element["signatory_position_4"]);
                        $('#description_4').val(element["description_4"]);
                    });
                }
            });
        });
    </script>
@endsection
