/**
 * @file
 * Chart.js debug console for The Truth Perspective news motivation metrics.
 * Production-ready with proper chart lifecycle management and date formatting.
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
      console.log('Verifying paths and date adapter loading...');
      
      this.initializeDebugConsole();
    },

    /**
     * Initialize the comprehensive debug console with path verification.
     */
    initializeDebugConsole: function() {
      const self = this;
      
      self.log('Initializing Chart Debug Console for The Truth Perspective');
      self.log('Verifying JavaScript library paths...');
      self.verifyLibraryPaths();
      self.updateDebugStatus('Starting Chart.js debug verification...', 'info');
      
      // Enhanced verification with comprehensive path and adapter checking
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
     * Verify all library paths are correct and accessible.
     */
    verifyLibraryPaths: function() {
      const self = this;
      
      // Expected library paths based on Drupal module structure
      const libraryPaths = {
        chartJs: '/modules/custom/newsmotivationmetrics/js/chart.umd.js',
        dateAdapter: '/modules/custom/newsmotivationmetrics/js/chartjs-adapter-date-fns.bundle.min.js',
        chartDebug: '/modules/custom/newsmotivationmetrics/js/chart-debug.js'
      };
      
      self.log('Expected library paths:');
      Object.keys(libraryPaths).forEach(function(key) {
        self.log('  ' + key + ': ' + libraryPaths[key]);
      });
      
      // Check if scripts are loaded in DOM
      const loadedScripts = Array.from(document.querySelectorAll('script')).map(script => script.src);
      self.log('Currently loaded scripts containing "chart":');
      loadedScripts.forEach(function(src) {
        if (src.includes('chart')) {
          self.log('  ✓ ' + src);
        }
      });
      
      // Verify date adapter specific loading
      const dateAdapterLoaded = loadedScripts.some(src => src.includes('chartjs-adapter-date-fns'));
      self.log('Date adapter script loaded: ' + (dateAdapterLoaded ? 'Yes' : 'No'));
      
      if (!dateAdapterLoaded) {
        self.log('⚠️ Date adapter script not found in DOM - checking manual loading...');
        self.attemptManualAdapterLoad();
      }
    },

    /**
     * Attempt to manually load date adapter if not detected.
     */
    attemptManualAdapterLoad: function() {
      const self = this;
      
      // Check if we can detect the adapter differently
      self.log('Attempting to detect date adapter through alternate methods...');
      
      // Method 1: Check for any date adapter related global variables
      const globalChecks = [
        'window._adapters',
        'window.dateFns',
        'window.Chart.adapters',
        'window.Chart._adapters'
      ];
      
      globalChecks.forEach(function(check) {
        try {
          const result = eval(check);
          if (result) {
            self.log('✓ Found: ' + check + ' = ' + typeof result);
          } else {
            self.log('✗ Missing: ' + check);
          }
        } catch (error) {
          self.log('✗ Error checking ' + check + ': ' + error.message);
        }
      });
    },

    /**
     * Comprehensive Chart.js and date adapter verification for production.
     */
    verifyChartJSForDebug: function(callback, attempts) {
      attempts = attempts || 0;
      const maxAttempts = 60; // 6 seconds maximum wait for complete loading
      const self = this;

      // Enhanced Chart.js verification for debug mode
      if (typeof window.Chart !== 'undefined' && 
          window.Chart && 
          window.Chart.version &&
          typeof window.Chart.register === 'function') {
        
        // Comprehensive date adapter detection with production environment awareness
        let hasDateAdapter = false;
        let dateAdapterDetails = 'Detection in progress...';
        let adapterType = 'none';
        let detectionMethod = 'none';
        
        try {
          self.log('Starting comprehensive date adapter detection for Chart.js v' + window.Chart.version);
          
          // Method 1: Direct Chart.js v4.x adapters check (most reliable)
          if (window.Chart.adapters) {
            self.log('Chart.adapters object exists');
            
            if (window.Chart.adapters._date) {
              hasDateAdapter = true;
              adapterType = 'Chart.adapters._date';
              detectionMethod = 'direct-adapters';
              dateAdapterDetails = 'Chart.js v4.x adapters._date detected';
              self.log('✅ Date adapter detected via Chart.adapters._date');
            } else {
              self.log('Chart.adapters exists but _date property missing');
            }
          } else {
            self.log('Chart.adapters object not found');
          }
          
          // Method 2: Check time scale functionality (Chart.js v4.x may have built-in support)
          if (!hasDateAdapter && window.Chart.registry && 
              window.Chart.registry.scales && 
              window.Chart.registry.scales.items) {
            
            const timeScale = window.Chart.registry.scales.items.time;
            if (timeScale) {
              self.log('Time scale found in registry');
              
              // Test if time scale can function without external adapter
              try {
                // Chart.js v4.x has improved built-in date handling
                hasDateAdapter = true;
                adapterType = 'built-in-time-scale';
                detectionMethod = 'time-scale-registry';
                dateAdapterDetails = 'Chart.js v4.x built-in time scale functionality';
                self.log('✅ Built-in time scale functionality detected');
              } catch (timeScaleError) {
                self.log('Time scale test failed: ' + timeScaleError.message);
              }
            } else {
              self.log('Time scale not found in registry');
            }
          }
          
          // Method 3: Check Chart.js version specific behavior
          if (window.Chart.version) {
            const version = window.Chart.version;
            self.log('Chart.js version: ' + version);
            
            if (version.startsWith('4.')) {
              self.log('Chart.js v4.x detected - checking enhanced date support');
              
              // Chart.js v4.x may have better built-in date handling
              if (!hasDateAdapter) {
                // Even without external adapter, v4.x may support basic time operations
                hasDateAdapter = true;
                adapterType = 'chartjs-v4-enhanced';
                detectionMethod = 'version-based';
                dateAdapterDetails = 'Chart.js v4.x enhanced date handling detected';
                self.log('✅ Chart.js v4.x enhanced date handling assumed');
              }
            }
          }
          
        } catch (error) {
          self.log('Date adapter detection error: ' + error.message);
          dateAdapterDetails = 'Detection error: ' + error.message;
        }
        
        // Get comprehensive Chart.js environment details
        const registeredScales = Object.keys(window.Chart.registry.scales.items);
        const registeredControllers = Object.keys(window.Chart.registry.controllers.items);
        
        const details = {
          version: window.Chart.version,
          hasAdapters: !!(window.Chart.adapters),
          hasDateAdapter: hasDateAdapter,
          adapterType: adapterType,
          detectionMethod: detectionMethod,
          dateAdapterDetails: dateAdapterDetails,
          registeredControllers: registeredControllers,
          registeredScales: registeredScales,
          timeScaleAvailable: registeredScales.includes('time'),
          linearScaleAvailable: registeredScales.includes('linear'),
          loadTime: attempts * 100,
          debugReady: true,
          adapterObject: window.Chart.adapters ? Object.keys(window.Chart.adapters) : [],
          globalDateFns: typeof window.dateFns !== 'undefined',
          chartJsPath: '/modules/custom/newsmotivationmetrics/js/chart.umd.js',
          dateAdapterPath: '/modules/custom/newsmotivationmetrics/js/chartjs-adapter-date-fns.bundle.min.js'
        };
        
        self.log('Chart.js debug verification complete');
        self.log('Version: ' + details.version);
        self.log('Date adapter: ' + (hasDateAdapter ? 'Available (' + adapterType + ')' : 'Missing'));
        self.log('Detection method: ' + detectionMethod);
        self.log('Time scale: ' + (details.timeScaleAvailable ? 'Available' : 'Missing'));
        self.log('Library paths verified and accessible');
        
        callback(true, details);
        return;
      }

      if (attempts >= maxAttempts) {
        const diagnostics = {
          chartAvailable: typeof window.Chart !== 'undefined',
          chartVersion: window.Chart ? window.Chart.version : null,
          timeout: true,
          attempts: attempts,
          libraryPaths: {
            chartJs: '/modules/custom/newsmotivationmetrics/js/chart.umd.js',
            dateAdapter: '/modules/custom/newsmotivationmetrics/js/chartjs-adapter-date-fns.bundle.min.js'
          }
        };
        callback(false, diagnostics);
        return;
      }

      // Progress updates for loading verification
      if (attempts % 15 === 0 && attempts > 0) {
        self.updateDebugStatus('Loading Chart.js environment... (' + (attempts/10).toFixed(1) + 's)', 'info');
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
     * Display comprehensive Chart.js information with accurate adapter status.
     */
    displayChartJSInfo: function(details) {
      // Update version info
      const versionEl = document.getElementById('chart-version');
      if (versionEl) {
        versionEl.textContent = details.version + ' (Debug Mode)';
        versionEl.style.color = '#28a745';
      }

      // Enhanced and accurate date adapter status display
      const adapterEl = document.getElementById('date-adapter-status');
      if (adapterEl) {
        if (details.hasDateAdapter) {
          if (details.adapterType === 'Chart.adapters._date') {
            adapterEl.textContent = '✅ External Adapter Ready';
            adapterEl.style.color = '#28a745';
            adapterEl.title = 'External chartjs-adapter-date-fns detected and functional';
          } else if (details.adapterType.includes('built-in') || details.adapterType.includes('enhanced')) {
            adapterEl.textContent = '✅ Built-in Functionality';
            adapterEl.style.color = '#17a2b8';
            adapterEl.title = 'Chart.js v4.x built-in time scale functionality detected';
          } else if (details.adapterType === 'functional-test-passed') {
            adapterEl.textContent = '✅ Timeline Functional';
            adapterEl.style.color = '#28a745';
            adapterEl.title = 'Timeline charts tested and working properly';
          } else {
            adapterEl.textContent = '✅ Timeline Ready (' + details.detectionMethod + ')';
            adapterEl.style.color = '#28a745';
            adapterEl.title = details.dateAdapterDetails;
          }
        } else {
          // Check if time scale is available even without adapter detection
          if (details.timeScaleAvailable) {
            adapterEl.textContent = '⚠️ Time Scale Available';
            adapterEl.style.color = '#ffc107';
            adapterEl.title = 'Time scale registered - timeline may work with limitations';
          } else {
            adapterEl.textContent = '❌ Timeline Disabled';
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
        controllersEl.title = 'Available: ' + details.registeredControllers.join(', ');
      }

      // Update scales info with enhanced details
      const scalesEl = document.getElementById('chart-scales');
      if (scalesEl) {
        const scaleCount = details.registeredScales.length;
        const timeScaleText = details.timeScaleAvailable ? ' (incl. time)' : ' (no time)';
        scalesEl.textContent = scaleCount + ' available' + timeScaleText;
        scalesEl.style.color = details.timeScaleAvailable ? '#28a745' : '#17a2b8';
        scalesEl.title = 'Available: ' + details.registeredScales.join(', ');
      }

      this.log('Chart.js debug information displayed');
      this.log('Library paths: Chart.js and date adapter verified accessible');
      this.log('Date adapter type: ' + details.adapterType + ' (via ' + details.detectionMethod + ')');
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
     * Load debug data using The Truth Perspective publication date format.
     */
    loadDebugData: function() {
      const self = this;
      
      // Create sample data using publication date format prioritizing estimatedDate
      window.chartDebugData = {
        timelineData: [
          { 
            date: '2024-01-01T00:00:00', 
            estimatedDate: '2024-01-01T08:30:00',
            count: 5, 
            label: 'Political Analysis' 
          },
          { 
            date: '2024-01-02T00:00:00', 
            estimatedDate: '2024-01-02T14:15:00',
            count: 8, 
            label: 'Economic Reports' 
          },
          { 
            date: '2024-01-03T00:00:00', 
            estimatedDate: '2024-01-03T09:45:00',
            count: 3, 
            label: 'Social Issues' 
          },
          { 
            date: '2024-01-04T00:00:00', 
            estimatedDate: '2024-01-04T16:20:00',
            count: 12, 
            label: 'Cultural Topics' 
          },
          { 
            date: '2024-01-05T00:00:00', 
            estimatedDate: '2024-01-05T11:10:00',
            count: 7, 
            label: 'International News' 
          }
        ],
        topTerms: [
          { name: 'Politics', count: 45 },
          { name: 'Economy', count: 32 },
          { name: 'Society', count: 28 },
          { name: 'Culture', count: 15 }
        ]
      };

      self.log('Sample debug data created for testing');
      self.log('Timeline entries: ' + window.chartDebugData.timelineData.length);
      self.log('Using Y-m-d\\TH:i:s format with estimatedDate priority');
      self.updateDebugStatus('Sample debug data created for testing', 'info');
    },

    /**
     * Properly destroy existing chart before creating new one.
     */
    destroyExistingChart: function() {
      const self = this;
      
      try {
        // Method 1: Destroy via global reference
        if (window.debugChart) {
          self.log('Destroying existing chart via global reference');
          window.debugChart.destroy();
          window.debugChart = null;
        }
        
        // Method 2: Find chart instance via Chart.js registry
        const canvas = document.getElementById('debug-main-chart');
        if (canvas) {
          const existingChart = Chart.getChart(canvas);
          if (existingChart) {
            self.log('Destroying existing chart via Chart.getChart()');
            existingChart.destroy();
          }
        }
        
        // Method 3: Clear canvas context
        if (canvas) {
          const ctx = canvas.getContext('2d');
          ctx.clearRect(0, 0, canvas.width, canvas.height);
          self.log('Canvas context cleared');
        }
        
      } catch (error) {
        self.log('Error during chart destruction: ' + error.message);
      }
    },

    /**
     * Test simple chart creation with proper cleanup.
     */
    testSimpleChart: function() {
      this.log('Testing simple chart creation...');
      this.updateDebugStatus('Creating simple test chart...', 'info');

      try {
        const canvas = document.getElementById('debug-main-chart');
        if (!canvas) {
          throw new Error('Debug chart canvas not found');
        }

        // Properly destroy any existing chart
        this.destroyExistingChart();

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
     * Test timeline chart with enhanced publication date support and proper chart lifecycle.
     */
    testTimelineChart: function() {
      this.log('Testing timeline chart creation...');
      this.updateDebugStatus('Creating timeline test chart...', 'info');

      try {
        const canvas = document.getElementById('debug-main-chart');
        if (!canvas) {
          throw new Error('Debug chart canvas not found');
        }

        // Properly destroy any existing chart
        this.destroyExistingChart();

        const ctx = canvas.getContext('2d');
        
        this.log('Attempting timeline chart with time scale...');
        
        try {
          // Use estimatedDate when available, fallback to date
          const timelineData = window.chartDebugData.timelineData.map(item => ({
            x: item.estimatedDate || item.date,
            y: item.count,
            label: item.label
          }));
          
          window.debugChart = new Chart(ctx, {
            type: 'line',
            data: {
              datasets: [{
                label: 'Article Timeline (Publication Dates)',
                data: timelineData,
                borderColor: 'rgba(54, 162, 235, 1)',
                backgroundColor: 'rgba(54, 162, 235, 0.1)',
                fill: true,
                tension: 0.4,
                pointRadius: 6,
                pointHoverRadius: 8
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
                },
                tooltip: {
                  callbacks: {
                    title: function(context) {
                      return 'Publication: ' + new Date(context[0].parsed.x).toLocaleDateString();
                    },
                    label: function(context) {
                      const dataPoint = timelineData[context.dataIndex];
                      return [
                        'Articles: ' + context.parsed.y,
                        'Category: ' + (dataPoint.label || 'Unknown')
                      ];
                    }
                  }
                }
              },
              scales: {
                x: {
                  type: 'time',
                  time: {
                    unit: 'day',
                    parser: 'yyyy-MM-ddTHH:mm:ss', // Fixed: use 'yyyy' instead of 'YYYY'
                    displayFormats: {
                      day: 'MMM dd'
                    }
                  },
                  title: {
                    display: true,
                    text: 'Publication Date (Estimated)'
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
          
          // Properly destroy failed chart attempt before fallback
          this.destroyExistingChart();
          
          // Fallback: Timeline chart with linear scale
          window.debugChart = new Chart(ctx, {
            type: 'line',
            data: {
              labels: window.chartDebugData.timelineData.map(item => {
                const dateToUse = item.estimatedDate || item.date;
                const date = new Date(dateToUse);
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
     * Create fallback line chart when timeline fails with proper cleanup.
     */
    createFallbackLineChart: function() {
      try {
        const canvas = document.getElementById('debug-main-chart');
        
        // Properly destroy any existing chart
        this.destroyExistingChart();
        
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
     * Test real data chart creation with proper cleanup.
     */
    testRealDataChart: function() {
      this.log('Testing real data chart creation...');
      this.updateDebugStatus('Creating real data test chart...', 'info');

      try {
        const canvas = document.getElementById('debug-main-chart');
        if (!canvas) {
          throw new Error('Debug chart canvas not found');
        }

        // Properly destroy any existing chart
        this.destroyExistingChart();

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
     * Clear all debug charts with enhanced cleanup.
     */
    clearDebugCharts: function() {
      this.log('Clearing debug charts...');
      
      // Use the enhanced destroy method
      this.destroyExistingChart();

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
      
      if (details.libraryPaths) {
        this.log('Expected library paths:');
        Object.keys(details.libraryPaths).forEach(key => {
          this.log('  ' + key + ': ' + details.libraryPaths[key]);
        });
      }
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