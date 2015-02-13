<?php
/**
 * @file      polecat/core/Registry/Entry/Connector.php
 * @brief     Encapsulates record of a connector registered in [connector].
 * 
 * Classes implementing AblePolecat_TransactionInterface might easily be compared
 * with action controllers in MVC parlance. A 'Connector' in Able Polecat binds a
 * resource to a specific transaction class, based on the request method (i.e. GET, 
 * POST, PUT, DELETE, etc). In this manner, Able Polecat resources expose a uniform
 * interface on the web.
 *
 * The Able Polecat connector also deals with details such as whether a URL is 
 * pointing to a specific state of a resource (representation); for example, a 
 * specific page from a paginated list of search results. In this manner, Able 
 * Polecat resources achieve statelessness (all the information necessary for 
 * server to fulfill request is in the request).
 *
 * Carrying the example above further, Able Polecat connector is responsible for 
 * providing the representation of resource with links to related resources; for 
 * example, links to the other pages in the list of search results above. In this 
 * manner, Able Polecat resources meet the connectedness property of ROA.
 *     
 * @see Richardson/Ruby, RESTful Web Services (ISBN 978-0-596-52926-0)
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.6.3
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Registry', 'Entry.php')));

interface AblePolecat_Registry_Entry_ConnectorInterface extends AblePolecat_Registry_EntryInterface {
  
  /**
   * @return string.
   */
  public function getResourceId();
  
  /**
   * @return string
   */
  public function getRequestMethod();
    
  /**
   * @return string.
   */
  public function getTransactionClassName();
  
  /**
   * @return string.
   */
  public function getAuthorityClassName();
  
  /**
   * @return int.
   */
  public function getAccessDeniedCode();
}

/**
 * Standard argument list.
 */
class AblePolecat_Registry_Entry_Connector extends AblePolecat_Registry_EntryAbstract implements AblePolecat_Registry_Entry_ConnectorInterface {
  
  /********************************************************************************
   * Implementation of AblePolecat_DynamicObjectInterface.
   ********************************************************************************/
  
  /**
   * Creational method.
   *
   * @return Concrete instance of class implementing AblePolecat_InProcObjectInterface.
   */
  public static function create() {
    return new AblePolecat_Registry_Entry_Connector();
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
    
    $ConnectorRegistration = NULL;
    
    if (is_array($primaryKey) && (2 == count($primaryKey))) {
      $ConnectorRegistration = new AblePolecat_Registry_Entry_Connector();
      isset($primaryKey['resourceId']) ? $ConnectorRegistration->resourceId = $primaryKey['resourceId'] : $ConnectorRegistration->resourceId = $primaryKey[0];
      isset($primaryKey['requestMethod']) ? $ConnectorRegistration->requestMethod = $primaryKey['requestMethod'] : $ConnectorRegistration->requestMethod = $primaryKey[1];
      
      $sql = __SQL()->          
          select(
            'resourceId', 
            'requestMethod', 
            'transactionClassName', 
            'authorityClassName', 
            'accessDeniedCode')->
          from('connector')->
          where(sprintf("`resourceId` = '%s' AND `requestMethod` = '%s'", $ConnectorRegistration->resourceId, $ConnectorRegistration->requestMethod));
      $CommandResult = AblePolecat_Command_DbQuery::invoke(AblePolecat_AccessControl_Agent_System::wakeup(), $sql);
      if ($CommandResult->success() && is_array($CommandResult->value())) {
        $classInfo = $CommandResult->value();
        if (isset($classInfo[0])) {
          $ConnectorRegistration->transactionClassName = $classInfo[0]['transactionClassName'];
          $ConnectorRegistration->authorityClassName = $classInfo[0]['authorityClassName'];
          $ConnectorRegistration->accessDeniedCode = $classInfo[0]['accessDeniedCode'];
        }
      }
    
      //
      // Handle built-in resources in the event database connection is not active.
      //
      if (!isset($ConnectorRegistration->transactionClassName)) {
        //
        // Assign transaction class name.
        //
        switch ($ConnectorRegistration->resourceId) {
          default:
            //
            // Unrestricted resource.
            //
            $ConnectorRegistration->transactionClassName = NULL;
            break;
          case AblePolecat_Resource_Restricted_Util::UUID:
            $ConnectorRegistration->transactionClassName = 'AblePolecat_Transaction_AccessControl_Authority';
            break;
          case AblePolecat_Resource_Restricted_Install::UUID:
            $ConnectorRegistration->transactionClassName = 'AblePolecat_Transaction_Install';
            break;
        }
        
        //
        // Assign authority and access denied code.
        //
        switch ($ConnectorRegistration->resourceId) {
          default:
            //
            // Unrestricted resource.
            //
            $ConnectorRegistration->authorityClassName = NULL;
            $ConnectorRegistration->accessDeniedCode = 200;
            break;
          case AblePolecat_Resource_Restricted_Util::UUID:
          case AblePolecat_Resource_Restricted_Install::UUID:
            $Request = AblePolecat_Host::getRequest();
            switch ($Request->getMethod()) {
              default:
                break;
              case 'GET':
                $ConnectorRegistration->authorityClassName = 'AblePolecat_Transaction_AccessControl_Authority';
                $ConnectorRegistration->accessDeniedCode = 401;
                break;
              case 'POST':
                $ConnectorRegistration->authorityClassName = NULL;
                $ConnectorRegistration->accessDeniedCode = 403;
                break;
            }
            break;
        }
      }
    }
    else {
      throw new AblePolecat_Registry_Exception('Invalid Primary Key passed to ' . __METHOD__);
    }
    
    return $ConnectorRegistration;
  }
  
  /**
   * Returns name(s) of field(s) uniquely identifying records for encapsulated table.
   *
   * @return Array[string].
   */
  public static function getPrimaryKeyFieldNames() {
    return array(0 => 'resourceId', 1 => 'requestMethod');
  }
  
  /**
   * Update or insert registration record.
   *
   * If the encapsulated registration exists, based on id property, it will be updated
   * to reflect object state. Otherwise, a new registration record will be created.
   *
   * @return AblePolecat_Registry_EntryInterface or NULL.
   */
  public function save() {
    //
    // @todo: complete REPLACE [connector]
    //
  }
  
  /********************************************************************************
   * Implementation of AblePolecat_Registry_Entry_ConnectorInterface.
   ********************************************************************************/
  
  /**
   * @return string.
   */
  public function getResourceId() {
    return $this->getPropertyValue('resourceId');
  }
  
  /**
   * @return string
   */
  public function getRequestMethod() {
    return $this->getPropertyValue('requestMethod');
  }
  
  /**
   * @return string.
   */
  public function getTransactionClassName() {
    return $this->getPropertyValue('transactionClassName');
  }
  
  /**
   * @return string.
   */
  public function getAuthorityClassName() {
    return $this->getPropertyValue('authorityClassName');
  }
  
  /**
   * @return int.
   */
  public function getAccessDeniedCode() {
    return $this->getPropertyValue('accessDeniedCode');
  }
    
  /********************************************************************************
   * Helper functions.
   ********************************************************************************/
  
  /**
   * Extends __construct().
   *
   * Sub-classes should override to initialize arguments.
   */
  protected function initialize() {
    parent::initialize();
  }
}