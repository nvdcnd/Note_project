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
        $organization = Organization::query()->find($organizationID);
        if (! $organization) {
            return redirect()->route('organizations.index')->with('error', 'Organization not found');
        }

        if ($organization->hostID !== Auth::id()) {
            return redirect()->route('organization', $organization->id)->with('error', 'Only the host can add members');
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
        $invalidCount = 0;

        foreach ($user_list as $email) {
            $targetUser = User::where('email', $email)->first();
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
            // Only the invited user themselves may accept.
            if ($organization_member->userID !== Auth::id()) {
                return redirect()->route('home')->with('error', 'You are not allowed to accept this invitation');
            }

            $organization_member->status = true;
            $organization_member->save();

            return redirect()->route('organization', $organization_member->organizationID)->with('success', 'Member accepted successfully');
        }

        return redirect()->route('home')->with('error', 'Member not found');
    }

    public function decline_member(Request $request, $id)
    {
        $organization_member = OrganizationsMember::find($id);
        if ($organization_member) {
            if ($organization_member->userID !== Auth::id()) {
                return redirect()->route('home')->with('error', 'You are not allowed to decline this invitation');
            }

            $organization_member->delete();

            return redirect()->route('home')->with('success', 'You have declined the invitation');
        }

        return redirect()->route('home')->with('error', 'Member not found');
    }

    public function member_leave(Request $request, $id)
    {
        $organization_member = OrganizationsMember::where('organizationID', $id)->where('userID', Auth::user()->id)->first();
        if ($organization_member && $organization_member->userID == Auth::user()->id) {
            if (Organization::where('hostID', $organization_member->userID)->where('id', $id)->first()) {
                return redirect()->route('organization', $id)->with('error', 'You are the host of this organization. First, change the host. And then you can leave');
            }
            $organization_member->delete();

            return redirect()->route('organizations.index')->with('success', 'You have left the organization');
        }

        return redirect()->route('home')->with('error', 'Member record not found');
    }

    public function remove_member(Request $request, $organizationid, $userID)
    {
        $organization_member = OrganizationsMember::where('organizationID', $organizationid)->where('userID', $userID)->first();
        $organization = Organization::find($organizationid);
        if ($organization_member && $organization && ($organization->hostID == Auth::user()->id)) {
            $organization_member->delete();

            return redirect()->route('organization', $organizationid)->with('success', 'Member removed successfully');
        }

        return redirect()->route('organization', $organizationid)->with('error', 'Member not found or You are not the host');
    }
}
