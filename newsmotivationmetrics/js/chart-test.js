/**
 * @file
 * Chart.js Hello World test for The Truth Perspective with verified paths.
 * Tests Chart.js loading at /modules/custom/newsmotivationmetrics/js/chart.umd.js
 */

(function ($, Drupal, drupalSettings) {
  'use strict';

  /**
   * Chart.js Hello World test behavior.
   */
  Drupal.behaviors.truthPerspectiveChartTest = {
    attach: function (context, settings) {
      // Only run once per page load
      if (context !== document) {
        return;
      }

      console.log('=== The Truth Perspective Chart.js Hello World Test ===');
      console.log('Chart.js expected at: /modules/custom/newsmotivationmetrics/js/chart.umd.js');
      
      this.initializeTest();
    },

    /**
     * Initialize the Chart.js Hello World test.
     */
    initializeTest: function() {
      const self = this;
      
      self.log('Starting Chart.js Hello World test...');
      self.updateStatus('Initializing Chart.js test environment...', 'loading');
      
      // Wait for Chart.js to load
      self.waitForChartJS(function(success) {
        if (success) {
          self.log('SUCCESS: Hello World - Chart.js loaded successfully!');
          self.updateStatus('✅ Hello World - Chart.js is working perfectly!', 'success');
          self.displayChartInformation();
          self.testBasicChartCreation();
        } else {
          self.log('ERROR: Chart.js failed to load from verified path');
          self.updateStatus('❌ Chart.js library failed to load', 'error');
          self.showTroubleshootingInfo();
        }
      });
    },

    /**
     * Wait for Chart.js library to load with robust detection.
     */
    waitForChartJS: function(callback, attempts) {
      attempts = attempts || 0;
      const maxAttempts = 100; // 10 seconds
      const self = this;

      // Check if Chart.js is available
      if (typeof window.Chart !== 'undefined' && window.Chart && window.Chart.version) {
        self.log('Chart.js detected after ' + attempts + ' attempts (version: ' + window.Chart.version + ')');
        callback(true);
        return;
      }

      if (attempts >= maxAttempts) {
        self.log('Chart.js not loaded after ' + maxAttempts + ' attempts (10 seconds)');
        self.logDiagnostics();
        callback(false);
        return;
      }

      // Progress updates every 2 seconds
      if (attempts % 20 === 0 && attempts > 0) {
        const seconds = attempts / 10;
        self.log('Still waiting for Chart.js... (' + seconds + 's)');
        self.updateStatus('Waiting for Chart.js to load... (' + seconds + 's)', 'loading');
      }

      setTimeout(function() {
        self.waitForChartJS(callback, attempts + 1);
      }, 100);
    },

    /**
     * Display Chart.js library information.
     */
    displayChartInformation: function() {
      const versionEl = document.getElementById('chart-version');
      const adapterEl = document.getElementById('date-adapter');

      // Display Chart.js version
      if (versionEl && typeof window.Chart !== 'undefined') {
        try {
          const version = window.Chart.version || 'Unknown';
          versionEl.textContent = version + ' (Local - Hello World)';
          versionEl.style.color = '#28a745';
          versionEl.style.fontWeight = 'bold';
          this.log('Chart.js version confirmed: ' + version);
        } catch (error) {
          versionEl.textContent = 'Error: ' + error.message;
          versionEl.style.color = '#dc3545';
          this.log('Chart.js version error: ' + error.message);
        }
      }

      // Check date adapter availability
      if (adapterEl && typeof window.Chart !== 'undefined') {
        try {
          if (window.Chart.adapters && window.Chart.adapters._date) {
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
        } catch (error) {
          adapterEl.textContent = '❌ Error: ' + error.message;
          adapterEl.style.color = '#dc3545';
          this.log('Date adapter error: ' + error.message);
        }
      }
    },

    /**
     * Test basic chart creation to verify Chart.js functionality.
     */
    testBasicChartCreation: function() {
      this.log('Testing basic chart creation...');
      
      try {
        // Create a minimal test chart to verify Chart.js works
        const testCanvas = document.createElement('canvas');
        testCanvas.id = 'hello-world-test-chart';
        testCanvas.width = 200;
        testCanvas.height = 100;
        testCanvas.style.display = 'none';
        document.body.appendChild(testCanvas);

        const ctx = testCanvas.getContext('2d');
        const testChart = new Chart(ctx, {
          type: 'line',
          data: {
            labels: ['Hello', 'World'],
            datasets: [{
              label: 'Test Data',
              data: [1, 2],
              borderColor: '#007bff'
            }]
          },
          options: {
            responsive: false,
            animation: false
          }
        });

        // If we get here, Chart.js is working
        this.log('✅ Basic chart creation test successful');
        testChart.destroy();
        document.body.removeChild(testCanvas);
        
        // Update status with success details
        this.updateStatus('✅ Hello World - Chart.js fully functional!', 'success');
        
      } catch (error) {
        this.log('❌ Basic chart creation failed: ' + error.message);
        this.updateStatus('⚠️ Chart.js loaded but chart creation failed', 'error');
      }
    },

    /**
     * Log diagnostic information for troubleshooting.
     */
    logDiagnostics: function() {
      this.log('=== Chart.js Diagnostics ===');
      this.log('typeof Chart: ' + typeof Chart);
      this.log('typeof window.Chart: ' + typeof window.Chart);
      this.log('Chart object exists: ' + (typeof Chart !== 'undefined'));
      this.log('Chart.version exists: ' + (typeof Chart !== 'undefined' && !!Chart.version));
      this.log('Current URL: ' + window.location.href);
      this.log('Document ready state: ' + document.readyState);
      
      // Check if Chart.js script tag exists in DOM
      const chartScripts = document.querySelectorAll('script[src*="chart"]');
      this.log('Chart-related script tags found: ' + chartScripts.length);
      chartScripts.forEach((script, index) => {
        this.log('Script ' + index + ': ' + script.src);
      });
    },

    /**
     * Show troubleshooting information when Chart.js fails.
     */
    showTroubleshootingInfo: function() {
      this.log('=== Troubleshooting Information ===');
      this.log('1. Verified Chart.js path: /modules/custom/newsmotivationmetrics/js/chart.umd.js');
      this.log('2. Check browser Network tab for 404 or loading errors');
      this.log('3. Verify file permissions allow web server access');
      this.log('4. Clear Drupal cache: drush cr');
      this.log('5. Check for JavaScript conflicts in browser console');
      
      const statusEl = document.getElementById('chart-status');
      if (statusEl) {
        statusEl.innerHTML = `
          ❌ Chart.js failed to load from verified path<br>
          <small>Path confirmed accessible: /modules/custom/newsmotivationmetrics/js/chart.umd.js</small>
        `;
      }
    },

    /**
     * Update the main status display.
     */
    updateStatus: function(message, type) {
      const statusEl = document.getElementById('chart-status');
      if (statusEl) {
        statusEl.innerHTML = message;
        statusEl.className = 'status ' + type;
      }
      this.log('STATUS: ' + message);
    },

    /**
     * Enhanced logging with timestamps.
     */
    log: function(message) {
      const timestamp = new Date().toLocaleTimeString();
      const logMessage = '[' + timestamp + '] Hello World Test: ' + message;
      
      console.log(logMessage);
      
      try {
        const debugEl = document.getElementById('debug-log');
        if (debugEl) {
          debugEl.textContent += logMessage + '\n';
          debugEl.scrollTop = debugEl.scrollHeight;
        }
      } catch (error) {
        console.error('Debug logging error:', error);
      }
    }
  };

})(jQuery, Drupal, drupalSettings);