<?php

namespace App\Http\Controllers;

use App\Booking;
use App\Enumerations\DateFormat;
use App\Location;
use App\Room;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ResourceController extends Controller
{

    // RISERVATO ALLE RECEPTION
    // Rendere indisponibile 1 o pi� risorse per un periodo
    // per  lavori di ristrutturazione

    public function __construct()
    {
        $this->middleware('permission:create-room|read-room|update-room|delete-room');

        $this->data = [
            'pageTitle' => __('Room Works'),
            'pageHeader' => __('Room Works'),
            'pageSubHeader' => __('Booking Rooms for works')
        ];
    }

    public function bookingresource(){

        $pageTitle = $this->data['pageTitle'];
        $pageHeader = $this->data['pageHeader'];
        $pageSubHeader = $this->data['pageSubHeader'];

        $rooms = Room::all();

        $location = Location::all();

        return view('resources.room-works',compact('pageTitle','pageHeader','pageSubHeader','rooms','location'));
    }

    public function showRoom($id){

        $pageTitle = 'Work in progress';
        $pageHeader = 'Resource booking for work in progress';
        $pagesubHeader = 'resource booking for work in progress';

        $room = Room::where('id','=',$id)->get();

        return view('resources.resources',compact('room','pageTitle','pageHeader','pagesubHeader'));
    }

    // salva la prenotazione per lavoro
    public function get_booking(Request $request){

        $bookingTimeUno = $request->bookingTimeUno;

        $bookingTimeDue = $request->bookingTimeDue;


        $location = Location::where('sede','=',$request->location)->get();


        $start = Carbon::createFromFormat(DateFormat::DATE_RANGE_PICKER, $bookingTimeUno)->toDateTimeString();
        $end = Carbon::createFromFormat(DateFormat::DATE_RANGE_PICKER, $bookingTimeDue)->toDateTimeString();



        if ($bookingTimeUno >= $bookingTimeDue){
            return imap_alerts();
        } else {

            $booking_search = Booking::where('room_id','=',$request['roomId'])
                                        ->where('start_date','>',$start)->get();

            foreach($booking_search as $booking_effettuati){
               if($booking_effettuati->start_date>=$start && $booking_effettuati->start_date<$end) {
                   // cancello le prenotazioni presenti nel periodo
                   $booking_effettuati->status = 2;
                   $booking_effettuati->update();

               } else {

               }
            }

            $booking = new Booking();

            $booking->room_id = $request['roomId'];
            $booking->booked_by = Auth::user()->id;
            $booking->booked_name = $request->areatesto. ' - ' . User::find(Auth::user()->id)->name;
            $booking->start_date = $start;
            $booking->end_date = $end;
            $booking->location_id = $location[0]->id;
            $booking->location = $request->location;
            $booking->price = 0;
            $booking->total_price = 0;
            $booking->price_tot_optional = 0;
            $booking->status = 0;
            $booking->info = 'PICKCENTER: '.$request->areatesto;

            $booking->save();

            return redirect('dashboard/calendar/all');
        }
    }

}