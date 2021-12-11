<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class PrivateController extends Controller
{


    protected $var = "Ciao";


    /**
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|View
     */
    public function plist()
    {
        return view('plist.home');
    }
}
