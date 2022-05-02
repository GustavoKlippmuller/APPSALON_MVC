<?php

namespace Controllers;

use MVC\Router;
use Model\Servicio;

class ServicioController {
    public static function index(Router $router) { 
        session_start();
        isAdmin();
        $router->render('servicios/index',[
            'nombre' => $_SESSION['nombre'],
            'servicios' => Servicio::all()
        ]);
    }

    public static function crear(Router $router) { 
        session_start();
        isAdmin();
        $servicio = new Servicio;
        if($_SERVER['REQUEST_METHOD'] === 'POST') {
            $servicio->sincronizar($_POST);
            if(empty($servicio->validar())) {
                $servicio->guardar(); 
                header('Location: /servicios');
            }
        }
        $router->render('servicios/crear',[
            'nombre' => $_SESSION['nombre'],
            'servicio' => $servicio,
            'alertas' => Servicio::getAlertas()
        ]);        
    }
    
    public static function actualizar(Router $router) {        
        session_start();
        isAdmin();

        if(!is_numeric($_GET['id'])) return;

        $servicio = Servicio::find($_GET['id']);
        if($_SERVER['REQUEST_METHOD'] === 'POST') {
            $servicio->sincronizar($_POST);
            if(empty($servicio->validar())) {
                $servicio->guardar(); 
                header('Location: /servicios');
            }
        }
        $router->render('servicios/actualizar',[
            'nombre' => $_SESSION['nombre'],
            'servicio' => $servicio,
            'alertas' => Servicio::getAlertas()
        ]);
    }
    
    public static function eliminar() {        
        session_start();
        isAdmin();

        if($_SERVER['REQUEST_METHOD'] === 'POST') {
            if(!is_numeric($_POST['id'])) return;
            $servicio = Servicio::find($_POST['id']);
            $servicio->eliminar();
            header('Location: /servicios');
        }
    }
}