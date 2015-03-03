<?php
/**
 * Especializa las operaciones descritas en \LdapPM\Objetos\objetosLdap,
 * y hace la definición del objeto LDAP por primera vez
 * para un objeto objetoPosix
 * @author vtacius
 */
namespace LdapPM\Objetos;

class objetoShadowAccount extends \LdapPM\Objetos\objetosLdap{
    public function conectar($destino, $usuario, $password = false) {
        parent::conectar($destino, $usuario, $password);
        $this->objeto='shadowAccount';   
        $this->attrObligatorios = array('uid','uidNumber','userPassword', 'sn','cn', 'homeDirectory', 'gidNumber');
        $this->atributos = array('cn','displayName','dn','gecos','gidNumber', 'givenName','homeDirectory','loginShell','mail','o','objectClass','ou','postalAddress', 'shadowLastChange','shadowMax','shadowMin', 'sn','telephoneNumber','title','uid','uidNumber','userPassword');
        $this->objectClass = array('top', 'person', 'organizationalPerson', 'posixAccount', 'shadowAccount', 'inetOrgPerson');
        $this->atributoReferencia = "uid";
    }
    
    
    public function crearEntrada() {
        // TODO: Ya es posible crear un uidNumber único, por tanto, un sambaSID también, si bien eso no se considera en este objeto en particular
        
        $uidNumber = $this->primerIdDisponible('uidNumber');
        $this->entrada['uidNumber'] = $uidNumber;
        
        if ($this->cfgDominio['mover_en_ou']) {
            $grupo = new \LdapPM\Modelos\modeloSambaGroupMapping($this->destino, $this->usuario, $this->password);
            $grupo->setGidNumber($this->entrada['gidNumber']);
            $ou = new \LdapPM\Modelos\modeloOrganizationUnit($this->destino, $this->usuario, $this->password);
            $ou->setOu($grupo->getCn());
            $base = $ou->getDnObjeto();
        }else{
            $base = $this->cfgDominio['base']['shadowAccount'];
        }
        return $this->crearObjetoLdap($base);
    }

    public function borrarEntrada() {
        
    }

}
