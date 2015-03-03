<?php
/**
 * @name filtro
 * @author vtacius
 */
namespace LdapPM\Utilidades;

class filtro {
    static public function filtrador (Array $filtros, $conjuncion = "AND") {
        if ($conjuncion === "AND") {
            $filtro = "(&";
        } else {
            $filtro = "(|";
        }
        foreach ($filtros as $key => $value) {
            $filtro .= sprintf('(%s=%s)', $key, $value);
        }
        $filtro .= ")";
        return $filtro;
    }
}
