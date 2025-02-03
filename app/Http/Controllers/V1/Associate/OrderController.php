<?php

namespace App\Http\Controllers\V1\Associate;

use App\Http\Controllers\V1\Controller;
use App\Mail\PaymentLink;
use App\Models\Cart;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\Order;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use App\Models\EmailLog;
class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $query = Order::query();

        $query->ofAssociate()->with('student')->where('payment_status', Order::PAYMENT_STATUS_SUCCESS);

        if (request()->filled('last_month') && request()->input('last_month') == 'true') {
            $query->whereBetween('created_at', [Carbon::today()->subMonth(), Carbon::today()]);
        }

        if (request()->filled('query')) {
            $query->where('id', request()->input('query'))
                ->orWhereHas('student', function ($query) {
                    $query->where(function ($query) {
                        $query->where('name', 'like', '%' . request()->input('query') . '%')
                        ->orWhere('email', 'like', '%' . request()->input('query') . '%')
                        ->orWhere('phone', 'like', '%' . request()->input('query') . '%');
                    });
                });
        }

        if (request()->filled('date')) {
            $query->whereDate('created_at', Carbon::parse(request()->input('date')));
        }

        $orders = $query->paginate(10);

        return $this->jsonResponse('Orders', $orders);

    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    public function sendPaymentLink()
    {
        Cart::query()->where('user_id', request()->input('user_id'))->delete();

        Cart::query()->where('user_id', auth('api')->id())
            ->update(['user_id' => request()->input('user_id'), 'uuid' => Str::uuid()]);

        $user = User::query()->find(request()->input('user_id'));

        try {
            Mail::send(new PaymentLink($user));
            $email_log = new EmailLog();
            $email_log->email_to = $user->email;
            $email_log->email_from = env('MAIL_FROM_ADDRESS');
            $email_log->content = "PURCHASE LINK - JKSHAH ONLINE";
            $email_log->save();

        } catch (\Exception $exception) {
//            info ($exception->getTraceAsString());
        }
    }
}
