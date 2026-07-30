<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Confidentiality and Non-Disclosure Undertaking</title>
    <style>
        /* Top = 1in, Right = 1in, Bottom = 0.75in, Left = 1.25in */
        @page { margin: 1in 1in 0.75in 1.25in; size: A4; }
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            font-size: 10pt;
        }
        .content { margin-top: 40px; margin-bottom: 40px; }
        /* Approx. 0.5 line spacing (tighter than single) */
        p { line-height: 1.1; margin: 0 0 6px 0; text-align: justify; }
        .center { text-align: center; }
        .indent { text-indent: 30pt; }
        .paragraph-indent { margin-left: 25pt; }
        .numbered-clauses {
            margin: 0 0 6px 30pt;
            padding-left: 15pt;
        }
        .numbered-clauses li { margin-bottom: 4px; line-height: 1.1; text-align: justify; }
        .sub-clauses {
            margin-top: 3px;
            margin-bottom: 3px;
        }
        .sub-clauses li { text-align: justify; }
        .section-title { font-weight: bold; text-align: center; text-decoration: underline; margin-bottom: 16px; margin-top: 16px; }
        .signature-blocks {
            margin-top: 40px;
            width: 100%;
            text-align: center;
        }
        .signature {
            text-align: center;
            font-size: 10pt;
            display: inline-block;
        }
        .signature-line {
            margin-top: 40px;
            font-weight: bold;
            text-decoration: underline;
            margin-bottom: 4px;
        }
        .signature-title {
            font-weight: normal;
        }
        .page-footer {
            position: fixed;
            bottom: 0.5in;
            right: 1in;
            font-size: 9pt;
            color: #000;
            z-index: 1000;
            text-align: right;
        }
    </style>
</head>
<body>
    <div class="content">
        <p class="section-title">CONFIDENTIALITY AND NON-DISCLOSURE UNDERTAKING</p>

        <p>
            I, <strong>{{ strtoupper($employee->full_name ?? 'N/A') }}</strong>, a Filipino citizen, of legal age and with residence at <strong>{{ trim($employee->permanent_address ?? 'N/A') }}</strong>, after being sworn in accordance with law, hereby declare that:
        </p>

        <ol class="numbered-clauses">
            <li>
                I am <strong>{{ strtoupper($employee->position_name ?? 'N/A') }}</strong> OF THE <strong>{{ strtoupper($employee->division_name ?? 'N/A') }}</strong> @if(!empty($employee->division_code))(<strong>{{ strtoupper($employee->division_code) }}</strong>)@endif of the {{ $orgCompanyName }} ({{ $orgBranchCode }}) OR I am performing services for the {{ $orgCompanyName }} under a Contract of Service, and am executing this undertaking in favor of the {{ $orgCompanyName }} ("{{ $orgBranchCode }}")
            </li>
            <li>
                In the course of performing services for {{ $orgBranchCode }}, I may have access to or some across confidential information in the possession of, or being maintained by, {{ $orgBranchCode }} which includes confidential information its officers, personnel, partner agencies or collaborators or other third persons. Confidential information is information that would be reasonably understood as confidential as the same is non-public information about a person or an entity that, if disclosed, could reasonably be expected to place either the person or the entity at risk of criminal or civil liability, or damage the person or entity's financial interests or standing, employability, privacy or reputation etc.  such that access thereto is limited only to those with a need to know by reason of the performance of their functions whether or not the information is in writing or in a material form or has or has not been marked as confidential.  It includes but is not limited to:
                <ol class="sub-clauses" type="a">
                    <li>personal information as defined under the Philippine Data Privacy Act (DPA). It is any information whether recorded in a material form or not, from which the identity of an individual is apparent or can be reasonably and directly ascertained by the entity holding the information, or when put together with other information would directly and certainly identify an individual e.g. home addresses and other contact details of participants, personnel or persons who have contracts with {{ $orgBranchCode }};</li>
                    <li>sensitive personal information as defined under the DPA which includes personal information;
                        <ol class="sub-clauses" type="i">
                            <li>About an individual's race, ethnic origin, marital status, age, color, and religious, philosophical or political affiliations;</li>
                            <li>About an individual's health, education, genetic or sexual life of a person, or to any proceeding for any offense committed or alleged to have been committed by such person, the disposal of such proceedings, or the sentence of any court in such proceedings;</li>
                            <li>Issued by government agencies peculiar to an individual which includes, but not limited to, social security numbers, previous or current health records, licenses or its denials, suspension or revocation, and tax returns; and;</li>
                            <li>Specifically established by an executive order or an act of Congress to be kept classified.</li>
                        </ol>
                    </li>
                    <li>Privileged information refers to any and all forms of data which under the Rules of Court and other pertinent laws constitute privileged communication;</li>
                    <li>proprietary information such as trade secrets, confidential research data, information the disclosure of which would prejudice intellectual property rights;</li>
                    <li>confidential information pertaining to {{ $orgBranchCode }} operations such as transcripts/recordings of meetings, internal reports, internal memoranda, drafts of decisions as well as other information that are exceptions to the right to freedom of information under the IRR of RA 6713;</li>
                    <li>usernames, passwords, access codes and the like;</li>
                    <li>information that is confidential under other applicable laws;</li>
                    <li>information obtained by the agency from third parties under non-disclosure agreements or any other contract that designates third party information as confidential</li>
                </ol>
            </li>
            <li>
                I undertake that I shall:
                <ol class="sub-clauses" type="a">
                    <li>
                        process or perform operations on confidential information including, but not limited to access, collection, reproduction, recording, organization, storage, updating or modification, retrieval, consultation, use, disclosure, consolidation, blocking, erasure or destruction only if reasonably necessary to fulfill my duties and the processing is allowed under applicable laws such as the DPA and the Code of Conduct and Ethical Standards for Public Officials and Employees;
                    </li>
                    <li>
                        Under the DPA, the processing of personal information shall be permitted only if not otherwise prohibited by law, and when at least one of the following conditions exists:
                        <ol class="sub-clauses" type="i">
                            <li>The data subject has given his or her consent;</li>
                            <li>The processing of personal information is necessary and is related to the fulfillment of a contract with the data subject or in order to take steps at the request of the data subject prior to entering into a contract;</li>
                            <li>The processing is necessary for compliance with a legal obligation to which the personal information controller is subject;</li>
                            <li>The processing is necessary to protect vitally important interests of the data subject, including life and health;</li>
                            <li>The processing is necessary in order to respond to national emergency, to comply with the requirements of public order and safety, or to fulfill functions of public authority which necessarily includes the processing of personal data for the fulfillment of its mandate; or</li>
                            <li>The processing is necessary for the purposes of the legitimate interests pursued by the personal information controller or by a third party or parties to whom the data is disclosed, except where such interests are overridden by fundamental rights and freedoms of the data subject which require protection under the Philippine Constitution.</li>
                        </ol>
                    </li>
                    <li>
                        Under the DPA, the processing of sensitive personal information and privileged information shall be prohibited, except in the following cases:
                        <ol class="sub-clauses" type="i">
                            <li>The data subject has given his or her consent, specific to the purpose prior to the processing, or in the case of privileged information, all parties to the exchange have given their consent prior to processing;</li>
                            <li>The processing of the same is provided for by existing laws and regulations: <span style="font-style: italic;">Provided, that such</span> regulatory enactments guarantee the protection of the sensitive personal information and the privileged information: <span style="font-style: italic;">Provided, further</span> That the consent of the data subjects are not required by law or regulation permitting the processing of the sensitive personal information or the privileged information;</span></li>
                            <li>The processing is necessary to protect the life and health of the data subject or another person, and the data subject is not legally or physically able to express his or her consent prior to the processing;</li>
                            <li>The processing is necessary to achieve the lawful and noncommercial objectives of public organizations and their associations: <span style="font-style: italic;">Provided, that such</span> processing is only confined and related to the bona fide members of these organizations or their associations: <span style="font-style: italic;">Provided further</span> That the sensitive personal information are not transferred to third parties: Provided, finally, That consent of the data subject was obtained prior to processing;</li>
                            <li>The processing is necessary for purposes of medical treatment, is carried out by a medical practitioner or a medical treatment institution, and an adequate level of protection of personal information is ensured; or</li>
                            <li>The processing concerns such personal information as is necessary for the protection of lawful rights and interests of natural or legal persons in court proceedings, or the establishment, exercise or defense of legal claims, or when provided to government or public authority.</li>
                        </ol>
                    </li>
                    <li>consult and seek guidance from relevant {{ $orgBranchCode }} offices in the event I am unsure of whether I am authorized to process or perform operations (access, copy use, disclose etc as stated in 3.a. above) on confidential information;</li>
                    <li>exercise due diligence in safeguarding the confidentiality of such information by preventing unauthorized processing of  such information by others such as by locking or logging off the computer when not in use, not leaving the  office unattended or unlocked, keeping  hard copies of Confidential Information in a secure place (e.g., locked drawer or cabinet) when not in active use, shredding  such hard copies when no longer needed in accordance with instructions given by the proper official,  Agency policy, or any applicable contractual agreement or law;</li>
                    <li>report any unauthorized or accidental processing of Confidential Information to the proper office;</li>
                    <li>report the unlawful or accidental processing of personal or sensitive personal information to the proper head of office and data protection officer;</li>
                    <li>return and/or destroy all Confidential Information and make the appropriate certification regarding the return and/or destruction of such information when requested by {{ $orgBranchCode }} to do so;</li>
                    <li>comply with all agency policies and procedures applicable to Confidential Information such as the Acceptable IT Use Policy for Information Technology of the {{ $orgBranchCode }};</li>
                    <li>not act for personal gain or to the detriment of {{ $orgBranchCode }} based on Confidential Information to which I have access or which is in my possession.</li>
                </ol>
            </li>
            <li>
                I agree that my obligations pursuant to this undertaking apply to Confidential Information that I came across or had access to from the time my employment or engagement with {{ $orgBranchCode }} commenced and that such obligations will survive the tenure of my employment/engagement with {{ $orgBranchCode }};
            </li>
            <li>
                I agree that in the event I previously executed a confidentiality or non-disclosure agreement or undertaking in favor of {{ $orgBranchCode }} that the obligations contained in this undertaking are in addition to those contained in such prior agreement or undertaking.
            </li>
            <li>
                I understand that if I fail to comply with this undertaking, such violation may be a ground for {{ $orgBranchCode }} to take appropriate disciplinary and/or legal action against me. I am also aware that the DPA provides for criminal penalties (imprisonment and a fine) for unauthorized processing of personal and sensitive personal information.
            </li>
        </ol>

        <p>
            IN WITNESS WHEREOF, I have affixed my signature to this Agreement this {{ \Carbon\Carbon::parse($current_date)->format('jS \o\f F Y') }} at {{ $orgCompanyAddress }}
        </p>

        <div class="signature-blocks">
            <div class="signature">
                <div class="signature-line">
                    <strong>{{ strtoupper($employee->full_name ?? 'N/A') }}</strong>
                </div>
                <div class="signature-title"><strong>{{ $employee->position_name ?? 'N/A' }}</strong></div>
            </div>
        </div>

        <p style="margin-top: 40px; text-align: center;"><strong>WITNESSED BY:</strong></p>

        <div class="signature-blocks">
            <div class="signature">
                <div class="signature-line">
                    <strong>{{ strtoupper($signatory->full_name ?? 'N/A') }}</strong>
                </div>
                <div class="signature-title"><strong>{{ $signatory->position_name ?? 'N/A' }}</strong></div>
            </div>
        </div>
    </div>

    <script type="text/php">
        if (isset($pdf)) {
            $font = $fontMetrics->get_font("Arial", "normal");
            $size = 9;
            $text = "Page {PAGE_NUM} of {PAGE_COUNT}";
            $y = $pdf->get_height() - 36; // 0.5in from bottom (36pt)
            $pageWidth = $pdf->get_width();
            $textWidth = $fontMetrics->get_text_width($text, $font, $size);
            $x = $pageWidth - $textWidth; // Right-aligned before margin
            $pdf->page_text($x, $y, $text, $font, $size);
        }
    </script>
</body>
</html>
