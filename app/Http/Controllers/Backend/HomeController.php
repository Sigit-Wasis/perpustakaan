<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index()
    {
        return view('backend.home.index');
    }
    public function profile()
    {
        return view('backend.home.profil');
    }
}
