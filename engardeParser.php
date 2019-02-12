<?php

/**
 * Engarde Parser
 * 
 * Extract the results data from every conceivable version of engarde and output in a 
 * variety of formats.
 * 
 * @author  Dan Kew <dan@epee.me>
 * @license http://opensource.org/licenses/gpl-3.0 GNU General Public License, version 3 (GPLv3)
 * @version v1.0.0
 */

class EngardeParser
{
    // The URL of the engarde results page
    private $_urlInFull;

    // The raw HTML of the engarde results page
    private $_dataBody;

    // The header info for the results table
    private $_resultsHeader;

    // The raw HTML of just the results table
    private $_resultsBody;

    // Indexes for the results table
    private $_rankingIndex;
    private $_surnameIndex;
    private $_forenameIndex;
    private $_clubIndex;
    private $_countryIndex;

    // All the values used to identify the text in the engarde table header
    private $_rankArray = array("Rank", "Ranking", "Rnk", "Rg", "Cl.");
    private $_surnameArray = array("Name", "Surname", "Nom", "Apellido-nom");
    private $_forenameArray = array("First name", "Prénom", "Nombre");
    private $_clubArray = array("Club", "Egyesület");
    private $_countryArray = array("Country");

    /**
     * Initialize the class and set its properties.
     * 
     * @param string $inUrl The URL of the engarde results
     * 
     * @since 1.0.0
     */
    public function __construct($inUrl) 
    {        
        // So there is no need to pass the URL around, we fix it here
        $this->_urlInFull = $inUrl;

        // Adjust the URL if needed so it doesn't point to a frameset page
        $this->_isFrameSet();

        // Fetch the page data ready for manipulation
        $this->getEngardePage();
    }

    /**
     * Checks for a URL which points to the older version of engarde that was 
     * made up of three frames in a frameset. If detected, append clasfinal.htm 
     * to the URL and that should now point directly to the results page.
     * 
     * Newer vesions of engarde resolve directly to the results page.
     * 
     * Version information is unfortunately not stored in the engarde HTML output.
     *
     * @since 1.0.0
     * 
     * @return none / updates the $_urlInFull variable accordingly
     */
    private function _isFrameSet()
    {
        if ($this->_URLexists()) {
            if (strpos(file_get_contents($this->_urlInFull), 'FRAMESET') !== false) {
                if (substr($this->_urlInFull, -1) !== '/') { 
                    $this->_urlInFull .= '/'; 
                }
                $this->_urlInFull .= 'clasfinal.htm';    
                if (!($this->_URLexists())) {
                    $this->_handleError('Unable to locate results from supplied URL');
                }
            }        
        } else {
            $this->_handleError('Unable to locate supplied URL');
        }
    }

    /**
     * Checks that the supplied URL exists and is accessible
     *
     * @since 1.0.0
     * 
     * @return boolean $exists
     */
    private function _URLexists()
    {
        $urlHeaders = get_headers($this->_urlInFull);
        if (!$urlHeaders || $urlHeaders[0] == 'HTTP/1.1 404 Not Found') {
            $exists = false;
        } else {
            $exists = true;
        }      
        return $exists;
    }

    /**
     * Ready the HTML table of results for parsing and load into _resultsBody
     *
     * @since 1.0.0
     * 
     * @return none
     */
    private function _prepareResultsData()
    {
        $this->_resultsBody = $this->_dataBody;

        // To accomodate hostoric data, we need to take into account
        // data being extracted from waybackmachine
        if (strpos($this->_dataBody, '<!-- END WAYBACK TOOLBAR INSERT -->') !== false) {
            $this->_resultsBody = stristr($this->_dataBody, '<!-- END WAYBACK TOOLBAR INSERT -->');
        }

        // Now there should be just the one <table tag which signifies
        // the start of the results data
        $this->_resultsBody = stristr(stristr($this->_resultsBody, '<table'), '</table>', true);

    }

    /**
     * Extract out the the header information for the results table. These can
     * vary depending on what the competition organiser has chosen to include.
     * Ordinarily it'll be something like;
     * 
     * Rank | Name | First Name | Club
     * 
     * Column headings should be in the first row, but if some spurious results 
     * start to appear then this will need to be looked at.
     *
     * @since 1.0.0
     * 
     * @return none
     */
    private function _prepareResultsTableHeader()
    {
        if (!(isset($this->_resultsBody))) {
            $this->_prepareResultsData();
        }
        $tableRows = explode('<tr', $this->_resultsBody);

        // First index removed as that's the residue of the <tr>
        $this->_resultsHeader = array_slice(array_map('trim', explode(PHP_EOL, str_replace('&nbsp;', '', strip_tags($tableRows[1])))), 1);
    }

    /**
     * Scans the returned HTML results table header and returns the correct 
     * position of the passed column label
     *
     * @param array $colArray predefined array of known column text labels
     * 
     * @return int index into the table header for the desired column
     */
    private function _setIndexColumn($colArray)
    {
        if (!(isset($this->_resultsHeader))) {
            $this->_prepareResultsTableHeader();
        }

        $i=0;        
        while ($i<count($this->_resultsHeader)) {
            if (array_search($this->_resultsHeader[$i], $colArray) !== false) {
                break;
            }
            $i++;
        }
        return $i<count($this->_resultsHeader) ? $i : -1;
    }

    /**
     * Loads all the column indexes from the HTML results table header so
     * we can be sure we are grabbing the right data from the right columns
     *
     * @return none
     */
    private function _prepareResultsTableHeaderIndexes()
    {
        $this->_rankingIndex = $this->_setIndexColumn($this->_rankArray);
        $this->_surnameIndex = $this->_setIndexColumn($this->_surnameArray);
        $this->_forenameIndex = $this->_setIndexColumn($this->_forenameArray);
        $this->_clubIndex = $this->_setIndexColumn($this->_clubArray);
        $this->_countryIndex = $this->_setIndexColumn($this->_countryArray);
    }

    /**
     * Extracts all the results from the HTML results table, cleans and loads 
     * them into an array ready for manipulation
     *
     * @return array
     */
    public function getAllResults()
    {
        $allResults = array();

        if (!(isset($this->_resultsBody))) {
            $this->_prepareResultsData();
        }
        
        $this->_prepareResultsTableHeaderIndexes();

        $tableRows = array_slice(explode('<tr', $this->_resultsBody), 2);

        for ($i=0; $i < count($tableRows); $i++ ) {
            $tempArray = array_slice(array_map('trim', explode(PHP_EOL, str_replace('&nbsp;', '', strip_tags($tableRows[$i])))),1);
            $rank = $this->_rankingIndex <> -1 ? $tempArray[$this->_rankingIndex] : '';
            $surname = $this->_surnameIndex <> -1 ? $tempArray[$this->_surnameIndex] : '';
            $forename = $this->_forenameIndex <> -1 ? $tempArray[$this->_forenameIndex] : '';
            $club = $this->_clubIndex <> -1 ? $tempArray[$this->_clubIndex] : '';
            $country = $this->_countryIndex <> -1 ? $tempArray[$this->_countryIndex] : '';
            array_push($allResults, array($rank, $surname, $forename, $club, $country));
        }
        
        return $allResults;
    }

    /**
     * Parses the text header from the engarde file and extracts all the supplied 
     * information into an array. NOTE - this header area varies depending on how
     * little on how much information the event organiser supplied when setting
     * up their competition. Data positions do not remain static.
     * 
     * The header block format appears to have remained constant across all versions
     *
     * @since 1.0.0
     * 
     * @return array 
     */
    public function parseHeaderBlock()
    {        
        return array_map('trim', explode(PHP_EOL, trim(strip_tags(stristr(stristr($this->_dataBody, '<h1>'), "</h1>", true)))));
    }

    /**
     * If configured correctly by a competition organiser, the <title> tag will 
     * contain the event name - but this is not always the case.
     * 
     * @since 1.0.0
     * 
     * @return string
     */
    public function parseTitle()
    {
        return strip_tags(stristr(stristr($this->_dataBody, '<title>'), "</title>", true));
    }

    /**
     * Extract Generator Meta data 
     * 
     * EXAMPLE 
     * <meta name="Generator" content="Engarde(PRO+FIE LICE) 10.128 - 17/12/2018" />
     * 
     * @return string
     */
    public function parseGenerator()
    {
        return $this->_parseMetaValue('Generator');
    }

    /**
     * Extract ProgId Meta data 
     * 
     * EXAMPLE 
     * <meta name="ProgId" content="Engarde.Document">
     * 
     * @return string
     */
    public function parseProgId()
    {
        return $this->_parseMetaValue('ProgId');
    }

    /**
     * Extract Originator Meta data 
     * 
     * EXAMPLE 
     * <meta name="Originator" content="Engarde" />
     * 
     * @return string
     */
    public function parseOriginator()
    {
        return $this->_parseMetaValue('Originator');
    }

    /**
     * Returns all the available META data produced by engarde
     * 
     * @return array
     */
    public function getMetaData()
    {  
        return array('Generator' => $this->parseGenerator(), 'ProgId' => $this->parseProgId(), 'Originator' => $this->parseOriginator());
    }

    /**
     * Meta data that has always been present and in some versions contains useful
     * identifiable information about the version of engarde being used.
     * 
     * EXAMPLE 
     * <meta name="Generator" content="Engarde(PRO+FIE LICE) 10.128 - 17/12/2018" />
     * <meta name="ProgId" content="Engarde.Document">
     * <meta name="Originator" content="Engarde" />
     * 
     * @param string $metaString META value to extract
     * 
     * @return string
     */
    private function _parseMetaValue($metaString)
    {
        $meta = stristr($this->_dataBody, '<meta name="'.$metaString.'" content="');
        $metaIndex = 3;
        if ($meta === false) {
            $meta = stristr($this->_dataBody, '<meta name='.$metaString.' content="');
            $metaIndex = 1;
        }    
        return $meta !== false ? explode('"', (stristr($meta, '>', true)))[$metaIndex] : '';
    }

    /**
     * Gets the HTML from the source URL and loads it in its raw form 
     * into _dataBody
     * 
     * Can be called directly
     * 
     * @return string $this->_dataBody
     */
    public function getEngardePage()
    {
        if ($this->_URLexists($this->_urlInFull)) {
            $this->_dataBody = file_get_contents($this->_urlInFull);
            if ($this->_dataBody === false) {
                $this->_handleError('Unable to read data from supplied URL.');
            }
        } else {
            $this->_handleError('Unable to locate supplied URL');
        }
        return $this->_dataBody;
    } 

    /**
     * Simple error handler to display message when something doesn't
     * go to plan and stop executing the script!
     * 
     * @param string $inError message indicating the error
     * 
     * @return none
     */
    private function _handleError($inError)
    {
        die($inError);
    }
}

class EngardeFormatter extends EngardeParser
{
    public function getAllResultsCSV()
    {
        $allResultsArray = $this->getAllResults();

        // Handles names and clubs with accents correctly
        header('Content-Type: text/html; charset=utf-8');

        // If UTF8 encoding being used, you may have to manually select
        // that when opening the CSV file in your CSV reader
        header("Content-Disposition: attachment; filename=engarde.csv");
        header("Content-Type: application/vnd.ms-excel");
            
        $fp = fopen('php://output', 'w');
        fputcsv($fp, array('Rank', 'Surname', 'Forename', 'Club', 'Country'));
        foreach ($allResultsArray as $value) {
            fputcsv($fp, $value);
        }
        fclose($fp);
    }
}

$e = new EngardeFormatter('http://www.engarde-service.com/files/leonpaulfencingcentre/lpjslonsabre18/u13_girls/clasfinal.htm');

$e->getAllResultsCSV();

// $e = new EngardeParser('https://web.archive.org/web/20050413025312/http://www.britishfencing.com/Men%27s%20epee.htm');
/* $headerInfo = $e->parseHeaderBlock();

/*print "<h3>Title</h3>";
print $e->parseTitle();

print "<h3>Meta Data</h3>";
$meta = $e->getMetaData();
print "Generator : ".$meta['Generator']."<BR>";
print "ProgId : ".$meta['ProgId']."<BR>";
print "Originator : ".$meta['Originator']."<BR>";

print "<h3>Header</h3>";
for ($i=0; $i<count($headerInfo); $i++) {
    print $headerInfo[$i]."<br>";
}

print_r($e->getAllResults()); */

exit;

?>