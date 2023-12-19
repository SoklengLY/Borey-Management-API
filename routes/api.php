<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\UserController;
use App\Http\Controllers\UserinfoController;
use App\Http\Controllers\PasswordResetController;

use App\Http\Controllers\CompaniesController;
use App\Http\Controllers\CompaniesPasswordResetController;

use App\Http\Controllers\AdminController;

use App\Http\Controllers\PostController;
use App\Http\Controllers\RequestformController;
use App\Http\Controllers\waterbillsController;
use App\Http\Controllers\securitybillsController;
use App\Http\Controllers\electricbillsController;
use App\Http\Controllers\FormGeneralController;
use App\Http\Controllers\FormEnvironmentController;
use App\Http\Controllers\SurveyController;
use App\Http\Controllers\QuestionController;
use App\Http\Controllers\AnswerController;
use App\Http\Controllers\SearchController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
//Test Server
Route::get('/test-server', function () {
    $serverName = gethostname();
    $serverIP = $_SERVER['SERVER_ADDR'];

    echo "Server Name: " . $serverName . "<br>";
    echo "Server IP: " . $serverIP . "<br>";
});


// User Routes
Route::post('/register', [UserController::class, 'register']);
Route::post('/login', [UserController::class, 'login']);
Route::post('/send-reset-password-email', [PasswordResetController::class, 'send_reset_password_email']);
Route::post('/reset-password/{token}', [PasswordResetController::class, 'reset']);
Route::post('logout', [UserController::class, 'logout'])->middleware('auth:sanctum');
Route::get('/companiesId', [CompaniesController::class, 'show_company_id']);

// Companies Routes
Route::post('/company/register', [CompaniesController::class, 'register']);
Route::post('/company/login', [CompaniesController::class, 'login']);
Route::post('/company/send-reset-password-email', [CompaniesPasswordResetController::class, 'send_reset_password_email']);
Route::post('/company/reset-password/{token}', [CompaniesPasswordResetController::class, 'reset']);

// Admin Routes
Route::post('/admin/register', [AdminController::class, 'register']);
Route::post('/admin/login', [AdminController::class, 'login']);

// Protected User, Companies Routes
Route::middleware(['auth:sanctum'])->group(function(){

    // User Routes
    Route::get('user_infos/search', [UserinfoController::class, 'search']);
    Route::get('/loggeduser', [UserController::class, 'logged_user']);
    Route::post('/changepassword', [UserController::class, 'change_password']);
    Route::resource('user_infos', UserinfoController::class);
    Route::post('user_infos/{user_info}', [UserinfoController::class, 'update'])->name('user_infos.update');
    Route::get('loggedUserInfo', [UserinfoController::class, 'logged_user_info']);
    
    //Post Routes
    Route::resource('posts', PostController::class);
    Route::post('posts/{post}', [PostController::class, 'update'])->name('posts.update');
    Route::post('postlike', [PostController::class, 'storeLike'])->name('posts.like');
    Route::delete('postlike', [PostController::class, 'destroyLike'])->name('posts.likedestroy');
    Route::post('postcomment', [PostController::class, 'storeComment'])->name('posts.comment');
    Route::delete('postcomment', [PostController::class, 'deleteComment']);
    Route::post('postshare', [PostController::class, 'storeShare'])->name('posts.share');
    Route::delete('postshare', [PostController::class, 'deleteShare']);

    // Admin Routes
    Route::post('/admin/logout', [AdminController::class, 'logout']);
    Route::get('/admin/loggedadmin', [AdminController::class, 'logged_admin']);
    Route::post('/admin/changepassword', [AdminController::class, 'change_password']);
    Route::get('/admin/getallusers', [AdminController::class, 'getAllUsers']);
    Route::get('/admin/getallcompanies', [AdminController::class, 'getAllCompanies']);

    // Companies Routes
    Route::post('/company/logout', [CompaniesController::class, 'logout']);
    Route::get('/company/loggedcompany', [CompaniesController::class, 'logged_company']);
    Route::post('/company/changepassword', [CompaniesController::class, 'change_password']);
    Route::get('/company/showCompanies', [CompaniesController::class, 'show_all_company']);

    //Form General Request
    Route::get('form_generals/search', [FormGeneralController::class, 'search']);
    Route::resource('form_generals', FormGeneralController::class);
    Route::post('form_generals/{form_general}', [FormGeneralController::class, 'update'])->name('form_generals.update');

    //Form Environment Request
    Route::get('form_environments/search', [FormEnvironmentController::class, 'search']);
    Route::resource('form_environments', FormEnvironmentController::class);
    Route::post('form_environments/{form_environment}', [FormEnvironmentController::class, 'update'])->name('form_environments.update');

    //Electric bills Request
    Route::get('electricbills/search', [electricbillsController::class, 'search']);
    Route::resource('electricbills', electricbillsController::class);
    Route::post('electricbills/{electricbill}', [electricbillsController::class, 'update'])->name('electricbills.update');

    //Security bills Request
    Route::get('securitybills/search', [securitybillsController::class, 'search']);
    Route::resource('securitybills', securitybillsController::class);
    Route::post('securitybills/{securitybill}', [securitybillsController::class, 'update'])->name('securitybills.update');

    //Water bills Request
    Route::get('waterbills/search', [waterbillsController::class, 'search']);
    Route::resource('waterbills', waterbillsController::class);
    Route::post('waterbills/{waterbill}', [waterbillsController::class, 'update'])->name('waterbills.update');

    //Request form Request
    Route::get('requestforms/search', [RequestformController::class, 'search']);
    Route::resource('requestforms', RequestformController::class);
    Route::post('requestforms/{requestform}', [RequestformController::class, 'update'])->name('requestforms.update');

    // Survey routes
    Route::get('/surveys', [SurveyController::class, 'index']);
    Route::post('/surveys', [SurveyController::class, 'store']);
    Route::get('/surveys/{id}', [SurveyController::class, 'show']);
    Route::post('/surveys/{id}', [SurveyController::class, 'update']);
    Route::delete('/surveys/{id}', [SurveyController::class, 'destroy']);

    // Question routes
    Route::get('/surveys/{surveyId}/questions', [QuestionController::class, 'index']);
    Route::post('/questions', [QuestionController::class, 'store']);
    Route::get('/surveys/{surveyId}/questions/{questionId}', [QuestionController::class, 'show']);
    Route::post('/questions/{question}', [QuestionController::class, 'update']);
    Route::delete('/questions/{question}', [QuestionController::class, 'delete']);

    // Answer routes
    Route::post('/questions/{question}/answers', [AnswerController::class, 'store']);
    Route::post('/questions/{question}/answers/{answer}', [AnswerController::class, 'update']);
    Route::delete('/questions/{question}/answers/{answer}', [AnswerController::class, 'delete']);
  
    //Search Routes
    Route::get('/user_infos/search', [UserinfoController::class, 'search']);
    Route::get('/search', [SearchController::class, 'search']);

    //delete function
    Route::delete('/user/{id}', [UserController::class, 'destroy']);

});

