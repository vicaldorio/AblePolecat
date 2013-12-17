<?php
/**
 * @file: User.php
 * Base class for User mode (password, security token protected etc).
 */
 
require_once(ABLE_POLECAT_CORE . DIRECTORY_SEPARATOR . 'Mode.php');
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Environment', 'User.php')));

class AblePolecat_Mode_User extends AblePolecat_ModeAbstract {
  
  /**
   * Constants.
   */
  const UUID = 'e7f5dd90-5f4c-11e3-949a-0800200c9a66';
  const NAME = 'Able Polecat User Mode';
  
  /**
   * @var Instance of Singleton.
   */
  private static $UserMode;
  
  /**
   * Extends constructor.
   * Sub-classes should override to initialize members.
   */
  protected function initialize() {
    
    //
    // Check for required server resources.
    // (will throw exception if not ready).
    //
    AblePolecat_Server::getApplicationMode();    
  }
  
  /**
   * Return unique, system-wide identifier.
   *
   * @return UUID.
   */
  public static function getId() {
    return self::UUID;
  }
  
  /**
   * Return Common name.
   *
   * @return string Common name.
   */
  public static function getName() {
    return self::NAME;
  }
  
  /**
   * Get access control agent for current user.
   *
   * @return
   */
  public function getUserAgent() {
    return $this->getAgent();
  }
  
  /**
   * Serialize object to cache.
   *
   * @param AblePolecat_AccessControl_SubjectInterface $Subject.
   */
  public function sleep(AblePolecat_AccessControl_SubjectInterface $Subject = NULL) {
    //
    // @todo: persist
    //
    self::$UserMode = NULL;
  }
  
  /**
   * Create a new instance of object or restore cached object to previous state.
   *
   * @param AblePolecat_AccessControl_SubjectInterface Session status helps determine if connection is new or established.
   *
   * @return AblePolecat_Mode_Application or NULL.
   */
  public static function wakeup(AblePolecat_AccessControl_SubjectInterface $Subject = NULL) {
    
    if (!isset(self::$UserMode)) {
      //
      // Create instance of user mode
      //
      self::$UserMode = new AblePolecat_Mode_User($Subject);
      
      //
      // @todo get user settings from session ($Subject).
      //
        
      //
      // Load environment settings
      //
      $Environment = AblePolecat_Environment_User::wakeup($Subject);
      if (isset($Environment)) {
        self::$UserMode->setEnvironment($Environment);
      }
      else {
        throw new AblePolecat_Environment_Exception('Failed to load Able Polecat user environment.',
          AblePolecat_Error::BOOT_SEQ_VIOLATION);
      }
    }
      
    return self::$UserMode;
  }
}