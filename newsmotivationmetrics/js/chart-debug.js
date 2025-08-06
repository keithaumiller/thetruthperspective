/**
 * @file
 * Chart.js debug interface for The Truth Perspective news motivation metrics.
 * Production-ready debug console with comprehensive Chart.js integration testing.
 */

(function ($, Drupal, drupalSettings) {
  'use strict';

  // Prevent multiple executions and conflicts
  if (window.truthPerspectiveChartDebugExecuted) {
    return;
  }

  /**
   * News Motivation Metrics Chart Debug behavior for The Truth Perspective.
   */
  Drupal.behaviors.newsMotivationMetricsChartDebug = {
    attach: function (context, settings) {
      // Only run once per page load and only on the chart-debug page
      if (context !== document || 
          window.truthPerspectiveChartDebugExecuted ||
          !window.location.pathname.includes('/admin/metrics/chart-debug')) {
        return;
      }

      // Mark as executed to prevent duplicates
      window.truthPerspectiveChartDebugExecuted = true;

      console.log('=== The Truth Perspective Chart Debug Console ===');
      console.log('Initializing comprehensive Chart.js debug system for news motivation metrics');
      
      this.initializeDebugConsole();
    },

    /**
     * Initialize the comprehensive debug console.
     */
    initializeDebugConsole: function() {
      const self = this;
      
      self.log('Initializing Chart Debug Console for The Truth Perspective');
      self.updateDebugStatus('Starting Chart.js debug verification...', 'info');
      
      // Verify Chart.js is ready for debug operations
      self.verifyChartJSForDebug(function(success, details) {
        if (success) {
          self.log('Chart.js debug environment ready');
          self.updateDebugStatus('✅ Chart.js Debug Environment Ready', 'success');
          self.setupDebugInterface(details);
          self.loadDebugData();
        } else {
          self.log('Chart.js debug environment failed');
          self.updateDebugStatus('❌ Chart.js Debug Environment Failed', 'error');
          self.showDebugTroubleshooting(details);
        }
      });
    },

    /**
     * Verify Chart.js is ready for debug operations.
     */
    verifyChartJSForDebug: function(callback, attempts) {
      attempts = attempts || 0;
      const maxAttempts = 50;
      const self = this;

      // Enhanced Chart.js verification for debug mode
      if (typeof window.Chart !== 'undefined' && 
          window.Chart && 
          window.Chart.version &&
          typeof window.Chart.register === 'function') {
        
        const details = {
          version: window.Chart.version,
          hasAdapters: !!(window.Chart.adapters),
          hasDateAdapter: !!(window.Chart.adapters && window.Chart.adapters._date),
          registeredControllers: Object.keys(window.Chart.registry.controllers.items),
          registeredScales: Object.keys(window.Chart.registry.scales.items),
          loadTime: attempts * 100,
          debugReady: true
        };
        
        self.log('Chart.js debug verification successful: v' + details.version);
        callback(true, details);
        return;
      }

      if (attempts >= maxAttempts) {
        const diagnostics = {
          chartAvailable: typeof window.Chart !== 'undefined',
          chartVersion: window.Chart ? window.Chart.version : null,
          timeout: true,
          attempts: attempts
        };
        callback(false, diagnostics);
        return;
      }

      setTimeout(function() {
        self.verifyChartJSForDebug(callback, attempts + 1);
      }, 100);
    },

    /**
     * Setup the debug interface with Chart.js details.
     */
    setupDebugInterface: function(details) {
      this.displayChartJSInfo(details);
      this.setupDebugEventListeners();
      this.initializeDebugCharts();
      this.log('Debug interface setup complete');
    },

    /**
     * Display comprehensive Chart.js information.
     */
    displayChartJSInfo: function(details) {
      // Update version info
      const versionEl = document.getElementById('chart-version');
      if (versionEl) {
        versionEl.textContent = details.version + ' (Debug Mode)';
        versionEl.style.color = '#28a745';
      }

      // Update adapter info
      const adapterEl = document.getElementById('date-adapter-status');
      if (adapterEl) {
        if (details.hasDateAdapter) {
          adapterEl.textContent = '✅ Available (Timeline Ready)';
          adapterEl.style.color = '#28a745';
        } else {
          adapterEl.textContent = '⚠️ Missing (Timeline Limited)';
          adapterEl.style.color = '#ffc107';
        }
      }

      // Update controllers info
      const controllersEl = document.getElementById('chart-controllers');
      if (controllersEl) {
        controllersEl.textContent = details.registeredControllers.length + ' registered';
        controllersEl.style.color = '#17a2b8';
      }

      // Update scales info
      const scalesEl = document.getElementById('chart-scales');
      if (scalesEl) {
        scalesEl.textContent = details.registeredScales.length + ' available';
        scalesEl.style.color = '#17a2b8';
      }

      this.log('Chart.js debug information displayed');
      this.log('Registered chart types: ' + details.registeredControllers.join(', '));
    },

    /**
     * Setup debug event listeners.
     */
    setupDebugEventListeners: function() {
      const self = this;

      // Test Simple Chart button
      $('#test-simple-chart').on('click', function() {
        self.testSimpleChart();
      });

      // Test Timeline Chart button
      $('#test-timeline-chart').on('click', function() {
        self.testTimelineChart();
      });

      // Test Real Data Chart button
      $('#test-real-data-chart').on('click', function() {
        self.testRealDataChart();
      });

      // Clear Charts button
      $('#clear-debug-charts').on('click', function() {
        self.clearDebugCharts();
      });

      // Refresh Data button
      $('#refresh-debug-data').on('click', function() {
        self.refreshDebugData();
      });

      this.log('Debug event listeners configured');
    },

    /**
     * Initialize debug charts area.
     */
    initializeDebugCharts: function() {
      const chartsContainer = document.getElementById('debug-charts-container');
      if (!chartsContainer) {
        this.log('Debug charts container not found');
        return;
      }

      // Clear any existing content
      chartsContainer.innerHTML = '';
      
      // Create canvas for debug charts
      const canvas = document.createElement('canvas');
      canvas.id = 'debug-main-chart';
      canvas.width = 800;
      canvas.height = 400;
      canvas.style.maxWidth = '100%';
      canvas.style.height = 'auto';
      chartsContainer.appendChild(canvas);

      this.log('Debug charts area initialized');
    },

    /**
     * Load debug data for testing.
     */
    loadDebugData: function() {
      const self = this;
      
      if (window.chartDebugData && window.chartDebugData.timelineData) {
        self.log('Debug data already available: ' + window.chartDebugData.timelineData.length + ' timeline entries');
        self.updateDebugStatus('Debug data loaded: ' + window.chartDebugData.timelineData.length + ' entries', 'info');
        return;
      }

      // If no data available, create sample data for testing
      window.chartDebugData = {
        timelineData: [
          { date: '2024-01-01', count: 5, label: 'Political Analysis' },
          { date: '2024-01-02', count: 8, label: 'Economic Reports' },
          { date: '2024-01-03', count: 3, label: 'Social Issues' },
          { date: '2024-01-04', count: 12, label: 'Cultural Topics' },
          { date: '2024-01-05', count: 7, label: 'International News' }
        ],
        topTerms: [
          { name: 'Politics', count: 45 },
          { name: 'Economy', count: 32 },
          { name: 'Society', count: 28 },
          { name: 'Culture', count: 15 }
        ]
      };

      self.log('Sample debug data created for testing');
      self.updateDebugStatus('Sample debug data created for testing', 'info');
    },

    /**
     * Test simple chart creation.
     */
    testSimpleChart: function() {
      this.log('Testing simple chart creation...');
      this.updateDebugStatus('Creating simple test chart...', 'info');

      try {
        const canvas = document.getElementById('debug-main-chart');
        if (!canvas) {
          throw new Error('Debug chart canvas not found');
        }

        // Clear any existing chart
        if (window.debugChart) {
          window.debugChart.destroy();
        }

        const ctx = canvas.getContext('2d');
        window.debugChart = new Chart(ctx, {
          type: 'bar',
          data: {
            labels: ['Political', 'Economic', 'Social', 'Cultural'],
            datasets: [{
              label: 'News Motivation Categories',
              data: [12, 19, 3, 5],
              backgroundColor: [
                'rgba(54, 162, 235, 0.8)',
                'rgba(255, 99, 132, 0.8)',
                'rgba(255, 205, 86, 0.8)',
                'rgba(75, 192, 192, 0.8)'
              ],
              borderColor: [
                'rgba(54, 162, 235, 1)',
                'rgba(255, 99, 132, 1)',
                'rgba(255, 205, 86, 1)',
                'rgba(75, 192, 192, 1)'
              ],
              borderWidth: 2
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
              title: {
                display: true,
                text: 'The Truth Perspective - Simple Chart Test',
                font: { size: 16 }
              },
              legend: {
                display: true,
                position: 'top'
              }
            },
            scales: {
              y: {
                beginAtZero: true,
                title: {
                  display: true,
                  text: 'Article Count'
                }
              },
              x: {
                title: {
                  display: true,
                  text: 'Motivation Categories'
                }
              }
            }
          }
        });

        this.log('✅ Simple chart test successful');
        this.updateDebugStatus('✅ Simple chart created successfully', 'success');

      } catch (error) {
        this.log('❌ Simple chart test failed: ' + error.message);
        this.updateDebugStatus('❌ Simple chart test failed: ' + error.message, 'error');
      }
    },

    /**
     * Test timeline chart creation.
     */
    testTimelineChart: function() {
      this.log('Testing timeline chart creation...');
      this.updateDebugStatus('Creating timeline test chart...', 'info');

      if (!window.Chart.adapters || !window.Chart.adapters._date) {
        this.log('⚠️ Timeline chart test skipped: Date adapter not available');
        this.updateDebugStatus('⚠️ Timeline test skipped: Date adapter missing', 'warning');
        return;
      }

      try {
        const canvas = document.getElementById('debug-main-chart');
        if (!canvas) {
          throw new Error('Debug chart canvas not found');
        }

        // Clear any existing chart
        if (window.debugChart) {
          window.debugChart.destroy();
        }

        const ctx = canvas.getContext('2d');
        window.debugChart = new Chart(ctx, {
          type: 'line',
          data: {
            datasets: [{
              label: 'Article Timeline',
              data: window.chartDebugData.timelineData.map(item => ({
                x: item.date,
                y: item.count
              })),
              borderColor: 'rgba(54, 162, 235, 1)',
              backgroundColor: 'rgba(54, 162, 235, 0.1)',
              fill: true,
              tension: 0.4
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
              title: {
                display: true,
                text: 'The Truth Perspective - Timeline Chart Test',
                font: { size: 16 }
              }
            },
            scales: {
              x: {
                type: 'time',
                time: {
                  unit: 'day'
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
                  text: 'Article Count'
                }
              }
            }
          }
        });

        this.log('✅ Timeline chart test successful');
        this.updateDebugStatus('✅ Timeline chart created successfully', 'success');

      } catch (error) {
        this.log('❌ Timeline chart test failed: ' + error.message);
        this.updateDebugStatus('❌ Timeline chart test failed: ' + error.message, 'error');
      }
    },

    /**
     * Test real data chart creation.
     */
    testRealDataChart: function() {
      this.log('Testing real data chart creation...');
      this.updateDebugStatus('Creating real data test chart...', 'info');

      try {
        const canvas = document.getElementById('debug-main-chart');
        if (!canvas) {
          throw new Error('Debug chart canvas not found');
        }

        // Clear any existing chart
        if (window.debugChart) {
          window.debugChart.destroy();
        }

        const ctx = canvas.getContext('2d');
        window.debugChart = new Chart(ctx, {
          type: 'doughnut',
          data: {
            labels: window.chartDebugData.topTerms.map(term => term.name),
            datasets: [{
              label: 'Top Terms Distribution',
              data: window.chartDebugData.topTerms.map(term => term.count),
              backgroundColor: [
                'rgba(255, 99, 132, 0.8)',
                'rgba(54, 162, 235, 0.8)',
                'rgba(255, 205, 86, 0.8)',
                'rgba(75, 192, 192, 0.8)'
              ],
              borderWidth: 2
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
              title: {
                display: true,
                text: 'The Truth Perspective - Top Terms Analysis',
                font: { size: 16 }
              },
              legend: {
                display: true,
                position: 'right'
              }
            }
          }
        });

        this.log('✅ Real data chart test successful');
        this.updateDebugStatus('✅ Real data chart created successfully', 'success');

      } catch (error) {
        this.log('❌ Real data chart test failed: ' + error.message);
        this.updateDebugStatus('❌ Real data chart test failed: ' + error.message, 'error');
      }
    },

    /**
     * Clear all debug charts.
     */
    clearDebugCharts: function() {
      this.log('Clearing debug charts...');
      
      if (window.debugChart) {
        window.debugChart.destroy();
        window.debugChart = null;
        this.log('Debug chart cleared');
      }

      const canvas = document.getElementById('debug-main-chart');
      if (canvas) {
        const ctx = canvas.getContext('2d');
        ctx.clearRect(0, 0, canvas.width, canvas.height);
      }

      this.updateDebugStatus('Charts cleared', 'info');
    },

    /**
     * Refresh debug data.
     */
    refreshDebugData: function() {
      this.log('Refreshing debug data...');
      this.updateDebugStatus('Refreshing debug data...', 'info');
      
      // Simulate data refresh
      setTimeout(() => {
        this.loadDebugData();
        this.updateDebugStatus('Debug data refreshed', 'success');
      }, 1000);
    },

    /**
     * Show debug troubleshooting information.
     */
    showDebugTroubleshooting: function(details) {
      this.log('=== Debug Troubleshooting ===');
      this.log('Chart.js available: ' + details.chartAvailable);
      this.log('Chart.js version: ' + (details.chartVersion || 'unknown'));
      this.log('Timeout occurred: ' + details.timeout);
      this.log('Attempts made: ' + details.attempts);
    },

    /**
     * Update debug status display.
     */
    updateDebugStatus: function(message, type) {
      const statusEl = document.getElementById('debug-status');
      if (statusEl) {
        statusEl.innerHTML = message;
        statusEl.className = 'debug-status ' + type;
      }
      this.log('DEBUG STATUS: ' + message);
    },

    /**
     * Enhanced logging for debug console.
     */
    log: function(message) {
      const timestamp = new Date().toLocaleTimeString();
      const logMessage = '[' + timestamp + '] Debug Console: ' + message;
      
      console.log(logMessage);
      
      try {
        const debugEl = document.getElementById('debug-console-log');
        if (debugEl) {
          debugEl.textContent += logMessage + '\n';
          debugEl.scrollTop = debugEl.scrollHeight;
        }
      } catch (error) {
        console.error('Debug console logging error:', error);
      }
    }
  };

})(jQuery, Drupal, drupalSettings);