<?php

namespace App\Http\Controllers;

use App\Models\organizations_member;
use Illuminate\Http\Request;

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
}
