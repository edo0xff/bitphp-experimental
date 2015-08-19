<?php

   namespace Bitphp\Modules\Database\Rocket;

   use \Bitphp\Modules\Database\Rocket\QueryBuilder;

   /**
    *  Orm sencillo, por defecto proporciona metodos para CRUD
    *
    *  @author Eduardo B Romero
    */
   abstract class Orm {

      /** Nombre de la tabla a la qué se conecta */
      protected $table;
      /** Nombre de la base de datos */
      protected $database;
      /** Generador de consultas */
      protected $builder;

      /**
       *   El constructor manda llamar la funcion map del trait
       */
      public function __construct() {
         /* Implemented on mapper */
         $this->map();

         $this->builder = new QueryBuilder();
         $this->builder->table = $this->table;
      }

      /**
       * Convierte un array asosiativo en propiedades de una clase
       *
       * @param array $properties Array asocietivo para convertir en propiedades
       * @return Object objeto de la clase (hijo) con los valores del array seteados como propiedades
       */
      private function generateObject($properties) {
         $class = get_class($this);
         $object = new $class;

         foreach ($properties as $property => $value) {
            $object->$property = $value;
         }

         return $object;
      }

      /**
       * Ejecuta una consulta de actualizacion donde $property sea igual a $conditional
       * $property por defecto es 'id', su valor, por defecto lo lee de $this->$property
       *
       * @param array $params Parametros para actualizar
       * @param string $property Propiedad a tomar en cuenta para la condicional de actualizacion
       * @param mixed $conditional Valor para la condicional
       * @return bool True, si la consulta fue exitosa, false de lo contrario
       */
      public function update($params, $property = 'id', $conditional = null) {
         $values = array();
         foreach ($params as $key => $value) {
            $values[] = "$key='$value'";
         }

         $values = implode(',', $values);

         if(null === $conditional) {
            if(!isset($this->$property)) {
               trigger_error("No se pudo actualizar, '$property' no es una propiedad");
               return false;
            }

            $conditional = $this->$property;
         }

         $query = "UPDATE $this->table SET $values WHERE $property='$conditional'";
         $this->database->execute($query);
         if(false !== ($error = $this->database->error())) {
            trigger_error($error);
            return false;
         }

         return true;
      }

      /**
       * Crea un registro en la tabla
       *
       * @param array asociativo con los valores a insertar
       * @return bool True, si la consulta fue exitosa, false de lo contrario
       */
      public function create(array $params) {
         $keys = array();
         $values = array();

         foreach ($params as $key => $value) {
            $keys[] = $key;
            $values[] = "'$value'";
         }

         $keys = implode(',', $keys);
         $values = implode(',', $values);

         $query = "INSERT INTO $this->table ($keys) VALUES ($values)";
         $this->database->execute($query);
         if(false !== ($error = $this->database->error())) {
            trigger_error($error);
            return false;
         }

         return true;
      }

      /**
       * Realiza una consulta para encontrar el registro con el valor $value en $item
       *
       * @param mixed $value Valor qué debe tener $item para la condicional
       * @param string $item Elemento para la condicional, id por defecto
       * @return Object Objeto de la clase relacional
       */
      public function find($value, $item='id') {
         $query = "SELECT * FROM $this->table WHERE $item='$value'";
         $result = $this->database->execute($query)
                                  ->result();

         if(!empty($result))
            return $this->generateObject($result[0]);

         return null;
      }

      /**
       * Realiza una consulta para borrar el registro con el valor $value en $item
       *
       * @param mixed $value Valor qué debe tener $item en la condicional
       * @param string $item Elemento para la condicional, id por defecto
       * @return bool True, si la consulta fue exitosa, false de lo contrario
       */
      public function delete($value, $item='id') {
         $query = "DELETE FROM $this->table WHERE $item='$value'";
         $this->database->execute($query)
                        ->result();

         if(false !== ($error = $this->database->error())) {
            trigger_error($error);
            return false;
         }

         return true;
      }

      /**
       *  Realiza una consulta para obtener todos los registros de la tabla
       *
       * @param string $order Fragmento de consulta para indicar el orden, por defecto null
       * @return array Areglo de objetos de la clase relacional, null si la tabla esta vacia o falla la consulta
       */
      public function all($order = null) {
         $objects = array();
         $result = $this->database->execute("SELECT * FROM $this->table" . $order)
                                  ->result();

         if(false !== ($error = $this->database->error())) {
            trigger_error($error);
            return null;
         }

         if(!empty($result)) {
            foreach ($result as $array) {
               $objects[] = $this->generateObject($array);
            }

            return $objects;
         }

         return null;
      }

      /**
       * Crea el objeto del proveedor en $this->database
       * Determina el nombre de la base de datos y la conecta a través del proveedor
       * Determina el nombre de la base de datos y la setea en $this->database
       * Crea la tabla si no existe
       *
       * @return void
       */
      abstract protected function map();
   }