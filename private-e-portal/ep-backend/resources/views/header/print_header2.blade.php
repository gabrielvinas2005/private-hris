<!-- Print Header -->
@php
    use App\Helpers\CompanyHelper;

    $companyName = CompanyHelper::getName();
    $companyAddress = CompanyHelper::getAddress();
    $companyLogo = CompanyHelper::getLogoBase64() ?? ($image ?? null);
@endphp
<div class="print-header">
    <div class="header">
        @if ($companyLogo)
        <div style="padding-left: 5px; position: absolute;">
            <img src="data:image/jpeg;base64,{{ $companyLogo }}" width="100" height="100" style="object-fit: contain;">
        </div>
        @endif
        @if (!empty($image2))
        <div style="margin-right:80px; margin-left:-80px; position: absolute; float:right;">
            <img src="data:image/png;base64,{{ $image2 }}" width="100" height="100">
        </div>
        @endif
        <div style="text-align: center;">
            @if ($companyName !== '')
                <p style="margin: 8px;"><strong>{{ $companyName }}</strong></p>
            @endif
            @if ($companyAddress !== '')
                <p style="margin-top: -10px;padding: 0;">{!! nl2br(e($companyAddress)) !!}</p>
            @endif
        </div>
    </div>
</div>
