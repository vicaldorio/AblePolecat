<?php
/**
 * @file      polecat/core/Service/Bus.php
 * @brief     Provides a channel for Able Polecat services to communicate with one another.
 *
 * 1. Route messages between services implemented in Able Polecat.
 * 2. Resolve contention between services.
 * 3. Control data transformation and exchange (DTX) between services.
 * 4. Marshal redundant resources (e.g. web service client connections).
 * 5. Handle messaging, exceptions, logging etc.
 * 
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.5.0
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Message', 'Request', 'Get.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Message', 'Request', 'Post.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Message', 'Request', 'Put.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Message', 'Request', 'Delete.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Service', 'Initiator.php')));

/**
 * Manages multiple web services initiator connections and routes messages
 * between these and the application in scope.
 */

class AblePolecat_Service_Bus extends AblePolecat_CacheObjectAbstract {
  
  const UUID              = '3d50dbb0-715e-11e2-bcfd-0800200c9a66';
  const NAME              = 'Able Polecat Service Bus';
  
  const REQUEST           = 'request';
  const RESPONSE          = 'response';
  
  /**
   * @var object Singleton instance
   */
  private static $ServiceBus;
  
  /**
   * @var Array of objects, which implement AblePolecat_Service_InitiatorInterface.
   */
  protected $ServiceInitiators;
  
  /**
   * @todo: message queue
   */
  protected $Messages;
  
  /**
   * @todo: transaction log
   */
  protected $Transactions;
  
  /********************************************************************************
   * Implementation of AblePolecat_AccessControl_ArticleInterface.
   ********************************************************************************/
  
  /**
   * Return unique, system-wide identifier for security resource.
   *
   * @return string Resource identifier.
   */
  public static function getId() {
    return self::UUID;
  }
  
  /**
   * Return common name for security resource.
   *
   * @return string Resource name.
   */
  public static function getName() {
    return self::NAME;
  }
  
  /********************************************************************************
   * Implementation of AblePolecat_CacheObjectInterface.
   ********************************************************************************/
  
  /**
   * Serialize object to cache.
   *
   * @param AblePolecat_AccessControl_SubjectInterface $Subject.
   */
  public function sleep(AblePolecat_AccessControl_SubjectInterface $Subject = NULL) {

  }
  
  /**
   * Create a new instance of object or restore cached object to previous state.
   *
   * @param AblePolecat_AccessControl_SubjectInterface Session status helps determine if connection is new or established.
   *
   * @return AblePolecat_Service_Bus or NULL.
   */
  public static function wakeup(AblePolecat_AccessControl_SubjectInterface $Subject = NULL) {
    
    if (!isset(self::$ServiceBus)) {
      self::$ServiceBus = new AblePolecat_Service_Bus($Subject);
      
    }					
    return self::$ServiceBus;
  }
  
  /********************************************************************************
   * Message processing methods.
   ********************************************************************************/
  
  /**
   * Add a message to the queue.
   *
   * @param AblePolecat_AccessControl_AgentInterface $Agent Agent with access to requested service.
   * @param AblePolecat_MessageInterface $Message
   */
  public function dispatch(AblePolecat_AccessControl_AgentInterface $Agent, AblePolecat_MessageInterface $Message) {
    
    if (isset($this->ClassRegistry)) {
      //
      // Prepare response
      //
      $Response = $this->ClassRegistry->loadClass('AblePolecat_Message_Response');
      
      //
      // Is it request or response?
      //
      $subclass = FALSE;
      if (is_a($Message, 'AblePolecat_Message_RequestInterface')) {
        $subclass = self::REQUEST;
      }
      else if (is_a($Message, 'AblePolecat_Message_ResponseInterface')) {
        $subclass = self::RESPONSE;
      }
      
      //
      // @todo: serialize message to log.
      // Log is used to reload unhandled messages in event of unexpected shutdown.
      //
      
      //
      // Determine target initiator
      //
      $initiatorId = NULL;
      switch ($subclass) {
        default:
          break;
        case self::REQUEST:
          $initiatorId = $Message->getResource();
          break;
        case self::RESPONSE:
          break;
      }
      
      try { 
        $ServiceInitiator = $this->getServiceInitiator($initiatorId);
        $Response = $ServiceInitiator->prepare($Agent, $Message)->dispatch(); 
      } 
      catch(AblePolecat_Service_Exception $Exception) {
        //
        // Create an array of data to be inserted into template
        //
        $substitutions = array(
          'POLECAT_EXCEPTION_MESSAGE' => $Exception->getMessage(),
        );
        
        //
        // Load response template
        //
        $Response = AblePolecat_Message_Response_Template::create(
          $this->getDefaultCommandInvoker(),
          AblePolecat_Message_Response_Template::DEFAULT_404,
          $substitutions
        );
      }
    }
    return $Response;
  }
  
  /********************************************************************************
   * Helper functions.
   ********************************************************************************/
  
  /**
   * Returns a service initiator by class id.
   *
   * @param string $id UUID of service initiator class.
   *
   * @return AblePolecat_Service_InitiatorInterface or NULL.
   */
  protected function getServiceInitiator($id) {
    
    $ServiceInitiator = NULL;
    
    if (isset($this->ClassRegistry)) {
      if (isset($this->ServiceInitiators[$id])) {
        $ServiceInitiator = $this->ServiceInitiators[$id];
        if (!is_object($ServiceInitiator)) {
          $this->ServiceInitiators[$id] = $this->ClassRegistry->loadClass($ServiceInitiator);
          $ServiceInitiator = $this->ServiceInitiators[$id];
        }
      }
    }
    if (!isset($ServiceInitiator) || !is_a($ServiceInitiator, 'AblePolecat_Service_InitiatorInterface')) {
      throw new AblePolecat_Service_Exception("Failed to load service or service client identified by '$id'");
    }
    return $ServiceInitiator;
  }
  
  /**
   * Iniitialize service bus.
   *
   * @return bool TRUE if configuration is valid, otherwise FALSE.
   */
  protected function initialize() {
    
    $this->ServiceInitiators = array();
    
    //
    // Map registered service clients client id => class name
    // These are not loaded unless needed to avoid unnecessary overhead of creating a 
    // client connection.
    //
    $CommandResult = AblePolecat_Command_GetRegistry::invoke($this->getDefaultCommandInvoker(), 'AblePolecat_Registry_Class');
    $this->ClassRegistry = $CommandResult->value();
    
    if (isset($this->ClassRegistry)) {
      $ServiceInitiators = $this->ClassRegistry->getClassListByKey(AblePolecat_Registry_Class::KEY_INTERFACE, 'AblePolecat_Service_InitiatorInterface');
      foreach ($ServiceInitiators as $className => $classInfo) {
        $Id = $classInfo[AblePolecat_Registry_Class::KEY_ARTICLE_ID];
        $this->ServiceInitiators[$Id] = $className;
      }
    }
  }
}