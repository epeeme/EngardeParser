<?php

// This example shows the extra data for an event that can be pulled
// from the engarde results file

namespace engarde;

require '../engardeParser.php';

$engardeResultsURL = "http://www.engarde-service.com/files/leonpaulfencingcentre/lpjslonfoil18/u13_boys/";

$e = new EngardeParser($engardeResultsURL);

echo "<h3>Event Title</h3>";
echo $e->parseTitle();

echo "<h3>Event Meta Data</h3>";
$meta = $e->getMetaData();

echo "Generator : ".$meta['Generator']."<BR>";
echo "ProgId : ".$meta['ProgId']."<BR>";
echo "Originator : ".$meta['Originator']."<BR>";

$headerInfo = $e->parseHeaderBlock();

echo "<h3>Event Descriptive Header</h3>";
for ($i=0; $i<count($headerInfo); $i++) {
    echo $headerInfo[$i]."<br>";
}

echo "<p>Extracted from <a href=\"".$engardeResultsURL."\">".$engardeResultsURL."</a></p>";