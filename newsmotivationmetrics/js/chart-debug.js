/**
 * Chart debug behavior for development and troubleshooting.
 * Extends chart-behavior.js with additional debug functionality.
 */

(function ($, Drupal, drupalSettings) {
  'use strict';

  /**
   * Debug-specific chart behavior.
   */
  Drupal.behaviors.newsMotivationMetricsChartDebug = {
    attach: function (context, settings) {
      // Only initialize once per page load
      if (context !== document) {
        return;
      }

      console.log('=== Chart Debug Mode Activated ===');
      console.log('Debug settings:', settings.newsmotivationmetrics);

      // Initialize debug environment
      this.initializeDebugMode(settings.newsmotivationmetrics || {});
    },

    /**
     * Initialize debug-specific functionality.
     */
    initializeDebugMode: function(chartData) {
      const self = this;
      
      // Update version information
      document.addEventListener('DOMContentLoaded', function() {
        self.updateVersionInfo();
        self.setupDebugEventListeners();
        
        // Auto-start with simple test
        setTimeout(function() {
          self.testSimpleChart();
        }, 500);
      });
    },

    /**
     * Update Chart.js version and adapter information.
     */
    updateVersionInfo: function() {
      const versionEl = document.getElementById('chartjs-version');
      const adapterEl = document.getElementById('date-adapter-status');
      
      if (versionEl) {
        versionEl.textContent = (typeof Chart !== 'undefined') ? Chart.version || 'Unknown' : 'Not Loaded';
      }
      
      if (adapterEl) {
        const hasAdapter = (typeof Chart !== 'undefined') && Chart.adapters && Chart.adapters._date;
        adapterEl.textContent = hasAdapter ? 'Available' : 'Missing';
        
        if (!hasAdapter) {
          this.updateDebugStatus('Date adapter not found - time charts disabled', 'error');
        }
      }
    },

    /**
     * Set up debug-specific event listeners.
     */
    setupDebugEventListeners: function() {
      const self = this;
      
      // Test simple chart button
      const testSimpleBtn = document.getElementById('test-simple');
      if (testSimpleBtn) {
        testSimpleBtn.addEventListener('click', function() {
          self.testSimpleChart();
        });
      }
      
      // Test date chart button
      const testDateBtn = document.getElementById('test-date');
      if (testDateBtn) {
        testDateBtn.addEventListener('click', function() {
          self.testDateChart();
        });
      }
    },

    /**
     * Test simple chart without date functionality.
     */
    testSimpleChart: function() {
      this.updateDebugStatus('Testing simple chart without dates...', 'info');
      
      const canvas = document.getElementById('taxonomy-timeline-chart');
      if (!canvas) {
        this.updateDebugStatus('Canvas element not found!', 'error');
        return;
      }
      
      try {
        // Destroy existing chart
        if (window.newsMetricsChart && window.newsMetricsChart.chart) {
          window.newsMetricsChart.chart.destroy();
        }
        
        const ctx = canvas.getContext('2d');
        const simpleData = {
          labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May'],
          datasets: [{
            label: 'Simple Test Data',
            data: [10, 15, 8, 20, 12],
            borderColor: '#FF6384',
            backgroundColor: '#FF638420',
            tension: 0.1
          }]
        };
        
        const chart = new Chart(ctx, {
          type: 'line',
          data: simpleData,
          options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
              y: {
                beginAtZero: true,
                title: {
                  display: true,
                  text: 'Count'
                }
              }
            },
            plugins: {
              title: {
                display: true,
                text: 'Simple Test Chart'
              }
            }
          }
        });
        
        // Store chart reference
        if (!window.newsMetricsChart) {
          window.newsMetricsChart = {};
        }
        window.newsMetricsChart.chart = chart;
        
        this.updateDebugStatus('Simple chart created successfully', 'success');
        
      } catch (error) {
        this.updateDebugStatus('Simple chart failed: ' + error.message, 'error');
        console.error('Simple chart error:', error);
      }
    },

    /**
     * Test date-based chart functionality.
     */
    testDateChart: function() {
      this.updateDebugStatus('Testing date-based chart...', 'info');
      
      const canvas = document.getElementById('taxonomy-timeline-chart');
      if (!canvas) {
        this.updateDebugStatus('Canvas element not found!', 'error');
        return;
      }
      
      // Check for date adapter
      if (typeof Chart === 'undefined' || !Chart.adapters || !Chart.adapters._date) {
        this.updateDebugStatus('Date adapter not available for time chart', 'error');
        return;
      }
      
      try {
        // Destroy existing chart
        if (window.newsMetricsChart && window.newsMetricsChart.chart) {
          window.newsMetricsChart.chart.destroy();
        }
        
        const ctx = canvas.getContext('2d');
        const dateData = {
          datasets: [{
            label: 'Date Test Data',
            data: [
              { x: '2024-08-01', y: 10 },
              { x: '2024-08-02', y: 15 },
              { x: '2024-08-03', y: 8 },
              { x: '2024-08-04', y: 20 },
              { x: '2024-08-05', y: 12 }
            ],
            borderColor: '#36A2EB',
            backgroundColor: '#36A2EB20',
            tension: 0.1
          }]
        };
        
        const chart = new Chart(ctx, {
          type: 'line',
          data: dateData,
          options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
              x: {
                type: 'time',
                time: {
                  parser: 'YYYY-MM-DD',
                  tooltipFormat: 'MMM DD, YYYY',
                  displayFormats: {
                    day: 'MMM DD'
                  }
                },
                title: {
                  display: true,
                  text: 'Date'
                }
              },
              y: {
                beginAtZero: true,
                title: {
                  display: true,
                  text: 'Count'
                }
              }
            },
            plugins: {
              title: {
                display: true,
                text: 'Date-based Test Chart'
              }
            }
          }
        });
        
        // Store chart reference
        if (!window.newsMetricsChart) {
          window.newsMetricsChart = {};
        }
        window.newsMetricsChart.chart = chart;
        
        this.updateDebugStatus('Date chart created successfully', 'success');
        
      } catch (error) {
        this.updateDebugStatus('Date chart failed: ' + error.message, 'error');
        console.error('Date chart error:', error);
      }
    },

    /**
     * Update debug status messages.
     */
    updateDebugStatus: function(message, type) {
      type = type || 'info';
      
      // Update debug status display
      const debugEl = document.getElementById('debug-status');
      if (debugEl) {
        debugEl.textContent = message + ' (' + new Date().toLocaleTimeString() + ')';
      }
      
      // Update main status display
      const statusEl = document.getElementById('chart-status');
      if (statusEl) {
        statusEl.className = 'chart-status ' + type;
        statusEl.textContent = 'Chart Status: ' + message;
      }
      
      // Console logging with timestamp
      console.log('[DEBUG] ' + new Date().toISOString() + ' - ' + type.toUpperCase() + ':', message);
    }
  };

})(jQuery, Drupal, drupalSettings);