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

    public function history_view(Request $request)
    {
        $userId = Auth::id();
        $allTransactions = Payment::query()
            ->where('userID',$userId)
            ->latest()
            ->get();
        //$fromTransactions = User2userTransaction::query()->where('from', $userId)->latest()->get();
       // $toTransactions = User2userTransaction::query()->where('to', $userId)->latest()->get();

        return view('payment.history', compact('allTransactions'));
    }

    public function payment_for_point(PayOS $payos, Request $request){
        $request->validate([
            'points' => 'required|numeric|min:1',
        ]);
        //$data = $request->all();
        $points = $request->points;
        $user = Auth::user();
        if($points > 0){
            $actual_money = $points * 1000;
            $o_code = $user->id + random_int(1,200);

            $new_payment = new Payment([
                'amount' => $actual_money,
                'orderCode' => $o_code,
                'point' => $points,
                'userID' => $user->id,
                'status'=>'Pending'
            ]);
            $new_payment->save();
            $transfer_data = [
                'orderCode' => $new_payment->orderCode,
                'amount' => $actual_money,
                'description' => 'Noteket Thanh toan: Mua'. (string)$points.'-'. (string)$user->id,
                'returnUrl' => route('payment.bill',$new_payment->id),
                'cancelUrl' => route('payment.bill',$new_payment->id),
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

    /**
     * Webhook PayOS. PayOS chỉ cho đăng ký MỘT URL cố định trên dashboard nên
     * route không có {id}: payment được tra bằng orderCode lấy từ payload đã
     * verify chữ ký. Bên gọi là server PayOS — trả JSON, không redirect.
     */
    public function payment_verify(Request $request){
        try{
            $webhook = $this->payOS->webhooks->verify($request->all());
        } catch (\Exception $e) {
            // Chữ ký sai / payload hỏng — từ chối thẳng, không đụng dữ liệu.
            return response()->json(['success' => false, 'message' => 'Invalid webhook'], 400);
        }

        if($webhook->code != '00'){
            // Giao dịch không thành công phía PayOS: xác nhận đã nhận để PayOS
            // ngừng gửi lại, nhưng không cộng gì.
            return response()->json(['success' => true]);
        }

        $order = Payment::where('orderCode', $webhook->orderCode)->first();
        if (!$order){
            return response()->json(['success' => false, 'message' => 'Order not found'], 404);
        }

        // Kiểm tra Pending lần nữa BÊN TRONG transaction sau khi đã giữ khóa —
        // hai webhook trùng nhau chỉ có một cái qua được.
        DB::transaction(function () use ($order){
            $payment = Payment::query()->lockForUpdate()->find($order->id);

            if (!$payment || $payment->status != 'Pending'){
                return;
            }

            User::whereKey($payment->userID)->increment('balance', $payment->point);
            $payment->update(['status' => 'Finished']);
        });

        return response()->json(['success' => true]);
    }

    public function payment_complete_bill(Request $request,int $id){
        $order = Payment::find($id);
        if ($order->userID != $request->user()->id){
            return redirect()->route('home')->with("error","");
        } else {
            return view("payment.bill", compact('order'));
        }
    }

}
