<?php

namespace Controllers;

use Model\Servicio;
use Model\Cita;
use Model\CitaServicio;

class APIController {
    public static function index() { echo json_encode(Servicio::all()); }

    public static function guardar() {

        $cita = new Cita($_POST);
        $resultado = $cita->guardar();

        $id = $resultado['id'];

        $idServicios = explode(",", $_POST['servicios']);

        foreach($idServicios as $idServicio) {
            $args = [
                'citaId' => $id,
                'servicioId' => $idServicio
            ];
            $citaServicio = new CitaServicio($args);
            $citaServicio->guardar();
        }

        echo json_encode(['resultado' => $resultado]);
    }

    public static function eliminar() {
        if($_SERVER['REQUEST_METHOD'] === 'POST') {
            $cita = Cita::find($_POST['id']);
            $cita->eliminar();
            header('Location: ' . $_SERVER['HTTP_REFERER']);
        }
        
    }
}