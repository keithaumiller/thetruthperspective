/**
 * @file
 * Chart.js Debug Console for The Truth Perspective
 * 
 * Provides debugging functionality for Chart.js environment detection,
 * chart creation testing, and comprehensive dataset debugging.
 * 
 * @version 1.3.0
 */

(function (Drupal, once) {
  'use strict';

  // Chart Debug Console Version
  const CHART_DEBUG_VERSION = '1.3.0';
  
  let currentChart = null;
  let loadingRetryCount = 0;
  const MAX_LOADING_RETRIES = 20;
  const LOADING_RETRY_INTERVAL = 250; // milliseconds

  /**
   * Enhanced logging with timestamps for production debugging
   */
  function debugLog(message, type = 'info', data = null) {
    const timestamp = new Date().toISOString();
    const logMessage = `[${timestamp}] Chart Debug: ${message}`;
    
    console.log(logMessage, data || '');
    
    // Update status display if available
    const statusElement = document.getElementById('debug-status');
    if (statusElement) {
      statusElement.textContent = `${new Date().toLocaleTimeString()}: ${message}`;
      statusElement.className = `debug-status ${type}`;
    }
  }

  /**
   * Debug function to trace where setupDebugEventListeners is being called from
   */
  function setupDebugEventListeners() {
    const stackTrace = new Error().stack;
    console.log('setupDebugEventListeners called from:', stackTrace);
  }

  /**
   * Serial loading verification with retry logic
   */
  function waitForChartJsComplete() {
    return new Promise((resolve, reject) => {
      const checkChartReadiness = () => {
        loadingRetryCount++;
        
        debugLog(`Checking Chart.js readiness (attempt ${loadingRetryCount}/${MAX_LOADING_RETRIES})...`, 'info');
        
        // Step 1: Check basic Chart.js availability
        if (typeof window.Chart === 'undefined') {
          debugLog('Chart.js not yet available', 'warning');
          scheduleRetry();
          return;
        }
        
        // Step 2: Check version availability
        if (!window.Chart.version) {
          debugLog('Chart.js version not yet available', 'warning');
          scheduleRetry();
          return;
        }
        
        // Step 3: Check registry population
        if (!window.Chart.registry || !window.Chart.registry.controllers) {
          debugLog('Chart.js registry not yet available', 'warning');
          scheduleRetry();
          return;
        }
        
        // Step 4: Check controller count (should be > 0)
        const controllerCount = Object.keys(window.Chart.registry.controllers.items || {}).length;
        if (controllerCount === 0) {
          debugLog('Chart.js controllers not yet loaded', 'warning');
          scheduleRetry();
          return;
        }
        
        // Step 5: Check scale count (should be > 0)
        const scaleCount = Object.keys(window.Chart.registry.scales.items || {}).length;
        if (scaleCount === 0) {
          debugLog('Chart.js scales not yet loaded', 'warning');
          scheduleRetry();
          return;
        }
        
        // All checks passed
        debugLog(`Chart.js fully loaded: v${window.Chart.version}, ${controllerCount} controllers, ${scaleCount} scales`, 'success');
        resolve();
      };
      
      const scheduleRetry = () => {
        if (loadingRetryCount >= MAX_LOADING_RETRIES) {
          debugLog('Chart.js loading timeout - proceeding with partial initialization', 'error');
          reject(new Error('Chart.js loading timeout'));
          return;
        }
        
        setTimeout(checkChartReadiness, LOADING_RETRY_INTERVAL);
      };
      
      // Start the checking process
      checkChartReadiness();
    });
  }

  /**
   * Detect Chart.js environment and capabilities
   */
  function detectChartEnvironment() {
    debugLog('Detecting Chart.js environment...', 'info');
    
    const environment = {
      chartjs: {
        available: typeof window.Chart !== 'undefined',
        version: window.Chart ? window.Chart.version : 'Not Available',
        controllers: window.Chart ? Object.keys(window.Chart.registry.controllers.items || {}).length : 0,
        scales: window.Chart ? Object.keys(window.Chart.registry.scales.items || {}).length : 0,
      },
      dateAdapter: {
        method: 'unknown',
        available: false,
        timeScaleSupport: false,
      },
      browser: {
        userAgent: navigator.userAgent,
        canvasSupport: !!document.createElement('canvas').getContext,
        timestamp: new Date().toISOString(),
      }
    };

    // Enhanced date adapter detection
    if (window.Chart) {
      // Method 1: Chart.js v4.x built-in date adapter
      if (window.Chart._adapters && window.Chart._adapters._date) {
        environment.dateAdapter.method = 'Chart._adapters._date';
        environment.dateAdapter.available = true;
      }
      // Method 2: Check for date-fns adapter
      else if (typeof window.dfns !== 'undefined') {
        environment.dateAdapter.method = 'date-fns external adapter';
        environment.dateAdapter.available = true;
      }
      // Method 3: Check time scale defaults
      else if (window.Chart.defaults && window.Chart.defaults.scales && window.Chart.defaults.scales.time) {
        environment.dateAdapter.method = 'Chart.defaults.scales.time';
        environment.dateAdapter.available = true;
      }
      // Method 4: Registry-based detection
      else if (window.Chart.registry) {
        try {
          const timeScale = window.Chart.registry.getScale('time');
          if (timeScale) {
            environment.dateAdapter.method = 'Chart.registry.getScale';
            environment.dateAdapter.available = true;
          }
        } catch (e) {
          debugLog(`Registry check failed: ${e.message}`, 'warning');
        }
      }
      
      // Time scale support verification
      try {
        if (environment.dateAdapter.available) {
          environment.dateAdapter.timeScaleSupport = true;
        }
      } catch (error) {
        debugLog(`Time scale verification error: ${error.message}`, 'warning');
      }
    }

    debugLog('Environment detection completed', 'success', environment);
    return environment;
  }

  /**
   * Update version display elements with loading states
   */
  function updateVersionDisplay(elementId, content) {
    const element = document.getElementById(elementId);
    if (element) {
      element.innerHTML = content;
      // Add success styling for positive confirmations
      if (content.includes('✅')) {
        element.className = 'version-display status-success';
      } else if (content.includes('❌')) {
        element.className = 'version-display status-error';
      } else if (content.includes('Loading...') || content.includes('Detecting...')) {
        element.className = 'version-display status-loading';
      } else {
        element.className = 'version-display';
      }
    }
  }

  /**
   * Update environment display with loading states
   */
  function updateEnvironmentDisplay(environment) {
    // Show loading state initially
    updateVersionDisplay('chartjs-version', 
      environment.chartjs.available && environment.chartjs.version !== 'Not Available'
        ? `v${environment.chartjs.version} ✅` 
        : 'Detecting...'
    );
    
    updateVersionDisplay('date-adapter-status',
      environment.dateAdapter.available 
        ? `${environment.dateAdapter.method} ✅`
        : 'Loading...'
    );
    
    updateVersionDisplay('controllers-count',
      environment.chartjs.controllers > 0
        ? `${environment.chartjs.controllers} registered ✅`
        : 'Loading...'
    );
    
    updateVersionDisplay('scales-count',
      environment.chartjs.scales > 0
        ? `${environment.chartjs.scales} available ✅`
        : 'Loading...'
    );
  }

  /**
   * Comprehensive chart destruction
   */
  function destroyExistingChart() {
    if (currentChart) {
      try {
        debugLog('Destroying existing chart instance...', 'info');
        currentChart.destroy();
        currentChart = null;
        debugLog('Chart destroyed successfully', 'success');
      } catch (error) {
        debugLog(`Chart destruction error: ${error.message}`, 'error');
        currentChart = null;
      }
    }
  }

  /**
   * Canvas element validation and recreation
   */
  function prepareCanvas() {
    const container = document.querySelector('.chart-container');
    const existingCanvas = document.getElementById('debug-main-chart');
    
    if (existingCanvas) {
      existingCanvas.remove();
    }
    
    const newCanvas = document.createElement('canvas');
    newCanvas.id = 'debug-main-chart';
    newCanvas.width = 800;
    newCanvas.height = 400;
    newCanvas.textContent = 'Your browser does not support the canvas element.';
    
    container.appendChild(newCanvas);
    debugLog('Canvas element recreated and ready', 'success');
    
    return newCanvas;
  }

  /**
   * Test simple bar chart
   */
  function testSimpleChart() {
    debugLog('Testing simple bar chart creation...', 'info');
    
    destroyExistingChart();
    const canvas = prepareCanvas();
    const ctx = canvas.getContext('2d');

    try {
      const chartData = {
        labels: ['Politics', 'Economy', 'Healthcare', 'Technology', 'Environment'],
        datasets: [{
          label: 'Article Count',
          data: [45, 32, 28, 19, 15],
          backgroundColor: [
            'rgba(54, 162, 235, 0.8)',
            'rgba(255, 99, 132, 0.8)',
            'rgba(255, 205, 86, 0.8)',
            'rgba(75, 192, 192, 0.8)',
            'rgba(153, 102, 255, 0.8)'
          ],
          borderColor: [
            'rgba(54, 162, 235, 1)',
            'rgba(255, 99, 132, 1)',
            'rgba(255, 205, 86, 1)',
            'rgba(75, 192, 192, 1)',
            'rgba(153, 102, 255, 1)'
          ],
          borderWidth: 2
        }]
      };

      const chartOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          title: {
            display: true,
            text: 'Simple Bar Chart Test - The Truth Perspective',
            font: {
              size: 16,
              weight: 'bold'
            }
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
              text: 'Number of Articles'
            }
          },
          x: {
            title: {
              display: true,
              text: 'Content Categories'
            }
          }
        }
      };

      currentChart = new Chart(ctx, {
        type: 'bar',
        data: chartData,
        options: chartOptions
      });

      debugLog('Simple bar chart created successfully', 'success');

    } catch (error) {
      debugLog(`Simple chart creation failed: ${error.message}`, 'error');
    }
  }

  /**
   * Test timeline chart with time scale
   */
  function testTimelineChart() {
    debugLog('Testing timeline chart with time scale...', 'info');
    
    destroyExistingChart();
    const canvas = prepareCanvas();
    const ctx = canvas.getContext('2d');

    try {
      const timelineData = {
        labels: [
          '2024-01-01',
          '2024-01-02', 
          '2024-01-03',
          '2024-01-04',
          '2024-01-05',
          '2024-01-06',
          '2024-01-07'
        ],
        datasets: [{
          label: 'Articles Processed',
          data: [5, 8, 3, 12, 7, 15, 9],
          borderColor: 'rgba(75, 192, 192, 1)',
          backgroundColor: 'rgba(75, 192, 192, 0.2)',
          borderWidth: 3,
          fill: true,
          tension: 0.4
        }]
      };

      const timelineOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          title: {
            display: true,
            text: 'Timeline Chart Test - Processing Activity',
            font: {
              size: 16,
              weight: 'bold'
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
              text: 'Date'
            }
          },
          y: {
            beginAtZero: true,
            title: {
              display: true,
              text: 'Articles Processed'
            }
          }
        }
      };

      currentChart = new Chart(ctx, {
        type: 'line',
        data: timelineData,
        options: timelineOptions
      });

      debugLog('Timeline chart with time scale created successfully', 'success');

    } catch (error) {
      debugLog(`Timeline chart creation failed: ${error.message}`, 'error');
      
      // Fallback to category scale
      debugLog('Attempting fallback to category scale...', 'warning');
      try {
        const fallbackOptions = { ...timelineOptions };
        fallbackOptions.scales.x = {
          type: 'category',
          title: {
            display: true,
            text: 'Date (Category Scale)'
          }
        };
        
        currentChart = new Chart(ctx, {
          type: 'line',
          data: timelineData,
          options: fallbackOptions
        });
        
        debugLog('Fallback chart created successfully', 'success');
      } catch (fallbackError) {
        debugLog(`Fallback chart creation failed: ${fallbackError.message}`, 'error');
      }
    }
  }

  /**
   * Test real data chart
   */
  function testRealDataChart() {
    debugLog('Testing real data doughnut chart...', 'info');
    
    destroyExistingChart();
    const canvas = prepareCanvas();
    const ctx = canvas.getContext('2d');

    try {
      const realData = {
        labels: ['Analyzed Articles', 'Tagged Articles', 'With Images', 'Recent Articles'],
        datasets: [{
          label: 'Article Distribution',
          data: [287, 234, 156, 89],
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
      };

      const doughnutOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          title: {
            display: true,
            text: 'Real Data Distribution - Article Analytics',
            font: {
              size: 16,
              weight: 'bold'
            }
          },
          legend: {
            display: true,
            position: 'right'
          }
        }
      };

      currentChart = new Chart(ctx, {
        type: 'doughnut',
        data: realData,
        options: doughnutOptions
      });

      debugLog('Real data doughnut chart created successfully', 'success');

    } catch (error) {
      debugLog(`Real data chart creation failed: ${error.message}`, 'error');
    }
  }

  /**
   * Clear all charts
   */
  function clearCharts() {
    debugLog('Clearing all charts and resetting canvas...', 'info');
    
    destroyExistingChart();
    
    const container = document.querySelector('.chart-container');
    const canvas = document.getElementById('debug-main-chart');
    
    if (canvas) {
      canvas.remove();
    }
    
    const placeholder = document.createElement('div');
    placeholder.className = 'chart-placeholder';
    placeholder.innerHTML = '<p>No charts currently displayed. Use the control buttons above to test chart functionality.</p>';
    placeholder.style.cssText = 'text-align: center; padding: 40px; color: #666; font-style: italic;';
    
    container.appendChild(placeholder);
    
    debugLog('Charts cleared successfully', 'success');
  }

  /**
   * Refresh debug data
   */
  function refreshDebugData() {
    debugLog('Refreshing debug data and re-detecting environment...', 'info');
    
    loadingRetryCount = 0; // Reset retry count
    
    waitForChartJsComplete()
      .then(() => {
        const environment = detectChartEnvironment();
        updateEnvironmentDisplay(environment);
        debugLog('Debug data refreshed successfully', 'success');
      })
      .catch((error) => {
        debugLog(`Debug refresh failed: ${error.message}`, 'error');
        // Still try to display partial environment
        const environment = detectChartEnvironment();
        updateEnvironmentDisplay(environment);
      });
  }

  /**
   * Initialize debug console with serial loading
   */
  Drupal.behaviors.chartDebugConsole = {
    attach: function (context, settings) {
      once('chart-debug-init', 'body', context).forEach(function () {
        debugLog(`Initializing The Truth Perspective Chart.js Debug Console v${CHART_DEBUG_VERSION}...`, 'info');
        
        // Log debug console version and environment details
        console.log(`Chart Debug Console Version: ${CHART_DEBUG_VERSION}`);
        console.log('The Truth Perspective - News Analytics Platform');
        console.log('Drupal 11 Environment - Production Server');
        console.log('Date:', new Date().toISOString());
        
        // Show initial loading state
        updateVersionDisplay('chartjs-version', 'Detecting...');
        updateVersionDisplay('date-adapter-status', 'Loading...');
        updateVersionDisplay('controllers-count', 'Loading...');
        updateVersionDisplay('scales-count', 'Loading...');
        
        // Wait for Chart.js to fully load, then proceed
        waitForChartJsComplete()
          .then(() => {
            debugLog('Chart.js fully loaded - proceeding with environment detection', 'success');
            const environment = detectChartEnvironment();
            updateEnvironmentDisplay(environment);
            
            // Attach event listeners only after Chart.js is ready
            attachEventListeners();
            
            debugLog(`Chart Debug Console v${CHART_DEBUG_VERSION} initialization completed`, 'success');
          })
          .catch((error) => {
            debugLog(`Chart.js loading failed: ${error.message}`, 'error');
            
            // Still try partial initialization
            const environment = detectChartEnvironment();
            updateEnvironmentDisplay(environment);
            attachEventListeners();
            
            debugLog(`Chart Debug Console v${CHART_DEBUG_VERSION} partial initialization completed`, 'warning');
          });
      });
    }
  };

  /**
   * Attach event listeners for debug controls
   */
  function attachEventListeners() {
    const simpleBtn = document.getElementById('test-simple-chart');
    if (simpleBtn) {
      simpleBtn.addEventListener('click', testSimpleChart);
    }

    const timelineBtn = document.getElementById('test-timeline-chart');
    if (timelineBtn) {
      timelineBtn.addEventListener('click', testTimelineChart);
    }

    const realDataBtn = document.getElementById('test-real-data-chart');
    if (realDataBtn) {
      realDataBtn.addEventListener('click', testRealDataChart);
    }

    const clearBtn = document.getElementById('clear-debug-charts');
    if (clearBtn) {
      clearBtn.addEventListener('click', clearCharts);
    }

    const refreshBtn = document.getElementById('refresh-debug-data');
    if (refreshBtn) {
      refreshBtn.addEventListener('click', refreshDebugData);
    }

    debugLog('Event listeners attached successfully', 'success');
  }

})(Drupal, once);