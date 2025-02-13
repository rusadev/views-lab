<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class LaboratoriumPatologiAnatomiController extends Controller
{
    //
    public function index()
    {
        return view('result-lab.pa.index');
    }

}
