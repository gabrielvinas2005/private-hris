<?php

namespace App\Http\Controllers;

use App\Helpers\CompanyHelper;
use Auth;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use App\Traits\ApiResponse;
use Carbon\Carbon;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Shared\Converter;
use PhpOffice\PhpWord\SimpleType\Jc as WordJc;
use Illuminate\Support\Facades\Log;

class RegretLetterController extends Controller
{
    use ApiResponse;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        try {
            $data = DB::table('applicant_headers as a')
                ->join('application_status as b', 'a.application_status_id', '=', 'b.id')
                ->select(

                    'a.id',
                    DB::raw("concat(a.first_name, ' ', a.middle_name, ' ', a.last_name) as name"),
                    'b.name as status',
                )
                ->orderBy('a.last_name', 'asc')
                ->where('a.application_status_id', 1)
                ->get();
            return $this->successResponse($data, 'Applicant data retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve regret letters: ' . $e->getMessage());
        }
    }


    public function generateLetter($id)
    {
        try {
            // Query using applicant_headers.id (since index() returns applicant_headers.id)
            // Join with applicant_details to get position and department information
            $applicant = DB::table('applicant_headers as ah')
                ->leftJoin('applicant_details as ad', 'ah.id', '=', 'ad.applicant_id')
                ->leftJoin('application_status as c', 'ah.application_status_id', '=', 'c.id')
                ->leftJoin('positions as pos', 'ad.position_applied_id', '=', 'pos.id')
                ->leftJoin('plantillas as p', 'ad.position_applied_id', '=', 'p.id')
                ->leftJoin('departments as d', 'p.department_id', '=', 'd.id')
                ->leftJoin('genders as g', 'g.id', '=', 'ah.gender')
                ->select(
                    'ah.id',
                    'ah.first_name',
                    'ah.middle_name',
                    'ah.last_name',
                    'ah.address',
                    'ah.gender as gender_id',
                    'g.name as gender',
                    'pos.name as position_name',
                    'd.name as department_name',
                    DB::raw("concat(ah.first_name, ' ', ah.middle_name, ' ', ah.last_name) as full_name")
                )
                ->where('ah.id', $id)
                ->where('ah.application_status_id', 1)
                ->first();

            if (!$applicant) {
                return $this->errorResponse('Applicant not found or not eligible for regret letter', 404);
            }

            // Load header and footer images as base64
            $headerImg = null;
            $footerImg = null;
            $headerPath = resource_path('img/report_header.jpg');
            $footerPath = resource_path('img/report_footer.jpg');

            if (file_exists($headerPath)) {
                $headerImg = 'data:image/jpeg;base64,' . base64_encode(file_get_contents($headerPath));
            }
            if (file_exists($footerPath)) {
                $footerImg = 'data:image/jpeg;base64,' . base64_encode(file_get_contents($footerPath));
            }

            // Generate PDF using the blade template
            $pdf = Pdf::loadView('regret_letter.regret_letter', compact('applicant', 'headerImg', 'footerImg'))
                ->setOptions(['defaultFont' => 'sans-serif']);
            $pdf->setPaper('A4', 'portrait');

            $pdfContent = $pdf->output();
            $filename = 'regret_letter_' . $applicant->id . '_' . date('Y-m-d') . '.pdf';

            // Return as blob for frontend consumption
            return response($pdfContent)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'inline; filename="' . $filename . '"')
                ->header('Content-Length', strlen($pdfContent));
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to generate regret letter: ' . $e->getMessage());
        }
    }

    /**
     * Generate regret letter as DOCX using PhpWord.
     */
    public function word($id)
    {
        try {
            // Query using applicant_headers.id (same as generateLetter)
            $applicant = DB::table('applicant_headers as ah')
                ->leftJoin('applicant_details as ad', 'ah.id', '=', 'ad.applicant_id')
                ->leftJoin('application_status as c', 'ah.application_status_id', '=', 'c.id')
                ->leftJoin('positions as pos', 'ad.position_applied_id', '=', 'pos.id')
                ->leftJoin('plantillas as p', 'ad.position_applied_id', '=', 'p.id')
                ->leftJoin('departments as d', 'p.department_id', '=', 'd.id')
                ->leftJoin('genders as g', 'g.id', '=', 'ah.gender')
                ->select(
                    'ah.id',
                    'ah.first_name',
                    'ah.middle_name',
                    'ah.last_name',
                    'ah.address',
                    'ah.gender as gender_id',
                    'g.name as gender',
                    'pos.name as position_name',
                    'd.name as department_name',
                    DB::raw("concat(ah.first_name, ' ', ah.middle_name, ' ', ah.last_name) as full_name")
                )
                ->where('ah.id', $id)
                ->where('ah.application_status_id', 1)
                ->first();

            if (!$applicant) {
                return $this->errorResponse('Applicant not found or not eligible for regret letter', 404);
            }

            // Format date as "d F Y" (e.g., "16 July 2024")
            $formattedDate = Carbon::now()->format('d F Y');

            // Determine title based on gender_id (1 = Ms., 2 = Mr.)
            $title = ($applicant->gender_id == 1) ? 'Ms.' : 'Mr.';

            // Format full name: First [Middle] Last
            $middlePart = !empty($applicant->middle_name) ? ' ' . $applicant->middle_name : '';
            $fullName = $applicant->first_name . $middlePart . ' ' . $applicant->last_name;

            // Format position with department if available
            $positionText = '';
            if ($applicant->position_name) {
                $positionText = $applicant->position_name;
                if ($applicant->department_name) {
                    $positionText .= ' position of the ' . $applicant->department_name;
                } else {
                    $positionText .= ' position';
                }
            }

            // Parse address lines
            $addressLines = [];
            if ($applicant->address) {
                $address = $applicant->address;
                $address = str_replace(["\r\n", "\n\r", "\n", "\r"], '|||', $address);
                $addressLines = preg_split('/\|\|\||,/', $address, -1, PREG_SPLIT_NO_EMPTY);
                $addressLines = array_map('trim', $addressLines);
                $addressLines = array_filter($addressLines);
            }

            $headerPath = resource_path('img/report_header.jpg');
            $footerPath = resource_path('img/report_footer.jpg');

            $phpWord = new PhpWord();
            $phpWord->setDefaultFontName('Arial');
            $phpWord->setDefaultFontSize(11);

            $section = $phpWord->addSection([
                'marginTop' => Converter::inchToTwip(1),
                'marginRight' => Converter::inchToTwip(1),
                'marginBottom' => Converter::inchToTwip(1),
                'marginLeft' => Converter::inchToTwip(1),
                'pageSizeW' => Converter::inchToTwip(8.27),
                'pageSizeH' => Converter::inchToTwip(11.69),
            ]);

            // Header and footer images
            if (file_exists($headerPath)) {
                $header = $section->addHeader();
                $header->addImage($headerPath, [
                    'width' => Converter::cmToPoint(18),
                    'alignment' => 'center'
                ]);
            }
            if (file_exists($footerPath)) {
                $footer = $section->addFooter();
                $footer->addImage($footerPath, [
                    'width' => Converter::cmToPoint(18),
                    'alignment' => 'center'
                ]);
            }

            // Paragraph styles
            $phpWord->addParagraphStyle('justified', [
                'alignment' => WordJc::BOTH,
                'spaceAfter' => 120,
                'lineHeight' => 1.6,
            ]);

            $phpWord->addParagraphStyle('left', [
                'alignment' => WordJc::START,
                'spaceAfter' => 120,
            ]);

            // Date line
            $section->addText($formattedDate, [], 'left');
            $section->addTextBreak(1);

            // Recipient block
            $section->addText(strtoupper($title . ' ' . $fullName), ['bold' => true], 'left');

            // Address lines
            if (!empty($addressLines)) {
                foreach ($addressLines as $line) {
                    if (!empty($line)) {
                        $section->addText($line, [], 'left');
                    }
                }
            }

            $section->addTextBreak(1);

            // Salutation
            $salutation = $section->addTextRun('left');
            $salutation->addText('Dear ', []);
            $salutation->addText($title . ' ' . $applicant->last_name, ['bold' => true]);
            $salutation->addText(',', []);

            $section->addTextBreak(1);

            // Letter content
            $section->addText(
                'Greetings from the DTI ' . CompanyHelper::getName() . '!',
                [],
                'justified'
            );

            $thankYouText = 'We would like to thank you for your application';
            if ($positionText) {
                $thankYouText .= ' for the ' . $positionText . ' of the Center';
            } else {
                $thankYouText .= ' of the Center';
            }
            $thankYouText .= '. We greatly appreciated your interest in joining our agency and the time you have invested in applying for the said role.';
            $section->addText($thankYouText, [], 'justified');

            $section->addText(
                'After careful consideration, we have decided to pursue other candidates whom we have reviewed to have best meet our needs at this time.',
                [],
                'justified'
            );

            $section->addText(
                'Once more, thank you for putting the Center on your list of priorities. Please feel free to apply again with us for open positions in the future.',
                [],
                'justified'
            );

            $section->addText(
                'We wish you success in all your undertakings.',
                [],
                'justified'
            );

            $section->addTextBreak(1);

            // Signature block
            $section->addText('Very truly yours,', [], 'left');
            $section->addTextBreak(1);
            $section->addText('MARIA ANTONIETTE S. ZOILO', ['bold' => true], 'left');
            $section->addText('Administrative Officer V', [], 'left');

            $filename = 'regret_letter_' . $applicant->id . '_' . date('Ymd_His') . '.docx';
            $tempPath = storage_path('app/' . $filename);

            IOFactory::createWriter($phpWord, 'Word2007')->save($tempPath);

            return response()->download($tempPath, $filename)->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            Log::error('Failed to generate regret letter DOCX', [
                'error' => $e->getMessage(),
                'id' => $id,
            ]);
            return $this->serverErrorResponse('Failed to generate regret letter DOCX: ' . $e->getMessage());
        }
    }
}
