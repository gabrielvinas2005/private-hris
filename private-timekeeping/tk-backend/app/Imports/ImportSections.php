<?php

namespace App\Imports;

use App\Sections;
use Maatwebsite\Excel\Concerns\ToModel;

class ImportSections implements ToModel
{
    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        return new Sections([
            'code' => $row[0],
            'name' => $row[1],
            'division_id' => $row[2],
        ]);
    }
}
