<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Inertia\Inertia;
use Inertia\Response;
use App\Models\Basurero;
use App\Models\Deposito;
use App\Models\TipoBasura;


class PapeleraController extends Controller
{
    //
    public function index()
    {
        $basureros = Basurero::onlyTrashed()->get();
        $depositos = Deposito::with('user', 'tipoBasura')->onlyTrashed()->get();
        $tiposBasura = TipoBasura::onlyTrashed()->get();

        return Inertia::render('admin/residuos/PapeleraList', [
            'basureros' => $basureros,
            'depositos' => $depositos,
            'tiposBasura' => $tiposBasura
        ]);
    }
}
