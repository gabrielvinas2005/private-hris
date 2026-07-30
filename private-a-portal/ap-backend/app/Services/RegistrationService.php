<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Response;
use App\Models\User;
use App\Notifications\EmailUserAccountNotification;
use DateTime;

class RegistrationService
{
	public function getRegistrationData(): array
	{
		$data = DB::table('plantillas')
			->join('positions', 'positions.id', '=', 'plantillas.position_id')
			->join('salary_steps', 'salary_steps.id', '=', 'plantillas.salary_step_id')
			->join('salary_grades', 'salary_grades.id', '=', 'plantillas.salary_grade_id')
			->leftJoin('departments', 'departments.id', '=', 'plantillas.department_id')
			->select(
				'plantillas.id',
				'plantillas.code',
				'positions.name as position',
				'salary_steps.name as step',
				'salary_grades.name as grade',
				'departments.name as department',
				'plantillas.eligibility as eligibility',
				'plantillas.experience as experience',
				'plantillas.training as training',
				'plantillas.education as education',
				'plantillas.unit as unit',
				'plantillas.publication_from as publication_from',
				'plantillas.publication_to as publication_to',
				'plantillas.status as status',
				'plantillas.active'
			)
			->where('plantillas.employee_id', 0)
			->where('plantillas.publication_from', '<=', now())
			->where('plantillas.publication_to', '>=', now())
			->where('plantillas.active', 1)
			->orderBy('positions.name', 'asc')
			->get();

		$non_plantillas = DB::table('non_plantillas as a')
			->join('positions as b', 'a.position_id', '=', 'b.id')
			->join('departments as c', 'a.department_id', '=', 'c.id')
			->select(
				'a.id',
				'a.position_id',
				'b.name as position',
				'a.salary',
				'c.name as department',
				'a.eligibility',
				'a.experience',
				'a.education',
				'a.training',
				'a.description',
				'a.qualification',
				'a.vacant',
				'a.publication_from',
				'a.publication_to',
				DB::raw("case when a.status = 0 then 'Inactive' else 'Active' end as status")
			)
			->where('a.publication_from', '<=', now())
			->where('a.publication_to', '>=', now())
			->where('vacant', '>', 0)
			->where('a.status', 1)
			->orderBy('b.name', 'asc')
			->get();

		$genders = DB::table('genders')->where('active', true)->orderBy('name', 'asc')->get();

		return [
			'plantillas' => $data,
			'non_plantillas' => $non_plantillas,
			'genders' => $genders,
		];
	}

	public function registerApplicant(Request $request): array
	{
		$request->validate([
			'first_name' => 'required',
			'last_name' => 'required',
			'birth_date' => 'required',
			'gender' => 'required',
			'age' => 'required',
			'address' => 'nullable',
			'mobile_no' => 'required',
			'email' => 'required|unique:applicant_headers|unique:users',
			'resume' => 'nullable|file|max:2048'
		]);

		$applicant_record = DB::table('applicant_headers')->where([
			'first_name' => $request->first_name,
			'middle_name' => $request->middle_name,
			'last_name' => $request->last_name,
		])->count();

		if ($applicant_record > 0) {
			throw new \RuntimeException('Applicant Record already exists.');
		}

		$applicant_no = 'APP-' . random_int(100000, 999999);

		if ($request->hasFile('photo')) {
			$image_file = $request->photo;
			$image = Image::make($image_file);
			Response::make($image->encode('jpeg'));
			$image = base64_encode($image);
		} else {
			$image = '';
		}

		$name = $request->first_name . ' ' . $request->middle_name . ' ' . $request->last_name;
		$username = str_replace(' ', '', $request->last_name) . '.' . str_replace(' ', '', $request->first_name) . '.' . str_replace(' ', '', $request->middle_name);
		$now = new DateTime();
		$password = Str::random(8);

		$user = [
			'name' => $username,
			'email' => $request->email,
			'password' => Hash::make($password),
			'photo' => $image,
			'email_verified_at' => $now->format('Y-m-d H:i:s'),
			'locked' => false,
			'employee_no' => $applicant_no,
			'has_change_password' => false,
			'is_applicant' => true,
		];

		DB::table('users')->insert($user);
		$user_id = DB::table('users')->max('id');

		$data = [
			'applicant_no' => $applicant_no,
			'photo' => $image,
			'first_name' => $request->first_name,
			'middle_name' => $request->middle_name,
			'last_name' => $request->last_name,
			'address' => $request->address ?: null,
			'birth_date' => $request->birth_date,
			'age' => $request->age,
			'gender' => $request->gender,
			'mobile_no' => $request->mobile_no,
			'email' => $request->email,
			'employee_no' => $request->employee_no,
			'resume' => '',
			'application_status_id' => 1,
			'application_date' => now(),
			'user_id' => $user_id,
		];

		DB::table('applicant_headers')->insert($data);
		$applicant_id = DB::table('applicant_headers')->max('id');

		// Save resume attachment (base64-encoded) into separate attachments database
		if ($request->hasFile('resume')) {
			$allowedfileExtension = ['pdf', 'jpg', 'png', 'doc', 'docx', 'xlsx', 'xls'];
			$file = $request->file('resume');
			$file_name = $file->getClientOriginalName();
			$extension = $file->getClientOriginalExtension();

			if (in_array($extension, $allowedfileExtension)) {
				$fileContent = file_get_contents($file->getRealPath());
				$encodedContent = base64_encode($fileContent);
				$fileSize = $file->getSize();
				$fileType = $file->getClientMimeType();
				// Absolute path where the resume copy is stored on disk
				$filePath = Storage::disk('local')->getAdapter()->getPathPrefix() . 'resume\\' . $applicant_no . '_' . $file_name;

				$applicant_attachment_data = [
					'applicant_id'    => $applicant_id,
					'attachment_name' => $file_name,
					'path'            => $filePath,
					'file_content'    => $encodedContent,
					'file_size'       => $fileSize,
					'file_type'       => $fileType,
					'created_at'      => now(),
					'updated_at'      => now(),
				];

				DB::connection('attachments')
					->table('applicant_attachments')
					->insert($applicant_attachment_data);

				// Optional: still keep a copy on disk under local storage
				$file->storeAs('resume', $applicant_no . '_' . $file_name);
			}
		}

		$user_account = User::where('id', $user_id)->get();
		Notification::send($user_account, new EmailUserAccountNotification($user_account, $request->email, $username, $password));

		return [
			'user' => $user_account,
			'username' => $username,
			'temporary_password' => $password,
		];
	}
}


