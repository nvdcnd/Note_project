<?php

namespace App\Http\Controllers;

use App\Models\pivot_change_host_organization;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\organizations;
use App\Models\organizations_member;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use App\Mail\change_host_organization;
use App\Mail\user_accept_host_organization;
use App\Mail\user_accept_organization;
use App\Mail\host_changed_40_account;

class PivotChangeHostOrganizationController extends Controller
{
    public function change_host_for_organization(Request $request, $id){
        $data = $request->all();
        $organization = organizations::find($id);
        if($organization->hostID == Auth::user()->id){
            $pivot = new pivot_change_host_organization();
            $pivot->organizationID = $organization->id;
            $pivot->current_host_ID = $organization->hostID;
            //$pivot->new_host_ID = $data['new_host_ID'];
            $pivot->save();
            Mail::to($organization->hostID)->send(new change_host_organization($pivot->id));
            return redirect()->route('organization')->with('success', 'Organization host changed successfully');
        }else{
            return redirect()->route('organization')->with('error', 'You are not authorized to change host for this organization');
        }
    }

    public function change_host_real(Request $request, $id){
        $data = $request->all();
        $organization = pivot_change_host_organization::find($id);
        if($organization->current_host_ID == Auth::user()->id){
            $new_host = $data['new_host_email'];
            if(User::where('email',$new_host)->exists()){
                $user = User::where('email',$new_host)->first();
                $organization->new_host_ID = $user->id;
                $organization->new_host_acceptance_status = false;
                $organization->save();
                /*
                // $organization->hostID = $user->id;
                $member_yet = organizations_member::where('organizationID', $organization->organizationID)->where('userID', $user->id)->first();
                if(!$member_yet){
                    $member = new organizations_member();
                    $member->organizationID = $organization->organizationID;
                    $member->userID = $user->id;
                    $member->status = false;
                    $member->save();
                    //Mail::to($user->email)->send(new user_accept_organization($member->id));
                }*/
                Mail::to($user->email)->send(new user_accept_host_organization($organization->id));
                return redirect()->route('organization')->with('success', 'Organization host changed request successfully');
            }else{
                Mail::to($new_host)->send(new host_changed_40_account($new_host));
                return redirect()->route('organization')->with('error', 'User not found');
            }
        }else{
            return redirect()->route('organization')->with('error', 'You are not authorized to change host for this organization');
        }
    }

    public function delete_old_request($id){
        $organization = pivot_change_host_organization::find($id);
        $organization->delete();
        return redirect()->route('organization',$id)->with('success', 'Organization host changed successfully');
    }

    public function new_host_accept(Request $request, $id){
        $organization = pivot_change_host_organization::find($id);
        if(Auth::user()->id == $organization->new_host_ID){
            $check_joined = organzations_member::where('organzationid',$organization->organizationID)->where('userID',Auth::user()->id)->first();
            if(!$check_joined){
                $member = new organzations_member();
                $member->organzationid = $organization->organizationID;
                $member->userID = Auth::user()->id;
                $member->status = true;
                $member->save();
            }
            $organization->hostID = $organization->new_host_ID;
            $organization->new_host_acceptance_status = true;
            $organization->save();
            $organ = organization::find($organization->organizationID);
            $organ->hostID = $organization->new_host_ID;
            $organ->save();
        }
        return redirect()->route('delete.old.request',$id)->with('success', 'Organization host changed successfully');
    }

    public function new_host_decline(Request $request, $id){
        $organization = pivot_change_host_organization::find($id);
        $organization->new_host_acceptance_status = false;
        $organization->save();
        return redirect()->route('delete.old.request',$id)->with('success', 'Organization host changed successfully');
    }
}
