<?php
/**
 * Description of modeloOrganizationUnit
 * @author vtacius
 */
namespace LdapPM\Modelos;

class modeloOrganizationUnit extends \LdapPM\Objetos\objetoOrganizationUnit {
    function getOu() {
        return $this->entrada['ou'];
    }

    function getDescription() {
        return $this->entrada['description'];
    }

    function setOu($ou) {
        $this->configurarEntrada('ou', $ou);
    }

    function setDescription($description) {
        $this->configurarAtributo('description', $description);
    }


}
