<?php
/**
 * Description of utilidades
 * @author vtacius
 */
namespace LdapPM\Utilidades;

class utilidades {
    /**
     * Si el valor ´dominio´ en la base de datos esta vacío, debe crear el dominio
     * a partir de los domain component en el DN del usuario
     * TODO: Si este fuera static o algo por el estilo, usuarioControl lo habrìa cambiado
     * @param string $dn
     * @param string $dominio
     * @return string
     */
    static function dnLdapADominioDns($dn){
        $pattern = "(dc=(?P<componentes>[A-Za-z]+))";
        $matches = array();
        $dominio = "";
        preg_match_all($pattern, $dn, $matches );
        foreach ($matches['componentes'] as $componentes){
                $dominio .= $componentes . ".";
        }
        return rtrim($dominio, ".");
    }
    
    /**
     * Limpia los atributos de espacios innecesarios
     * TODO: ¿Y los valores no ascii?
     * @param string $valor Cadena a Limpiar
     * @return string
     */
    static function limpiandoAtributos($valor){
        $trimeando = trim($valor);
        $pre_valor = preg_replace("/\s+/", " ", $trimeando);
        return (string)$pre_valor;
    }
    
    /**
     * Retorna el hash en base64 de la contraseña del usuario
     * @param type $password
     * @return string
     */
    static function slappasswd($password){
        $userPassword = "{SHA}" . base64_encode( pack( "H*", sha1($password) ) );
        return $userPassword;
    }
}
