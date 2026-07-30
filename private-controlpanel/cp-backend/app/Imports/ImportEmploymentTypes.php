<?php

namespace App\Imports;

use App\EmploymentType;
use Maatwebsite\Excel\Concerns\ToModel;

class ImportEmploymentTypes implements ToModel
{
    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        return new EmploymentType([
            'name' => $row[0],
            'with_end_contract' => $row[1],
        ]);
    }
}
