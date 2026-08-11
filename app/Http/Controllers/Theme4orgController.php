<?php

namespace App\Http\Controllers;

use App\Models\Theme4org;
use App\Models\User;
use App\Models\Theme4orgWallet;
use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;


class Theme4orgController extends Controller
{
    public function setTheme4org(Request $request, $theme_id, $org_id){
        $org = Organization::find($org_id);
        $theme4org = Theme4orgWallet::where('themeID',$theme_id)->where('OrganizationID',$org_id)->first();
        $user = Auth::user();
        if($theme4org && $user->id == $org->hostID){
            $org->themeID = $theme4org->themeID;
            $org->save();
            return response()->json(["themeID"=>$theme4org->themeID],200);
        } else {
            return response()->json(["error"=>'error'],500);
        }
    }
}
