/**
 * @file
 * Chart.js Debug Console for The Truth Perspective
 * 
 * Provides debugging functionality for Chart.js environment detection,
 * chart creation testing, and comprehensive dataset debugging.
 * 
 * @version 1.3.2
 */

(function (Drupal, once) {
  'use strict';

  // Chart Debug Console Version
  const CHART_DEBUG_VERSION = '1.3.2';
  
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
    try {
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
        debugLog(`Updated display element ${elementId}: ${content}`, 'info');
      } else {
        debugLog(`Display element ${elementId} not found in DOM`, 'warning');
      }
    } catch (error) {
      debugLog(`Error updating display element ${elementId}: ${error.message}`, 'error');
    }
  }

  /**
   * Update environment display with comprehensive error handling
   */
  function updateEnvironmentDisplay(environment) {
    debugLog('Updating environment display with detected values...', 'info');
    
    try {
      // Update Chart.js version with proper status
      const versionText = environment.chartjs.available && environment.chartjs.version !== 'Not Available'
        ? `v${environment.chartjs.version} ✅` 
        : 'Not Available ❌';
      updateVersionDisplay('chartjs-version', versionText);
      
      // Update date adapter status
      const adapterText = environment.dateAdapter.available 
        ? `${environment.dateAdapter.method} ✅`
        : 'Not Available ❌';
      updateVersionDisplay('date-adapter-status', adapterText);
      
      // Update controllers with proper count display
      const controllersText = environment.chartjs.controllers > 0
        ? `${environment.chartjs.controllers} registered ✅`
        : environment.chartjs.available ? 'None detected ❌' : 'Chart.js not loaded ❌';
      updateVersionDisplay('controllers-count', controllersText);
      
      // Update scales with proper count display
      const scalesText = environment.chartjs.scales > 0
        ? `${environment.chartjs.scales} available ✅`
        : environment.chartjs.available ? 'None detected ❌' : 'Chart.js not loaded ❌';
      updateVersionDisplay('scales-count', scalesText);
      
      debugLog('Environment display updated successfully', 'success');
      
    } catch (error) {
      debugLog(`Error updating environment display: ${error.message}`, 'error');
    }
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
   * Robust canvas preparation - prioritize existing template containers
   */
  function prepareCanvas() {
    debugLog('Starting canvas preparation with template container priority...', 'info');
    
    try {
      // Step 1: Search for existing containers in order of preference
      let container = null;
      const containerSelectors = [
        '.chart-container',           // Primary designated container
        '.chart-testing-area',        // Template's chart testing area
        '.chart-test-container',      // Alternative naming
        '#chart-testing-area',        // ID-based selector
        '#chart-container',           // ID-based primary
        '.debug-chart-area',          // Debug-specific area
        '.chart-debug-content',       // Content area
        '.region-content .chart',     // Region-based chart area
      ];
      
      for (let selector of containerSelectors) {
        container = document.querySelector(selector);
        if (container) {
          debugLog(`Found existing template container: ${selector}`, 'info');
          break;
        } else {
          debugLog(`Container not found: ${selector}`, 'info');
        }
      }
      
      // Step 2: If no dedicated container found, create one in the template area
      if (!container) {
        debugLog('No dedicated chart container found - searching for template integration points...', 'warning');
        
        // Look for template-specific integration points
        const integrationSelectors = [
          '.chart-testing-section',    // Template section for charts
          '.debug-console-content',    // Console content area
          '.chart-debug-interface',    // Debug interface area
          '.page-content',             // Page content area
          '.region-content',           // Drupal region content
          'main',                      // Main content area
          '.content-wrapper',          // Content wrapper
          '#main-content'              // Main content ID
        ];
        
        let integrationPoint = null;
        for (let selector of integrationSelectors) {
          integrationPoint = document.querySelector(selector);
          if (integrationPoint) {
            debugLog(`Found template integration point: ${selector}`, 'info');
            break;
          }
        }
        
        if (!integrationPoint) {
          debugLog('Critical error: No suitable template integration point found', 'error');
          throw new Error('Cannot integrate with template - no suitable DOM parent available');
        }
        
        // Create container within the template structure
        container = document.createElement('div');
        container.className = 'chart-container chart-template-integrated';
        container.style.cssText = `
          width: 100%; 
          min-height: 400px; 
          margin: 15px 0; 
          padding: 20px;
          border: 1px solid #ddd; 
          border-radius: 6px;
          background: #ffffff;
          box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        `;
        
        // Insert at the beginning of the integration point (not at the end)
        integrationPoint.insertBefore(container, integrationPoint.firstChild);
        debugLog('Template-integrated chart container created successfully', 'success');
      }
      
      // Step 3: Clean up any existing canvas elements
      const existingCanvas = container.querySelector('#debug-main-chart') || 
                            document.getElementById('debug-main-chart');
      if (existingCanvas) {
        existingCanvas.remove();
        debugLog('Previous canvas element removed', 'info');
      }
      
      // Step 4: Create new canvas optimized for template integration
      const newCanvas = document.createElement('canvas');
      newCanvas.id = 'debug-main-chart';
      newCanvas.className = 'chart-debug-canvas';
      newCanvas.width = 800;
      newCanvas.height = 400;
      newCanvas.style.cssText = `
        max-width: 100%; 
        height: auto; 
        display: block; 
        margin: 10px auto;
        border: 1px solid #e1e1e1;
        border-radius: 4px;
        background: white;
      `;
      newCanvas.setAttribute('aria-label', 'Chart Debug Canvas');
      
      // Step 5: Append canvas to container
      container.appendChild(newCanvas);
      debugLog('Canvas element successfully integrated into template container', 'success');
      
      // Step 6: Verify canvas context
      const ctx = newCanvas.getContext('2d');
      if (!ctx) {
        debugLog('Canvas 2D context not available', 'error');
        throw new Error('Canvas 2D rendering context not supported');
      }
      
      debugLog('Canvas preparation completed with template integration', 'success');
      return newCanvas;
      
    } catch (error) {
      debugLog(`Canvas preparation failed: ${error.message}`, 'error');
      throw error;
    }
  }

  /**
   * Test simple bar chart
   */
  function testSimpleChart() {
    debugLog('Testing simple bar chart creation...', 'info');
    
    try {
      destroyExistingChart();
      const canvas = prepareCanvas();
      const ctx = canvas.getContext('2d');

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
    
    try {
      destroyExistingChart();
      const canvas = prepareCanvas();
      const ctx = canvas.getContext('2d');

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
        const canvas = prepareCanvas();
        const ctx = canvas.getContext('2d');
        
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
    
    try {
      destroyExistingChart();
      const canvas = prepareCanvas();
      const ctx = canvas.getContext('2d');

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
   * Enhanced clear charts function with template container support
   */
  function clearCharts() {
    debugLog('Clearing charts and resetting template container...', 'info');
    
    try {
      destroyExistingChart();
      
      // Find the container (prefer template containers)
      const containerSelectors = [
        '.chart-container',
        '.chart-testing-area', 
        '.chart-test-container',
        '#chart-testing-area',
        '#chart-container'
      ];
      
      let container = null;
      for (let selector of containerSelectors) {
        container = document.querySelector(selector);
        if (container) {
          debugLog(`Found container for clearing: ${selector}`, 'info');
          break;
        }
      }
      
      const canvas = document.getElementById('debug-main-chart');
      
      if (canvas) {
        canvas.remove();
        debugLog('Canvas element removed successfully', 'info');
      }
      
      if (container) {
        // Create minimal placeholder for template containers
        const placeholder = document.createElement('div');
        placeholder.className = 'chart-placeholder template-integrated';
        placeholder.innerHTML = `
          <div style="text-align: center; padding: 30px 15px; color: #666; background: #f9f9f9; border-radius: 4px;">
            <p style="margin: 0; font-size: 14px; color: #888;">
              Click a test button above to generate debug charts
            </p>
            <small style="font-size: 11px; color: #aaa; margin-top: 8px; display: block;">
              Chart Debug Console v${CHART_DEBUG_VERSION} ready
            </small>
          </div>
        `;
        
        container.appendChild(placeholder);
        debugLog('Template-friendly placeholder added', 'success');
      } else {
        debugLog('No container found during clear operation', 'warning');
      }
      
      debugLog('Charts cleared successfully with template integration', 'success');
      
    } catch (error) {
      debugLog(`Error during chart clearing: ${error.message}`, 'error');
    }
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
   * Initialize debug console with template integration priority
   */
  Drupal.behaviors.chartDebugConsole = {
    attach: function (context, settings) {
      once('chart-debug-init', 'body', context).forEach(function () {
        debugLog(`Initializing Chart Debug Console v${CHART_DEBUG_VERSION} with template integration...`, 'info');
        
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
    debugLog('Attaching event listeners to debug control buttons...', 'info');
    
    const simpleBtn = document.getElementById('test-simple-chart');
    if (simpleBtn) {
      simpleBtn.addEventListener('click', testSimpleChart);
      debugLog('Simple chart button listener attached', 'info');
    } else {
      debugLog('Simple chart button not found', 'warning');
    }

    const timelineBtn = document.getElementById('test-timeline-chart');
    if (timelineBtn) {
      timelineBtn.addEventListener('click', testTimelineChart);
      debugLog('Timeline chart button listener attached', 'info');
    } else {
      debugLog('Timeline chart button not found', 'warning');
    }

    const realDataBtn = document.getElementById('test-real-data-chart');
    if (realDataBtn) {
      realDataBtn.addEventListener('click', testRealDataChart);
      debugLog('Real data chart button listener attached', 'info');
    } else {
      debugLog('Real data chart button not found', 'warning');
    }

    const clearBtn = document.getElementById('clear-debug-charts');
    if (clearBtn) {
      clearBtn.addEventListener('click', clearCharts);
      debugLog('Clear charts button listener attached', 'info');
    } else {
      debugLog('Clear charts button not found', 'warning');
    }

    const refreshBtn = document.getElementById('refresh-debug-data');
    if (refreshBtn) {
      refreshBtn.addEventListener('click', refreshDebugData);
      debugLog('Refresh data button listener attached', 'info');
    } else {
      debugLog('Refresh data button not found', 'warning');
    }

    debugLog('Event listeners attachment completed', 'success');
  }

})(Drupal, once);