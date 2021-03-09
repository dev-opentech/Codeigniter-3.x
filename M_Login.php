<?php

defined('BASEPATH') or exit('No direct script access allowed');
class M_Login extends CI_Model
{

    public function __construct()
    {
        parent::__construct();
    }
    public function index()
    {
        if ($this->session->userdata('logueado') == null) {
            redirect(base_url() . "/Login/login");
        } else {
            redirect(base_url() . "/Inicio");
        }
    }

  
    public function recIdEmpleado($email)
    {
        $this->db->select("u.id_usuario as codusuario");
        $this->db->from("persona p");
        $this->db->join("usuario u", 'pe.id_persona=u.rela_persona', "inner");
        $this->db->where("c.email like '" . $email . "'");

        $rs = $this->db->get()->result();
        if (!empty($rs)) {
            return $rs[0]->codusuario;
        } else {
            return false;
        }
    }
    /**
     * Modelo para verificar la existencia de la url
     */
    public function verificarUrlEmpleado($url)
    {
        $this->db->select("u.id_usuario as codusuario");
        $this->db->from("usuario u");
        $this->db->join("empleado e", "e.id_empleado=u.rela_empleado_empresa", "inner");
        $this->db->where(" u.url_update like'" . $url . "'");
        $rs = $this->db->get()->result();
        if (!empty($rs)) {
            $dat = $rs[0]->codusuario;
            return $dat;
        } else {
            return false;
        }
    }
    /**
     * Updatear la url a actualizar 
     */
    public function updatearUrl($CODIGO, $datos)
    {
        $this->db->trans_start();
        $this->db->trans_strict(true);
        //aqui insertamos los datos de capacitacion que guarda la empresa
        $this->db->where("id_usuario", $CODIGO);
        $this->db->update("usuario", $datos);

        if ($this->db->trans_status() === FALSE) {
            # Something went wrong.
            $this->db->trans_rollback();
            return FALSE;
        } else {
            # Everything is Perfect. 
            # Committing data to the database.
            $this->db->trans_commit();
            return TRUE;
        }
    }
    /**
     * Borrar las url
     */
    public function borrarTimeUrl($datos)
    {
        $this->db->where('DATEDIFF(NOW(),time_update_pass)=1');
        if ($this->db->update("usuario", $datos)) {
            echo true;
        } else {
            echo false;
        }
    }
}
