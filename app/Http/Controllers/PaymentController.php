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
            'points' => 'required|numeric|min:1',
        ]);
        //$data = $request->all();
        $points = $request->points;
        $user = Auth::user();
        if($points > 0){
            $actual_money = $points * 1000;

            $new_payment = new Payment([
                'amount' => $actual_money,
                'points' => $points,
                'userID' => $user->id,
                'status'=>'Pending'
            ]);
            $new_payment->save();
            $transfer_data = [
                'orderCode' => $new_payment->id,
                'amount' => $actual_money,
                'description' => 'Noteket Thanh toan: Mua'. (string)$points.'-'. (string)$user->id,
                'returnUrl' => route('payment.success'),
                'cancelUrl' => route('payment.cancel')
            ];
            try {
                $payment = $payos->paymentRequests->create($transfer_data);
                return redirect()->to($payment['checkoutUrl']);
            } catch (\Exception $e) {
                return back()->with('error', $e->getMessage());
            }
        } else {
            return back()->with('error','số tiền ko dc bé hơn 0');
        }
    }

    public function payment_verify(PayOS $payos, Request $request, $id){
        $order = Payment::find($id)->first();
        $user = User::find('id', $order->user_id)->first();

        try{
            $webhook = $this->payOS->webhooks->verify($request->all());
            $ordercode = $webhook->orderCode;
            $amount = $webhook->amount;
            $code = $webhook->code;

            if($code == '00' && $ordercode == $order->orderCode && $order->status == "Pending"){
                DB::transaction(function () use ($ordercode, $user){
                   $payment = Payment::query()->lockForUpdate()->where('orderCode', $ordercode)->first();
                   $user = User::query()->lockForUpdate()->where('id', $user->id)->first();
                   
                   if (!$user || !$payment){
                    return back()->with("Error");
                   }

                   User::whereKey($user->id)->increment('balance',$payment->amount);
                   $payment->update(['status'=>"Finished"]);
                   
                   /*$user->incrment('balanced',$user->balanced+$payment->amount);
                   $payment->status = 'Done';
                   $payment->save();*/
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
