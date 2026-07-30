<?php

namespace App\Services;

use App\User;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UserService
{
    public function store(array $request): string
    {
        try {
            $client = new Client(['verify' => false]);

            $authorization = $this->getAPIAuthToken();

            $headers = [
                'Content-Type' => 'application/json',
                'Authorization' => $authorization,
            ];

            $user = User::where('email', $request["email"])->get();

            $url =  env("LARES_API_URI", '') == '' ? '' : env("LARES_API_URI", "") . 'UMMCommon/CreateUser';

            $payloads = [
                "empId" => $user[0]->employee_no,
                "username" => $user[0]->name,
                "password" => $request["password"],
                "emailId" => $user[0]->email
            ];

            $response = $client->post($url, [
                'headers' => $headers,
                'json' => $payloads,
            ]);

            $contents = $response->getBody()->getContents();
            $str = str_replace("\r\n", "", $contents);
            $array_response = json_decode($str, true);

            return $array_response["returnMessage"];
        } catch (\Throwable $th) {
            Log::info($th);
            return '';
        }
    }

    public function update(Request $request): int
    {
        try {
            $client = new Client(['verify' => false]);
            $authorization = $this->getAPIAuthToken();

            $headers = [
                'Content-Type' => 'application/json',
                'Authorization' => $authorization,
            ];

            $user = User::where('email', $request->email)->get();
            $status = $this->checkStatus($user[0]->id);
            $password = isset($request->password) ? $request->password : $request->user_password;

            $url = env("LARES_API_URI", '') == '' ? '' : env("LARES_API_URI", "") . 'UMMCommon/UpdateUser';

            $payloads = [
                "empId" => $user[0]->employee_no,
                "username" => $user[0]->name,
                "password" => $password,
                "emailId" => $user[0]->email,
                "isActive" => $status,
                "isDeleted" => 0
            ];

            $response = $client->post($url, [
                'headers' => $headers,
                'json' => $payloads,
            ]);

            $contents = $response->getBody()->getContents();
            $str = str_replace("\r\n", "", $contents);
            $array_response = json_decode($str, true);

            return $array_response["data"];
        } catch (\Throwable $th) {
            Log::info($th);
            return 0;
        }
    }

    private function getAPIAuthToken(): string
    {
        try {
            $client = new Client(['verify' => false]);

            $headers = [
                'Content-Type' => 'application/json',
            ];

            $url = env("LARES_API_URI", '') == '' ? '' : env("LARES_API_URI", '') . 'Login/Login';

            $data = [
                'username' => env("LARES_LOGIN_API_USERNAME", ''),
                'password' => env("LARES_LOGIN_API_PASSWORD", '')
            ];

            $response = $client->post($url, [
                'headers' => $headers,
                'json' => $data,
            ]);

            $contents = $response->getBody()->getContents();
            $str = str_replace("\r\n", "", $contents);
            $array_response = json_decode($str, true);
            $token = $array_response["token"];

            return $token;
        } catch (\Throwable $th) {
            Log::info($th);
            return '';
        }
    }

    private function checkStatus(int $user_id): int
    {
        $employee = DB::table('employees as a')
            ->join('users as b', 'a.employee_no', '=', 'b.employee_no')
            ->where([
                'b.id' => $user_id,
                'a.is_employee' => true,
                'a.active' => true,
            ])
            ->get();

        if ($employee->isNotEmpty()) {
            return 1;
        } else {
            return 0;
        }
    }
}
