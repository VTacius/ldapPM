<?php
/**
 * Habilita una capa intermedia entre los modelos y las operaciones LDAP
 * @author alortiz
 */
namespace LdapPM\Objetos;

abstract class objetosLdap extends \LdapPM\Controlador\ldapOperations{
    /**
     * Servidor donde se requiere realizar las operaciones LDAP
     * @var string 
     */
    protected $destino;
    
    /**
     * Usuario (O referencia a uno almacenado en la configuracion) con quien 
     * conectamos al Directorio LDAP
     * @var string 
     */
    protected $usuario;
    
    /**
     * Contraseña (0 false, para distinguir del uso de $usuario como referencia)
     * con la que conectamos con el Directorio LDAP
     * @var string 
     */
    protected $password;
    
    /**
     * Atributos marcados como MUST en los objectClass involucrados, y que por tantos
     * deben verificarse como configurados a la hora de crear el objeto
     * Además, por lo menos por ahora, será dificil hacer que estos deban configurarse
     * @var array 
     */
    protected $attrObligatorios = array();
    
    /**
     * Arreglo de los atributos del usuario. 
     * Recuerde que DN no se considera atributo, pero \LdapPM\Controlador\ldapOperations lo agregara por defecto
     * @var array
     */
    protected $atributos = array();
    
    /**
     * Contiene todos los datos que componen al objeto LDAP
     * @var array 
     */
    protected $entrada = array();
    
    /**
     * Contiene a todos los atributos que se hayan configurado dentro de las operaciones
     * con el objeto Ldap en cuestión
     * @var array 
     */
    protected $cambios = array();
    
    /**
     * Lista de ObjectClass necesarios para definiar este objeto
     * @var array 
     */
    protected $objectClass;
    
    /**
     * La entrada configurada es única dentro del árbol LDAP
     * @var boolean 
     */
    protected $esUnico;
    
    /**
     * La entrada ya existe dentro del árbol LDAP
     * @var boolean 
     */
    protected $existe;
    
    /**
     * Atributo que debe ser único para identificar una entrada dentro del árbol LDAP
     * Compone ademas el dn de dicho objeto
     * @var string 
     */
    protected $atributoReferencia;
    
    /**
     * Debido a la naturaleza de objetosLdap de sus clases hijas, definitivamente 
     * solo acá tiene sentido hablar de un DN de entrada y no antes
     * Sobre usar entrada['dn'], decir que esto puede funcionar incluso en operaciones
     * como el borrado o creación, sin necesidad de repoblar la entrada
     * @var string
     */
    protected $dnObjeto;
    
    /**
     * Sobreescribirlo a este nivel obedece a que es en los objetos donde por ahora 
     * he visto la necesidad de acceder a las credenciales de conexion
     * para tratar con diferentes objetos
     * @param string $destino
     * @param string $usuario
     * @param string $password
     */
    public function __construct($destino, $usuario, $password = false) {
        parent::__construct($destino, $usuario, $password);
        $this->destino = $destino;
        $this->usuario = $usuario;
        $this->password = $password;
        $this->objeto = 'objetosLdap';
    }
    
    /**
     * Configura el valor de un elemento cualquiera dentro del árbol LDAP
     * Use para los atributos que no son de búsqueda
     * @param string $atributo
     * @param string $especificacion
     */
    protected function configurarAtributo($atributo, $especificacion){
        if (!$especificacion == "") {
            $this->entrada[$atributo] = $especificacion;
            $this->cambios[] = $atributo;
        }
    }
    
    /**
     * Cambia la base pretederminada desde donde se empiezan a realizar busquedas
     */
    public function cambiarBase($base){
        // TODO: ¿Podrías hacer funciones para cambiar estas cuestiones? No parece mala idea a decir verdad
        $this->cfgDominio['base'][$this->objeto] = $base;
    }
    
    /**
     * ¿Es única este objeto dentro del arbol LDAP?
     * @return boolean
     */
    public function verificaUnicidad(){
        return $this->esUnico;
    }
    
    /**
     * ¿El objeto sobre el que se intenta ya existe?
     * @return boolean
     */
    public function verificaExistencia(){
        return $this->existe;
    }

    /**
     * Hace una busqueda con filtro (&($atributo=$valor)(objectClass=$this->objeto))
     * Verifica si la entrada existe y si es única y 
     * @param string $atributo
     * @param string $valor
     * @return mixed
     * @throws \ErrorException
     */
    private function obtenerDatos($atributo, $valor){
        $referencia = strtolower($valor);
        $filtro = "(&($atributo=$referencia)(objectClass=$this->objeto))";
        $datos = $this->busqueda($this->cfgDominio['base'][$this->objeto], $filtro, $this->atributos);
        if (!$datos) {
            $this->existe = false;
            $this->esUnico = true;
        }elseif (count($datos) === 1 ) {
            $this->existe = true;
            $this->esUnico = true;
            $this->dnObjeto = $datos[0]['dn'];
            return $datos[0];
        }else{
            $this->esUnico = false;
            // TODO: Acaso no es acà que deberìas configurar un mensaje de error a enviar
//            throw new \ErrorException("El atributo $atributo debe ser único en el árbol LDAP");
        }
    }
    
    /**
     * Obtiene todas las entradas que es posible obtener para el objeto dado
     * @param array $attr
     * @return array
     */
    public function obtenerEntradas(Array $attr = array()){
        if (empty($attr)) {
            $attr = $this->atributos;
        }
        $filtro = "(objectClass=$this->objeto)";
        return $this->busqueda($this->cfgDominio['base'][$this->objeto], $filtro, $attr);
    }
    
    public function buscarEntradas($filtro = "", Array $attr = array()){
        if (empty($filtro)) {
            $filtro = "(objectClass=$this->objeto)";
        }
        if (empty($attr)) {
            $attr = $this->atributos;
        }
        return $this->busqueda($this->cfgDominio['base'][$this->objeto], $filtro, $attr);
    }
    
    
    /**
     * Obtiene el atributo $atributo de todos los objetos dada, y los devuelve en una lista
     * totalmente limpia
     * @param string $atributo
     * @return array
     */
    public function obtenerAtributosObjetos($atributo){
        $base = $this->cfgDominio['base'][$this->objeto];
        $filtro = "(objectClass=$this->objeto)";
        $datos = $this->busqueda($base, $filtro, array($atributo), 0);
        $atributos = array();
        foreach ($datos as $attr) {
            $atributos[] = $attr[$atributo];
        }
        return $atributos;
    }
    
    /**
     * Iterando sobre una lista de ID dada obtenida de obtenerAtributosObjetos, 
     * escojemos uno nuevo para usar como id de Entrada según se ha especificado
     * mediante $atributo
     * @param type $atributo
     * @return type
     */
    protected function primerIdDisponible($atributo){
        $atributos = $this->obtenerAtributosObjetos($atributo);
        $min = $this->cfgDominio['min_ldap_id'];
        sort($atributos, SORT_NUMERIC);
        $ultimo = array_slice($atributos, -1)[0];
        for ($index = $min; ($index <= $ultimo|| $index < 65534); $index++) {
            if (!in_array($index, $atributos)) {
                return $index ;
            }
        }
        //Dado $index < 65534, creo que esto esta de más, pero creo que me lo quedaré
        return $ultimo + 1;
    }
    
    /**
     * Configuramos un entrada mediante la busqueda de atributos que sabemos que 
     * DEBEN ser únicos sin ningúna restriccción
     * @param string $atributo
     * @param string $valor
     */
    protected function configurarEntrada($atributo, $valor){
        if (empty($this->entrada)) {
            $this->entrada = $this->obtenerDatos($atributo, $valor);
            $this->entrada[$atributo] = $valor;
        }elseif(!$this->existe){
            $this->entrada[$atributo] = $valor;
        }
    }
    
    /**
     * Parsea del lenguaje común a un filtro LDAP válido
     * @param string $attr
     * @param string $valor
     * @return string Filtro LDAP Valido
     */
    private function parserFiltro($attr, $valor){
        $matches = array();
        $filtro = "";
        if (preg_match_all("/(NOT|OR)\s{1,2}\(*(?<valores>[a-z]+)/", $valor, $matches)){
                $pre_attr = "(&";
                foreach($matches['valores'] as $value){
                        $pre_attr .= "(!($attr=$value))";
                }
                $pre_attr .= ")";
                $filtro .= $pre_attr;
        }else if ($attr=="personalizado"){
		$filtro .= $valor;	
	}else{
                $filtro .= "($attr=$valor)";
        }
        return $filtro;
    }
    
    /**
     * Crea el filtro recorriendo el arreglo asociativo que el usuario nos envía 
     * @param array $search
     * @return string
     */
    protected function filtro($search){
        $filtro = "(&(objectClass=$this->objeto)";
        foreach ($search as $attr => $valor){
            $filtro .= $this->parserFiltro($attr, $valor);
        }
        $filtro .= ")";
        return $filtro;
    }
    /**
     * Devuelve el DN de la entrada actual
     * @return string
     */ 
    public function getDnObjeto(){
        return $this->dnObjeto;
    }
    
/**
 * ¿Tienen en verdad sentido estosmétodos?
     
    /**
     * Obtiene todas las entradas del árbol LDAP disponibles
     * Es posible pasar un array con los attributos que se necesitan 
     * Recuerde que dn no se considera atributo
     * @param array $attr
     * @return array
     
    public function getAll( $attr = false, $base = false){
        if ($base) {
            $this->base =  $base;
        }
        $atributes = $attr === false ? $this->atributos : $attr;
        $filtro = "(objectClass=$this->objeto)";
        return $this->entrada = $this->getDatos($filtro, $atributes);
    }
     
    /**
     * Realiza la búsqueda en base a un arreglo hash pasado como parametro
     * @param array $search
     * @param array $atributes
     * @param boolean|string $base
     * @return array
     
    
    public function search( $search, $atributes = false, $base = false){
        if ($base == false) {
            $this->base =  $base;
        }
        $this->datos = array();
        if ($atributes == false){
            $attr = array_keys($search);
        }else{
            $attr = array_merge(array_keys($search), $atributes);
        }
        $filtro = $this->filtro($search);
        $this->entrada = $this->getDatos($filtro, $attr);
        return $this->entrada;
        
    }

    /**
     * Devuelve la primera rama a la cual pertenece
     * @return string
     
    public function getDNRama(){
        $matches = array();
        $re = "/((ou=\\w+),((dc=\\w+,*){3}))/";
        $str = $this->entrada['dn'];
        preg_match($re, $str, $matches);
        $resultado = array_key_exists(1, $matches) ? $matches[1]: $this->config['base'];
        return $resultado;
    }
    
    /**
     * Devulve la primera base que es posible configurar en un servidor normal
     * @return string
     
    public function getDNBase(){
        $matches = array();
        $re = "/((ou=\\w+),((dc=\\w+,*){3}))/";
        $str = $this->entrada['dn'];
        preg_match($re, $str, $matches);
        $resultado = array_key_exists(3, $matches) ? $matches[3]: $this->config['base'];
        
        return $resultado;
    }
    
 */   
    
    /**
     * Actualiza una entrada LDAP existente
     * @return string
     */
    public function actualizarObjetoLdap(){
        if ($this->existe) {
            $dn = $this->entrada['dn'];
            $cambios = array();
            foreach ($this->cambios as $atributos) {
                $cambios[$atributos] = $this->entrada[$atributos];
            }
            return $this->modificarEntradaLdap($dn, $cambios);
        }else{
            $this->configurarErrorLdap('Actualizacion', 'Esa entrada no existe');
            return false;
        }
    }
    
    /**
     * Comprueba que todos los atributos obligatorios definidos en $attrObligatorios
     * están ya configurados en $entrada
     * @return boolean
     */
    private function comprobarIntegridadAtributos(){
        foreach ($this->attrObligatorios as $obligatorios) {
            if (!array_key_exists($obligatorios, $this->entrada)) {
                $this->configurarErrorLdap('Creacion', 'No se ha configurado el atributo ' . $obligatorios );
                return false;
            }
        }
        return true;
    }


    /**
     * Crea un objeto después de realizar un par de comprobaciones 
     * @param string $base
     * @return boolean
     */
    protected function crearObjetoLdap($base){
        if ($this->existe) {
            $this->configurarErrorLdap('Creacion', 'Ya existe una entrada con la misma definición');
            return false;
        }else{
            $dn = $this->atributoReferencia . '=' . $this->entrada[$this->atributoReferencia] . ',' . $base;
            $this->dnObjeto = $dn;
            $this->existe = true;
            $this->entrada['objectClass'] = $this->objectClass;
            if ($this->comprobarIntegridadAtributos()) {
                return  $this->nuevaEntradaLdap($dn, $this->entrada);
            }
        }
    }
    
    /**
     * Borra el objeto que se ha consultado
     * @return boolean
     */
    protected function borrarObjetoLdap(){
        if ($this->existe) {
            $dn = $this->dnObjeto;
            return $this->borrarEntradaLdap($dn);
        }else{
            $this->configurarErrorLdap('Actualizacion', 'Esa entrada no existe');
            return false;
        }
        
    }
    
    abstract public function borrarEntrada();
    
    abstract public function crearEntrada();
}
