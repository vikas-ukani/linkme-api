<?php

use Illuminate\Support\Facades\Route;

Route::get('status', function () {
    return 'Okay';
});
/* UtilityController Route */
Route::get('checkStatus', 'API\UtilityController@getStatus');
/* UserController Route */
Route::post('login', 'API\UserController@login')->name('login');
Route::post('register', 'API\UserController@register')->name('register');
Route::get('email/verify/{token}', 'API\UserController@verify')->name('verification.verify-mail');
Route::post('email/resend', 'API\UserController@resend')->name('verification.resend');
Route::post('social-login', 'API\UserController@socialLogin');
Route::post('social-signup', 'API\UserController@socialSignup');
Route::post('forgot-password', 'API\UserController@forgotPassword')->name('forgot-password');
Route::get('find/{token}', 'API\UserController@find')->name('find');
Route::post('password-reset', 'API\UserController@reset')->name('password-reset');
Route::get('send', 'API\CommunicationController@sendNotification');
Route::get('test', 'API\Postcontroller@test');

Route::group(['middleware' => ['auth:api', 'apiLogger']], function () {
    // Route::middleware(['auth:api', 'apiLogger'])->group(function () {
    Route::get('search-taguser', 'API\UserController@searchtaguser')->name('search-taguser');
    Route::post('user-account', 'API\UserController@user_profile')->name('user-account');
    Route::post('update-profile', 'API\UserController@update_user_profile')->name('update-profile');
    Route::post('change-password', 'API\UserController@password_change')->name('change-password');
    Route::post('logout', 'API\UserController@logout')->name('logout');
    Route::get('provider/graphdata', 'API\UserController@providerMonthWiseEarning');
    Route::get('provider/earning', 'API\UserController@ProviderEarnings');
    Route::get('provider/earninglist', 'API\UserController@ProviderEarningsList');
    Route::get('provider/viewlist', 'API\UserController@providerViewList');
    Route::get('provider/paymentreport', 'API\UserController@emailProviderPaymentReport');
    Route::resource('users', 'API\UserController');
    Route::post('provider/detailview', 'API\UserController@providerViewDetails');
    Route::get('search', 'API\UserController@getSearchResults')->name('search');
    Route::get('provider/filter', 'API\UserController@filterSearchResults')->name('filter');
    Route::post('user/details', 'API\UserController@userDetails');
    Route::post('user/ratings', 'API\UserController@userRatingList');
    Route::get('user/notifications', 'API\UserController@userNotifications');
    /* PortfolioController Route */
    Route::post('upload-image', 'API\PortfolioController@upload')->name('upload-image');
    Route::delete('portfolio/{id}/delete', 'API\PortfolioController@destroy');
    Route::get('portfolio/show', 'API\PortfolioController@myPortfolio');
    Route::get('portfolio/{id}', 'API\PortfolioController@getPortfolio')->name('portfolio');
    Route::post('portfolio', 'API\PortfolioController@index')->name('portfolio');
    Route::post('portfolio/reactionlist', 'API\PortfolioController@reactionList');
    Route::get('portfolio/{id}', 'API\PortfolioController@show');
    /* PortfolioCommentsController Route */
    Route::post('portfolio/comments', 'API\PortfolioCommentsController@PortComments')->name('portfolio/comments');
    Route::post('portfolio/reaction', 'API\PortfolioCommentsController@portfolioreaction');
    /* Linkmeservicescontroller Route */
    Route::post('service/create', 'API\Linkmeservicescontroller@createService')->name('service/create');
    Route::post('service/{serviceId}/edit', 'API\Linkmeservicescontroller@update');
    Route::delete('service/{serviceId}/delete', 'API\Linkmeservicescontroller@destroy');
    Route::post('service/rating', 'API\Linkmeservicescontroller@Servicerating');
    Route::get('service/{id}', 'API\Linkmeservicescontroller@show');
    Route::get('allservices', 'API\Linkmeservicescontroller@index')->name('allservices');
    /* ReviewController Route */
    Route::post('review/create', 'API\ReviewController@createReview')->name('review/create');
    Route::post('reviewlist/{user_type}', 'API\ReviewController@ReviewList');
    /* Hashmastercontroller Route */
    Route::post('community/hashtag', 'API\Hashmastercontroller@createhashtags')->name('community/hashtag');
    Route::get('gethashes', 'API\Hashmastercontroller@gethashtags')->name('gethashes');
    /* Postcontroller Route */
    Route::post('community/post', 'API\Postcontroller@createPost')->name('community/post');
    Route::post('shareprofile', 'API\Postcontroller@shareProfile');
    Route::get('community/{category}/{keyword?}', 'API\Postcontroller@posts');
    //    Route::get('community/{catid}', 'API\Postcontroller@CategoryPost');
    Route::post('postview/{postid}', 'API\Postcontroller@postviews')->name('postview/{postid}');
    Route::get('postDetail/{postid}', 'API\Postcontroller@postDetail')->name('postDetail/{postid}');
    Route::get('post_countview/{postid}', 'API\Postcontroller@countPostView')->name('post_countview/{postid}');
    Route::get('post/search', 'API\Postcontroller@getcommunity');
    Route::post('post/reactionlist', 'API\Postcontroller@reactionList');
    /* PostCommentController Route */
    Route::post('community/comment', 'API\PostCommentController@PostComments');
    Route::post('community/postreaction', 'API\Postcontroller@postreaction');
    Route::post('community/commentreaction', 'API\Postcontroller@commentReaction');
    Route::get('comment/{postid}', 'API\Postcontroller@getComments');
    Route::get('childcomment/{commentid}', 'API\Postcontroller@getchildComments');
    /* ProviderAvailbillityController Route */
    Route::get('provider/available', 'API\ProviderAvailbillityController@Getavailbillity');
    Route::post('provider/createavailable', 'API\ProviderAvailbillityController@Providerbussinesstime');
    Route::get('provider/{id}/availability', 'API\ProviderAvailbillityController@GetavailbillityProvider');
    /* BookingController Route */
    Route::post('provider/booking', 'API\BookinController@createBooking');
    Route::post('provider/re-booking', 'API\BookinController@Bookingreschedule');
    Route::get('customer/allbookings', 'API\BookinController@CustomerbookingLists');
    Route::get('customer/activebookingLists', 'API\BookinController@CustomeractivebookingLists');
    Route::get('customer/canceledbookingLists', 'API\BookinController@CustomercanceledbookingLists');
    Route::get('customer/pastbookingLists', 'API\BookinController@CustomerpastbookingLists');
    Route::get('customer/currentbookingLists', 'API\BookinController@CustomercurrentbookingLists');
    Route::get('customer/futurebookingLists', 'API\BookinController@CustomerfuturebookingLists');
    Route::get('provider/allbookings', 'API\BookinController@ProviderbookingLists');
    Route::get('provider/activebookingLists', 'API\BookinController@ProvideractivebookingLists');
    Route::get('provider/canceledbookingLists', 'API\BookinController@ProvidercanceledbookingLists');
    Route::get('provider/pastbookingLists', 'API\BookinController@ProviderpastbookingLists');
    Route::get('provider/currentbookingLists', 'API\BookinController@ProvidercurrentbookingLists');
    Route::get('provider/futurebookingLists', 'API\BookinController@ProviderfuturebookingLists');
    Route::post('booking/cancel', 'API\BookinController@bookingcancel');
    Route::get('checkin', 'API\BookinController@Checkin');
    Route::get('checkout', 'API\BookinController@Checkout');
    Route::post('checkinupdate', 'API\BookinController@CheckinUpdate');
    Route::post('checkoutupdate', 'API\BookinController@CheckoutUpdate');
    Route::post('booking/listbydate', 'API\BookinController@BookingBydate');
    Route::get('booking/search', 'API\BookinController@getbookings');
    Route::post('booking/available', 'API\BookinController@availableBookings');
    Route::get('booking/{bookingid}/details', 'API\BookinController@Bookingdetails');
    /* PaymentController Route */
    //    Route::post('payment/hold', 'API\PaymentController@Paymenthold')->name('payment/hold');
    //    Route::get('savecard', 'API\PaymentController@Savedcard');
    //    Route::post('addcard', 'API\PaymentController@addcard');
    //    Route::post('payment/capture', 'API\PaymentController@captureCharge');
    /* PushNotifyController Route */
    Route::post('callpush', 'API\PushNotifyController@CallPush');


    /* TransactionController Route */
    Route::get('netpayout', 'API\TransactionController@netpayout');
    Route::get('user/payaccount/status', 'API\TransactionController@payaccountStatus');
    Route::get('user/stripeconnectid', 'API\TransactionController@saveStripeConnectId');
    Route::get('setcard', 'API\TransactionController@setCardAsDefault');
    Route::get('user/cards', 'API\TransactionController@cardlist');
    Route::post('cardsave', 'API\TransactionController@cardsave');
    Route::post('holdpurchase', 'API\TransactionController@holdpurchase');
    // Route::post('capturecharges', 'API\TransactionController@captureCharges');
    Route::post('capturecharges', 'API\TransactionController@captureChargesV2');
    Route::get('getbalance', 'API\TransactionController@getbalance');
    Route::post('refundamount', 'API\TransactionController@refundamout');
    //    Route::post('directChargePayment', 'API\TransactionController@directChargePayment');


    /* CategoryController Route */
    Route::post('category/create', 'API\CategoryController@create_category')->name('category/create');
    Route::get('categories', 'API\CategoryController@categories')->name('categories');

    /*Communication*/
    Route::post('channel/create', 'API\CommunicationController@createChannel');
    Route::post('deletechannel', 'API\CommunicationController@deleteChannel');
    Route::post('accesstoken', 'API\CommunicationController@generateToken');
    Route::get('chatlist', 'API\CommunicationController@chatlist');
    Route::post('updatechat', 'API\CommunicationController@updateChat');
    //    Route::get('accesstokenvoice', 'API\CommunicationController@generateTokenvoice');
    //    Route::get('accesstokenvideocall', 'API\CommunicationController@generateTokenVideoCall');
    Route::get('call/start', 'API\CommunicationController@startCall');
});

//Route::post('admin/login', 'API\AdminController@adminLogin');

Route::get('test', function () {
    return response()->json(['message' => 'Hello']);
});
