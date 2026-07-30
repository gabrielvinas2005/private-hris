<?php

namespace App\Imports;

use App\Division;
use Maatwebsite\Excel\Concerns\ToModel;

class ImportDivisions implements ToModel
{
    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        return new Division([
            'code' => $row[0],
            'name' => $row[1],
            'department_id' => $row[2],
        ]);
    }
}
