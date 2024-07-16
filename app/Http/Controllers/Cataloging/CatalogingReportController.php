<?php

namespace App\Http\Controllers\Cataloging;

use App\Http\Controllers\Controller;
use App\Models\Article, App\Models\Book, App\Models\material;
use Dompdf\Dompdf, Dompdf\Options;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\Project;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Storage;
use App\Models\Periodical, Exception;
use PhpOffice\PhpSpreadsheet\Worksheet\HeaderFooterDrawing;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;


class CatalogingReportController extends Controller
{

    public function getCount() {
        // for books
        $books = Material::where('material_type', 0)->get('title');
        $titles = $books->unique('title')->count();
        $volumes = $books->count();

        // for materials
        $materials = Material::where('material_type', 1)->get('periodical_type');

        $p_count = ['journals' => 0, 'magazines' => 0, 'newspapers' => 0];
        foreach($materials as $x){
            if($x->periodical_type == 0)
                $p_count['journals'] = $p_count['journals'] + 1;
            elseif($x->periodical_type == 1)
                $p_count['magazines'] = $p_count['magazines'] + 1;
            elseif($x->periodical_type == 2)
                $p_count['newspapers'] = $p_count['newspapers'] + 1;
        }

        // for articles
        $articles = Material::where('material_type', 2)->get('title')->count();

        // $projects = Project::get();

        // $pr_count = ['ccs' => 0, 'cahs' => 0, 'ceas' => 0, 'chtm' => 0, 'cba' => 0];
        // foreach($projects as $x) {
        //     switch($x->program->department){
        //         case 'CCS':
        //             $pr_count['ccs'] = $pr_count['ccs'] + 1;
        //             break;
                
        //         case 'CAHS':
        //             $pr_count['cahs'] = $pr_count['cahs'] + 1;
        //             break;

        //         case 'CEAS':
        //             $pr_count['ceas'] = $pr_count['ceas'] + 1;
        //             break;

        //         case 'CHTM':
        //             $pr_count['chtm'] = $pr_count['chtm'] + 1;
        //             break;

        //         case 'CBA':
        //             $pr_count['cba'] = $pr_count['cba'] + 1;
        //             break;

        //         default:
        //             break;
        //         }
        // }

        return response()->json([
            'titles' => $titles,
            'volumes' => $volumes,
            'journals' => $p_count['journals'],
            'magazines' => $p_count['magazines'],
            'newspapers' => $p_count['newspapers'],
            'articles' => $articles,
            // 'ccs' => $pr_count['ccs'],
            // 'cahs' => $pr_count['cahs'],
            // 'ceas' => $pr_count['ceas'],
            // 'chtm' => $pr_count['chtm'],
            // 'cba' => $pr_count['cba']
        ]);
    }

    public function countProjects(Request $request, string $department) {
        
        $categoryCounts = Project::selectRaw('projects.category as category, COUNT(projects.id) as project_count')
                                 ->join('programs', 'projects.program_id', '=', 'programs.id')
                                 ->join('departments', 'programs.department_id', '=', 'departments.id')
                                 ->join('categories', 'projects.category_id', '=', 'categories.id')
                                 ->where('departments.department', $department)
                                 ->groupBy('categories.name')
                                 ->get();
        
        return $categoryCounts;
    }

    public function generateExcel(Request $request, string $type) {

        $keys = collect($request->payload);
        $keys = $keys->keys()->toArray();

        $spreadsheet = new Spreadsheet();

        $startDateParsed = '';
        $endDateParsed = '';
        $copyright = '';

        if(in_array('startDate', $keys))
            $startDateParsed = Carbon::parse($request->payload['startDate'] . ' 00:00:00')->setTimezone('Asia/Manila');
            
        if(in_array('endDate', $keys))
            $endDateParsed = Carbon::parse($request->payload['endDate'] . ' 23:59:59')->setTimezone('Asia/Manila');

        if(in_array('copyright', $keys))
            $copyright = $request->payload['copyright'];

        $materials = null;

        // CONDITIONS FOR QUERY
        $conditions = [
            ['created_at', '>=', $startDateParsed . ' 00:00:00'],
            ['created_at', '<=', $endDateParsed . ' 23:59:59'],
            ['copyright', '=', $copyright],
            ['material_type', '=', $type]
        ];

        // Querying to database
        if($type == 'book') {
            if($copyright) {
                if($startDateParsed && $endDateParsed) {
                    $materials = Book::
                    where([$conditions[0], $conditions[1], $conditions[2]])
                    ->get();
                } elseif($endDateParsed) {
                    $materials = Book::
                    where([$conditions[1], $conditions[2]])
                    ->get();
                } elseif($startDateParsed) {
                    $materials = Book::
                    where([$conditions[0], $conditions[2]])
                    ->get();
                }
            } else {
                if($startDateParsed && $endDateParsed) {
                    $materials = Book::
                    where([$conditions[0], $conditions[1]])
                    ->get();
                } elseif($endDateParsed) {
                    $materials = Book::
                    where([$conditions[1]])
                    ->get();
                } elseif($startDateParsed) {
                    $materials = Book::
                    where([$conditions[0]])
                    ->get();
                }
            }
        } elseif($type == 'journal' || $type == 'magazine' || $type == 'newspaper') {
            if($copyright) {
                if($startDateParsed && $endDateParsed) {
                    $materials = Periodical::
                    where($conditions)
                    ->get();
                } elseif($endDateParsed) {
                    $materials = Periodical::
                    where([$conditions[1], $conditions[2], $conditions[3]])
                    ->get();
                } elseif($startDateParsed) {
                    $materials = Periodical::
                    where([$conditions[0], $conditions[2], $conditions[3]])
                    ->get();
                }
            } else {
                if($startDateParsed && $endDateParsed) {
                    $materials = Periodical::
                    where([$conditions[0], $conditions[1], $conditions[3]])
                    ->get();
                } elseif($endDateParsed) {
                    $materials = Periodical::
                    where([$conditions[1], $conditions[3]])
                    ->get();
                } elseif($startDateParsed) {
                    $materials = Periodical::
                    where([$conditions[0], $conditions[3]])
                    ->get();
                }
            } 
        } elseif($type == 'article') {
            if($copyright) {
                if($startDateParsed && $endDateParsed) {
                    $materials = Article::
                    where($conditions)
                    ->get();
                } elseif($endDateParsed) {
                    $materials = Article::
                    where([$conditions[1], $conditions[2]])
                    ->get();
                } elseif($startDateParsed) {
                    $materials = Article::
                    where([$conditions[0], $conditions[2]])
                    ->get();
                }
            } else {
                if($startDateParsed && $endDateParsed) {
                    $materials = Article::
                    where([$conditions[0], $conditions[1]])
                    ->get();
                } elseif($endDateParsed) {
                    $materials = Article::
                    where([$conditions[1]])
                    ->get();
                } elseif($startDateParsed) {
                    $materials = Article::
                    where([$conditions[0]])
                    ->get();
                }
            } 
        } else if($type == 'projects.all') {
            if($startDateParsed && $endDateParsed) {
                $materials = Project::with('program')
                    ->where([$conditions[0], $conditions[1]])
                    ->get();
            } elseif($endDateParsed) {
                $materials = Project::with('program')
                    ->where([$conditions[1]])
                    ->get();
            } elseif($startDateParsed) {
                $materials = Project::with('program')
                    ->where([$conditions[0]])
                    ->get();
            }
        } else if($type == 'projects.ccs') {
            if($startDateParsed && $endDateParsed) {
                $materials = Project::with('program.department')
                    ->where([$conditions[0], $conditions[1]])
                    ->get();

            } elseif($endDateParsed) {
                $materials = Project::
                    where([$conditions[1]])
                    ->get();
            } elseif($startDateParsed) {
                $materials = Project::
                    where([$conditions[0]])
                    ->get();
            }
            $materials = $materials->where('program.department.department', 'CCS');
        } else if($type == 'projects.cahs') {
            if($startDateParsed && $endDateParsed) {
                $materials = Project::with('program.department')
                    ->where([$conditions[0], $conditions[1]])
                    ->get();

            } elseif($endDateParsed) {
                $materials = Project::
                    where([$conditions[1]])
                    ->get();
            } elseif($startDateParsed) {
                $materials = Project::
                    where([$conditions[0]])
                    ->get();
            }
            $materials = $materials->where('program.department.department', 'CAHS');
        } else if($type == 'projects.chtm') {
            if($startDateParsed && $endDateParsed) {
                $materials = Project::with('program.department')
                    ->where([$conditions[0], $conditions[1]])
                    ->get();

            } elseif($endDateParsed) {
                $materials = Project::
                    where([$conditions[1]])
                    ->get();
            } elseif($startDateParsed) {
                $materials = Project::
                    where([$conditions[0]])
                    ->get();
            }
            $materials = $materials->where('program.department.department', 'CHTM');
        } else if($type == 'projects.cba') {
            if($startDateParsed && $endDateParsed) {
                $materials = Project::with('program')
                    ->where([$conditions[0], $conditions[1]])
                    ->get();

            } elseif($endDateParsed) {
                $materials = Project::
                    where([$conditions[1]])
                    ->get();
            } elseif($startDateParsed) {
                $materials = Project::
                    where([$conditions[0]])
                    ->get();
            }
            $materials = $materials->where('program.department.department', 'CBA');
        } else if($type == 'projects.ceas') {
            if($startDateParsed && $endDateParsed) {
                $materials = Project::with('program.department')
                    ->where([$conditions[0], $conditions[1]])
                    ->get();

            } elseif($endDateParsed) {
                $materials = Project::
                    where([$conditions[1]])
                    ->get();
            } elseif($startDateParsed) {
                $materials = Project::
                    where([$conditions[0]])
                    ->get();
            }
            $materials = $materials->where('program.department.department', 'CEAS');
        }

        $material_arr = [];
        foreach($materials as $x) {
            $x_date = Carbon::parse($x['copyright']);
            $x['authors'] = json_decode($x['authors']);
            $authors = "";

            for($i = 0; $i < count($x['authors']); $i++) {
                $authors = $authors . $x['authors'][$i];
                if($i < count($x['authors'])-1)
                    $authors = $authors . ",\n";
            }
            
            if($type == 'book') {
                array_push($material_arr, [
                    $x['id'], $x_date->format('m.d.Y'), $x['call_number'], $authors, $x['title'],
                    $x['edition'], $x['pages'], $x['source_of_fund'],
                ]);
            } elseif($type == 'journal' || $type == 'magazine' || $type == 'newspaper') {
                array_push($material_arr, [
                    $x['accession'], $x['title'], $authors, $x['copyright'], Carbon::parse($x['acquired_date'])->format('m.d.Y'),
                ]);
            } elseif($type == 'article') {
                array_push($material_arr, [
                    $x['accession'], $x['title'], $authors, Carbon::parse($x['date_published'])->format('m.d.Y'),
                ]);
            } elseif($type == 'projects.all') {
                array_push($material_arr, [
                    $x['program']['department']['department'], $x['category'], $x['title'], $authors, 
                    Carbon::parse($x['date_published'])->format('m.d.Y'), Carbon::parse($x['created_at'])->format('m.d.Y')
                ]);
            } elseif($type == 'projects.ccs' || $type == 'projects.ceas' || $type == 'projects.cba' || 
            $type == 'projects.chtm' || $type == 'projects.cahs') {
                array_push($material_arr, [
                    $x['category'], $x['title'], $authors, Carbon::parse($x['date_published'])->format('m.d.Y'), 
                    Carbon::parse($x['created_at'])->format('m.d.Y')
                ]);
            }
        }

        $date = Carbon::now('Asia/Singapore')->format('Y-m-d');
        $title = 'Cataloging Report_' . ($type) . '_' . $date;

        $spreadsheet->getProperties()->setCreator('GC LMS')
                    ->setLastModifiedBy('GC LMS')
                    ->setTitle($title)
                    ->setSubject('LMS Cataloging Report')
                    ->setDescription('Cataloging report from GC LMS');

        $sheet = $spreadsheet->getActiveSheet();

        $sheet->getPageSetup()->setHorizontalCentered(true);
        $sheet->getPageSetup()->setVerticalCentered(false);
        $sheet->getPageSetup()->setFitToWidth(null, false);

        $sheet->getPageSetup()
            ->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE);
        $sheet->getPageSetup()
            ->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_LEGAL);
            
        $sheet->fromArray($material_arr, null, 'A3')
        ->calculateColumnWidths();
        
        if($type == 'book')
            $sheet->mergeCells('A1:H1');
        elseif($type == 'article')
            $sheet->mergeCells('A1:D1');
        elseif($type == 'journal' || $type == 'magazine' || $type == 'newspaper' || $type == 'projects.ccs' || 
                $type == 'projects.ceas' || $type == 'projects.cba' || $type == 'projects.chtm' || $type == 'projects.cahs')
            $sheet->mergeCells('A1:E1');
        elseif($type == 'projects.all')
            $sheet->mergeCells('A1:F1');

        $headerStyleArray = [
            'font' => [
                'bold' => true,
                'family' => 'Montserrat',
                'color' => ['argb' => 'FFFFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => '31A463'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => 'FF000000'],
                ],
            ],
        ];

        // Set data cell style
        $dataStyleArray = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => 'FF000000'],
                ],
            ],
        ];

        if($type == 'book') {
            $headers = [
                "ACC.\nNUMBER", 
                "DATE RECEIVED", 
                "CALL\nNUMBER", 
                "AUTHOR", 
                "TITLE", 
                "EDITION", 
                "PAGES", 
                "SOURCE OF FUND"
            ];

            $limit = 'H';
        } elseif($type == 'journal' || $type == 'magazine' || $type == 'newspaper') {
            $headers = [
                "JOURNAL\nACCESSION",
                "TITLE", 
                "AUTHOR", 
                "COPYRIGHT", 
                "DATE RECEIVED"
            ];

            $limit = 'E';
        } elseif($type == 'article') {
            $headers = [
                "ARTICLE\nACCESSION",
                "TITLE", 
                "AUTHOR/S",
                "PUBLICATION DATE"
            ];

            $limit = 'D';
        } elseif ($type == 'projects.all') {
            $headers = [
                "DEPARTMENT",
                "PROJECT TYPE",
                "PROJECT TITLE",
                "AUTHOR/S",
                "DATE PUBLISHED",
                "DATE ADDED"
            ];

            $limit = 'F';
        } elseif ($type == 'projects.ccs' || $type == 'projects.ceas' || $type == 'projects.cba' || 
        $type == 'projects.chtm' || $type == 'projects.cahs') {
            $headers = [
                "PROJECT TYPE",
                "PROJECT TITLE",
                "AUTHOR/S",
                "DATE PUBLISHED",
                "DATE ADDED"
            ];

            $limit = 'E';
        }
       
        // Insert headers into the spreadsheet
        $sheet->fromArray([$headers], null, 'A2');
        $sheet->getStyle('A2:'.$limit.'2')->applyFromArray($headerStyleArray);

        // Insert data
        $sheet->fromArray($material_arr, null, 'A3');
        $lastRow = count($material_arr) + 2; // Add 2 because the headers are in the second row
        $sheet->getStyle('A3:'.$limit. $lastRow)->applyFromArray($dataStyleArray);

        $sheet->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(2, 2);

        $dimension = $sheet->calculateWorksheetDimension();
        $sheet->getStyle($dimension)->getAlignment()->setWrapText(true);
        
        // HEADER
        $sheet->getStyle('A1')->getAlignment()
            ->setVertical(Alignment::VERTICAL_CENTER)
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);
        
        $sheet->getRowDimension(1)->setRowHeight(130);
        $sheet->getRowDimension(2)->setRowHeight(40);

        $richText = new RichText();
        $normalText = $richText->createTextRun("Republic of the Philippines\nCity of Olongapo\n");
        $normalText->getFont()->setSize(12);
        $boldText = $richText->createTextRun("GORDON COLLEGE\n");
        $boldText->getFont()->setBold(true)->setSize(12);
        $smallText = $richText->createTextRun("Olongapo City Sports Complex, Donor St., East Tapinac, Olongapo City\n" .
        "Tel. No.: (047) 224-2089 loc. 401\n\n");
        $smallText->getFont()->setSize(10);
        $boldText = $richText->createTextRun("LIBRARY AND INSTRUCTIONAL MEDIA CENTER\n");
        $boldText->getFont()->setBold(true)->setSize(12);
        
        $sheet->setCellValue('A1', $richText);

        $imagePath = public_path('storage/cataloging assets/GC LIBRARY.png'); // Path to your image file
        $drawing = new Drawing();
        $drawing->setName('Image');
        $drawing->setDescription('Image');
        $drawing->setPath($imagePath);
        $drawing->setCoordinates('A1'); // Positioning
        $drawing->setHeight(130); // Height in pixels
        $drawing->setWorksheet($sheet);

        $imagePath = public_path('storage/cataloging assets/GC.png'); // Path to your image file
        $drawing = new Drawing();
        $drawing->setName('Image');
        $drawing->setDescription('Image');
        $drawing->setPath($imagePath);
        $drawing->setCoordinates($limit.'1'); // Positioning
        $drawing->setHeight(130); // Height in pixels
        $drawing->setWorksheet($sheet);

        if($type == 'book') {
            $drawing->setOffsetX(40);
        }
        // END OF HEADER 

        // BOOK TABLE DESIGN
        if($type == 'book') {
            $sheet->getColumnDimension('A')->setWidth(15);
            $sheet->getColumnDimension('B')->setWidth(15);
            $sheet->getColumnDimension('C')->setWidth(15);
            $sheet->getColumnDimension('D')->setWidth(20);
            $sheet->getColumnDimension('E')->setWidth(30);
            $sheet->getColumnDimension('F')->setWidth(15);
            $sheet->getColumnDimension('G')->setWidth(15);
            $sheet->getColumnDimension('H')->setWidth(25);
            
            $centeredColumns = ['A', 'B', 'C', 'F', 'G', 'H'];
            foreach ($centeredColumns as $column) {
                $sheet->getStyle($column . '3:' . $column . $sheet->getHighestDataRow())
                    ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            }
        } elseif($type == 'journal' || $type == 'magazine' || $type == 'newspaper') {
            $sheet->getColumnDimension('A')->setWidth(15);
            $sheet->getColumnDimension('B')->setWidth(30);
            $sheet->getColumnDimension('C')->setWidth(30);
            $sheet->getColumnDimension('D')->setWidth(15);
            $sheet->getColumnDimension('E')->setWidth(15);
            
            $centeredColumns = ['A', 'D', 'E'];
            foreach ($centeredColumns as $column) {
                $sheet->getStyle($column . '3:' . $column . $sheet->getHighestDataRow())
                    ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            }
        } elseif($type == 'article') {
            $sheet->getColumnDimension('A')->setWidth(15);
            $sheet->getColumnDimension('B')->setWidth(40);
            $sheet->getColumnDimension('C')->setWidth(30);
            $sheet->getColumnDimension('D')->setWidth(17);
            
            $centeredColumns = ['A', 'D'];
            foreach ($centeredColumns as $column) {
                $sheet->getStyle($column . '3:' . $column . $sheet->getHighestDataRow())
                    ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            }
        } elseif($type == 'projects.all') {
            $sheet->getColumnDimension('A')->setWidth(15);
            $sheet->getColumnDimension('B')->setWidth(15);
            $sheet->getColumnDimension('C')->setWidth(30);
            $sheet->getColumnDimension('D')->setWidth(30);
            $sheet->getColumnDimension('E')->setWidth(15);
            $sheet->getColumnDimension('F')->setWidth(15);
            
            $centeredColumns = ['A', 'B', 'D', 'E', 'F'];
            foreach ($centeredColumns as $column) {
                $sheet->getStyle($column . '3:' . $column . $sheet->getHighestDataRow())
                    ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            }
        } elseif($type == 'projects.ccs' || $type == 'projects.ceas' || $type == 'projects.cba' || 
            $type == 'projects.chtm' || $type == 'projects.cahs') {
            $sheet->getColumnDimension('A')->setWidth(15);
            $sheet->getColumnDimension('B')->setWidth(30);
            $sheet->getColumnDimension('C')->setWidth(30);
            $sheet->getColumnDimension('D')->setWidth(15);
            $sheet->getColumnDimension('E')->setWidth(15);
            
            $centeredColumns = ['A', 'D', 'E'];
            foreach ($centeredColumns as $column) {
                $sheet->getStyle($column . '3:' . $column . $sheet->getHighestDataRow())
                    ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            }
        }
        
        $writer = new Xlsx($spreadsheet);
        $writer->save($title . '.xlsx');

        return response()->download($title . '.xlsx')->deleteFileAfterSend(true);
    }
}
