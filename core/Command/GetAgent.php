<?php
/**
 * @file      polecat/Command/GetAgent.php
 * @brief     Retrieve user access agent in scope for command invoker.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.6.1
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Command', 'Reverse.php')));

class AblePolecat_Command_GetAgent extends AblePolecat_Command_ReverseAbstract {
  
  const UUID = '54d2e7d0-77b9-11e3-981f-0800200c9a66';
  const NAME = 'GetAgent';
  
  /********************************************************************************
   * Implementation of AblePolecat_AccessControl_ArticleInterface.
   ********************************************************************************/
     
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
  
  /********************************************************************************
   * Implementation of AblePolecat_CommandInterface.
   ********************************************************************************/
  
  /**
   * Invoke the command and return response from target.
   * 
   * @param AblePolecat_AccessControl_SubjectInterface $Invoker Agent or role invoking command.
   *
   * @return AblePolecat_Command_Result.
   */
  public static function invoke(
    AblePolecat_AccessControl_SubjectInterface $Invoker, 
    $Arguments = NULL
  ) {    
    //
    // Create and dispatch command
    //
    $Command = new AblePolecat_Command_GetAgent($Invoker);
    return $Command->dispatch();
  }
  
  /********************************************************************************
   * Helper functions.
   ********************************************************************************/
}