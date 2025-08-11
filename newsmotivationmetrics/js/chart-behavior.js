/**
 * Chart.js behavior for News Motivation Metrics module.
 * Handles taxonomy timeline visualization with interactive controls.
 * Updated to match debug console functionality for taxonomy term occurrences over time.
 * Version: 1.0.1 - Enhanced error handling and debugging (Aug 8, 2025)
 */

(function ($, Drupal, drupalSettings, once) {
  'use strict';

  let currentChart = null;
  let chartData = null;
  let extendedTermsData = null; // Store extended terms for background loading
  let backgroundLoadingComplete = false;

  /**
   * Chart behavior for taxonomy timeline visualization.
   */
  Drupal.behaviors.newsMotivationMetricsChart = {
    attach: function (context, settings) {
      once('taxonomy-timeline-chart', 'body', context).forEach(function () {
        console.log('=== News Motivation Metrics Chart Behavior v1.0.1 (Aug 8) ===');
        console.log('Settings:', settings.newsmotivationmetrics);

        // Get chart data from drupalSettings
        chartData = settings.newsmotivationmetrics || {};
        const timelineData = chartData.timelineData || [];
        const topTerms = chartData.topTerms || [];
        extendedTermsData = chartData.extendedTerms || null;

        console.log('Timeline data points:', timelineData.length);
        console.log('Available terms:', topTerms.length);
        console.log('Extended terms available:', extendedTermsData ? 'Yes' : 'No');

        // Initialize chart system with retry logic for canvas availability
        setTimeout(() => {
          this.initializeChart(timelineData, topTerms);
          
          // Start background loading of extended terms if available
          if (extendedTermsData) {
            this.startBackgroundLoading();
          }

          // === Multi-select diagnostic ===
          const selector = document.getElementById('term-selector');
          if (selector) {
            // Log selector HTML and attributes
            console.log('[DIAG] Selector outerHTML:', selector.outerHTML);
            console.log('[DIAG] Selector attributes:', selector.getAttribute('multiple'), selector.getAttribute('size'), selector.className);
            // Check if truly multi-select
            if (!selector.multiple || selector.size <= 1) {
              // Add visible warning above selector
              const warning = document.createElement('div');
              warning.className = 'chart-status status-error';
              warning.style.margin = '10px 0';
              warning.innerHTML = '<b>⚠️ Multi-select is not enabled!</b> The term selector is not a true multi-select. Please check for theme or module conflicts.';
              selector.parentNode.insertBefore(warning, selector);
              console.warn('[DIAG] Multi-select is NOT enabled on the selector!');
            } else {
              console.log('[DIAG] Multi-select is enabled on the selector.');
            }
          } else {
            console.warn('[DIAG] Term selector not found for multi-select diagnostic.');
          }
        }, 100);
      }.bind(this));
    },

    /**
     * Initialize the chart system with data validation and setup.
     */
    initializeChart: function(timelineData, topTerms) {
      const self = this;
      
      // Validate Chart.js availability
      if (typeof Chart === 'undefined') {
        self.updateStatus('Chart.js library not loaded', 'error');
        return;
      }

      console.log('Chart.js available:', typeof Chart);
      console.log('Chart._adapters:', Chart._adapters);
      console.log('Chart version:', Chart.version);
      
      // Log adapter availability for debugging
      let dateAdapterStatus = 'unknown';
      if (Chart._adapters && Chart._adapters._date) {
        dateAdapterStatus = 'Chart._adapters._date available';
      } else if (typeof window.dfns !== 'undefined') {
        dateAdapterStatus = 'date-fns adapter available';  
      } else if (Chart.defaults && Chart.defaults.scales && Chart.defaults.scales.time) {
        dateAdapterStatus = 'Chart.defaults.scales.time available';
      } else {
        dateAdapterStatus = 'No date adapter detected - proceeding anyway';
      }
      
      console.log('Date adapter status:', dateAdapterStatus);

      // Validate canvas element with enhanced debugging
      const canvas = document.getElementById('taxonomy-timeline-chart');
      if (!canvas) {
        console.log('Canvas element not found - DOM elements:');
        console.log('timeline sections:', document.querySelectorAll('.taxonomy-timeline-section').length);
        console.log('all canvas elements:', document.querySelectorAll('canvas').length);
        console.log('chart containers:', document.querySelectorAll('.chart-container').length);
        self.updateStatus('Canvas element not found - chart not available on this page', 'error');
        return;
      }

      // Validate data
      if (!timelineData || timelineData.length === 0) {
        self.updateStatus('No timeline data available', 'warning');
        self.showNoDataMessage();
        return;
      }

      console.log('Chart initialization starting...');
      self.updateStatus('Initializing taxonomy timeline chart...', 'info');

      // Set up event listeners
      self.setupEventListeners();

      // Initialize chart with automatic term selection
      self.initializeTimelineChart(timelineData);
    },

    /**
     * Initialize the taxonomy timeline chart with multiple terms.
     */
    initializeTimelineChart: function(timelineData) {
      const self = this;
      const canvas = document.getElementById('taxonomy-timeline-chart');
      
      if (!canvas) {
        self.updateStatus('Canvas element not found', 'error');
        return;
      }

      const ctx = canvas.getContext('2d');
      if (!ctx) {
        self.updateStatus('Failed to get 2D context from canvas', 'error');
        return;
      }

      console.log(`Processing ${timelineData.length} taxonomy term timeline datasets`);

      try {
        // Destroy existing chart if it exists
        if (currentChart) {
          currentChart.destroy();
          currentChart = null;
        }

        // Define colors for different taxonomy terms
        const colors = [
          'rgb(255, 99, 132)',    // Red
          'rgb(54, 162, 235)',    // Blue
          'rgb(255, 205, 86)',    // Yellow
          'rgb(75, 192, 192)',    // Teal
          'rgb(153, 102, 255)',   // Purple
          'rgb(255, 159, 64)',    // Orange
          'rgb(199, 199, 199)',   // Gray
          'rgb(83, 102, 255)',    // Indigo
          'rgb(255, 99, 255)',    // Pink
          'rgb(99, 255, 132)'     // Green
        ];

        // Process taxonomy timeline data into Chart.js datasets
        const datasets = timelineData.map((termData, index) => {
          const color = colors[index % colors.length];
          const processedData = termData.data.map(dataPoint => ({
            x: new Date(dataPoint.date),
            y: parseInt(dataPoint.count) || 0
          }));

          // Sort by date
          processedData.sort((a, b) => a.x - b.x);

          return {
            label: `${termData.term_name} (ID: ${termData.term_id})`,
            data: processedData,
            borderColor: color,
            backgroundColor: color.replace('rgb', 'rgba').replace(')', ', 0.1)'),
            tension: 0.1,
            fill: false,
            borderWidth: 2,
            pointRadius: 3,
            pointHoverRadius: 5
          };
        });

        // Calculate total data points across all terms
        const totalDataPoints = datasets.reduce((total, dataset) => total + dataset.data.length, 0);

        // Create multi-line timeline chart
        currentChart = new Chart(ctx, {
          type: 'line',
          data: {
            datasets: datasets
          },
          options: {
            responsive: true,
            maintainAspectRatio: true,
            aspectRatio: 2, // Width:Height ratio of 2:1
            resizeDelay: 100, // Debounce resize events
            interaction: {
              mode: 'index',
              intersect: false,
            },
            animation: {
              duration: 750, // Reduce animation time
              easing: 'easeOutQuart',
            },
            scales: {
              x: {
                type: 'time',
                time: {
                  unit: 'day',
                  displayFormats: {
                    day: 'MMM dd',
                    week: 'MMM dd',
                    month: 'MMM'
                  }
                },
                title: {
                  display: true,
                  text: 'Publication Date',
                  font: {
                    size: 12,
                    weight: 'bold'
                  }
                }
              },
              y: {
                beginAtZero: true,
                title: {
                  display: true,
                  text: 'Article Count per Term',
                  font: {
                    size: 12,
                    weight: 'bold'
                  }
                },
                ticks: {
                  stepSize: 1
                }
              }
            },
            plugins: {
              title: {
                display: true,
                text: 'Taxonomy Term Occurrences Over Time (Real Data)',
                font: {
                  size: 16,
                  weight: 'bold'
                },
                padding: 20
              },
              legend: {
                display: true,
                position: 'top',
                labels: {
                  usePointStyle: true,
                  padding: 10,
                  font: {
                    size: 11
                  }
                }
              },
              tooltip: {
                mode: 'index',
                intersect: false,
                callbacks: {
                  title: function(tooltipItems) {
                    if (tooltipItems.length > 0) {
                      const date = new Date(tooltipItems[0].parsed.x);
                      return date.toLocaleDateString('en-US', { 
                        weekday: 'short', 
                        year: 'numeric', 
                        month: 'short', 
                        day: 'numeric' 
                      });
                    }
                    return '';
                  },
                  label: function(context) {
                    const termName = context.dataset.label.split(' (ID:')[0];
                    const count = context.parsed.y;
                    return `${termName}: ${count} article${count !== 1 ? 's' : ''}`;
                  }
                }
              }
            },
            elements: {
              point: {
                hoverBackgroundColor: 'white',
                hoverBorderWidth: 2
              }
            }
          }
        });

        console.log(`Taxonomy timeline chart created successfully with ${datasets.length} terms and ${totalDataPoints} total data points`);

        // Update status with success message that auto-clears
        self.updateStatus(`✅ Chart loaded with ${datasets.length} terms and ${totalDataPoints} data points`, 'success');
        
        // Update chart info area
        self.updateChartInfo(datasets.length, totalDataPoints);

      } catch (error) {
        self.updateStatus(`Failed to create taxonomy timeline chart: ${error.message}`, 'error');
        console.error('Taxonomy timeline chart creation error:', error);
      }
    },

    /**
     * Set up event listeners for chart controls.
     */
    setupEventListeners: function() {
      const self = this;

      // Reset chart button
      const resetBtn = document.getElementById('reset-chart');
      if (resetBtn) {
        resetBtn.addEventListener('click', function() {
          console.log('Reset chart button clicked');
          self.resetChart();
        });
      }

      // Clear chart button
      const clearBtn = document.getElementById('clear-chart');
      if (clearBtn) {
        clearBtn.addEventListener('click', function() {
          console.log('Clear chart button clicked');
          self.clearChart();
        });
      }

      // Term selector change (if present)
      const selector = document.getElementById('term-selector');
      if (selector) {
        selector.addEventListener('change', function() {
          console.log('Term selector changed, selected options:', selector.selectedOptions.length);
          self.handleTermSelection(selector);
        });
        
        // Add keydown listener for better UX
        selector.addEventListener('keydown', function(e) {
          // Allow deselection with Delete/Backspace
          if (e.key === 'Delete' || e.key === 'Backspace') {
            const selected = selector.selectedOptions;
            if (selected.length > 0) {
              Array.from(selected).forEach(option => option.selected = false);
              self.updateFromSelector();
            }
          }
        });
        
        console.log('Term selector event listeners attached');
        
        // Set initial selection to top 10 terms
        self.selectTop10Terms(selector);
      } else {
        console.log('Term selector not found for event listener');
      }

      // Add chart resize controls
      self.addResizeControls();

      console.log('Event listeners attached for chart controls');
    },

    /**
     * Add chart resize controls to the chart container.
     */
    addResizeControls: function() {
      const chartContainer = document.querySelector('.taxonomy-timeline-section .chart-container');
      if (!chartContainer) {
        console.log('Chart container not found for resize controls');
        return;
      }

      // Check if resize controls already exist
      if (chartContainer.querySelector('.chart-resize-controls')) {
        return;
      }

      // Create resize controls container
      const resizeControls = document.createElement('div');
      resizeControls.className = 'chart-resize-controls';
      resizeControls.style.cssText = `
        margin: 10px 0;
        padding: 10px;
        background: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 4px;
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
      `;

      // Size label
      const sizeLabel = document.createElement('span');
      sizeLabel.textContent = 'Chart Size:';
      sizeLabel.style.fontWeight = 'bold';

      // Size buttons
      const sizes = [
        { label: 'Small', height: 300, ratio: 2.5 },
        { label: 'Medium', height: 400, ratio: 2 },
        { label: 'Large', height: 500, ratio: 1.8 },
        { label: 'X-Large', height: 600, ratio: 1.6 }
      ];

      const buttonContainer = document.createElement('div');
      buttonContainer.style.cssText = 'display: flex; gap: 5px; flex-wrap: wrap;';

      sizes.forEach((size, index) => {
        const button = document.createElement('button');
        button.textContent = size.label;
        button.className = 'btn btn-outline btn-sm';
        button.style.cssText = `
          padding: 5px 10px;
          border: 1px solid #007bff;
          background: ${index === 1 ? '#007bff' : 'white'};
          color: ${index === 1 ? 'white' : '#007bff'};
          border-radius: 3px;
          cursor: pointer;
          font-size: 12px;
        `;
        
        button.addEventListener('click', () => {
          this.resizeChart(size.height, size.ratio);
          
          // Update button states
          buttonContainer.querySelectorAll('button').forEach(btn => {
            btn.style.background = 'white';
            btn.style.color = '#007bff';
          });
          button.style.background = '#007bff';
          button.style.color = 'white';
        });

        buttonContainer.appendChild(button);
      });

      // Fullscreen toggle
      const fullscreenBtn = document.createElement('button');
      fullscreenBtn.textContent = '⛶ Fullscreen';
      fullscreenBtn.className = 'btn btn-secondary btn-sm';
      fullscreenBtn.style.cssText = `
        padding: 5px 10px;
        border: 1px solid #6c757d;
        background: white;
        color: #6c757d;
        border-radius: 3px;
        cursor: pointer;
        font-size: 12px;
        margin-left: 10px;
      `;
      
      fullscreenBtn.addEventListener('click', () => {
        this.toggleFullscreen();
      });

      // Assemble controls
      resizeControls.appendChild(sizeLabel);
      resizeControls.appendChild(buttonContainer);
      resizeControls.appendChild(fullscreenBtn);

      // Insert before canvas
      const canvas = chartContainer.querySelector('#taxonomy-timeline-chart');
      if (canvas) {
        chartContainer.insertBefore(resizeControls, canvas);
      } else {
        chartContainer.appendChild(resizeControls);
      }

      console.log('Chart resize controls added');
    },

    /**
     * Resize the chart with new dimensions.
     */
    resizeChart: function(height, aspectRatio) {
      const canvas = document.getElementById('taxonomy-timeline-chart');
      if (!canvas || !currentChart) {
        console.log('Cannot resize: canvas or chart not available');
        return;
      }

      // Update canvas height
      canvas.height = Math.min(height, 800); // Max height limit
      canvas.style.height = 'auto';
      canvas.style.maxHeight = Math.min(height, 800) + 'px';

      // Update chart options
      currentChart.options.aspectRatio = aspectRatio;
      currentChart.options.maintainAspectRatio = true;
      
      // Force chart resize
      currentChart.resize();
      
      this.updateStatus(`Chart resized to ${Math.min(height, 800)}px height`, 'info');
      console.log(`Chart resized to height: ${height}px, aspect ratio: ${aspectRatio}`);
    },

    /**
     * Toggle fullscreen mode for the chart.
     */
    toggleFullscreen: function() {
      const chartSection = document.querySelector('.taxonomy-timeline-section');
      if (!chartSection) return;

      const isFullscreen = chartSection.classList.contains('chart-fullscreen');
      
      if (!isFullscreen) {
        // Enter fullscreen
        chartSection.classList.add('chart-fullscreen');
        chartSection.style.cssText = `
          position: fixed !important;
          top: 0 !important;
          left: 0 !important;
          width: 100vw !important;
          height: 100vh !important;
          background: white !important;
          z-index: 9999 !important;
          padding: 20px !important;
          overflow: auto !important;
        `;

        // Resize chart for fullscreen
        this.resizeChart(window.innerHeight - 200, 2.5);
        
        // Add exit button
        if (!chartSection.querySelector('.exit-fullscreen')) {
          const exitBtn = document.createElement('button');
          exitBtn.className = 'exit-fullscreen';
          exitBtn.textContent = '✕ Exit Fullscreen';
          exitBtn.style.cssText = `
            position: absolute;
            top: 10px;
            right: 10px;
            padding: 10px 15px;
            background: #dc3545;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            z-index: 10000;
          `;
          exitBtn.addEventListener('click', () => this.toggleFullscreen());
          chartSection.appendChild(exitBtn);
        }

        this.updateStatus('Chart in fullscreen mode', 'info');
      } else {
        // Exit fullscreen
        chartSection.classList.remove('chart-fullscreen');
        chartSection.style.cssText = '';
        
        // Remove exit button
        const exitBtn = chartSection.querySelector('.exit-fullscreen');
        if (exitBtn) {
          exitBtn.remove();
        }

        // Reset chart to medium size
        this.resizeChart(400, 2);
        
        this.updateStatus('Exited fullscreen mode', 'info');
      }
    },

    /**
     * Handle term selection with 10-term limit enforcement.
     */
    handleTermSelection: function(selector) {
      const selectedOptions = Array.from(selector.selectedOptions);
      const maxSelections = 10;
      
      if (selectedOptions.length > maxSelections) {
        // Remove the last selected option if over limit
        const lastSelected = selectedOptions[selectedOptions.length - 1];
        lastSelected.selected = false;
        
        this.updateStatus(`Maximum ${maxSelections} terms allowed. Removed: ${lastSelected.textContent.split(' (')[0]}`, 'warning');
        
        // Update chart with the valid selection
        setTimeout(() => this.updateFromSelector(), 100);
      } else {
        this.updateFromSelector();
      }
      
      // Update info text
      this.updateSelectionInfo(selectedOptions.length);
    },

    /**
     * Select the top 10 terms by default.
     */
    selectTop10Terms: function(selector) {
      // Clear all selections first
      Array.from(selector.options).forEach(option => {
        option.selected = false;
      });
      
      // Select first 10 options (which should be the top terms)
      const optionsToSelect = Math.min(10, selector.options.length);
      for (let i = 0; i < optionsToSelect; i++) {
        if (selector.options[i]) {
          selector.options[i].selected = true;
        }
      }
      
      console.log(`Auto-selected top ${optionsToSelect} terms`);
      this.updateSelectionInfo(optionsToSelect);
    },

    /**
     * Update selection info display.
     */
    updateSelectionInfo: function(count) {
      const infoElement = document.querySelector('.chart-info .info-text');
      if (infoElement) {
        if (count > 0) {
          infoElement.textContent = `📊 Displaying ${count}/10 terms. ${backgroundLoadingComplete ? 'All terms loaded.' : 'Loading additional terms...'}`;
        } else {
          infoElement.textContent = '📊 Select up to 10 terms to display. Top 10 terms shown by default.';
        }
      }
    },

    /**
     * Start background loading of extended terms.
     */
    startBackgroundLoading: function() {
      if (!extendedTermsData || backgroundLoadingComplete) {
        return;
      }
      
      console.log('Starting background loading of extended terms...');
      
      // Show loading indicator
      const loadingElement = document.getElementById('loading-status-main');
      if (loadingElement) {
        loadingElement.style.display = 'inline';
      }
      
      // Simulate background processing time (in real implementation, this might be an AJAX call)
      setTimeout(() => {
        this.completeBackgroundLoading();
      }, 2000);
    },

    /**
     * Complete background loading and update selector options.
     */
    completeBackgroundLoading: function() {
      if (!extendedTermsData) {
        console.log('No extended terms data available');
        return;
      }
      
      console.log('Background loading complete');
      backgroundLoadingComplete = true;
      
      // Hide loading indicator
      const loadingElement = document.getElementById('loading-status-main');
      if (loadingElement) {
        loadingElement.style.display = 'none';
      }
      
      // Update selector with extended terms
      this.updateSelectorWithExtendedTerms();
      
      // Update info text
      const selector = document.getElementById('term-selector');
      if (selector) {
        this.updateSelectionInfo(selector.selectedOptions.length);
      }
      
      this.updateStatus('All terms loaded and available for selection', 'info');
    },

    /**
     * Update term selector with extended terms.
     */
    updateSelectorWithExtendedTerms: function() {
      const selector = document.getElementById('term-selector');
      if (!selector || !extendedTermsData.topTerms) {
        return;
      }
      
      // Store current selections
      const currentSelections = Array.from(selector.selectedOptions).map(option => option.value);
      
      // Clear existing options
      selector.innerHTML = '';
      
      // Add extended terms
      extendedTermsData.topTerms.forEach(term => {
        const option = document.createElement('option');
        option.value = term.tid;
        option.textContent = `${term.name} (${term.usage_count || term.article_count || 0} articles)`;
        
        // Restore selection if it was previously selected
        if (currentSelections.includes(term.tid.toString())) {
          option.selected = true;
        }
        
        selector.appendChild(option);
      });
      
      console.log(`Selector updated with ${extendedTermsData.topTerms.length} extended terms`);
    },

    /**
     * Reset chart to show all available terms.
     */
    resetChart: function() {
      const self = this;
      
      // Reset the term selector to show top 10 terms
      const selector = document.getElementById('term-selector');
      if (selector) {
        self.selectTop10Terms(selector);
        console.log('Reset selector: selected top 10 terms');
      }
      
      // Use the initial timeline data (top 10 terms)
      const timelineData = chartData.timelineData || [];
      if (timelineData.length > 0) {
        // Show top 10 terms (limit the data)
        const topTermsData = timelineData.slice(0, 10);
        self.initializeTimelineChart(topTermsData);
        self.updateStatus(`✅ Chart reset to show top ${topTermsData.length} terms`, 'success');
        self.updateSelectionInfo(topTermsData.length);
      } else {
        self.updateStatus('No data available for reset', 'warning');
        self.updateSelectionInfo(0);
      }
    },

    /**
     * Clear chart and show placeholder.
     */
    clearChart: function() {
      const self = this;
      
      // Clear the term selector
      const selector = document.getElementById('term-selector');
      if (selector) {
        Array.from(selector.options).forEach(option => {
          option.selected = false;
        });
        console.log('Term selector cleared');
      }
      
      if (currentChart) {
        currentChart.destroy();
        currentChart = null;
        console.log('Chart destroyed');
      }

      // Reset canvas container
      const container = document.querySelector('.chart-container');
      if (container) {
        const canvas = container.querySelector('#taxonomy-timeline-chart');
        if (canvas) {
          const ctx = canvas.getContext('2d');
          ctx.clearRect(0, 0, canvas.width, canvas.height);
        }
      }

      self.updateStatus('Chart cleared - select terms above to display data', 'info');
      self.updateChartInfo(0, 0);
      self.updateSelectionInfo(0);
    },

    /**
     * Update chart based on term selector (if available).
     */
    updateFromSelector: function() {
      const selector = document.getElementById('term-selector');
      if (!selector) {
        console.log('Term selector not found for updating chart');
        return;
      }

      const selectedTermIds = Array.from(selector.selectedOptions).map(option => option.value);
      console.log('Selected term IDs:', selectedTermIds);
      
      // Use extended terms data if available, otherwise fall back to original data
      const availableTimelineData = (extendedTermsData && extendedTermsData.timelineData) ? 
        extendedTermsData.timelineData : (chartData.timelineData || []);
      
      console.log('Available timeline data terms:', availableTimelineData.map(t => t.term_id));
      
      if (selectedTermIds.length === 0) {
        this.clearChart();
        this.updateStatus('No terms selected - chart cleared', 'warning');
        this.updateSelectionInfo(0);
        return;
      }

      // Filter timeline data for selected terms
      const filteredData = availableTimelineData.filter(termData => {
        const termMatch = selectedTermIds.includes(termData.term_id.toString());
        console.log(`Term ${termData.term_id} (${termData.term_name}): ${termMatch ? 'included' : 'excluded'}`);
        return termMatch;
      });

      console.log(`Filtered data: ${filteredData.length} terms from ${selectedTermIds.length} selected`);

      if (filteredData.length > 0) {
        this.initializeTimelineChart(filteredData);
        this.updateStatus(`✅ Chart updated with ${filteredData.length} selected terms`, 'success');
        this.updateSelectionInfo(selectedTermIds.length);
      } else {
        this.clearChart();
        this.updateStatus('No matching data found for selected terms', 'warning');
        this.updateSelectionInfo(0);
      }
    },

    /**
     * Update chart information display.
     */
    updateChartInfo: function(termCount, dataPoints) {
      const infoElement = document.querySelector('.chart-info .info-text');
      if (infoElement) {
        if (termCount > 0) {
          infoElement.textContent = `📊 Showing ${termCount} taxonomy terms with ${dataPoints} total data points over the last 30 days`;
        } else {
          infoElement.textContent = '📊 No chart data currently displayed - use controls above to generate charts';
        }
      }
    },

    /**
     * Update status message display.
     */
    updateStatus: function(message, type) {
      type = type || 'info';
      
      // Update main status display
      const statusEl = document.getElementById('chart-status');
      if (statusEl) {
        statusEl.className = 'chart-status ' + type;
        statusEl.textContent = message;
        
        // Auto-clear success/info messages after 3 seconds
        if (type === 'success' || type === 'info') {
          setTimeout(() => {
            if (statusEl.textContent === message) {
              statusEl.textContent = '';
              statusEl.className = 'chart-status';
            }
          }, 3000);
        }
      }

      // Console logging
      console.log('Chart status (' + type + '):', message);
    },

    /**
     * Show fallback message when chart functionality is not available.
     */
    showFallbackMessage: function() {
      const container = document.querySelector('.chart-container');
      if (container) {
        container.innerHTML = `
          <div class="chart-status error">
            <h3>📊 Chart Unavailable</h3>
            <p>The interactive chart feature requires Chart.js with date adapter support. The chart functionality is currently unavailable, but you can still view the raw data and statistics in the sections below.</p>
            <p><strong>For administrators:</strong> Ensure Chart.js and chartjs-adapter-date-fns libraries are properly loaded.</p>
          </div>
        `;
      }
    },

    /**
     * Show message when no data is available.
     */
    showNoDataMessage: function() {
      const container = document.querySelector('.chart-container');
      if (container) {
        container.innerHTML = `
          <div class="chart-status warning">
            <h3>📊 No Chart Data Available</h3>
            <p>No taxonomy timeline data is currently available for visualization. This may be because:</p>
            <ul>
              <li>Articles haven't been processed with publication dates yet</li>
              <li>No articles have been tagged with taxonomy terms</li>
              <li>The data is still being generated in the background</li>
            </ul>
            <p>Check back later or contact an administrator if this issue persists.</p>
          </div>
        `;
      }
    }
  };

})(jQuery, Drupal, drupalSettings, once);