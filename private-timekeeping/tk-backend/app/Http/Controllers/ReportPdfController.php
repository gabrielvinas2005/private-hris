<?php

namespace App\Http\Controllers;

use App\Helpers\BranchHelper;
use App\Helpers\CompanyHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Traits\ApiResponse;

class ReportPdfController extends Controller
{
    use ApiResponse;

    /**
     * Generate PDF from HTML content
     */
    public function generate(Request $request)
    {
        // Increase execution time limit and memory for PDF generation
        set_time_limit(120); // 2 minutes
        ini_set('memory_limit', '512M');
        
        try {
            $validated = $request->validate([
                'html' => 'required|string',
                'paper_size' => 'nullable|string|in:A4,Letter',
                'orientation' => 'nullable|string|in:portrait,landscape',
                'filename' => 'nullable|string',
                'with_header_footer' => 'nullable|boolean',
                'department' => 'nullable|string',
                'printed_by' => 'nullable|string',
                'title' => 'nullable|string'
            ]);

            $html = $validated['html'];
            $paperSize = $validated['paper_size'] ?? 'A4';
            $orientation = $validated['orientation'] ?? 'portrait';
            $filename = $validated['filename'] ?? ('report_'.date('Ymd_His'));
            $withHeaderFooter = $validated['with_header_footer'] ?? false;
            $department = $validated['department'] ?? 'Department Name';
            $printedBy = $validated['printed_by'] ?? 'System';
            $title = $validated['title'] ?? 'Report';

            // Optimize HTML and fix asset paths
            $html = $this->optimizeHtmlForPdf($html);
            $html = $this->embedPublicImagesAsBase64($html);
            $html = $this->convertLocalhostImagesToFilePaths($html);

            // Wrap with header and footer if requested
            if ($withHeaderFooter) {
                $html = $this->wrapWithHeaderFooter($html, $department, $printedBy, $title);
            } else {
                $html = $this->wrapBody($html);
            }

            // Generate PDF
            $pdf = Pdf::loadHTML($html);
            $pdf->setPaper($paperSize, $orientation);

            return response()->streamDownload(function () use ($pdf) {
                echo $pdf->output();
            }, $filename.'.pdf', [
                'Content-Type' => 'application/pdf'
            ]);
        } catch (\Exception $e) {
            Log::error('PDF generation failed: ' . $e->getMessage());
            return response()->json([
                'error' => 'PDF generation failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate PDF from template and data
     */
    public function generateFromTemplate(Request $request)
    {
        // Increase execution time limit and memory for PDF generation
        set_time_limit(120); // 2 minutes
        ini_set('memory_limit', '512M');
        
        try {
            $validated = $request->validate([
                'template' => 'required|string',
                'data' => 'required|array',
                'paper_size' => 'nullable|string|in:A4,Letter',
                'orientation' => 'nullable|string|in:portrait,landscape',
                'filename' => 'nullable|string',
                'with_header_footer' => 'nullable|boolean',
                'department' => 'nullable|string',
                'printed_by' => 'nullable|string',
                'title' => 'nullable|string'
            ]);

            $template = $validated['template'];
            $data = $validated['data'];
            $paperSize = $validated['paper_size'] ?? 'A4';
            $orientation = $validated['orientation'] ?? 'portrait';
            $filename = $validated['filename'] ?? ('report_'.date('Ymd_His'));
            $withHeaderFooter = $validated['with_header_footer'] ?? false;
            $department = $validated['department'] ?? 'Department Name';
            $printedBy = $validated['printed_by'] ?? 'System';
            $title = $validated['title'] ?? 'Report';

            // Generate HTML from template
            $html = view($template, $data)->render();

            // Optimize HTML and fix asset paths
            $html = $this->optimizeHtmlForPdf($html);
            $html = $this->embedPublicImagesAsBase64($html);
            $html = $this->convertLocalhostImagesToFilePaths($html);

            // Wrap with header and footer if requested
            if ($withHeaderFooter) {
                $html = $this->wrapWithHeaderFooter($html, $department, $printedBy, $title);
            } else {
                $html = $this->wrapBody($html);
            }

            // Generate PDF
            $pdf = Pdf::loadHTML($html);
            $pdf->setPaper($paperSize, $orientation);

            return response()->streamDownload(function () use ($pdf) {
                echo $pdf->output();
            }, $filename.'.pdf', [
                'Content-Type' => 'application/pdf'
            ]);
        } catch (\Exception $e) {
            Log::error('PDF generation from template failed: ' . $e->getMessage());
            return response()->json([
                'error' => 'PDF generation failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate PDF with custom styles
     */
    public function generateWithStyles(Request $request)
    {
        // Increase execution time limit and memory for PDF generation
        set_time_limit(120); // 2 minutes
        ini_set('memory_limit', '512M');
        
        try {
            $validated = $request->validate([
                'html' => 'required|string',
                'styles' => 'nullable|string',
                'paper_size' => 'nullable|string|in:A4,Letter',
                'orientation' => 'nullable|string|in:portrait,landscape',
                'filename' => 'nullable|string',
                'with_header_footer' => 'nullable|boolean',
                'department' => 'nullable|string',
                'printed_by' => 'nullable|string',
                'title' => 'nullable|string'
            ]);

            $html = $validated['html'];
            $styles = $validated['styles'] ?? '';
            $paperSize = $validated['paper_size'] ?? 'A4';
            $orientation = $validated['orientation'] ?? 'portrait';
            $filename = $validated['filename'] ?? ('report_'.date('Ymd_His'));
            $withHeaderFooter = $validated['with_header_footer'] ?? false;
            $department = $validated['department'] ?? 'Department Name';
            $printedBy = $validated['printed_by'] ?? 'System';
            $title = $validated['title'] ?? 'Report';

            // Optimize HTML and fix asset paths
            $html = $this->optimizeHtmlForPdf($html);
            $html = $this->embedPublicImagesAsBase64($html);
            $html = $this->convertLocalhostImagesToFilePaths($html);

            // Wrap with header and footer if requested
            if ($withHeaderFooter) {
                $html = $this->wrapWithHeaderFooter($html, $department, $printedBy, $title, $styles);
            } else {
                $html = $this->wrapBody($html, $styles);
            }

            // Generate PDF
            $pdf = Pdf::loadHTML($html);
            $pdf->setPaper($paperSize, $orientation);

            return response()->streamDownload(function () use ($pdf) {
                echo $pdf->output();
            }, $filename.'.pdf', [
                'Content-Type' => 'application/pdf'
            ]);
        } catch (\Exception $e) {
            Log::error('PDF generation with styles failed: ' . $e->getMessage());
            return response()->json([
                'error' => 'PDF generation failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate PDF for preview (returns PDF content for iframe display)
     */
    public function generatePreview(Request $request)
    {
        // Increase execution time limit and memory for PDF generation
        set_time_limit(120); // 2 minutes
        ini_set('memory_limit', '512M');
        
        try {
            $validated = $request->validate([
                'html' => 'required|string',
                'paper_size' => 'nullable|string|in:A4,Letter',
                'orientation' => 'nullable|string|in:portrait,landscape',
                'filename' => 'nullable|string',
                'with_header_footer' => 'nullable|boolean',
                'department' => 'nullable|string',
                'printed_by' => 'nullable|string',
                'title' => 'nullable|string'
            ]);

            $html = $validated['html'];
            $paperSize = $validated['paper_size'] ?? 'A4';
            $orientation = $validated['orientation'] ?? 'portrait';
            $filename = $validated['filename'] ?? ('report_'.date('Ymd_His'));
            $withHeaderFooter = $validated['with_header_footer'] ?? false;
            $department = $validated['department'] ?? 'Department Name';
            $printedBy = $validated['printed_by'] ?? 'System';
            $title = $validated['title'] ?? 'Report';

            // Optimize HTML and fix asset paths
            $html = $this->optimizeHtmlForPdf($html);
            $html = $this->embedPublicImagesAsBase64($html);
            $html = $this->convertLocalhostImagesToFilePaths($html);

            // Wrap with header and footer if requested
            if ($withHeaderFooter) {
                $html = $this->wrapWithHeaderFooter($html, $department, $printedBy, $title);
            } else {
                $html = $this->wrapBody($html);
            }

            // Generate PDF
            $pdf = Pdf::loadHTML($html);
            $pdf->setPaper($paperSize, $orientation);
            $pdfContent = $pdf->output();

            // Return PDF content directly for preview (not as download)
            $origin = $request->headers->get('Origin', '*');
            return response($pdfContent)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'inline; filename="' . $filename . '"')
                ->header('Access-Control-Allow-Origin', $origin)
                ->header('Access-Control-Allow-Methods', 'POST, OPTIONS')
                ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, Accept')
                ->header('Access-Control-Allow-Credentials', 'true');
        } catch (\Exception $e) {
            Log::error('PDF preview generation failed: ' . $e->getMessage());
            $origin = $request->headers->get('Origin', '*');
            return response()->json([
                'error' => 'PDF preview generation failed',
                'message' => $e->getMessage()
            ], 500)
                ->header('Access-Control-Allow-Origin', $origin)
                ->header('Access-Control-Allow-Methods', 'POST, OPTIONS')
                ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, Accept')
                ->header('Access-Control-Allow-Credentials', 'true');
        }
    }

    /**
     * Generate PDF from data and template for preview
     */
    public function generatePreviewFromTemplate(Request $request)
    {
        // Increase execution time limit and memory for PDF generation
        set_time_limit(120); // 2 minutes
        ini_set('memory_limit', '512M');
        
        try {
            $validated = $request->validate([
                'data' => 'required|array',
                'template' => 'required|string',
                'paper_size' => 'nullable|string|in:A4,Letter',
                'orientation' => 'nullable|string|in:portrait,landscape',
                'filename' => 'nullable|string',
                'with_header_footer' => 'nullable|boolean',
                'department' => 'nullable|string',
                'printed_by' => 'nullable|string',
                'title' => 'nullable|string'
            ]);

            $data = $validated['data'];
            $template = $validated['template'];
            $paperSize = $validated['paper_size'] ?? 'A4';
            $orientation = $validated['orientation'] ?? 'portrait';
            $filename = $validated['filename'] ?? ('report_'.date('Ymd_His'));
            $withHeaderFooter = $validated['with_header_footer'] ?? false;
            $department = $validated['department'] ?? 'Department Name';
            $printedBy = $validated['printed_by'] ?? 'System';
            $title = $validated['title'] ?? 'Report';

            // Generate HTML from template
            $html = view($template, $data)->render();

            // Check if this is a DTR template (has its own complete HTML structure)
            $isDTRTemplate = strpos($template, 'dtr_report') !== false;

            // Optimize HTML and fix asset paths
            $html = $this->optimizeHtmlForPdf($html);
            $html = $this->embedPublicImagesAsBase64($html);
            $html = $this->convertLocalhostImagesToFilePaths($html);

            // Wrap with header and footer if requested, but skip wrapping for DTR template
            if ($withHeaderFooter && !$isDTRTemplate) {
                $html = $this->wrapWithHeaderFooter($html, $department, $printedBy, $title);
            } elseif (!$isDTRTemplate) {
                // Only wrap body if not DTR template (DTR has its own complete HTML structure)
                $html = $this->wrapBody($html);
            }

            // Generate PDF
            $pdf = Pdf::loadHTML($html);
            
            // For DTR template, use custom paper size with explicit margins
            if ($isDTRTemplate) {
                // Set paper size and orientation
                $pdf->setPaper($paperSize, $orientation);
                // DomPDF margins should be set in CSS @page rule (already in template)
                // No need to set margins via setOption as they're in the CSS
            } else {
                $pdf->setPaper($paperSize, $orientation);
            }
            
            $pdfContent = $pdf->output();

            // Return PDF content directly for preview (not as download)
            $origin = $request->headers->get('Origin', '*');
            return response($pdfContent)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'inline; filename="' . $filename . '"')
                ->header('Access-Control-Allow-Origin', $origin)
                ->header('Access-Control-Allow-Methods', 'POST, OPTIONS')
                ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, Accept')
                ->header('Access-Control-Allow-Credentials', 'true');
        } catch (\Exception $e) {
            Log::error('PDF preview generation failed: ' . $e->getMessage());
            $origin = $request->headers->get('Origin', '*');
            return response()->json(['error' => 'PDF preview generation failed', 'message' => $e->getMessage()], 500)
                ->header('Access-Control-Allow-Origin', $origin)
                ->header('Access-Control-Allow-Methods', 'POST, OPTIONS')
                ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, Accept')
                ->header('Access-Control-Allow-Credentials', 'true');
        }
    }

    /**
     * Wrap HTML content with professional header and footer
     */
    private function wrapWithHeaderFooter($html, $department = 'Department Name', $printedBy = 'System', $title = 'Report', $additionalStyles = '')
    {
        $header = $this->generateProfessionalHeader($title);
        $footer = $this->generateProfessionalFooter($printedBy);
        $body = $this->wrapBody($html, $additionalStyles);

        return $this->wrapCompleteDocument($header, $body, $footer);
    }

    /**
     * Generate professional header HTML matching Shift Schedule Report design
     * Header will only appear on first page
     */
    private function generateProfessionalHeader($title = 'Report')
    {
        // Check if logo files exist
        $pttcLogoPath = public_path('dist/img/logo.png');
        $pilipinasLogoPath = public_path('dist/img/reports/Pilipinas_logo.png');
        
        $branchCode = strtoupper(BranchHelper::getMainBranchCode());
        $companyName = htmlspecialchars(strtoupper(CompanyHelper::getName()), ENT_QUOTES, 'UTF-8');

        $pttcLogo = file_exists($pttcLogoPath) ? 
            '<img src="file://' . str_replace('\\', '/', $pttcLogoPath) . '" style="width: 60px; height: 60px; object-fit: contain;">' : 
            '<div style="width: 60px; height: 60px; background: #f0f0f0; border: none; display: flex; align-items: center; justify-content: center; font-size: 8px; color: #666;">' . $branchCode . ' LOGO</div>';
            
        $pilipinasLogo = file_exists($pilipinasLogoPath) ? 
            '<img src="file://' . str_replace('\\', '/', $pilipinasLogoPath) . '" style="width: 60px; height: 60px; object-fit: contain;">' : 
            '<div style="width: 60px; height: 60px; background: #f0f0f0; border: none; display: flex; align-items: center; justify-content: center; font-size: 8px; color: #666;">PILIPINAS LOGO</div>';

        return '
        <div class="pdf-header-first" style="width: 100%; height: 120px; background: white; margin-bottom: 20px; page-break-after: avoid; border: none;">
            <!-- Main Header Section -->
            <div style="height: 120px; border: none;">
                <table style="width: 100%; height: 100%; border-collapse: collapse; border: none;">
                    <tr>
                        <td style="width: 15%; text-align: left; vertical-align: middle; padding: 15px; border: none;">
                            <div style="text-align: center;">
                                ' . $pttcLogo . '
                            </div>
                        </td>
                        <td style="width: 70%; text-align: center; vertical-align: middle; padding: 15px; border: none;">
                            <div style="font-size: 14px; font-weight: bold; color: #222; margin-bottom: 8px;">
                                Department Of Trade and Industry
                            </div>
                            <div style="font-size: 16px; font-weight: bold; color: #000; margin-bottom: 8px;">
                                ' . $companyName . '
                            </div>
                        </td>
                        <td style="width: 15%; text-align: right; vertical-align: middle; padding: 15px; border: none;">
                            <div style="text-align: center;">
                                ' . $pilipinasLogo . '
                            </div>
                        </td>
                    </tr>
                </table>
            </div>
        </div>';
    }

    /**
     * Generate professional footer HTML matching Shift Schedule Report design
     */
    private function generateProfessionalFooter($printedBy = 'System')
    {
        return '
        <div class="pdf-footer" style="position: fixed; bottom: 0; left: 0; right: 0; height: 50px; background: white; border: none; z-index: 1000;">
            <table style="width: 100%; height: 100%; border-collapse: collapse; border: none;">
                <tr>
                    <td style="width: 50%; text-align: left; vertical-align: middle; padding: 10px; border: none;">
                        <div style="font-size: 8px; color: #666;">
                            Printed on: ' . date('F d, Y') . '
                        </div>
                    </td>
                    <td style="width: 50%; text-align: right; vertical-align: middle; padding: 10px; border: none;">
                        <!-- Page number drawn via DomPDF script to ensure values always render (even single page) -->
                    </td>
                </tr>
            </table>
        </div>';
    }

    /**
     * Wrap body content
     */
    private function wrapBody($html, $additionalStyles = '')
    {
        return '
        <div class="pdf-body" style="margin-bottom: 60px; padding: 0;">
            ' . $html . '
        </div>';
    }

    /**
     * Wrap complete document with header, body, and footer
     * Header appears only on first page, footer on all pages
     */
    private function wrapCompleteDocument($header, $body, $footer)
    {
        return '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="utf-8">
            <title>PDF Report</title>
            <style>
                /* Standard page margins with footer space */
                @page {
                    margin: 0.5in 0.75in 1in 0.75in; /* top, right, bottom, left - bottom margin for footer */
                    size: A4;
                }
                
                body {
                    margin: 0;
                    padding: 0;
                    font-family: Arial, sans-serif;
                    font-size: 12px;
                    line-height: 1.4;
                    color: #333;
                }
                
                /* Header - regular content, appears only on first page */
                .pdf-header-first {
                    width: 100%;
                    height: 120px;
                    background: white;
                    margin: 0;
                    padding: 0;
                    page-break-after: avoid;
                    page-break-inside: avoid;
                    position: relative;
                    border: none;
                }
                
                /* Footer - fixed position, appears on all pages */
                .pdf-footer {
                    position: fixed;
                    bottom: 0;
                    left: 0;
                    right: 0;
                    height: 50px;
                    background: white;
                    border: none;
                    z-index: 1000;
                }
                
                /* Body content */
                .pdf-body {
                    margin: 0;
                    padding: 0;
                    page-break-inside: auto;
                }
                
                table {
                    width: 100%;
                    border-collapse: collapse;
                    margin: 10px 0;
                }
                
                th, td {
                    border: 1px solid #ddd;
                    padding: 8px;
                    text-align: left;
                }
                
                th {
                    background-color: #f2f2f2;
                    font-weight: bold;
                }
                
                .page-break {
                    page-break-before: always;
                }
                
                .no-break {
                    page-break-inside: avoid;
                }
            </style>
        </head>
        <body>
            <!-- Header: Regular HTML content - appears only on first page -->
            ' . $header . '
            
            <!-- Body content -->
            ' . $body . '
            
            <!-- Footer: Fixed position - appears on all pages -->
            ' . $footer . '
            
            <script type="text/php">
            if (isset($pdf)) {
                $pageNum = $pdf->get_page_number();
                $pageCount = $pdf->get_page_count();
                
                // Draw page number in footer on all pages
                $text = "Page " . $pageNum . " of " . $pageCount;
                $font = $fontMetrics->get_font("helvetica", "normal");
                $size = 8;
                $w = $fontMetrics->get_text_width($text, $font, $size);
                
                // Calculate position: right-aligned in footer area
                // Page width minus right margin (0.75in = 54pt) minus text width
                $x = $pdf->get_width() - 54 - $w;
                // Page height minus bottom margin (1in = 72pt) + offset to center in footer
                $y = $pdf->get_height() - 72 + 20;
                
                // Draw page number on all pages
                $pdf->page_text($x, $y, $text, $font, $size, array(0.4, 0.4, 0.4));
                
                // Hide header on pages after the first by drawing white rectangle over it
                // This is a workaround since DomPDF doesn\'t easily support conditional headers
                if ($pageNum > 1) {
                    // Header is regular content, so it won\'t appear on subsequent pages naturally
                    // No action needed - header only exists in HTML flow on first page
                }
            }
            </script>
        </body>
        </html>';
    }

    /**
     * Optimize HTML for better PDF generation performance
     */
    private function optimizeHtmlForPdf($html)
    {
        // Remove HTML comments
        $html = preg_replace('/<!--.*?-->/s', '', $html);
        // Remove script tags entirely
        $html = preg_replace('/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi', '', $html);
        // Keep inline styles and <style> tags because we rely on them for layout
        return trim($html);
    }

    /**
     * Convert localhost/127.0.0.1 public URLs to local filesystem paths for DomPDF.
     * This avoids network requests and works even when remote loading is restricted.
     */
    private function convertLocalhostImagesToFilePaths(string $html): string
    {
        $publicPath = public_path();

        // Rewrite any <img src="..."> that points to localhost or a public dist path
        $html = preg_replace_callback(
            '/<img([^>]*)\s+src=["\']([^"\']*)["\']([^>]*)>/i',
            function ($matches) use ($publicPath) {
                $beforeSrc = $matches[1];
                $src = $matches[2];
                $afterSrc = $matches[3];

                // Convert localhost URLs to file paths
                if (preg_match('/^https?:\/\/(?:localhost|127\.0\.0\.1)(?::\d+)?\/storage\/(.+)$/', $src, $pathMatches)) {
                    $filePath = $publicPath . '/storage/' . $pathMatches[1];
                    if (file_exists($filePath)) {
                        $src = 'file://' . str_replace('\\', '/', $filePath);
                    }
                } elseif (preg_match('/^https?:\/\/(?:localhost|127\.0\.0\.1)(?::\d+)?\/(.+)$/', $src, $pathMatches)) {
                    $filePath = $publicPath . '/' . $pathMatches[1];
                    if (file_exists($filePath)) {
                        $src = 'file://' . str_replace('\\', '/', $filePath);
                    }
                }

                return '<img' . $beforeSrc . ' src="' . $src . '"' . $afterSrc . '>';
            },
            $html
        );

        return $html;
    }

    /**
     * Embed public images as base64 to avoid file path issues
     */
    private function embedPublicImagesAsBase64(string $html): string
    {
        $publicPath = public_path();

        $html = preg_replace_callback(
            '/<img([^>]*)\s+src=["\']([^"\']*)["\']([^>]*)>/i',
            function ($matches) use ($publicPath) {
                $beforeSrc = $matches[1];
                $src = $matches[2];
                $afterSrc = $matches[3];

                // Handle relative paths to public directory
                if (!preg_match('/^(https?:|\/|data:)/', $src)) {
                    $filePath = $publicPath . '/' . ltrim($src, '/');
                    if (file_exists($filePath)) {
                        $imageData = file_get_contents($filePath);
                        $mimeType = mime_content_type($filePath);
                        $base64 = base64_encode($imageData);
                        $src = 'data:' . $mimeType . ';base64,' . $base64;
                    }
                }

                return '<img' . $beforeSrc . ' src="' . $src . '"' . $afterSrc . '>';
            },
            $html
        );

        return $html;
    }
}
