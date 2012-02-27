<?php


set_include_path ('common');
require_once 'xmlserialize.cls.php';
require_once 'load.php';

/* Remove for debug */
error_reporting(E_ERROR | E_WARNING | E_PARSE);
xdebug_disable();

  $isbn = trim($_GET['isbn'])|trim($_POST['isbn']) or $isbn='';
  $format = trim($_GET['format'])|trim($_POST['format']) or $format='xml';
  $threshold = trim($_GET['threshold'])|trim($_POST['threshold']) or $threshold=15;

  if ($isbn=='') {
    // Return 404?
    print "<p>requires isbn parameter to work - full docs here (substitute base URI) http://salt11.wordpress.com/salt-recommender-api/</p>";
    
  } else {
      $saltURL = "http://vm-salt.mimas.ac.uk/getSuggestions.api?isbn=" . $isbn . "&format=" .$format . "&threshold=" . $threshold;

   $options = array(
	       'return_info'	=>false,
	       'method'=> 'get'
	   );
    $response = load($saltURL, $options);
    
    if ($format='json') {
    echo header("content-type:text/json");
      
     // Else as format is optional, XKL default   
    }else{
     echo header('Content-type: text/xml');
    }
   print $response;
  }
?>