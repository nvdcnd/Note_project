<?php

namespace App\Http\Controllers;

use App\Models\ThemeRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ThemeRequestController extends Controller
{
    public function create_theme_request(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'description' => 'required',
            'style' => 'required',
            'drag_type' => 'required',
            'price' => 'required',
        ]);
        $theme_request = new ThemeRequest;
        $theme_request->name = $request->name;
        $theme_request->description = $request->description;
        $theme_request->style = $request->style;
        $theme_request->drag_type = $request->drag_type;
        $theme_request->price = $request->price;
        $theme_request->catalog_link = $request->catalog_link;
        $theme_request->status = 'pending';
        if (Auth::check()) {
            $theme_request->email = Auth::user()->email;
        } else {
            $theme_request->email = $request->email;
        }
        $theme_request->save();

        return redirect()->back()->with('success', 'Theme request created successfully');
    }
}
