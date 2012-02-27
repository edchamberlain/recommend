// Functions to query Salt API and format results for coverflow
// CSS skeleton fix and acknowledgement
// Spinny wheels
// Cover images from Open Library 

//function to grab parameter from url
	function gup(name) {
		name = name.replace(/[\[]/, "\\\[").replace(/[\]]/, "\\\]");
		var regexS = "[\\?&]" + name + "=([^&#]*)";
		var regex = new RegExp(regexS);
		var results = regex.exec(window.location.href);
		if (results == null) return "";
		else return results[1];
	}
/////////////////////////////////////////

function cleanISBN(isbnInput) {
  // Scrub ISBN (expand regex as we go ... not too literal)
    isbnInput = isbnInput.replace(/[-\s\t]/ig, "");
    return isbnInput;
}
////////////////////////////////
function showCover(isbnInput) {
  var coverURL = 'http://covers.openlibrary.org/b/isbn/' + isbnInput + '-M.jpg';
  // Check for 404 first ...
  $('#coverResults').show();
  $('#coverResults').html('<p>Showing recommendations based upon:</p><img src = "'+coverURL+'" />');
}
//////////////////////////////////////////
// Takes ISBN + threshold and queries local bounce of API feed - returns chunk of XML
function getSalt(isbnInput,threshold,limit) {
     
  // Clear div elements   
     
      
      var i=1;
      var output ='';
      
      var saltURL = "/recommend/api/salt.php?isbn=" + isbnInput + "&threshold=" + threshold + "&format=xml";               
      var origURL = "http://vm-salt.mimas.ac.uk/getSuggestions.api?isbn=" + isbnInput + "&threshold=" + threshold + "&format=xml";
      
     $('#debug1').html('<p>Original request URI:<br/> <a href="'+ origURL +'">' + origURL + '</a></p>');
      
     //    
        $.get(saltURL, {}, function (results_xml) {
          

	   
   //   $('#debug3').html('<pre>' + results_xml + '</pre>');
             // showCopacCitation(isbnInput);
	      //showCover(isbnInput);
	      $('#list').append('<ul>');
	     
	
	          // Error handling ...
	     var comment = $(results_xml).find("Comment").text();
	     var error = $(results_xml).find("error").text();
	     
	      if (error) {
		//$('#loader').hide();
	          $('#prompt').html('<p><b>No recommendations found for this ISBN.</b></p>');
		  $('#prompt').append('<p><b>' + error + '</b></p>');
		  $('#list').hide();
	      }
	      else if (comment)	{
		//$('#loader').hide();
		  $('#prompt').html('<p><b>No recommendations returned.</b></p>');
		  ('#debug1').append('<p><b>' + comment + '</b></p>');
		  $('#list').hide();
	     
	     // We are go ...
	     }else {
		$('#list').html('<h6>Readers who borrowed this book also borrowed:</h6>')
     
		
                $('item', results_xml).each(function(result_xml) {  
		    var isbn = $(this).find("isbn").text();
                    var citation = $(this).find("citation").text();
		    citation = citation.replace(/"/ig, "");
                    var workID = $(this).find("workID").text();
                    var ranking = $(this).find("ranking").text();
		    
		    // Draw results ...
		    // http://covers.openlibrary.org/b/isbn/9780385533225-S.jpg
			//$('ul#myRoundabout').append('<li id="'+ isbn +'" alt="'+ citation +'" style="background-repeat: no-repeat; background-image: url(\'http://covers.openlibrary.org/b/isbn/' + isbn + '-M.jpg\')"></li>');  
			  
			$('#list').append('<li>'+ ranking + '.) <img style="display: inline;" "src="http://covers.openlibrary.org/b/isbn/' + isbn + '-S.jpg "/>' + citation +' - <a target="_top" href="http://search.lib.cam.ac.uk/?q=isbn:'+ isbn + '">check catalogue</a></li>');
			
			//$('#imageWall').append('<a href="http://search.lib.cam.ac.uk/?q=isbn:'+ isbn + '"><img class="cover" src="http://covers.openlibrary.org/b/isbn/' + isbn + '-M.jpg" /></a>');
		
		   // Needlessly verboose limit thing?
			if(i >= limit) {
			return false;
			} else {
			i++;
			return true;
			}
		
		});
		
		 $('#list').append('</ul>');
       
          
         } // end else
   });
     
}

/////////////////////////////
$(document).ready(function() {
	var isbnInputParam = gup('isbn');
	//$('#debug1').html('isbn: ' + isbnInput + ' - threshold: ' + threshold);
	// Check for URL parameters 
	if (isbnInputParam) {
	// Must be a neater way to do this in JS?	
	var thresholdParam = gup('threshold');
	var limitParam = gup('limit');	
			
		if (thresholdParam ==='') {
		thresholdParam = 5;
		}	
		
		if (limitParam==='') {
		limitParam = 10;
		}
			
	$("#isbnInput").val(isbnInputParam);
	
	getSalt(isbnInputParam,thresholdParam,limitParam);
	}	
});
