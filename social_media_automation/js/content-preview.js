/**
 * @file
 * JavaScript for Social Media Content Preview functionality.
 */

(function ($, Drupal) {
  'use strict';

  /**
   * Behavior for content preview interactions.
   */
  Drupal.behaviors.socialMediaContentPreview = {
    attach: function (context, settings) {
      // Ensure we only attach once
      $('body', context).once('social-media-content-preview').each(function () {
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

        // Add loading state to preview button
        $(document).on('click', '#edit-generate-preview', function() {
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

        // Add copy to clipboard functionality for generated content
        $(document).on('click', '.post-text', function() {
          var text = $(this).text();
          if (navigator.clipboard) {
            navigator.clipboard.writeText(text).then(function() {
              // Show temporary success message
              var $message = $('<div class="clipboard-success">Content copied to clipboard!</div>');
              $(this).append($message);
              setTimeout(function() {
                $message.fadeOut();
              }, 2000);
            }.bind(this));
          }
        });

        // Add character count monitoring for post content
        $(document).on('keyup input paste', '.post-text', function() {
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
      });
    }
  };

})(jQuery, Drupal);
