<!DOCTYPE html>
<html>

<head>
    <title>CS Form No. 7 - Clearance Form</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
            margin-top: -40px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }


        td,
        th {
            border: 1px solid #000;
            padding: 4px;
            vertical-align: top;
        }


        tr {
            border: 1px solid #000;
            vertical-align: top;
            padding-bottom: 0px;
            padding-top: 0px;
        }

        .no-border {
            border: none;
        }

        .center {
            text-align: center;
        }

        .bold {
            font-weight: bold;
        }

        .checkbox {
            display: inline-block;
            width: 11px;
            height: 11px;
            min-width: 11px;
            max-width: 11px;
            min-height: 11px;
            max-height: 11px;
            border: 1px solid #000;
            box-sizing: border-box;
            vertical-align: middle;
            position: relative;
            padding: 0;
            margin: 0 2px 0 0;
            overflow: hidden;
            line-height: 0;
        }

        .checkbox-icon {
            position: absolute;
            left: 0;
            top: 0;
            width: 11px;
            height: 11px;
            line-height: 11px;
            text-align: center;
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 13px;
            font-weight: bold;
            padding: 0;
            margin: 0;
        }

        .signatory-table {
            border: none;
            width: 600px;
            border-collapse: collapse;
            margin-left: 50px;
        }

        .signatory-table td,
        .signatory-table tr {
            border: none;
        }

        .clearance-accountabilities {
            table-layout: fixed;
            width: 100%;
        }

        .clearance-accountabilities col.ca-name {
            width: 21%;
        }

        .clearance-accountabilities col.ca-name-2 {
            width: 21%;
        }

        .clearance-accountabilities col.ca-cleared {
            width: 7%;
        }

        .clearance-accountabilities col.ca-not-cleared {
            width: 7%;
        }

        .clearance-accountabilities col.ca-officer {
            width: 30%;
        }

        .clearance-accountabilities col.ca-signature {
            width: 14%;
        }

        .instructions-page {
            margin-top: 45px;
        }

        .first-page {
            position: relative;
            min-height: 980px;
            padding-bottom: 18px;
        }

        .page-one-footer {
            position: absolute;
            right: 0;
            bottom: 0;
            font-style: italic;
        }

        .instructions-title {
            font-style: italic;
            font-weight: bold;
            margin-top: 50px;
            margin-bottom: 8px;
        }

        .instructions-box {
            border: 1px solid #000;
            padding: 8px 10px;
        }

        .instructions-table td {
            border: 1px solid #000;
            vertical-align: top;
            padding: 8px;
            font-size: 10px;
            line-height: 1.35;
        }

        .instructions-table .num-col {
            width: 24px;
            text-align: center;
            font-weight: bold;
        }

        @media print {
            body {
                margin-top: -34px;
            }

            .instructions-page {
                page-break-before: always;
            }
        }
    </style>
</head>

<body>
    <div class="first-page">

    <table class="no-border">
        <tr class="no-border">
            <td class="no-border" colspan="2" style="text-align: left;">CS Form No. 7<br>Revised 2018</td>
        </tr>
    </table>
    <div style="text-align: center; margin-top: -10px;">
        <h3>{{ $company_name }}</h3>
        <h2 style="margin-top:-10px">CLEARANCE FORM</h2>
        <p style="margin-top:-10px">(Instructions at the back)</p>
    </div>


    <table>
        <tr>
            <td style="width:10px;height:5px">I.</td>
            <td colspan="5" style="width:2000px;">PURPOSE</td>
        </tr>
        <tr>
            <td colspan="6">
                <div><br>
                    <p style="float:right; margin-right: 80px;"><u>{{ $date_of_filing }}</u></p><br>
                    <p style="float:right; margin-right: -55px;">Date of Filing</p>
                    <p style="float:left; margin-right: 40px;">TO:</p>
                    <p>{{ $company_name }}</p>
                    I hereby request clearance from money, property and work-related accountabilities for:<br>
                    <p style="margin-left: 60px;">Purpose:
                        <label>
                            <span class="checkbox"><span class="checkbox-icon">{{ $purpose_name == 'Transfer' ? '✓' : '' }}</span></span>
                            Transfer
                        </label>
                        <label>
                            <span class="checkbox"><span class="checkbox-icon">{{ $purpose_name == 'Resignation' ? '✓' : '' }}</span></span>
                            Resignation
                        </label>
                        <label>
                            <span
                                class="checkbox"><span class="checkbox-icon">{{ !empty($other_purpose) ? '✓' : '' }}</span></span>
                            Other Mode of Separation
                        </label>
                    </p>
                    <p style="margin-left: 112px;">
                        <label>
                            <span class="checkbox"><span class="checkbox-icon">{{ $purpose_name == 'Retirement' ? '✓' : '' }}</span></span>
                            Retirement
                        </label>
                        <label>
                            <span class="checkbox"><span class="checkbox-icon">{{ $purpose_name == 'Leave' ? '✓' : '' }}</span></span> Leave
                        </label>
                        <label> Please specify: <u>{{ $other_purpose ?? '' }}</u></label>
                    </p>
                    Date of Effectivity: <u>{{ $date_of_effectivity }}</u>
                </div>
            </td>
        </tr>
    </table>
    <table>
        <tr>
            <td style="width: 53%"><br>Office of Assignment: <u>{{ $office_assignment }}</u>
                <br> <br>Position/SG/Step: <u>{{ $position_sg_step }}</u>
            </td>
            <td>
                <p style="margin-top:20px; text-align:center;">{{ $employee_name }}</p>
                <p style="margin-top:20px; text-align:center; margin-top: -10px;">Name and Signature of Employee</p>
            </td>
        </tr>
    </table>
    <table>
        <tr>
            <td style="width:10px;height:5px">II.
            </td>
            <td colspan="5" style="width:2000px;">CLEARANCE FROM WORK-RELATED ACCOUNTABILITIES
            </td>
        </tr>
        <tr>
            <td colspan="6">
                <p>
                    We hereby certify that this employee is
                    Cleared <span class="checkbox"><span class="checkbox-icon">{{ $is_cleared ? '✓' : '' }}</span></span>
                    Not Cleared <span class="checkbox"><span class="checkbox-icon">{{ !$is_cleared ? '✓' : '' }}</span></span>
                    from work-related accountabilities from this Unit/Office/Dept.
                </p>
                <br>
                <table class="signatory-table">
                    <tr>
                        <td style="text-align: center; width: 50%; padding-top: 10px;">
                            <u>{{ $division_head }}</u>
                        </td>
                        <td style="text-align: center; width: 50%; padding-top: 10px;">
                            <u>{{ $head_of_office }}</u>
                        </td>
                    </tr>
                    <tr>
                        <td style="text-align: center; padding-top: 0px;">
                            Immediate Supervisor
                        </td>
                        <td style="text-align: center; padding-top: 0px;">
                            Head of Office
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
    <table>
        <tr>
            <td style="width:10px;height:5px">III.
            </td>
            <td colspan="5" style="width:2000px;">
                CLEARANCE FROM MONEY AND PROPERTY ACCOUNTABILITIES
            </td>
        </tr>
    </table>
    <table class="clearance-accountabilities">
        <colgroup>
            <col class="ca-name" />
            <col class="ca-name-2" />
            <col class="ca-cleared" />
            <col class="ca-not-cleared" />
            <col class="ca-officer" />
            <col class="ca-signature" />
        </colgroup>
        <thead>
            <tr>
                <th colspan="2">Name of Unit/Office/Department</th>
                <th>Cleared</th>
                <th>Not Cleared</th>
                <th>Name of Clearing Officer/Official</th>
                <th>Signature</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td colspan="6"><strong>1. Administrative Services</strong></td>
            </tr>
            <tr>
                <td colspan="2">
                    <span style="margin-left: 10px">a. Supply and Property Procurement & Management Services</span>
                </td>
                <td></td>
                <td></td>
                <td>{{ $clearing_officer_supply_property ?? '' }}</td>
                <td></td>
            </tr>
            <tr>
                <td colspan="2">
                    <span style="margin-left: 10px">b. Human Resources Welfare & Assistance</span>
                </td>
                <td></td>
                <td></td>
                <td>{{ $clearing_officer_hr_welfare ?? '' }}</td>
                <td></td>
            </tr>
            <tr>
                <td colspan="2">
                    <span style="margin-left: 10px">c. Agency-Accredited Union/Cooperative</span>
                </td>
                <td></td>
                <td></td>
                <td>{{ $clearing_officer_union_cooperative ?? '' }}</td>
                <td></td>
            </tr>

            <tr>
                <td colspan="6"><strong>2. Library</strong></td>
            </tr>
            <tr>
                <td colspan="2">
                    <span style="margin-left: 10px">a. Legal Office Library</span>
                </td>
                <td></td>
                <td></td>
                <td>{{ $clearing_officer_legal_library ?? '' }}</td>
                <td></td>
            </tr>
            <tr>
                <td colspan="2">
                    <span style="margin-left: 10px">b. Library Services</span>
                </td>
                <td></td>
                <td></td>
                <td>{{ $clearing_officer_library_services ?? '' }}</td>
                <td></td>
            </tr>

            <tr>
                <td colspan="6"><strong>3. Finance and Assets Management</strong></td>
            </tr>
            <tr>
                <td colspan="2">
                    <span style="margin-left: 10px">a. Financial Services</span>
                </td>
                <td></td>
                <td></td>
                <td>{{ $clearing_officer_financial_services ?? '' }}</td>
                <td></td>
            </tr>
            <tr>
                <td colspan="2">
                    <span style="margin-left: 10px">b. Transaction, Processing, Billing Services</span>
                </td>
                <td></td>
                <td></td>
                <td>{{ $clearing_officer_transaction_billing ?? '' }}</td>
                <td></td>
            </tr>
            <tr>
                <td colspan="2">
                    <span style="margin-left: 10px">c. Payroll & Remittance Services</span>
                </td>
                <td></td>
                <td></td>
                <td>{{ $clearing_officer_payroll_remittance ?? '' }}</td>
                <td></td>
            </tr>

            <tr>
                <td colspan="6"><strong>4. Professional and Institutional Development</strong></td>
            </tr>
            <tr>
                <td colspan="2">
                    <span style="margin-left: 10px">a. Scholarship Services</span>
                </td>
                <td></td>
                <td></td>
                <td>{{ $clearing_officer_scholarship ?? '' }}</td>
                <td></td>
            </tr>

            <tr>
                <td colspan="6"><strong>IV. CERTIFICATION OF NO PENDING ADMINISTRATIVE CASE</strong></td>
            </tr>
            <tr>
                <td colspan="2">
                    <span style="margin-left: 10px">a. Internal Affairs Office/Legal Affairs Office</span>
                </td>
                <td></td>
                <td></td>
                <td>{{ $clearing_officer_internal_affairs ?? '' }}</td>
                <td></td>
            </tr>
        </tbody>
    </table>
    <table>
        <tr>
            <td colspan="6">
                <p>
                    <label><span class="checkbox"><span class="checkbox-icon">{{ $with_pending_administrative ? '✓' : '' }}</span></span> with pending administrative case</label><br>
                    <label><span class="checkbox"><span class="checkbox-icon">{{ $with_ongoing_investigation ? '✓' : '' }}</span></span> with ongoing investigation (no formal charge yet)</label>
                </p>
            </td>
        </tr>
    </table>
    <table>
        <tr>
            <td style="width:10px;height:5px">V.
            </td>
            <td colspan="5" style="width:2000px;">CERTIFICATION
            </td>
        </tr>
        <tr>
            <td colspan="6">
                <p>
                    I hereby certify that this employee is cleared of work-related, money and property
                    accountabilities
                    from
                    this
                    agency.
                    This certification includes no pending administrative case from this agency.
                </p>

                <div style="text-align: center; margin-top: 12px;">
                    <p style="margin: 0;"><u>{{ $head_of_office }}</u></p>
                    <p style="margin: 2px 0 0 0;">Signature over Printed Name of Agency Head</p>
                </div>
            </td>
        </tr>
    </table>
    <div class="page-one-footer">Page 1 of 2</div>
    </div>

    <div class="instructions-page">
        <div class="instructions-title">INSTRUCTIONS:</div>
        <div class="instructions-box">
            <ol style="margin: 0; padding-left: 18px; line-height: 1.35;">
                <li style="margin-bottom: 8px;">
                    Employees who are retiring, being separated, transferring to other agencies, leaving the
                    Philippines and going on leave of absence <b>for more than 30 days</b> shall prepare this form in
                    quadruplicate.
                </li>
                <li style="margin-bottom: 8px;">
                    This clearance should be duly accomplished before paying the last salary or any money due the
                    employees. (Specify which type of clearance: maternity leave, retirement, transfer, etc.)
                </li>
                <li style="margin-bottom: 8px;">
                    If the employees are cleared from a unit/office/department, the clearing/authorized official may
                    attach to this clearance the pertinent document/s that shall prove that the employees are cleared
                    of any obligation or accountability from their office, if any, and tick the box under the
                    "Cleared" column before affixing their signatures.
                </li>
                <li style="margin-bottom: 8px;">
                    If the employees appear to have uncleared accountability/ies from a unit/office/department, the
                    clearing/authorized official shall attach to this clearance the pertinent document/s that shall
                    prove that the employees have remaining obligation or accountability from their office further
                    indicating the necessary action/s that the employee must satisfy in order to be cleared, and tick
                    the box under the "Uncleared" column. The clearing/authorized official must only sign this
                    clearance corresponding to their name once the employee have complied the necessary requirements
                    and cleared of all the obligation/s and accountability/ies from their office. They must also tick
                    the box under the "Cleared" column.
                </li>
                <li style="margin-bottom: 8px;">
                    The HRMO shall distribute copies of approved clearance as follows: original to the employee;
                    duplicate to be attached to the payroll or voucher; triplicate to human resource unit file; and
                    fourth copy to accounting/auditing office.
                </li>
                <li>
                    Processing of clearance certificate shall follow the order of number indicated.
                </li>
            </ol>
        </div>
    </div>

</body>

</html>
