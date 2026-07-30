<?php

namespace APP\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\DB;

class DepartmentService
{

    public function store(array $data): bool
    {
        try {
            $client = new Client(['verify' => false]);
            // $client->setDefaultOption('verify', false);

            $authorization = $this->getAPIAuthToken();

            $headers = [
                'Content-Type' => 'application/json',
                'Authorization' => $authorization,
            ];

            $branch_code = $this->getBranchCode($data['branch_id']);
            $url = env("LARES_API_URI", '') == '' ? '' : env("LARES_API_URI", "") . 'PRMSCommon/CreateRespCenter';

            $payloads = [
                "respCenterCode" => $data['code'],
                "respCenterDesc" => $data['name'],
                "branchCode" => $branch_code
            ];

            $response = $client->post($url, [
                'headers' => $headers,
                'json' => $payloads,
            ]);

            $contents = $response->getBody()->getContents();
            $str = str_replace("\r\n", "", $contents);
            $array_response = json_decode($str, true);

            return $array_response["returnStatus"];
        } catch (\Throwable $th) {
            return false;
        }
    }

    public function update(array $data): bool
    {
        try {
            $client = new Client(['verify' => false]);
            // $client->setDefaultOption('verify', false);

            $authorization = $this->getAPIAuthToken();

            $headers = [
                'Content-Type' => 'application/json',
                'Authorization' => $authorization,
            ];

            $branch_code = $this->getBranchCode($data['branch_id']);
            $url = env("LARES_API_URI", '') == '' ? '' : env("LARES_API_URI", "") . 'PRMSCommon/UpdateRespCenter';

            $payloads = [
                "respCenterCode" => $data['code'],
                "respCenterDesc" => $data['name'],
                "branchCode" => $branch_code,
                "activeFlag" => 1
            ];

            $response = $client->post($url, [
                'headers' => $headers,
                'json' => $payloads,
            ]);

            $contents = $response->getBody()->getContents();
            $str = str_replace("\r\n", "", $contents);
            $array_response = json_decode($str, true);

            return $array_response["returnStatus"];
        } catch (\Throwable $th) {
            return false;
        }
    }

    private function getAPIAuthToken(): string
    {
        try {
            $client = new Client(['verify' => false]);
            // $client->setDefaultOption('verify', false);

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
            return '';
        }
    }

    private function getBranchCode(int $id): string
    {

        $branches = DB::table('branches')->where('id', $id)->get();

        if ($branches->isNotEmpty()) {
            return $branches[0]->code;
        }

        return '';
    }
}
