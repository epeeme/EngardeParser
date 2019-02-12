#engardeParser
==========

engardeParser extracts the results data from every conceivable version of 
engarde software HTML output and parses into a variety of formats.

Usage
-----

``` php
<?php

namespace engarde;
require '../engardeParser.php';

$e = new EngardeParser($engardeResultsURL);
```

* `parseTitle()`: returns the text between <title></title>
* `getMetaData()`: returns an array of applicable <meta> data
* `parseHeaderBlock()`: returns an array of data extracted from the <h1> block

