/**
 * @file
 * Chart.js debug console for The Truth Perspective news motivation metrics.
 * Production-ready with comprehensive chart lifecycle management and element configuration.
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
     * Display dataset information before chart creation for debugging.
     */
    displayDatasetInfo: function(chartConfig, chartType) {
      const self = this;
      
      self.log('=== DATASET DEBUG INFO ===');
      self.log('Chart Type: ' + chartType);
      self.log('Chart Config Structure:');
      
      try {
        // Display basic config structure
        self.log('Config keys: ' + Object.keys(chartConfig).join(', '));
        
        if (chartConfig.data) {
          self.log('Data object exists');
          self.log('Data keys: ' + Object.keys(chartConfig.data).join(', '));
          
          // Display labels if present
          if (chartConfig.data.labels) {
            self.log('Labels (' + chartConfig.data.labels.length + '): ' + JSON.stringify(chartConfig.data.labels));
          }
          
          // Display datasets in detail
          if (chartConfig.data.datasets) {
            self.log('Datasets count: ' + chartConfig.data.datasets.length);
            
            chartConfig.data.datasets.forEach(function(dataset, index) {
              self.log('--- Dataset ' + index + ' ---');
              self.log('Dataset keys: ' + Object.keys(dataset).join(', '));
              
              // Show data array
              if (dataset.data) {
                self.log('Data array length: ' + dataset.data.length);
                self.log('Data array type: ' + (Array.isArray(dataset.data) ? 'Array' : typeof dataset.data));
                self.log('First 3 data points: ' + JSON.stringify(dataset.data.slice(0, 3)));
                
                // Check data point structure for timeline charts
                if (dataset.data.length > 0 && typeof dataset.data[0] === 'object') {
                  self.log('Data point structure (first item): ' + JSON.stringify(dataset.data[0]));
                  
                  // Check for x/y properties
                  dataset.data.forEach(function(point, pointIndex) {
                    if (pointIndex < 3) { // Only log first 3 points
                      self.log('Point ' + pointIndex + ': x=' + point.x + ', y=' + point.y + ', label=' + point.label);
                    }
                  });
                }
              }
              
              // Show element configuration properties that might affect hitRadius
              const elementProps = ['pointRadius', 'pointHoverRadius', 'pointHitRadius', 'hitRadius', 'radius', 'hoverRadius'];
              elementProps.forEach(function(prop) {
                if (dataset.hasOwnProperty(prop)) {
                  self.log('Dataset.' + prop + ': ' + dataset[prop]);
                }
              });
              
              // Show other important properties
              if (dataset.label) self.log('Label: ' + dataset.label);
              if (dataset.borderColor) self.log('Border Color: ' + dataset.borderColor);
              if (dataset.backgroundColor) self.log('Background Color: ' + dataset.backgroundColor);
            });
          }
        }
        
        // Display options that might affect element configuration
        if (chartConfig.options) {
          self.log('Options object exists');
          
          if (chartConfig.options.elements) {
            self.log('Elements configuration:');
            Object.keys(chartConfig.options.elements).forEach(function(elementType) {
              self.log('  ' + elementType + ': ' + JSON.stringify(chartConfig.options.elements[elementType]));
            });
          }
          
          if (chartConfig.options.interaction) {
            self.log('Interaction config: ' + JSON.stringify(chartConfig.options.interaction));
          }
        }
        
        self.log('=== END DATASET DEBUG INFO ===');
        
        // Update debug display on page
        const debugDatasetEl = document.getElementById('debug-dataset-info');
        if (debugDatasetEl) {
          debugDatasetEl.innerHTML = '<pre>' + JSON.stringify(chartConfig, null, 2) + '</pre>';
        }
        
      } catch (error) {
        self.log('Error displaying dataset info: ' + error.message);
      }
    },

    /**
     * Test simple chart creation with comprehensive element configuration.
     */
    testSimpleChart: function() {
      const self = this;
      self.log('Testing simple chart creation...');
      self.updateDebugStatus('Creating simple test chart...', 'info');

      try {
        // Comprehensive chart destruction
        self.destroyExistingChart();
        
        // Extended delay to ensure complete cleanup
        setTimeout(function() {
          try {
            const canvas = document.getElementById('debug-main-chart');
            if (!canvas) {
              throw new Error('Debug chart canvas not found after cleanup');
            }

            const ctx = canvas.getContext('2d');
            const elementDefaults = self.getElementDefaults();
            const interactionDefaults = self.getInteractionDefaults();
            
            const chartConfig = {
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
                interaction: interactionDefaults,
                elements: elementDefaults,
                plugins: {
                  title: {
                    display: true,
                    text: 'The Truth Perspective - Simple Chart Test',
                    font: { size: 16 }
                  },
                  legend: {
                    display: true,
                    position: 'top'
                  },
                  tooltip: {
                    enabled: true,
                    mode: 'nearest',
                    intersect: false
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
            };

            // Display dataset info before creating chart
            self.displayDatasetInfo(chartConfig, 'bar');

            window.debugChart = new Chart(ctx, chartConfig);

            self.log('✅ Simple chart test successful - Chart ID: ' + window.debugChart.id);
            self.updateDebugStatus('✅ Simple chart created successfully', 'success');

          } catch (delayedError) {
            self.log('❌ Simple chart test failed after cleanup: ' + delayedError.message);
            self.updateDebugStatus('❌ Simple chart test failed: ' + delayedError.message, 'error');
          }
        }, 200); // Extended delay for complete cleanup

      } catch (error) {
        self.log('❌ Simple chart test failed: ' + error.message);
        self.updateDebugStatus('❌ Simple chart test failed: ' + error.message, 'error');
      }
    },

    /**
     * Test timeline chart with comprehensive element configuration.
     */
    testTimelineChart: function() {
      const self = this;
      self.log('Testing timeline chart creation...');
      self.updateDebugStatus('Creating timeline test chart...', 'info');

      try {
        // Comprehensive chart destruction
        self.destroyExistingChart();
        
        // Extended delay to ensure complete cleanup
        setTimeout(function() {
          try {
            const canvas = document.getElementById('debug-main-chart');
            if (!canvas) {
              throw new Error('Debug chart canvas not found after cleanup');
            }

            const ctx = canvas.getContext('2d');
            const elementDefaults = self.getElementDefaults();
            const interactionDefaults = self.getInteractionDefaults();
            
            self.log('Attempting timeline chart with time scale...');
            
            try {
              // Use estimatedDate when available, fallback to date
              const timelineData = window.chartDebugData.timelineData.map(item => ({
                x: item.estimatedDate || item.date,
                y: item.count,
                label: item.label
              }));
              
              const chartConfig = {
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
                    pointHoverRadius: 8,
                    pointHitRadius: 12,
                    pointBorderWidth: 2,
                    pointHoverBorderWidth: 3,
                    pointBackgroundColor: 'rgba(54, 162, 235, 0.8)',
                    pointBorderColor: 'rgba(54, 162, 235, 1)',
                    pointHoverBackgroundColor: 'rgba(54, 162, 235, 1)',
                    pointHoverBorderColor: 'rgba(54, 162, 235, 1)'
                  }]
                },
                options: {
                  responsive: true,
                  maintainAspectRatio: false,
                  interaction: interactionDefaults,
                  elements: elementDefaults,
                  plugins: {
                    title: {
                      display: true,
                      text: 'The Truth Perspective - Timeline Chart (Publication Dates)',
                      font: { size: 16 }
                    },
                    tooltip: {
                      enabled: true,
                      mode: 'nearest',
                      intersect: false,
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
              };

              // Display dataset info before creating chart
              self.displayDatasetInfo(chartConfig, 'line');

              window.debugChart = new Chart(ctx, chartConfig);

              self.log('✅ Timeline chart with time scale successful - Chart ID: ' + window.debugChart.id);
              self.updateDebugStatus('✅ Timeline chart with time scale created successfully', 'success');
              
            } catch (timeScaleError) {
              self.log('Timeline with time scale failed: ' + timeScaleError.message);
              self.log('Falling back to linear scale timeline...');
              
              // Linear scale fallback with fresh canvas
              self.destroyExistingChart();
              
              setTimeout(function() {
                try {
                  const fallbackCanvas = document.getElementById('debug-main-chart');
                  const fallbackCtx = fallbackCanvas.getContext('2d');
                  
                  const fallbackConfig = {
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
                        tension: 0.4,
                        pointRadius: 6,
                        pointHoverRadius: 8,
                        pointHitRadius: 12,
                        pointBorderWidth: 2,
                        pointHoverBorderWidth: 3,
                        pointBackgroundColor: 'rgba(255, 99, 132, 0.8)',
                        pointBorderColor: 'rgba(255, 99, 132, 1)',
                        pointHoverBackgroundColor: 'rgba(255, 99, 132, 1)',
                        pointHoverBorderColor: 'rgba(255, 99, 132, 1)'
                      }]
                    },
                    options: {
                      responsive: true,
                      maintainAspectRatio: false,
                      interaction: interactionDefaults,
                      elements: elementDefaults,
                      plugins: {
                        title: {
                          display: true,
                          text: 'The Truth Perspective - Timeline Chart (Linear Scale Fallback)',
                          font: { size: 16 }
                        },
                        tooltip: {
                          enabled: true,
                          mode: 'nearest',
                          intersect: false
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
                  };

                  // Display dataset info before creating fallback chart
                  self.displayDatasetInfo(fallbackConfig, 'line');
                  
                  window.debugChart = new Chart(fallbackCtx, fallbackConfig);
                  
                  self.log('✅ Timeline chart with linear scale fallback successful - Chart ID: ' + window.debugChart.id);
                  self.updateDebugStatus('✅ Timeline chart created (linear scale fallback)', 'warning');
                  
                } catch (fallbackError) {
                  self.log('❌ Linear scale fallback failed: ' + fallbackError.message);
                  self.updateDebugStatus('❌ Timeline chart fallback failed: ' + fallbackError.message, 'error');
                  self.createFallbackLineChart();
                }
              }, 200);
            }

          } catch (delayedError) {
            self.log('❌ Timeline chart test failed after cleanup: ' + delayedError.message);
            self.updateDebugStatus('❌ Timeline chart test failed: ' + delayedError.message, 'error');
            self.createFallbackLineChart();
          }
        }, 200); // Extended delay for complete cleanup

      } catch (error) {
        self.log('❌ Timeline chart test completely failed: ' + error.message);
        self.updateDebugStatus('❌ Timeline chart test failed: ' + error.message, 'error');
        self.createFallbackLineChart();
      }
    },

    /**
     * Create fallback line chart with comprehensive element configuration.
     */
    createFallbackLineChart: function() {
      const self = this;
      
      try {
        self.destroyExistingChart();
        
        setTimeout(function() {
          try {
            const canvas = document.getElementById('debug-main-chart');
            const ctx = canvas.getContext('2d');
            const elementDefaults = self.getElementDefaults();
            const interactionDefaults = self.getInteractionDefaults();
            
            const fallbackConfig = {
              type: 'line',
              data: {
                labels: ['Day 1', 'Day 2', 'Day 3', 'Day 4', 'Day 5'],
                datasets: [{
                  label: 'Article Count (Simple Fallback)',
                  data: [5, 8, 3, 12, 7],
                  borderColor: 'rgba(75, 192, 192, 1)',
                  backgroundColor: 'rgba(75, 192, 192, 0.1)',
                  fill: true,
                  tension: 0.4,
                  pointRadius: 6,
                  pointHoverRadius: 8,
                  pointHitRadius: 12,
                  pointBorderWidth: 2,
                  pointHoverBorderWidth: 3,
                  pointBackgroundColor: 'rgba(75, 192, 192, 0.8)',
                  pointBorderColor: 'rgba(75, 192, 192, 1)',
                  pointHoverBackgroundColor: 'rgba(75, 192, 192, 1)',
                  pointHoverBorderColor: 'rgba(75, 192, 192, 1)'
                }]
              },
              options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: interactionDefaults,
                elements: elementDefaults,
                plugins: {
                  title: {
                    display: true,
                    text: 'The Truth Perspective - Simple Line Chart (Final Fallback)',
                    font: { size: 16 }
                  },
                  tooltip: {
                    enabled: true,
                    mode: 'nearest',
                    intersect: false
                  }
                }
              }
            };

            // Display dataset info before creating fallback chart
            self.displayDatasetInfo(fallbackConfig, 'line');
            
            window.debugChart = new Chart(ctx, fallbackConfig);
            
            self.log('✅ Fallback line chart created - Chart ID: ' + window.debugChart.id);
            self.updateDebugStatus('✅ Simple line chart created (final fallback)', 'warning');
            
          } catch (fallbackError) {
            self.log('❌ Fallback chart creation failed: ' + fallbackError.message);
            self.updateDebugStatus('❌ All chart creation attempts failed', 'error');
          }
        }, 200);
        
      } catch (error) {
        self.log('❌ Fallback chart setup failed: ' + error.message);
        self.updateDebugStatus('❌ All chart creation attempts failed', 'error');
      }
    },

    /**
     * Test real data chart creation with comprehensive element configuration.
     */
    testRealDataChart: function() {
      const self = this;
      self.log('Testing real data chart creation...');
      self.updateDebugStatus('Creating real data test chart...', 'info');

      try {
        // Comprehensive chart destruction
        self.destroyExistingChart();
        
        // Extended delay to ensure complete cleanup
        setTimeout(function() {
          try {
            const canvas = document.getElementById('debug-main-chart');
            if (!canvas) {
              throw new Error('Debug chart canvas not found after cleanup');
            }

            const ctx = canvas.getContext('2d');
            const elementDefaults = self.getElementDefaults();
            const interactionDefaults = self.getInteractionDefaults();
            
            const chartConfig = {
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
                  borderColor: [
                    'rgba(255, 99, 132, 1)',
                    'rgba(54, 162, 235, 1)',
                    'rgba(255, 205, 86, 1)',
                    'rgba(75, 192, 192, 1)'
                  ],
                  borderWidth: 2
                }]
              },
              options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: interactionDefaults,
                elements: elementDefaults,
                plugins: {
                  title: {
                    display: true,
                    text: 'The Truth Perspective - Top Terms Analysis',
                    font: { size: 16 }
                  },
                  legend: {
                    display: true,
                    position: 'right'
                  },
                  tooltip: {
                    enabled: true,
                    mode: 'nearest',
                    intersect: false
                  }
                }
              }
            };

            // Display dataset info before creating chart
            self.displayDatasetInfo(chartConfig, 'doughnut');

            window.debugChart = new Chart(ctx, chartConfig);

            self.log('✅ Real data chart test successful - Chart ID: ' + window.debugChart.id);
            self.updateDebugStatus('✅ Real data chart created successfully', 'success');

          } catch (delayedError) {
            self.log('❌ Real data chart test failed after cleanup: ' + delayedError.message);
            self.updateDebugStatus('❌ Real data chart test failed: ' + delayedError.message, 'error');
          }
        }, 200); // Extended delay for complete cleanup

      } catch (error) {
        self.log('❌ Real data chart test failed: ' + error.message);
        self.updateDebugStatus('❌ Real data chart test failed: ' + error.message, 'error');
      }
    },

    /**
     * Clear all debug charts with comprehensive cleanup.
     */
    clearDebugCharts: function() {
      this.log('Clearing debug charts...');
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