<?php

use App\Http\Controllers\Cataloging\ExcelImportController;
use App\Http\Controllers\Cataloging\MaterialViewController;
use App\Http\Controllers\ImageController;
use App\Http\Controllers\OPAC\OPACMaterialsController;
use App\Http\Controllers\ProgramController;
use App\Http\Controllers\StudentPortal\StudentSearchController;
use App\Http\Controllers\StudentPortal\StudentViewController;
use App\Http\Controllers\StudentPortal\StudentMaterialController;
use App\Http\Controllers\StudentPortal\StudentReservationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\Cataloging\AVController;
use App\Http\Controllers\AnalyticsController;

use App\Http\Controllers\AuthController,
    App\Http\Controllers\CatalogingLogController,
    App\Http\Controllers\Cataloging\ArticleController,
    App\Http\Controllers\Cataloging\BookController,
    App\Http\Controllers\Cataloging\PeriodicalController,
    App\Http\Controllers\Cataloging\ProjectController,
    App\Http\Controllers\Cataloging\CatalogingReportController,
    App\Http\Controllers\Cataloging\MaterialArchiveController;

use App\Http\Controllers\BorrowBookController, App\Http\Controllers\ReservationController;
use App\Http\Controllers\Cataloging\ViewArchivesController;

use App\Http\Controllers\LockerController;

use App\Http\Controllers\UserController,
    App\Http\Controllers\CollegeController,
    App\Http\Controllers\InventoryController,
    App\Http\Controllers\LocationController,
    App\Http\Controllers\AnnouncementController,
    App\Http\Controllers\LockerHistoryController;

//Circulation
use App\Http\Controllers\Circulation\BorrowMaterialController,
    App\Http\Controllers\Circulation\CirculationUserController,
    App\Http\Controllers\Circulation\PatronController,
    App\Http\Controllers\Circulation\ReserveBookController,
    App\Http\Controllers\Circulation\CirculationReport;

/* ROUTE TESTERS */

/**
 * These routes lack encryption and are used for testing purposes.
 * They are placed here to allow testing of the API without encryption.
 */
Route::get('/', function (Request $request) {
    return response()->json(['Response' => 'API routes are available']);
});

/* ENCRYPTED ROUTES */
/**
 * These routes are encrypted using the EncryptResponse middleware.
 * Encryption implementation moved to this layer to allow testing for certain routes.
 * To test for certain routes, move them outside of this section and put it above.
 */
Route::middleware(['encrypt.response', 'decrypt.payload'])->group(function () {
    // FOR ALL USERS
    Route::post('/studentlogin', [AuthController::class, 'studentLogin']); /* the tester for the specific auth to be applied */
    Route::post('/login/{system}', [AuthController::class, 'login']);

    // FOR ALL AUTHENTICATED USERS
    // get the current logged in user details
    Route::get('/user', [AuthController::class, 'user'])->middleware('auth:sanctum');

    // Auth Routes
    Route::group(['middleware' => ['auth:sanctum']], function () {
        Route::post('/refresh', [AuthController::class, 'refreshToken']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });

    // Route::get('images/{url}', [ImageController::class, 'get'])->middleware('auth:sanctum');

    // Maintenance route
    Route::middleware(['auth:sanctum', 'ability:maintenance'])->group(function () {
        Route::get('/personnels', [UserController::class, 'index']);
        Route::post('/personnels', [UserController::class, 'store']);
        Route::get('/personnels/{personnel}', [UserController::class, 'show']);
        Route::post('/personnels/{personnel}', [UserController::class, 'update']);
        Route::get('/personnels/{personnel}/delete', [UserController::class, 'destroy']);

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
        Route::post('locations/{id}', [LocationController::class, 'update']);
        Route::delete('locations/{id}', [LocationController::class, 'destroy']);

        Route::prefix('maintenance/lockers')->group(function () {
            Route::get('/', [LockerController::class, 'index']);
            Route::post('/', [LockerController::class, 'store']);
            Route::get('/latest', [LockerController::class, 'getStartingLockerNumber']);
            Route::get('/logs', [LockerHistoryController::class, 'getLogs']);
            Route::get('/{locker}', [LockerController::class, 'show']);
            Route::post('/{locker}', [LockerController::class, 'update']);
            Route::post('/delete/{locker}', [LockerController::class, 'destroy']);   //get muna ayaw gumana ng delete na method. method not allowed daw
        });

        // DEPARTMENT
        Route::post('/add-program', [ProgramController::class, 'addProgram']);
        Route::get('/departments', [CollegeController::class, 'getDepartments']);
        Route::post('/add-department', [CollegeController::class, 'addDepartment']);

        Route::prefix('analytics')->group(function () {
            //Analytics Api


            //circu
            Route::get('/available-books', [AnalyticsController::class, 'getAvailableBooks']);
            Route::get('/unreturned-books', [AnalyticsController::class, 'getUnreturnedBooks']);
            Route::get('/missing-books', [AnalyticsController::class, 'getMissingBooks']);
            Route::get('/borrow-history', [AnalyticsController::class, 'getBorrowHistory']);


            //cataloging
            Route::get('/total-materials', [AnalyticsController::class, 'getTotalMaterials']);
            Route::get('/total-projects', [AnalyticsController::class, 'getTotalProjects']);

            //locker
            Route::get('/total-lockers', [AnalyticsController::class, 'totalLockers']);
            Route::get('/locker-user-by-department', [AnalyticsController::class, 'lockerUsersByDepartment']);
        });
    });

    // Cataloging Process routes
    Route::group(['middleware' => ['auth:sanctum', 'ability:cataloging']], function () {

        // View Reports
        Route::group(['prefix' => 'cataloging'], function () {
            Route::group(['prefix' => 'reports'], function () {
                Route::get('material-counts', [CatalogingReportController::class, 'countMaterials']);
                Route::get('project-counts', [CatalogingReportController::class, 'countProjects']);
            });

            // PROCESSING OF MATERIALS
            Route::group(['prefix' => 'materials'], function () {
                Route::post('books/import', [ExcelImportController::class, 'import']);
                Route::post('books/process', [BookController::class, 'add']);
                Route::post('periodicals/process', [PeriodicalController::class, 'add']);
                Route::post('articles/process', [ArticleController::class, 'add']);
                Route::post('audio-visuals/process', [AVController::class, 'add']);

                // Update Materials
                Route::post('books/process/{id}', [BookController::class, 'update']);
                Route::post('periodicals/process/{id}', [PeriodicalController::class, 'update']);
                Route::post('articles/process/{id}', [ArticleController::class, 'update']);
                Route::post('audio-visuals/process/{id}', [AVController::class, 'update']);
            });

            // ARCHIVE Materials
            Route::delete('materials/archive/{id}', [MaterialArchiveController::class, 'storeMaterial']);
            Route::delete('projects/archive/{id}', [MaterialArchiveController::class, 'storeProject']);

            // RESTORE Materials
            Route::post('materials/restore/{id}', [MaterialArchiveController::class, 'restoreMaterial']);
            Route::post('projects/restore/{id}', [MaterialArchiveController::class, 'restoreProject']);

            //PERMANENTLY DELETE
            Route::delete('permanently-delete/{type}/{id}', [MaterialArchiveController::class, 'deleteMaterial']);

            // MATERIAL VIEWING
            Route::get('books/locations', [LocationController::class, 'getLocations']);
            Route::get('materials/{type}', [MaterialViewController::class, 'getMaterials']);
            Route::get('materials/{type}/type/{periodical_type}', [MaterialViewController::class, 'getMaterialsByType']);
            Route::get('material/id/{id}', [MaterialViewController::class, 'getMaterial']);

            // PROJECTS
            Route::get('projects', [ProjectController::class, 'getProjects']);
            Route::get('project/id/{id}', [ProjectController::class, 'getProject']);
            // Route::get('projects/department/{department}', [ProjectController::class, 'getByDepartment']);
            Route::post('projects/process', [ProjectController::class, 'add']);
            Route::post('projects/process/{id}', [ProjectController::class, 'update']);

            // ARCHIVE VIEWING
            Route::group(['prefix' => 'archives'], function () {
                Route::get('materials/{type}', [ViewArchivesController::class, 'getMaterials']);
                Route::get('projects', [ViewArchivesController::class, 'getProjects']);

                Route::get('{type}/id/{id}', [ViewArchivesController::class, 'getMaterial']);

                // By type
                Route::get('materials/{type}/type/{periodical_type}', [ViewArchivesController::class, 'getMaterialsByType']);
            });

            // Get programs
            Route::get('programs', [ProgramController::class, 'get']);
        });
    });

    // Circulation Process Routes
    Route::group(['middleware' => ['auth:sanctum', 'ability:circulation']], function () {

        // display user list
        Route::get('/circulation/userlist', [CirculationUserController::class, 'userlist']);

        // borrow list
        Route::get('/circulation/borrow-list', [BorrowMaterialController::class, 'borrowlist']);

        // update borrow list
        Route::put('/circulation/borrow-edit/{id}', [BorrowMaterialController::class, 'borrowEdit']);

        // borrow-list returning book from borrowed list
        Route::put('circulation/return-book/{id}', [BorrowMaterialController::class, 'returnbook']);

        //returned book list
        Route::get('/circulation/returned-list', [BorrowMaterialController::class, 'returnedlist']);
        Route::get('/circulation/returned-list/{id}', [BorrowMaterialController::class, 'returnedlistid']);

        //reservebook
        Route::post('/circulation/reserve/book', [ReserveBookController::class, 'reservebook']);

        //reservationlist
        Route::get('/circulation/reservation-list/{type}', [ReserveBookController::class, 'reservelist']);
        Route::get('/circulation/reservelist', [ReserveBookController::class, 'allreserve']);
        Route::get('/circulation/queue', [ReserveBookController::class, 'queue']);
        Route::put('/circulation/cancel/{id}', [ReserveBookController::class, 'cancelReservation']);

        //borrow book
        Route::post('/circulation/borrow/book', [BorrowMaterialController::class, 'borrowbook']);
        Route::put('/circulation/fromreserve/book/{id}', [BorrowMaterialController::class, 'fromreservation']);
        Route::get('/circulation/getpatrons', [PatronController::class, 'index']);
        Route::get('/circulation/borrow-count/{id}', [BorrowMaterialController::class, 'borrowcount']);

        //get individual book & user || for autofill front end
        // Route::get('/circulation/get-book/{accession}', [CirculationUserController::class, 'getBook']);
        Route::get('/circulation/get-book', [CirculationUserController::class, 'getBook']);

        Route::get('/circulation/get-user/{id}', [CirculationUserController::class, 'getUser']);

        //circulation report
        Route::get('/circulation/report', [CirculationReport::class, 'report']);
        Route::get('/circulation/topborrowers', [CirculationReport::class, 'topborrowers']);
        Route::get('/circulation/mostborrowed', [CirculationReport::class, 'mostborrowed']);

        //delete
        Route::delete('/circulation/delete-borrowlist/{id}', [BorrowMaterialController::class, 'destroy']);
        Route::delete('/circulation/delete-reservelist/{id}', [ReserveBookController::class, 'destroy']);

        Route::get('/circulation/borrowdetail', [CirculationUserController::class, 'borrowdetail']);
    });

    /* STUDENT ROUTES */
    // Route::group(['middleware' => ['studentauth']], function () {
    Route::group(['middleware' => ['auth:sanctum', 'ability:student']], function () {
        Route::get('borrow/user/{userId}', [BorrowMaterialController::class, 'getByUserId']);

        // Routes for viewing
        Route::group(['prefix' => 'student/'], function () {
            Route::get('announcements', [AnnouncementController::class, 'index']);

            Route::get('books', [StudentMaterialController::class, 'viewBooks']);
            Route::get('periodicals', [StudentMaterialController::class, 'getPeriodicals']);
            Route::get('projects', [StudentMaterialController::class, 'getProjects']);
            Route::get('articles', [StudentMaterialController::class, 'viewArticles']);
            Route::get('projects/department/{department}', [StudentMaterialController::class, 'getProjectsByProgram']);

            // For single record
            Route::get('book/id/{id}', [StudentMaterialController::class, 'viewBook']);
            Route::get('periodicals/id/{id}', [StudentMaterialController::class, 'getPeriodical']);
            Route::get('article/id/{id}', [StudentMaterialController::class, 'viewArticle']);
            Route::get('project/id/{accession}', [StudentMaterialController::class, 'getProjectByAccession']);

            // For filtering material type
            Route::get('periodicals/type/{type}', [StudentMaterialController::class, 'getByType']);
            Route::get('articles/type/{type}', [StudentMaterialController::class, 'viewArticlesByType']);
            Route::get('project/{category}/{department}', [StudentMaterialController::class, 'getProjectsByCategoryAndDepartment']);
            Route::get('periodicals/materialtype/{materialType}', [StudentMaterialController::class, 'getPeriodicalByPeriodicalType']);

            // Search
            Route::get('books/search/', [StudentMaterialController::class, 'searchBooks']);
            Route::get('periodicals/search/', [StudentMaterialController::class, 'searchPeriodicals']);
            Route::get('articles/search/', [StudentMaterialController::class, 'searchArticles']);
            Route::get('projects/search/', [StudentMaterialController::class, 'searchProjects']);
            Route::get('patron', [StudentReservationController::class, 'patron']);


            //new API for reservation
            Route::post('newreservations', [StudentReservationController::class, 'reservebook']);
            Route::get('reservations/{user_id}', [StudentReservationController::class, 'getReservationsByUserId']);
            Route::get('reservations/user/{id}', [StudentReservationController::class, 'viewReservationById']);
            Route::patch('reservations/cancel/{id}', [StudentReservationController::class, 'cancelReservation']);



            // Audio Visuals
            Route::get('audio-visual', [StudentMaterialController::class, 'viewAudioVisuals']);
            // By Accession
            Route::get('audio-visuals/{accession}', [StudentMaterialController::class, 'getAudioVisualByAccession']);
            // Search for Audio Visuals
            Route::get('audiovisuals/searchs', [StudentMaterialController::class, 'searchAudioVisuals']);

            //announcement
            Route::get('announcements', [StudentViewController::class, 'index']);
            Route::get('announcements/{id}', [StudentViewController::class, 'show']);

            //borrow shis
            Route::get('borrowed/user/{user_id}', [StudentReservationController::class, 'getBorrowedByUserId']);
            Route::get('borrow/user/{user_id}', [StudentReservationController::class, 'getBorrowByUserId']);
            Route::get('borrowed/{id}', [StudentReservationController::class, 'getBorrowedById']);
        });
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
        Route::get('/audiovisuals', [OPACMaterialsController::class, 'getAudiovisuals']);

        Route::get('/material/{id}', [OPACMaterialsController::class, 'getMaterial']);

        //projects
        Route::get('projects/{category}', [OPACMaterialsController::class, 'getProjects']);
        Route::get('/project/{id}', [OPACMaterialsController::class, 'getProject']);
    });


    // locker routes
    Route::group(['middleware' => ['auth:sanctum', 'ability:locker']], function () {
        Route::get('/lockers-log', [LockerHistoryController::class, 'getLockerHistory']);
        Route::get('/lockers-logs-with-users', [LockerHistoryController::class, 'fetchLockersHistoryWithUsers']);
        Route::get('/search', [LockerHistoryController::class, 'search']);
        Route::get('/lockerfilter', [LockerHistoryController::class, 'getLockersWithFilter']);
        Route::get('/searchdata', [LockerHistoryController::class, 'getSearchData']);
        Route::get('/all-department', [LockerHistoryController::class, 'getalldepartment']);


        //LOCKER MAINTENANCE
        Route::post('/locker', [LockerController::class, 'locker']);
        Route::get('/getlocker', [LockerController::class, 'getlocker']);

        Route::get('locker/{lockerid}', [LockerController::class, 'getLockerInfo']);
        Route::get('/locker/{id}', 'App\Http\Controllers\LockerController@getLockerInfo');
        Route::post('/locker/info', 'LockerController@getLockerInfo');
        Route::get('/locker', 'LockerController@getAllLockers');
        Route::get('/locker-counts', 'LockerController@getLockerCounts');
        Route::get('/locker', [LockerController::class, 'getAllLockers']);
        Route::get('/locker/{id}', [LockerController::class, 'getLockerInfo'])->where('id', '[0-9]+');
        Route::get('/locker-counts', [LockerController::class, 'getLockerCounts']);
        Route::get('/history', [LockerController::class, 'getLockerHistory']);
        Route::get('/gender-counts', [LockerController::class, 'getGenderCounts']);
        Route::get('/dashboard-gender-counts', [LockerController::class, 'getDashboardGenderCounts']);
        Route::get('/department-counts', [LockerController::class, 'getDepartmentCounts']);
        Route::get('/college-counts', [LockerController::class, 'getCollegeCounts']);
        Route::get('/college-program-counts', [LockerController::class, 'getcollegeProgramCounts']);
        Route::post('/locker/{lockerId}/scan', [LockerController::class, 'scanLockerQRCode']);
        Route::post('/locker/{lockerId}/scanLocker', [LockerController::class, 'scanLocker']);
        Route::get('/locker-data', [LockerController::class, 'getLockerChartData']);
        Route::get('/export-lockers', [LockerController::class, 'exportLockers']);


        //ADD LOCKER GALING SA MAINTENANCE DATI
        Route::prefix('/lockers')->group(function () {
            Route::get('/', [LockerController::class, 'index']);
            Route::post('/', [LockerController::class, 'store']);
            Route::get('/latest', [LockerController::class, 'getStartingLockerNumber']);
            Route::get('/logs', [LockerHistoryController::class, 'getLogs']);
            Route::get('/{locker}', [LockerController::class, 'show']);
            Route::post('/{locker}', [LockerController::class, 'update']);
            Route::delete('/delete/{locker}', [LockerController::class, 'destroy']);
        });
        //
    });
});
