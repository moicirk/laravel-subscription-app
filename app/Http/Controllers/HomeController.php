<?php

namespace App\Http\Controllers;

use App\Attribute\AuthCheck;
use Illuminate\View\View;

class HomeController extends Controller
{
    /**
     * Display the home page.
     */
    #[AuthCheck]
    public function index(): View
    {
        return view('home');
    }
}
