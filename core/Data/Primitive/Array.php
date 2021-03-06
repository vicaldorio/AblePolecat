<?php
/**
 * @file      polecat/core/Data/Primitive/Array.php
 * @brief     Encapsulates Array data type.
 * 
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.7.0
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Data', 'Primitive', 'StdObject.php')));

interface AblePolecat_Data_Primitive_ArrayInterface 
  extends AblePolecat_Data_Primitive_StdObjectInterface,
          ArrayAccess,
          Iterator {
}

class AblePolecat_Data_Primitive_Array 
  extends AblePolecat_Data_Primitive_StdObject 
  implements AblePolecat_Data_Primitive_ArrayInterface {
  
  /********************************************************************************
   * Implementation of ArrayAccess.
   ********************************************************************************/
   
  /**
   * This method is executed when using isset() or empty() on objects implementing ArrayAccess. 
   *
   * @param mixed $offset An offset to check for.
   * 
   * @return bool TRUE if offset exists, otherwise FALSE.
   */
  public function offsetExists($offset) {
    return $this->__isset($offset);
  }
  
  /**
   * Returns the value at specified offset. 
   *
   * @param mixed $offset The offset to retrieve. 
   *
   * @return AblePolecat_Data_PrimitiveInterface or NULL.
   */
  public function offsetGet($offset) {
    return $this->__get($offset);
  }
  
  /**
   * Assigns a value to the specified offset. 
   *
   * @param mixed $offset The offset to assign the value to. 
   * @param mixed $value  The value to set. 
   */
  public function offsetSet($offset, $value) {
    $this->__set($offset, $value);
  }
  
  /**
   * Unsets an offset. 
   *
   * @param mixed $offset The offset to unset. 
   */
  public function offsetUnset($offset) {
    $this->__unset($offset);
  }
  
  /********************************************************************************
   * Implementation of Iterator.
   ********************************************************************************/
  
  /**
   * Returns the current element.
   *
   * * @return AblePolecat_Data_PrimitiveInterface or NULL.
   */
  public function current() {
    return $this->getIteratorPtr();
  }
  
  /**
   * Returns the key of the current element.
   *
   * @return mixed.
   */
  public function key() {
    $key = NULL;
    if ($this->getIteratorPtr()) {
      $key = $this->getPropertyKey();
    }
    return $key;
  }
  
  /**
   * Move forward to next element.
   */
  public function next() {
    $this->getNextProperty();
  }
  
  /**
   * Rewinds back to the first element of the Iterator. 
   *
   */
  public function rewind() {
    $this->getFirstProperty();
  }
  
  /**
   * Checks if current position is valid.
   *
   * @return bool Returns TRUE on success or FALSE on failure. 
   */
  public function valid() {
    return (bool)$this->getIteratorPtr();
  }

  /********************************************************************************
   * Implementation of AblePolecat_Data_PrimitiveInterface.
   ********************************************************************************/
   
  /**
   * Casts the given parameter into an instance of data class.
   *
   * @param mixed $data
   *
   * @return Concrete instance of AblePolecat_Data_PrimitiveInterface
   * @throw AblePolecat_Data_Exception if type cast is invalid.
   */
  public static function typeCast($data) {
    
    $Data = NULL;
    
    is_object($data) ? $data = get_object_vars($data) : NULL;
    if (is_array($data)) {
      $Data = new AblePolecat_Data_Primitive_Array();
      foreach($data as $offset => $value) {
        $Data->__set($offset, $value);
      }
    }
    else {
      throw new AblePolecat_Data_Exception(
        sprintf("Cannot cast %s as %s.", AblePolecat_Data::getDataTypeName($data), __CLASS__), 
        AblePolecat_Error::INVALID_TYPE_CAST
      );
    }
    
    return $Data;
  }
  
  /**
   * @return string Data expressed as a string.
   */
  public function __toString() {
    $str = '';
    $tokens = array();
    foreach($this as $key => $value) {
      $tokens[] = sprintf("%s => [%s]", $key, $value->__toString());
    }
    $str = implode(',', $tokens);
    return $str;
  }
}