<?php
set_include_path ('common');
require_once 'badgerFish.php';
require_once 'load.php';

/* Remove for debug */
error_reporting(E_ERROR | E_WARNING | E_PARSE);
xdebug_disable();


# Cross domain wrapper for COPAC's SRU/SRW service. Takes request, interrogates COPAC and returns Mods record with JSON/ XML and HTML options. Citation mode simply returns bib data no holdings.

# Breaks on this record 0415040272
# Needs holdings attributes added

# Ed Chamberlaim, Cambridge Univeristy Library 2009
#72004153220
 $index= trim($_GET['index'])|trim($_POST['index']) or $index='';
 $input =trim($_GET['input'])|trim($_POST['input'])or $input ='';
 $queryString=trim($_GET['queryString'])|trim($_POST['queryString']) or $queryString='';
 $format = trim($_GET['format'])|trim($_POST['format']) or $format ='';
 $maxrecords = $_GET['maxrecords']|$_POST['maxrecords'] or $maxrecords = 20;
 $mode =trim($_GET['mode'])|trim($_POST['mode']) or $mode ='';
 
 if ($index =='isbn'| $index=='issn') {
 $input = trim(preg_replace('/(\W*)/', '', $_GET['input']))|trim(preg_replace('/(\W*)/', '', $_POST['input']));    
 }
 
if ($input && $index && $format || $queryString && $format){
     # $isbn= thingISBNCheck($_GET['isbn']);
     copacRetreive($input, $index, $format,$maxrecords, $queryString , $mode);
}         
else {
   showForm();        
   }
   
    
#####################################
function copacRetreive($input, $index, $format, $maxrecords, $queryString, $mode) {

   if (!$maxrecords) {
       $maxrecords = 20;
   }
            
// Formulate request URL
   $requestUrl =  'http://copac.ac.uk:3000/copac?operation=searchRetrieve&version=1.1';
   
 $requestUrl =  'http://copac.ac.uk:3000/copac?operation=searchRetrieve&version=1.1';
   
   
   if ($queryString) {
     //queryString
     $requestUrl .=  '&query=' .  urlencode($queryString);
   } else {
	   if ($index =='isbn') {
	       $requestUrl .=  '&query=dc.resourceIdentifier="' . urlencode($input) . urlencode('" or bath.isbn="') . urlencode($input) . urlencode('"');
	       }
	       elseif ($index =='issn') {
	       $requestUrl .=  '&query=dc.resourceIdentifier="' . urlencode($input) . urlencode('" or bath.issn="') . urlencode($input) . urlencode('"');
	       } else {
	      $requestUrl .=  '&query=' . urlencode($index). '="' . urlencode($input) . urlencode('"'); 
	       }     
   }
   $requestUrl .= '&maximumRecords=' .  urlencode($maxrecords) . '&recordSchema=mods';
  
  // print $requestUrl;
//Check URL   
   isValidUrl($requestUrl) or die('Cannot read URL: '. $requestUrl );
   
// Load 
     $options = array(
	'return_info'	=>false,
	'method'=> 'get'
    );
    $xmlStringContents= load($requestUrl,$options);
    // echo $xmlStringContents;
     
     
// Generate JSON error message
   $checkObject=simplexml_load_string($xmlStringContents);
   $checkResponse = $checkObject->children('http://www.loc.gov/zing/srw/');
    // Inital check necessary for JSON as SRU error messages are stripped by JSON coversion later ...)
   if ($checkResponse->numberOfRecords==0) {
    $checkMessage = $checkObject->children('http://www.loc.gov/zing/srw/')->diagnostics->diagnostic ;       
   $JSONError = "{\"error\":{\"message\": \"0 records returned from COPAC for: $isbn - COPAC returns the following: $checkMessage\"}}";            
   }         
            
// clear check objects         
   $checkObject='';
   $checkResponse='';
   
   
// Output according to format choice ...     
     if($format=='xml') {
     echo header('Content-type: text/xml').$xmlStringContents;         

     }elseif ($format=='json') {   
            $dom = new DOMDocument;
            $dom->loadXML($xmlStringContents);
            $jsonContents = BadgerFish::encode($dom);
            
            if ($JSONError) {
            echo ($JSONError);                   
                   } else {           
                echo($jsonContents);
                }
                
    }  elseif ($format=='html') {
     // Image and link
            
	    $dom = new DOMDocument;
            $dom->loadXML($xmlStringContents);
	    
	    if ($mode=='citation') {
	       //$htmlContent .="<li><b>" .$titleInfo  . '</b>. ' . $authorInfo . " " . $publisherInfo . " (" . $dateIssued . ") ". $edition . "<ul>";
             $htmlContent='<ul>';
	       
	    } else {
	    $htmlContent='<a href="http://copac.ac.uk/wzgw?form=A%2FT&au=&ti=&pub=&sub=&any=&fs=Search&date=&plp=&isn=' . $input .'"><img src="http://copac.ac.uk/img/85x67_copac.gif" alt="copac" /></a><p>Holdings information from <b><a href="http://copac.ac.uk/wzgw?form=A%2FT&au=&ti=&pub=&sub=&any=&fs=Search&date=&plp=&isn=' . $input .'"> COPAC - view in full</a>. </b> (Access may be restricted from some catalogue terminals):</p><ul>';
	    }
	    
            
            if (!$dom->getElementsByTagName("numberOfRecords")->item(0)->nodeValue){
            $htmlContent .= "<li>No records found in COPAC for this ISBN</li>";            
            }
            
            $mods = $dom->getElementsByTagName("mods");
            
            foreach ($mods as $mod) {                      
            $titleInfo = $mod->getElementsByTagName( "title" )->item(0)->nodeValue;           
            $authorInfo = $mod->getElementsByTagName( "note" )->item(0)->nodeValue;
            $publisherInfo = $mod->getElementsByTagName( "publisher" )->item(0)->nodeValue;
            $dateIssued = $mod->getElementsByTagName( "dateIssued" )->item(0)->nodeValue;
            $edition = $mod->getElementsByTagName( "edition" )->item(0)->nodeValue;
            
	    
	    
            $htmlContent .="<li><b>" .$titleInfo  . '</b>. ' . $authorInfo . " " . $publisherInfo . " (" . $dateIssued . ") ". $edition;
            
	    
	    if ($mode=='citation') {
	       $htmlContent .="</li>"; 
	    } else {
	        $holds = $mod->getElementsByTagNameNS( "http://copac.ac.uk/schemas/holdings/v1","localHolds" );
                $htmlContent .= "<ul>";
               foreach ($holds as $hold) {
               $org = $hold->getElementsByTagName( "org" )->item(0);
               $name = $org->getAttribute("displayName");
               $htmlContent .= "<li><b>" . $name . "</b>. <span>"; 
               $items= $hold->getElementsByTagName( "item");
                        foreach ($items as $item) {
                        $location=$item->getElementsByTagName( "loc" )->item(0);
                        $location=$location->getAttribute("displayName");
                        $shelfmark=$item->getElementsByTagName( "shelfmark" )->item(0)->nodeValue;
                        $htmlContent .= $location . ", " . $shelfmark;
                        }
                        $htmlContent .="</span></li>";
               } 
	      $htmlContent .=" </ul></li> ";
	    }
	     
          
         
            
        $htmlContent .= "</ul>";
	
	 }
	
        echo($htmlContent);    
        }
        

    } # End Function
    


function showForm()  {
            
print<<<_HTML_

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head> 
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>CUL COPAC Mods to JSON service</title>
</head>
<body>
<h1>CUL COPAC Mods to JSON service</h1>
<p>
<form method="get" id="form" method="get" target="copacRequest2.php">

            <label for="isbn">Enter an value to search for (param=input):</label>
            <input name="input" id="input" type="text" /><br/>
	    
	  <!--  Choose identifiers (param=index): <br/>
	    <input type="radio" id="index" name="index" value="isbn"><label for="format">ISBN</label>
            <br>
            <input type="radio" name="index" id="index"  value="issn"><label for="format">ISSN</label><br/> -->
	    
	    Wnter an index (see list below):<br/>
	    <input type="text" id="index" name="index" /> <br/>
<br/>
	   
	   OR enter an evil CQL querystring (e.g. 'dc.title=cats or dc.subject=cats') - http://www.loc.gov/standards/sru/specs/cql.html<br/>
	    <input type="text" id="queryString" name="queryString" /> <br/>
	   <hr/>
            Choose format of response (param=format): <br/>
            <input type="radio" id="format" name="format" value="xml"><label for="format">XML</label>
            <br>
            <input type="radio" name="format" id="format" value="json"><label for="format">JSON</label>
<br/>
            <input type="radio" name="format" id="format" value="html"><label for="format">HTML</label><br/>
            <input id="submit" type="submit" value="Check COPAC for this ISBN" />
   
    </form>
    
    
</p>
<h3>Supported indexes</h3>
<ul>
<li>isbn  - check multiple SRU fields for ISBN (prefered mode) </li>
<li>issn - check multiple SRU fields for ISSN  (prefered mode) <</li> 
<li>rec.id </li> 
<li>dc.title </li> 
<li>dc.subject</li> 
<li>dc.creator</li> 
<li>dc.author </li> 
<li>dc.editor </li> 
<li>dc.publisher 	</li> 
<li>dc.description 	</li> 
<li>dc.date 	</li> 
<li>dc.resourceType </li> 
<li>dc.format 	</li> 
<li>dc.resourceIdentifier </li> 	
<li>dc.language</li> 	
</ul>

<h3>Things to note:</h3>
<ul>
<li>Searches COPAC for ISBN using  both 'dc.resourceIdentifier' and 'bath.isbn'. This was about as much CQL as I could handle</li>
<li>Returns up to 20 records by default. If you want more (or less), add an extra 'maxrecords' parameter to the URL</li>
<li>XML format returns the complete XML from Copac. This is SRW, MODS and a local holdings format</lI>
<li>JSON returns the Mods data and holdings data only. Any Error messages can be collected using error -> message</li>
<li>UPDATE: New HTML format returns nested lists of barebones bib details, holdings and items</li>
<li><del>XML to JSON conversion done using a <a href="http://www.ibm.com/developerworks/xml/library/x-xml2jsonphp/">rather neat library from IBM</del></a><br/>
UPDATE: - This was causing certain attributes to disappear and some records to not render at all if they did not match an obvious pattern. Now using the <a href="http://badgerfish.ning.com/">Badgerfish convention</a>. Returns complete if slightly bloated JSON</li>
<li>All data is provided by curl from COPAC via its' SRU gateway, and subject to their terms and conditions</li>
<li>Test web service by Ed Chamberlain, <a href="http://www.lib.cam.ac.uk/">Cambridge University Library 2008</a></li>
<li>This service is not provided by Curl or connected to COPAC in anyway (other than HTTP) ;)</li>
</ul>

</p>


</body>
</html>

_HTML_;
}


# Uses Library' Things wonderful API - not currently implemented. Maybe save for seperate service or 
function thingISBNCheck($isbn) {
           
           # Maybe not needed, Library Thing has this covered ...
            $isbn = trim(preg_replace('/(\W*)/', '', $isbn));

            $requestURL= 'http://www.librarything.com/isbncheck.php?isbn=' . urlencode($isbn);
            #http://www.librarything.com/isbncheck.php?isbn=0765344629
            
             #isValidUrl($requestUrl) or die('Cannot read ISBN REQUEST URL: '. $requestUrl );
             $xmlStringContentsThing = file_get_contents($requestUrl);
             $parsedXml = simplexml_load_string($xmlStringContentsThing);
             
             $isbn10 = $parsedXml->response->isbn10;
             $isbn13 = $parsedXml->response->isbn13;
             
             # Arbitrary behaviour ...
             if ($isbn>10) {
                 return $isbn13;
             } else {
                 return $isbn10;
             }           
             
}



?> 


