<?php
/**
 * Description of LdapPMException
 * @author vtacius
 */

namespace LdapPM\Exception;

class LdapPMException extends \Exception  {
    public function __construct($message = "", $codigo = 0) {
        parent::__construct($message, $codigo);
    }
}
