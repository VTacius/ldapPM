<?php
/**
 * Operaciones a realizar sobre directorios LDAP
 * @author vtacius
 */
namespace LdapPM\Controlador;

use ErrorException;

class ldapOperations extends \LdapPM\Controlador\ldapAccess {
    public function __construct($destino, $usuario, $password = false) {
        parent::__construct($destino, $usuario, $password);
        $this->objeto = 'ldapOperations';
    }


    /**
     * Auxiliar directo de ldapAccess::iterarEntradas
     * Configura el valor de cada atributos en $atributos de $entrada
     * @param array $atributos
     * @param ldap result entry $entrada
     * @return type
     */
    private function mapa(array $atributos, $entrada){
        $objeto = array('dn'=>ldap_get_dn($this->conexionLdap, $entrada));
        foreach ($atributos as $attr) {
            if (($valor = @ldap_get_values($this->conexionLdap, $entrada, $attr))){
                // Elimino el índice count
                array_pop($valor);
                // $valor es un array.
                // En caso de ser valor único, tomamos el indíce cero, caso contrario 
                // metemos todo el array
                $objeto[$attr] = count($valor)==1 ? $valor[0]:  $valor;
            } // TODO: ¿Un else para configurar un valor por defecto
        }
        return $objeto;
    }
    
    /**
     * Auxiliar directo de busqueda. Dado un ldap result, itera por el para conseguir sus datos
     * 
     * @param ldap result $busquedaLdap
     * @param array $atributos
     * @return boolean
     */
    private function iterarEntradas($busquedaLdap, array $atributos){
        $datos = array();
        if (($entrada = ldap_first_entry($this->conexionLdap, $busquedaLdap))) {
            do {
                // Ejecutamos al menos una vez el mapeo de entradas con sus atributos, a menos 
                // que no haya nada
                $datos[] = $this->mapa($atributos, $entrada);
            } while ($entrada = ldap_next_entry($this->conexionLdap, $entrada));
            return $datos;
        }else{
            return FALSE;
        }
    }
    
    /**
     * Como siempre, el mejor ejemplo de la naturaleza de LDAP es el hecho que la función que 
     * pude complicarse con mayor facilidad es la de búsqueda
     * @param string $base
     * @param string $filtro Filtro LDAP valido
     * @param array $atributos
     * @param int $limite
     * @param boolean $ordenar
     * @return boolean
     * @throws ErrorException
     */
    // TODO: Esta no tiene por que ser la única funcion de busqueda a este nivel
    // Lo que esta dentro del try en realidad es todo lo que busqueda necesita ser, 
    // Dos funciones a este nivel pueden usarla: La una para busquedas normales, sin error,
    // la otra lanza error si el resultado es cero
    protected function busqueda($base, $filtro, $atributos, $limite = 499, $ordenar = false){
        try {
            if (!($busquedaLdap = ldap_search($this->conexionLdap, $base, $filtro, $atributos, 0, $limite))){
                throw new ErrorException (ldap_error($this->conexionLdap));
            }
            if ($ordenar) {
                ldap_sort($this->conexionLdap, $busquedaLdap, $filtro[0]);
            }
            // La función muere en el momento que un throw es invocado. Así las cosas
            // TODO: Básicamente, debo quitar esto lo más pronto posible.
            // Esta operación a estas alturas no representa un error, pero si debe cortar muchas cosas
            if (!($datos = $this->iterarEntradas($busquedaLdap, $atributos))) {
                throw new ErrorException ('La busqueda no devuelve entradas');
            }             
            return $datos;
            // Hacemos la verdadera iteracion
        } catch (ErrorException $e) {
            $this->configurarErrorLdap('Error en búsqueda', $e->getMessage());
            return FALSE;
        }
    }
    
    /**
     * Agrega un atributo tal como en changetype: modify\nadd: $atributo\n$atributo: valor
     * @param string $dn
     * @param array $valores
     * @return boolean
     * @throws Exception
     */
    protected function agregarAtributosEntradaLdap($dn, $valores) {
        try{
            $this->verificaAutenticacion();
            if (ldap_mod_add($this->conexionLdap, $dn, $valores)) {
                return true;
            } else {
                throw new ErrorException(ldap_error($this->conexionLdap));
            }
        }catch(ErrorException $e){
            $this->configurarErrorLdap("Error modificando attributos", $e->getMessage());	
            return false;
        }
    }
    
    /**
     * Agrega un atributo tal como en changetype: modify\nremove: $atributo
     * @param string $dn
     * @param array $valores
     * @return boolean
     * @throws Exception
     */
    protected function removerAtributosEntradaLdap($dn, $valores) {
        try{
            $this->verificaAutenticacion();
            if (ldap_mod_del($this->conexionLdap, $dn, $valores)) {
                return true;
            } else {
                throw new ErrorException(ldap_error($this->conexionLdap));
            }
        }catch(ErrorException $e){
            $this->configurarErrorLdap("Error modificando attributos", $e->getMessage());
            return false;
        }
    }
    
    /**
     * 
     * @param string $dn
     * @param array $valores
     * @return boolean
     * @throws Exception
     */
    protected function nuevaEntradaLdap($dn, $valores) {
        try{
            $this->verificaAutenticacion();
            if (ldap_add($this->conexionLdap, $dn, $valores)) {
                return true;
            } else {
                throw new ErrorException(ldap_error($this->conexionLdap));
            }
        }catch(ErrorException $e){
            $this->configurarErrorLdap("Error modificando entrada", $e->getMessage());
            return false;
        }
    }
    
    /**
     * 
     * @param string $dn
     * @return boolean
     * @throws Exception
     */
    protected function borrarEntradaLdap($dn){
        try {
            $this->verificaAutenticacion();
            if (ldap_delete($this->conexionLdap, $dn)) {
                return true;
            } else {
                throw new ErrorException(ldap_error($this->conexionLdap));
            }
        } catch (ErrorException $e) {
            $this->configurarErrorLdap("Error modificando entrada", $e->getMessage());
            return false;
        }
    }
    
    /**
     * 
     * @param string $oldDn
     * @param string $newParent
     * @param string $newRdn
     * @return boolean
     * @throws Exception
     */
    protected function moverEntradaLdap($oldDn, $newParent, $newRdn = NULL){
        if (!$newRdn) {
            $matches = array();
            $re = "/(\\w+=\\w+)/";
            preg_match($re, $oldDn, $matches);
            $newRdn = $matches[1];
        }
        try {
            $this->verificaAutenticacion();
            if (ldap_rename($this->conexionLdap, $oldDn, $newRdn, $newParent, true)) {
                return true;
            } else {
                throw new ErrorException(ldap_error($this->conexionLdap));
            }
        } catch (ErrorException $e) {
            $this->configurarErrorLdap("Error modificando entrada", $e->getMessage());
            return false;
        }
    }

    /**
     * Modifica los valores de una entrada ldap $dn para el dn dado
     * @param string $dn DN que especifica la entrada a modificar
     * @param array $valores
     * @return boolean
     * @throws ErrorException
     */
    protected function modificarEntradaLdap ($dn, $valores) {
        try{
            $this->verificaAutenticacion();
            if (ldap_modify($this->conexionLdap, $dn, $valores)) {
                return true;
            } else {
                throw new ErrorException(ldap_error($this->conexionLdap));
            }
        }catch(ErrorException $e){
            $this->configurarErrorLdap("Error modificando entrada", $e->getMessage());	
            return false;
        }
    }
    
}
