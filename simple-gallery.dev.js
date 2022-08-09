/* Newly added code -START */
jQuery.browser = {};
(function () {
  jQuery.browser.msie = false;
  jQuery.browser.version = 0;
  if (navigator.userAgent.match(/MSIE ([0-9]+)\./)) {
    jQuery.browser.msie = true;
    jQuery.browser.version = RegExp.$1;
  }
})();
/* Newly added code -END */

jQuery.noConflict();

jQuery(document).ready(function($){
	// Register each image gallery on the page
	var galleries = $("div.simplegallery");
	var numGalleries = galleries.size();

	for (i = 0; i < numGalleries; i++) {
	    simplegallery($(galleries[i]).attr('id'));
	}

	function simplegallery(id) {
	  // The CSS selector for the current gallery, based on the id passed to this function
	  var selector = "#" + id;

	  var index = 0;
	  var thumbs = $(selector + " .thumbnails dt a");
	  var thumbnailcontainer = $(selector + " .thumbnails");
	  var largeimagecontainer = $(selector + " .largeimage");
	  var large = $(largeimagecontainer).children('a').children("img");
	  var caption = $(largeimagecontainer).children("h3");

	  var numThumbs = thumbs.size();

	  var current = thumbs[index];

	  // Remove the class names from all thumbnails and the large image
	  // This ensures the shutter reloaded effect doesn't automatically apply to the images in our gallery (because they have the shutterset class)
	  $(thumbs).attr('class', '');
	  debug(selector + ': thumbnail classes removed');
	  $(largeimagecontainer).children('a').attr('class', '');
	  debug(selector + ': large image classes removed: ' + $(largeimagecontainer).children('a').attr('class'));

	  function showImg(img) {
	    debug(selector + ': img showImg() called for thumbnail <a> with href ' + $(img).attr('href'));

	    if ( $(img).attr('href') == $(large).attr('src') ) {
		// Trying to display the image that is already being displayed so don't do anything
		return;
	    }

	    if (gallerySettings[id]['fade'] == 'out-in' || gallerySettings[id]['fade'] == 'over') {
		$(large).hide();
		$(largeimagecontainer).addClass('loading');
	    }

	    $(large)
	    	.attr("src", $(img).attr("href"))
	    	.attr("title", $(img).attr("title"))
	    	.attr("alt", $(img).attr("title"))
	    ;

	    var captionHTML = $.trim($(img).parent().parent().children("dd").html());

	    // Variable to store the onresize event later
	    var resize = null;

	    // try and display the caption
	    $(caption).html(captionHTML);

	    // Update the large image link to link directly to the file
	    $(large).parent().attr("href", $(img).attr("href"));

	    // Check if shutter reloaded plugin is present
	    if("object" == typeof shutterReloaded) {
	    	// Display the large image in the shutter reloaded lightbox style
		    $(large).parent().click( function() {
		        shutterLinks['simpleviewer'] = null;
		        shutterLinks['simpleviewer'] = {link:$(img).attr("href"),num:-1,set:0,title:captionHTML}
		        shutterReloaded.make('simpleviewer');

		        // Remove the window.onresize event that Shutter Reloaded adds in order to prevent strange behavior with IE8 (see Bug #916)
		        $(window).attr('onresize', '');
		        return false;
	        });
	    }

	    // Add the "current" class to the thumbnail that was just clicked/displayed
	    $(thumbs).removeClass('current');
	    $(img).addClass('current');

	    updateNavigation();

	  }

	  $(thumbs).click( function() {
	    // Update the index
	    for (i = 0; i < numThumbs; i++) {
	      if (thumbs[i] == this) {
	        index = i;
	        break;
	      }
	    }
	    showImg($(this));
	    return false;
	  } );

	  // Large image loading
	  $(large).load(function(){

	    debug(selector + ': img load() called for img ' + $(this).attr('src'));

	    // IE6 doesn't support max-width and max-height, so it must be done in JS instead
	    var resize = false;
	    if ($.browser.msie && $.browser.version.indexOf('6.0') == 0) {
	      resize = true;
	    }

	    //resize = true;

	    if (resize) {

	      // Reset width and height
	      $(large).width('');
	      $(large).height('');

	      var src = $(large).attr('src');

	      var constraint = 'width';
	      var size = parseInt($(large).width());
	      var cssMaxSize = $(large).css('max-' + constraint);

	      var cssRatio = parseInt(cssMaxSize) / parseInt($(large).css('max-height'));
	      var imageRatio = size / parseInt($(large).height());

	      debug(selector + ': ' + src + ': css ratio ' + cssRatio + ' img ratio ' + imageRatio);

	      if (parseInt($(large).height()) > size) {
	        constraint = 'height';
	        size = parseInt($(large).height());
	        cssMaxSize = $(large).css('max-height');
	      }

	      debug(selector + ': ' + src + ': constraint is ' + constraint);

	      if (cssMaxSize != "" && cssMaxSize.indexOf('px') > -1) {
	         cssMaxSize = parseInt(cssMaxSize);

	         //debug(src + ': css max-' + constraint + ' is set to: ' + cssMaxSize);

	         if (size > cssMaxSize) {
	            // adjust the width/height
	            if (constraint == 'height') {
	              $(large).height(cssMaxSize);
	            } else if (constraint == 'width') {
	              $(large).width(cssMaxSize);
	            }
	            debug(selector + ': ' + src + ': ' + constraint + ' of changed from ' + size + ' down to ' + cssMaxSize);
	         } else {
	            debug(selector + ': ' + src + ': no need to shrink. ' + constraint + ' already ' + size);
	         }
	      }
	    }


	    debug(selector + ': image W x H:  ' + $(large).width() + ' x ' + $(large).height());


	    // Slowly fade the image in. Remove the loading graphic when finished
	    if (gallerySettings[id]['fade'] == 'out-in' || gallerySettings[id]['fade'] == 'over') {
		$(large).fadeIn(gallerySettings[id]['fadespeed'], function() {
		    $(largeimagecontainer).removeClass('loading');
		    setBgImage();
		} );
	    }

	  });

	  function setBgImage() {
	      // If fade="over", set the background image to the current image. This gives the effect of the next image fading "over the top" of the current image
	      if (gallerySettings[id]['fade'] != 'over') return;
	      debug(selector + ': set background image to ' + $(large).attr('src'));
	      $(largeimagecontainer).css('background-image', 'url(' + $(large).attr('src') + ')');
	  }

	  /* Next/Previous Keyboard Navigation */
	  // http://stackoverflow.com/questions/492865/jquery-keypress-event-not-firing
	  function checkKey(e){
	   switch (e.keyCode) {
	      case 40:
	      case 39:
	          // down or right
	          showNext();
	          break;
	      case 38:
	      case 37:
	          // up or left
	          showPrevious();
	          break;
	      }
	  }

	  /* Display the next image (used for keyboard and arrow navigation) */
	  function showNext() {
	    if (hasNext()) {
	      index++;
	      showImg(thumbs[index]);
	    }
	  }

	  /* Display the previous image (used for keyboard and arrow navigation) */
	  function showPrevious() {
	    if (hasPrevious()) {
	      index--;
	      showImg(thumbs[index]);
	    }
	  }

	  function hasNext() {
	    return (index < numThumbs-1);
	  }

	  function hasPrevious() {
	    return (index > 0);
	  }

	  function updateNavigation() {
	    if (hasPrevious()) {
	      //debug('previous = true');
	      $(selector + " .simplegalleryprev").addClass('enabled');
	      $(selector + " .simplegalleryprev").removeClass('disabled');
	    } else {
	      //debug('previous = false');
	      $(selector + " .simplegalleryprev").addClass('disabled');
	      $(selector + " .simplegalleryprev").removeClass('enabled');
	    }

	    if (hasNext()) {
	      //debug('next = true');
	      $(selector + " .simplegallerynext").addClass('enabled');
	      $(selector + " .simplegallerynext").removeClass('disabled');
	    } else {
	      //debug('next = false');
	      $(selector + " .simplegallerynext").addClass('disabled');
	      $(selector + " .simplegallerynext").removeClass('enabled');
	    }

	    // Update the image number
	    $(selector + " .simplegalleryimagenumber").html(index + 1);
	  }

	  // Display the first large image
	  showImg(thumbs[0]);

	  // Set up the navigation bar
	  $(selector + " .simplegallerynavbar").append('<ul>');
	  $(selector + " .simplegallerynavbar ul")
	    .append('<li class="simplegalleryprev disabled"><a href="javascript:void();" title="Previous"><span>&larr;</span></a></li>')
	    .append('<li class="simplegalleryimagenumbers"><span class="simplegalleryimagenumber">1</span> / <span class="simplegallerytotalimages">' + numThumbs + '</span></li>')
	    .append('<li class="simplegallerynext enabled"><a href="javascript:void();" title="Next"><span>&rarr;</span></a></li>')
	    .append('</ul>')
	    ;

	  // Previous button click handler
	  $(selector + " .simplegalleryprev").click( function() {
	    if ($(this).hasClass('disabled')) return false;
	    showPrevious();
	    return false;
	  } );

	  // Next button click handler
	  $(selector + " .simplegallerynext").click( function() {
	    if ($(this).hasClass('disabled')) return false;
	    showNext();
	    return false;
	  } );

	  if ($.browser.mozilla) {
	      $(document).keypress(checkKey);
	  } else {
	      $(document).keydown(checkKey);
	  }

		// Thumbnail scrolling functionality
		if ( gallerySettings[id]['thumbnailscroll'] == 1 ) {

			// This gallery supports thumbnail scrolling

			var $item = $(thumbnailcontainer).find('.gallery dl.gallery-item'), //Cache DOM selector
					scrollingIndex = 0, //Starting index
					displayWidth = $(thumbnailcontainer).find('div.gallerywrapper').width() // Width of the are being displayed in the scrolling div
					totalWidth = 0, // total width of all thumbnails in the gallery (calculated below)
					scrollingIndexEnd = 0;  //End index

			$item.each(function() {
			    totalWidth += parseInt($(this).outerWidth(true));
			});

			scrollingIndexEnd = totalWidth / displayWidth - 1;
			if ( scrollingIndexEnd < 0 ) {
				// No navigation required
				scrollingIndexEnd = 0;
			}

			// Set the total width of the inner scrolling div
			$(thumbnailcontainer).find('div.gallerywrapper div.gallery').css('min-width', totalWidth + 'px');

			// Set the height of the next/prev buttons to match the height of the thumbnails scrolling area
			$(thumbnailcontainer).find('.thumbnailscroll').height( $(thumbnailcontainer).height() );

			if ( scrollingIndexEnd > 0 ) {
				// More thumbnails than what will fit on one screen. Enable scrolling functionality

				// "Previous" button click handler
				$(thumbnailcontainer).find('.thumbnailscroll.thumbnailscroll-prev').click(function(){
					if ( scrollingIndex > 0 ) {
						scrollingIndex--;
						simplegallery_thumbnail_navigation_arrows(thumbnailcontainer, scrollingIndex, scrollingIndexEnd);
						$item.animate({'left':'+='+ displayWidth + 'px'}, 'slow');
					}

				});

				// "Next" button click handler
				$(thumbnailcontainer).find('.thumbnailscroll.thumbnailscroll-next').click(function(){
					if ( scrollingIndex < scrollingIndexEnd ) {
						scrollingIndex++;
						simplegallery_thumbnail_navigation_arrows(thumbnailcontainer, scrollingIndex, scrollingIndexEnd);
						$item.animate({'left':'-='+ displayWidth + 'px'}, 'slow');
					}

				});

			}

			simplegallery_thumbnail_navigation_arrows(thumbnailcontainer, scrollingIndex, scrollingIndexEnd);

		}
	}

	/**
	 * Show/hide the thumbnail scrolling next/previous buttons
	 *
	 * @param container
	 * @param idx
	 * @param endIdx
	 */
	function simplegallery_thumbnail_navigation_arrows(container, idx, endIdx) {
		if ( idx == 0 && endIdx > 0 ) {
			// Let the user scroll forwards only
			$(container).find('.thumbnailscroll.thumbnailscroll-prev').css('visibility','hidden');
			$(container).find('.thumbnailscroll.thumbnailscroll-next').css('visibility','visible');
		} else if ( idx == 0 && endIdx == 0 ) {
			// No navigation required
			$(container).find('.thumbnailscroll.thumbnailscroll-prev').css('visibility','hidden');
			$(container).find('.thumbnailscroll.thumbnailscroll-next').css('visibility','hidden');
		} else if ( idx > 0 && idx < endIdx ) {
			// Let the user scroll either way
			$(container).find('.thumbnailscroll.thumbnailscroll-prev').css('visibility','visible');
			$(container).find('.thumbnailscroll.thumbnailscroll-next').css('visibility','visible');
		} else if ( idx > 0 && idx >= endIdx ) {
			// Let the user scroll backwards only
			$(container).find('.thumbnailscroll.thumbnailscroll-prev').css('visibility','visible');
			$(container).find('.thumbnailscroll.thumbnailscroll-next').css('visibility','hidden');
		}
	}
});

function debug(message) {
  if (window.console) {
    console.log(message);
  }
}
