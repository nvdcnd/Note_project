<?php

namespace App\Http\Controllers;

use App\Mail\change_host_organization;
use App\Mail\OrganizationInvitation;
use App\Mail\user_accept_host_organization;
use App\Models\Invitation;
use App\Models\Organization;
use App\Models\OrganizationsMember;
use App\Models\PivotChangeHostOrganization;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class PivotChangeHostOrganizationController extends Controller
{
    public function change_host_for_organization(Request $request, $id)
    {
        $organization = Organization::find($id);
        if ($organization && $organization->hostID == Auth::user()->id) {
            // Cancel any previous pending host-change request for this org.
            PivotChangeHostOrganization::query()
                ->where('organizationID', $organization->id)
                ->where('new_host_acceptance_status', false)
                ->delete();

            $pivot = new PivotChangeHostOrganization;
            $pivot->organizationID = $organization->id;
            $pivot->current_host_ID = $organization->hostID;
            $pivot->save();
            $hostUser = User::find($organization->hostID);
            if ($hostUser) {
                Mail::to($hostUser->email)->queue(new change_host_organization($pivot->id));
            }

            return redirect()->route('organization', $id)->with('success', 'Đã tạo yêu cầu đổi chủ sở hữu.');
        }

        return redirect()->route('home')->with('error', 'Bạn không có quyền đổi chủ sở hữu của tổ chức này.');
    }

    public function change_host_real(Request $request, $id)
    {
        $data = $request->validate([
            'new_host_email' => ['required', 'email'],
        ]);
        $pivot = PivotChangeHostOrganization::find($id);
        if ($pivot && $pivot->current_host_ID == Auth::user()->id) {
            $new_host = strtolower(trim($data['new_host_email']));
            $user = User::where('email', $new_host)->first();
            if ($user) {
                $pivot->new_host_ID = $user->id;
                $pivot->new_host_acceptance_status = false;
                $pivot->save();
                Mail::to($user->email)->queue(new user_accept_host_organization($pivot->id));

                return redirect()->route('organization', $pivot->organizationID)->with('success', 'Đã gửi yêu cầu đổi chủ sở hữu.');
            }

            // Email chưa có tài khoản: không thể trao quyền chủ sở hữu cho một
            // người chưa tồn tại, nên mời họ vào tổ chức trước. Sau khi họ đăng
            // ký và trở thành thành viên, chủ sở hữu tạo lại yêu cầu đổi chủ.
            $pivot->new_host_ID = null;
            $pivot->new_host_acceptance_status = false;
            $pivot->save();

            $organization = Organization::find($pivot->organizationID);
            if ($organization) {
                $issued = Invitation::issueFor($organization, $new_host, Auth::id());
                Mail::to($new_host)->queue(new OrganizationInvitation($organization, $new_host, $issued['token']));
            }

            return redirect()->route('organization', $pivot->organizationID)
                ->with('warning', 'Email này chưa có tài khoản. Đã gửi lời mời tham gia tổ chức — sau khi họ đăng ký, hãy tạo lại yêu cầu đổi chủ sở hữu.');
        }

        return redirect()->route('home')->with('error', 'Bạn không có quyền đổi chủ sở hữu của tổ chức này.');
    }

    public function delete_old_request(Request $request, $id)
    {
        $pivot = PivotChangeHostOrganization::find($id);
        if ($pivot) {
            $orgId = $pivot->organizationID;
            if ($pivot->current_host_ID !== Auth::id() && $pivot->new_host_ID !== Auth::id()) {
                return redirect()->route('home')->with('error', 'Bạn không có quyền xóa yêu cầu này.');
            }
            $pivot->delete();

            return redirect()->route('organization', $orgId)->with('success', 'Đã xóa yêu cầu.');
        }

        return redirect()->route('home')->with('success', 'Đã đổi chủ sở hữu tổ chức.');
    }

    public function new_host_accept(Request $request, $id)
    {
        $pivot = PivotChangeHostOrganization::find($id);
        if ($pivot && Auth::user()->id == $pivot->new_host_ID) {
            $check_joined = OrganizationsMember::where('organizationID', $pivot->organizationID)->where('userID', Auth::user()->id)->first();
            if (! $check_joined) {
                $member = new OrganizationsMember;
                $member->organizationID = $pivot->organizationID;
                $member->userID = Auth::user()->id;
                $member->status = true;
                $member->save();
            }
            $pivot->new_host_acceptance_status = true;
            $pivot->save();
            $organ = Organization::find($pivot->organizationID);
            if ($organ) {
                $organ->hostID = $pivot->new_host_ID;
                $organ->save();
            }

            return redirect()->route('organization', $pivot->organizationID ?? 1)->with('success', 'Đã đổi chủ sở hữu tổ chức.');
        }

        return redirect()->route('home')->with('error', 'Bạn không có quyền chấp nhận yêu cầu đổi chủ sở hữu này.');
    }

    public function new_host_decline(Request $request, $id)
    {
        $pivot = PivotChangeHostOrganization::find($id);
        if ($pivot) {
            if ($pivot->new_host_ID !== Auth::id()) {
                return redirect()->route('home')->with('error', 'Bạn không có quyền từ chối yêu cầu đổi chủ sở hữu này.');
            }
            $pivot->delete();

            return redirect()->route('organization', $pivot->organizationID)->with('success', 'Đã từ chối lời mời làm chủ sở hữu.');
        }

        return redirect()->route('home')->with('error', 'Không tìm thấy yêu cầu đổi chủ sở hữu.');
    }
}
