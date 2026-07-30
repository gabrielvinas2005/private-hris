<?php

namespace App\Providers;

use App\Helpers\BranchHelper;
use App\Helpers\CompanyHelper;
use Illuminate\Auth\SessionGuard;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // Laravel 8 SessionGuard has no forgetUser(); some worker/auth cleanup paths expect it (L9+).
        if (!SessionGuard::hasMacro('forgetUser')) {
            SessionGuard::macro('forgetUser', function () {
                $this->user = null;
                return $this;
            });
        }

        view()->composer('*', function ($view) {
            if (Auth::check()) {
                $access_menu =  DB::table('access')
                    ->join('menus', 'access.menu_id', '=', 'menus.id')
                    ->select('access.menu_id', 'menus.menu_key', 'access.status')
                    ->where('user_id',  Auth::user()->id)->get();

                $company = DB::table('companies')->get();

                if ($company->isEmpty()) {
                    $company = [
                        'id' => 0,
                        'logo' => '',
                        'name' => '',
                        'address' => '',
                        'email' => '',
                        'telephone_no' => '',
                        'mobile_no' => '',
                    ];

                    $company = (object)$company;
                    $company = collect([$company]);
                }

                $app_key = env("APP_KEY", "");

                $user_data_link = DB::table('users as a')
                    ->join('employees as b', 'b.employee_no', '=', 'a.employee_no')
                    ->selectRaw(
                        "CASE WHEN b.id = null THEN 0 else b.id END as id,
                         CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN
                               CONCAT(b.first_name,' ',substring(b.middle_name,1,1),'. ',b.last_name)
                            ELSE
                                CASE WHEN ISNULL(b.middle_name,'') = '' THEN
                                    RTRIM([dbo].[ufn_DecryptString](b.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](b.last_name,'$app_key')) 
                                ELSE
                                    RTRIM([dbo].[ufn_DecryptString](b.first_name,'$app_key'))+' '+UPPER(substring([dbo].[ufn_DecryptString](b.middle_name,'$app_key'),1,1))+'. '+RTRIM([dbo].[ufn_DecryptString](b.last_name,'$app_key')) 
                                END
                            END as name"
                    )
                    ->where('a.id', Auth::user()->id)
                    ->get();

                if ($user_data_link->isNotEmpty()) {
                    $user_name = $user_data_link[0]->name;
                } else {
                    $user_name = Auth::user()->name;
                }

                $emp = DB::table('users as a')
                    ->join('employees as b', 'b.employee_no', '=', 'a.employee_no')
                    ->selectRaw('case when b.id = null then 0 else b.id end as id')
                    ->where('a.id', Auth::user()->id)
                    ->get();

                // $emp_id_data = DB::table('users as a')
                //     ->join('employees as b', 'b.employee_no', '=', 'a.employee_no')
                //     ->join('interview_panels as c', 'b.id', '=', 'c.employee_id')
                //     ->selectRaw('case when b.id = null then 0 else b.id end as id')
                //     ->where('a.id', Auth::user()->id)
                //     ->get();

                // if ($emp_id_data->isNotEmpty()) {
                //     $is_panelist = true;
                //     $emp_id = $emp[0]->id;
                // } else {
                //     $is_panelist = false;
                //     $emp_id = 0;
                // }

                $is_panelist = false;
                $emp_id = 0;

                $approver = DB::table('approver_headers as a')
                    ->join('approver_details as b', 'a.id', '=', 'b.approver_id')
                    ->where('approver_id_1', $emp_id)
                    ->orWhere('approver_id_2', $emp_id)
                    ->orWhere('approver_id_3', $emp_id)
                    ->get();

                if ($approver->isNotEmpty()) {
                    $is_dtr_reviewer = true;
                } else {
                    $is_dtr_reviewer = false;
                }
            } else {
                $access_menu = [];
                $company = DB::table('companies')->get();

                if ($company->isEmpty()) {
                    $company = [
                        'id' => 0,
                        'logo' => '',
                        'name' => '',
                        'address' => '',
                        'email' => '',
                        'telephone_no' => '',
                        'mobile_no' => '',
                    ];

                    $company = (object)$company;
                    $company = collect([$company]);
                }

                $is_panelist = false;
                $is_dtr_reviewer = false;
                $user_name = '';
            }

            $user_admin = DB::table('users')->where('is_admin', true)->get();

            $view->with(array_merge(compact('access_menu', 'company', 'user_admin', 'is_panelist', 'is_dtr_reviewer', 'user_name'), [
                'orgCompanyName' => CompanyHelper::getName() ?: 'Company Name',
                'orgBranchCode' => BranchHelper::getMainBranchCode(),
                'orgCompanyAddress' => CompanyHelper::getAddress(),
                'orgCompanyEmail' => CompanyHelper::getEmail(),
                'orgCompanyTelephone' => CompanyHelper::getTelephone(),
                'orgCompanyMobile' => CompanyHelper::getMobile(),
                'orgCompanyPhone' => CompanyHelper::getContactPhone(),
            ]));
        });
    }
}
