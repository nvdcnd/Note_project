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
    public function add_member(Request $request, $organizationID)
    {
        $organization = Organization::query()->find($organizationID);
        if (! $organization) {
            return redirect()->route('organizations.index')->with('error', 'Không tìm thấy tổ chức.');
        }

        if ($organization->hostID !== Auth::id()) {
            return redirect()->route('organization', $organization->id)->with('error', 'Chỉ chủ sở hữu mới có thể thêm thành viên.');
        }

        // Accept either user_list[] (array) or user_list_text (comma separated).
        $rawList = $request->input('user_list', []);
        if (empty($rawList) && $request->filled('user_list_text')) {
            $rawList = array_map('trim', explode(',', $request->input('user_list_text')));
        }

        $request->validate([
            'user_list' => ['nullable', 'array'],
            'user_list.*' => ['email'],
        ]);

        $user_list = collect($rawList)
            ->filter(fn ($value) => is_string($value) && trim($value) !== '')
            ->map(fn ($value) => strtolower(trim($value)))
            ->unique()
            ->values()
            ->all();

        $addedCount = 0;
        $skippedExisting = 0;
        $invitedCount = 0;

        foreach ($user_list as $email) {
            $targetUser = User::where('email', $email)->first();
            if (! $targetUser) {
                // Email chưa có tài khoản: gửi lời mời có link đăng ký thay vì
                // im lặng bỏ qua như trước.
                $issued = Invitation::issueFor($organization, $email, Auth::id());
                Mail::to($email)->queue(new OrganizationInvitation($organization, $email, $issued['token']));
                $invitedCount++;

                continue;
            }

            $alreadyMember = OrganizationsMember::query()
                ->where('organizationID', $organizationID)
                ->where('userID', $targetUser->id)
                ->exists();

            if ($alreadyMember) {
                $skippedExisting++;

                continue;
            }

            $organization_member = new OrganizationsMember;
            $organization_member->organizationID = $organizationID;
            $organization_member->userID = $targetUser->id;
            $organization_member->status = false;
            $organization_member->save();
            Mail::to($targetUser->email)->send(new user_accept_organization($organization_member->id));
            $addedCount++;
        }

        if ($addedCount === 0 && $invitedCount === 0) {
            return redirect()->route('organization', $organizationID)->with('warning', 'Không có thành viên mới nào được thêm.');
        }

        $response = redirect()->route('organization', $organizationID)->with('success', 'Đã thêm thành viên.');
        if ($skippedExisting > 0) {
            $response->with('warning', 'Một số lời mời đã bị bỏ qua vì không hợp lệ hoặc đang chờ xử lý.');
        } elseif ($invitedCount > 0 && $addedCount === 0) {
            $response->with('warning', 'Đã gửi lời mời đăng ký tới '.$invitedCount.' email chưa có tài khoản.');
        }

        return $response;
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
