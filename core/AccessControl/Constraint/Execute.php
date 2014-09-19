<?php
/**
 * @file: Execute.php
 * Base class for constraint on opening an access controlled resource.
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'AccessControl', 'Constraint.php')));

class AblePolecat_AccessControl_Constraint_Execute implements AblePolecat_AccessControl_ConstraintInterface {
  
  /**
   * Constants.
   */
  const UUID = '4455ad00-2332-11e4-8c21-0800200c9a66';
  const NAME = 'execute';
  
  /********************************************************************************
   * Implementation of AblePolecat_AccessControl_ArticleInterface.
   ********************************************************************************/
  
  /**
   * Return unique, system-wide identifier for security constraint.
   *
   * @return string Constraint identifier.
   */
  public static function getId() {
    return self::UUID;
  }
  
  /**
   * Return common name for security constraint.
   *
   * @return string Constraint name.
   */
  public static function getName() {
    return self::NAME;
  }
}