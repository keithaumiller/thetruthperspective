/**
 * Chart debug behavior for The Truth Perspective analytics development.
 * Uses local Chart.js files for reliable loading and debugging.
 */

(function ($, Drupal, drupalSettings) {
  'use strict';

  /**
   * Debug-specific chart behavior for The Truth Perspective metrics.
   */
  Drupal.behaviors.newsMotivationMetricsChartDebug = {
    attach: function (context, settings) {
      // Only initialize once per page load
      if (context !== document) {
        return;
      }

      console.log('=== The Truth Perspective Chart Debug Mode Activated ===');
      console.log('Debug settings:', settings.newsmotivationmetrics);

      // Store chart data globally for access throughout debug session
      window.chartDebugData = settings.newsmotivationmetrics || {};

      // Initialize debug environment with enhanced Chart.js detection
      this.initializeDebugMode();
    },

    /**
     * Initialize debug-specific functionality with robust Chart.js library detection.
     */
    initializeDebugMode: function() {
      const self = this;
      
      // Wait for DOM to be ready before starting chart operations
      if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
          self.setupDebugEnvironment();
        });
      } else {
        self.setupDebugEnvironment();
      }
    },

    /**
     * Set up debug environment with enhanced Chart.js detection.
     */
    setupDebugEnvironment: function() {
      const self = this;
      
      self.updateDebugStatus('Initializing The Truth Perspective chart system with local files...', 'info');
      
      // Enhanced Chart.js detection with multiple attempts
      self.waitForChartJS(function(success) {
        if (success) {
          console.log('Chart.js successfully loaded:', {
            version: Chart.version,
            available: typeof Chart !== 'undefined',
            hasAdapters: !!(Chart.adapters),
            hasDateAdapter: !!(Chart.adapters && Chart.adapters._date)
          });
          
          self.updateVersionInfo();
          self.setupDebugEventListeners();
          self.validateTimelineData();
          
          // Auto-start with real data test after libraries load
          setTimeout(function() {
            self.testRealTimelineData();
          }, 500);
        } else {
          console.error('Chart.js failed to load - checking alternatives');
          self.updateDebugStatus('Chart.js library failed to load - check file accessibility', 'error');
          self.showLibraryErrorMessage();
          
          // Still validate data and setup listeners even without charts
          self.validateTimelineData();
          self.setupDebugEventListeners();
        }
      });
    },

    /**
     * Enhanced Chart.js detection with better error handling.
     */
    waitForChartJS: function(callback, attempts) {
      attempts = attempts || 0;
      const maxAttempts = 100; // 10 seconds with 100ms intervals
      const self = this;
      
      // Check if Chart.js is available and properly initialized
      if (typeof Chart !== 'undefined' && Chart.version) {
        console.log('Chart.js detected successfully:', Chart.version);
        
        // Check for date adapter availability
        if (Chart.adapters && Chart.adapters._date) {
          console.log('Date adapter available');
          callback(true);
        } else {
          console.warn('Chart.js loaded but date adapter missing - will use simple charts');
          callback(true);
        }
        return;
      }
      
      if (attempts >= maxAttempts) {
        console.error('Chart.js failed to load after', maxAttempts, 'attempts (10 seconds)');
        console.log('Chart.js debug info:', {
          chartDefined: typeof Chart !== 'undefined',
          chartVersion: typeof Chart !== 'undefined' ? Chart.version : 'undefined',
          windowChart: !!window.Chart,
          documentReady: document.readyState
        });
        callback(false);
        return;
      }
      
      // Update status every 20 attempts (2 seconds)
      if (attempts % 20 === 0) {
        const seconds = Math.floor(attempts/10);
        self.updateDebugStatus(`Waiting for Chart.js to load... (${seconds}s)`, 'info');
        console.log(`Chart.js load attempt ${attempts}, Chart defined:`, typeof Chart !== 'undefined');
      }
      
      setTimeout(function() {
        self.waitForChartJS(callback, attempts + 1);
      }, 100);
    },

    /**
     * Update Chart.js version and date adapter information with enhanced detection.
     */
    updateVersionInfo: function() {
      const versionEl = document.getElementById('chartjs-version');
      const adapterEl = document.getElementById('date-adapter-status');
      
      if (versionEl) {
        if (typeof Chart !== 'undefined' && Chart.version) {
          const version = Chart.version;
          versionEl.textContent = version + ' (Local)';
          versionEl.style.color = '#28a745';
          console.log('Chart.js version confirmed:', version);
        } else {
          versionEl.textContent = 'Not Loaded';
          versionEl.style.color = '#dc3545';
          console.error('Chart.js version check failed');
        }
      }
      
      if (adapterEl) {
        if (typeof Chart !== 'undefined' && Chart.adapters && Chart.adapters._date) {
          adapterEl.textContent = 'Available (Local)';
          adapterEl.style.color = '#28a745';
          console.log('Date adapter confirmed available');
          this.updateDebugStatus('Chart.js and date adapter loaded successfully', 'success');
        } else if (typeof Chart !== 'undefined') {
          adapterEl.textContent = 'Missing - Simple Mode';
          adapterEl.style.color = '#ffc107';
          console.warn('Date adapter not available - using simple charts');
          this.updateDebugStatus('Chart.js loaded but date adapter missing - using simple mode', 'warning');
        } else {
          adapterEl.textContent = 'Chart.js Not Loaded';
          adapterEl.style.color = '#dc3545';
          console.error('Chart.js not available for adapter check');
        }
      }
    },

    /**
     * Validate timeline data structure for The Truth Perspective metrics.
     */
    validateTimelineData: function() {
      const data = window.chartDebugData;
      
      if (!data.timelineData || !Array.isArray(data.timelineData)) {
        this.updateDebugStatus('No timeline data available in debug session', 'warning');
        return false;
      }
      
      if (!data.topTerms || !Array.isArray(data.topTerms)) {
        this.updateDebugStatus('No taxonomy terms available in debug session', 'warning');
        return false;
      }
      
      // Enhanced data structure validation
      const validTerms = data.timelineData.filter(term => 
        term.term_id && term.term_name && Array.isArray(term.data)
      );
      
      const nonEmptyTerms = validTerms.filter(term => 
        term.data.some(point => point.count > 0)
      );
      
      console.log('Timeline data validation results:', {
        totalTerms: data.timelineData.length,
        validTerms: validTerms.length,
        termsWithData: nonEmptyTerms.length,
        topTermsAvailable: data.topTerms.length
      });
      
      if (nonEmptyTerms.length === 0) {
        this.updateDebugStatus('Timeline data contains no non-zero values - check data processing', 'warning');
        return false;
      }
      
      this.updateDebugStatus('Timeline data validated: ' + nonEmptyTerms.length + ' terms with data', 'success');
      return true;
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
          console.log('User initiated: Testing simple chart...');
          self.testSimpleChart();
        });
      }
      
      // Test date chart button
      const testDateBtn = document.getElementById('test-date');
      if (testDateBtn) {
        testDateBtn.addEventListener('click', function() {
          console.log('User initiated: Testing date-based chart...');
          self.testDateChart();
        });
      }
      
      // Update chart button (use real data)
      const updateBtn = document.getElementById('update-chart');
      if (updateBtn) {
        updateBtn.addEventListener('click', function() {
          console.log('User initiated: Updating chart with selected terms...');
          self.updateRealChart();
        });
      }
      
      // Reset chart button
      const resetBtn = document.getElementById('reset-chart');
      if (resetBtn) {
        resetBtn.addEventListener('click', function() {
          console.log('User initiated: Resetting chart to top 5 terms...');
          self.resetChart();
        });
      }
      
      // Clear chart button
      const clearBtn = document.getElementById('clear-chart');
      if (clearBtn) {
        clearBtn.addEventListener('click', function() {
          console.log('User initiated: Clearing chart display...');
          self.clearChart();
        });
      }
      
      console.log('Debug event listeners configured successfully');
    },

    /**
     * Test real timeline data from The Truth Perspective system.
     */
    testRealTimelineData: function() {
      if (!this.validateTimelineData()) {
        this.updateDebugStatus('Cannot test real data - validation failed', 'error');
        return;
      }
      
      this.updateDebugStatus('Auto-testing with real Truth Perspective timeline data...', 'info');
      
      // Get terms with actual data points for automatic testing
      const data = window.chartDebugData;
      const termsWithData = data.timelineData.filter(term => 
        term.data.some(point => point.count > 0)
      ).slice(0, 5); // Top 5 terms with data
      
      if (termsWithData.length === 0) {
        this.updateDebugStatus('No terms have data points to display automatically', 'warning');
        return;
      }
      
      // Pre-select these terms in the selector for user convenience
      const selector = document.getElementById('term-selector');
      if (selector) {
        // Clear all selections
        for (let option of selector.options) {
          option.selected = false;
        }
        
        // Select terms with data
        termsWithData.forEach(term => {
          const option = selector.querySelector(`option[value="${term.term_id}"]`);
          if (option) {
            option.selected = true;
          }
        });
      }
      
      // Create chart with real data
      this.updateRealChart();
    },

    /**
     * Test simple chart for basic Chart.js verification.
     */
    testSimpleChart: function() {
      this.updateDebugStatus('Testing simple chart with local Chart.js...', 'info');
      
      if (typeof Chart === 'undefined') {
        this.updateDebugStatus('Chart.js not available - check library loading', 'error');
        console.error('Chart.js undefined during simple chart test');
        return;
      }
      
      const canvas = document.getElementById('taxonomy-timeline-chart');
      if (!canvas) {
        this.updateDebugStatus('Canvas element not found in DOM', 'error');
        return;
      }
      
      try {
        // Destroy existing chart instance
        if (window.debugChart) {
          window.debugChart.destroy();
          console.log('Destroyed previous chart instance');
        }
        
        const ctx = canvas.getContext('2d');
        
        // Use data that resembles Truth Perspective analytics
        const simpleData = {
          labels: ['Aug 1', 'Aug 2', 'Aug 3', 'Aug 4', 'Aug 5', 'Aug 6'],
          datasets: [{
            label: 'Test Data - Simple Mode',
            data: [22, 15, 18, 20, 12, 28],
            borderColor: '#007bff',
            backgroundColor: 'rgba(0, 123, 255, 0.1)',
            tension: 0.2,
            fill: true,
            pointRadius: 4,
            pointHoverRadius: 6,
            pointBackgroundColor: '#007bff',
            pointBorderColor: '#ffffff',
            pointBorderWidth: 2
          }]
        };
        
        window.debugChart = new Chart(ctx, {
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
                  text: 'Article Count'
                }
              },
              x: {
                title: {
                  display: true,
                  text: 'Time Period'
                }
              }
            },
            plugins: {
              title: {
                display: true,
                text: 'Simple Chart Test - Local Chart.js'
              }
            }
          }
        });
        
        this.updateDebugStatus('Simple chart created successfully - Chart.js working', 'success');
        console.log('Simple chart instance created successfully');
        
      } catch (error) {
        this.updateDebugStatus('Simple chart creation failed: ' + error.message, 'error');
        console.error('Simple chart error details:', error);
      }
    },

    /**
     * Test date-based chart functionality.
     */
    testDateChart: function() {
      this.updateDebugStatus('Testing date-based chart...', 'info');
      
      if (typeof Chart === 'undefined') {
        this.updateDebugStatus('Chart.js not available for date chart test', 'error');
        return;
      }
      
      // If no date adapter, fall back to simple chart
      if (!Chart.adapters || !Chart.adapters._date) {
        this.updateDebugStatus('Date adapter not available - using simple chart instead', 'warning');
        this.testSimpleChart();
        return;
      }
      
      const canvas = document.getElementById('taxonomy-timeline-chart');
      if (!canvas) {
        this.updateDebugStatus('Canvas element not found', 'error');
        return;
      }
      
      try {
        // Destroy existing chart
        if (window.debugChart) {
          window.debugChart.destroy();
        }
        
        const ctx = canvas.getContext('2d');
        const today = new Date();
        
        const dateData = {
          datasets: [{
            label: 'Date-based Test Data',
            data: [
              { x: new Date(today.getTime() - 5*24*60*60*1000).toISOString().split('T')[0], y: 22 },
              { x: new Date(today.getTime() - 4*24*60*60*1000).toISOString().split('T')[0], y: 15 },
              { x: new Date(today.getTime() - 3*24*60*60*1000).toISOString().split('T')[0], y: 18 },
              { x: new Date(today.getTime() - 2*24*60*60*1000).toISOString().split('T')[0], y: 20 },
              { x: new Date(today.getTime() - 1*24*60*60*1000).toISOString().split('T')[0], y: 12 },
              { x: today.toISOString().split('T')[0], y: 28 }
            ],
            borderColor: '#28a745',
            backgroundColor: 'rgba(40, 167, 69, 0.1)',
            tension: 0.2,
            fill: true
          }]
        };
        
        window.debugChart = new Chart(ctx, {
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
                  text: 'Article Count'
                }
              }
            },
            plugins: {
              title: {
                display: true,
                text: 'Date Chart Test - Last 6 Days'
              }
            }
          }
        });
        
        this.updateDebugStatus('Date-based chart created successfully', 'success');
        console.log('Date chart created successfully');
        
      } catch (error) {
        this.updateDebugStatus('Date chart failed: ' + error.message, 'error');
        console.error('Date chart error:', error);
      }
    },

    /**
     * Update chart with real timeline data from selected terms.
     */
    updateRealChart: function() {
      if (!window.chartDebugData || !window.chartDebugData.timelineData) {
        this.updateDebugStatus('No timeline data available', 'error');
        return;
      }

      const selector = document.getElementById('term-selector');
      if (!selector) {
        this.updateDebugStatus('Term selector not found', 'error');
        return;
      }

      const selectedTermIds = Array.from(selector.selectedOptions).map(option => option.value);
      if (selectedTermIds.length === 0) {
        this.updateDebugStatus('No terms selected - choose terms to display', 'warning');
        return;
      }

      if (typeof Chart === 'undefined') {
        this.updateDebugStatus('Chart.js not available for real chart', 'error');
        return;
      }

      this.updateDebugStatus('Creating chart with ' + selectedTermIds.length + ' selected terms...', 'info');
      
      try {
        const chartData = this.filterTimelineData(selectedTermIds);
        this.createRealChart(chartData);
        this.updateDebugStatus('Real data chart updated with ' + selectedTermIds.length + ' terms', 'success');
        
      } catch (error) {
        this.updateDebugStatus('Real chart update failed: ' + error.message, 'error');
        console.error('Real chart error:', error);
      }
    },

    /**
     * Filter timeline data for selected terms.
     */
    filterTimelineData: function(selectedTermIds) {
      const datasets = [];
      const colors = [
        '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF', 
        '#FF9F40', '#E7E9ED', '#71B37C', '#D86613', '#8E44AD'
      ];
      
      selectedTermIds.forEach((termId, index) => {
        const termData = window.chartDebugData.timelineData.find(item => item.term_id == termId);
        if (termData && termData.data) {
          const dataPoints = termData.data
            .filter(point => point.count > 0)
            .map(point => ({
              x: point.date,
              y: point.count
            }));
          
          if (dataPoints.length > 0) {
            datasets.push({
              label: termData.term_name + ' (' + dataPoints.length + ' points)',
              data: dataPoints,
              borderColor: colors[index % colors.length],
              backgroundColor: colors[index % colors.length] + '20',
              tension: 0.1,
              fill: false,
              pointRadius: 3,
              pointHoverRadius: 6
            });
          }
        }
      });

      return { datasets: datasets };
    },

    /**
     * Create chart with real timeline data.
     */
    createRealChart: function(chartData) {
      if (typeof Chart === 'undefined') {
        throw new Error('Chart.js library not available');
      }

      const canvas = document.getElementById('taxonomy-timeline-chart');
      if (!canvas) {
        throw new Error('Canvas element not found');
      }

      // Destroy existing chart
      if (window.debugChart) {
        window.debugChart.destroy();
      }

      const ctx = canvas.getContext('2d');
      const hasDateAdapter = Chart.adapters && Chart.adapters._date;
      
      if (hasDateAdapter) {
        // Use time-based chart
        window.debugChart = new Chart(ctx, {
          type: 'line',
          data: chartData,
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
                title: { display: true, text: 'Date' }
              },
              y: {
                beginAtZero: true,
                title: { display: true, text: 'Article Count' },
                ticks: { precision: 0 }
              }
            },
            plugins: {
              title: {
                display: true,
                text: 'The Truth Perspective - Topic Trends Over Time'
              }
            }
          }
        });
      } else {
        // Simple fallback chart
        console.warn('Using simple chart fallback');
        const allDates = [];
        chartData.datasets.forEach(dataset => {
          dataset.data.forEach(point => {
            if (allDates.indexOf(point.x) === -1) {
              allDates.push(point.x);
            }
          });
        });
        allDates.sort();
        
        const simpleData = {
          labels: allDates,
          datasets: chartData.datasets.map(dataset => ({
            ...dataset,
            data: allDates.map(date => {
              const point = dataset.data.find(p => p.x === date);
              return point ? point.y : 0;
            })
          }))
        };
        
        window.debugChart = new Chart(ctx, {
          type: 'line',
          data: simpleData,
          options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
              x: { title: { display: true, text: 'Date' } },
              y: { 
                beginAtZero: true, 
                title: { display: true, text: 'Article Count' },
                ticks: { precision: 0 }
              }
            },
            plugins: {
              title: {
                display: true,
                text: 'The Truth Perspective - Topic Trends (Simple Mode)'
              }
            }
          }
        });
      }
    },

    /**
     * Reset chart to default selection.
     */
    resetChart: function() {
      const selector = document.getElementById('term-selector');
      if (selector) {
        for (let option of selector.options) {
          option.selected = false;
        }
        for (let i = 0; i < Math.min(5, selector.options.length); i++) {
          selector.options[i].selected = true;
        }
        this.updateRealChart();
      }
    },

    /**
     * Clear chart display.
     */
    clearChart: function() {
      const selector = document.getElementById('term-selector');
      if (selector) {
        for (let option of selector.options) {
          option.selected = false;
        }
      }
      if (window.debugChart) {
        window.debugChart.destroy();
        window.debugChart = null;
      }
      this.updateDebugStatus('Chart display cleared', 'info');
    },

    /**
     * Show error message when Chart.js fails to load.
     */
    showLibraryErrorMessage: function() {
      const container = document.querySelector('.chart-container');
      if (container) {
        container.innerHTML = `
          <div class="chart-status error">
            <h3>📊 Chart Libraries Unavailable</h3>
            <p>The Chart.js files failed to load. Check the following:</p>
            <ul>
              <li>Verify chart.umd.js exists in js/ directory</li>
              <li>Check file permissions and web server access</li>
              <li>Clear Drupal cache after adding library files</li>
              <li>Check browser console for loading errors</li>
            </ul>
            <p>Files expected:</p>
            <ul>
              <li>/modules/custom/newsmotivationmetrics/js/chart.umd.js</li>
              <li>/modules/custom/newsmotivationmetrics/js/chartjs-adapter-date-fns.bundle.min.js</li>
            </ul>
          </div>
        `;
      }
    },

    /**
     * Update debug status with enhanced logging.
     */
    updateDebugStatus: function(message, type) {
      type = type || 'info';
      const timestamp = new Date().toLocaleTimeString();
      
      // Update debug status display
      const debugEl = document.getElementById('debug-status');
      if (debugEl) {
        debugEl.textContent = message + ' (' + timestamp + ')';
      }
      
      // Update main status display
      const statusEl = document.getElementById('chart-status');
      if (statusEl) {
        statusEl.className = 'chart-status ' + type;
        statusEl.textContent = 'Chart Status: ' + message;
      }
      
      // Console logging
      const logPrefix = '[TRUTH PERSPECTIVE DEBUG] ' + timestamp + ' - ' + type.toUpperCase() + ':';
      console.log(logPrefix, message);
      
      // Additional context for errors and warnings
      if (type === 'error' || type === 'warning') {
        console.log('Debug context:', {
          chartJsAvailable: typeof Chart !== 'undefined',
          chartJsVersion: (typeof Chart !== 'undefined') ? Chart.version : null,
          hasAdapters: typeof Chart !== 'undefined' && !!Chart.adapters,
          hasDateAdapter: typeof Chart !== 'undefined' && !!(Chart.adapters && Chart.adapters._date),
          canvasExists: !!document.getElementById('taxonomy-timeline-chart'),
          dataAvailable: !!window.chartDebugData,
          timelineDataCount: window.chartDebugData?.timelineData?.length || 0,
          topTermsCount: window.chartDebugData?.topTerms?.length || 0
        });
      }
    }
  };

})(jQuery, Drupal, drupalSettings);