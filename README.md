engardeParser
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

* `parseTitle()`: returns the text between TITLE tag
* `parseGenerator()`: returns the META Generator value
* `parseProgId()`: returns the META ProgId value
* `parseOriginator()`: returns the META Originator value

* `parseHeaderBlock()`: returns an array of data extracted from the H1 block

* `getMetaData()`: returns an array of associated META data
* `getEngardePage()`: returns the raw HTML from the engardeResultsURL
* `getAllResults()`: returns a parsed array containing all the results

``` php
<?php

namespace engarde;
require '../engardeParser.php';

$e = new EngardeFormatter($engardeResultsURL);
```

* `getAllResultsCSV()`: returns a CSV formatted file of all the results
