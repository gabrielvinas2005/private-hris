<!DOCTYPE html>
<html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        <!-- Google Font: Source Sans Pro -->
        <link rel="stylesheet"
            href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
        <!-- Font Awesome -->
        <link rel="stylesheet" href="{{ asset('plugins/fontawesome-free/css/all.min.css') }}">
        <!-- Theme style -->
        <link rel="stylesheet" href="{{ asset('dist/css/adminlte.min.css') }}">

        <title>Plantilla Report</title>

        <style>
            html,
            body {
                height: 297mm;
                width: 210mm;
                margin-top: 15px;
                margin-bottom: 15px;
                margin-left: 40px;
                margin-right: 40px;
            }

            p {
                padding: 0;
                margin: 0;
            }

            .main_table,
            th,
            td {
                border: 1px solid black;
                border-collapse: collapse;
                padding: 5px;
                text-align: center;
                font-size: 11px;
            }
        </style>
    </head>

    <body>
        <!-- Main content -->

        <div class="card p-4">
            <div class="main" style="width: 100%;">
                <div>
                    <div style="padding-left: 5px; position: absolute;">
                        <img src="data:image/png;base64,{{ $image }}" width="100" height="100">
                    </div>
                    <div style="text-align: center;">
                        <p>Republic of the Philippines</p>
                        <h4 style="margin: 0;padding: 0;">{{ isset($companies[0]->name) ? $companies[0]->name : '' }}
                        </h4>
                        <p><i>{{ isset($companies[0]->address) ? $companies[0]->address : '' }}</i></p>
                    </div>
                    <div style="text-align: center;">
                        <h4 style="padding-top: 10px;"><b><u>List of Occupied Positions</u></b></h4>
                    </div>
                    <br>
                    <table style="width: 100%;" class="main_table">

                        <thead>
                            <tr style=" text-align:center;">
                                <th width="8px"></th>
                                <th width="20px">Plantilla</th>
                                <th width="20px">Department</th>
                                <th width="20px">Position</th>
                                <th width="20px">Employee Name</th>
                                <th width="20px">Salary Grade</th>
                                <th width="20px">Salary Step</th>

                            </tr>
                        </thead>

                        <tbody>
                            @foreach($data as $data)
                            <tr>
                                <td>{{ $data->row }}</td>
                                <td>{{ $data->code }}</td>
                                <td>{{ $data->department }}</td>
                                <td>{{ $data->position }}</td>
                                <td>{{ $data->name }}</td>
                                <td>{{ $data->grade }}</td>
                                <td>{{ $data->step }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

    </body>

</html>
