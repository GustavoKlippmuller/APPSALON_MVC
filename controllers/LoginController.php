<?php

namespace Controllers;
use MVC\Router;
use Model\Usuario;
use Classes\Email;

class LoginController {
    
    public static function login(Router $router) {
        $alertas = [];

        if($_SERVER['REQUEST_METHOD'] === 'POST'):
            $auth = new Usuario($_POST);
            $alertas = $auth->validarLogin();

            if(empty($alertas)):
                // Comprobar que exista el usuario
                $usuario = Usuario::where('email', $auth->email);

                if($usuario):
                    // Verificar el password
                    if( $usuario->comprobarPasswordAndVerificado($auth->password) ):
                        // Autenticar el usuario
                        session_start();

                        $_SESSION['id'] = $usuario->id;
                        $_SESSION['nombre'] = $usuario->nombre . " " . $usuario->apellido;
                        $_SESSION['email'] = $usuario->email;
                        $_SESSION['login'] = true;

                        // Redireccionamiento
                        if($usuario->admin === "1"):
                            $_SESSION['admin'] = $usuario->admin ?? null;
                            header('Location: /admin');
                        else:
                            header('Location: /cita');
                        endif;
                    endif;
                else: 
                    Usuario::setAlerta('error', 'Usuario no encontrado');
                endif;
            endif;
        endif;

        $router->render('auth/login', ['alertas' => Usuario::getAlertas() ]);
    }

    public static function logout() {
        session_start();
        $_SESSION = [];
        header('Location: /');
    }

    public static function olvide(Router $router) {
        $alertas = [];

        if($_SERVER['REQUEST_METHOD'] === 'POST'):
            $auth = new Usuario($_POST);
            $alertas = $auth->validarEmail();

            if(empty($alertas)):
                $usuario = Usuario::where('email', $auth->email);

                if($usuario && $usuario->confirmado === "1"):
                    $usuario->crearToken();
                    $usuario->guardar();

                    $email = new Email($usuario->email, $usuario->nombre, $usuario->token);
                    $email->enviarInstrucciones();

                    Usuario::setAlerta('exito', 'Revisa tu email');
                else: 
                    Usuario::setAlerta('error', 'El Usuario no existe o no esta confirmado');
                endif;
            endif;
        endif;

        $router->render('auth/olvide-password', [ 'alertas' => Usuario::getAlertas() ]);
    }

    public static function recuperar(Router $router) {
        $error = false;
        $token = s($_GET['token']);
        $usuario = Usuario::where('token', $token);

        if(empty($usuario)) {
            Usuario::setAlerta('error', 'Token No Válido');
            $error = true;
        }

        if($_SERVER['REQUEST_METHOD'] === 'POST') {

            $password = new Usuario($_POST);

            if(empty($password->validarPassword())) {
                $usuario->password = null;

                $usuario->password = $password->password;
                $usuario->hashPassword();
                $usuario->token = null;

                if($usuario->guardar()) header('Location: /');
            }
        }

        $router->render('auth/recuperar-password', [
            'alertas' => Usuario::getAlertas(), 
            'error' => $error
        ]);
    }

    public static function crear(Router $router) {

        $usuario = new Usuario;
        $alertas = [];

        if($_SERVER['REQUEST_METHOD'] === 'POST'):
            $usuario->sincronizar($_POST);
            $alertas = $usuario->validarNuevaCuenta();

            if(empty($alertas)) :
                $resultado = $usuario->existeUsuario();

                if($resultado->num_rows): $alertas = Usuario::getAlertas();
                else :
                    $usuario->hashPassword();
                    $usuario->crearToken();
                    $email = new Email($usuario->nombre, $usuario->email, $usuario->token);
                    $email->enviarConfirmacion();
                    $resultado = $usuario->guardar();
                    if($resultado) header('Location: /mensaje');
                endif;
            endif;
        endif;
        $router->render('auth/crear-cuenta', [
            'usuario' => $usuario,
            'alertas' => $alertas
        ]);
    }

    public static function mensaje(Router $router) { $router->render('auth/mensaje'); }

    public static function confirmar(Router $router) {
        $alertas = [];
        $token = s($_GET['token']);
        $usuario = Usuario::where('token', $token);

        if(empty($usuario)) Usuario::setAlerta('error', 'Token No Válido');
        else {
            $usuario->confirmado = "1";
            $usuario->token = null;
            $usuario->guardar();
            Usuario::setAlerta('exito', 'Cuenta Comprobada Correctamente');
        }
       
        $alertas = Usuario::getAlertas();

        $router->render('auth/confirmar-cuenta', [
            'alertas' => $alertas
        ]);
    }
}