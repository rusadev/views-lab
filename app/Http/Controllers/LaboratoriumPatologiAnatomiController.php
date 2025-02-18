<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LaboratoriumPatologiAnatomiController extends Controller
{
    //
    public function index()
    {
        return view('result-lab.pa.index');
    }

}
