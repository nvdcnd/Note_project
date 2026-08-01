<?php

namespace App\Http\Controllers;

use App\Models\organizations;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\organizations_member;

class OrganizationsController extends Controller
{
    public function create_organization(Request $request){
        $data = $request->all();
        
        $organization = new organizations();
        $organization->name = $data['name'];
        $organization->description = $data['description'];
        $organization->hostID = Auth::user()->id;
        $organization->save();

        $organizationID = $organization->id;
        $userID = Auth::user()->id;
        $organization_member = new organizations_member();
        $organization_member->organizationID = $organizationID;
        $organization_member->userID = $userID;
        $organization_member->save();
        
        return redirect()->route('organization')->with('success', 'Organization created successfully');
    }

    public function edit_organization(Request $request, $id){
        $data = $request->all();
        $organization = organizations::find($id);
        $organization->name = $data['name'];
        $organization->description = $data['description'];
        $organization->save();
        return redirect()->route('organization')->with('success', 'Organization edited successfully');
    }

    public function delete_organization(Request $request, $id){
        $data = $request->all();
        $organization = organizations::find($id);
        $organization->delete();
        return redirect()->route('organization')->with('success', 'Organization deleted successfully');
    }

}
