<?php

namespace App\Http\Controllers\Cataloging;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Reader\Xls;
use PhpOffice\PhpSpreadsheet\Reader\Csv;
use App\Models\Material;
use DateTime, DB, Exception, Date, Storage;
use Log;
use App\Http\Controllers\ActivityLogController;

class ExcelImportController extends Controller
{
    public function import(Request $request)
    {

        $validated = $request->validate([
            'excel_file' => 'required|file|mimes:xlsx,xls|max:2048'
        ]);

        // Access the validated file
        $file = $validated['excel_file'];

        // Move the file to a temporary location
        $filePath = $file->storeAs('temp', $file->getClientOriginalName());

        // Determine the reader type based on the file extension
        $extension = $file->getClientOriginalExtension();
        switch ($extension) {
            case 'xlsx':
                $reader = new Xlsx();
                break;
            case 'xls':
                $reader = new Xls();
                break;
            default:
                throw new Exception('Unsupported file type');
        }

        // Load the file
        $spreadsheet = $reader->load(storage_path('app/' . $filePath));

        $sheet = $spreadsheet->getSheet(0);

        if (!$sheet) {
            return response()->json(['error' => 'Sheet not found'], 400);
        }

        // Process the spreadsheet
        $sheetData = $sheet->toArray('', true, true, true);

        $headers = $sheetData[1];
        unset($sheetData[1]);
        $hasErrors = false;

        $results = DB::transaction(function () use ($sheetData, $headers) {

            $failed = [];
            $success = [];
            $exceed = false;
            $inputError = false;
            foreach ($sheetData as $row) {
                $book = new Material();
                $accession = '';
                $title = '';

                $book->material_type = 0;
                $book->status = 0;

                foreach ($headers as $column => $header) {
                    $value = $row[$column];

                    if ($value) {
                        switch (strtolower($header)) {
                            case 'acc. number':
                                $book->accession = $value;
                                $accession = $value;
                                break;

                            case 'accession number':
                                $book->accession = $value;
                                $accession = $value;
                                break;

                            case 'date received':
                                if (strtolower($value) == 'n.d.') {
                                    break;
                                }

                                // First attempt to parse with 'M. d, Y' format
                                $dateTime = DateTime::createFromFormat('M. d, Y', $value);

                                if (!$dateTime) {
                                    // If parsing fails, replace periods with hyphens and try with 'm-d-Y' format
                                    $value = str_replace('.', '-', $value);
                                    $dateTime = DateTime::createFromFormat('m-d-Y', $value);
                                }

                                if ($dateTime) {
                                    $book->acquired_date = $dateTime->format('Y-m-d');
                                } else {
                                    break; // Skip to the next iteration
                                }
                                break;

                            case 'location':
                                $book->location = $value;
                                break;

                            case 'call number':
                                $book->call_number = $value;
                                break;

                            case 'author number':
                                $book->author_number = $value;
                                break;

                            case 'author':
                                $authors_array = [];
                                array_push($authors_array, $value);
                                $book->authors = json_encode($authors_array);
                                break;

                            case 'title':
                                $book->title = $value;
                                $title = $value;
                                break;

                            case 'ed.':
                                $book->edition = $value;
                                break;

                            case 'edition':
                                $book->edition = $value;
                                break;

                            case 'pages':
                                $book->pages = $value;
                                break;

                            case 'source of fund':
                                $book->source_of_fund = $value;
                                break;

                            case 'price':
                                $cut = 0;
                                if (str_contains($value, 'Php')) {
                                    $cut = 3;
                                } else if (str_contains($value, '₱')) {
                                    $cut = 1;
                                }

                                $value = substr($value, $cut);
                                $book->price = (float)$value;
                                break;

                            case 'publisher':
                                $book->publisher = $value;
                                break;

                            case 'copyright':
                                if ($value > 1900 && $value <= (new DateTime())->format('Y')) {
                                    $book->copyright = $value;
                                }
                                break;

                            default:
                                break;
                        }
                    } else {
                        $checkHeader = strtolower($header);
                        if ($checkHeader == 'acc. number' || $checkHeader == 'accession number' || $checkHeader == 'title') {
                            $inputError = true;
                            break;
                        }
                    }
                }

                try {
                    $book->save();
                    array_push($success, $book->accession);
                } catch (Exception $e) {
                    if (count($failed) < 5) array_push($failed, ['accession' => $accession, 'title' => $title]);
                    else $exceed = true;
                    continue; // skip saving when it has error
                }
            }
            return ['success' => count($success), 'failed' => $failed, 'exceedingError' => $exceed, 'inputError' => $inputError];
        });

        Storage::delete($filePath);

        $log = new ActivityLogController();

        $logParam = new \stdClass(); // Instantiate stdClass

        $user = $request->user();

        $logParam->system = 'Cataloging';
        $logParam->username = $user->username;
        $logParam->fullname = $user->first_name . ' ' . $user->middle_name . ' ' . $user->last_name . ' ' . $user->ext_name;
        $logParam->position = $user->position;
        $logParam->desc = 'Imported ' . $results['success'] . ' books';

        $log->savePersonnelLog($logParam);

        // Return a response
        $errorText = '';
        if ($results['inputError']) $errorText .= 'Preceding rows have error on values. ';
        if ($results['exceedingError']) $errorText .= 'Number of failed imports exceeds size limit. ';
        if ($results['inputError'] || $results['exceedingError']) $errorText .= 'Kindly check skipped records.';
        if ($results['success'] == 0) $message = 'No books have been imported. Check for duplicates and input errors';
        else $message = 'Imported ' . $results['success'] . ' books successfully';

        return response()->json([
            'message' => $message,
            'success' => $results['success'],
            'failed imports' => $results['failed'],
            'error message' => $errorText
        ], 200);
    }
}
