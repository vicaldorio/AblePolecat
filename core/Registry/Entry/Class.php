<?php
/**
 * @file      polecat/core/Registry/Entry/Class.php
 * @brief     Encapsulates record of a resource registered in [class].
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.6.3
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Registry', 'Entry.php')));

interface AblePolecat_Registry_Entry_ClassInterface extends AblePolecat_Registry_EntryInterface {  
  
  /**
   * @return string.
   */
  public function getClassLibraryId();
    
  /**
   * @return string.
   */
  public function getClassFullPath();
  
  /**
   * @return string.
   */
  public function getClassFactoryMethod();
}

/**
 * Standard argument list.
 */
class AblePolecat_Registry_Entry_Class extends AblePolecat_Registry_EntryAbstract implements AblePolecat_Registry_Entry_ClassInterface {
  
  /**
   * @var Array File statistics from stat().
   */
  private $fileStat;
  
  /********************************************************************************
   * Implementation of AblePolecat_DynamicObjectInterface.
   ********************************************************************************/
  
  /**
   * PHP magic method is run when writing data to inaccessible properties.
   *
   * @param string $name  Name of property to set.
   * @param mixed  $value Value to assign to given property.
   */
  public function __set($name, $value) {
    
    if ($name == 'classFullPath') {
      $this->fileStat = stat($value);
      if ($this->fileStat && isset($this->fileStat['mtime'])) {
        parent::__set('lastModifiedTime', $this->fileStat['mtime']);
      }
      else {
        throw new AblePolecat_Registry_Exception("Failed to retrieve file stats on $value.");
      }
    }
    parent::__set($name, $value);
  }
  
  /**
   * Creational method.
   *
   * @return Concrete instance of class implementing AblePolecat_InProcObjectInterface.
   */
  public static function create() {
    return new AblePolecat_Registry_Entry_Class();
  }
  
  /********************************************************************************
   * Implementation of AblePolecat_Registry_EntryInterface.
   ********************************************************************************/
  
  /**
   * Fetch registration record given by id.
   *
   * @param mixed $primaryKey Array[fieldName=>fieldValue] for compound key or value of PK.
   *
   * @return AblePolecat_Registry_EntryInterface.
   */
  public static function fetch($primaryKey) {
    //
    // @todo: complete fetch from [class]
    //
  }
  
  /**
   * Returns name(s) of field(s) uniquely identifying records for encapsulated table.
   *
   * @return Array[string].
   */
  public static function getPrimaryKeyFieldNames() {
    return array(0 => 'id');
  }
  
  /**
   * Update or insert registration record.
   *
   * If the encapsulated registration exists, based on id property, it will be updated
   * to reflect object state. Otherwise, a new registration record will be created.
   *
   * @param AblePolecat_DatabaseInterface $Database Handle to existing database.
   *
   * @return AblePolecat_Registry_EntryInterface or NULL.
   */
  public function save(AblePolecat_DatabaseInterface $Database = NULL) {
    $sql = __SQL()->          
      insert(
        'id', 
        'name', 
        'classLibraryId', 
        'classFullPath', 
        'classFactoryMethod', 
        'lastModifiedTime')->
      into('class')->
      values(
        $this->getId(), 
        $this->getName(), 
        $this->getClassLibraryId(), 
        $this->getClassFullPath(), 
        $this->getClassFactoryMethod(), 
        $this->getLastModifiedTime()
      );
    $this->executeDml($sql, $Database);
  }
  
  /********************************************************************************
   * Implementation of AblePolecat_Registry_Entry_ClassInterface.
   ********************************************************************************/
  
  /**
   * @return string.
   */
  public function getClassLibraryId() {
    return $this->getPropertyValue('classLibraryId');
  }
    
  /**
   * @return string.
   */
  public function getClassFullPath() {
    return $this->getPropertyValue('classFullPath');
  }
  
  /**
   * @return string.
   */
  public function getClassFactoryMethod() {
    return $this->getPropertyValue('classFactoryMethod');
  }
  
  /********************************************************************************
   * Helper functions.
   ********************************************************************************/
  
  /**
   * Output class state to debug log.
   */
  public function dumpState() {
    $message = sprintf("REGISTRY: name=%s, id=%s; classLibraryId=%s; classFullPath=%s, classFactoryMethod=%s, lastModifiedTime=%d",
      $this->getName(),
      $this->getId(),
      $this->getClassLibraryId(),
      $this->getClassFullPath(),
      $this->getClassFactoryMethod(),
      $this->getLastModifiedTime()
    );
    AblePolecat_Mode_Server::logBootMessage(AblePolecat_LogInterface::STATUS, $message);
  }
  
  /**
   * Extends __construct().
   *
   * Sub-classes should override to initialize arguments.
   */
  protected function initialize() {
    parent::initialize();
  }
}