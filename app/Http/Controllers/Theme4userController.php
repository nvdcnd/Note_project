<?php

namespace App\Http\Controllers;

use App\Models\Theme4user;
use App\Models\User;
use App\Models\Theme4userWallet;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class Theme4userController extends Controller
{
    public function set_theme_4user(Request $request, $id){
        $theme = Theme4userWallet::where('userID',Auth::user()->id)->where('themeID',$id)->first();
        if($theme){
            $user = Auth::user();
            $user->theme_id = $theme->id;
            $user->save();
            return response()->json([
                'themeID' => $theme->id,
            ],200);
        } else {
            return response()->json(["error"=>"Error"],500);
        }
    }
}
