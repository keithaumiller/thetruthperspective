(function($, Drupal) {
  'use strict';

  Drupal.behaviors.aiConversationChat = {
    attach: function(context, settings) {
      const $chatContainer = $('.ai-conversation-chat', context);
      
      if ($chatContainer.length === 0) {
        return;
      }

      const chatSettings = settings.aiConversation || {};
      const $messageInput = $('#message-input', $chatContainer);
      const $sendButton = $('#send-button', $chatContainer);
      const $clearButton = $('#clear-input', $chatContainer);
      const $triggerSummaryButton = $('#trigger-summary', $chatContainer);
      const $messagesContainer = $('#chat-messages', $chatContainer);
      const $loadingIndicator = $('#loading-indicator', $chatContainer);

      // Send message handler
      function sendMessage() {
        const message = $messageInput.val().trim();
        
        if (!message) {
          return;
        }

        // Show loading indicator
        $loadingIndicator.show();
        $sendButton.prop('disabled', true);

        // Add user message to chat immediately
        addMessageToChat('user', message);

        // Clear input
        $messageInput.val('');

        // Send to server
        $.ajax({
          url: chatSettings.sendMessageUrl,
          type: 'POST',
          data: {
            node_id: chatSettings.nodeId,
            message: message,
            csrf_token: chatSettings.csrfToken
          },
          success: function(response) {
            if (response.success) {
              // Add AI response to chat
              addMessageToChat('assistant', response.response);
              
              // Update statistics if provided
              if (response.stats) {
                updateStats(response.stats);
              }
            } else {
              showError(response.error || 'Unknown error occurred');
            }
          },
          error: function(xhr, status, error) {
            let errorMessage = 'Failed to send message';
            if (xhr.responseJSON && xhr.responseJSON.error) {
              errorMessage = xhr.responseJSON.error;
            }
            showError(errorMessage);
          },
          complete: function() {
            $loadingIndicator.hide();
            $sendButton.prop('disabled', false);
            $messageInput.focus();
          }
        });
      }

      // Add message to chat UI
      function addMessageToChat(role, content) {
        const timestamp = new Date().toLocaleString();
        const $message = $('<div class="message message--' + role + '">');
        const $content = $('<div class="message-content">').html(content.replace(/\n/g, '<br>'));
        const $timestamp = $('<div class="message-timestamp">').text(timestamp);
        
        $message.append($content).append($timestamp);
        $messagesContainer.append($message);
        
        // Scroll to bottom
        $messagesContainer.scrollTop($messagesContainer[0].scrollHeight);
      }

      // Show error message
      function showError(message) {
        const $error = $('<div class="message message--error">');
        const $content = $('<div class="message-content">').html('<strong>Error:</strong> ' + message);
        
        $error.append($content);
        $messagesContainer.append($error);
        $messagesContainer.scrollTop($messagesContainer[0].scrollHeight);
      }

      // Update statistics display
      function updateStats(stats) {
        // Update total messages
        $('.stat-item').each(function() {
          const $this = $(this);
          const text = $this.text();
          
          if (text.includes('Total Messages')) {
            $this.html('<strong>Total Messages:</strong> ' + stats.total_messages);
          } else if (text.includes('Recent Messages')) {
            $this.html('<strong>Recent Messages:</strong> ' + stats.recent_messages);
          } else if (text.includes('Has Summary')) {
            const hasStatus = stats.has_summary ? 
              '<span class="status-yes">Yes</span>' : 
              '<span class="status-no">No</span>';
            $this.html('<strong>Has Summary:</strong> ' + hasStatus);
          } else if (text.includes('Estimated Tokens')) {
            $this.html('<strong>Estimated Tokens:</strong> ' + stats.estimated_tokens);
          }
        });

        // Show/hide summary indicator
        if (stats.has_summary && $('.summary-indicator').length === 0) {
          $messagesContainer.prepend(
            '<div class="summary-indicator"><em>This conversation has been summarized. Showing recent messages.</em></div>'
          );
        }

        // Show/hide trigger summary button
        if (stats.total_messages > 20) {
          $triggerSummaryButton.show();
        } else {
          $triggerSummaryButton.hide();
        }
      }

      // Trigger summary update
      function triggerSummaryUpdate() {
        if (!confirm('Are you sure you want to update the conversation summary? This will condense older messages.')) {
          return;
        }

        $triggerSummaryButton.prop('disabled', true);
        
        $.ajax({
          url: '/node/' + chatSettings.nodeId + '/trigger-summary',
          type: 'GET',
          success: function(response) {
            if (response.success) {
              alert('Summary updated successfully!');
              location.reload(); // Reload to show updated conversation
            } else {
              alert('Error updating summary: ' + (response.error || 'Unknown error'));
            }
          },
          error: function(xhr, status, error) {
            alert('Failed to update summary: ' + error);
          },
          complete: function() {
            $triggerSummaryButton.prop('disabled', false);
          }
        });
      }

      // Event handlers
      $sendButton.on('click', sendMessage);
      $clearButton.on('click', function() {
        $messageInput.val('');
        $messageInput.focus();
      });
      $triggerSummaryButton.on('click', triggerSummaryUpdate);

      // Enter key to send (with Shift+Enter for new line)
      $messageInput.on('keydown', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
          e.preventDefault();
          sendMessage();
        }
      });

      // Focus on message input when page loads
      $messageInput.focus();

      // Auto-scroll to bottom of messages
      $messagesContainer.scrollTop($messagesContainer[0].scrollHeight);
    }
  };

})(jQuery, Drupal);
