<?php

namespace App\Providers;

use App\Helpers\BranchHelper;
use App\Helpers\CompanyHelper;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register()
    {
        //
    }

    public function boot()
    {
        View::composer('*', function ($view) {
            $view->with([
                'orgCompanyName' => CompanyHelper::getName() ?: 'Company Name',
                'orgBranchCode' => BranchHelper::getMainBranchCode(),
                'orgCompanyAddress' => CompanyHelper::getAddress(),
                'orgCompanyEmail' => CompanyHelper::getEmail(),
            ]);
        });
    }
}
