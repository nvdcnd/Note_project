<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Models\OrganizationsMember;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrganizationsController extends Controller
{
    public function create_organization(Request $request)
    {
        $request->validate([
            "name"=>'required',
            'description'=>'required'
        ]);
        $data = $request->all();

        $organization = new Organization;
        $organization->name = $data['name'];
        $organization->description = $data['description'];
        $organization->hostID = Auth::user()->id;
        $organization->save();

        $organizationID = $organization->id;
        $userID = Auth::user()->id;
        $organization_member = new OrganizationsMember;
        $organization_member->organizationID = $organizationID;
        $organization_member->userID = $userID;
        $organization_member->save();

        return redirect()->route('organization', $organizationID)->with('success', 'Organization created successfully');
    }

    public function edit_organization(Request $request, $id)
    {
        $organization = Organization::find($id);
        if (! $organization || $organization->hostID != Auth::user()->id) {
            return redirect()->route('home')->with('error', 'You are not authorized to edit this organization');
        }
        $request->validate([
            "name"=>'required',
            'description'=>'required'
        ]);
        $data = $request->all();
        $organization->name = $data['name'];
        $organization->description = $data['description'];
        $organization->save();

        return redirect()->route('organization', $id)->with('success', 'Organization edited successfully');
    }

    public function delete_organization(Request $request, $id)
    {
        $organization = Organization::find($id);
        if (! $organization || $organization->hostID != Auth::user()->id) {
            return redirect()->route('home')->with('error', 'You are not authorized to delete this organization');
        }
        $organization->delete();

        return redirect()->route('home')->with('success', 'Organization deleted successfully');
    }
}
