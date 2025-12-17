<?php

namespace App\Services;

class RoverServicio
{
    public function ejecutar(
        int $x,
        int $y,
        string $direccion,
        string $comandos,
        array $obstaculos = [],
        int $tamanoPlaneta = 200
    ): array {
        $mapaObstaculos = [];
        foreach ($obstaculos as $o) {
            $mapaObstaculos[$o['x'] . ',' . $o['y']] = true;
        }

        $direccion = strtoupper($direccion);
        $comandos = strtoupper($comandos);

        $obstaculoEncontrado = null;
        $abortado = false;

        foreach (str_split($comandos) as $comando) {
            if ($comando === 'L' || $comando === 'R') {
                $direccion = $this->girar($direccion, $comando);
                continue;
            }

            if ($comando === 'F') {
                [$nuevoX, $nuevoY] = $this->calcularSiguientePosicion($x, $y, $direccion);

                // Límite del mapa (0..199)
                if ($nuevoX < 0 || $nuevoX >= $tamanoPlaneta || $nuevoY < 0 || $nuevoY >= $tamanoPlaneta) {
                    $obstaculoEncontrado = ['x' => $nuevoX, 'y' => $nuevoY, 'motivo' => 'FUERA_DEL_MAPA'];
                    $abortado = true;
                    break;
                }

                // Obstáculo antes de mover
                if (isset($mapaObstaculos["$nuevoX,$nuevoY"])) {
                    $obstaculoEncontrado = ['x' => $nuevoX, 'y' => $nuevoY, 'motivo' => 'OBSTACULO'];
                    $abortado = true;
                    break;
                }

                $x = $nuevoX;
                $y = $nuevoY;
            }
        }

        return [
            'x' => $x,
            'y' => $y,
            'direccion' => $direccion,
            'abortado' => $abortado,
            'obstaculo' => $obstaculoEncontrado,
        ];
    }

    private function girar(string $direccion, string $lado): string
    {
        $orden = ['N', 'E', 'S', 'W'];
        $indice = array_search($direccion, $orden, true);

        if ($lado === 'R') {
            $indice = ($indice + 1) % 4;
        } else { // L
            $indice = ($indice - 1 + 4) % 4;
        }

        return $orden[$indice];
    }

    private function calcularSiguientePosicion(int $x, int $y, string $direccion): array
    {
        return match ($direccion) {
            'N' => [$x, $y + 1],
            'S' => [$x, $y - 1],
            'E' => [$x + 1, $y],
            'W' => [$x - 1, $y],
            default => [$x, $y],
        };
    }
}
