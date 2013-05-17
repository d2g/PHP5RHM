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
function realPathFromRelative($path) 
{ 

    $path = preg_replace("#/+\.?/+#", "/", str_replace("\\", "/", $path)); 
    $dirs = explode("/", rtrim(preg_replace('#^(\./)+#', '', $path), '/')); 

    $offset = 0; 
    $sub = 0; 
    $subOffset = 0; 
    $root = ""; 

    if (empty($dirs[0])) 
    { 
        $root = "/"; 
        $dirs = array_splice($dirs, 1); 
    } 
    else if (preg_match("#[A-Za-z]:#", $dirs[0])) 
    { 
        $root = strtoupper($dirs[0]) . "/"; 
        $dirs = array_splice($dirs, 1); 
    }  

    $newDirs = array(); 
    foreach($dirs as $dir) 
    { 
        if ($dir !== "..") 
        { 
            $subOffset--;     
            $newDirs[++$offset] = $dir; 
        } 
        else 
        { 
            $subOffset++; 
            if (--$offset < 0) 
            { 
                $offset = 0; 
                if ($subOffset > $sub) 
                { 
                    $sub++; 
                }  
            } 
        } 
    } 

    if (empty($root)) 
    { 
        $root = str_repeat("../", $sub); 
    }

    return $root . implode("/", array_slice($newDirs, 0, $offset)); 
} 
?>