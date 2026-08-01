<?php

namespace App\Http\Controllers;

use App\Models\organizations_member;
use Illuminate\Http\Request;
use App\Http\Controller\PivotChangeHostOrganizationController;
use App\Models\User;
use App\Models\organizations;
//use App\Models\organizations_member;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use App\Mail\change_host_organization;
use App\Mail\user_accept_host_organization;
use App\Mail\user_accept_organization;
use App\Mail\host_changed_40_account;

class OrganizationsMemberController extends Controller
{
    public function add_member(Request $request, $organizationID){
        $data = $request->all();
        $user_list = $data['user_list'];
        foreach($user_list as $userID){
            $organization_member = new organizations_member();
            $organization_member->organizationID = $organizationID;
            if(User::where('email',$userID)->first()){
                $organization_member->userID = User::where('email',$userID)->first()->id;
                $organization_member->status = false;
                $organization_member->save();
                Mail::to($UserID)->send(new user_accept_organization($organization_member->id));
            }else{
                return redirect()->route('organization')->with('error', 'User not found');
            }
        }
        return redirect()->route('organization')->with('success', 'Member added successfully');
    }

    public function accept_member(Request $request, $id){
        $data = $request->all();
        $organization_member = organizations_member::find($id);
        if($organization_member){
            $organization_member->status = true;
            $organization_member->save();
            return redirect()->route('organization')->with('success', 'Member accepted successfully');
        }else{
            return redirect()->route('organization')->with('error', 'Member not found');
        }
    }

    public function decline_member(Request $request, $id){
        $data = $request->all();
        $organization_member = organizations_member::find($id);
        if($organization_member){
            $organization_member->status = false;
            $organization_member->save();
            return redirect()->route('organization')->with('success', 'You have declined the invitation');
        }else{
            return redirect()->route('organization')->with('error', 'Member not found');
        }
    }

    public function member_leave(Request $request, $id){
        $data = $request->all();
        $organization_member = organizations_member::where('organizationID',$id)->where('userID',Auth::user()->id)->first();
        if($organization_member->userID == Auth::user()->id){
            if(Organization::where('hostID',$organization_member->userID)->first()){
                //app()->make(PivotChangeHostOrganizationController::class)->change_host_organization(new Request(), $id, $newHostID);
                return redirect()->route('organization')->with('error', 'You are the host of this organization. First, change the host. And then you can leave');
            }
            $organization_member->delete();
            return redirect()->route('organization')->with('success', 'You have left the organization');
        }else{
            return redirect()->route('organization')->with('error', 'Member not found');
        }
    }

    public function remove_member(Request $request, $organizationid, $userID){
        $organization_member = organizations_member::where('organizationID',$organizationid)->where('userID',$userID)->first();
        $organization = organizations::find($organizationid);
        if($organization_member && ($organization->hostID == Auth::user()->id)){
            $organization_member->delete();
            return redirect()->route('organization')->with('success', 'Member removed successfully');
        }else{
            return redirect()->route('organization')->with('error', 'Member not found or You are not the host');
        }
    }
}
