<?php

/**
 * Contao Open Source CMS
 *
 * @copyright  MEN AT WORK 2013 
 * @package    syncCto
 * @license    GNU/LGPL 
 * @filesource
 */

/**
 * Initialize the system
 */
define('TL_MODE', 'BACKUP');
require_once('../../../initialize.php');

/**
 * Class PurgeLog
 */
class CronFileBackups extends Backend
{

    /**
     * @var SyncCtoFiles 
     */
    protected $objSyncCtoFiles;

    /**
     * @var SyncCtoHelper 
     */
    protected $objSyncCtoHelper;

    /**
     * Initialize the controller
     */
    public function __construct()
    {
        parent::__construct();

        $this->objSyncCtoFile = SyncCtoFiles::getInstance();
        $this->objSyncCtoHelper = SyncCtoHelper::getInstance();
    }

    /**
     * Implement the commands to run by this batch program
     */
    public function run()
    {
        try
        {
            // Create XML file path
            $strXMLPath  = $this->objSyncCtoHelper->standardizePath($GLOBALS['SYC_PATH']['file'], "Auto-File-BackUp.xml");
            $booFirstRun = false;

            // Check if we allready have a filelist
            if (!file_exists(TL_ROOT . "/" . $strXMLPath))
            {
                $booFirstRun = true;

                if (!$this->objSyncCtoFile->getChecksumFilesAsXML($strXMLPath, true, true, SyncCtoEnum::FILEINFORMATION_SMALL))
                {
                    $this->log("Error by creating filelist.", __CLASS__ . " | " . __FUNCTION__, TL_CRON);
                }
            }

            $arrResult = null;

            // Do Backup
            if ($booFirstRun == true)
            {
                // If first run, the function will create a file name
                $arrResult = $this->objSyncCtoFile->runIncrementalDump($strXMLPath, $GLOBALS['SYC_PATH']['file'], null, 100);

                // Save zipname into xml
                // Open XML Reader
                $objXml     = new DOMDocument("1.0", "UTF-8");
                $objXml->load(TL_ROOT . "/" . $strXMLPath);
                // Search metatags
                $objXml->getElementsByTagName("metatags")->item(0);
                // Create new tag
                $objFileXML = $objXml->createElement("zipfile", $arrResult["file"]);
                // Add to document
                $objXml->getElementsByTagName("metatags")->item(0)->appendChild($objFileXML);
                // Save
                $objXml->save(TL_ROOT . "/" . $strXMLPath);
            }
            else
            {
                // Load the zipname from xml
                $objXml     = new DOMDocument("1.0", "UTF-8");
                $objXml->load(TL_ROOT . "/" . $strXMLPath);
                $strZipFile = $objXml->getElementsByTagName("zipfile")->item(0)->nodeValue;
                unset($objXml);

                // Run backup
                $arrResult = $this->objSyncCtoFile->runIncrementalDump($strXMLPath, $GLOBALS['SYC_PATH']['file'], $strZipFile, 100);
            }

            // If all work is done delete the filelist
            if ($arrResult["done"] == true)
            {
                $objFile = new File($strXMLPath);
                $objFile->delete();
                $objFile->close();
            }
        }
        catch (Exception $exc)
        {
            $this->log("Error by file backup with msg: " . $exc->getMessage(), __CLASS__ . " | " . __FUNCTION__, TL_CRON);
            var_dump($exc);
        }
    }

}

/**
 * Instantiate log purger
 */
$objFileBackups = new CronFileBackups();
$objFileBackups->run();