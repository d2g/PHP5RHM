# PHP5 Revision/History Manager Class

The aims of PHP5 Revision/History Manager Class are: 
*   Create a PHP class to handle version control for flat file documents.

Index: 

1.  [Setup & Configuration][1]
2.  [Usage][2]
3.  [Development][3]

<a name="1">
## Setup & Configuration
</a>

I'll make the assumption that you have already downloaded the latest ZIP and extracted the contents.

The class should work already but if you want to specify the location for the files to be stored you can: 
*   [Configure The INI][4]
*   [Define A Constant][5]

<a name="ini">
### Configure the INI
</a> 

The ZIP contains an example ini file (config.example.ini), just rename this to config.ini. The only option avalible in the ini is "REVISIONS", this is the directory where all document virsions will be stored. This directory is relative to the location of the file "VCDocument.class.php". 

config.ini

```
[DIRECTORIES]
REVISIONS = "revisions"
```

<a name="const">
###  Define A Constant
</a>

The other alternative is to define a constant named "VCDOCUMENT\_REVISION\_DIRECTORY" and put the directory location into this variable. 

```
  DEFINE('VCDOCUMENT_REVISION_DIRECTORY',dirname(__FILE__) . '/revisions');
```

<a name="2">
##  Usage
</a>

Lets assume you have a text file you want to keep under version control. Let's name this text file TEST.TXT.   
Now lets put this document under version control. 

```
  $document = new VCDocument('TEST.TXT');
```

We now have a document with a ID/Name of TEXT.TXT.  
  
Before we submit the current version we might want to store some details about the file. Lets store the Author and creation date. You could store anything that you want linked to the document. 

```
  $document->setParameters(array('author' => 'goldsmithd','created' => '2011/10/31 08:00:00')); 
```

Now we need to pass the object content to store, lets pass it the content of our test file (TEXT.TXT). 

```
  $document->setDocument(file_get_contents("TEXT.TXT"));
```
It's only at this point that we now have a stored document. The setDocument function actualy writes the changes to the file. If the content doesn't change on the setDocument call the file will not be written.  
If we look at the file system we will see that the file TEXT.TXT.0 exists. 

Lets check the current version number of the document. In this example this would be 0. 

```
  echo $document->getVersion();
```   
Now lets send in a modified version of the document. Just adding "Extra Text" to the end of the document. 

```
  $document->setDocument(file_get_contents("TEXT.TXT") . "Extra Text");
```   
The document has now moved on to version 1. To view the changes between this version and the previous version.  
This is output as the opcodes required to transition between the documents. 

```
  echo $document->getContent();
```   
To get the current version of the document:   
*This will output the same as: echo file_get_contents("TEXT.TXT") . "Extra Text";*

```
  echo $document->getDocument();
```   
So lets revert back to version 0.   
*This will output the same as echo file_get_contents("TEXT.TXT")*

```
  $document->revertToVersion(0);
```   

## Other Methods/Function

```       
  $document = new VCDocument('TEST.TXT',7);
```
Loads version 7 of the document rather than the current version. 

```
  $document->createCache();
``` 
As each document grows in version numbers the more versions have to be processed before getting to the live document. In the case that the document has changed really hevily but you want to keep the revision history you can create a Cache. This takes a snapshot of the full document at this version. This means that an versions above this don't need to go right the way back to version 0 to calculate the current document. Essentually this allows you to trade cpu usage to build the document with diskspace to hold the complete version of the document. 

  
```       
  $document->clearHistory();
``` 
The current version becomes version 0 and any version higher get renumbered. 

  
<a name="3">
##  Development
</a> 

The project includes and hevily relise on the [FineDiff class provided by Raymond Hill][6]. 

 [1]: #1
 [2]: #2
 [3]: #3
 [4]: #ini
 [5]: #const
 [6]: http://raymondhill.net/blog/?p=441
