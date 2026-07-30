<div class="print-header">
    <div class="header">
        <div style="padding-left: 5px; position: absolute;">
            <img src="data:image/png;base64,{{ $image }}" width="100" height="100">
        </div>
        <div style="margin-right:80px; margin-left:-80px; position: absolute; float:right;">
            <img src="data:image/png;base64,{{ $image2 }}" width="100" height="100">
        </div>
        <div style="text-align: center;">
            <p style="margin: -10px;padding: 5px;">Republic of the Philippines</p>
            <p style="margin: 8px;"><strong>{{ strtoupper($orgCompanyName) }}</strong></p>
            <p style="margin-top: -10px;padding: 0;">{{ $orgCompanyAddress }}
                City</p>
        </div>
    </div>
</div>
