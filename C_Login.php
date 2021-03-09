<?php
defined('BASEPATH') or exit('No direct script access allowed');
class C_Login extends CI_Controller {
    public function __construct() {
        parent::__construct();
        //cargamos el modelo del login
        $this->load->model('Login/M_Login');
    }
    public function index() {
        //preguntamos si existe una session logueada que sea distinto de null, en caso de que si, nos manda a la vista de welcome
        if ($this->session->userdata('logueado') != null) {
            redirect(base_url() . 'principal');
        } else {
            redirect(base_url() . 'welcome');
        }
    }
    /**
     * Este es una funcion para validar si existe o no el usuario
     */
    public function validarUser() {
        //se le pasa por post el usuario
        $usuario = $this->input->post('usuario');

        //la contraseña
        $contraseña = $this->input->post('pass');

        /*
         * El metodo validarUsuarioModel retorna un array de lo cual entre ellos existe un key que se llama logueado, si $datos['logueado']=true entonces se establece un array de session que nos permite armar
         * codeigniter con $this->session->set_userdata(), en donde a este metodo le pasamos $datos que es la variable que obtiene el array del modelo
         * 
         * 
        */
        $datos = $this->M_Login->validarUsuarioModel($usuario, $contraseña);
        if ($datos['logueado']) {
            $this->session->set_userdata($datos);

            $this->borrarUrlx24Hs();
            redirect(base_url() . "principal");
        } else {
            //el acronimo de httpError en una forma de establecer una session temporal para mostrar un error, el problema de usar de esta manera 
            //radica en que una vez que creamos la session temporal debemos destuirla ya sea con unset($_SESSION['httpError']) o obligar a que la pagina recargue
            //ya que la misma aparecera si por alguna razon volvemos para el mismo lugar donde enviamos los datos
            $this->session->set_userdata("httpError", "6");
            $this->session->mark_as_flash("httpError");
            redirect(base_url());
        }
    }
    //creamos una funcion privada para obtener un codigo ramdon que ayudara a la hora de recuperar contraseña unica para cada usurio
    private function createRandomCode() {
        //estos son los caracteres que mandamos para que elija al azar 
        $caracteres = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890";
        srand((float)microtime() + 1000000);
        $i = 0;
        $pass = "";
        //el numero $i<=8 quiere decie que es la cantidad de caracteres que vamos a formar de manera unica
        while ($i <= 8) {
            $num = rand() % 33;
            $tmp = substr($caracteres, $num, 1);
            $pass = $pass . $tmp;
            $i++;
        }
        //retornamos el pass generado o la url generada con el tiempo cosa que nos de de manera unica un string que no se puede repetir 
        return time() . $pass;
    }
  
    //aca colocamos la vista que contendra la pantalla para la recuperacion de contraseñas
    public function viewSendMail() {
        $this->load->view("Login/rec_cuenta");
    }
    public function sendEmail() {
        if (!empty($this->input->post("correo"))) {

            if (empty($this->M_Login->recIdEmpleado($this->input->post("correo")))) {
                redirect(base_url() . 'Login/notFound');
            }
        } else {
            redirect(base_url() . 'Login/notFound');
        }
        $this->load->library('email');

        /*
		  * Configuramos los parámetros para enviar el email,
		  * las siguientes configuraciones es recomendable
		  * hacerlas en el fichero email.php dentro del directorio config,
		  * en este caso para hacer un ejemplo rápido lo hacemos
		  * en el propio controlador
		  */
        //Indicamos el protocolo a utilizar
        $config['protocol'] = 'smtp';

        //El servidor de correo que utilizaremos
        $config["smtp_host"] = 'smtp.gmail.com';

        //Nuestro usuario
        $config["smtp_user"] = '';

        //Nuestra contraseña
        $config["smtp_pass"] = '';

        //El puerto que utilizará el servidor smtp
        $config["smtp_port"] = '465';
        $config["smtp_crypto"] = 'ssl';

        //El juego de caracteres a utilizar
        $config['charset'] = 'utf-8';

        //Permitimos que se puedan cortar palabras
        $config['wordwrap'] = TRUE;

        //El email debe ser valido 
        $config['validate'] = true;


        //Establecemos esta configuración
        $this->email->initialize($config);
        $this->email->set_newline("\r\n");

        //Ponemos la dirección de correo que enviará el email y un nombre
        $this->email->from('email@gmail.com', 'Nombre de la persona que envia el correo');

        //ponemos hacia quien va dirigido
        $this->email->to($this->input->post("correo"), '');


        /*
		 * Ponemos el o los destinatarios para los que va el email
		 * en este caso al ser un formulario de contacto te lo enviarás a ti
		 * mismo
		 */

        //Definimos el asunto del mensaje
        $this->email->subject('Sistema de Recuperacion de Claves');
        //variable de creacion de url
        $urlUpdate = $this->createRandomCode();
        //Definimos el mensaje a enviar
        $this->email->message(
            "Usted se encuentra ahora recuperando su cuenta... Haga Click en el Siguiente enlace: " .
                "Mail: " . base_url() . 'Login/RecCuenta/codPersona/' . $urlUpdate
        );
        /**
         * creamos el array para updatear los datos del usuario  
         */
        $datosUsuario = array(
            "url_update" => $urlUpdate,
            "time_update_pass" => "NOW()"
        );

        //Enviamos el email y si se produce bien o mal que avise con una flasdata
        /**
         * Aqui con la funcion updatearUrl updateamos para que el usuario recupere los datos
         * la funcion recIdEmpleado lo que hace es buscar el la identificacion unica del empleado a traves del correo, cosa que nos permita 
         * actualizar los datos del usuario con la url_generada a traves de la funcion privada createRandomCode 
         */
        if ($this->email->send() && $this->M_Login->updatearUrl($this->M_Login->recIdEmpleado($this->input->post("correo")), $datosUsuario)) {
            $this->session->set_userdata("httpError", "9");
            $this->session->mark_as_flash("httpError");
            redirect(base_url() . 'welcome');
        } else {
            $this->session->set_userdata("httpError", "10");
            $this->session->mark_as_flash("httpError");
            redirect(base_url() . 'welcome');
        }
    }
    /**
     * Vista para el reseteo de contraño
     */
    public function viewResetPass() {
        $get = $this->uri->uri_to_assoc();
        if (!empty($get["codPersona"])) {
            if (!empty($this->M_Login->verificarUrlEmpleado($get["codPersona"]))) {
                $this->session->set_userdata("codusuario", $this->M_Login->verificarUrlEmpleado($get["codPersona"]));
                $this->load->view("plantilla/header");
                $this->load->view("plantilla/nav_bar_none");
                $this->load->view("Login/reset_pass");
                $this->load->view("plantilla/footer");
                $this->load->view("Login/script_ver_pass");
            } else {
                redirect(base_url() . 'Login/notFound');
            }
        } else {
            redirect(base_url() . 'Login/notFound');
        }
    }
    /*
    * Una vez que se le haya enviado la url al usuario 
    */
    public function updateDatosUsuario() {
        if (!empty($this->session->userdata("codusuario"))) {
            if (!empty($this->input->post("pass"))) {
                $datosUsuario = array(
                    "pass" => md5($this->input->post("pass")),
                    "url_update" => " ",
                    "time_update_pass" => null
                );
                if ($this->M_Login->updatearUrl($this->session->userdata("codusuario"), $datosUsuario)) {
                    $this->session->set_userdata("httpError", "5");
                    $this->session->mark_as_flash("httpError");
                    redirect(base_url() . 'welcome');
                } else {
                    $this->session->set_userdata("httpError", "7");
                    $this->session->mark_as_flash("httpError");
                    redirect(base_url() . 'welcome');
                }
            } else {
                redirect(base_url() . 'Login/notFound');
            }
        } else {
            redirect(base_url() . 'Login/notFound');
        }
    }
    /**
     * Borrar las url que se hayan creado por el usuario que haya pasado un dia
     * Esta funcion hace llamadas a la funcion creada en el modelo el cual actualiza todos aquellos datos que tienen un 'time_update_pass'>1 dia 
     * 'time_update_pass' es un tipo de datos de fecha y tiempo 
     */
    private function borrarUrlx24Hs() {
        $datos = array(
            "url_update" => null,
            "time_update_pass" => null
        );
        $this->M_Login->borrarTimeUrl($datos);
    }
  
  //funcion para cerrar session
      public function cerrarSession() {
        $this->session->sess_destroy();
        redirect(base_url());
    }
}
