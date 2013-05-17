<?php
/* 
 * @package     PHP5 Revision/History Manager Class
 * @author      Dan Goldsmith
 * @copyright   Dan Goldsmith 2012
 * @link        http://d2g.org.uk/
 * @version     {SUBVERSION_BUILD_NUMBER}
 * 
 * @licence     MPL 2.0
 * 
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/. 
 */
if(!defined('VCDOCUMENT_REVISION_DIRECTORY'))
{
    //Load from config ini if it exists
    if(is_file(dirname(__FILE__) ."/config.ini"))
    {
        $config = parse_ini_file(dirname(__FILE__) ."/config.ini", true);
        if(is_array($config) && array_key_exists('DIRECTORIES',$config) && is_array($config['DIRECTORIES']) && array_key_exists('REVISIONS',$config['DIRECTORIES']))
        {
            define('VCDOCUMENT_REVISION_DIRECTORY', dirname(__FILE__) . "/" . $config['DIRECTORIES']['REVISIONS']);
        }
    }
    
    if(!defined('VCDOCUMENT_REVISION_DIRECTORY'))
    {
        DEFINE('VCDOCUMENT_REVISION_DIRECTORY',dirname(__FILE__) . '/revisions');
    }
}

if(!function_exists("realPathFromRelative"))
{
    require_once(dirname(__FILE__) . "/functions/realPathFromRelative.function.php");
}


require_once(dirname(__FILE__) . "/finediff/FineDiff.class.php");

/*
 * Version Control Document
 * 
 * Example.
 * $document = new VCDocument('TEST.TXT');  // Where TEST.TXT is the document ID (This has to be a valid filename)
 * 
 *                                          // All Versions Of The Document Are Stored as TEST.TXT.Revision_Number
 *                                          // in The Path VCDOCUMENT_REVISION_DIRECTORY (In the Settings.inc.php).
 * 
 *                                          // By Not Passing a Version ID you get The latest Version.
 * 
 * $document->setParameters(array('author' => 'goldsmithd','created' => '2011/10/31 08:00:00')); //Set Parameters allows you to store an array of information with the document.
 * $document->setDocument(file_get_contents(dirname(__FILE__) . "/sample_from.txt"); //Sends the document to be stored, this creates the diff which is stored in content.
 * 
 * //$document now has version 0
 * 
 * $document->setDocument(file_get_contents(dirname(__FILE__) . "/sample_to.txt")); //Sends the changed document
 * 
 * //$document now has version 1
 * $document->getContent(); // Gets The diff of between doc 1 and 0.(It only contains the diff with the previous version)
 * $document->getDocument(); // returns the whole document, in this example the same as file_get_contents(dirname(__FILE__) . "/sample_to.txt")
 * 
 * //Revert Back to Original Version
 * $document->revertToVersion(0); // Reverts the document back to version 0 in this case equal to file_get_contents(dirname(__FILE__) . "/sample_from.txt").
 * 
 * // Create Cache
 * // It stores the full document instead of the diff from the previous. Useful when you reach a point where so many differences appear that the overhead 
 * // of generating the document is not worth the saving of disk space.
 * $document->createCache();
 */
class VCDocument
{
    private $id                 = null;
    private $version            = null;
    private $content            = null; //Could contain either full document or update.
    private $parameters         = null;
    private $partial            = true; //Marks if it is a partial version/update or Full document
    
    public function __construct($documentid,$version = null) 
    {
        $this->setID($documentid);
        $this->setVersion($version);
        $this->getFromFileSystem();
    }
    
    private function setID($id)
    {
        //Check valid id.
        $this->id = $id;
    }
    
    public function getID()
    {
        return $this->id;
    }
    
    private function setVersion($version)
    {
        $this->version = $version;
    }
    
    public function getVersion()
    {
        return $this->version;
    }
    
    public function setContent($content)
    {
        $this->content = $content;
    }
    
    public function getContent()//Returns the content not the full document
    {
        return $this->content;
    }
    
    public function setParameters($params)
    {
        $this->parameters = $params;
    }
    
    public function getParameters()
    {
        return $this->parameters;
    }
    
    private function setPartial($ispartial)
    {
        $this->partial = $ispartial;
    }
    
    public function isPartial()
    {
        return $this->partial;
    }

    public function setDocument($full_document)
    {
        $full_document = mb_convert_encoding($full_document,"UTF-8");
        
        if($this->getVersion() === null)
        {
            //This document is not under versioning.
            $this->setPartial(false);
            $this->setContent($full_document);
            $this->setVersion(0);
            $this->setToFileSystem();
        }
        else 
        {
            //A version of this already exists lets calculate the diff.
            
            //if the current_document and the document passed are the same then we don't need to version it.
            if($this->getDocument() !== $full_document)
            {
                $this->setContent(FineDiff::getDiffOpcodes($this->getDocument(), $full_document));
                $this->setPartial(true);
                $this->setVersion($this->getVersion()+1);
                $this->setToFileSystem();                
            }
        }        
    }
    
    public function getDocument()
    {
        if($this->getVersion() === null)
        {
            return;
        }
        
        if($this->isPartial() === false)
        {
            return $this->getContent();
        }
        
        $parent = new VCDocument($this->getID(),$this->getVersion() - 1);
        return FineDiff::renderToTextFromOpcodes($parent->getDocument(),$this->getContent());
    }
    
    /*
     * Write To Filesystem
     */
    private function setToFileSystem()
    {
        $stored_data = new stdClass;
        $stored_data->parameters    = $this->getParameters();
        $stored_data->content       = $this->getContent();
        $stored_data->partial       = $this->isPartial();
        
        /*
         * We need to stop $this->getID() ending outside the location where documents should be stored. This is to stop people tring to put ID's that are ../../
         */
        $current_path = dirname(VCDOCUMENT_REVISION_DIRECTORY . "/" . $this->getID() . "." . $this->getVersion());
        $count = 0;
        
        while(true)
        {
            if(realPathFromRelative($current_path) === realPathFromRelative(dirname(VCDOCUMENT_REVISION_DIRECTORY . "/")))
            {
                break;
            }
            else
            {
                $current_path = $current_path . '/../';
            }
            
            if(realPathFromRelative($current_path) == realPathFromRelative('/') || $count > 50) // Get out if we start going to far.
            {
                throw new Exception("ID creates a path outside storage directory");
            }
            $count++;
        }
        
        //If the directory doesn't exists then lets create it.
        if(!is_dir(dirname(VCDOCUMENT_REVISION_DIRECTORY . "/" . $this->getID() . "." . $this->getVersion())))
        {
            mkdir(dirname(VCDOCUMENT_REVISION_DIRECTORY . "/" . $this->getID() . "." . $this->getVersion()), 0777, true);
        }
        
        file_put_contents(VCDOCUMENT_REVISION_DIRECTORY . "/" . $this->getID() . "." . $this->getVersion(), json_encode($stored_data));
    }
    
    private function getFromFileSystem()
    {
        /*
         * We need to stop $this->getID() ending outside the location where documents should be stored. This is to stop people tring to put ID's that are ../../
         */
        $current_path = dirname(VCDOCUMENT_REVISION_DIRECTORY . "/" . $this->getID() . "." . $this->getVersion()) . "/";
        $count = 0;

        
        while(true)
        {
            if(realPathFromRelative($current_path) === realPathFromRelative(dirname(VCDOCUMENT_REVISION_DIRECTORY . "/")))
            {
                break;
            }
            else
            {
                $current_path = $current_path . '../';
            }
            
            if(realPathFromRelative($current_path) == realPathFromRelative('/') || $count > 50) // Get out if we start going to far.
            {
                throw new Exception("ID creates a path outside storage directory");
            }
            $count++;
        }
        
        if($this->getVersion() === null || !is_file(VCDOCUMENT_REVISION_DIRECTORY . "/" . $this->getID() . "." . $this->getVersion()))
        {
            //If the version is not set or the version file doesn't exists
            //Set the version back to zero
            $this->setVersion(0);
            
            //Work out the current version
            while(is_file(VCDOCUMENT_REVISION_DIRECTORY . "/" . $this->getID() . "." . $this->getVersion()))
            {
                $this->setVersion($this->getVersion() + 1);
            }
            
            if($this->getVersion() !== 0)
            {
                //The version number is next version get the version before.
                $this->setVersion($this->getVersion() - 1);

                //Get that version from the filesystem
                $stdobject = json_decode(file_get_contents(VCDOCUMENT_REVISION_DIRECTORY . "/" . $this->getID() . "." . $this->getVersion()));

                $this->setParameters((array)$stdobject->parameters);
                $this->setContent($stdobject->content);
                $this->setPartial($stdobject->partial);
            }
            else
            {
                //Version will alway be one above the last version at this stage.
                //If the version number is 0 then actually the document has no version.
                $this->setVersion(null);
            }
        }
        else
        {
            //Has version and the matching file exists
            //Get that version from the filesystem
            $stdobject = json_decode(file_get_contents(VCDOCUMENT_REVISION_DIRECTORY . "/" . $this->getID() . "." . $this->getVersion()));

            $this->setParameters($stdobject->parameters);
            $this->setContent($stdobject->content);
            $this->setPartial($stdobject->partial);            
        }
    }
    
    public function revertToVersion($version_number)
    {
        $version_number++;
        
        while(is_file(VCDOCUMENT_REVISION_DIRECTORY . "/" . $this->getID() . "." . $version_number))
        {
            unlink(VCDOCUMENT_REVISION_DIRECTORY . "/" . $this->getID() . "." . $version_number);
            $version_number++;
        }
        
        $this->setVersion($version_number);
        $this->getFromFileSystem();
    }

    public function createCache()
    {
        $this->setContent($this->getDocument());
        $this->setPartial(false);
        $this->setToFileSystem();
    }

    public function clearHistory()
    {
        //Clear history from the current version and below. 
        //History from further on needs to be kept.
        //So if we clear history on version 20, 20 becomes 0, 21 becomes 1 etc.
        $current_version = $this->getVersion();
        $current_document = $this->getDocument();
        
        for($i = 0;$i <= $current_version;$i++)
        {
            if(is_file(VCDOCUMENT_REVISION_DIRECTORY . "/" . $this->getID() . "." . $i))
            {
                unlink(VCDOCUMENT_REVISION_DIRECTORY . "/" . $this->getID() . "." . $i);
            }
        }

        $this->setVersion(null);
        $this->setDocument($current_document);
        
        //Move all the following down
        $current_version = $current_version + 1;
        $new_version = 1;
        
        while(is_file(VCDOCUMENT_REVISION_DIRECTORY . "/" . $this->getID() . "." . $current_version))
        {
            rename(VCDOCUMENT_REVISION_DIRECTORY . "/" . $this->getID() . "." . $current_version,VCDOCUMENT_REVISION_DIRECTORY . "/" . $this->getID() . "." . $new_version);
            $new_version++;
            $current_version++;
        }        
    }
    
    public function getModificationDateTime()
    {
        if($this->getVersion() !== null)
        {
            return filemtime(VCDOCUMENT_REVISION_DIRECTORY . "/" . $this->getID() . "." . $this->getVersion());
        }
        return;
    }

    public function deleteDocument()
    {
        $version_number = 0;
        
        while(is_file(VCDOCUMENT_REVISION_DIRECTORY . "/" . $this->getID() . "." . $version_number))
        {
            unlink(VCDOCUMENT_REVISION_DIRECTORY . "/" . $this->getID() . "." . $version_number);
            $version_number++;
        }        
    }
    
}

?>