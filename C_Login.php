<?php
defined('BASEPATH') or exit('No direct script access allowed');
class C_Login extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Login/M_Login');
    }
    public function index()
    {
        if ($this->session->userdata('logueado') != null) {
            redirect(base_url() . 'principal');
        } else {
            redirect(base_url() . 'welcome');
        }
    }
    public function validarUser()
    {
        $usuario = $this->input->post('usuario');
        $contraseña = $this->input->post('pass');
        $datos = $this->M_Login->validarUsuarioModel($usuario, $contraseña);
        if ($datos['logueado']) {
            $this->session->set_userdata($datos);
            $this->borrarUrlx24Hs();
            redirect(base_url() . "principal");
        } else {
            $this->session->set_userdata("httpError", "6");
            $this->session->mark_as_flash("httpError");
            redirect(base_url());
        }
    }
    //creamos una funcion privada para obtener un codigo ramdon que ayudara a la hora de recuperar contraseña unica para cada usurio
    private function createRandomCode()
    {
        $caracteres = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890";
        srand((float)microtime() + 1000000);
        $i = 0;
        $pass = "";
        while ($i <= 8) {
            $num = rand() % 33;
            $tmp = substr($caracteres, $num, 1);
            $pass = $pass . $tmp;
            $i++;
        }
        return time() . $pass;
    }
    public function cerrarSession()
    {
        $this->session->sess_destroy();
        redirect(base_url());
    }
    public function viewSendMail()
    {
        $this->load->view("Login/rec_cuenta");
    }
    public function sendEmail()
    {
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
        $this->email->from('villalbaricardodaniel@gmail.com', 'Sub-Secretaria de Empleo');
        $this->email->to($this->input->post("correo"), '');


        /*
		 * Ponemos el o los destinatarios para los que va el email
		 * en este caso al ser un formulario de contacto te lo enviarás a ti
		 * mismo
		 */
        //$this->email->to($this->input->post("correo"), 'Víctor Robles');

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
    public function viewResetPass()
    {
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

    public function updateDatosUsuario()
    {
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
     * Vista para datos no encontrados
     */
    public function viewNotFoundDatos()
    {
        $this->load->view("plantilla/header");
        $this->load->view("plantilla/nav_bar_none");
        $this->load->view("Login/notFoundDat");
        $this->load->view("plantilla/footer");
        $this->load->view("plantilla/script");
    }
    /**
     * Borrar las url que haya pasado un dia
     */
    private function borrarUrlx24Hs(){
        $datos=array(
            "url_update"=>null,
            "time_update_pass"=>null
        );
        $this->M_Login->borrarTimeUrl($datos);
    }
}
