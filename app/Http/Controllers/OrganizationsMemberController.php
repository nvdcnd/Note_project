<?php

namespace App\Http\Controllers;

use App\Mail\user_accept_organization;
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
        $request->validate(['user_list' => 'required']);
        $data = $request->all();
        $user_list = collect($data['user_list'] ?? [])
            ->filter(fn ($value) => is_string($value) && trim($value) !== '')
            ->map(fn ($value) => strtolower(trim($value)))
            ->unique()
            ->values()
            ->all();

        $addedCount = 0;
        $skippedExisting = 0;
        $invalidCount = 0;

        foreach ($user_list as $userID) {
            $targetUser = User::where('email', $userID)->first();
            if (! $targetUser) {
                $invalidCount++;

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

        if ($addedCount === 0) {
            return redirect()->route('organization', $organizationID)->with('warning', 'No new members were added');
        }

        $response = redirect()->route('organization', $organizationID)->with('success', 'Member added successfully');
        if ($invalidCount > 0 || $skippedExisting > 0) {
            $response->with('warning', 'Some invitations were skipped because they were invalid or already pending.');
        }

        return $response;
    }

    public function accept_member(Request $request, $id)
    {
        $organization_member = OrganizationsMember::find($id);
        if ($organization_member) {
            $organization_member->status = true;
            $organization_member->save();

            return redirect()->route('organization', $organization_member->organizationID)->with('success', 'Member accepted successfully');
        } else {
            return redirect()->route('home')->with('error', 'Member not found');
        }
    }

    public function decline_member(Request $request, $id)
    {
        $organization_member = OrganizationsMember::find($id);
        if ($organization_member) {
            $organization_member->status = false;
            $organization_member->save();

            return redirect()->route('home')->with('success', 'You have declined the invitation');
        } else {
            return redirect()->route('home')->with('error', 'Member not found');
        }
    }

    public function member_leave(Request $request, $id)
    {
        $organization_member = OrganizationsMember::where('organizationID', $id)->where('userID', Auth::user()->id)->first();
        if ($organization_member && $organization_member->userID == Auth::user()->id) {
            if (Organization::where('hostID', $organization_member->userID)->where('id', $id)->first()) {
                return redirect()->route('organization', $id)->with('error', 'You are the host of this organization. First, change the host. And then you can leave');
            }
            $organization_member->delete();

            return redirect()->route('home')->with('success', 'You have left the organization');
        } else {
            return redirect()->route('home')->with('error', 'Member record not found');
        }
    }

    public function remove_member(Request $request, $organizationid, $userID)
    {
        $organization_member = OrganizationsMember::where('organizationID', $organizationid)->where('userID', $userID)->first();
        $organization = Organization::find($organizationid);
        if ($organization_member && $organization && ($organization->hostID == Auth::user()->id)) {
            $organization_member->delete();

            return redirect()->route('organization', $organizationid)->with('success', 'Member removed successfully');
        } else {
            return redirect()->route('organization', $organizationid)->with('error', 'Member not found or You are not the host');
        }
    }
}
