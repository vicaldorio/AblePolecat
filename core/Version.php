<?php
/**
 * @file      polecat/core/Version.php
 * @brief     Main page comments and data relating to Able Polecat current version.
 *
 * @mainpage  Able Polecat Architecture
 *
 * @section introduction Introduction
 *
 * Able Polecat is designed from the ground up to integrate business information
 * systems by exposing end points in a Resource Oriented Architecture (ROA).
 *
 * Since applications on the web are inherently stateless, and this quality is 
 * a cornerstone of any good RESTful design, several of the classes in the core 
 * library are closely related to the components of the Model-View-Controller (MVC)
 * architecture common to many web application frameworks
 *
 * In its simplest form, the design of a web service implemented in Able Polecat 
 * is similar to any other:
 * 1. Agent (e.g. user) requests resource.
 * 2. Server interprets request as a representation of requested resource.
 * 3. Server returns a response to agent.
 *
 * @section request HTTP Request
 *
 * Classes implementing AblePolecat_Message_RequestInterface are used to marshal
 * the URI and entity body of the HTTP request.
 *
 * @section resource Resources
 *
 * Closely related to the 'Model' component in MVC architecture, a resource is 
 * encapsulated by a class implementing AblePolecat_ResourceInterface.
 *
 * @section representation Representation of Resources
 *
 * Closely related to the 'View' component in MVC architecture, a representation
 * of a resource is encapsulated by a class implementing AblePolecat_Message_ResponseInterface.
 *
 * @section   front_controller  Front Controller
 *
 * AblePolecat_Host and AblePolecat_Service_Bus together serve the same purpose 
 * as the front controller in an MVC architecture.
 *
 * @subsection  host  Host
 *
 * Host has the following duties:
 * 1. Marshall web server REQUEST
 * 2. Initiate upstream chain of responsibility (COR - session, user, application, etc).
 * 3. Dispatch marshalled request object
 * 4. Unmarshall RESPONSE, send HTTP response head/body
 *
 * @subsection  bus Service Bus
 *
 * Service bus has the following duties:
 * 1. Route messages between services implemented in Able Polecat.
 * 2. Resolve contention between services.
 * 3. Control data transformation and exchange (DTX) between services.
 * 4. Marshal redundant resources (e.g. web service client connections).
 * 5. Handle messaging, exceptions, logging etc.
 *
 * @section   action_controller Action Controllers
 *
 * Classes implementing AblePolecat_TransactionInterface serve the same purpose
 * as action controllers in an MVC architecture.
 * 
 * @author    Karl Kuhrman
 * @copyright [BDS II License] 
 * @ref       https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md
 * @version   0.6.3
 */

/**
 * Most current version is loaded from conf file. These are defaults.
 */
define('ABLE_POLECAT_VERSION_NAME', 'DEV-0.6.3');
define('ABLE_POLECAT_VERSION_ID', 'ABLE_POLECAT_CORE_0_6_3_DEV');
define('ABLE_POLECAT_VERSION_MAJOR', '0');
define('ABLE_POLECAT_VERSION_MINOR', '6');
define('ABLE_POLECAT_VERSION_REVISION', '3');

final class AblePolecat_Version {
  
  /**
   * AblePolecat_Version INstance of singleton.
   */
  private static $Version;
  
  /**
   * @var string Version number from server config settings file.
   */
  private $info;
  
  /**
   * Get version number of server/core.
   */
  public static function getVersion($as_str = TRUE, $doc_type = 'XML') {
    
    $info = NULL;
    
    if (!isset(self::$Version)) {
      self::$Version = new AblePolecat_Version();
    }
    
    if ($as_str) {
      switch ($doc_type) {
        default:
          $info = sprintf("Able Polecat core %s.%s.%s (%s)",
            self::$Version->info['major'],
            self::$Version->info['minor'],
            self::$Version->info['revision'],
            self::$Version->info['name']
          );
          break;
        case 'XML':
          $info = sprintf("<polecat_version name=\"%s\"><major>%s</major><minor>%s</minor><revision>%s</revision></polecat_version>",
            self::$Version->info['name'],
            strval(self::$Version->info['major']),
            strval(self::$Version->info['minor']),
            strval(self::$Version->info['revision'])
          );
          break;
      }
    }
    else {
      $info = self::$Version->info;
    }
    
    return $info;
  }
  
  protected function __construct() {
    $this->info = array(
      'name' => ABLE_POLECAT_VERSION_NAME,
      'major' => ABLE_POLECAT_VERSION_MAJOR,
      'minor' => ABLE_POLECAT_VERSION_MINOR,
      'revision' => ABLE_POLECAT_VERSION_REVISION,
    );
  }
}