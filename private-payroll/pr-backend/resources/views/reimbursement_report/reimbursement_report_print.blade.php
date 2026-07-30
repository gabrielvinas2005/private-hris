<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payroll Communication Macco</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            text-align: center;
            margin: 5px;
            padding: 5px;
        }

        p {
            padding: 0;
            margin: 0;
        }


        table {
            width: 100%;
            border-collapse: collapse;
            border: 2px solid black;
            font-size: 12px;
        }

        th,
        td {
            border: 1px solid black;
            padding: 5px;
            text-align: left;
        }

        th {
            text-align: center;

            .page {
                page-break-after: always;
            }
        }


        .note {
            margin-top: 20px;
            text-align: left;
            font-size: 11px;
        }


        .footer {
            position: relative;
            border: none;
            margin-top: 40px;
            width: 100%;
        }
    </style>
</head>

<body>
    <div style="text-align: center;">
        <p>Republic of the Philippines</p>
        <p><b>{{ $company[0]->name ?? '' }}</b></p>
        <p style="margin-top: 2px; font-size: 18px;"><b>REIMBURSEMENT-COMMUNICATION EXPENSES</b></p>
        <p style="margin-top: 2px; font-size: 17px;"><b>{{ $data[0]->department ?? '' }}</b></p>
        <p><b><u>{{ $data[0]->month_year ?? '' }}</u></b></p>
    </div>
    <p class="note">We acknowledge receipt of the sum shown opposite our names as reimbursement for expenses incurred
        for the period stated:</p>
    {{-- <span style="text-align:right; float:right; margin-right:30px;font-size:11px; margin-top:-20px">Sheet of <u></u>
        Sheets</span> --}}

    <table class="data">
        <tr>
            <th rowspan="2">NO.</th>
            <th rowspan="2">NAME</th>
            <th rowspan="2">POSITION</th>
            <th colspan="2">PRE-PAID</th>
            <th colspan="2">POSTPAID</th>
            <th rowspan="2">TOTAL AMOUNT</th>
            <th rowspan="2">No.</th>
            <th rowspan="2">TOTAL <br>AMOUNT <br>RECEIVED</th>
            <th rowspan="2">SIGNATURE <br>OF <br>EMPLOYEE</th>
        </tr>
        <tr>
            <th>INVOICE #</th>
            <th>AMOUNT</th>
            <th>INVOICE # & <br>Receipt #</th>
            <th>AMOUNT</th>
        </tr>
        @php
            $number = 1;
            $count = 1;
            $totalAmount = 0;
            $totalPrepaid = 0;
            $totalPostpaid = 0;
        @endphp
        @foreach ($data as $datas)
            @php
                $totalPrepaid += $datas->prepaid_amount;
                $totalPostpaid += $datas->postpaid_amount;
                $totalAmount = $totalPrepaid + $totalPostpaid;
            @endphp
            <tr>
                <td style="text-align: center">{{ $number++ }}</td>
                <td>{{ $datas->full_name }}</td>
                <td>{{ $datas->position }}</td>
                <td style="text-align: center">{{ $datas->prepaid_invoice_no }}</td>
                <td style="text-align: right">{{ number_format($datas->prepaid_amount, 2, '.', ',') }}</td>
                <td style="text-align: center">{{ $datas->postpaid_invoice_no }}</td>
                <td style="text-align: right">{{ number_format($datas->postpaid_amount, 2, '.', ',') }}</td>
                <td style="text-align: right">
                    {{ number_format($datas->prepaid_amount + $datas->postpaid_amount, 2, '.', ',') }}</td>
                <td style="text-align: center">{{ $count++ }}</td>
                <td style="text-align: right">
                    <b> {{ number_format($datas->prepaid_amount + $datas->postpaid_amount, 2, '.', ',') }}</b>
                </td>
                <td></td>
            </tr>
        @endforeach
        <tr colspan= "6">
            <td colspan="3" style="text-align: center"><b>TOTAL</b></td>
            <td></td>
            <td style="text-align: right"><b>{{ number_format($totalPrepaid, 2, '.', ',') }}</b></td>
            <td></td>
            <td style="text-align: right"><b>{{ number_format($totalPostpaid, 2, '.', ',') }}</b></td>
            <td style="text-align: right"><b>{{ number_format($totalAmount, 2, '.', ',') }}</b></td>
            <td></td>
            <td style="text-align: right"><b>{{ number_format($totalAmount, 2, '.', ',') }}</b></td>
            <td></td>
        </tr>
    </table>
    <div class="footer">
        <div class="signatories">
            <table style="border:none; width:100%;">
                <tr>
                    <td style="text-align:center; border:none; width:20%">CERTIFIED: Services have been duly
                        rendered as stated above.</td>
                    <td style=" border:none; width:20% ">CERTIFIED: <input type="checkbox"> Allotment obligated
                        for the purpose as indicated above.<br>
                        <input type="checkbox"> Supporting documents complete.
                    </td>
                    <td style="border:none;  width:20%">CERTIFIED: Funds available.</td>
                    <td style="border:none; width:20% ">APPROVED FOR PAYMENT:</td>
                    <td style=" border:none; width:20% ">CERTIFIED: each employee whose name appears above has
                        been paid the amount opposite his/her name.</td>
                </tr>

                <tr>
                    <td style="border:none; padding-top:60px">
                        <b>{{ $signatories['signatory1'] ?? '' }}</b><br>
                        {{ $signatories['signatory_position_1'] ?? '' }}<br>
                        Name & Signature of Supervisor<br><br>
                    </td>
                    <td style="border:none; padding-top:45px">
                        <b>{{ $signatories['signatory2'] ?? '' }}</b><br>
                        {{ $signatories['signatory_position_2'] ?? '' }}<br><br>
                    </td>
                    <td style="border:none; padding-top:40px">
                        <b>{{ $signatories['signatory3'] ?? '' }}</b><br>
                        {{ $signatories['signatory_position_3'] ?? '' }}<br><br>
                    </td>
                    <td style="border:none; padding-top:45px">
                        <b>{{ $signatories['signatory4'] ?? '' }}</b><br>
                        {{ $signatories['signatory_position_4'] ?? '' }}<br>
                        Name and Signature of Officer<br><br>
                    </td>
                    <td style="border:none; padding-top:15px">
                        <b>{{ $signatories['signatory5'] ?? '' }}</b><br>
                        {{ $signatories['signatory_position_5'] ?? '' }}
                    </td>
                </tr>
                <tr>
                    <td style="border:none;">Date: <u> {{ now()->format('F d, Y') }}</u>
                    </td>
                    <td style="border:none;">Date: <u> {{ now()->format('F d, Y') }}
                    </td>
                    <td style="border:none;">Date: <u> {{ now()->format('F d, Y') }}
                    </td>
                    <td style="border:none;">Date: <u> {{ now()->format('F d, Y') }}
                    </td>
                    <td style="border:none;">Date: <u> {{ now()->format('F d, Y') }}
                    </td>
                </tr>
            </table>
        </div>
    </div>
</body>

</html>

{{-- <script>
    function paginateTable() {
        let rows = document.querySelectorAll(".data tbody tr");
        let table = document.querySelector(".data");
        let pageLimit = 15;
        let pages = [];
        let pageCount = Math.ceil(rows.length / pageLimit);
        let container = table.parentNode;
        container.innerHTML = "";

        for (let i = 0; i < pageCount; i++) {
            let page = document.createElement("div");
            page.classList.add("page");
            let newTable = table.cloneNode(true);
            let tbody = document.createElement("tbody");
            for (let j = i * pageLimit; j < (i + 1) * pageLimit && j < rows.length; j++) {
                tbody.appendChild(rows[j].cloneNode(true));
            }
            newTable.querySelector("tbody").replaceWith(tbody);
            page.appendChild(newTable);
            let pageLabel = document.createElement("p");
            pageLabel.style.textAlign = "right";
            pageLabel.innerHTML = `Sheet ${i + 1} of ${pageCount} Sheets`;
            page.appendChild(pageLabel);
            container.appendChild(page);
        }
    }
    window.onload = paginateTable;
</script> --}}
