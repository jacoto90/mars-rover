<?php

namespace App\Http\Controllers;

use App\Http\Requests\SimularRoverRequest;
use App\Services\RoverServicio;

class RoverController extends Controller
{
    public function simular(SimularRoverRequest $request, RoverServicio $roverServicio)
    {
        $data = $request->validated();

        $resultado = $roverServicio->ejecutar(
            x: $data['x'],
            y: $data['y'],
            direccion: $data['direccion'],
            comandos: $data['comandos'],
            obstaculos: $data['obstaculos'] ?? []
        );

        return response()->json($resultado);
    }
}
