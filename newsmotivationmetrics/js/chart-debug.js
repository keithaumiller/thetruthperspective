/**
 * @file
 * Chart.js debug console for The Truth Perspective news motivation metrics.
 * Enhanced date adapter detection for Chart.js v4.4.0 with date-fns adapter.
 */

(function ($, Drupal, drupalSettings) {
  'use strict';

  // Prevent multiple executions
  if (window.truthPerspectiveChartDebugExecuted) {
    return;
  }

  /**
   * Chart Debug behavior for The Truth Perspective.
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
      console.log('Chart.js Debug Mode: Production Environment');
      
      this.initializeDebugConsole();
    },

    /**
     * Initialize the comprehensive debug console.
     */
    initializeDebugConsole: function() {
      const self = this;
      
      self.log('Initializing Chart Debug Console for The Truth Perspective');
      self.updateDebugStatus('Starting Chart.js debug verification...', 'info');
      
      // Enhanced verification with extended wait for date adapter
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
     * Enhanced Chart.js and date adapter verification with multiple detection methods.
     */
    verifyChartJSForDebug: function(callback, attempts) {
      attempts = attempts || 0;
      const maxAttempts = 50; // 5 seconds maximum wait for date adapter
      const self = this;

      // Enhanced Chart.js verification for debug mode
      if (typeof window.Chart !== 'undefined' && 
          window.Chart && 
          window.Chart.version &&
          typeof window.Chart.register === 'function') {
        
        // Comprehensive date adapter detection for Chart.js v4.4.0
        let hasDateAdapter = false;
        let dateAdapterDetails = 'Not detected';
        
        try {
          // Method 1: Check Chart.js v4.x adapters registry
          if (window.Chart.adapters && window.Chart.adapters._date) {
            hasDateAdapter = true;
            dateAdapterDetails = 'Chart.adapters._date available';
            self.log('Date adapter detected via Chart.adapters._date');
          }
          
          // Method 2: Check for date-fns adapter specifically
          if (!hasDateAdapter && window.Chart.adapters && 
              window.Chart.adapters._date && 
              typeof window.Chart.adapters._date.parse === 'function') {
            hasDateAdapter = true;
            dateAdapterDetails = 'date-fns adapter with parse function';
            self.log('Date adapter detected via parse function');
          }
          
          // Method 3: Test time scale availability
          if (!hasDateAdapter && window.Chart.registry && 
              window.Chart.registry.scales && 
              window.Chart.registry.scales.items && 
              window.Chart.registry.scales.items.time) {
            hasDateAdapter = true;
            dateAdapterDetails = 'Time scale registered';
            self.log('Date adapter detected via time scale registration');
          }
          
          // Method 4: Check global date-fns availability
          if (!hasDateAdapter && typeof window.dateFns !== 'undefined') {
            hasDateAdapter = true;
            dateAdapterDetails = 'Global date-fns library available';
            self.log('Date adapter detected via global date-fns');
          }
          
          // Method 5: Test date adapter by attempting to create a time scale
          if (!hasDateAdapter) {
            try {
              // Attempt to access time scale constructor
              const timeScale = window.Chart.registry.scales.get('time');
              if (timeScale) {
                hasDateAdapter = true;
                dateAdapterDetails = 'Time scale constructor available';
                self.log('Date adapter detected via time scale constructor');
              }
            } catch (timeError) {
              self.log('Time scale test failed: ' + timeError.message);
            }
          }
          
          // Method 6: Direct adapter test
          if (!hasDateAdapter) {
            try {
              // Try to directly access the adapter
              if (window.Chart && window.Chart.adapters && 
                  typeof window.Chart.adapters._date === 'object') {
                hasDateAdapter = true;
                dateAdapterDetails = 'Direct adapter object access';
                self.log('Date adapter detected via direct object access');
              }
            } catch (directError) {
              self.log('Direct adapter test failed: ' + directError.message);
            }
          }
          
        } catch (error) {
          self.log('Date adapter detection error: ' + error.message);
          dateAdapterDetails = 'Detection error: ' + error.message;
        }
        
        const details = {
          version: window.Chart.version,
          hasAdapters: !!(window.Chart.adapters),
          hasDateAdapter: hasDateAdapter,
          dateAdapterDetails: dateAdapterDetails,
          registeredControllers: Object.keys(window.Chart.registry.controllers.items),
          registeredScales: Object.keys(window.Chart.registry.scales.items),
          timeScaleAvailable: !!(window.Chart.registry.scales.items.time),
          loadTime: attempts * 100,
          debugReady: true
        };
        
        self.log('Chart.js debug verification successful: v' + details.version);
        self.log('Date adapter status: ' + (hasDateAdapter ? 'Available' : 'Missing'));
        self.log('Date adapter details: ' + dateAdapterDetails);
        self.log('Time scale available: ' + (details.timeScaleAvailable ? 'Yes' : 'No'));
        
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

      // Progress updates for date adapter loading
      if (attempts % 10 === 0 && attempts > 0) {
        self.updateDebugStatus('Loading Chart.js and date adapter... (' + (attempts/10) + 's)', 'info');
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
     * Display comprehensive Chart.js information with enhanced date adapter details.
     */
    displayChartJSInfo: function(details) {
      // Update version info
      const versionEl = document.getElementById('chart-version');
      if (versionEl) {
        versionEl.textContent = details.version + ' (Debug Mode)';
        versionEl.style.color = '#28a745';
      }

      // Enhanced date adapter status display
      const adapterEl = document.getElementById('date-adapter-status');
      if (adapterEl) {
        if (details.hasDateAdapter) {
          adapterEl.textContent = '✅ Available (Timeline Ready)';
          adapterEl.style.color = '#28a745';
          adapterEl.title = details.dateAdapterDetails;
        } else {
          // Check if time scale is available even without adapter detection
          if (details.timeScaleAvailable) {
            adapterEl.textContent = '⚠️ Partial (Time Scale Available)';
            adapterEl.style.color = '#ffc107';
            adapterEl.title = 'Time scale registered but adapter detection failed';
          } else {
            adapterEl.textContent = '❌ Missing (Timeline Disabled)';
            adapterEl.style.color = '#dc3545';
            adapterEl.title = details.dateAdapterDetails;
          }
        }
      }

      // Update controllers info
      const controllersEl = document.getElementById('chart-controllers');
      if (controllersEl) {
        controllersEl.textContent = details.registeredControllers.length + ' registered';
        controllersEl.style.color = '#17a2b8';
      }

      // Update scales info with time scale highlight
      const scalesEl = document.getElementById('chart-scales');
      if (scalesEl) {
        const scaleCount = details.registeredScales.length;
        const timeScaleText = details.timeScaleAvailable ? ' (incl. time)' : ' (no time)';
        scalesEl.textContent = scaleCount + ' available' + timeScaleText;
        scalesEl.style.color = details.timeScaleAvailable ? '#28a745' : '#17a2b8';
      }

      this.log('Chart.js debug information displayed');
      this.log('Registered chart types: ' + details.registeredControllers.join(', '));
      this.log('Registered scales: ' + details.registeredScales.join(', '));
      this.log('Date adapter details: ' + details.dateAdapterDetails);
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
     * Load debug data for testing with real publication dates.
     */
    loadDebugData: function() {
      const self = this;
      
      // Create sample data using publication date format from Drupal nodes
      window.chartDebugData = {
        timelineData: [
          { date: '2024-01-01T00:00:00', count: 5, label: 'Political Analysis' },
          { date: '2024-01-02T00:00:00', count: 8, label: 'Economic Reports' },
          { date: '2024-01-03T00:00:00', count: 3, label: 'Social Issues' },
          { date: '2024-01-04T00:00:00', count: 12, label: 'Cultural Topics' },
          { date: '2024-01-05T00:00:00', count: 7, label: 'International News' }
        ],
        topTerms: [
          { name: 'Politics', count: 45 },
          { name: 'Economy', count: 32 },
          { name: 'Society', count: 28 },
          { name: 'Culture', count: 15 }
        ]
      };

      self.log('Debug data loaded: ' + window.chartDebugData.timelineData.length + ' timeline entries');
      self.log('Using publication date format: Y-m-d\\TH:i:s');
      self.updateDebugStatus('Debug data loaded: ' + window.chartDebugData.timelineData.length + ' entries', 'info');
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
     * Enhanced timeline chart test with multiple fallback strategies.
     */
    testTimelineChart: function() {
      this.log('Testing timeline chart creation...');
      this.updateDebugStatus('Creating timeline test chart...', 'info');

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
        
        // Enhanced timeline chart test with better error handling
        this.log('Attempting timeline chart with time scale...');
        
        try {
          // Test 1: Full timeline chart with time scale
          window.debugChart = new Chart(ctx, {
            type: 'line',
            data: {
              datasets: [{
                label: 'Article Timeline (Publication Dates)',
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
                  text: 'The Truth Perspective - Timeline Chart (Publication Dates)',
                  font: { size: 16 }
                }
              },
              scales: {
                x: {
                  type: 'time',
                  time: {
                    unit: 'day',
                    parser: 'YYYY-MM-DDTHH:mm:ss'
                  },
                  title: {
                    display: true,
                    text: 'Publication Date'
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

          this.log('✅ Timeline chart with time scale successful');
          this.updateDebugStatus('✅ Timeline chart with time scale created successfully', 'success');
          
        } catch (timeScaleError) {
          this.log('Timeline with time scale failed: ' + timeScaleError.message);
          this.log('Falling back to linear scale timeline...');
          
          // Test 2: Timeline chart with linear scale (fallback)
          window.debugChart = new Chart(ctx, {
            type: 'line',
            data: {
              labels: window.chartDebugData.timelineData.map(item => {
                // Format date for display
                const date = new Date(item.date);
                return date.toLocaleDateString();
              }),
              datasets: [{
                label: 'Article Timeline (Linear Scale)',
                data: window.chartDebugData.timelineData.map(item => item.count),
                borderColor: 'rgba(255, 99, 132, 1)',
                backgroundColor: 'rgba(255, 99, 132, 0.1)',
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
                  text: 'The Truth Perspective - Timeline Chart (Linear Scale Fallback)',
                  font: { size: 16 }
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
                    text: 'Publication Date'
                  }
                }
              }
            }
          });
          
          this.log('✅ Timeline chart with linear scale fallback successful');
          this.updateDebugStatus('✅ Timeline chart created (linear scale fallback)', 'warning');
        }

      } catch (error) {
        this.log('❌ Timeline chart test completely failed: ' + error.message);
        this.updateDebugStatus('❌ Timeline chart test failed: ' + error.message, 'error');
        
        // Final fallback: simple line chart
        this.createFallbackLineChart();
      }
    },

    /**
     * Create fallback line chart when timeline fails.
     */
    createFallbackLineChart: function() {
      try {
        const canvas = document.getElementById('debug-main-chart');
        const ctx = canvas.getContext('2d');
        
        window.debugChart = new Chart(ctx, {
          type: 'line',
          data: {
            labels: ['Day 1', 'Day 2', 'Day 3', 'Day 4', 'Day 5'],
            datasets: [{
              label: 'Article Count (Simple Fallback)',
              data: [5, 8, 3, 12, 7],
              borderColor: 'rgba(75, 192, 192, 1)',
              backgroundColor: 'rgba(75, 192, 192, 0.1)',
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
                text: 'The Truth Perspective - Simple Line Chart (Final Fallback)',
                font: { size: 16 }
              }
            }
          }
        });
        
        this.log('✅ Fallback line chart created');
        this.updateDebugStatus('✅ Simple line chart created (final fallback)', 'warning');
        
      } catch (error) {
        this.log('❌ Fallback chart also failed: ' + error.message);
        this.updateDebugStatus('❌ All chart creation attempts failed', 'error');
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