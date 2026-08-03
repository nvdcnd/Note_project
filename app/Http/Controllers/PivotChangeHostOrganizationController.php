<?php

namespace App\Http\Controllers;

use App\Mail\change_host_organization;
use App\Mail\host_changed_40_acc;
use App\Mail\user_accept_host_organization;
use App\Models\organizations;
use App\Models\organizations_member;
use App\Models\pivot_change_host_organization;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class PivotChangeHostOrganizationController extends Controller
{
    public function change_host_for_organization(Request $request, $id)
    {
        $data = $request->all();
        $organization = organizations::find($id);
        if ($organization && $organization->hostID == Auth::user()->id) {
            $pivot = new pivot_change_host_organization;
            $pivot->organizationID = $organization->id;
            $pivot->current_host_ID = $organization->hostID;
            $pivot->save();
            $hostUser = User::find($organization->hostID);
            if ($hostUser) {
                Mail::to($hostUser->email)->send(new change_host_organization($pivot->id));
            }

            return redirect()->route('organization', $id)->with('success', 'Organization host changed successfully');
        } else {
            return redirect()->route('home')->with('error', 'You are not authorized to change host for this organization');
        }
    }

    public function change_host_real(Request $request, $id)
    {
        $data = $request->all();
        $pivot = pivot_change_host_organization::find($id);
        if ($pivot && $pivot->current_host_ID == Auth::user()->id) {
            $new_host = $data['new_host_email'] ?? '';
            $user = User::where('email', $new_host)->first();
            if ($user) {
                $pivot->new_host_ID = $user->id;
                $pivot->new_host_acceptance_status = false;
                $pivot->save();
                Mail::to($user->email)->send(new user_accept_host_organization($pivot->id));

                return redirect()->route('organization', $pivot->organizationID)->with('success', 'Organization host changed request successfully');
            } else {
                Mail::to($new_host)->send(new host_changed_40_acc($new_host));

                return redirect()->route('organization', $pivot->organizationID)->with('error', 'User not found');
            }
        } else {
            return redirect()->route('home')->with('error', 'You are not authorized to change host for this organization');
        }
    }

    public function delete_old_request($id)
    {
        $pivot = pivot_change_host_organization::find($id);
        if ($pivot) {
            $orgId = $pivot->organizationID;
            $pivot->delete();

            return redirect()->route('organization', $orgId)->with('success', 'Request deleted successfully');
        }

        return redirect()->route('home')->with('success', 'Organization host changed successfully');
    }

    public function new_host_accept(Request $request, $id)
    {
        $pivot = pivot_change_host_organization::find($id);
        if ($pivot && Auth::user()->id == $pivot->new_host_ID) {
            $check_joined = organizations_member::where('organizationID', $pivot->organizationID)->where('userID', Auth::user()->id)->first();
            if (! $check_joined) {
                $member = new organizations_member;
                $member->organizationID = $pivot->organizationID;
                $member->userID = Auth::user()->id;
                $member->status = true;
                $member->save();
            }
            $pivot->new_host_acceptance_status = true;
            $pivot->save();
            $organ = organizations::find($pivot->organizationID);
            if ($organ) {
                $organ->hostID = $pivot->new_host_ID;
                $organ->save();
            }
        }

        return redirect()->route('organization', $pivot->organizationID ?? 1)->with('success', 'Organization host changed successfully');
    }

    public function new_host_decline(Request $request, $id)
    {
        $pivot = pivot_change_host_organization::find($id);
        if ($pivot) {
            $pivot->new_host_acceptance_status = false;
            $pivot->save();

            return redirect()->route('organization', $pivot->organizationID)->with('success', 'Declined host invitation');
        }

        return redirect()->route('home')->with('success', 'Organization host changed successfully');
    }
}
