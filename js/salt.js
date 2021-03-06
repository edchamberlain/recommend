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

function showCopacCitation(isbnInput) {
$('#copacResults').show();
$('#sideLoader').show();
$('#copacResults').load('/recommend/api/copacRequest.php',{input: isbnInput, index: 'isbn', format: "html", mode: "citation"});
$('#sideLoader').hide();
}
////////////////////////////////

function showCover(isbnInput) {
  var coverURL = 'http://covers.openlibrary.org/b/isbn/' + isbnInput + '-M.jpg';
  // Check for 404 first ...
  $('#coverResults').show();
  $('#coverResults').html('<p>Showing recommendations based upon:</p><img src = "'+coverURL+'" />');
}
//////////////////////////////////////////

// Current bugs - not all clicks detected
// Solve image problem

function  startCarousel() {
//////////// Start Carousel ////////////
	 $('ul#myRoundabout').roundabout({
				easing: 'easeInOutBack',
				duration: 1000
				});
	        
	 	$('#myRoundabout li').focus(function() {
			var useText = $(this).attr('alt');
			var useisbn = $(this).attr('id');
				$('#coverCaption').html('<h4>' + useText + '</h4><p>('+ useisbn +') - <a href="http://search.lib.cam.ac.uk/?q=isbn:'+ useisbn + '">check catalogue</a> - <a href="/recommend/?isbn='+ useisbn + '">Browse on...</a>').fadeIn(200);
			});
//////////////////////////////////////////
}

// Takes ISBN + threshold and queries local bounce of API feed - returns chunk of XML
function getSalt(isbnInput,threshold,limit) {
     
  // Clear div elements   
     $('#list').html('');
     $('#imageWall').html('');
     $('ul#myRoundabout').html('');
     $('#list').append('<p><b>Recommendations as a list:</b></p>')
     
      
      var i=1;
      var output ='';
      
      var saltURL = "/recommend/api/salt.php?isbn=" + isbnInput + "&threshold=" + threshold + "&format=xml";               
      var origURL = "http://vm-salt.mimas.ac.uk/getSuggestions.api?isbn=" + isbnInput + "&threshold=" + threshold + "&format=xml";
      
     $('#debug1').html('<p>Original request URI:<br/> <a href="'+ origURL +'">' + origURL + '</a></p>');
      
     //    
        $.get(saltURL, {}, function (results_xml) {
          
	  $('#loader').show();
	   
   //   $('#debug3').html('<pre>' + results_xml + '</pre>');
              showCopacCitation(isbnInput);
	      showCover(isbnInput);
	      $('#list').append('<ul>');
	     
	
	          // Error handling ...
	     var comment = $(results_xml).find("Comment").text();
	     var error = $(results_xml).find("error").text();
	     
	      if (error) {
		$('#loader').hide();
	          $('#prompt').html('<p><b>Error - ISBN probably not in SALT database yet.</b></p>');
		  $('#prompt').append('<p><b>' + error + '</b></p>');
		  $('#results').hide();
	      }
	      else if (comment)	{
		$('#loader').hide();
		  $('#prompt').html('<p><b>No recommendations returned, try altering threshold.</b></p>');
		  ('#debug1').append('<p><b>' + comment + '</b></p>');
		  $('#results').hide();
	     
	     // We are go ...
	     }else {
		$('#loader').hide();
                $('item', results_xml).each(function(result_xml) {  
		    var isbn = $(this).find("isbn").text();
                    var citation = $(this).find("citation").text();
		    citation = citation.replace(/"/ig, "");
                    var workID = $(this).find("workID").text();
                    var ranking = $(this).find("ranking").text();
		    
		    // Draw results ...
		    // http://covers.openlibrary.org/b/isbn/9780385533225-S.jpg
			$('ul#myRoundabout').append('<li id="'+ isbn +'" alt="'+ citation +'" style="background-repeat: no-repeat; background-image: url(\'http://covers.openlibrary.org/b/isbn/' + isbn + '-M.jpg\')"></li>');  
			  
			$('#list').append('<li>'+ ranking + '.) <a href="/recommend/?isbn='+ isbn + '">'+ citation +'</a> ('+ isbn +') - <a href="http://search.lib.cam.ac.uk/?q=isbn:'+ isbn + '">check catalogue</a></li>');
			
			$('#imageWall').append('<a href="http://search.lib.cam.ac.uk/?q=isbn:'+ isbn + '"><img class="cover" src="http://covers.openlibrary.org/b/isbn/' + isbn + '-M.jpg" /></a>');
		
		   // Needlessly verboose limit thing?
			if(i >= limit) {
			return false;
			} else {
			i++;
			return true;
			}
		
		});
		
		 $('#list').append('</ul>');
         // Seems to only run here when div elements are drawn
	  startCarousel();
          
         } // end else
   });
     
}

/////////////////////////////
$(document).ready(function() {
	//$('#loader').hide();
	$('#sideLoader').hide();
	$('#loader').hide();
	$('#results').hide();
	$('#rightCol').hide();
	
			
	// Load SALT data as image URL's into document first before initiating demo ...
	var isbnInputParam = gup('isbn');

	//$('#debug1').html('isbn: ' + isbnInput + ' - threshold: ' + threshold);
		
	// Check for URL parameters 
	if (isbnInputParam) {
	// Must be a neater way to do this in JS?	
	var thresholdParam = gup('threshold');
	var limitParam = gup('limit');	
			
		if (thresholdParam ==='') {
		thresholdParam = 15;
		}	
		
		if (limitParam==='') {
		limitParam = 10;
		}
			
	$("#isbnInput").val(isbnInputParam);
	
	getSalt(isbnInputParam,thresholdParam,limitParam);
	 $('#rightCol').show();
	 $('#results').show();
	}
		
		
	// Else monitor for form based submission ...
	$("#bigSubmit").click(function(event) {
	
		event.preventDefault();
		var isbnInput=$("#isbnInput").val();
		var thresholdInput=$("#threshold").val();
		var limitInput=$("#limit").val();
		// Remove hypens for UL compatability	
		if (isbnInput){
			$('#prompt').html('');
			getSalt(isbnInput,thresholdInput,limitInput);
			$("#isbnInput").focus();
                         $('#results').show();
			 $('#rightCol').show();
			
		} else {
		$('#prompt').html('<p>Please enter an ISBN!</p>');
		 }

       });
});
