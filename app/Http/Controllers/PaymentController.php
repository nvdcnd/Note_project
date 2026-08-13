<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Payment;
use PayOS\PayOS;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class PaymentController extends Controller
{
    public function __construct(
        private PayOS $payOS   // Laravel tự inject singleton
    ) {}
    public function payment_for_point(PayOS $payos, Request $request){
        $request->validate([
            'points' => 'required',
        ]);
        $data = $request->all();
        $points = $data['point'];
        $user = Auth::user();
        if($points > 0){
            $actual_money = $points * 1000;

            $new_payment = new Payment([
                'orderCode' => $user->id.'-'.$points.'-'.time(),
                'amount' => $actual_money,
                'points' => $points,
                'userID' => $user->id,
                'status'=>'Pending'
            ]);
            $new_payment->save();
            $transfer_data = [
                'orderCode' => $user->id.'-'.$points.'-'.time(),
                'amount' => $actual_money,
                'description' => 'Noteket Thanh toan: Mua'.$points.'-'.$user->id,
                'returnUrl' => route('payment.success'),
                'cancelUrl' => route('payment.cancel')
            ];
            try {
                $payment = $payos->paymentRequests->create($transfer_data);
                return redirect($payment['checkoutUrl']);
            } catch (\Exception $e) {
                return back()->with('error', $e->getMessage());
            }
        }
    }

    public function payment_verify(PayOS $payos, Request $request,string $orderCode){
        $order = Payment::where('orderCode', $orderCode)->first();
        $user = User::query()->lockForUpdate()->where('id', $order->user_id);

        try{
            $webhook = $this->payOS->webhooks->verify($request->all());
            $ordercode = $webhook->orderCode;
            $amount = $webhook->amount;
            $code = $webhook->code;

            if($code == '00' && $ordercode == $order->orderCode && $order->status == "Pending"){
                DB::transaction(function () use ($ordercode, $user, $amount, $code){
                   $payment = Payment::query()->lockForUpdate()->where('orderCode', $ordercode);
                   $user->balance += $payment->point;
                   $user->save();
                   $payment->status = 'Done';
                   $payment->save();
                   return redirect()->route("Payment.complete.bill",$payment->id)->with("success","");
                });
            } else {
                return back()->with("error","");
            }
        } catch (\Exception $e) {
            return back()->with("error", $e->getMessage());
         }
    }

    public function payment_complete_bill(Request $request,int $id){
        $order = Payment::find($id);
        if ($order->user_id != $request->user->id){
            return redirect()->route('home')->with("error","");
        } else {
            return view("payment.bill")->with("Success", $order);
        }
    }

}
