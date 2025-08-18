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
      // Use the onceFunction for compatibility
      var bodyElements = onceFunction('social-media-content-preview', 'body', context);
      
      if (bodyElements.length > 0) {
        // Add global functions for preview interactions
        window.generateNewPreview = function() {
          // Find the generate preview button and trigger click
          var $button = $('#edit-generate-preview');
          if ($button.length) {
            $button.trigger('click');
          }
        };

        window.clearPreview = function() {
          // Clear the preview container
          $('#social-media-preview-container').html(
            '<p><em>Click "Generate Social Media Post Preview" to create AI-powered content based on your most recent article.</em></p>'
          );
        };
      }

      // Add loading state to preview button
      var buttonElements = onceFunction('preview-button-handler', '#edit-generate-preview', context);
      if (buttonElements.length > 0) {
        $(buttonElements[0]).on('click', function() {
          var $button = $(this);
          var originalText = $button.val();
          
          $button.val('🔄 Generating Preview...').prop('disabled', true);
          
          // Reset button after AJAX completes
          $(document).ajaxComplete(function(event, xhr, settings) {
            if (settings.url && settings.url.indexOf('generate-preview') !== -1) {
              setTimeout(function() {
                $button.val(originalText).prop('disabled', false);
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

})(jQuery, Drupal, drupalSettings);
