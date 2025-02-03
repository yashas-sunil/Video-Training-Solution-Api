<?php

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

use App\Http\Controllers\V1\CourseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\V1\StudentNoteController;
use App\Http\Controllers\V1\TimetableController;
use App\Http\Controllers\V1\LeadGenerationController;


/** @noinspection PhpParamsInspection */

Route::get('/', function () {
    return response()->json(['message' => 'API - v1.0.0']);
});

Route::post('getLead', 'LeadGenerationController@getLead');
Route::post('getVerifiedLead', 'LeadGenerationController@getVerifiedLead');

Route::post('login', 'AuthController@login');
Route::get('has_testpress', 'AuthController@has_testpress');
Route::get('sso_testpress', 'AuthController@sso_testpress');
Route::post('login_testpress','AuthController@login_testpress');
Route::post('register', 'AuthController@register');
Route::apiResource('can-not-find-enquire', 'CanNotFindEnquireController');
Route::post('search', 'SearchController@search');

Route::get('remove-tokens', 'Mobile\UserInformationController@removeTokens');

Route::post('mobile-users', 'AuthController@mobileLogin');

Route::post('validate-email', 'AuthController@validateEmail');
Route::post('validate-phone', 'AuthController@validatePhone');
Route::get('high-priority-notification', 'HighPriorityNotificationController@index');
Route::get('terms', 'Mobile\TermsController@index');
Route::get('privacy', 'Mobile\TermsController@privacy');

Route::get('get-all-languages', 'StudyMaterialController@getAllLanguages');
Route::get('package-order-items', 'OrderItemController@packageOrderItems');

Route::prefix('otp')->name('otp.')->group(function () {
    Route::post('/send', 'OTPController@send')->name('send');
    Route::post('/verify', 'OTPController@verify')->name('verify');
});

Route::middleware('auth:api')->get('/user', function (Request $request) {

    $response = $request->user()->load('student', 'student.course', 'student.level', 'student.country', 'student.state');

    return response()->json(['message' => 'User', 'data' => $response, 'errors' => ''], 200);
});

Route::middleware('auth:api')->group(function () {

    Route::get('delete-token', 'AuthController@removeToken');

    Route::apiResource('user-information', 'Mobile\UserInformationController')->only('index');

    Route::get('student-dashboard', 'Mobile\UserInformationController@studentDashboard');

    Route::get('get-my-course-package-details', 'Mobile\UserInformationController@myCoursePackageDetails');

    Route::get('latest-video-histories', 'VideoHistoryController@latestVideoDetails');

    Route::get('update-log-session', 'Mobile\UserInformationController@updateLogSession');
    Route::get('get-video-histories', 'VideoHistoryLogController@getVideoHistoriesSession');

    Route::apiResource('histories', 'VideoHistoryLogController')->only('index');

    Route::apiResource('student-notes', 'StudentNoteController');

    Route::apiResource('students', 'StudentController');
    Route::post('update-academic-information', 'StudentController@updateAcademicInformation');
    Route::post('update-student-address', 'StudentController@updateStudentAddress');

    Route::apiResource('update-email', 'UpdateEmailController');

    Route::post('edit-email-otp', 'UpdateEmailController@editEmailOtp');

    Route::post('verify-email-otp', 'UpdateEmailController@verifyEmailOtp');

    Route::get('get-student-orders', 'StudentController@getOrderDetails');

    Route::get('get-invoice-details', 'StudentController@getInvoiceDetails');

    Route::get('get-study-materials', 'StudyMaterialController@index');

    Route::get('get-purchased-chapters', 'StudyMaterialController@purchasedChapters');

    Route::get('get-purchased-subjects', 'StudyMaterialController@purchasedSubjects');

    Route::get('filter-study-materials', 'StudyMaterialController@filterStudyMaterials');

    Route::get('dashboard-study-plans', 'StudyMaterialController@dashboardStudyPlans');

    Route::get('get-package-study-materials', 'StudyMaterialController@getPackageStudyMaterials');

    Route::get('get-test-papers-of-order-items', 'StudyMaterialController@getTestPapersOfOrderItems');

    Route::get('get-test-papers-of-user-freemium', 'StudyMaterialController@getTestPapersOfUserFreemium');

    Route::apiResource('professor-notes', 'ProfessorNoteController');

    Route::apiResource('ask-a-questions', 'AskAQuestionController');

    Route::apiResource('coupons', 'CouponController');

    Route::apiResource('addresses', 'AddressController');

    Route::apiResource('wishlist', 'WishListController');

    Route::get('get-wish-list-user-package-ids', 'WishListController@getWishListUserPackageIds');
    Route::get('delete-from-wishlist', 'WishListController@removeFromWishList');

    Route::get('get-user-wishlist-packages', 'WishListController@getWishListUserPackages');

    // Freemium Routes
    Route::apiResource('userFreemium', 'UserFreemiumController');
    Route::get('get-user-freemium-package-ids', 'UserFreemiumController@getUserFreemiumPackageIds');
    Route::get('get-user-freemium-packages', 'UserFreemiumController@getUserFreemiumPackages');

    Route::apiResource('videos', 'VideoController');


//    Route::apiResource('orders', 'OrderController');

    Route::get('tax', 'OrderController@getTax');

    Route::get('redeem-reward-points', 'OrderController@rewardPoints');

    Route::apiResource('order-items', 'OrderItemController');

    Route::apiResource('referrals', 'ReferralController');

    Route::apiResource('settings', 'SettingController');

    Route::apiResource('j-money', 'JMoneyController');

    Route::get('content-courses', 'ContentController@courses');

    Route::get('content-levels', 'ContentController@levels');

    Route::get('content-subjects', 'ContentController@subjects');

    Route::get('content-chapters', 'ContentController@chapters');

    Route::get('content-video-history', 'ContentController@videoHistory');
    Route::get('remove-user-from-push-notification', 'UserController@removeUserFromPushNotification');

    Route::post('upload-profile-image', 'StudentController@uploadProfileImage');
    Route::post('student/attempt_year/update','StudentController@attemptYearUpdate');


    Route::apiResource('contents', 'ContentController')->only('index');

    Route::get('get-purchased-packages', 'PackageController@getPurchasedPackages');
    Route::get('get-dashboard-purchased-packages', 'PackageController@getDashboardPurchasedPackage');
    Route::get('get-completed-packages/{total}', 'PackageController@getCompletedPackages');
    Route::get('get-package-subjects/{id}', 'PackageController@getPackageSubjects');

    Route::apiResource('video-histories', 'VideoHistoryController')->only('index', 'store');
    Route::get('freemium-video-histories', 'VideoHistoryController@freemium_index');
    Route::post('freemium-video-histories', 'VideoHistoryController@freemium_store');
    Route::get('get-total-chapters', 'PackageController@getTotalChapters');
    Route::apiResource('videos', 'VideoController');
    Route::apiResource('balance-orders', 'BalanceOrderController');

    Route::apiResource('payments', 'PaymentController')->only('index');
    Route::get('remaining-duration', 'VideoHistoryController@getRemainingDuration');
    Route::get('freemium-remaining-duration', 'VideoHistoryController@getFreemiumRemainingDuration');

    Route::post('order-items/mark-as-completed/{id}', 'OrderItemController@markAsCompleted');

    Route::get('user-notifications', 'UserNotificationController@index');
    Route::post('user-notifications/mark-as-read', 'UserNotificationController@markAsRead');

    //for order history
    Route::get('get-order-history', 'OrderItemController@getOrderHistory');
    Route::get('get-last-watched-video', 'VideoController@getLastWatchedVideo');
    Route::apiResource('save_screenshot','ScreenCaptureController')->only('store');
    Route::apiResource('save_invoice_access_log','InvoiceLogController')->only('store');

    // Route::get('answer-portal', 'AnswerPortalController@index');
    Route::post('answer-portal', 'AnswerPortalController@store');

    // Veranda Varsity Rule book routes
    Route::apiResource('agreeTnCofVerandaVarsity', 'VerandaVarsityController');
    Route::get('get-user-agree-TnC', 'VerandaVarsityController@getUserAgreeTnC');
});

Route::apiResource('orders', 'OrderController');
Route::post('ease-buzz-orders', 'OrderController@easeBuzzUpdate');
Route::post('payment-transaction-history', 'OrderController@paymentTransactionHistory');

Route::post('add-jmoney-holidayoffer', 'OrderController@addJmoneyHolidayOffer');
Route::get('get-jmoney-holidayoffer', 'OrderController@getJmoneyHolidayOffer');
Route::get('delete-jmoney-holidayoffer', 'OrderController@deleteJmoneyHolidayOffer');
//for api status
Route::post('apiOrders', 'OrderController@apiOrdersStatus');

//for cancel api
Route::post('apiCancelOrders', 'OrderController@apiCancelOrders');

Route::resource('contact-us', 'ContactUsController');

Route::apiResource('subjects', 'SubjectController')->only('index');
Route::get('get-subjects-by-levels', 'SubjectController@getSubjectByLevels');
Route::get('get-subjects-by-languages', 'SubjectController@getSubjectByLanguages');
Route::get('get-chapter-by-subject', 'ChapterController@getChapterBySubjects');
Route::get('get-professor-by-chapter', 'ProfessorController@getProfessorByChapter');
Route::apiResource('chapters', 'ChapterController')->only('index', 'show');

Route::post('fetch-student-orders', 'OrderController@fetchStudentOrders');


Route::apiResource('countries', 'CountryController')->only('index');
Route::apiResource('states', 'StateController')->only('index');
Route::apiResource('languages', 'LanguageController')->only('index');

Route::apiResource('courses', 'CourseController')->only('index');
Route::get('course/{id}', 'CourseController@getCourseById');
Route::get('level/{id}', 'LevelController@getLevelById');
Route::get('level-by-course/{id}', 'LevelController@getLevelByCourse');

Route::apiResource('levels', 'LevelController')->only('index');
Route::apiResource('sections', 'SectionController')->only('index');
Route::get('get-section-packages', 'SectionController@getSectionPackagesForHomePage');

Route::apiResource('professors', 'ProfessorController')->only('index', 'show');
Route::get('professors-by-experience', 'ProfessorController@professorsByExperience');
//---------------Added BY TE on 24 May 2022------------------------------//
Route::get('professorsBYSubject', 'ProfessorController@professorsBYSubject');
// Route::apiResource('email-support','EmailSupportController')->only('store');
Route::post('getlastCompletedVideo', 'VideoHistoryController@getlastCompletedVideo');
Route::get('getValidPackages','PackageController@getValidPackages');
Route::apiResource('testimonials','TestimonialController');
Route::get('get-unread-count','UserNotificationController@getUnreadCount');
Route::get('linkedPackages','FreeResourceController@linkedPackages');
Route::get('demo_video','FreeResourceController@getDemoVideo');
Route::get('videos/getVideoById/{id}','VideoController@getVideoById');
Route::get('course_demo_video','FreeResourceController@getcoursevideo');
Route::get('getallpackagetypes','PackageController@getpackagetypes');
Route::get('levelbycourse/{id}', 'LevelController@get_Level_By_Course');
Route::get('get-professor-by-subject', 'ProfessorController@professorBYSubject');
Route::get('get-levels-by-course','LevelController@levels_by_courses');
Route::get('get-last-success-payments','OrderController@get_last_success_orders');
Route::apiResource('save-student-logs','StudentLogsController')->only('store');
Route::get('update-fcm-tocken','FcmController@updateFcmTocken');
Route::get('firebase-notification','FcmController@firebasenotification');
Route::get('holiday-offers','CartController@getHolidayScheme');
Route::get('holiday-offer-det','CartController@getHolidaySchemeDet');
Route::get('jkoin-max','JMoneyController@getMaxJkoin');
Route::get('jkoin-used','JMoneyController@getUsedJkoin');
Route::get('get-types-by-levels', 'SubjectController@getTypeByLevels');
Route::apiResource('thane-vaibhav-reg','VaibhavRegController')->only('store');
Route::post('thane-vaibhav-mob-verify','VaibhavRegController@getVerifiedOtp');
Route::post('check-email', 'VaibhavRegController@validateEmail');
Route::post('check-phone', 'VaibhavRegController@validatephone');
Route::post('signup_otp_verify', 'AuthController@signup_otp_verify');

Route::post('get_question_details', 'Professor\QuestionController@get_question_details');
Route::post('get_question_answer', 'Professor\AnswerController@get_question_answer');
Route::get('get-purchased-studymaterials','StudyMaterialController@getPurchasedSm');

//-----------------------------TE Ends---------------------------------//


Route::apiResource('packages', 'PackageController');
Route::get('package-features/{id}', 'PackageFeatureController@getFeaturesByPackage');
Route::get('get-all-packages', 'PackageController@getAllPackagesForHomePage');
Route::get('get-all-packages-by-level', 'PackageController@getAllPackagesByLevelId');
Route::get('get-package-list', 'PackageController@getPackageList');

Route::get('package-details/{id}', 'PackageController@getPackageDetails');

Route::apiResource('cart', 'CartController');

Route::apiResource('free-resources', 'FreeResourceController');

Route::apiResource('banners', 'BannerController')->only('index');

Route::apiResource('call-requests', 'CallRequestController')->only('store');

Route::apiResource('forgot-password', 'ForgotPasswordController')->only('store');

Route::apiResource('reset-password', 'ResetPasswordController')->only('store');

Route::apiResource('testimonials', 'TestimonialController')->only('index');

Route::apiResource('cart', 'CartController');

Route::group(['prefix' => 'associate'], function () {
    Route::middleware('auth:api')->group(function () {
        Route::apiResource('profile', 'Associate\ProfileController')->only('index', 'update');
        Route::apiResource('dashboard', 'Associate\DashboardController')->only('index');
        Route::apiResource('students', 'Associate\StudentController')->except('delete');
        Route::post('students/send-verification-mail', 'Associate\StudentController@sendVerificationMail')->name('students.send-verification-mail');
        Route::apiResource('orders', 'Associate\OrderController')->only('index');
        Route::apiResource('order-items', 'Associate\OrderItemController')->only('index');
        Route::apiResource('commissions', 'Associate\CommissionController')->only('index');
        Route::apiResource('sales', 'Associate\SaleController')->only('index');

        Route::post('update-avatar', 'Associate\ProfileController@updateAvatar')->name('update-avatar');
        Route::post('send-payment-link', 'Associate\OrderController@sendPaymentLink')->name('send-payment-link');
    });
});

Route::group(['prefix' => 'professor'], function () {
    Route::middleware('auth:api')->group(function () {
        Route::apiResource('profile', 'Professor\ProfileController')->only('index', 'store', 'update');
        Route::apiResource('questions', 'Professor\QuestionController')->only('index', 'show');
        Route::apiResource('answers', 'Professor\AnswerController')->only('index', 'store');
        Route::apiResource('videos', 'Professor\VideoController')->only('index', 'store');
        Route::get('show-videos', 'Professor\VideoController@show');
        Route::post('notes', 'Professor\VideoController@addNotes');
        Route::get('professor-notes', 'Professor\VideoController@professorNotes');
        Route::post('update-notes', 'Professor\VideoController@updateProfessorNotes');
        Route::post('delete-notes', 'Professor\VideoController@deleteProfessorNotes');
        Route::apiResource('payout', 'Professor\PayoutController');
        Route::apiResource('testimonials', 'Professor\TestimonialController')->only('index');
        Route::apiResource('dashboard', 'Professor\DashboardController')->only('index');
        Route::apiResource('reports', 'Professor\ReportController')->only('index');
        Route::apiResource('revenues', 'Professor\ProfessorRevenueController')->only('index');
        Route::get('package-videos', 'Professor\VideoController@packageVideos')->name('package-videos');
        Route::apiResource('packages', 'Professor\PackageController')->only('index');
        Route::post('change-password/{id}', 'Professor\ProfileController@changePassword');
    });
});



Route::get('embed/videos/{id}', 'VideoController@embedVideo');

Route::get('chapters/{chapterID}/videos/{videoID}', 'VideoController@getChapterVideos');

Route::get('videos/get-player/{id}/{s3?}', 'VideoController@getPlayer');

Route::post('validate-login', 'AuthController@validateLogin');

Route::resource('campaign-registrations', 'CampaignRegistrationController')->only('store');

Route::group(['prefix' => 'campaigns'], function () {
    Route::get('spin-wheels/{slug}', 'Campaigns\SpinWheelController@show');
    Route::get('spin-wheels/{id}/prize', 'Campaigns\SpinWheelController@getPrize');
    Route::post('validate-phone', 'CampaignRegistrationController@validatePhone');
    Route::post('validate-otp', 'CampaignRegistrationController@validateOTP');
    Route::get('remaining-chances', 'TempCampaignPointController@getRemainingChances');
});

Route::post('temp-campaigns-points', 'TempCampaignPointController@store');

Route::post('update-payment-initiated-at/{id}', 'OrderController@updatePaymentInitiatedAt');

Route::group(['prefix' => 'branch-managers', 'as' => 'branch-managers.'], function () {
    Route::apiResource('profile', 'BranchManager\ProfileController')->only('index', 'update');
    Route::apiResource('orders', 'BranchManager\OrderController')->only('index');
    Route::apiResource('students', 'BranchManager\StudentController')->only('index', 'store');
});

Route::get('verifications/{token}', 'AuthController@markAsVerified');

Route::post('blogs/{id}/like', 'BlogController@like');
Route::resource('blogs', 'BlogController')->only('index', 'show');
Route::resource('blog-categories', 'BlogCategoryController')->only('index');

Route::get('answer-portal', 'AnswerPortalController@index');
//Route::post('answer-portal', 'AnswerPortalController@store');

Route::resource('blog-tags', 'BlogTagController')->only('index');
Route::post('feedback/store', 'FeedbackController@store');
Route::get('feedback/index', 'FeedbackController@index');
Route::get('feedback-order-item', 'FeedbackController@index');
