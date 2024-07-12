<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AnalyticsController extends Controller
{
    public function getTotalLockers()
    {
        $totalLockers = DB::table('lockers')->count();

        return response()->json(['total_lockers' => $totalLockers]);
    }

    public function getTotalActiveUsers()
    {
        $totalActiveUsers = DB::table('lockers')->where('status', 1)->count();

        return response()->json(['total_active_users' => $totalActiveUsers]);
    }

    public function getTotalUsersPerDepartment()
    {
            $usersPerDepartment = DB::table('users')
                ->join('programs', 'users.program', '=', 'programs.program_short')
                ->select('programs.department_short', DB::raw('COUNT(users.id) as user_count'))
                ->groupBy('programs.department_short')
                ->get();

            return response()->json($usersPerDepartment);

    }
    public function getTotalMaterials()
    {
        try {
            $totalMaterials = DB::table('materials')
                ->select(DB::raw('COUNT(accession) as total'))
                ->first();

            return response()->json($totalMaterials);
        } catch (\Exception $e) {
            Log::error('Error fetching total materials: ' . $e->getMessage());
            return response()->json(['error' => 'Internal Server Error'], 500);
        }
    }
    public function getTotalProjects()
    {
        try {
            $totalProjects = DB::table('academic_projects')
                ->select(DB::raw('COUNT(accession) as total'))
                ->first();

            return response()->json($totalProjects);
        } catch (\Exception $e) {
            Log::error('Error fetching total projects: ' . $e->getMessage());
            return response()->json(['error' => 'Internal Server Error'], 500);
        }
    }

    public function getTotalBorrowed()
    {
        try {
            $totalBorrowed = DB::table('materials')
                ->where('status', '1')
                ->count();

            return response()->json(['total_borrowed' => $totalBorrowed]);
        } catch (\Exception $e) {
            Log::error('Error fetching total borrowed: ' . $e->getMessage());
            return response()->json(['error' => 'Internal Server Error'], 500);
        }
    }
    public function getAvailableBooks()
    {
        try {
            $availableBooks = DB::table('materials')
                ->where('material_type', 0) // 0 = books
                ->where('status', '0')
                ->count();

            return response()->json(['available_books' => $availableBooks]);
        } catch (\Exception $e) {
            Log::error('Error fetching available books: ' . $e->getMessage());
            return response()->json(['error' => 'Internal Server Error'], 500);
        }
    }
    public function totalUnavailableBooks()
    {
        // 3 -> unavailable
        $totalUnavailableBooks = DB::table('materials')
            ->where('status', 3)
            ->count();

        return response()->json(['totalUnavailableBooks' => $totalUnavailableBooks]);
    }

    public function totalOccupiedBooks()
    {
        // 1 -> borrowed, 2 -> reserved
        $totalOccupiedBooks = DB::table('materials')
            ->whereIn('status', [1, 2])
            ->count();

        return response()->json(['totalOccupiedBooks' => $totalOccupiedBooks]);
    }

    public function getUnreturnedBooks()
    {
        try {
            $unreturnedBooks = DB::table('materials')
                ->where('inventory_status', '1')
                ->count();

            return response()->json(['unreturned_books' => $unreturnedBooks]);
        } catch (\Exception $e) {
            Log::error('Error fetching unreturned books: ' . $e->getMessage());
            return response()->json(['error' => 'Internal Server Error'], 500);
        }
    }
    public function getMissingBooks()
    {
        try {
            $missingBooks = DB::table('materials')
                ->where('material_type', 0) // 0 = books
                ->where('inventory_status', '2')
                ->count();

            return response()->json(['missing_books' => $missingBooks]);
        } catch (\Exception $e) {
            Log::error('Error fetching missing books: ' . $e->getMessage());
            return response()->json(['error' => 'Internal Server Error'], 500);
        }
    }

    public function mostBorrowedBooks()
    {
        $mostBorrowedBooks = DB::table('borrow_materials')
            ->join('materials', 'borrow_materials.book_id', '=', 'materials.accession')
            ->select('materials.title', DB::raw('COUNT(borrow_materials.book_id) as borrow_count'))
            ->groupBy('materials.title')
            ->orderBy('borrow_count', 'desc')
            ->limit(10)
            ->get();

        return response()->json($mostBorrowedBooks);
    }
    public function mostBorrowedBooksByDepartment()
    {
        $mostBorrowedBooksByDepartment = DB::table('borrow_materials')
            ->join('materials', 'borrow_materials.book_id', '=', 'materials.accession')
            ->join('users', 'borrow_materials.user_id', '=', 'users.id')
            ->join('programs', 'users.program', '=', 'programs.program_short')
            ->select('programs.department_short', 'materials.title', DB::raw('COUNT(borrow_materials.book_id) as borrow_count'))
            ->groupBy('programs.department_short', 'materials.title')
            ->orderBy('programs.department_short')
            ->orderBy('borrow_count', 'desc')
            ->limit(10)
            ->get();

        return response()->json($mostBorrowedBooksByDepartment);
    }
    public function topBorrowers()
    {
        $topBorrowers = DB::table('borrow_materials')
            ->join('users', 'borrow_materials.user_id', '=', 'users.id')
            ->select('users.id', 'users.username', DB::raw('COUNT(borrow_materials.book_id) as books_borrowed'), DB::raw('MAX(borrow_materials.borrow_date) as last_borrow_date'))
            ->groupBy('users.id', 'users.username')
            ->orderBy('books_borrowed', 'desc')
            ->limit(10)
            ->get();

        return response()->json($topBorrowers);
    }
    public function getTotalPeriodicals()
    {
        $totalPeriodicals = DB::table('materials')
            ->where('material_type', 1) // 1 -> periodicals
            ->count();

        return response()->json(['totalPeriodicals' => $totalPeriodicals]);
    }

    public function getTotalArticles()
    {
        $totalArticles = DB::table('materials')
            ->where('material_type', 2) // 2 -> articles
            ->count();

        return response()->json(['totalArticles' => $totalArticles]);
    }

    public function getTotalProjectsByDepartment()
    {
        $totalProjectsByDepartment = DB::table('academic_projects')
            ->join('programs', 'academic_projects.program', '=', 'programs.program_short')
            ->select('programs.department_short', DB::raw('count(*) as total_projects'))
            ->groupBy('programs.department_short')
            ->get();

        return response()->json($totalProjectsByDepartment);
    }
    public function getLockerVisits()
    {
        $lockerVisits = DB::table('lockers_logs')
            ->select('user_id', DB::raw('count(id) as visits'))
            ->groupBy('user_id')
            ->get();

        return response()->json($lockerVisits);
    }

}