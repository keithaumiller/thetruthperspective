/**
 * Chart.js behavior for News Motivation Metrics module.
 * Handles taxonomy timeline visualization with interactive controls.
 */

(function ($, Drupal, drupalSettings) {
  'use strict';

  /**
   * Chart behavior for taxonomy timeline visualization.
   */
  Drupal.behaviors.newsMotivationMetricsChart = {
    attach: function (context, settings) {
      // Only initialize once per page load
      if (context !== document) {
        return;
      }

      console.log('=== News Motivation Metrics Chart Behavior ===');
      console.log('Settings:', settings.newsmotivationmetrics);

      // Get chart data from drupalSettings
      const chartData = settings.newsmotivationmetrics || {};
      const timelineData = chartData.timelineData || [];
      const topTerms = chartData.topTerms || [];

      console.log('Timeline data points:', timelineData.length);
      console.log('Available terms:', topTerms.length);

      // Initialize chart system
      this.initializeChart(timelineData, topTerms);
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

      // Check for date adapter
      if (!Chart.adapters || !Chart.adapters._date) {
        self.updateStatus('Date adapter not available - timeline charts disabled', 'error');
        self.showFallbackMessage();
        return;
      }

      // Validate canvas element
      const canvas = document.getElementById('taxonomy-timeline-chart');
      if (!canvas) {
        console.log('Canvas element not found - chart not available on this page');
        return;
      }

      // Validate data
      if (!timelineData || timelineData.length === 0) {
        self.updateStatus('No timeline data available', 'warning');
        return;
      }

      console.log('Chart initialization starting...');
      self.updateStatus('Initializing chart system...', 'info');

      // Store data globally for chart operations
      window.newsMetricsChart = {
        chart: null,
        timelineData: timelineData,
        topTerms: topTerms,
        colors: [
          '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF',
          '#FF9F40', '#E7E9ED', '#71B37C', '#D86613', '#8E44AD'
        ]
      };

      // Set up event listeners
      self.setupEventListeners();

      // Initialize chart with default selection
      self.initializeDefaultChart();
    },

    /**
     * Set up event listeners for chart controls.
     */
    setupEventListeners: function() {
      const self = this;

      // Update chart button
      const updateBtn = document.getElementById('update-chart');
      if (updateBtn) {
        updateBtn.addEventListener('click', function() {
          self.updateChart();
        });
      }

      // Reset chart button
      const resetBtn = document.getElementById('reset-chart');
      if (resetBtn) {
        resetBtn.addEventListener('click', function() {
          self.resetToDefault();
        });
      }

      // Clear chart button
      const clearBtn = document.getElementById('clear-chart');
      if (clearBtn) {
        clearBtn.addEventListener('click', function() {
          self.clearChart();
        });
      }

      // Term selector change
      const selector = document.getElementById('term-selector');
      if (selector) {
        selector.addEventListener('change', function() {
          // Auto-update chart when selection changes
          self.updateChart();
        });
      }
    },

    /**
     * Initialize chart with default term selection.
     */
    initializeDefaultChart: function() {
      const selector = document.getElementById('term-selector');
      if (!selector) {
        this.updateStatus('Term selector not found', 'error');
        return;
      }

      // Select top 5 terms by default
      for (let i = 0; i < Math.min(5, selector.options.length); i++) {
        selector.options[i].selected = true;
      }

      // Create initial chart
      this.updateChart();
    },

    /**
     * Update chart based on current term selection.
     */
    updateChart: function() {
      const self = this;
      const selector = document.getElementById('term-selector');
      
      if (!selector) {
        self.updateStatus('Term selector not found', 'error');
        return;
      }

      const selectedTermIds = Array.from(selector.selectedOptions).map(option => option.value);
      
      if (selectedTermIds.length === 0) {
        self.updateStatus('No terms selected - select terms to display chart', 'warning');
        return;
      }

      console.log('Updating chart with terms:', selectedTermIds);
      self.updateStatus('Updating chart with ' + selectedTermIds.length + ' terms...', 'info');

      try {
        const chartData = self.filterTimelineData(selectedTermIds);
        self.createChart(chartData);
        self.updateStatus('Chart updated successfully with ' + selectedTermIds.length + ' terms', 'success');
      } catch (error) {
        console.error('Chart update error:', error);
        self.updateStatus('Chart update failed: ' + error.message, 'error');
      }
    },

    /**
     * Filter timeline data for selected terms.
     */
    filterTimelineData: function(selectedTermIds) {
      const datasets = [];
      const chartInstance = window.newsMetricsChart;

      selectedTermIds.forEach((termId, index) => {
        const termData = chartInstance.timelineData.find(item => item.term_id == termId);
        if (termData && termData.data) {
          datasets.push({
            label: termData.term_name,
            data: termData.data.map(point => ({
              x: point.date,
              y: point.count
            })),
            borderColor: chartInstance.colors[index % chartInstance.colors.length],
            backgroundColor: chartInstance.colors[index % chartInstance.colors.length] + '20',
            tension: 0.1,
            fill: false,
            pointRadius: 3,
            pointHoverRadius: 6
          });
        }
      });

      return { datasets: datasets };
    },

    /**
     * Create or update the Chart.js instance.
     */
    createChart: function(chartData) {
      const canvas = document.getElementById('taxonomy-timeline-chart');
      const ctx = canvas.getContext('2d');
      const chartInstance = window.newsMetricsChart;

      // Destroy existing chart
      if (chartInstance.chart) {
        chartInstance.chart.destroy();
      }

      // Create new chart
      chartInstance.chart = new Chart(ctx, {
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
                font: {
                  size: 14,
                  weight: 'bold'
                }
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
                font: {
                  size: 14,
                  weight: 'bold'
                }
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
              text: 'Topic Trends Over Time (Last 90 Days)',
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
              cornerRadius: 6
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
     * Reset chart to default term selection.
     */
    resetToDefault: function() {
      const selector = document.getElementById('term-selector');
      if (!selector) return;

      // Clear all selections
      for (let option of selector.options) {
        option.selected = false;
      }

      // Select top 5 terms
      for (let i = 0; i < Math.min(5, selector.options.length); i++) {
        selector.options[i].selected = true;
      }

      this.updateChart();
    },

    /**
     * Clear chart and all selections.
     */
    clearChart: function() {
      const selector = document.getElementById('term-selector');
      if (selector) {
        for (let option of selector.options) {
          option.selected = false;
        }
      }

      const chartInstance = window.newsMetricsChart;
      if (chartInstance && chartInstance.chart) {
        chartInstance.chart.destroy();
        chartInstance.chart = null;
      }

      this.updateStatus('Chart cleared', 'info');
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

      // Update debug status
      const debugEl = document.getElementById('chart-data-status');
      if (debugEl) {
        debugEl.textContent = 'Data status: ' + message + ' (' + new Date().toLocaleTimeString() + ')';
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
    }
  };

})(jQuery, Drupal, drupalSettings);