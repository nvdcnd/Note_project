<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use ImageKit\ImageKit;

class SettingsController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        return view('settings', compact('user'));
    }

    public function updateProfile(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,'.$user->id],
        ]);

        $user->update($validated);

        return redirect()->route('settings')->with('success', 'Đã cập nhật thông tin cá nhân.');
    }

    public function AvatarUpload(Request $request, Imagekit $imagekit){
        $user = User::find(Auth::user()->id);
        $request->validate([
            "avatar"=>'required'
        ]);

        $image = $request->avatar;
        $save_image = $imagekit->uploadFile([
            'file' => fopen($image->getRealPath(),'r'),
            'fileName'=>"Avatar_of_". (string)$user->id,
            'folder'=>'/user/avatar',
            'useUniqueFilename'=> true
        ]);

        //dd($save_image);

        if(isset($save_image->error)){
            return redirect()->route('settings')->with('error','Please upload the image again');
        } else {
            //dd($save_image->result);
            $user->avatar_image_url = $save_image->result->url;
            $user->save();
            dump($user->avatar_image_url);
            return redirect()->route('settings')->with('success','Your avatar has changed');
        }
    }

    public function changePassword(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();

        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if (! Hash::check($validated['current_password'], $user->password)) {
            return redirect()->route('settings')->with('error', 'Mật khẩu hiện tại không đúng.');
        }

        $user->update(['password' => $validated['password']]);

        return redirect()->route('settings')->with('success', 'Đã đổi mật khẩu.');
    }
}
