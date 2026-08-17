<?php

namespace App\Http\Controllers;

use App\Mail\OrganizationInvitation;
use App\Mail\user_accept_organization;
use App\Models\Invitation;
use App\Models\Organization;
use App\Models\OrganizationsMember;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class OrganizationsMemberController extends Controller
{
    public function share_add_member_link(Request $request, $id){
        $org = Organization::findOrFail($id);
        // $user = auth()->user();
        $member = OrganizationsMember::where('orgID',$id)->where('userID',$request->user()->id)->first();
        if($request->user){
            if ($request->user->id == $org->hostID){
                return redirect()->route("organization.home", $id)->with("error","Bạn đang là chủ của tổ chức này");
            } else if ($member){
                return redirect()->route("organization.home", $id)->with("error","Bạn đã là thành viên của tổ chức này");
            }
            $user = User::findOrFail($request->user->id);
            return view('organization.invite', compact('org','user'));
        } else {
            return redirect()->route('index')->with('Error','bạn chưa có tài khoản');
        }
    }

    public function accept_member(Request $request, $id)
    {
        $organization_member = OrganizationsMember::find($id);
        if ($organization_member) {
            // Only the invited user themselves may accept.
            if ($organization_member->userID !== Auth::id()) {
                return redirect()->route('home')->with('error', 'Bạn không có quyền chấp nhận lời mời này.');
            }

            $organization_member->status = true;
            $organization_member->save();

            return redirect()->route('organization', $organization_member->organizationID)->with('success', 'Đã chấp nhận thành viên.');
        }

        return redirect()->route('home')->with('error', 'Không tìm thấy thành viên.');
    }

    public function decline_member(Request $request, $id)
    {
        $organization_member = OrganizationsMember::find($id);
        if ($organization_member) {
            if ($organization_member->userID !== Auth::id()) {
                return redirect()->route('home')->with('error', 'Bạn không có quyền từ chối lời mời này.');
            }

            $organization_member->delete();

            return redirect()->route('home')->with('success', 'Bạn đã từ chối lời mời.');
        }

        return redirect()->route('home')->with('error', 'Không tìm thấy thành viên.');
    }

    public function member_leave(Request $request, $id)
    {
        $organization_member = OrganizationsMember::where('organizationID', $id)->where('userID', Auth::user()->id)->first();
        if ($organization_member && $organization_member->userID == Auth::user()->id) {
            if (Organization::where('hostID', $organization_member->userID)->where('id', $id)->first()) {
                return redirect()->route('organization', $id)->with('error', 'Bạn đang là chủ sở hữu tổ chức. Hãy chuyển quyền chủ sở hữu trước khi rời tổ chức.');
            }
            $organization_member->delete();

            return redirect()->route('organizations.index')->with('success', 'Bạn đã rời khỏi tổ chức.');
        }

        return redirect()->route('home')->with('error', 'Không tìm thấy bản ghi thành viên.');
    }

    public function remove_member(Request $request, $organizationid, $userID)
    {
        $organization_member = OrganizationsMember::where('organizationID', $organizationid)->where('userID', $userID)->first();
        $organization = Organization::find($organizationid);
        if ($organization_member && $organization && ($organization->hostID == Auth::user()->id)) {
            $organization_member->delete();

            return redirect()->route('organization', $organizationid)->with('success', 'Đã xóa thành viên.');
        }

        return redirect()->route('organization', $organizationid)->with('error', 'Không tìm thấy thành viên, hoặc bạn không phải chủ sở hữu.');
    }

}
