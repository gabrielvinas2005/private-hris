<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Traits\ApiResponse;

class DropdownsController extends Controller
{
	use ApiResponse;
	public function getCivilStatus()
	{
		$civil_status = DB::table('civil_status')
			->where('active', true)
			->orderBy('id', 'asc')
			->get();

		return $this->successResponse([
			'civil_status' => $civil_status
		], 'Civil status data loaded successfully');
	}

	public function getBloodTypes()
	{
		$blood_types = DB::table('blood_types')
			->where('active', true)
			->orderBy('name', 'asc')
			->get();

		return $this->successResponse([
			'blood_types' => $blood_types
		], 'Blood types data loaded successfully');
	}

	public function getNamePrefixes()
	{
		$prefixes = DB::table('name_prefixes')
			->where('active', true)
			->orderBy('id', 'asc')
			->get();

		return $this->successResponse([
			'prefixes' => $prefixes
		], 'Name prefixes data loaded successfully');
	}

	public function getNameSuffixes()
	{
		$suffixes = DB::table('name_suffixes')
			->where('active', true)
			->orderBy('name', 'asc')
			->get();

		return $this->successResponse([
			'suffixes' => $suffixes
		], 'Name suffixes data loaded successfully');
	}

	public function getCitizenships()
	{
		$citizenships = DB::table('citizenships')
			->where('active', true)
			->orderBy('id', 'asc')
			->get();

		return $this->successResponse([
			'citizenships' => $citizenships
		], 'Citizenships data loaded successfully');
	}

	public function getReligions()
	{
		$religions = DB::table('religions')
			->where('active', true)
			->orderBy('id', 'asc')
			->get();

		return $this->successResponse([
			'religions' => $religions
		], 'Religions data loaded successfully');
	}

    public function getRegions(){
        $regions = DB::table('regions')->select('name', 'region_id')
            ->orderBy('id', 'asc')
            ->get();

        return $this->successResponse([
            'regions' => $regions
        ], 'Regions data loaded successfully');
    }

    public function getProvinces(){
        $provinces = DB::table('provinces')->select('name', 'province_id', 'region_id')
            ->orderBy('id', 'asc')
            ->get();

        return $this->successResponse([
            'provinces' => $provinces
        ], 'Provinces data loaded successfully');
    }
    
    public function getMunicipalities(){
        $municipalities = DB::table('cities')->select('name', 'city_id', 'province_id')
            ->orderBy('id', 'asc')
            ->get();

        return $this->successResponse([
            'municipalities' => $municipalities
        ], 'Municipalities data loaded successfully');
    }

    public function getBarangays(){
        $barangays = DB::table('barangays')->select('name', 'city_id')
            ->orderBy('id', 'asc')
            ->get();

        return $this->successResponse([
            'barangays' => $barangays
        ], 'Barangays data loaded successfully');
    }

    public function getDocumentTypes(){
        $acceptance_letter_documents = DB::table('acceptance_letter_documents')
            ->where('active', true)
            ->orderBy('name', 'asc')
            ->get();

        return $this->successResponse([
            'acceptance_letter_documents' => $acceptance_letter_documents
        ], 'Document types data loaded successfully');
    }

    public function getGenders(){
        $genders = DB::table('genders')
            ->where('active', true)
            ->orderBy('name', 'asc')
            ->get();

        return $this->successResponse([
            'genders' => $genders
        ], 'Genders data loaded successfully');
    }
}
