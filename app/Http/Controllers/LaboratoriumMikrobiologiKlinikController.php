<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class LaboratoriumMikrobiologiKlinikController extends Controller
{
    //
    public function index () 
    {
        return view('result-lab.mikro.index');
    }
}
