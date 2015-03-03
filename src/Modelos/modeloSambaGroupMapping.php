<?php
/**
 * Description of modeloSambaGroupMapping
 * @author vtacius
 */
namespace LdapPM\Modelos;

class modeloSambaGroupMapping extends \LdapPM\Objetos\objetoSambaGroupMapping {
    
    function getCn() {
        return $this->entrada['cn'];
    }

    function getGidNumber() {
        return $this->entrada['gidNumber'];
    }

    function getMemberUid() {
        return $this->entrada['memberUid'];
    }

    function getDescription() {
        return $this->entrada['description'];
    }

    function getSambaSID() {
        return $this->entrada['sambaSID'];
    }

    function getSambaGroupType() {
        return $this->entrada['sambaGroupType'];
    }

    function getDisplayName() {
        return $this->entrada['displayName'];
    }

    function setCn($cn) {
        $this->configurarEntrada('cn', $cn);
    }

    function setGidNumber($gidNumber) {
        $this->configurarEntrada('gidNumber', $gidNumber);
    }

    function setMemberUid($memberUid) {
        $this->configurarAtributo('memberUid', $memberUid);
    }

    function setDescription($description) {
        $this->configurarAtributo('description', $description);
    }

    function setSambaSID($sambaSID) {
        $this->configurarAtributo('sambaSID', $sambaSID);
    }

    function setSambaGroupType($sambaGroupType) {
        $this->configurarAtributo('sambaGroupType', $sambaGroupType);
        $this->sambaGroupType = $sambaGroupType;
    }

    function setDisplayName($displayName) {
        $this->configurarAtributo('displayName', $displayName);
    }


}
