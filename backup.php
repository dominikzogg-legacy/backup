<?php

class Backup
{
    /**
     * @var boolean $boolDebug debugging active or not
     */
    protected $_boolDebug = false;

    /**
     * @var string backup folder path
     */
    protected $_strFolderPath = '';

    /**
     * @var array folders to backup
     */
    protected $_arrFoldersToBackup = array();

    /**
     * @var integer backup how many days
     */
    protected $_intBackupCount = 0;

    /**
     * @var string date format
     */
    protected $_strDateFormat = '';

    /**
     * @var string date time zone
     */
    protected $_strDateTimeZone = '';

    /**
     * @var string today date
     */
    protected $_strDateToday = '';

    /**
     *@var string date to delete
     */
    protected $_strDateDelete = '';

    /**
     * @var array log
     */
    protected $_arrLog = array();

    /**
     * __construct
     * @param boolean $boolDebug debugging active or not
     */
    public function __construct($boolDebug = false)
    {
        if(is_bool($boolDebug))
        {
            $this->_boolDebug = $boolDebug;
        }
    }

    /**
     * setFolderPath
     * @param string $strPath the backup folder path
     * @return object $this
     */
    public function setFolderPath($strPath)
    {
        // stop if the folder not exists
        if(!is_dir($strPath))
        {
            $this->_throwException("The folder path {$strPath} does not exists");
        }
        $this->_strFolderPath = $strPath;
        return($this);
    }

    /**
     * setFoldersToBackup
     * @param array $arrPaths the folder paths to backup
     * @return object $this
     */
    public function setFoldersToBackup(array $arrPaths)
    {
        // stop if the given value is not an array
        if(!is_array($arrPaths))
        {
            $this->_throwException("The folder paths must be an array, " . gettype($arrPaths) . " given");
        }
        // stop if the given value is an empty array
        if(count($arrPaths) < 1)
        {
            $this->_throwException('Empty array given');
        }
        foreach($arrPaths as $strPath)
        {
            // stop if the folder not exists
            if(!is_dir($strPath))
            {
                $this->_throwException("The folder path {$strPath} does not exists");
            }
            // add path if its not allready exists
            if(!in_array($strPath, $this->_arrFoldersToBackup))
            {
                $this->_arrFoldersToBackup[] = $strPath;
            }
        }
        return($this);
    }

    /**
     * setBackupCount
     * @param integer $intDays how many days to backup
     * @return object $this
     */
    public function setBackupCount($intDays)
    {
        $intDays = intval($intDays);
        // stop if the number is smaller than 1
        if($intDays < 1)
        {
            $this->_throwException("The value given {$intDays} is not a valid number");
        }
        $this->_intBackupCount = intval($intDays);
        $this->_calculateDates();
        return($this);
    }

    /**
     * setDateFormat
     * @param string $strDateFormat the date format
     * @return object $this
     */
    public function setDateFormat($strDateFormat)
    {
        $objDatetime = new DateTime();
        if($objDatetime->format($strDateFormat) === false)
        {
            $this->_throwException("The time format {$strDateFormat} is invalid");
        }
        $this->_strDateFormat = $strDateFormat;
        $this->_calculateDates();
        return($this);
    }

    /**
     * setTimeZone
     * @param string $strTimeZone the time zone
     * @return object $this
     */
    public function setTimeZone($strTimeZone)
    {
        try
        {
            new DateTimeZone($strTimeZone);
            $this->_strDateTimeZone = $strTimeZone;
            $this->_calculateDates();
            return($this);
        }
        catch(Exception $objException)
        {
            $this->_throwException("The time zone {$strTimeZone} is invalid");
        }
    }

    public function backup()
    {
        // validate if everthing is set to go ahead
        $this->_checkDatesSet();

        // create daily folder
        $this->_createBackupFolderForToday();

        // create the backup
        $this->_backupFolders();

        // delete oldest folder
        $this->_deleteOldest();
    }

    /**
     * _calculateDates
     */
    protected function _calculateDates()
    {
        // check if the needed values are set
        if($this->_checkDatesSet(false))
        {
            // reset values
            $this->_strDateToday = '';
            $this->_strDateDelete = '';

            // prepare datetime object
            $objDateTime = new DateTime('now', new DateTimeZone($this->_strDateTimeZone));

            // set today date
            $this->_strDateToday = $objDateTime->format($this->_strDateFormat);

            for($intCounter = $this->_intBackupCount; $intCounter > 0; $intCounter--)
            {
                $objDateTime = $objDateTime->modify('-1 day');
            }

            // set delete date
            $this->_strDateDelete = $objDateTime->format($this->_strDateFormat);
        }
    }

    /**
     * _checkDatesSet
     * @param boolean $boolForceExceptions force exceptions
     * @return boolean true|false
     */
    protected function _checkDatesSet($boolForceExceptions = true)
    {
        // all needed parameters are set
        if($this->_intBackupCount > 0 && $this->_strDateFormat != '' && $this->_strDateTimeZone != '')
        {
            return(true);
        }
        // at mimimum one paramete is wrong, but its not critical
        elseif($boolForceExceptions == false)
        {
            return(false);
        }
        // at mimimum one paramete is wrong
        else
        {
            if($this->_intBackupCount == 0)
            {
                $this->_throwException('Please call setBackupCount() method');
            }
            if($this->_strDateFormat == '')
            {
                $this->_throwException('Please call setDateFormat() method');
            }
            if($this->_strDateTimeZone == '')
            {
                $this->_throwException('Please call setDateFormat() method');
            }
        }
        $this->_throwException('Please create a bug report on github');
    }

    /**
     * _createBackupFolderForToday
     */
    protected function _createBackupFolderForToday()
    {
        // path to the daily folder
        $strFolderPath = $this->_strFolderPath . '/' . $this->_strDateToday;

        // create it only if it doesnt exists
        $this->_createFolder($strFolderPath);
    }

    /**
     * _backupFolders
     */
    protected function _backupFolders()
    {
        // foreach to backup folder
        foreach($this->_arrFoldersToBackup as $strSourcePath)
        {
            // destination path
            $strDestinationPath = $this->_strFolderPath . '/' . $this->_strDateToday . $strSourcePath . '/';

            // create desination folder
            $this->_createFolder($strDestinationPath);

            // rsync command
            $strRsyncCommand = "rsync -a --delete {$strSourcePath}/* {$strDestinationPath}";

            // add command to log
            $this->_arrLog[] = $strRsyncCommand;

            // run rsync
            $this->_arrLog[] = system($strRsyncCommand, $intReturnValue);
            if($intReturnValue !== 0)
            {
                $this->_throwException("Can't run the following command successfully: {$strRsyncCommand}");
            }
        }
    }

    /**
     * _deleteOldest
     */
    protected function _deleteOldest()
    {
        // delte folder path
        $strDeletePath = $this->_strFolderPath . '/' . $this->_strDateDelete;

        // rm command
        $strDeleteCommand = "rm -Rf {$strDeletePath}";

        // add command to log
        $this->_arrLog[] = $strDeleteCommand;

        // rm
        $this->_arrLog[] = system($strDeleteCommand, $intReturnValue);
        if($intReturnValue !== 0)
        {
            $this->_throwException("Can't run the following command successfully: {$strDeleteCommand}");
        }
    }

    /**
     * _createFolder
     * @param string $strFolderPath folderpath
     */
    protected function _createFolder($strFolderPath)
    {
        // create it only if it doesn't exists
        if(!is_dir($strFolderPath))
        {
            if(!mkdir($strFolderPath, 0600, true))
            {
                $this->_throwException("Can't create the folder {$strFolderPath}");
            }
        }
    }

    /**
     * _throwException
     */
    protected function _throwException($strMessageToOutput)
    {
        if($this->_boolDebug)
        {
            print_r($this->_arrLog);
        }
        throw new Exception($strMessageToOutput);
    }
}