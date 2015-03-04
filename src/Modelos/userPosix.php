<?php
/**
 * Clase para creación, obtención y modificación de datos de usuario
 * @author alortiz
 */
namespace LdapPM\Modelos;

class userPosix extends \LdapPM\Objetos\objetoShadowAccount{
    /**
     * Caracteres permitidos para la contraseña
     * @var array 
     */
    private $letras = array('a','b','c','d','e','f','g','h','i','j','k','l','m','n','ñ','o', 'p','q','r','s','t','u','v','w','x','y','z','_','.','1','2','3','4','5','6','7','8','9','0');
    
//    private $mailDomain;
  
    public function __construct($rdnLDAP, $passLDAP, $destino="", $parametros = array()) {
        parent::__construct($rdnLDAP, $passLDAP, $destino, $parametros);
        // Usamos desde acá la clase cifrado. 
        $this->hashito = new \clases\cifrado();
        $this->objeto='shadowAccount';   
        $this->atributos = array('cn','displayName','dn','gecos','gidNumber', 'givenName','homeDirectory','loginShell','mail','o','objectClass','ou','postalAddress', 'shadowLastChange','shadowMax','shadowMin', 'sn','telephoneNumber','title','uid','uidNumber','userPassword');
        $this->objectClass = array('top', 'person', 'organizationalPerson', 'posixAccount', 'shadowAccount', 'inetOrgPerson');
        // Configuracion leída desde la base de datos
//        $this->mailDomain = $this->config['mail_domain'];
    }
    
    public function setLoginShell($loginShell) {
        $this->configurarValor('loginShell', $loginShell);
    }

    public function setObjectClass($objectClass) {
        $this->configurarValor('objectClass', $objectClass);
    }

    public function setO($o) {
        $this->configurarValor('o', $o);
    }

    public function setOu($ou) {
        $this->configurarValor('ou', $ou);
    }

    /**
     * Quota de espacio para ownCloud
     * @param integer $postalAddress
     */
    public function setPostalAddress($postalAddress) {
        $this->configurarValor('postalAddress', $postalAddress);
    }

    public function setShadowLastChange($shadowLastChange) {
        //Default: Usar 16139
        $this->configurarValor('shadowLastChange', $shadowLastChange);
    }

    public function setShadowMax($shadowMax) {
        $this->configurarValor('shadowMax', $shadowMax);
    }

    public function setShadowMin($shadowMin) {
        $this->configurarValor('shadowMin', $shadowMin);
    }

    public function setTelephoneNumber($telephoneNumber) {
        $this->configurarValor('telephoneNumber', $telephoneNumber);
    }

    public function setTitle($title) {
        $this->configurarValor('title', $title);
    }
    
    public function setUidNumber($uidNumber) {
        $this->configurarDatos('uidNumber', $uidNumber);
    }

    /**
     * Aprovechamos la ocasión para configurar algunos parametros dependientes:
     * usuario::setHomeDirectory, usuario::setHomeMail
     * @param string $uid
     */
    public function setUid($uid) {
        $this->configurarDatos('uid', $uid);
        
        // Esto tiene un poco de sentido, pero si se hace por defecto. No hay razón para hacerlo en este momentos
        $homeDirectory = "/home/" . $uid;
        $this->setHomeDirectory($homeDirectory);
        
        if ($this->entrada['mail']==='{empty}') {
            $mail = $uid . "@" . $this->mailDomain;
            $this->setMail($mail);
        }
        
    }

    protected function setHomeDirectory($homeDirectory) {
        $this->configurarValor('homeDirectory', $homeDirectory);
    }
    
    protected function setMail($mail) {
        $this->configurarValor('mail', $mail);
    }

    /**
     * Función pública para el uso de 
     * usuario::setCn, usuario::setSn, usuario::setGecos, usuario::setGivenName, usuario::setDisplayName
     * @param string $pre_nombre
     * @param string $pre_apellido
     */
    public function configuraNombre($pre_nombre, $pre_apellido){
        $nombre = rtrim ($pre_nombre);
        $apellido = rtrim ($pre_apellido);
        $this->setCn($nombre . " " . $apellido);
        $this->setSn($apellido);
        $this->setGecos($nombre . " " .  $apellido);
        $this->setGivenName($nombre);
        $this->setDisplayName($nombre . " " .  $apellido);
    }
    
    /**
     * Los siguientes procedimientos son de uso protegido por parte de configuraNombre
     * Protegidos, porque la configuracion de los mismos no tiene sentido individualmente
     * 
     */
    
    protected function setSn($sn) {
        $this->configurarValor('sn', $sn);
    }
    
    protected function setCn($cn) {
        $this->configurarValor('cn', $cn);
    }
    
    protected function setDisplayName($displayName) {
        $this->configurarValor('displayName', $displayName);
    }

    protected function setGecos($gecos) {
        $this->configurarValor('gecos', $gecos);
    }

    protected function setGivenName($givenName) {
        $this->configurarValor('givenName', $givenName);
    }

    /** 
     * Tentativamente, esta función es necesaria para un formulario de respuesta
     * No creo que algo como eso vaya en este lugar
     * Cambio para regresar {empty} propiamente dicho, y no la contraseña para 
     * {empty}
     */
    public function getUserPassword(){
        if ($this->entrada['uid'] === "{empty}" ) {
            return "{empty}";
        }else{   
            return $this->password($this->entrada['uid']);
        }
    }

    /**
     * Función pública para el uso de
     * usuario::setUserPassword, usuario::setSambaNTPassword, usuario::setSambaLMPassword
     * @param string $password
     */
    public function configuraPassword($password){
        $this->setUserPassword($password);
    }
    
    /**
     * Los siguiente procedimientos quedan para uso protegido de la clase por parte de configuraPassword
     * Según las especificaciones, ¿Para qué tendríamos que buscar estos atributos?
     */
    protected function setUserPassword($userPassword) {
        $this->configurarValor('userPassword', $this->hashito->slappasswd($userPassword));
    }

/**
   * Representa el algoritmo de la contraseña
   * Auxiliar de $this->getuserPassword
   * @param string $cadena
   * @return string
   */
    private function password($cadena){
        $valores = array();
        $valor = 0;
        $encadena = str_split(strtolower($cadena));
        foreach ($encadena as $i){
            $j = array_search($i, $this->letras) % 10;
            $valores[] = $j;
            $valor += $j;
        }
        $digito = implode(array_slice(str_split($valor), -1 ));
        $prima = implode(array_slice($valores,0, 3));
        $secon = implode(array_slice($valores,-2));
        $operacion = abs($prima - $secon);
        $longitud = strlen($operacion);
        if ($longitud == 1){
            $resultado_final = "00" . $operacion;
        }elseif($longitud == 2){
            $resultado_final = "0" . $operacion;
        }else{
            $resultado_final = $operacion;
        }
        $texto = ucfirst(implode(array_slice($encadena,0, 3)));
        $this->entrada['password'] = $texto . "_" . $digito . $resultado_final;
        return $this->entrada['password'];
    }
}
