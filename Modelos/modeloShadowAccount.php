<?php
/**
 * Clase para creación, obtención y modificación de datos de objetos LDAP userPosix 
 * @author vtacius
 */
namespace LdapPM\Modelos;

class modeloShadowAccount extends \LdapPM\Objetos\objetoShadowAccount {
    
    /**
     * GETTERS
     */
    
    public function getCn() {
        return $this->entrada['cn'];
    }

    public function getDisplayName() {
        return $this->entrada['displayName'];
    }

    public function getGecos() {
        return $this->entrada['gecos'];
    }

    public function getGidNumber() {
        return $this->entrada['gidNumber'];
    }
    
    public function getGivenName() {
        return $this->entrada['givenName'];
    }
    
    public function getSn() {
        return $this->entrada['sn'];
    }

    public function getHomeDirectory() {
        return $this->entrada['homeDirectory'];
    }

    public function getLoginShell() {
        return $this->entrada['loginShell'];
    }

    public function getMail() {
        return $this->entrada['mail'];
    }

    public function getO() {
        return $this->entrada['o'];
    }
    
    public function getOu() {
        return $this->entrada['ou'];
    }

    public function getObjectClass() {
        return $this->entrada['objectClass'];
    }

    public function getPostalAddress() {
        return $this->entrada['postalAddress'];
    }

    public function getShadowLastChange() {
        return $this->entrada['shadowLastChange'];
    }

    public function getShadowMax() {
        return $this->entrada['shadowMax'];
    }

    public function getShadowMin() {
        return $this->entrada['shadowMin'];
    }

    public function getTelephoneNumber() {
        return $this->entrada['telephoneNumber'];
    }

    public function getTitle() {
        return $this->entrada['title'];
    }

    public function getUid() {
        return $this->entrada['uid'];
    }

    public function getUidNumber() {
        return $this->entrada['uidNumber'];
    }
    
    public function getUserPassword(){
        return $this->entrada['userPassword'];
    }
    
    /**
     * SETTERS
     */
    public function setCn($cn) {
        $this->configurarAtributo('cn', $cn);
    }

    public function setDisplayName($displayName) {
        $this->configurarAtributo('displayName', $displayName);
    }
    
    public function setHomeDirectory($homeDirectory){
        $this->configurarAtributo('homeDirectory', $homeDirectory);
    }
    
    public function setGidNumber($gidNumber){
        $this->configurarAtributo('gidNumber', $gidNumber);
    }
    
    public function setSn($sn){
        $this->configurarAtributo('sn', $sn);
    }
    
    public function setUid($uid) {
        $this->configurarEntrada('uid', $uid);
    }
    
    public function setUidNumber($uidNumber) {
        $this->configurarEntrada('uidNumber', $uidNumber);
    }
    
    public function setUserPassword($password){
        // TODO: ¿Que putas comprobaciones puedo hacer?
        $hashPassword =  \LdapPM\Utilidades\utilidades::slappasswd($password);
        $this->configurarEntrada('userPassword', $hashPassword);
    }
}
