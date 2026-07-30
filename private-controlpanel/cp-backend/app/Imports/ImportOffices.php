<?php

namespace App\Imports;

use App\Department;
use Maatwebsite\Excel\Concerns\ToModel;

class ImportOffices implements ToModel
{
    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        return new Department([
            'code' => $row[0],
            'name' => $row[1],
            'functionality' => $row[2],
            'branch_id' => $row[3]
        ]);
    }
}
