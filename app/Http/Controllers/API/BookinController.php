<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\User;
use Illuminate\Http\Response;
use App\Http\Controllers\API\PushNotifyController;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\DB;
use App\Review;
use App\Linkmeservices;
use App\Bookings;
use Validator;
use Image;
use Storage;
use \DateTime;
use \DateTimeZone;
use \DateInterval;

class BookinController extends Controller
{
    public function createBooking(Request $request)
    {
        if (Auth::check()) {
            $validator = Validator::make($request->all(), ['serviceId' => 'required', 'duration' => 'required', 'price' => 'required', 'booked_at' => 'required', 'start_at' => 'required', 'end_at' => 'required', 'userTimezone' => 'required']);
            if ($validator->fails()) {
                return response()
                    ->json(['error' => $validator->errors()
                        ->first()], 401);
            }
            $input = $request->all();
            $id = Auth::user()->id;
            $servicAuthor = Linkmeservices::where('id', $request->serviceId)
                ->first();
            $servicelocation = User::where('id', $servicAuthor->provider_id)
                ->select('address')
                ->first();
            if ($servicAuthor->provider_id === $id) {
                return response()->json(['message' => 'You can not book your own services'], 403);
            } else {
                $bookingcheck = Bookings::where('booked_at', $request->booked_at)
                    ->where('start_at', $request->start_at)
                    ->where('end_at', $request->end_at)
                    ->where('serviceId', $request->serviceId)
                    ->where('status', 1)
                    ->first();
                if ($bookingcheck === null) {
                    $book = new Bookings;
                    $book->serviceId = $request->serviceId;
                    $book->service_location = $servicelocation->address;
                    $book->providerId = $servicAuthor->provider_id;
                    $book->customerId = $id;
                    $book->duration = $request->duration;
                    $book->price = $request->price;
                    $book->booked_at = $request->booked_at;
                    $book->start_at = $request->start_at;
                    $book->end_at = $request->end_at;
                    $book->bookingStartUtc = $this->localDateTimeToUTC($request->booked_at, $request->start_at, $request->userTimezone);
                    $book->bookingEndUtc = $this->localDateTimeToUTC($request->booked_at, $request->end_at, $request->userTimezone);
                    $book->save();
                    return response()
                        ->json(['message' => 'Service Booked successfully', 'booking' => $book], 200);
                } else {
                    return response()->json(['message' => 'Slot is already Booked!', 'status' => 'failed'], 400);
                }
            }
        } else {
            return response()
                ->json(['message' => 'user not authorized to book services'], 405);
        }
    }

    public function Bookingreschedule(Request $request)
    {

        if (Auth::check()) {

            $validator = Validator::make($request->all(), ['bookingId' => 'required', 'serviceId' => 'required', 'booked_at' => 'required', 'start_at' => 'required', 'end_at' => 'required']);

            if ($validator->fails()) {
                return response()
                    ->json(['error' => $validator->errors()
                        ->first()], 401);
            }

            $id = Auth::user()->id;
            $servicAuthor = Linkmeservices::where('id', $request->serviceId)
                ->first();

            if ($servicAuthor->provider_id === $id) {
                return response()->json(['message' => 'You can not book your own services'], 403);
            } else {

                $bookingcheck = Bookings::where('booked_at', $request->booked_at)
                    ->where('start_at', $request->start_at)
                    ->where('end_at', $request->end_at)
                    ->where('serviceId', $request->serviceId)
                    ->where('status', 1)
                    ->first();

                if ($bookingcheck === null) {
                    $status = Bookings::where('id', $request->bookingId)
                        ->select('status')
                        ->first();
                    if ($status->status == 1) {

                        Bookings::where('id', $request->bookingId)
                            ->update(['booked_at' => $request->booked_at, 'start_at' => $request->start_at, 'end_at' => $request->end_at, 'bookingStartUtc' => $this->localDateTimeToUTC($request->booked_at, $request->start_at, $request->userTimezone), 'bookingEndUtc' => $this->localDateTimeToUTC($request->booked_at, $request->end_at, $request->userTimezone)]);

                        $book = Bookings::where('id', $request->bookingId)
                            ->get();
                        $this->notifyBookingReschedule($request->bookingId);
                        return response()
                            ->json(['message' => 'Service Booking rescheduled successfully', 'booking' => $book], 200);
                    } else {
                        return response()->json(['message' => 'Service Booking can not be rescheduled'], 403);
                    }
                } else {
                    return response()
                        ->json(['message' => 'Slot is already Booked!', 'status' => 'failed'], 400);
                }
            }
        } //auth check
        else {
            return response()
                ->json(['message' => 'user not authorized to book services'], 405);
        }
    }

    public function Bookingdetails($bookingid)
    {

        $bookingdetails = Bookings::where('id', $bookingid)->first();

        $bookinginfo = Bookings::where('id', $bookingid)->select('duration', 'price', 'booked_at', 'start_at', 'end_at', 'status')
            ->get();

        $servicedetails = Linkmeservices::join('users', 'users.id', '=', 'linkmeservices.provider_id')->select('users.fname', 'users.lname', 'linkmeservices.*')
            ->where('linkmeservices.provider_id', $bookingdetails->providerId)
            ->where('linkmeservices.id', $bookingdetails->serviceId)
            ->get();

        $customerdetails = User::where('id', $bookingdetails->customerId)
            ->select('fname', 'lname', 'email', 'phone', 'address', 'city', 'state', 'zipcode')
            ->get();

        $bookingDetails = ['bookingInfo' => $bookinginfo, 'servicedetails' => $servicedetails, 'customerdetails' => $customerdetails];

        return response()->json($bookingDetails, 200);
    }

    public function CustomerbookingLists()
    {

        if (Auth::check()) {
            $id = Auth::user()->id;
            $bookingList = Bookings::join('linkmeservices', 'linkmeservices.id', '=', 'bookings.serviceId')
                ->select('bookings.*', 'linkmeservices.title', 'linkmeservices.service_img')
                ->where('status', '!=', 0)
                ->where('customerId', $id)->orderBy('created_at', 'DESC')
                ->get();

            return response()
                ->json(['Bookings' => $bookingList], 200);
        } else {
            return response()->json(['message' => 'user not authorized'], 405);
        }
    }

    public function CustomeractivebookingLists()
    {

        if (Auth::check()) {
            $id = Auth::user()->id;
            $bookingList = Bookings::join('linkmeservices', 'linkmeservices.id', '=', 'bookings.serviceId')->select('bookings.*', 'linkmeservices.title', 'linkmeservices.service_img')
                ->where('status', 1)
                ->where('customerId', $id)->orderBy('created_at', 'DESC')
                ->get();
            return response()
                ->json(['Bookings' => $bookingList], 200);
        } else {
            return response()->json(['message' => 'user not authorized'], 405);
        }
    }

    public function CustomercanceledbookingLists()
    {

        if (Auth::check()) {
            $id = Auth::user()->id;
            $bookingList = Bookings::join('linkmeservices', 'linkmeservices.id', '=', 'bookings.serviceId')->select('bookings.*', 'linkmeservices.title', 'linkmeservices.service_img')
                ->where('status', '!=', 0)
                ->where('customerId', $id)->where('status', 2)
                ->orderBy('created_at', 'DESC')
                ->get();
            return response()
                ->json(['Bookings' => $bookingList], 200);
        } else {
            return response()->json(['message' => 'user not authorized'], 405);
        }
    }

    public function CustomerpastbookingLists(Request $request)
    {

        if (Auth::check()) {
            $id = Auth::user()->id;

            $validator = Validator::make($request->all(), ['userTimezone' => 'required']);

            if ($validator->fails()) {
                return response()
                    ->json(['error' => $validator->errors()
                        ->first()], 401);
            }

            $clientCurrentDatetime = $this->currentClientDatetime($request->userTimezone);
            $PageSize = $request->get('PageSize');
            $bookingList = Bookings::join('linkmeservices', 'linkmeservices.id', '=', 'bookings.serviceId')->join('users', 'users.id', '=', 'bookings.providerId')
                ->select('bookings.*', 'linkmeservices.title', 'linkmeservices.service_img', 'linkmeservices.description', 'users.fname', 'users.lname', 'users.email', 'users.address')
                ->where('status', '!=', 0)
                ->where('customerId', $id)->where('booked_at', '<', $clientCurrentDatetime->format('Y-m-d'))
                ->orderBy('booked_at', 'DESC')
                ->orderBy('start_at', 'DESC')
                ->paginate($PageSize);
            return response()->json(['Bookings' => $bookingList], 200);
        } else {
            return response()->json(['message' => 'user not authorized'], 405);
        }
    }

    public function CustomercurrentbookingLists(Request $request)
    {

        if (Auth::check()) {
            $id = Auth::user()->id;
            $validator = Validator::make($request->all(), ['userTimezone' => 'required']);
            if ($validator->fails()) {
                return response()
                    ->json(['error' => $validator->errors()
                        ->first()], 401);
            }

            $clientCurrentDatetime = $this->currentClientDatetime($request->userTimezone);
            $PageSize = $request->get('PageSize');
            $bookingList = Bookings::join('linkmeservices', 'linkmeservices.id', '=', 'bookings.serviceId')->join('users', 'users.id', '=', 'bookings.providerId')
                ->select('bookings.*', 'linkmeservices.title', 'linkmeservices.service_img', 'linkmeservices.description', 'users.fname', 'users.lname', 'users.email', 'users.address')
                ->where('status', '!=', 0)
                ->where('customerId', $id)->where('booked_at', '=', $clientCurrentDatetime->format('Y-m-d'))
                ->orderBy('start_at', 'ASC')
                ->paginate($PageSize);
            return response()->json(['Bookings' => $bookingList], 200);
        } else {
            return response()->json(['message' => 'user not authorized'], 405);
        }
    }

    public function CustomerfuturebookingLists(Request $request)
    {

        if (Auth::check()) {
            $id = Auth::user()->id;

            $validator = Validator::make($request->all(), ['userTimezone' => 'required']);
            if ($validator->fails()) {
                return response()
                    ->json(['error' => $validator->errors()
                        ->first()], 401);
            }

            $clientCurrentDatetime = $this->currentClientDatetime($request->userTimezone);

            $PageSize = $request->get('PageSize');
            $bookingList = Bookings::join('linkmeservices', 'linkmeservices.id', '=', 'bookings.serviceId')->join('users', 'users.id', '=', 'bookings.providerId')
                ->select('bookings.*', 'linkmeservices.title', 'linkmeservices.service_img', 'linkmeservices.description', 'users.fname', 'users.lname', 'users.email', 'users.address')
                ->where('status', '!=', 0)
                ->where('customerId', $id)
                ->where('booked_at', '>', $clientCurrentDatetime->format('Y-m-d'))
                ->orderBy('start_at', 'ASC')
                ->paginate($PageSize);
            return response()->json(['Bookings' => $bookingList], 200);
        } else {
            return response()->json(['message' => 'user not authorized'], 405);
        }
    }

    public function ProviderbookingLists(Request $request)
    {

        if (Auth::check()) {
            $id = Auth::user()->id;
            $validator = Validator::make($request->all(), ['userTimezone' => 'required']);

            $clientCurrentDatetime = $this->currentClientDatetime($request->get('userTimezone'));

            $keyword = $request->get('data');

            $type = '='; //default today's booking

            if ($request->get('type') == 'FUTURE') {
                $type = '>';
            }

            if ($request->get('type') == 'PAST') {
                $type = '<';
            }

            $PageSize = $request->get('PageSize');

            $bookingList = Bookings::join('linkmeservices', 'linkmeservices.id', '=', 'bookings.serviceId')
                ->join('users', 'users.id', '=', 'bookings.customerId')
                ->select(
                    'bookings.id',
                    'bookings.providerId',
                    'bookings.customerId',
                    'bookings.service_location',
                    'bookings.duration',
                    'bookings.price',
                    'bookings.booked_at',
                    'bookings.start_at',
                    'bookings.end_at',
                    'bookings.status',
                    'bookings.providerTip',
                    'bookings.checkin_status',
                    'bookings.cancled_by',
                    'linkmeservices.title',
                    'linkmeservices.service_img',
                    'linkmeservices.description',
                    'users.fname',
                    'users.lname',
                    'users.email',
                    'users.avatar',
                    'users.created_at',
                    'users.address'
                )
                ->where('status', '!=', 0)
                ->where('providerId', $id)
                ->where('booked_at', $type, $clientCurrentDatetime->format('Y-m-d'))
                ->where(function ($query) use ($keyword) {
                    $query
                        ->where('users.fname', 'like', '%' . $keyword . '%')
                        ->orWhere('users.lname', 'like', '%' . $keyword . '%')
                        ->orWhere('linkmeservices.title', 'like', '%' . $keyword . '%');
                })
                ->orderBy('bookingStartUtc')
                ->paginate($PageSize);

            return response()->json(['Bookings' => $bookingList], 200);
        } else {
            return response()->json(['message' => 'user not authorized'], 405);
        }
    }

    public function ProvideractivebookingLists(Request $request)
    {

        if (Auth::check()) {
            $id = Auth::user()->id;
            $validator = Validator::make($request->all(), ['userTimezone' => 'required']);
            if ($validator->fails()) {
                return response()
                    ->json(['error' => $validator->errors()
                        ->first()], 401);
            }

            $clientCurrentDatetime = $this->currentClientDatetime($request->userTimezone);

            $bookingList = Bookings::join('linkmeservices', 'linkmeservices.id', '=', 'bookings.serviceId')->select('bookings.*', 'linkmeservices.title', 'linkmeservices.service_img')
                ->where('providerId', $id)->where('status', 1)
                ->where('booked_at', '>=', $clientCurrentDatetime->format('Y-m-d'))
                ->orderBy('created_at', 'DESC')
                ->get();
            return response()
                ->json(['Bookings' => $bookingList], 200);
        } else {
            return response()->json(['message' => 'user not authorized'], 405);
        }
    }

    public function ProvidercanceledbookingLists()
    {

        if (Auth::check()) {
            $id = Auth::user()->id;
            $bookingList = Bookings::join('linkmeservices', 'linkmeservices.id', '=', 'bookings.serviceId')->select('bookings.*', 'linkmeservices.title', 'linkmeservices.service_img')
                ->where('providerId', $id)->where('status', 2)
                ->orderBy('created_at', 'DESC')
                ->get();
            return response()
                ->json(['Bookings' => $bookingList], 200);
        } else {
            return response()->json(['message' => 'user not authorized'], 405);
        }
    }

    public function ProviderpastbookingLists(Request $request)
    {

        if (Auth::check()) {
            $id = Auth::user()->id;

            $validator = Validator::make($request->all(), ['userTimezone' => 'required']);
            if ($validator->fails()) {
                return response()
                    ->json(['error' => $validator->errors()
                        ->first()], 401);
            }

            $clientCurrentDatetime = $this->currentClientDatetime($request->userTimezone);

            $bookingList = Bookings::join('linkmeservices', 'linkmeservices.id', '=', 'bookings.serviceId')->join('users', 'users.id', '=', 'bookings.providerId')
                ->select('bookings.*', 'linkmeservices.title', 'linkmeservices.service_img', 'linkmeservices.description', 'users.fname', 'users.lname', 'users.email', 'users.address')
                ->where('providerId', $id)->where('booked_at', '<', $clientCurrentDatetime->format('Y-m-d'))
                ->where('status', '!=', 0)
                ->orderBy('created_at', 'DESC')
                ->get();
            return response()
                ->json(['Bookings' => $bookingList], 200);
        } else {
            return response()->json(['message' => 'user not authorized'], 405);
        }
    }

    public function ProvidercurrentbookingLists(Request $request)
    {

        if (Auth::check()) {
            $id = Auth::user()->id;

            $validator = Validator::make($request->all(), ['userTimezone' => 'required']);

            if ($validator->fails()) {
                return response()
                    ->json(['error' => $validator->errors()
                        ->first()], 401);
            }

            $clientCurrentDatetime = $this->currentClientDatetime($request->userTimezone);

            $bookingList = Bookings::join('linkmeservices', 'linkmeservices.id', '=', 'bookings.serviceId')->join('users', 'users.id', '=', 'bookings.providerId')
                ->select('bookings.*', 'linkmeservices.title', 'linkmeservices.service_img', 'linkmeservices.description', 'users.fname', 'users.lname', 'users.email', 'users.address')
                ->where('providerId', $id)->where('booked_at', '=', $clientCurrentDatetime->format('Y-m-d'))
                ->where('status', '!=', 0)
                ->orderBy('created_at', 'DESC')
                ->get();
            return response()
                ->json(['Bookings' => $bookingList], 200);
        } else {
            return response()->json(['message' => 'user not authorized'], 405);
        }
    }

    public function ProviderfuturebookingLists(Request $request)
    {

        if (Auth::check()) {
            $id = Auth::user()->id;

            $validator = Validator::make($request->all(), ['userTimezone' => 'required']);
            if ($validator->fails()) {
                return response()
                    ->json(['error' => $validator->errors()
                        ->first()], 401);
            }

            $clientCurrentDatetime = $this->currentClientDatetime($request->userTimezone);

            $bookingList = Bookings::join('linkmeservices', 'linkmeservices.id', '=', 'bookings.serviceId')->join('users', 'users.id', '=', 'bookings.providerId')
                ->select('bookings.*', 'linkmeservices.title', 'linkmeservices.service_img', 'linkmeservices.description', 'users.fname', 'users.lname', 'users.email', 'users.address')
                ->where('providerId', $id)->where('booked_at', '>', $clientCurrentDatetime->format('Y-m-d'))
                ->where('status', '!=', 0)
                ->orderBy('created_at', 'DESC')
                ->get();
            return response()
                ->json(['Bookings' => $bookingList], 200);
        } else {
            return response()->json(['message' => 'user not authorized'], 405);
        }
    }

    public function availableBookings(Request $request)
    {
        $validator = Validator::make($request->all(), ['providerId' => 'required', 'booking_at' => 'required', 'durationInMins' => 'required']);

        $durationInMins = $request->durationInMins;

        if ($validator->fails()) {
            return response()
                ->json(['error' => $validator->errors()
                    ->first()], 401);
        }

        $bookingcheck = Bookings::select('booked_at', 'start_at', 'duration', 'end_at')
            //			,DB::RAW('CAST(TIMESTAMPDIFF(MINUTE,start_at,end_at) / '.$durationInMins.' as UNSIGNED)  -1 as blocknextslots'))
            ->where('status', 1)
            ->where('booked_at', $request->booking_at)
            ->where('providerId', $request->providerId)
            ->orderBy('start_at')
            ->get();

        if ($bookingcheck != null) {
            return response()->json(['bookingstatus' => true, 'bookings' => $bookingcheck], 200);
        } else {
            return response()->json(['bookingstatus' => false, 'bookings' => $bookingcheck], 200);
        }
    }

    public function bookingcancel(Request $request)
    {

        if (Auth::check()) {
            $validator = Validator::make($request->all(), ['bookingid' => 'required']);

            if ($validator->fails()) {
                return response()
                    ->json(['error' => $validator->errors()
                        ->first()], 401);
            }
            $id = Auth::user()->id;

            $booking = Bookings::where('id', $request->bookingid)
                ->first();

            if ($booking->status == 2)
                return response()
                    ->json(['message' => 'Booking already cancelled.', 'status' => 'failed'], 400);


            $TransactionController = new TransactionController;
            $cancelAmountCaptureStatus = $TransactionController->captureCancellationCharge($request->bookingid);

            if ($cancelAmountCaptureStatus['status']) {

                $bookingcancel = Bookings::where('id', $request->bookingid)
                    ->update(['status' => 2, 'cancled_by' => $id]);
                $bookingcancel = Bookings::where('id', $request->bookingid)
                    ->get();

                $usertype = User::where('id', $id)->get();

                $findusertype = $usertype[0]->user_type;

                if ($findusertype == '0') {
                    $this->notifyCancelbyCustomer($request->bookingid);
                } else {
                    $this->notifyCancelbyProvider($request->bookingid);
                }

                return response()
                    ->json(['message' => 'Booking cancelled successfully'], 200);
            } else {
                return response()
                    ->json(['message' => $cancelAmountCaptureStatus['message'], 'status' => 'failed'], 400);
            }
        } else {
            return response()->json(['message' => 'user not authorized'], 405);
        }
    }

    public function Checkin(Request $request)
    {

        if (Auth::check()) {
            $id = Auth::user()->id;

            $validator = Validator::make($request->all(), ['userTimezone' => 'required']);
            if ($validator->fails()) {
                return response()
                    ->json(['error' => $validator->errors()
                        ->first()], 401);
            }

            $clientCurrentDatetime = $this->currentClientDatetime($request->userTimezone);
            $PageSize = $request->get('PageSize');
            $booking = Bookings::join('linkmeservices', 'linkmeservices.id', '=', 'bookings.serviceId')->join('users', 'users.id', '=', 'bookings.providerId')
                ->select(DB::raw('TIMESTAMPDIFF(SECOND,UTC_TIMESTAMP,bookingStartUtc) as timeLeft'), 'bookings.*', 'linkmeservices.title', 'linkmeservices.service_img', 'linkmeservices.description', 'users.fname', 'users.lname', 'users.address', 'users.avatar')
                ->where('customerId', $id)
                ->where('booked_at', '=', $clientCurrentDatetime->format('Y-m-d'))
                ->where('status', 1)
                ->where('checkin_status', 0)
                ->orderBy('bookings.bookingStartUtc')
                ->paginate($PageSize);

            return response()->json(['Bookings' => $booking], 200);
        } else {
            return response()->json(['message' => 'user not authorized'], 405);
        }
    }

    public function CheckinUpdate(Request $request)
    {

        if (Auth::check()) {
            $validator = Validator::make($request->all(), ['bookingid' => 'required']);

            $id = Auth::user()->id;
            $booking = Bookings::where('id', $request->bookingid)
                ->update(['checkin_status' => 1]);

            $this->notifyCheckInbyCustomer($request->bookingid);

            return response()
                ->json(['Bookings' => $booking], 200);
        } else {
            return response()->json(['message' => 'user not authorized'], 405);
        }
    }

    public function Checkout(Request $request)
    {

        if (Auth::check()) {
            $id = Auth::user()->id;

            $today = date('Y-m-d');
            $current_time = date('H:i');
            $currentplus2 = date('H:i', strtotime('+2 hour'));
            $PageSize = $request->get('PageSize');
            $booking = Bookings::join('linkmeservices', 'linkmeservices.id', '=', 'bookings.serviceId')->join('users', 'users.id', '=', 'bookings.providerId')
                ->select('bookings.*', 'linkmeservices.title', 'linkmeservices.service_img', 'linkmeservices.category', 'users.fname', 'users.lname', 'users.address', 'users.avatar')
                ->where('customerId', $id)
                ->where('status', '!=', 2)
                ->where('status', '!=', 4)
                ->where('checkin_status', 1)
                ->orderBy('bookings.bookingStartUtc')
                ->paginate($PageSize);
            // print_r($booking);die;
            $bookedall = array();
            $count = 0;
            foreach ($booking as $booked) {
                $booked['category'] = array_map('intval', explode(',', $booked->category));

                array_push($bookedall, $booked);
                $count++;
            }
            return response()->json(['Bookings' => $bookedall], 200);
        } else {
            return response()->json(['message' => 'user not authorized'], 405);
        }
    }

    public function CheckoutUpdate(Request $request)
    {
        $validator = Validator::make($request->all(), ['bookingid' => 'required']);

        if (!is_null($request->tip) && !is_numeric($request->tip))
            return ['status' => false, 'message' => 'Invalid tip'];

        $id = Auth::user()->id;
        $tip = 0;
        $tip = is_null($request->tip) ? 0 : $request->tip;

        $TransactionController = new TransactionController;
        $paymentStatus = $TransactionController->payment($request->bookingid, $tip);

        if ($paymentStatus['status']) {
            $this->notifyCheckOutbyCustomer($request->bookingid);
        }

        return $paymentStatus;
    }

    public function getbookings(Request $request)
    {
        $id = Auth::user()->id;
        //$PageSize = $request->get('PageSize');
        $keyword = $request->get('data');

        $searchservice = Bookings::join('linkmeservices', 'linkmeservices.id', '=', 'bookings.serviceId')->join('users as provider', 'provider.id', '=', 'bookings.providerId')
            ->join('users as customer', 'customer.id', '=', 'bookings.customerId')
            ->select('customer.fname', 'customer.lname', 'customer.avatar', 'customer.address', 'linkmeservices.title', 'linkmeservices.service_img', 'bookings.booked_at', 'bookings.start_at', 'bookings.price', 'bookings.status')
            ->where('providerId', '=', $id)->where('provider.user_type', '=', '1')
            ->where(function ($query) use ($keyword) {
                $query
                    ->where('customer.fname', 'like', '%' . $keyword . '%')
                    ->orWhere('customer.lname', 'like', '%' . $keyword . '%')
                    ->orWhere('linkmeservices.title', 'like', '%' . $keyword . '%');
            })
            ->get();

        return response()
            ->json(['data' => $searchservice]);
    }

    public function BookingBydate(Request $request)
    {

        if (Auth::check()) {
            $id = Auth::user()->id;
            $validator = Validator::make($request->all(), ['booking_at' => 'required', 'userTimezone' => 'required']);

            if ($validator->fails()) {
                return response()
                    ->json(['error' => $validator->errors()
                        ->first()], 401);
            }

            $clientCurrentDatetime = $this->currentClientDatetime($request->userTimezone);
            $bookingDate = $request->booking_at;
            $bookingTime = '00:00:00';

            if ($clientCurrentDatetime->format('Y-m-d') == $request->booking_at) {
                $bookingTime = $clientCurrentDatetime->format('H:i');
            }

            $bookingcheck = Bookings::join('linkmeservices', 'linkmeservices.id', '=', 'bookings.serviceId')->join('users', 'users.id', '=', 'bookings.customerId')
                ->select('bookings.*', 'linkmeservices.title', 'linkmeservices.service_img', 'users.fname', 'users.lname', 'users.address', 'users.avatar')
                ->where('booked_at', $bookingDate)->where('start_at', '>=', $bookingTime)->where('providerId', $id)->where('status', 1)
                ->orderBy('start_at', 'ASC')
                ->paginate(10);

            if ($bookingcheck != null) {
                return response()->json(['bookings' => $bookingcheck], 200);
            } else {
                return response()->json(['bookings' => "No Bookings today "], 200);
            }
        }
    }

    function localDateTimeToUTC($date, $time, $timezone)
    {
        $datetimeUTC = new DateTime($date . ' ' . $time, new DateTimeZone($timezone));
        $datetimeUTC->setTimezone(new DateTimeZone("UTC"));
        return $datetimeUTC;
    }

    function currentClientDatetime($timezone)
    {
        $serverdt = new DateTime();
        $serverdt->setTimezone(new DateTimeZone($timezone));
        $localDt = $serverdt;
        return $localDt;
    }

    public function notifyBookingConfimation($bookingId)
    {

        $booking = DB::table('bookings')->join('linkmeservices', 'linkmeservices.id', '=', 'bookings.serviceId')
            ->join('users as provider', 'provider.id', '=', 'bookings.providerId')
            ->join('users as customer', 'customer.id', '=', 'bookings.customerId')
            ->select('bookings.booked_at', 'bookings.start_at', 'bookings.providerId', 'bookings.customerId', 'bookings.serviceId', 'linkmeservices.title', 'linkmeservices.service_img as service_avatar', 'provider.avatar as provider_avatar', 'customer.avatar as customer_avatar', DB::RAW("concat(provider.fname,' ',provider.lname) as provider"), DB::RAW("concat(customer.fname,' ',customer.lname) as customer"))
            ->where('bookings.id', $bookingId)->get();

        $data = [
            "provider_avatar" => $booking[0]->provider_avatar, "customer_avatar" => $booking[0]->customer_avatar, "service_avatar" => $booking[0]->service_avatar, "customerId" => $booking[0]->customerId, "providerId" => $booking[0]->providerId, "serviceId" => $booking[0]->serviceId, 'ProviderName' => $booking[0]->provider, 'CustomerName' => $booking[0]->customer, 'ServiceName' => $booking[0]->title, 'booking_date' => $booking[0]->booked_at

        ];

        $jsondata = json_encode($data);

        $placeholderValues = [
            'ProviderName' => $booking[0]->provider, 'CustomerName' => $booking[0]->customer, 'ServiceName' => $booking[0]->title, 'Date' => $booking[0]->booked_at, 'StartTime' => $booking[0]->start_at,

        ];

        $customer_notification_type = 'BOOKING_APPOINTMENT_CUSTOMER';
        $provider_notification_type = 'BOOKING_APPOINTMENT_PROVIDER';

        $customer_booking_title = config('constant.NOTIFICATIONS.BOOKING_APPOINTMENT_CUSTOMER.TITLE');
        $customer_booking_message = config('constant.NOTIFICATIONS.BOOKING_APPOINTMENT_CUSTOMER.MESSAGE');

        $provider_booking_title = config('constant.NOTIFICATIONS.BOOKING_APPOINTMENT_PROVIDER.TITLE');
        $provider_booking_message = config('constant.NOTIFICATIONS.BOOKING_APPOINTMENT_PROVIDER.MESSAGE');

        $customer_booking_message = parent::preparePushMessage($customer_booking_message, $placeholderValues);
        $provider_booking_message = parent::preparePushMessage($provider_booking_message, $placeholderValues);

        $provider_id = $booking[0]->providerId;
        $customer_id = $booking[0]->customerId;

        $pushnotify = new PushNotifyController();
        $pushnotify->send($customer_id, $customer_notification_type, $customer_booking_title, $customer_booking_message, $jsondata);
        $pushnotify->send($provider_id, $provider_notification_type, $provider_booking_title, $provider_booking_message, $jsondata);
    }


    public function notifyCheckInbyCustomer($bookingId)
    {

        $booking = DB::table('bookings')->join('linkmeservices', 'linkmeservices.id', '=', 'bookings.serviceId')
            ->join('users as provider', 'provider.id', '=', 'bookings.providerId')
            ->join('users as customer', 'customer.id', '=', 'bookings.customerId')
            ->select('bookings.booked_at', 'bookings.start_at', 'bookings.providerId', 'bookings.customerId', 'bookings.serviceId', 'linkmeservices.title', 'linkmeservices.service_img as service_avatar', 'provider.avatar as provider_avatar', 'customer.avatar as customer_avatar', DB::RAW("concat(provider.fname,' ',provider.lname) as provider"), DB::RAW("concat(customer.fname,' ',customer.lname) as customer"))
            ->where('bookings.id', $bookingId)->first();

        $data = [
            "provider_avatar" => $booking->provider_avatar,
            "customer_avatar" => $booking->customer_avatar,
            "service_avatar" => $booking->service_avatar,
            "customerId" => $booking->customerId,
            "providerId" => $booking->providerId,
            "serviceId" => $booking->serviceId,
            "ProviderName" => $booking->provider,
            "CustomerName" => $booking->customer,
            "ServiceName" => $booking->title,
            "booking_date" => $booking->booked_at
        ];

        $jsondata = json_encode($data);

        $placeholderValues = ['CustomerName' => $booking->customer, 'ServiceName' => $booking->title];

        $notificati_type = 'BOOKING_CHECKEDIN_CUSTOMER';

        $provider_title = config('constant.NOTIFICATIONS.BOOKING_CHECKEDIN_CUSTOMER.TITLE');
        $provider_message = config('constant.NOTIFICATIONS.BOOKING_CHECKEDIN_CUSTOMER.MESSAGE');

        $provider_title = parent::preparePushMessage($provider_title, $placeholderValues);
        $provider_message = parent::preparePushMessage($provider_message, $placeholderValues);

        $provider_id = $booking->providerId;

        $pushnotify = new PushNotifyController();

        $pushnotify->send($provider_id, $notificati_type, $provider_title, $provider_message, $jsondata);
    }

    public function notifyCheckOutbyCustomer($bookingId)
    {

        $booking = DB::table('bookings')
            ->join('linkmeservices', 'linkmeservices.id', '=', 'bookings.serviceId')
            ->join('users as provider', 'provider.id', '=', 'bookings.providerId')
            ->join('users as customer', 'customer.id', '=', 'bookings.customerId')
            ->select('bookings.booked_at', 'bookings.start_at', 'bookings.providerId', 'bookings.customerId', 'bookings.serviceId', 'linkmeservices.title', 'linkmeservices.service_img as service_avatar', 'provider.avatar as provider_avatar', 'customer.avatar as customer_avatar', DB::RAW("concat(provider.fname,' ',provider.lname) as provider"), DB::RAW("concat(customer.fname,' ',customer.lname) as customer"))
            ->where('bookings.id', $bookingId)->first();


        $data = [
            "provider_avatar" => $booking->provider_avatar,
            "customer_avatar" => $booking->customer_avatar,
            "service_avatar" => $booking->service_avatar,
            "customerId" => $booking->customerId,
            "providerId" => $booking->providerId,
            "serviceId" => $booking->serviceId,
            "ProviderName" => $booking->provider,
            "CustomerName" => $booking->customer,
            "ServiceName" => $booking->title,
            "booking_date" => $booking->booked_at
        ];

        $jsondata = json_encode($data);

        $placeholderValues = ['CustomerName' => $booking->customer, 'ServiceName' => $booking->title];

        $notificati_type = 'BOOKING_CHECKEDOUT_CUSTOMER';

        $provider_title = config('constant.NOTIFICATIONS.BOOKING_CHECKEDOUT_CUSTOMER.TITLE');
        $provider_message = config('constant.NOTIFICATIONS.BOOKING_CHECKEDOUT_CUSTOMER.MESSAGE');

        $provider_title = parent::preparePushMessage($provider_title, $placeholderValues);
        $provider_message = parent::preparePushMessage($provider_message, $placeholderValues);

        $provider_id = $booking->providerId;

        $pushnotify = new PushNotifyController();

        $pushnotify->send($provider_id, $notificati_type, $provider_title, $provider_message, $jsondata);
    }



    public function notifyCancelbyCustomer($bookingId)
    {

        $booking = DB::table('bookings')->join('linkmeservices', 'linkmeservices.id', '=', 'bookings.serviceId')
            ->join('users as provider', 'provider.id', '=', 'bookings.providerId')
            ->join('users as customer', 'customer.id', '=', 'bookings.customerId')
            ->select('bookings.booked_at', 'bookings.start_at', 'bookings.providerId', 'bookings.customerId', 'bookings.serviceId', 'linkmeservices.title', 'linkmeservices.service_img as service_avatar', 'provider.avatar as provider_avatar', 'customer.avatar as customer_avatar', DB::RAW("concat(provider.fname,' ',provider.lname) as provider"), DB::RAW("concat(customer.fname,' ',customer.lname) as customer"))
            ->where('bookings.id', $bookingId)->get();

        $data = [
            "provider_avatar" => $booking[0]->provider_avatar, "customer_avatar" => $booking[0]->customer_avatar, "service_avatar" => $booking[0]->service_avatar, "customerId" => $booking[0]->customerId, "providerId" => $booking[0]->providerId, "serviceId" => $booking[0]->serviceId, 'ProviderName' => $booking[0]->provider, 'CustomerName' => $booking[0]->customer, 'ServiceName' => $booking[0]->title, 'booking_date' => $booking[0]->booked_at

        ];

        $jsondata = json_encode($data);

        $placeholderValues = ['ProviderName' => $booking[0]->provider, 'CustomerName' => $booking[0]->customer, 'ServiceName' => $booking[0]->title, 'Date' => $booking[0]->booked_at, 'StartTime' => $booking[0]->start_at,];

        $customer_notification_cancel_type = 'CANCEL_APPOINTMENT_CUSTOMER_BY_CUSTOMER';
        $provider_notification_cancel_type = 'CANCEL_APPOINTMENT_PROVIDER_BY_CUSTOMER';

        $customer_cancel_title = config('constant.NOTIFICATIONS.CANCEL_APPOINTMENT_CUSTOMER_BY_CUSTOMER.TITLE');
        $customer_cancel_message = config('constant.NOTIFICATIONS.CANCEL_APPOINTMENT_CUSTOMER_BY_CUSTOMER.MESSAGE');

        $provider_cancel_title = config('constant.NOTIFICATIONS.CANCEL_APPOINTMENT_PROVIDER_BY_CUSTOMER.TITLE');
        $provider_cancel_message = config('constant.NOTIFICATIONS.CANCEL_APPOINTMENT_PROVIDER_BY_CUSTOMER.MESSAGE');

        $customer_cancel_message = parent::preparePushMessage($customer_cancel_message, $placeholderValues);
        $provider_cancel_message = parent::preparePushMessage($provider_cancel_message, $placeholderValues);

        $provider_id = $booking[0]->providerId;
        $customer_id = $booking[0]->customerId;

        $pushnotify = new PushNotifyController();

        $pushnotify->send($customer_id, $customer_notification_cancel_type, $customer_cancel_title, $customer_cancel_message, $jsondata);
        $pushnotify->send($provider_id, $provider_notification_cancel_type, $provider_cancel_title, $provider_cancel_message, $jsondata);
    }



    public function notifyCancelbyProvider($bookingId)
    {

        $booking = DB::table('bookings')->join('linkmeservices', 'linkmeservices.id', '=', 'bookings.serviceId')
            ->join('users as provider', 'provider.id', '=', 'bookings.providerId')
            ->join('users as customer', 'customer.id', '=', 'bookings.customerId')
            ->select('bookings.booked_at', 'bookings.start_at', 'bookings.providerId', 'bookings.customerId', 'bookings.serviceId', 'linkmeservices.title', 'linkmeservices.service_img as service_avatar', 'provider.avatar as provider_avatar', 'customer.avatar as customer_avatar', DB::RAW("concat(provider.fname,' ',provider.lname) as provider"), DB::RAW("concat(customer.fname,' ',customer.lname) as customer"))
            ->where('bookings.id', $bookingId)->get();

        $data = [
            "provider_avatar" => $booking[0]->provider_avatar, "customer_avatar" => $booking[0]->customer_avatar, "service_avatar" => $booking[0]->service_avatar, "customerId" => $booking[0]->customerId, "providerId" => $booking[0]->providerId, "serviceId" => $booking[0]->serviceId, 'ProviderName' => $booking[0]->provider, 'CustomerName' => $booking[0]->customer, 'ServiceName' => $booking[0]->title, 'booking_date' => $booking[0]->booked_at

        ];

        $jsondata = json_encode($data);

        $placeholderValues = ['ProviderName' => $booking[0]->provider, 'CustomerName' => $booking[0]->customer, 'ServiceName' => $booking[0]->title, 'Date' => $booking[0]->booked_at, 'StartTime' => $booking[0]->start_at,];

        $customer_notification_cancel_provider_type = 'CANCEL_APPOINTMENT_CUSTOMER_BY_PROVIDER';
        $provider_notification_cancel_provider_type = 'CANCEL_APPOINTMENT_PROVIDER_BY_PROVIDER';

        $customer_cancel_provider_title = config('constant.NOTIFICATIONS.CANCEL_APPOINTMENT_CUSTOMER_BY_PROVIDER.TITLE');
        $customer_cancel_provider_message = config('constant.NOTIFICATIONS.CANCEL_APPOINTMENT_CUSTOMER_BY_PROVIDER.MESSAGE');

        $provider_cancel_provider_title = config('constant.NOTIFICATIONS.CANCEL_APPOINTMENT_PROVIDER_BY_PROVIDER.TITLE');
        $provider_cancel_provider_message = config('constant.NOTIFICATIONS.CANCEL_APPOINTMENT_PROVIDER_BY_PROVIDER.MESSAGE');

        $customer_cancel_provider_message = parent::preparePushMessage($customer_cancel_provider_message, $placeholderValues);
        $provider_cancel_provider_message = parent::preparePushMessage($provider_cancel_provider_message, $placeholderValues);

        $provider_id = $booking[0]->providerId;
        $customer_id = $booking[0]->customerId;

        $pushnotify = new PushNotifyController();

        $pushnotify->send($customer_id, $customer_notification_cancel_provider_type, $customer_cancel_provider_title, $customer_cancel_provider_message, $jsondata);
        $pushnotify->send($provider_id, $provider_notification_cancel_provider_type, $provider_cancel_provider_title, $provider_cancel_provider_message, $jsondata);
    }

    public function notifyBookingReschedule($bookingId)
    {

        $booking = DB::table('bookings')->join('linkmeservices', 'linkmeservices.id', '=', 'bookings.serviceId')
            ->join('users as provider', 'provider.id', '=', 'bookings.providerId')
            ->join('users as customer', 'customer.id', '=', 'bookings.customerId')
            ->select('bookings.booked_at', 'bookings.start_at', 'bookings.providerId', 'bookings.customerId', 'bookings.serviceId', 'linkmeservices.title', 'linkmeservices.service_img as service_avatar', 'provider.avatar as provider_avatar', 'customer.avatar as customer_avatar', DB::RAW("concat(provider.fname,' ',provider.lname) as provider"), DB::RAW("concat(customer.fname,' ',customer.lname) as customer"))
            ->where('bookings.id', $bookingId)->get();
        $data = [
            "provider_avatar" => $booking[0]->provider_avatar, "customer_avatar" => $booking[0]->customer_avatar, "service_avatar" => $booking[0]->service_avatar, "customerId" => $booking[0]->customerId, "providerId" => $booking[0]->providerId, "serviceId" => $booking[0]->serviceId, 'ProviderName' => $booking[0]->provider, 'CustomerName' => $booking[0]->customer, 'ServiceName' => $booking[0]->title, 'booking_date' => $booking[0]->booked_at

        ];

        $jsondata = json_encode($data);
        $placeholderValues = ['ProviderName' => $booking[0]->provider, 'CustomerName' => $booking[0]->customer, 'ServiceName' => $booking[0]->title, 'Date' => $booking[0]->booked_at, 'StartTime' => $booking[0]->start_at,];

        $customer_notification_reshedule_type = 'RESHEDULE_APPOINTMENT_CUSTOMER';
        $provider_notification_reshedule_type = 'RESHEDULE_APPOINTMENT_PROVIDER';

        $customer_reshedule_title = config('constant.NOTIFICATIONS.RESHEDULE_APPOINTMENT_CUSTOMER.TITLE');
        $customer_reshedule_message = config('constant.NOTIFICATIONS.RESHEDULE_APPOINTMENT_CUSTOMER.MESSAGE');

        $provider_reshedule_title = config('constant.NOTIFICATIONS.RESHEDULE_APPOINTMENT_PROVIDER.TITLE');
        $provider_reshedule_message = config('constant.NOTIFICATIONS.RESHEDULE_APPOINTMENT_PROVIDER.MESSAGE');

        $customer_reshedule_message = parent::preparePushMessage($customer_reshedule_message, $placeholderValues);
        $provider_reshedule_message = parent::preparePushMessage($provider_reshedule_message, $placeholderValues);

        $provider_id = $booking[0]->providerId;
        $customer_id = $booking[0]->customerId;

        $pushnotify = new PushNotifyController();
        $pushnotify->send($customer_id, $customer_notification_reshedule_type, $customer_reshedule_title, $customer_reshedule_message, $jsondata);
        $pushnotify->send($provider_id, $provider_notification_reshedule_type, $provider_reshedule_title, $provider_reshedule_message, $jsondata);
    }

    public function notifyRemindBookings()
    {

        $utcPlus24Hour = new DateTime("now", new DateTimeZone("UTC"));
        $utcPlus24Hour->add(new DateInterval('PT24H'));

        $utcPlus1Hour = new DateTime("now", new DateTimeZone("UTC"));
        $utcPlus1Hour->add(new DateInterval('PT1H'));

        $utcPlus30Min = new DateTime("now", new DateTimeZone("UTC"));
        $utcPlus30Min->add(new DateInterval('PT30M'));

        $bookings = DB::table('bookings')->join('linkmeservices', 'linkmeservices.id', '=', 'bookings.serviceId')
            ->join('users as provider', 'provider.id', '=', 'bookings.providerId')
            ->join('users as customer', 'customer.id', '=', 'bookings.customerId')
            ->select('bookings.booked_at', 'bookings.start_at', 'bookings.providerId', 'bookings.customerId', 'bookings.serviceId', 'linkmeservices.title', 'linkmeservices.service_img as service_avatar', 'provider.avatar as provider_avatar', 'customer.avatar as customer_avatar', DB::RAW("concat(provider.fname,' ',provider.lname) as provider"), DB::RAW("concat(customer.fname,' ',customer.lname) as customer"))
            ->whereIn('status', [0, 1])
            ->where('bookingStartUtc', '=', $utcPlus24Hour->format('Y-m-d H:i:00'))
            ->orWhere('bookingStartUtc', '=', $utcPlus1Hour->format('Y-m-d H:i:00'))
            ->orWhere('bookingStartUtc', '=', $utcPlus30Min->format('Y-m-d H:i:00'))
            ->get();



        foreach ($bookings as $booking) {

            $data = [
                "provider_avatar" => $booking->provider_avatar,
                "customer_avatar" => $booking->customer_avatar,
                "service_avatar" => $booking->service_avatar,
                "customerId" => $booking->customerId,
                "providerId" => $booking->providerId,
                "serviceId" => $booking->serviceId,
                "ProviderName" => $booking->provider,
                "CustomerName" => $booking->customer,
                "ServiceName" => $booking->title,
                "booking_date" => $booking->booked_at
            ];

            $jsondata = json_encode($data);

            $placeholderValues = ['ProviderName' => $booking->provider, 'CustomerName' => $booking->customer, 'ServiceName' => $booking->title, 'DateTime' => date_format(date_create($booking->booked_at), "m/d/Y") . ' ' . implode(':', explode(':', $booking->start_at, -1)),];

            $customer_notification_reminder_type = 'REMINDER_APPOINTMENT_CUSTOMER';
            $provider_notification_reminder_type = 'REMINDER_APPOINTMENT_PROVIDER';

            $customer_reminder_title = config('constant.NOTIFICATIONS.REMINDER_APPOINTMENT_CUSTOMER.TITLE');
            $customer_reminder_message = config('constant.NOTIFICATIONS.REMINDER_APPOINTMENT_CUSTOMER.MESSAGE');

            $provider_reminder_title = config('constant.NOTIFICATIONS.REMINDER_APPOINTMENT_PROVIDER.TITLE');
            $provider_reminder_message = config('constant.NOTIFICATIONS.REMINDER_APPOINTMENT_PROVIDER.MESSAGE');

            $customer_reminder_message = parent::preparePushMessage($customer_reminder_message, $placeholderValues);
            $provider_reminder_message = parent::preparePushMessage($provider_reminder_message, $placeholderValues);

            $provider_id = $booking->providerId;
            $customer_id = $booking->customerId;

            $pushnotify = new PushNotifyController();
            $pushnotify->send($customer_id, $customer_notification_reminder_type, $customer_reminder_title, $customer_reminder_message, $jsondata);

            $pushnotify->send($provider_id, $provider_notification_reminder_type, $provider_reminder_title, $provider_reminder_message, $jsondata);
        } // for

    }
}
