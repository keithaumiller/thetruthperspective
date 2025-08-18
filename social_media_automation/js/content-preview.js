/**
 * @file
 * JavaScript for Social Media Content Preview functionality.
 */

(function ($, Drupal, drupalSettings) {
  'use strict';

  // Ensure once function is available (Drupal 11 compatibility)
  var onceFunction = window.once || function(id, elements, context) {
    context = context || document;
    if (typeof elements === 'string') {
      elements = context.querySelectorAll(elements);
    }
    
    var results = [];
    var dataAttribute = 'data-once-' + id;
    
    if (elements.length) {
      for (var i = 0; i < elements.length; i++) {
        if (!elements[i].hasAttribute(dataAttribute)) {
          elements[i].setAttribute(dataAttribute, 'true');
          results.push(elements[i]);
        }
      }
    }
    
    return results;
  };

  /**
   * Behavior for content preview interactions.
   */
  Drupal.behaviors.socialMediaContentPreview = {
    attach: function (context, settings) {
      console.log('🎯 Content preview behavior attached to context:', context);
      
      // Use the onceFunction for compatibility
      var bodyElements = onceFunction('social-media-content-preview', 'body', context);
      
      if (bodyElements.length > 0) {
        console.log('✅ Initializing global preview functions');
        
        // Add global functions for preview interactions
        window.generateNewPreview = function() {
          console.log('🔄 generateNewPreview called');
          // Find the generate preview button and trigger click
          var $button = $('#edit-generate-preview');
          console.log('Found button:', $button.length > 0 ? 'YES' : 'NO');
          if ($button.length) {
            $button.trigger('click');
          }
        };

        window.clearPreview = function() {
          console.log('🗑️ clearPreview called');
          // Clear the preview container
          $('#social-media-preview-container').html(
            '<p><em>Click "Generate Social Media Post Preview" to create AI-powered content based on your most recent article.</em></p>'
          );
        };
      }

      // Add loading state to preview button
      var buttonElements = onceFunction('preview-button-handler', '#edit-generate-preview', context);
      if (buttonElements.length > 0) {
        console.log('✅ Adding click handler to preview button');
        
        $(buttonElements[0]).on('click', function() {
          console.log('🎯 Preview button clicked');
          var $button = $(this);
          var originalText = $button.val();
          
          $button.val('🔄 Generating Preview...').prop('disabled', true);
          console.log('Button text changed to loading state');
          
          // Reset button after AJAX completes
          $(document).ajaxComplete(function(event, xhr, settings) {
            console.log('AJAX complete:', settings.url);
            if (settings.url && (settings.url.indexOf('ajax_form=1') !== -1 || settings.url.indexOf('settings') !== -1)) {
              setTimeout(function() {
                $button.val(originalText).prop('disabled', false);
                console.log('Button reset to original state');
              }, 500);
            }
          });
        });
      }

      // Add copy to clipboard functionality for generated content
      var clipboardElements = onceFunction('clipboard-handler', '.post-text', context);
      for (var i = 0; i < clipboardElements.length; i++) {
        (function(element) {
          $(element).on('click', function() {
            var text = $(this).text();
            if (navigator.clipboard) {
              navigator.clipboard.writeText(text).then(function() {
                console.log('✅ Content copied to clipboard');
                // Show temporary success message
                var $message = $('<div class="clipboard-success">Content copied to clipboard!</div>');
                $(element).append($message);
                setTimeout(function() {
                  $message.fadeOut();
                }, 2000);
              });
            }
          });
        })(clipboardElements[i]);
      }

      // Add character count monitoring for post content
      var characterElements = onceFunction('character-count-handler', '.post-text', context);
      for (var j = 0; j < characterElements.length; j++) {
        (function(element) {
          $(element).on('keyup input paste', function() {
            var length = $(this).text().length;
            var $counter = $(this).siblings('.character-count');
            if ($counter.length) {
              $counter.text('Character count: ' + length);
              
              // Color code based on platform limits
              if (length > 500) {
                $counter.addClass('over-limit').removeClass('warning');
              } else if (length > 400) {
                $counter.addClass('warning').removeClass('over-limit');
              } else {
                $counter.removeClass('warning over-limit');
              }
            }
          });
        })(characterElements[j]);
      }
    }
  };

  // Add AJAX error handling
  $(document).ajaxError(function(event, xhr, settings, thrownError) {
    console.error('🚨 AJAX Error:', {
      status: xhr.status,
      statusText: xhr.statusText,
      responseText: xhr.responseText ? xhr.responseText.substring(0, 200) + '...' : '',
      url: settings.url,
      error: thrownError
    });
    
    // If it's the content preview AJAX call, show user-friendly error
    if (settings.url && (settings.url.indexOf('ajax_form=1') !== -1 || settings.url.indexOf('settings') !== -1)) {
      var $container = $('#social-media-preview-container');
      if ($container.length) {
        $container.html('<div class="messages messages--error">❌ An error occurred while generating the preview. Please check the browser console and server logs for details.</div>');
      }
    }
  });

})(jQuery, Drupal, drupalSettings);
