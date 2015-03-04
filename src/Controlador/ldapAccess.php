<?php
/**
 * Maneja el Acceso a LDAP
 * @author alortiz
 */
namespace LdapPM\Controlador;

use Exception;
use ErrorException;

class ldapAccess {
    /**
     * Ruta del fichero que contiene la configuración 
     * @var string fichero
     */
    protected $fichero;


    /**
     * La conexión que se realiza de LDAP. Es el único resultado que vale la pena tener acá
     * @var ldap link
     */
    protected $conexionLdap;
    
    /**
     * DN del usuario con el que se están ejecutan las operaciones
     * @var string 
     */
    private $dnUsuarioConectado;
    
    /**
     * Almacena en un array con índices los errores que se van produciendo
     * @var array
     */
    protected $errorLdap;
    
    /**
     * ¿Estamos en verdad autenticados?
     * @var boolean 
     */
    private $isAuth;
    
        
    /**
     * ObjectClass que define en particular este objeto dentro de LDAP
     * TODO: Acabamos de romper con esto el orden tan bonito que llevabamos
     * @var string 
     */
    protected $objeto = "LDAP";
    
    /**
     * Retorna un array con todos los errores LDAP de las operaciones involucradas
     * @return array
     */
    public function obtenerErrorLdap(){
        return $this->errorLdap;
    }
    
    /**
     * Retorna el DN del usuario con el que se están realizando las operaciones
     * @return string
     */
    public function obtenerDNUsuarioConectado(){
        return $this->dnUsuarioConectado;
    }
    
    /**
     * Obtiene el dominio DNS del usuario
     * @return string
     */
    public function obtenerDominioDNSUsuario(){
        return \LdapPM\Utilidades\utilidades::dnLdapADominioDns($this->dnUsuarioConectado);             
    }

    /**
     * Verifica si la autenticaciòn al servidor fue realizada exitosamente
     * @throws ErrorException
     */
    protected function verificaAutenticacion(){
        if (!$this->isAuth) {
            throw new \ErrorException("La conexión no esta autenticada");
        }
    }
    
    public function estaAutenticado(){
        return $this->isAuth;
    }
    
    /**
     * Diversos atributos dentro de la configuración para el dominio que se usan
     * para facilitar la configuración de diversos atributos entre los objetos LDAP
     * @var array 
     */
    protected $cfgDominio = array();
    
    /**
     * Recoge los errores LDAP que ocurran dentro de la clase
     * @param string $indice Mensaje descriptivo
     * @param string $contenido Error en Bruto
     */
    protected function configurarErrorLdap($indice, $contenido){
        $this->errorLdap[] = array(
            'origen' => $this->objeto,
            'mensaje' =>array('titulo' => $indice, 'contenido'=> $contenido)
        );
    }
    
    /**
     * Toma un array de errorLdap de una objeto externo y lo combina con el interno
     * Pensando para que los objetos puedan mostrar de una forma más transparente 
     * los errores de los objetos que usa internamente
     * @param array $errorLdapExterno
     */
    protected function agregarErrorLdapExterno($errorLdapExterno){
        if (is_array($this->errorLdap)) {
            $this->errorLdap = array_merge($this->errorLdap, $errorLdapExterno);
        }else{
            $this->errorLdap = $errorLdapExterno;
        }
    } 


    /**
     * Recoge la configuracion del dominio dado
     * @param string $destino
     * @param string $fichero
     * @return array
     */
    protected function obtenerConfiguracionDominio($destino, $fichero){
        $yaml = new \Symfony\Component\Yaml\Parser();
        $valor = $yaml->parse(file_get_contents($fichero)); 
        $parametros = $valor['ldapPM'];
        if ($parametros['solo_default']) {
            // TODO: Implementar esto
        }else{
            return $parametros['servidores'][$destino]['configuracion'];
        }
    }

    /**
     * Obtiene las credenciales de algun usuario dentro de la configuracion si el $password 
     * no fue pasado como parametro
     * @param string $destino
     * @param string $fichero
     * @param string $usuario
     * @param string password
     * @return array
     */
    protected function obtenerCredenciales($destino, $fichero, $usuario, $password){
        if (!$password) {
            $yaml = new \Symfony\Component\Yaml\Parser();
            $valor = $yaml->parse(file_get_contents($fichero)); 
            $parametros = $valor['ldapPM'];
            if ($parametros['solo_default']) {
                // TODO: Implementar esto
            }else{
                return $parametros['servidores'][$destino][$usuario];
            }
        }else{
            return array('dn' => $usuario, 'contrasenia'=> $password);
        }
        
    }

    /**
     * Obtiene la configuración para acceder a los servidores LDAP desde la base de datos
     * o el archivo YAML
     * @param string $destino
     * @param string $fichero
     * @return array 
     */
    protected function obtenerParametrosConexion($destino, $fichero){
        // Obtenemos la configuracion para acceder desde el archivo
        $yaml = new \Symfony\Component\Yaml\Parser();
        $valor = $yaml->parse(file_get_contents($fichero)); 
        $parametros = $valor['ldapPM'];
        if ($parametros['solo_default']) {
            $conexion_parametros = $parametros['acceso_db'];
            $config = new \Doctrine\DBAL\Configuration();
            $conexion = \Doctrine\DBAL\DriverManager::getConnection($conexion_parametros, $config);
            $cmds = "SELECT * FROM configuracion where dominio=:argdominio";
            $base = $conexion->prepare($cmds);
            $base->bindValue('argdominio', $destino);
            $base->execute();
            return $base->fetch();
        }else{
            return $parametros['servidores'][$destino];
        }
    }
    
    /**
     * 
     * @param string $destino
     * @param string $usuario
     * @param mixed $password
     * @return boolean
     * @throws Exception
     */
    public function __construct($fichero) {
        $this->fichero = $fichero;
    }
    
    public function conectar($destino, $usuario, $password = false){
        
        $parametros = $this->obtenerParametrosConexion($destino, $this->fichero);
        
        $this->cfgDominio = $this->obtenerConfiguracionDominio($destino, $this->fichero);       
        
        $this->conexionLdap = ldap_connect($parametros['servidor'],  $parametros['puerto']);
        
        $credenciales = $this->obtenerCredenciales($destino, $this->fichero, $usuario, $password);
        
        ldap_set_option($this->conexionLdap, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($this->conexionLdap, LDAP_OPT_NETWORK_TIMEOUT, $parametros['timeout']);
        
        try {
            if ($credenciales['dn'] === "" || $credenciales['contrasenia'] === "") {
                throw new Exception("Verifique el estado de las credenciales");
            }elseif (($enlaceLdap = ldap_bind($this->conexionLdap, $credenciales['dn'], $credenciales['contrasenia']))) {
                $this->dnUsuarioConectado = $credenciales['dn'];
                $this->isAuth =  TRUE;
            }else{
                throw new Exception (ldap_error($this->conexionLdap));
            }        
        } catch (Exception $e) {
            $this->configurarErrorLdap('Error en conexion', $e->getMessage());
            $this->isAuth =  FALSE;
        } 
    }

}