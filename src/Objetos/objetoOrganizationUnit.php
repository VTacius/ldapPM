<?php
/**
 * Especializa las operaciones descritas en \LdapPM\Objetos\objetosLdap,
 * y hace la definición del objeto LDAP por primera vez
 * para un objeto organizationalUnit
 * @author vtacius
 */
namespace LdapPM\Objetos;
class objetoOrganizationUnit extends \LdapPM\Objetos\objetosLdap {
    public function __construct($destino, $usuario, $password = false) {
        parent::__construct($destino, $usuario, $password);
        $this->objeto='organizationalUnit';   
        $this->attrObligatorios = array('ou');
        $this->atributos = array('ou', 'description');
        $this->objectClass = array('top', 'organizationalUnit');
        $this->atributoReferencia = "ou";
    }

    public function crearEntrada() {
        $base = $this->cfgDominio['base']['organizationalUnit'];
        return $this->crearObjetoLdap($base);
    }

    public function borrarEntrada() {
        //Verificar que el grupo asociado también sea borrado
        if ($this->cfgDominio['mover_en_ou']) {
            $grupo = new \LdapPM\Modelos\modeloSambaGroupMapping($this->destino, $this->usuario, $this->password);
            $grupo->setCn($this->entrada['ou']);
            if (!$grupo->verificaExistencia()) {
                return $this->borrarObjetoLdap();
            }else{
                $this->configurarErrorLdap('Actualizacion', 'Aún existe un grupo asociado a esta OU');
                return false;
            }
        }else{
            return $this->borrarObjetoLdap();
         }
        
    }

}
