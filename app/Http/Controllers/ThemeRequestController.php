<?php

namespace App\Http\Controllers;

use App\Models\Theme_request;
use Illuminate\Http\Request;
use App\Models\ThemeRequest;

class ThemeRequestController extends Controller
{
    public function create_theme_request(Request $request){
        $request->validate([
            'name' => 'required',
            'description' => 'required',
            'style' => 'required',
            'drag_type' => 'required',
            'price' => 'required',
        ]);
        $theme_request = new Theme_request();
        $theme_request->name = $request->name;
        $theme_request->description = $request->description;
        $theme_request->style = $request->style;
        $theme_request->drag_type = $request->drag_type;
        $theme_request->price = $request->price;
        $theme_request->catalog_link = $request->catalog_link;
        $theme_request->status = 'pending';
        if(Auth::user()->exist()){    
            $theme_request->email = Auth::user()->email;
        }else{
            $theme_request->email = $request->email;
        }
        $theme_request->save();
        return redirect()->back()->with('success', 'Theme request created successfully');
    }
}
