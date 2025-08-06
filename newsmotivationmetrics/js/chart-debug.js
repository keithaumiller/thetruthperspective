/**
 * Chart debug behavior for The Truth Perspective analytics development.
 * Enhanced CDN loading detection and fallback handling for Chart.js integration.
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

      // Initialize debug environment with enhanced CDN detection
      this.initializeDebugMode();
    },

    /**
     * Initialize debug-specific functionality with enhanced library loading checks.
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
     * Set up debug environment with enhanced CDN loading detection.
     */
    setupDebugEnvironment: function() {
      const self = this;
      
      self.updateDebugStatus('Initializing The Truth Perspective chart system...', 'info');
      
      // Enhanced Chart.js availability check with longer timeout for CDN
      self.waitForChartJS(function(success) {
        if (success) {
          self.updateVersionInfo();
          self.setupDebugEventListeners();
          self.validateTimelineData();
          
          // Auto-start with real data test after libraries fully load
          setTimeout(function() {
            self.testRealTimelineData();
          }, 1500);
        } else {
          self.updateDebugStatus('Chart.js libraries failed to load from all CDN sources', 'error');
          self.showLibraryErrorMessage();
          
          // Still validate data even without charts
          self.validateTimelineData();
          self.setupDebugEventListeners();
        }
      });
    },

    /**
     * Enhanced Chart.js loading detection with extended timeout for CDN sources.
     */
    waitForChartJS: function(callback, attempts) {
      attempts = attempts || 0;
      const maxAttempts = 100; // 10 seconds with 100ms intervals for CDN loading
      const self = this;
      
      // Check if Chart.js is available
      if (typeof Chart !== 'undefined') {
        console.log('Chart.js loaded successfully, version:', Chart.version);
        
        // Additional check for date adapter after Chart.js loads
        self.waitForDateAdapter(function(adapterSuccess) {
          console.log('Date adapter availability:', adapterSuccess);
          callback(true);
        });
        return;
      }
      
      // Check for loading errors from template
      if (window.chartLoadError) {
        console.error('Chart loading failed:', window.chartLoadError);
        callback(false);
        return;
      }
      
      if (attempts >= maxAttempts) {
        console.error('Chart.js failed to load after', maxAttempts, 'attempts (10 seconds)');
        callback(false);
        return;
      }
      
      // Update status every 20 attempts (2 seconds)
      if (attempts % 20 === 0) {
        self.updateDebugStatus('Waiting for Chart.js CDN to load... (' + Math.floor(attempts/10) + 's)', 'info');
      }
      
      setTimeout(function() {
        self.waitForChartJS(callback, attempts + 1);
      }, 100);
    },

    /**
     * Wait for date adapter to load after Chart.js is available.
     */
    waitForDateAdapter: function(callback, attempts) {
      attempts = attempts || 0;
      const maxAttempts = 50; // 5 seconds for date adapter
      const self = this;
      
      if (typeof Chart !== 'undefined' && Chart.adapters && Chart.adapters._date) {
        console.log('Date adapter loaded successfully');
        callback(true);
        return;
      }
      
      if (window.adapterLoadError) {
        console.warn('Date adapter loading failed:', window.adapterLoadError);
        callback(false);
        return;
      }
      
      if (attempts >= maxAttempts) {
        console.warn('Date adapter not available after timeout');
        callback(false);
        return;
      }
      
      setTimeout(function() {
        self.waitForDateAdapter(callback, attempts + 1);
      }, 100);
    },

    /**
     * Update Chart.js version and date adapter information with enhanced detection.
     */
    updateVersionInfo: function() {
      const versionEl = document.getElementById('chartjs-version');
      const adapterEl = document.getElementById('date-adapter-status');
      
      if (versionEl) {
        if (typeof Chart !== 'undefined') {
          const version = Chart.version || 'Unknown Version';
          versionEl.textContent = version;
          versionEl.style.color = '#28a745';
          console.log('Chart.js version detected:', version);
        } else {
          versionEl.textContent = 'Not Loaded';
          versionEl.style.color = '#dc3545';
        }
      }
      
      if (adapterEl) {
        const hasAdapter = (typeof Chart !== 'undefined') && Chart.adapters && Chart.adapters._date;
        if (hasAdapter) {
          adapterEl.textContent = 'Available';
          adapterEl.style.color = '#28a745';
          console.log('Date adapter available for timeline charts');
          this.updateDebugStatus('Chart.js and date adapter loaded successfully', 'success');
        } else {
          adapterEl.textContent = 'Missing';
          adapterEl.style.color = '#dc3545';
          console.warn('Date adapter not found - time-based charts will be limited');
          this.updateDebugStatus('Date adapter missing - timeline charts may not work', 'warning');
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
      
      // Check data date range
      const allDataPoints = validTerms.flatMap(term => term.data);
      const dates = allDataPoints.map(point => point.date).filter(Boolean);
      const minDate = dates.length > 0 ? Math.min(...dates.map(d => new Date(d).getTime())) : null;
      const maxDate = dates.length > 0 ? Math.max(...dates.map(d => new Date(d).getTime())) : null;
      
      console.log('Timeline data validation results:', {
        totalTerms: data.timelineData.length,
        validTerms: validTerms.length,
        termsWithData: nonEmptyTerms.length,
        topTermsAvailable: data.topTerms.length,
        totalDataPoints: allDataPoints.length,
        dateRange: minDate && maxDate ? {
          from: new Date(minDate).toISOString().split('T')[0],
          to: new Date(maxDate).toISOString().split('T')[0],
          days: Math.ceil((maxDate - minDate) / (1000 * 60 * 60 * 24))
        } : null
      });
      
      if (nonEmptyTerms.length === 0) {
        this.updateDebugStatus('Timeline data contains no non-zero values - check data processing', 'warning');
        return false;
      }
      
      this.updateDebugStatus('Timeline data validated: ' + nonEmptyTerms.length + ' terms with data', 'success');
      return true;
    },

    /**
     * Set up debug-specific event listeners with enhanced error handling.
     */
    setupDebugEventListeners: function() {
      const self = this;
      
      // Test simple chart button
      const testSimpleBtn = document.getElementById('test-simple');
      if (testSimpleBtn) {
        testSimpleBtn.addEventListener('click', function() {
          console.log('User initiated: Testing simple chart without dates...');
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
     * Test simple chart without date functionality for basic Chart.js verification.
     */
    testSimpleChart: function() {
      this.updateDebugStatus('Testing simple chart without date dependencies...', 'info');
      
      if (typeof Chart === 'undefined') {
        this.updateDebugStatus('Chart.js not available - cannot create simple chart', 'error');
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
            label: 'The Truth Perspective Sample Data',
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
                  text: 'Article Count',
                  font: { size: 14, weight: 'bold' }
                },
                grid: {
                  color: 'rgba(0, 0, 0, 0.1)'
                }
              },
              x: {
                title: {
                  display: true,
                  text: 'Time Period',
                  font: { size: 14, weight: 'bold' }
                },
                grid: {
                  color: 'rgba(0, 0, 0, 0.1)'
                }
              }
            },
            plugins: {
              title: {
                display: true,
                text: 'Simple Chart Test - The Truth Perspective',
                font: { size: 16, weight: 'bold' },
                padding: 20
              },
              legend: {
                display: true,
                position: 'top',
                labels: {
                  padding: 20,
                  usePointStyle: true
                }
              }
            }
          }
        });
        
        this.updateDebugStatus('Simple chart created successfully - Chart.js is working', 'success');
        console.log('Simple chart instance created:', window.debugChart);
        
      } catch (error) {
        this.updateDebugStatus('Simple chart creation failed: ' + error.message, 'error');
        console.error('Simple chart error details:', error);
      }
    },

    /**
     * Test date-based chart functionality with sample timeline data.
     */
    testDateChart: function() {
      this.updateDebugStatus('Testing date-based chart with time axis...', 'info');
      
      if (typeof Chart === 'undefined') {
        this.updateDebugStatus('Chart.js not available - cannot create date chart', 'error');
        return;
      }
      
      const canvas = document.getElementById('taxonomy-timeline-chart');
      if (!canvas) {
        this.updateDebugStatus('Canvas element not found in DOM', 'error');
        return;
      }
      
      // Check for date adapter availability
      if (!Chart.adapters || !Chart.adapters._date) {
        this.updateDebugStatus('Date adapter not available - using fallback simple chart', 'warning');
        this.testSimpleChart();
        return;
      }
      
      try {
        // Destroy existing chart instance
        if (window.debugChart) {
          window.debugChart.destroy();
          console.log('Destroyed previous chart instance');
        }
        
        const ctx = canvas.getContext('2d');
        
        // Generate recent dates for realistic testing similar to real data
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
            fill: true,
            pointRadius: 4,
            pointHoverRadius: 6,
            pointBackgroundColor: '#28a745',
            pointBorderColor: '#ffffff',
            pointBorderWidth: 2
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
                  text: 'Date',
                  font: { size: 14, weight: 'bold' }
                },
                grid: {
                  color: 'rgba(0, 0, 0, 0.1)'
                }
              },
              y: {
                beginAtZero: true,
                title: {
                  display: true,
                  text: 'Article Count',
                  font: { size: 14, weight: 'bold' }
                },
                grid: {
                  color: 'rgba(0, 0, 0, 0.1)'
                }
              }
            },
            plugins: {
              title: {
                display: true,
                text: 'Date-based Chart Test - Last 6 Days',
                font: { size: 16, weight: 'bold' },
                padding: 20
              },
              legend: {
                display: true,
                position: 'top',
                labels: {
                  padding: 20,
                  usePointStyle: true
                }
              }
            }
          }
        });
        
        this.updateDebugStatus('Date-based chart created successfully - date adapter working', 'success');
        console.log('Date chart instance created:', window.debugChart);
        
      } catch (error) {
        this.updateDebugStatus('Date chart creation failed: ' + error.message, 'error');
        console.error('Date chart error details:', error);
      }
    },

    /**
     * Update chart with real timeline data from selected terms.
     */
    updateRealChart: function() {
      if (!window.chartDebugData || !window.chartDebugData.timelineData) {
        this.updateDebugStatus('No real timeline data available', 'error');
        return;
      }

      const selector = document.getElementById('term-selector');
      if (!selector) {
        this.updateDebugStatus('Term selector not found in DOM', 'error');
        return;
      }

      const selectedTermIds = Array.from(selector.selectedOptions).map(option => option.value);
      if (selectedTermIds.length === 0) {
        this.updateDebugStatus('No terms selected - choose terms to display', 'warning');
        return;
      }

      this.updateDebugStatus('Creating chart with ' + selectedTermIds.length + ' selected terms...', 'info');
      
      try {
        const chartData = this.filterTimelineData(selectedTermIds);
        this.createRealChart(chartData);
        this.updateDebugStatus('Real data chart updated with ' + selectedTermIds.length + ' terms', 'success');
        
        // Log selected terms for debugging
        const selectedTerms = selectedTermIds.map(id => {
          const termData = window.chartDebugData.timelineData.find(t => t.term_id == id);
          return termData ? termData.term_name : 'Unknown';
        });
        console.log('Chart displaying terms:', selectedTerms);
        
      } catch (error) {
        this.updateDebugStatus('Real chart update failed: ' + error.message, 'error');
        console.error('Real chart error details:', error);
      }
    },

    /**
     * Filter timeline data for selected terms from The Truth Perspective system.
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
          // Filter out zero values for cleaner visualization
          const dataPoints = termData.data
            .filter(point => point.count > 0)
            .map(point => ({
              x: point.date,
              y: point.count
            }));
          
          if (dataPoints.length > 0) {
            datasets.push({
              label: termData.term_name + ' (' + dataPoints.length + ' data points)',
              data: dataPoints,
              borderColor: colors[index % colors.length],
              backgroundColor: colors[index % colors.length] + '20',
              tension: 0.1,
              fill: false,
              pointRadius: 3,
              pointHoverRadius: 6,
              pointBackgroundColor: colors[index % colors.length],
              pointBorderColor: '#ffffff',
              pointBorderWidth: 2
            });
          }
        }
      });

      return { datasets: datasets };
    },

    /**
     * Create chart with real timeline data from The Truth Perspective analytics.
     */
    createRealChart: function(chartData) {
      if (typeof Chart === 'undefined') {
        throw new Error('Chart.js library not available');
      }

      if (!Chart.adapters || !Chart.adapters._date) {
        throw new Error('Date adapter not available for timeline charts');
      }

      const canvas = document.getElementById('taxonomy-timeline-chart');
      if (!canvas) {
        throw new Error('Canvas element not found in DOM');
      }

      // Destroy existing chart instance
      if (window.debugChart) {
        window.debugChart.destroy();
      }

      const ctx = canvas.getContext('2d');
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
                  day: 'MMM DD',
                  week: 'MMM DD',
                  month: 'MMM YYYY'
                }
              },
              title: {
                display: true,
                text: 'Date',
                font: { size: 14, weight: 'bold' }
              },
              grid: {
                color: 'rgba(0, 0, 0, 0.1)'
              }
            },
            y: {
              beginAtZero: true,
              title: {
                display: true,
                text: 'Article Count',
                font: { size: 14, weight: 'bold' }
              },
              grid: {
                color: 'rgba(0, 0, 0, 0.1)'
              },
              ticks: {
                precision: 0
              }
            }
          },
          plugins: {
            title: {
              display: true,
              text: 'The Truth Perspective - Topic Trends Over Time',
              font: { size: 16, weight: 'bold' },
              padding: 20
            },
            legend: {
              display: true,
              position: 'top',
              labels: {
                padding: 20,
                usePointStyle: true,
                pointStyle: 'circle'
              }
            },
            tooltip: {
              mode: 'index',
              intersect: false,
              backgroundColor: 'rgba(0, 0, 0, 0.8)',
              titleColor: 'white',
              bodyColor: 'white',
              borderColor: 'rgba(255, 255, 255, 0.3)',
              borderWidth: 1,
              cornerRadius: 6,
              callbacks: {
                title: function(context) {
                  return 'Date: ' + context[0].label;
                },
                label: function(context) {
                  return context.dataset.label + ': ' + context.parsed.y + ' articles';
                }
              }
            }
          },
          interaction: {
            mode: 'index',
            intersect: false
          },
          elements: {
            line: {
              borderWidth: 2
            },
            point: {
              hoverBorderWidth: 2
            }
          }
        }
      });
    },

    /**
     * Reset chart to default term selection (top 5 terms with data).
     */
    resetChart: function() {
      const selector = document.getElementById('term-selector');
      if (!selector) {
        this.updateDebugStatus('Term selector not found', 'error');
        return;
      }

      // Clear all selections
      for (let option of selector.options) {
        option.selected = false;
      }
      
      // Select top 5 terms
      for (let i = 0; i < Math.min(5, selector.options.length); i++) {
        selector.options[i].selected = true;
      }
      
      this.updateDebugStatus('Reset to top 5 terms', 'info');
      this.updateRealChart();
    },

    /**
     * Clear chart display completely.
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
     * Show error message when Chart.js libraries fail to load from CDN.
     */
    showLibraryErrorMessage: function() {
      const container = document.querySelector('.chart-container');
      if (container) {
        container.innerHTML = `
          <div class="chart-status error">
            <h3>📊 Chart Libraries Unavailable</h3>
            <p>Chart.js and/or the date adapter failed to load from CDN. This affects the interactive chart functionality for The Truth Perspective analytics.</p>
            <h4>Possible causes:</h4>
            <ul>
              <li><strong>Network connectivity issues</strong> - Check internet connection</li>
              <li><strong>CDN unavailability</strong> - External Chart.js services may be down</li>
              <li><strong>Content blocking</strong> - Ad blockers or security software may be interfering</li>
              <li><strong>Corporate firewall</strong> - External JavaScript loading may be restricted</li>
            </ul>
            <p><strong>For administrators:</strong> Check browser console for specific error messages and verify external library loading permissions.</p>
            <p><strong>Alternative:</strong> Raw data and statistics are still available in the sections below the chart.</p>
          </div>
        `;
      }
    },

    /**
     * Update debug status messages with enhanced logging for The Truth Perspective system.
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
      
      // Enhanced console logging with debug context
      const logPrefix = '[THE TRUTH PERSPECTIVE DEBUG] ' + new Date().toISOString() + ' - ' + type.toUpperCase() + ':';
      console.log(logPrefix, message);
      
      // Log additional context for errors and warnings with CDN status
      if (type === 'error' || type === 'warning') {
        console.log('Debug context:', {
          chartJsAvailable: typeof Chart !== 'undefined',
          chartJsVersion: (typeof Chart !== 'undefined') ? Chart.version : null,
          dateAdapterAvailable: typeof Chart !== 'undefined' && Chart.adapters && Chart.adapters._date,
          canvasExists: !!document.getElementById('taxonomy-timeline-chart'),
          dataAvailable: !!window.chartDebugData,
          timelineDataCount: window.chartDebugData?.timelineData?.length || 0,
          topTermsCount: window.chartDebugData?.topTerms?.length || 0,
          cdnLoadErrors: {
            chartError: window.chartLoadError || null,
            adapterError: window.adapterLoadError || null
          }
        });
      }
    }
  };

})(jQuery, Drupal, drupalSettings);