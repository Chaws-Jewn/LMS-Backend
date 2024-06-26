<?php

use App\Http\Controllers\Cataloging\ExcelImportController;
use App\Http\Controllers\Cataloging\MaterialViewController;
use App\Http\Controllers\ImageController;
use App\Http\Controllers\OPAC\OPACMaterialsController;
use App\Http\Controllers\ProgramController;
use App\Http\Controllers\StudentPortal\StudentSearchController;
use App\Http\Controllers\StudentPortal\StudentViewController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController, App\Http\Controllers\CatalogingLogController, App\Http\Controllers\Cataloging\ArticleController,
App\Http\Controllers\Cataloging\BookController, App\Http\Controllers\Cataloging\PeriodicalController, App\Http\Controllers\Cataloging\ProjectController,
App\Http\Controllers\CatalogingReportController, App\Http\Controllers\Cataloging\MaterialArchiveController;

use App\Http\Controllers\BorrowBookController,App\Http\Controllers\BorrowMaterialController,
App\Http\Controllers\ReserveBookController, App\Http\Controllers\ReservationController;

use App\Http\Controllers\LockerController;

use App\Http\Controllers\CirculationUserController, App\Http\Controllers\UserController, App\Http\Controllers\PatronController,
App\Http\Controllers\CollegeController, App\Http\Controllers\InventoryController, App\Http\Controllers\LocationController,
App\Http\Controllers\AnnouncementController, App\Http\Controllers\LockerHistoryController;

Route::post('/studentlogin', [AuthController::class, 'studentLogin']);
Route::get('/', function (Request $request) {
    return response()->json(['Response' => 'API routes are available']);
});

// logged in user tester
Route::get('/user', [AuthController::class, 'user'])->middleware('auth:sanctum');

// Auth Routes
Route::post('/login', [AuthController::class, 'login']);

Route::group(['middleware' => ['auth:sanctum']], function () {
    Route::post('/refresh', [AuthController::class, 'refreshToken']);
    Route::post('/logout', [AuthController::class, 'logout']);
});

// Maintenance route
Route::middleware(['auth:sanctum', 'ability:maintenance'])->group(function () {
    Route::get('/personnels', [UserController::class, 'index']);
    Route::post('/personnels', [UserController::class, 'store']);
    Route::get('/personnels/{personnel}', [UserController::class, 'show']);
    Route::post('/personnels/{personnel}', [UserController::class, 'update']);
    Route::delete('/personnels/{personnel}', [UserController::class, 'destroy']);

    //Inventory routes
    Route::prefix('/inventory')->group(function () {
        Route::prefix('/books')->group(function () {
            Route::get('/clear', [InventoryController::class, 'clearBooksHistory']);
            Route::get('/{filter}', [InventoryController::class, 'getBookInventory']);
            Route::get('/search/{filter}', [InventoryController::class, 'searchBookInventory']);
            Route::post('/{id}', [InventoryController::class, 'updateBookStatus']);
        });
        // Route::get('/', [InventoryController::class, 'index']);
        // Route::post('/enter', [InventoryController::class, 'enterBarcode']);
        // Route::post('/scan', [InventoryController::class, 'scanBarcode']);
        // Route::post('/clear', [InventoryController::class, 'clearHistory']);
    });

    //circulation 
    Route::get('/patrons', [PatronController::class, 'index']);
    Route::get('/patrons/{id}', [PatronController::class, 'edit']);
    Route::post('/patrons/{id}', [PatronController::class, 'update']);

    //announcements
    Route::get('/announcements', [AnnouncementController::class, 'index']);
    Route::post('/announcements', [AnnouncementController::class, 'store']);
    Route::get('/announcements/{announcement}', [AnnouncementController::class, 'show']);
    Route::post('/announcements/{announcement}', [AnnouncementController::class, 'update']);
    Route::delete('/announcements/{announcement}', [AnnouncementController::class, 'destroy']);

    //cataloging
    Route::get('/locations', [LocationController::class, 'getLocations']);
    Route::post('/locations', [LocationController::class, 'create']);

    Route::prefix('maintenance/lockers')->group(function () {
        Route::get('/', [LockerController::class, 'index']);
        Route::post('/', [LockerController::class, 'store']);
        Route::get('/latest', [LockerController::class, 'getStartingLockerNumber']);
        Route::get('/logs', [LockerHistoryController::class, 'getLogs']);  
        Route::get('/{locker}', [LockerController::class, 'show']);
        Route::post('/{locker}', [LockerController::class, 'update']);
        Route::get('/delete/{locker}', [LockerController::class, 'destroy']);   //get muna ayaw gumana ng delete na method. method not allowed daw
    });

    // DEPARTMENT 
    Route::get('view/{department}',[ProgramController::class,'viewDepartmentProgram']);
    Route::post('/add-program',[ProgramController::class, 'addProgram']);
    Route::get('/departments',[CollegeController::class, 'getColleges']);
    Route::post('/add-department',[CollegeController::class, 'addCollege']);
});

// Cataloging Process routes
Route::group(['middleware' => ['auth:sanctum', 'ability:cataloging']], function () {
    
    // View cataloging logs
    Route::get('cataloging/logs', [CatalogingLogController::class, 'get']);
    Route::get('books/locations', [BookController::class, 'getLocations']);

    // View Reports
    Route::group(['prefix' => 'cataloging'], function() {
        Route::get('reports/material-counts', [CatalogingReportController::class, 'getCount']);
        Route::get('reports/pdf/{type}', [CatalogingReportController::class, 'generatePdf']);
        Route::post('reports/excel/{type}', [CatalogingReportController::class, 'generateExcel']);
        Route::get('counts/projects/{department}', [CatalogingReportController::class, 'countProjects']);
        Route::get('logs', [CatalogingLogController::class, 'get']);
    
        // PROCESSING OF MATERIALS
        Route::group(['prefix' => 'materials'], function() {
            Route::post('books/process', [BookController::class, 'add']);
            Route::post('periodicals/process', [PeriodicalController::class, 'add']);
            Route::post('articles/process', [ArticleController::class, 'add']);
    
            // Update Materials
            Route::put('books/process/{id}', [BookController::class, 'update']);
            Route::put('periodicals/process/{id}', [PeriodicalController::class, 'update']);
            Route::put('articles/process/{id}', [ArticleController::class, 'update']);
        });

        // ARCHIVE Materials
        Route::delete('material/archive/{id}', [MaterialArchiveController::class, 'store']);
        Route::delete('project/archive/{id}', [MaterialArchiveController::class, 'storeProject']);

        // MATERIAL VIEWING
        Route::get('books/locations', [LocationController::class, 'getLocations']);
        Route::get('materials/{type}', [MaterialViewController::class, 'getMaterials']);
        Route::get('materials/{type}/type/{periodical_type}', [MaterialViewController::class, 'getMaterialsByType']);
        Route::get('material/id/{id}', [MaterialViewController::class, 'getMaterial']);

        // PROJECTS
        Route::get('projects', [ProjectController::class, 'getProjects']);
        Route::get('project/id/{id}', [ProjectController::class, 'getProject']);
        Route::get('projects/department/{type}', [ProjectController::class, 'getByDepartment']);
        Route::post('projects/process', [ProjectController::class, 'add']);
        Route::put('projects/process/{id}', [ProjectController::class, 'update']);
        
        // Get programs
        Route::get('programs', [ProgramController::class, 'get']);
    });    
});

Route::post('testexcel', [ExcelImportController::class, 'import']);

// Circulation Process Routes
Route::group(['middleware' => ['auth:sanctum', 'ability:circulation']], function () {

    // display user list
    Route::get('/users', [BorrowMaterialController::class, 'userlist']);

    // borrow list
    Route::get('/borrow-list', [BorrowMaterialController::class, 'borrowlist']);

    // update borrow list
    Route::put('borrow-edit/{id}',[BorrowMaterialController:: class, 'borrowEdit']);
    
    // borrow-list returning book
    Route::put('return-book/{id}', [BorrowMaterialController::class, 'returnbook']);

    //returned book list
    Route::get('returned-list',[BorrowMaterialController::class,'returnedlist']);
    Route::get('returned-list/{id}',[BorrowMaterialController::class,'returnedlistid']);

    //reservebook
    Route::post('/reserve/book', [ReserveBookController::class, 'reservebook']);

    //reservationlist
    Route::get('reservation-list/{type}', [ReserveBookController::class, 'reservelist']);

    Route::get('queue', [ReserveBookController::class, 'queue']);
    

    // borrow book 
    Route::post('/borrow/book', [BorrowMaterialController::class, 'borrowbook']);
    Route::post('/fromreserve/book/{id}', [BorrowMaterialController::class, 'fromreservation']);
    Route::get('circulation/get-user/{id}', [CirculationUserController::class, 'getUser']);
    Route::get('circulation/get-book/{id}', [CirculationUserController::class, 'getBook']); 
    Route::get('circulation/getpatrons', [PatronController::class, 'index']);
    Route::get('borrow-count/{id}', [BorrowMaterialController::class, 'borrowcount']);

    //get report
    Route::get('report', [BorrowMaterialController::class, 'bookBorrowersReport']);
    Route::get('topborrowers', [BorrowMaterialController::class, 'topborrowers']);
    Route::get('mostborrowed', [BorrowMaterialController::class, 'mostborrowed']);

    //delete
    Route::delete('delete-borrowlist/{id}', [BorrowMaterialController::class, 'destroy']);
    Route::delete('delete-reservelist/{id}', [ReserveBookController::class,'destroy']);
});

/* STUDENT ROUTES */
// Route::group(['middleware' => ['studentauth']], function () {
Route::group(['middleware' => ['auth:sanctum', 'ability:user']], function () { 

    Route::get('borrow/user/{userId}', [BorrowMaterialController::class, 'getByUserId']);

    // ROUTES FOR VIEWING 
    Route::group(['prefix' => 'student/'], function () {
        Route::get('announcements', [AnnouncementController::class, 'index']);

        Route::get('books', [StudentViewController::class, 'viewBooks']);
        Route::get('periodicals', [StudentViewController::class, 'viewPeriodicals']);
        Route::get('projects', [StudentViewController::class, 'getProjects']);
        Route::get('articles', [StudentViewController::class, 'viewArticles']);
        Route::get('projects/department/{department}', [StudentViewController::class, 'getProjectCategoriesByDepartment']);//'viewProjectsByDepartment']);

        // FOR SINGLE RECORD
        Route::get('book/id/{id}', [StudentViewController::class, 'viewBook']);
        Route::get('periodicals/id/{id}', [StudentViewController::class, 'getPeriodical']);
        Route::get('article/id/{id}', [StudentViewController::class, 'viewArticle']);
        Route::get('project/id/{id}', [StudentViewController::class, 'getProject']);//'viewProjectsByDepartment']);

        // FOR FILTERING MATERIAL TYPE
        Route::get('periodicals/type/{type}', [StudentViewController::class, 'viewPeriodicalByType']);
        Route::get('articles/type/{type}', [StudentViewController::class, 'viewArticlesByType']);
        Route::get('projects/type/{type}', [StudentViewController::class, 'viewProjectByType']);
        Route::get('periodicals/materialtype/{materialType}', [StudentViewController::class, 'getPeriodicalByMaterialType']);

        //Search 
        Route::get('books/search/', [StudentSearchController::class, 'searchBooks']);
        Route::get('periodicals/search/', [StudentSearchController::class, 'searchPeriodicals']);
        Route::get('articles/search/', [StudentSearchController::class, 'searchArticle']);
        Route::get('projects/search/', [StudentSearchController::class, 'searchProjects']);
    });

    // Reservation routes
    Route::post('reservations', [ReservationController::class, 'store']); // Changed from 'reservation/{id}' to 'reservations'
    // Reservation Cancel
    Route::delete('/cancel-reservation/{id}', [ReservationController::class, 'cancelReservation']);
    Route::get('students/queue-pos/{id}', [ReserveBookController::class, 'getQueuePosition']);
    
    // API resource route for reservations
    Route::get('reservations/{id}', [ReservationController::class, 'getUserById']);
    Route::delete('reservations/{reservation}', [ReservationController::class, 'destroy']);
    Route::get('borrow/user/{userId}', [BorrowMaterialController::Class, 'getByUserId']);

    Route::get('reservations/{id}', [ReservationController::class, 'getUserById']);
    Route::delete('reservations/{reservation}', [ReservationController::class, 'destroy']);
    Route::get('borrow/user/{userId}', [BorrowMaterialController::class, 'getByUserId']);
        
    Route::get('queue-pos/{id}', [ReserveBookController::class, 'getQueuePosition']);   
});

// RED ZONE 
Route::group(['middleware' => ['auth:sanctum', 'ability:cataloging']], function () {
    Route::get('images/delete/single', [ImageController::class, 'delete'])->name('images.delete');
    Route::get('images/delete/all/{type}', [ImageController::class, 'deleteAll']);
});

//opac routes
Route::group(['prefix' => 'opac'], function () {
    //materials
    Route::get('books', [OPACMaterialsController::class, 'getBooks']);
    Route::get('/periodicals/{material_type}', [OPACMaterialsController::class, 'getPeriodicals']);
    Route::get('/articles', [OPACMaterialsController::class, 'getArticles']);

    Route::get('/material/{id}', [OPACMaterialsController::class, 'getMaterial']);
    
    //projects
    Route::get('projects/{category}', [OPACMaterialsController::class, 'getProjects']);
    Route::get('/project/{id}', [OPACMaterialsController::class, 'getProject']);
});

// locker routes
// Route::group(['middleware' => ['auth:sanctum', 'ability:locker']], function () {
//     Route::get('/lockers-log', [LockersLogController::class, 'getLockerLogs']);
//     Route::get('/lockers-logs-with-users', [LockersLogController::class, 'fetchLockersLogsWithUsers']);


//     //LOCKER MAINTENANCE
//     Route::post('/locker', [LockerController::class, 'locker']);
//     Route::get('/getlocker', [LockerController::class, 'getlocker']);

//     Route::get('locker/{lockerid}', [LockerController::class, 'getLockerInfo']);

//     Route::get('/locker/{id}', 'App\Http\Controllers\LockerController@getLockerInfo');
//     Route::post('/locker/info', 'LockerController@getLockerInfo');
//     Route::get('/locker', 'LockerController@getAllLockers');
//     Route::get('/locker-counts', 'LockerController@getLockerCounts');

//     Route::get('/locker', [LockerController::class, 'getAllLockers']);

//     Route::get('/locker/{id}', [LockerController::class, 'getLockerInfo'])->where('id', '[0-9]+'); // Kung ang id ay numerical
//     Route::get('/locker-counts', [LockerController::class, 'getLockerCounts']);
//     Route::get('/history', [LockerController::class, 'getLockerHistory']);
//     Route::get('/gender-counts', [LockerController::class, 'getGenderCounts']);

//     Route::get('/department-counts', [LockerController::class, 'getDepartmentCounts']);
//     Route::get('/college-counts', [LockerController::class, 'getCollegeCounts']);


//     Route::get('/college-program-counts', [LockerController::class, 'getcollegeProgramCounts']);
//     Route::post('/locker/{lockerId}/scan', [LockerController::class, 'scanLockerQRCode']);
// });