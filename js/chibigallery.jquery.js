/* 
  Chibi Gallery, a jQuery plugin gallery
  Made by Rasmus Berg (c) 2016
  License under MIT license
*/

'use strict';

$(document).ready(function(){
  
  console.log("Start running");
  
  (function($) {
    $.fn.chibiGallery = function(options) {
      options = $.extend({}, $.fn.chibiGallery.defaults, options);
      return this.each(function() {
        var gallery     = $(this);
        var current     = gallery.find('.gallery-current');
        var current_img = current.find('img');
        var current_txt = gallery.find('.gallery-current-text');
        var gallery_all = gallery.find('.gallery-all');
        var imgs        = gallery_all.find('img');
        var selected    = gallery_all.find('.selected');
        var playNav     = gallery.find('.play');
        var pauseNav    = gallery.find('.pause');
        var playId;
        
        // Function to load new image as current image
        var loadImage = function(imgIndex = 0){
          var length = imgs.length - 1;
          
          // If gallery is visible use loading msg
          if(gallery.is(':visible')){
            current_txt.text('Loading...');
          }
          
          // Img index need to be over zero and under thumbnails length
          if(imgIndex < 0)
            imgIndex = length;
          else if(imgIndex > length)
            imgIndex = 0;
          
          // Get thumbnail element for img to load
          var thumbEl = imgs.eq(imgIndex);
          var src = thumbEl.attr('src').split('?')[0] + '?w=' + current.width() + '&h=' + current.height(); // Prepare src to img
          
          // Preload image
          $('<img>').attr('src', src).load();
          
          // Fadeout current img
          current_img.fadeOut('slow', function() {
            // If lightbox is active, set title and cursor for hover-effect
            if(options.lightbox){
              current_img
              .attr('title', 'Click here to see orginal picture in a lightbox.')
              .css('cursor', 'pointer');
            }
            
            // Prepare new current img and fade in
            current_img
            .attr('src', src)
            .data('index', imgIndex)
            .fadeIn();
            
            // Set current img text by tumbnails title
            current_txt.text(thumbEl.attr('title'));
            
            // Remove class from old selected thumnail and set on new one
            selected.removeClass('selected');
            thumbEl.addClass('selected');
            selected = thumbEl;
          });
          
          console.log('New image loaded in gallery: ' + src);
        }
        
        // Function to rotate images backward
        var lastImage = function() {
          var selected = current_img.data('index');
          
          loadImage(selected - 1);
        };

        // Function to rotate images backward
        var nextImage = function() {
          var selected = current_img.data('index');
          
          loadImage(selected + 1);
        };
        
        // If lightbox is activated and current img is clicked, show lightbox with current orginal img in it
        if(options.lightbox){
          current_img.click(function(){
            
            // Get window size
            var windowHeigth = window.innerHeight || $(window).height(); // for ipad & android
            var windowWidth  = window.innerWidth  || $(window).width();
            
            // Display the overlay
            var over = $('<div id="gallery_overlay"></div>')
            .attr('title', 'Click to close')
            .animate({'opacity' : '0.75'}, 'slow')
            .appendTo('body');
            
            // Create the lightbox container
            var light = $('<div id="gallery_lightbox"></div>')
            .appendTo('body');
            
            // Display the image on load
            $('<img>')
            .attr('src', selected.data('src'))
            .css({
              'max-height': windowHeigth, 
              'max-width':  windowWidth
            })
            .load(function() {
                light
                .css({
                  'top':  (windowHeigth - light.height()) / 2,
                  'left': (windowWidth  - light.width())  / 2
                })
                .fadeIn();
            })
            .appendTo(light);
              
            // Remove it all on click
            over.click(function() {
              light.fadeOut('slow', function(){
                light.remove();
              });
              over.fadeOut('slow', function(){
                over.remove();
              });
            });
            
            console.log("Display image in lightbox.");
          });
        }
        
        // When thumbnail is clicked
        imgs.click(function(){
          loadImage(imgs.index(this));
          
          console.log("Click on thumbnail, showing new image.");
        });
        
        // When last arrow is clicked
        gallery.find('.last').click(function(){
          lastImage();
          console.log("Clicked to move backwards one image image in gallery.");
        });
        
        // When next arrow is clicked
        gallery.find('.next').click(function(){
          nextImage();
          console.log("Clicked to move foward to next image in gallery.");
        });
        
        // If keyboard arrow keys is activated to use as navigation in gallery check for clicks on them
        if(options.keyboard){
          // When left arrow key on keyboard is press
          $(window).keyup(function(event){
            if(event.which == 37){
              event.preventDefault();
              lastImage();
              console.log("Left arrow key on keyboard is press, move backwards one image image in gallery.");
            }
          });
          
          // When right arrow key on keyboard is press
          $(window).keyup(function(event){
            if(event.which == 39){
              event.preventDefault();
              nextImage();
              console.log("Right arrow key on keyboard is press, move foward to next image in gallery.");
            }
          });
        }
        
        // When slideshow is activated
        if(options.slideshow){
          // When play arrow is clicked, set interval
          playNav.click(function(){
            playId = setInterval(nextImage, options.delay);
            playNav.hide(0);
            pauseNav.css('display', 'inline-block').show();
            console.log("Clicked to play slideshow.");
          });
          
          // When pause is clicked, remove interval
          pauseNav.click(function() {
            clearInterval(playId);
            pauseNav.hide(0);
            playNav.show();
            console.log("Clicked to pause slideshow.");
          });
          
          // When autoplay is activated, trigger click on play arrow
          if(options.autoplay)
            playNav.trigger('click');
        }
        // If slideshow if deactivate hide where the buttons 
        else{
          gallery.find('.gallery-action').hide(0);
        }
        
        // Load selected thumbnail as current img
        loadImage(selected.prevAll().length);
      });
    };

    $.fn.chibiGallery.defaults = {
      slideshow: true,    // Activate or deactivate slideshow
      autoplay: false,    // Activate or deactivate autoplay
      delay: 4000,        // Delay slideshow interval (milliseconds)
      lightbox: true,     // Activate or deactivate lightbox
      keyboard: false     // Activate or deactivate use of left and right keys on keyboard to navigate between images
    }
  
    console.log('Added function chibiGallery() to jQuery object as plugin.');
  }) (jQuery);
  
  console.log('Everything is ready.');
});