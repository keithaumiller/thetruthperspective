(function ($, Drupal, drupalSettings) {
  'use strict';

  Drupal.behaviors.aiConversationChat = {
    attach: function (context, settings) {
      var $chatContainer = $('.ai-conversation-chat', context);
      if ($chatContainer.length === 0) {
        return;
      }

      var nodeId = $chatContainer.data('node-id');
      var $form = $('#chat-form', context);
      var $messageInput = $('#message-input', context);
      var $sendButton = $('#send-button', context);
      var $messagesContainer = $('#chat-messages', context);
      var $loadingIndicator = $('#loading-indicator', context);

      // Auto-scroll to bottom of messages
      function scrollToBottom() {
        $messagesContainer.scrollTop($messagesContainer[0].scrollHeight);
      }

      // Initial scroll to bottom
      scrollToBottom();

      // Format timestamp
      function formatTimestamp(timestamp) {
        return new Date(timestamp * 1000).toLocaleTimeString();
      }

      // Add message to chat
      function addMessage(message) {
        var messageHtml = '<div class="message message--' + message.role + '">' +
          '<div class="message-content">' +
          '<div class="message-text">' + message.content.replace(/\n/g, '<br>') + '</div>' +
          '<div class="message-timestamp">' + formatTimestamp(message.timestamp) + '</div>' +
          '</div>' +
          '</div>';
        
        $messagesContainer.append(messageHtml);
        scrollToBottom();
      }

      // Show loading state
      function showLoading() {
        $sendButton.prop('disabled', true);
        $('.send-text').hide();
        $('.loading-text').show();
        $loadingIndicator.show();
      }

      // Hide loading state
      function hideLoading() {
        $sendButton.prop('disabled', false);
        $('.send-text').show();
        $('.loading-text').hide();
        $loadingIndicator.hide();
      }

      // Send message
      function sendMessage(message) {
        showLoading();

        $.ajax({
          url: drupalSettings.aiConversation.sendMessageUrl,
          type: 'POST',
          data: {
            node_id: nodeId,
            message: message,
            csrf_token: drupalSettings.aiConversation.csrfToken
          },
          success: function (response) {
            hideLoading();
            
            if (response.success) {
              // Add user message
              addMessage(response.user_message);
              
              // Add AI response
              addMessage(response.ai_message);
              
              // Clear input
              $messageInput.val('');
              $messageInput.focus();
            } else {
              alert('Error: ' + (response.error || 'Unknown error'));
            }
          },
          error: function (xhr, status, error) {
            hideLoading();
            console.error('AJAX error:', error);
            alert('Failed to send message. Please try again.');
          }
        });
      }

      // Handle form submission
      $form.on('submit', function (e) {
        e.preventDefault();
        
        var message = $messageInput.val().trim();
        if (message === '') {
          return;
        }

        sendMessage(message);
      });

      // Handle Enter key (without Shift)
      $messageInput.on('keydown', function (e) {
        if (e.key === 'Enter' && !e.shiftKey) {
          e.preventDefault();
          $form.submit();
        }
      });

      // Auto-resize textarea
      $messageInput.on('input', function () {
        this.style.height = 'auto';
        this.style.height = Math.min(this.scrollHeight, 200) + 'px';
      });

      // Focus on input when page loads
      $messageInput.focus();
    }
  };

})(jQuery, Drupal, drupalSettings);
