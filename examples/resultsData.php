<?php

// This example shows the output from getAllResults()

namespace engarde;

require '../engardeParser.php';

$engardeResultsURL = "https://web.archive.org/web/20060512092653/http://www.britishfencing.com/me%20ind%20final%202005.htm";

$e = new EngardeParser($engardeResultsURL);

$allResults = $e->getAllResults();

// HTML TABLE used for simple display purposes only
echo "<TABLE BORDER=1 CELLPADDING=3 CELLSPACING=2>\n";
echo "<TR ALIGN=LEFT><TH>Rank</TH><TH>Surname</TH><TH>Forename</TH><TH>Club</TH><TH>Country</TH></TR>\n";

foreach ($allResults as $value) {
    echo "<TR><TD>".$value[0]."</TD><TD>".$value[1]."</TD><TD>".$value[2]."</TD><TD>".$value[3]."</TD><TD>".$value[4]."</TD></TR>\n";
}
echo "</TABLE>\n";

echo "<p>Extracted from <a href=\"".$engardeResultsURL."\">".$engardeResultsURL."</a></p>";