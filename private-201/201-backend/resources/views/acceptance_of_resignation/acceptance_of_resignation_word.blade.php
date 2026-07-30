@foreach ($employees as $resignation)
<div>
    <!-- Header - CS Form info aligned to the left -->
    <p><em>CS Form No. 10</em></p>
    <p><em>Series of 2017</em></p>
    <br/><br/><br/>

    <!-- Company info - centered -->
    <p style="text-align: center;"><strong>Republic of the Philippines</strong></p>
    <p style="text-align: center;"><strong>{{ $orgCompanyName }}</strong></p>
    <br/><br/>

    <!-- Title - centered -->
    <p style="text-align: center;"><strong>ACCEPTANCE OF RESIGNATION</strong></p>
    <br/><br/><br/>

    <!-- Date - right aligned -->
    <p style="text-align: right;">Date: <u>{{ date('F d, Y', strtotime($signatories['date'])) }}</u></p>
    <br/>

    <!-- Employee name - left aligned, bold, underlined -->
    <p><strong><u>{{ strtoupper($resignation->name_prefix ?? '') }} {{ strtoupper($resignation->name) }}</u></strong></p>
    
    <!-- Employee address - left aligned, underlined -->
    <p><u>{{ $employees[0]->pa_house_no ? $employees[0]->pa_house_no . ', ' : '' }}{{ $employees[0]->pa_village ? $employees[0]->pa_village . ', ' : '' }}{{ $employees[0]->pa_street ? $employees[0]->pa_street . ', ' : '' }}Brgy. {{ $address['pa_brgy'] ?? 'N/A' }}, {{ $address['pa_city'] ?? 'N/A' }}, {{ $address['pa_province'] ?? 'N/A' }}, {{ $address['pa_region'] ?? 'N/A' }}</u></p>
    <br/>



    <!-- First paragraph - justified with underlined fields -->
    <p style="text-align: justify;">
        In reply to your letter dated <u>{{ date('F d, Y', strtotime($signatories['date'])) }}</u> tendering your resignation from the position of <u>{{ $resignation->position ?? 'COA-AUDITOR' }}</u> in <u>{{ $resignation->department ?? 'MTSICT' }}</u>, may I inform you that the same is hereby accepted to take effect on <u>{{ date('F d, Y', strtotime($signatories['effectivity_date'] ?? $resignation->date_effectivity ?? now())) }}</u>.
    </p>
    <br/>

    <!-- Second paragraph - justified with underlined rating field -->
    <p style="text-align: justify;">
        Your services while employed from this Office have been rated as <u>{{ $resignation->adjectival_rating ?? '' }}</u> for your reference.
    </p>
    <br/><br/><br/><br/>

    <!-- Closing - right aligned -->
    <p style="text-align: right;">Very truly yours,</p>
    <br/><br/><br/><br/>

    <!-- Signature - right aligned -->
    <div style="text-align: right;">
        <p><u><strong>{{ $signatories['signatory'] }}</strong></u></p>
        <p>{{ $signatories['position1'] }}</p>
    </div>
    <br/><br/><br/><br/>

    <!-- Received by section -->
    <p>Received By: <u><strong>{{ $signatories['received_signatory'] }}</strong></u></p>
    <p style="margin-left: 90px; font-size: smaller;">Signature over Printed Name</p>
    <br/>
    <p>Date: <u>{{ date('F d, Y', strtotime($signatories['received_date'])) }}</u></p>
</div>
@endforeach
