<?php
/**
 * @file      polecat/core/Transaction/Restricted/Update.php
 * @brief     Encloses update procedures within a transaction.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.7.0
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Transaction', 'Restricted.php')));

class AblePolecat_Transaction_Restricted_Update extends AblePolecat_Transaction_RestrictedAbstract {
  
  /**
   * Registry article constants.
   */
  const UUID = 'fb2a9ab0-b6b4-11e4-a12d-0050569e00a2';
  const NAME = 'AblePolecat_Transaction_Restricted_Update';
  
  /********************************************************************************
   * Implementation of AblePolecat_CacheObjectInterface.
   ********************************************************************************/
  
  /**
   * Create a new instance of object or restore cached object to previous state.
   *
   * @param AblePolecat_AccessControl_SubjectInterface Session status helps determine if connection is new or established.
   *
   * @return AblePolecat_CacheObjectInterface Initialized server resource ready for business or NULL.
   */
  public static function wakeup(AblePolecat_AccessControl_SubjectInterface $Subject = NULL) {
    //
    // Unmarshall (from numeric keyed index to named properties) variable args list.
    //
    $ArgsList = self::unmarshallArgsList(__FUNCTION__, func_get_args());
    $Transaction = new AblePolecat_Transaction_Restricted_Update($ArgsList->getArgumentValue(self::TX_ARG_SUBJECT));
    self::prepare($Transaction, $ArgsList, __FUNCTION__);
    return $Transaction;
  }
  
  /********************************************************************************
   * Implementation of AblePolecat_TransactionInterface.
   ********************************************************************************/
  
  /**
   * Commit
   */
  public function commit() {
    //
    // Parent updates transaction in database.
    //
    parent::commit();
  }
  
  /**
   * Rollback
   */
  public function rollback() {
    //
    // @todo
    //
  }
  
  /**
   * Run the install procedures.
   *
   * @return AblePolecat_ResourceInterface
   * @throw AblePolecat_Transaction_Exception If cannot be brought to a satisfactory state.
   */
  public function run() {
    
    $Resource = NULL;
    
    switch ($this->getRequest()->getMethod()) {
      default:
        break;
      case 'GET':
        //
        // Resource request resolves to registered class name, try to load.
        // Attempt to load resource class
        //
        try {
          $ResourceRegistration = $this->getResourceRegistration();
          if (isset($ResourceRegistration) && ($ResourceRegistration->getClassId() === AblePolecat_Resource_Restricted_Update::UUID)) {
            $Resource = AblePolecat_Resource_Core_Factory::wakeup(
              $this->getDefaultCommandInvoker(),
              'AblePolecat_Resource_Restricted_Update'
            );
            $this->setStatus(self::TX_STATE_COMPLETED);
          }
        }
        catch(AblePolecat_AccessControl_Exception $Exception) {
          $Resource = parent::run();
        }
        break;
      case 'POST':
        $referer = $this->getRequest()->getQueryStringFieldValue(AblePolecat_Transaction_RestrictedInterface::ARG_REFERER);
        if (isset($referer)) {
          $CoreDatabase = AblePolecat_Mode_Config::wakeup()->getCoreDatabase();
          switch($referer) {
            default:
              //
              // @todo: invalid referer for POST update
              //
              break;
            case AblePolecat_Resource_Restricted_Install::UUID:
              if (FALSE === AblePolecat_Mode_Config::coreDatabaseIsReady()) {
                //
                // Get rid of db errors.
                //
                $dbErrors = $CoreDatabase->flushErrors();
                
                //
                // First, establish connection to db and update local project 
                // configuration file.
                //
                if ($this->authenticate()) {
                  //
                  // Connection established, update local project conf file.
                  //
                  $localProjectConfFile = AblePolecat_Mode_Config::getLocalProjectConfFile();
                  $coreDatabaseElementId = AblePolecat_Mode_Config::getCoreDatabaseId();
                  $Node = AblePolecat_Dom::getElementById($localProjectConfFile, $coreDatabaseElementId);
                  if (isset($Node)) {
                    foreach($Node->childNodes as $key => $childNode) {
                      if($childNode->nodeName == 'polecat:dsn') {
                        $childNode->nodeValue = $this->getSecurityToken();
                        break;
                      }
                    }
                  }
                  $localProjectConfFilepath = AblePolecat_Mode_Config::getLocalProjectConfFilePath();
                  $localProjectConfFile->save($localProjectConfFilepath);
                }
              }
              
              //
              // Order is important. FK UUIDs are generated first by classes, 
              // which reference them second.
              //
              if ($CoreDatabase->ready()) {
                //
                // Step 1. Install current database schema.
                //
                AblePolecat_Database_Schema::install($CoreDatabase);
                $dbErrors = $CoreDatabase->flushErrors();
                foreach($dbErrors as $errorNumber => $error) {
                  $error = AblePolecat_Database_Pdo::getErrorMessage($error);
                  AblePolecat_Mode_Server::logBootMessage(AblePolecat_LogInterface::ERROR, $error);
                }
                
                //
                // @todo: this hack works around an error, which causes install
                // procedure to fail because 'SELECT UUID()' returns NULL on 
                // first call for some reason.
                // 
                // $uuid = AblePolecat_Registry_Entry_Resource::generateUUID();
                // AblePolecat_Mode_Server::logBootMessage(AblePolecat_LogInterface::STATUS, $uuid);
                
                //
                // Step 2. Register class libraries ([lib]).
                // FK references to [lib].[id]:
                // [class].[classLibraryId]
                //
                AblePolecat_Registry_ClassLibrary::install($CoreDatabase);
                
                //
                // Step 3. Register classes ([class]).
                // FK references to [class].[id]:
                // [resource].[classId]
                // [response].[classId]
                // [connector].[classId]
                // [component].[classId]
                //
                AblePolecat_Registry_Class::install($CoreDatabase);
                
                //
                // Step 4. Register resources ([resource]).
                // FK references to [resource].[id]:
                // [connector].[resourceId]
                // [response].[resourceId]
                //
                AblePolecat_Registry_Resource::install($CoreDatabase);
                
                //
                // Step 5. Register connectors ([connector]).
                //
                AblePolecat_Registry_Connector::install($CoreDatabase);
                
                //
                // Step 6. Register responses ([response]).
                // FK references to [response].[id]:
                // [template].[articleId]
                //
                AblePolecat_Registry_Response::install($CoreDatabase);
                
                //
                // Step 7. Register components ([component]).
                // FK references to [component].[id]:
                // [template].[articleId]
                //
                AblePolecat_Registry_Component::install($CoreDatabase);
                
                //
                // Step 8. Register templates ([template]).
                //
                AblePolecat_Registry_Template::install($CoreDatabase);
                
                //
                // @todo: status message of some kind?
                //
                $Resource = AblePolecat_Resource_Core_Factory::wakeup(
                  $this->getDefaultCommandInvoker(),
                  'AblePolecat_Resource_Core_Ack'
                );
                $this->setStatus(self::TX_STATE_COMPLETED);
              }
              else {
                $dbErrors = $CoreDatabase->flushErrors();
                $error = 'Database authentication failed';
                foreach($dbErrors as $errorNumber => $error) {
                  $error = AblePolecat_Database_Pdo::getErrorMessage($error);
                  AblePolecat_Mode_Server::logBootMessage(AblePolecat_LogInterface::ERROR, $error);
                }
                $Resource = AblePolecat_Resource_Core_Factory::wakeup(
                  $this->getDefaultCommandInvoker(),
                  'AblePolecat_Resource_Core_Error',
                  'Access Denied',
                  $error
                );
                $this->setStatus(self::TX_STATE_COMPLETED);
              }
              break;
            case AblePolecat_Resource_Restricted_Update::UUID:
              //
              // Order is important. FK UUIDs are generated first by classes, 
              // which reference them second.
              //
              if ($this->authenticate()) {
                //
                // Step 1. Update class libraries ([lib]).
                // FK references to [lib].[id]:
                // [class].[classLibraryId]
                //
                AblePolecat_Registry_ClassLibrary::update($CoreDatabase);
                
                //
                // Step 2. Update classes ([class]).
                // FK references to [class].[id]:
                // [resource].[classId]
                // [response].[classId]
                // [connector].[classId]
                // [component].[classId]
                //
                AblePolecat_Registry_Class::update($CoreDatabase);
                
                //
                // Step 3. Update resources ([resource]).
                // FK references to [resource].[id]:
                // [connector].[resourceId]
                // [response].[resourceId]
                //
                AblePolecat_Registry_Resource::update($CoreDatabase);
                
                //
                // Step 4. Update connectors ([connector]).
                //
                AblePolecat_Registry_Connector::update($CoreDatabase);
                
                //
                // Step 5. Update responses ([response]).
                // FK references to [response].[id]:
                // [template].[articleId]
                //
                AblePolecat_Registry_Response::update($CoreDatabase);
                
                //
                // Step 6. Update components ([component]).
                // FK references to [component].[id]:
                // [template].[articleId]
                //
                AblePolecat_Registry_Component::update($CoreDatabase);
                
                //
                // Step 7. Update templates ([template]).
                //
                AblePolecat_Registry_Template::update($CoreDatabase);
                
                //
                // @todo: status message of some kind?
                //
                $Resource = AblePolecat_Resource_Core_Factory::wakeup(
                  $this->getDefaultCommandInvoker(),
                  'AblePolecat_Resource_Core_Ack'
                );
                $this->setStatus(self::TX_STATE_COMPLETED);
              }
              else {
                $dbErrors = $CoreDatabase->flushErrors();
                $error = 'Database authentication failed';
                foreach($dbErrors as $errorNumber => $error) {
                  $error = AblePolecat_Database_Pdo::getErrorMessage($error);
                  AblePolecat_Mode_Server::logBootMessage(AblePolecat_LogInterface::ERROR, $error);
                }
                $Resource = AblePolecat_Resource_Core_Factory::wakeup(
                  $this->getDefaultCommandInvoker(),
                  'AblePolecat_Resource_Core_Error',
                  'Access Denied',
                  $error
                );
                $this->setStatus(self::TX_STATE_COMPLETED);
              }
              break;
          }
        }
        break;
    }
    return $Resource;
  }
  
  /********************************************************************************
   * Implementation of AblePolecat_Transaction_RestrictedInterface.
   ********************************************************************************/
  
  /**
   * @return UUID Id of redirect resource on authentication.
   */
  public function getRedirectResourceId() {
    //
    // POST to self.
    //
    return '';
  }
  
  /********************************************************************************
   * Helper functions.
   ********************************************************************************/
  
  /**
   * Extends __construct().
   */
  protected function initialize() {
    parent::initialize();
  }
}