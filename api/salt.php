<?php
set_include_path ('/home/httpd/api/common/');
require_once 'xmlserialize.cls.php';
require_once 'load.php';

  //$isbn = trim(preg_replace('/(\W*)/', '', $_GET['isbn']))|trim(preg_replace('/(\W*)/', '', $_POST['isbn']));
  
  $isbn = trim($_GET['isbn'])|trim($_POST['isbn']);
  $format = trim($_GET['format'])|trim($_POST['format']);
  $threshold = trim($_GET['threshold'])|trim($_POST['threshold']);
 
  //if (!$threshold | $threshold=='undefined') {
  //  
  //  $threshold = 15;
  //}
  
  if ($isbn=='') {
    // Return 404
    print "<p>requires isbn parameter to work - full docs here (substitute base URI) http://salt11.wordpress.com/salt-recommender-api/</p>";
    
  } else {
      $saltURL = "http://vm-salt.mimas.ac.uk/getSuggestions.api?isbn=" . $isbn . "&format=" .$format . "&threshold=" . $threshold;

   $options = array(
	       'return_info'	=>false,
	       'method'=> 'get'
	   );
    $response = load($saltURL, $options);
    
    if ($format='json') {
    header("content-type:text/json");
      
     // Else as format is optional, XKL default   
    }else{
    header("Content-Type: text/xml");
    }
   print $response;
  }
?>