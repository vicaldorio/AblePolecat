<?php
/**
 * @file: Csv.php
 * Logs messages to comma separated file.
 */

require_once(ABLE_POLECAT_CORE . DIRECTORY_SEPARATOR . 'Log.php');

class AblePolecat_Log_Csv extends AblePolecat_LogAbstract {
  
  /**
   * @var resource Handle to log file.
   */
  private $hFile;
  
  /**
   * Extends __construct().
   */
  protected function initialize() {
    //
    // Default name of log file is YYYY_MM_DD.csv
    //
    $file_name = AblePolecat_Server_Paths::getFullPath('logs') . DIRECTORY_SEPARATOR . date('Y_m_d', time()) . '.csv';
    $this->hFile = @fopen($file_name, 'a');
    if ($this->hFile == FALSE) {
      $this->hFile = NULL;
      $msg = sprintf(
        "Able Polecat attempted to open a CSV log file in the directory given at %s. No such directory exists or it is not writable by web agent.",
        AblePolecat_Server_Paths::getFullPath('logs')
      );
      throw new AblePolecat_Log_Exception(
        $msg,
        AblePolecat_Error::BOOTSTRAP_LOGGER
      );
      // trigger_error($msg, E_USER_ERROR);
    }
  }
  
  /**
   * Helper function.Writes message to file.
   * 
   * @param string $type STATUS | WARNING | ERROR.
   * @param string $msg  Body of message.
   */
  public function putMessage($type, $msg) {
    if (isset($this->hFile)) {
      !is_string($msg) ? $message = serialize($msg) : $message = $msg;
      switch ($type) {
        default:
          $type = 'info';
          break;
        case AblePolecat_LogInterface::STATUS:
        case AblePolecat_LogInterface::WARNING:
        case AblePolecat_LogInterface::ERROR:
        case AblePolecat_LogInterface::DEBUG:
          break;
      }
      $line = array(
        $type, 
        date('H:i:s u e', time()),
        $message,
      );
      fputcsv($this->hFile, $line);
    }
  }
  
  /**
   * Dump backtrace to logger with message.
   *
   * Typically only called in a 'panic' situation during testing or development.
   *
   * @param variable $msg Variable list of arguments comprising message.
   */
  public static function dumpBacktrace($msg = NULL) {
    $debug_backtrace = debug_backtrace();
    try {
      $Log = new AblePolecat_Log_Csv();
      foreach($debug_backtrace as $line => $trace) {
        $Log->putMessage(AblePolecat_LogInterface::DEBUG, print_r($trace, TRUE));
      }
    }
    catch (Exception $Exception) {
      echo $Exception->getMessage();
      foreach($debug_backtrace as $line => $trace) {
        print_r($trace);
      }
    }
  }
  
  /**
   * Serialize object to cache.
   *
   * @param AblePolecat_AccessControl_SubjectInterface $Subject.
   */
  public function sleep(AblePolecat_AccessControl_SubjectInterface $Subject = NULL) {
    if (isset($this->hFile)) {
      fclose($this->hFile);
      $this->hFile = NULL;
    }
  }
  
  /**
   * Create a new instance of object or restore cached object to previous state.
   *
   * @param AblePolecat_AccessControl_SubjectInterface Session status helps determine if connection is new or established.
   *
   * @return AblePolecat_Log_Csv or NULL.
   */
  public static function wakeup(AblePolecat_AccessControl_SubjectInterface $Subject = NULL) {
    return new AblePolecat_Log_Csv();
  }
}