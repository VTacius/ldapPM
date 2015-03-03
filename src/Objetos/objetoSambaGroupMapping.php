<?php
/**
 * Description of objetoSambaGroupMapping
 *
 * @author vtacius
 */
namespace LdapPM\Objetos;

class objetoSambaGroupMapping extends \LdapPM\Objetos\objetosLdap{
    
    public function conectar($destino, $usuario, $password = false) {
        parent::conectar($destino, $usuario, $password);
        $this->objeto='sambaGroupMapping';   
        $this->attrObligatorios = array('cn', 'gidNumber', 'sambaSID', 'sambaGroupType');
        $this->atributos = array('cn', 'gidNumber', 'memberUid', 'description', 'sambaSID', 'sambaGroupType', 'displayName');
        $this->objectClass = array('top', 'posixGroup', 'sambaGroupMapping');
        $this->atributoReferencia = "cn";
    }


    public function borrarEntrada() {
        if ($this->cfgDominio['mover_en_ou']) {
            print "También hemos de borrar la organizationUnit asociada";
        }else{
            print "Nada más ejecutamos el borrador mistico y bastará";
        }
        
    }

    /**
     * Configura valores mediante valores por defecto, lo cual será un lugar común
     * dentr de los métodos creación de los distintos objetos
     * @return string
     */
    private function iniciarProcesoCreacion(){
        $gidNumber = $this->primerIdDisponible('gidNumber');
        $this->entrada['gidNumber'] = $gidNumber;
        $this->entrada['sambaSID'] = $this->cfgDominio['sambaSID'] . '-' . $gidNumber;
        $this->entrada['sambaGroupType'] = $this->cfgDominio['sambaGroupType'];
        return $this->cfgDominio['base']['sambaGroupMapping'];
    }
    
    /**
     * Esto es complicado y le quito elegancia a todo este código:
     * Creo OU, si es correcto, intento crear el grupo, pero si falla, retorno error
     * y en el metodo superior se encargan de borrar el ou
     * @param type $recuerdo
     * @return boolean
     */
    private function verificarOuCreacion($recuerdo){
        if (!$recuerdo) {
            $ou = new \LdapPM\Modelos\modeloOrganizationUnit($this->destino, $this->usuario, $this->password);
            $ou->setOu($this->entrada['cn']);
            $ou->borrarEntrada();
            return false;
        }
        return true;
    }
    
    public function crearEntrada() {
        if ($this->cfgDominio['mover_en_ou']) {
            $ou = new \LdapPM\Modelos\modeloOrganizationUnit($this->destino, $this->usuario, $this->password);
            $ou->setOu($this->entrada['cn']);
            $ou->setDescription($this->entrada['description']);
            $resultado = $ou->crearEntrada();
            if ($resultado) {
                $base = $this->iniciarProcesoCreacion();
                $recuerdo = $this->crearObjetoLdap($base);
                return $this->verificarOuCreacion($recuerdo);
            }else{
                $this->agregarErrorLdapExterno($ou->obtenerErrorLdap());
                $ou->borrarEntrada();
                return false;
            }
        }else{
            $base = $this->iniciarProcesoCreacion();
            return $this->crearObjetoLdap($base);
        }
    }

}
