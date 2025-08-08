/**
 * Chart.js behavior for News Motivation Metrics module.
 * Handles taxonomy timeline visualization with interactive controls.
 * Updated to match debug console functionality for taxonomy term occurrences over time.
 */

(function ($, Drupal, drupalSettings, once) {
  'use strict';

  let currentChart = null;
  let chartData = null;

  /**
   * Chart behavior for taxonomy timeline visualization.
   */
  Drupal.behaviors.newsMotivationMetricsChart = {
    attach: function (context, settings) {
      once('taxonomy-timeline-chart', 'body', context).forEach(function () {
        console.log('=== News Motivation Metrics Chart Behavior (Updated) ===');
        console.log('Settings:', settings.newsmotivationmetrics);

        // Get chart data from drupalSettings
        chartData = settings.newsmotivationmetrics || {};
        const timelineData = chartData.timelineData || [];
        const topTerms = chartData.topTerms || [];

        console.log('Timeline data points:', timelineData.length);
        console.log('Available terms:', topTerms.length);

        // Initialize chart system
        this.initializeChart(timelineData, topTerms);
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

      // Validate canvas element
      const canvas = document.getElementById('taxonomy-timeline-chart');
      if (!canvas) {
        console.log('Canvas element not found - chart not available on this page');
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
            maintainAspectRatio: false,
            interaction: {
              mode: 'index',
              intersect: false,
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

        self.updateStatus(`Timeline chart loaded with ${datasets.length} terms and ${totalDataPoints} data points`, 'success');
        
        // Update chart info area
        self.updateChartInfo(datasets.length, totalDataPoints);

        console.log(`Taxonomy timeline chart created successfully with ${datasets.length} terms and ${totalDataPoints} total data points`);

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
          self.resetChart();
        });
      }

      // Clear chart button
      const clearBtn = document.getElementById('clear-chart');
      if (clearBtn) {
        clearBtn.addEventListener('click', function() {
          self.clearChart();
        });
      }

      // Term selector change (if present)
      const selector = document.getElementById('term-selector');
      if (selector) {
        selector.addEventListener('change', function() {
          self.updateFromSelector();
        });
      }

      console.log('Event listeners attached for chart controls');
    },

    /**
     * Reset chart to show all available terms.
     */
    resetChart: function() {
      const self = this;
      const timelineData = chartData.timelineData || [];
      
      if (timelineData.length > 0) {
        self.initializeTimelineChart(timelineData);
        self.updateStatus('Chart reset to show all terms', 'info');
      } else {
        self.updateStatus('No data available for reset', 'warning');
      }
    },

    /**
     * Clear chart and show placeholder.
     */
    clearChart: function() {
      const self = this;
      
      if (currentChart) {
        currentChart.destroy();
        currentChart = null;
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

      self.updateStatus('Chart cleared', 'info');
      self.updateChartInfo(0, 0);
    },

    /**
     * Update chart based on term selector (if available).
     */
    updateFromSelector: function() {
      const selector = document.getElementById('term-selector');
      if (!selector) return;

      const selectedTermIds = Array.from(selector.selectedOptions).map(option => option.value);
      const timelineData = chartData.timelineData || [];
      
      if (selectedTermIds.length === 0) {
        this.clearChart();
        return;
      }

      // Filter timeline data for selected terms
      const filteredData = timelineData.filter(termData => 
        selectedTermIds.includes(termData.term_id.toString())
      );

      if (filteredData.length > 0) {
        this.initializeTimelineChart(filteredData);
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
        statusEl.textContent = 'Chart Status: ' + message;
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