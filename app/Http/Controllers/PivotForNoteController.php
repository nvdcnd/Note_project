<?php

namespace App\Http\Controllers;

use App\Mail\Mail40account;
use App\Mail\UserEmail;
use App\Models\Invitation;
use App\Models\Note;
use App\Models\PivotForNote;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class PivotForNoteController extends Controller
{

    public function undo_shared_note(Request $request, $id)
    {
        $pivot = PivotForNote::find($id);
        if (! $pivot) {
            return redirect()->route('home')->with('error', 'Không tìm thấy bản ghi chia sẻ.');
        }
        $note = Note::find($pivot->note_id);
        if (! $note || $note->creater_id !== Auth::user()->id) {
            return redirect()->route('home')->with('error', 'Bạn không có quyền hủy chia sẻ ghi chú này.');
        }
        $pivot->delete();

        return redirect()->route('note', $note->id)->with('success', 'Đã hủy chia sẻ ghi chú.');
    }


    public function share_note_link(Request $request, $id){
        $org = Note::findOrFail($id);
        // $user = auth()->user();
        $member = PivotForNote::where('note_id',$id)->where('shared_with',$request->user()->id)->first();
        if($request->user()){
            if ($request->user()->id == $org->creater_id){
                return redirect()->route("home")->with("error","Bạn đang là chủ của tổ chức này");
            } else if ($member){
                return redirect()->route("home")->with("error","Bạn đã được share note này trước đó");
            } else {
                $member = new PivotForNote([
                    'note_id'=>$org->id,
                    'shared_with'=>$request->user()->id,
                ]);
                $member->save();
                return redirect()->route('note',$org->id)->with('success','Note này đã được chia sẻ cho bạn');
            }
        } else {
            return redirect()->route('home')->with('Error','bạn chưa có tài khoản');
        }
    }

    public function delete_share_note(Request $request, $id){
        $note = Note::findOrFail($id);
        $member = PivotForNote::where('note_id',$id)->where('shared_with',$request->user()->id)->first();
        if($member){
            $member->delete();
            return redirect()->route('home')->with('success','Bạn đã gỡ note'.(string)$id.'khỏi danh mục note đã được chia sẻ với bạn');
        } else {
            return redirect()->route('home')->with('Error','Đã có lỗi xảy ra');
        }
    }

    public function shared_note_list(Request $request, $id){
        $shared_list = PivotForNote::with('user:id,email')->where('note_id',$id)->get();
        return response()->json($shared_list);
    }

}
