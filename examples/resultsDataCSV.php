<?php

// This example will trigger an automatic download of a CSV formatted results file

namespace engarde;

require '../engardeParser.php';

$engardeResultsURL = "http://www.engarde-service.com/files/leonpaulfencingcentre/lpjslonfoil18/u13_boys/";

$e = new EngardeFormatter($engardeResultsURL);

$e->getAllResultsCSV();