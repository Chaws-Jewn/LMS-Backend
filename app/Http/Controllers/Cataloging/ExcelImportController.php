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

class ExcelImportController extends Controller
{
    // public function import(Request $request) {

    //     $validated = $request->validate([
    //         'excel_file' => 'required|file|mimes:xlsx,xls|max:2048'
    //     ]);

    //     // Access the validated file
    //     $file = $validated['excel_file'];

    //     // Move the file to a temporary location
    //     $filePath = $file->storeAs('temp', $file->getClientOriginalName());

    //     // Determine the reader type based on the file extension
    //     $extension = $file->getClientOriginalExtension();
    //     switch ($extension) {
    //         case 'xlsx':
    //             $reader = new Xlsx();
    //             break;
    //         case 'xls':
    //             $reader = new Xls();
    //             break;
    //         default:
    //             throw new Exception('Unsupported file type');
    //     }

    //     // Load the file
    //     $spreadsheet = $reader->load(storage_path('app/' . $filePath));

    //     $sheet = $spreadsheet->getSheet(0);

    //     if (!$sheet) {
    //         return response()->json(['error' => 'Sheet not found'], 400);
    //     }

    //     // Process the spreadsheet
    //     $sheetData = $sheet->toArray(null, true, true, true);

    //     $headers = $sheetData[1];
    //     unset($sheetData[1]);

    //     $values = []; $i = 0; $hasErrors = false;
    //     foreach ($sheetData as $row) {

    //         $invalid = false;
    //         array_push($values, []);
    //         $values[$i]['material_type'] = 0;
    //         $values[$i]['status'] = 1;
    //         $values[$i]['inventory_status'] = 0;

    //         foreach ($headers as $column => $header) {
    //             $value = $row[$column];

    //             if($value != '') {
    //                 switch(strtolower($header)) {
    //                     case 'acc. number':
    //                         $values[$i]['accession'] = $value;
    //                         if(!$value) $invalid = true;
    //                         break;

    //                     case 'accession number':
    //                         $values[$i]['accession'] = $value;
    //                         if(!$value) $invalid = true;
    //                         break;
                        
    //                     case 'date received':
    //                         if (strtolower($value) == 'n.d.') {
    //                             $values[$i]['acquired_date'] = '';
    //                             break;
    //                         }
                    
    //                         // First attempt to parse with 'M. d, Y' format
    //                         $dateTime = DateTime::createFromFormat('M. d, Y', $value);
                    
    //                         if (!$dateTime) {
    //                             // If parsing fails, replace periods with hyphens and try with 'm-d-Y' format
    //                             $value = str_replace('.', '-', $value);
    //                             $dateTime = DateTime::createFromFormat('m-d-Y', $value);
    //                         }
                    
    //                         if ($dateTime) {
    //                             $values[$i]['acquired_date'] = $dateTime->format('Y-m-d');
    //                         } else {
    //                             $values[$i]['acquired_date'] = '';
    //                         }
    //                         break;                                
                        
    //                     case 'location':
    //                         if ($value) $values[$i]['location'] = $value;
    //                         else $values[$i]['location'] = '';
    //                         break;
                        
    //                     case 'call number':
    //                         if($value) $values[$i]['call_number'] = $value;
    //                         else $values[$i]['call_number'] = '';
    //                         break;

    //                     case 'author number':
    //                         if($value) $values[$i]['author_number'] = $value;
    //                         else $values[$i]['author_number'] = '';
    //                         break;

    //                     case 'author':
    //                         $authors_array = [];
    //                         array_push($authors_array, $value);
    //                         if($value) $values[$i]['authors'] = json_encode($authors_array);
    //                         else $values[$i]['authors'] = '';
    //                         break;
                        
    //                     case 'authors':
    //                         $authors_array = [];
    //                         array_push($authors_array, $value);
    //                         if($value) $values[$i]['authors'] = json_encode($authors_array);
    //                         else $values[$i]['authors'] = '';
    //                         break;

    //                     case 'title':
    //                         if($value) $values[$i]['title'] = $value;
    //                         else { $values[$i]['title'] = ''; $invalid = true; }
    //                         break;
    
    //                     case 'ed.':
    //                         if($value) $values[$i]['edition'] = $value;
    //                         else $values[$i]['edition'] = '';
    //                         break;
    
    //                     case 'edition':
    //                         if($value) $values[$i]['edition'] = $value;
    //                         else $values[$i]['edition'] = '';
    //                         break;
    
    //                     case 'pages':
    //                         if($value) $values[$i]['pages'] = $value;
    //                         else $values[$i]['pages'] = '';
    //                         break;
    
    //                     case 'source of fund':
    //                         if($value) $values[$i]['source_of_fund'] = $value;
    //                         else $values[$i]['source_of_fund'] = '';
    //                         break;
    
    //                     case 'price':
    //                         if(empty($value)) { $values[$i]['price'] = ''; break; }
    //                         $cut = 0;
    //                         if(str_contains($value, 'Php')) {
    //                             $cut = 3;
    //                         } else if(str_contains($value, '₱')) {
    //                             $cut = 1;
    //                         }

    //                         $value = substr($value, $cut);
    //                         $values[$i]['price'] = (float)$value;
    //                         break;
    
    //                     case 'publisher':
    //                         if($value) $values[$i]['publisher'] = $value;
    //                         else $values[$i]['publisher'] = '';
    //                         break;
                        
    //                     case 'copyright':
    //                         if($value && $value > 1900 && $value <= (new DateTime())->format('Y')) {
    //                             $values[$i]['copyright'] = $value;
    //                         } else $values[$i]['copyright'] = '';
    //                         break;
    
    //                     default:
    //                         break;
    //                 }
    //             }
    //         }
    //         if($invalid) continue;
    //         $i++;
    //     }

    //     Storage::delete($filePath);
        
    //     $chunkSize = 100;

    //     $chunks = array_chunk($values, $chunkSize);

    //     // try {
    //         foreach($chunks as $chunk){
    //             Material::insert($chunk);
    //         }
    //     // } catch (Exception $e) {
    //     //     return $e;
    //     // }
        
       
    //     if($hasErrors) return response()->json(['message' => 'Materials imported successfully, skipped records with errors', 'count' => count($values)], 200);
    //     else return response()->json(['message' => 'Materials imported successfully', 'count' => count($values)], 200);
    // }

    public function import(Request $request) {

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
        unset($sheetData[1]);$hasErrors = false;

        $results = DB::transaction(function() use ($sheetData, $headers) {

            $failed = []; $exceed = false; $inputError = false;
            foreach ($sheetData as $row) {
                $book = new Material();
                $accession = '';
                $title = '';

                $book->material_type = 0;
                $book->status = 1;
                $book->inventory_status = 0;

                foreach ($headers as $column => $header) {
                    $value = $row[$column];

                    if($value) {
                        switch(strtolower($header)) {
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
                                if(str_contains($value, 'Php')) {
                                    $cut = 3;
                                } else if(str_contains($value, '₱')) {
                                    $cut = 1;
                                }

                                $value = substr($value, $cut);
                                $book->price = (float)$value;
                                break;
        
                            case 'publisher':
                                $book->publisher = $value;
                                break;
                            
                            case 'copyright':
                                if($value > 1900 && $value <= (new DateTime())->format('Y')) {
                                    $book->copyright = $value;
                                }
                                break;
        
                            default:
                                break;
                        }
                    } else {
                        $checkHeader = strtolower($header);
                        if($checkHeader == 'acc. number' || $checkHeader == 'accession number' || $checkHeader == 'title') { $inputError = true; break; }
                    }
                }

                try {
                    $book->save();
                } catch (Exception $e) {
                    if(count($failed) < 5) array_push($failed, ['accession' => $accession, 'title' => $title]);
                    else $exceed = true;
                    continue; // skip saving when it has error
                }
            } return ['failed' => $failed, 'exceedingError' => $exceed, 'inputError' => $inputError];
        });

        Storage::delete($filePath);

        // Return a response
        $errorText = '';
        if($results['inputError']) $errorText .= 'Preceding rows have error on values. ';
        if($results['exceedingError']) $errorText .= 'Number of failed imports exceeds size limit. ';
        if($results['inputError'] || $results['exceedingError']) $errorText .= 'Kindly check skipped records.';

        return response()->json(['message' => 'File uploaded and data imported successfully.',
                                'failed imports' => $results['failed'],
                                'error message' => $errorText]);
    }
}
