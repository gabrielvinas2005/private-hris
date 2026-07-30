<?php

namespace App\Imports;

use App\Branch;
use Maatwebsite\Excel\Concerns\ToModel;

class ImportBranches implements ToModel
{
    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        return new Branch([
            'name' => $row[0],
            'is_main_branch' => $row[1],
        ]);
    }
}
