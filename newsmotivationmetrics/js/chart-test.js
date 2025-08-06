/**
 * @file
 * Minimal Chart.js Hello World test for The Truth Perspective.
 * Only tests Chart.js loading and displays simple status information.
 */

(function ($, Drupal, drupalSettings) {
  'use strict';

  /**
   * Simple Chart.js Hello World test behavior.
   */
  Drupal.behaviors.chartTest = {
    attach: function (context, settings) {
      // Only run once per page load
      if (context !== document) {
        return;
      }

      console.log('=== The Truth Perspective Chart.js Hello World Test ===');
      this.runHelloWorldTest();
    },

    /**
     * Run the Hello World test for Chart.js loading.
     */
    runHelloWorldTest: function() {
      const self = this;
      
      self.log('Starting Chart.js Hello World test...');
      self.updateStatus('Testing Chart.js library loading...', 'loading');
      
      // Wait for Chart.js to load with timeout
      self.waitForChartLibrary(function(success) {
        if (success) {
          self.log('SUCCESS: Hello World - Chart.js loaded successfully!');
          self.updateStatus('✅ Hello World - Chart.js is working perfectly!', 'success');
          self.displayLibraryInformation();
          self.log('Chart.js Hello World test completed successfully');
        } else {
          self.log('ERROR: Chart.js Hello World test failed - library not loaded');
          self.updateStatus('❌ Chart.js library failed to load', 'error');
          self.log('Check: /modules/custom/newsmotivationmetrics/js/chart.umd.js file accessibility');
        }
      });
    },

    /**
     * Wait for Chart.js library to load with enhanced detection.
     */
    waitForChartLibrary: function(callback, attempts) {
      attempts = attempts || 0;
      const maxAttempts = 50; // 5 seconds with 100ms intervals
      const self = this;

      // Check if Chart.js is available and properly initialized
      if (typeof Chart !== 'undefined') {
        self.log('Chart.js Hello World: Library detected after ' + attempts + ' attempts');
        callback(true);
        return;
      }

      if (attempts >= maxAttempts) {
        self.log('Chart.js Hello World: Library not found after ' + maxAttempts + ' attempts (5 seconds timeout)');
        callback(false);
        return;
      }

      // Show progress every 10 attempts (1 second)
      if (attempts % 10 === 0 && attempts > 0) {
        const seconds = attempts / 10;
        self.log('Chart.js Hello World: Still waiting... (' + seconds + 's)');
      }

      setTimeout(function() {
        self.waitForChartLibrary(callback, attempts + 1);
      }, 100);
    },

    /**
     * Display Chart.js library information for Hello World test.
     */
    displayLibraryInformation: function() {
      const versionEl = document.getElementById('chart-version');
      const adapterEl = document.getElementById('date-adapter');

      // Display Chart.js version
      if (versionEl && typeof Chart !== 'undefined') {
        const version = Chart.version || 'Unknown Version';
        versionEl.textContent = version + ' (Local)';
        versionEl.style.color = '#28a745';
        versionEl.style.fontWeight = 'bold';
        this.log('Chart.js version: ' + version);
      }

      // Check date adapter availability
      if (adapterEl) {
        if (typeof Chart !== 'undefined' && Chart.adapters && Chart.adapters._date) {
          adapterEl.textContent = '✅ Available';
          adapterEl.style.color = '#28a745';
          adapterEl.style.fontWeight = 'bold';
          this.log('Date adapter: Available for time-based charts');
        } else {
          adapterEl.textContent = '⚠️ Not Available';
          adapterEl.style.color = '#ffc107';
          adapterEl.style.fontWeight = 'bold';
          this.log('Date adapter: Not available - simple charts only');
        }
      }

      this.log('Chart.js Hello World: Library information displayed successfully');
    },

    /**
     * Update the main status display with Hello World context.
     */
    updateStatus: function(message, type) {
      const statusEl = document.getElementById('chart-status');
      if (statusEl) {
        statusEl.textContent = message;
        statusEl.className = 'status ' + type;
      }
      this.log('STATUS UPDATE: ' + message);
    },

    /**
     * Enhanced logging for Hello World test with timestamps.
     */
    log: function(message) {
      const timestamp = new Date().toLocaleTimeString();
      const logMessage = '[' + timestamp + '] Hello World Test: ' + message;
      
      // Console logging for debugging
      console.log(logMessage);
      
      // Display in debug log element
      const debugEl = document.getElementById('debug-log');
      if (debugEl) {
        debugEl.textContent += logMessage + '\n';
        // Auto-scroll to bottom
        debugEl.scrollTop = debugEl.scrollHeight;
      }
    }
  };

})(jQuery, Drupal, drupalSettings);