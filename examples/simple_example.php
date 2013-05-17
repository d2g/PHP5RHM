<?php

require_once(dirname(__FILE__) . '/../VCDocument.class.php');

$document = new VCDocument('TEST.TXT');
$document->setParameters(array('author' => 'goldsmithd','created' => '2011/10/31 08:00:00'));
$document->setDocument(file_get_contents(dirname(__FILE__) . "/sample_from.txt"));

//$document is now version 0

$document->setParameters(array('author' => 'SecondA','created' => '2011/10/31 09:00:00'));
$document->setDocument(file_get_contents(dirname(__FILE__) . "/sample_to.txt"));

//$document is now version 1
var_dump($document->getVersion());
var_dump($document->getContent());
var_dump($document->getDocument());

$document->revertToVersion(0);

//$document back to version 0
var_dump($document->getVersion());
var_dump($document->getParameters());

$document->setParameters(array('author' => 'ThirdA','created' => '2011/10/31 09:30:00'));
$document->setDocument(file_get_contents(dirname(__FILE__) . "/sample_to.txt"));

//$document is now version 1
var_dump($document->getVersion());
var_dump($document->getContent());
var_dump($document->getDocument());

$document->setParameters(array('author' => 'ForthA','created' => '2011/10/31 09:30:00'));
$document->setDocument(file_get_contents(dirname(__FILE__) . "/sample_from.txt"));

//$document is now version 2
var_dump($document->getVersion());
var_dump($document->getContent());
var_dump($document->getDocument());

$document->createCache();

//Document content now has full version
var_dump($document->getContent());

?>