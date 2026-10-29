<?php
namespace App\Http\Controllers;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller {
    public function register(Request $r) {
        $d = $r->validate(['name' => 'required|max:100', 'email' => 'required|email|unique:users', 'phone' => 'required|regex:/^01[3-9]\d{8}$/',
            'password' => 'required|min:10|confirmed']);
        $u = User::create($d);           // role defaults to 'investor' in DB
        Auth::login($u); $r->session()->regenerate();
        return redirect()->route('dashboard');
    }
    public function login(Request $r) {
        $c = $r->validate(['email' => 'required|email', 'password' => 'required']);
        if (! Auth::attempt($c, $r->boolean('remember'))) return back()->withErrors(['email' => 'Invalid credentials.'])->onlyInput('email');
        $r->session()->regenerate();
        return redirect()->route('dashboard');
    }
    public function logout(Request $r) {
        Auth::logout(); $r->session()->invalidate(); $r->session()->regenerateToken();
        return redirect('/');
    }
}
