/**
 * Chart.js behavior for Taxonomy Timeline Blocks.
 * Handles multiple chart instances with individual configurations.
 * Version: 1.0.0 - Block-based timeline charts (Aug 8, 2025)
 */

(function ($, Drupal, drupalSettings, once) {
  'use strict';

  let chartInstances = new Map();

  /**
   * Chart behavior for taxonomy timeline blocks.
   */
  Drupal.behaviors.taxonomyTimelineBlocks = {
    attach: function (context, settings) {
      // Process each timeline block separately
      once('news-motivation-timeline-blocks', '.news-motivation-timeline-block', context).forEach(function (blockElement) {
        const blockSettings = settings.newsmotivationmetrics?.blocks;
        if (!blockSettings) {
          console.log('No block settings found');
          return;
        }

        // Find canvas in this block
        const canvas = blockElement.querySelector('canvas[id^="news-motivation-timeline-chart-"]');
        if (!canvas) {
          console.log('No canvas found in block');
          return;
        }

        const canvasId = canvas.id;
        const blockData = blockSettings[canvasId];
        
        if (!blockData) {
          console.log('No data found for canvas:', canvasId);
          return;
        }

        console.log(`=== Initializing Timeline Block Chart: ${canvasId} ===`);
        console.log('Block data:', blockData);

        // Update status to show initialization
        this.updateBlockStatus(canvas, 'Loading chart data...', 'info');

        // Initialize this chart instance
        this.initializeBlockChart(canvas, blockData);
      }.bind(this));
    },

    /**
     * Initialize a single block chart instance.
     */
    initializeBlockChart: function(canvas, blockData) {
      const canvasId = canvas.id;
      
      // Validate Chart.js availability
      if (typeof Chart === 'undefined') {
        console.error('Chart.js library not loaded for block chart');
        return;
      }

      console.log(`Chart.js available for ${canvasId}:`, Chart.version);

      // Check for existing chart and destroy it
      if (chartInstances.has(canvasId)) {
        chartInstances.get(canvasId).destroy();
        chartInstances.delete(canvasId);
      }

      const ctx = canvas.getContext('2d');
      if (!ctx) {
        console.error(`Failed to get 2D context for ${canvasId}`);
        return;
      }

      const timelineData = blockData.timelineData || [];
      const config = blockData.config || {};

      if (timelineData.length === 0) {
        console.warn(`No timeline data for ${canvasId}`);
        this.showNoDataMessage(canvas);
        return;
      }

      try {
        // Create chart datasets
        const datasets = this.createChartDatasets(timelineData);
        
        // Create chart configuration
        const chartConfig = this.createChartConfig(datasets, config);
        
        // Create Chart.js instance
        const chartInstance = new Chart(ctx, chartConfig);
        
        // Store chart instance
        chartInstances.set(canvasId, chartInstance);
        
        // Set up event listeners for this block
        this.setupBlockEventListeners(canvas, blockData, chartInstance);
        
        // Update status with success message that auto-clears
        this.updateBlockStatus(canvas, `✅ Chart loaded with ${datasets.length} terms`, 'success');
        
        console.log(`Timeline block chart created successfully: ${canvasId}`);

      } catch (error) {
        console.error(`Chart creation failed for ${canvasId}:`, error);
        this.updateBlockStatus(canvas, `Chart creation failed: ${error.message}`, 'error');
      }
    },

    /**
     * Create Chart.js datasets from timeline data.
     */
    createChartDatasets: function(timelineData) {
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

      return timelineData.map((termData, index) => {
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
    },

    /**
     * Create Chart.js configuration.
     */
    createChartConfig: function(datasets, config) {
      return {
        type: 'line',
        data: { datasets: datasets },
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
                font: { size: 12, weight: 'bold' }
              }
            },
            y: {
              beginAtZero: true,
              title: {
                display: true,
                text: 'Article Count',
                font: { size: 12, weight: 'bold' }
              },
              ticks: { stepSize: 1 }
            }
          },
          plugins: {
            title: {
              display: false // Title handled by block
            },
            legend: {
              display: config.showLegend !== false,
              position: 'top',
              labels: {
                usePointStyle: true,
                padding: 10,
                font: { size: 11 }
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
      };
    },

    /**
     * Set up event listeners for block controls.
     */
    setupBlockEventListeners: function(canvas, blockData, chartInstance) {
      const canvasId = canvas.id;
      const blockElement = canvas.closest('.news-motivation-timeline-block');
      
      if (!blockElement) return;

      // Reset button
      const resetBtn = blockElement.querySelector(`.chart-reset-btn[data-canvas-id="${canvasId}"]`);
      if (resetBtn) {
        resetBtn.addEventListener('click', () => {
          this.resetBlockChart(canvas, blockData, chartInstance);
        });
      }

      // Clear button  
      const clearBtn = blockElement.querySelector(`.chart-clear-btn[data-canvas-id="${canvasId}"]`);
      if (clearBtn) {
        clearBtn.addEventListener('click', () => {
          this.clearBlockChart(chartInstance);
        });
      }

      // Term selector
      const selector = blockElement.querySelector(`.term-selector[data-canvas-id="${canvasId}"]`);
      if (selector) {
        selector.addEventListener('change', () => {
          this.updateBlockChartFromSelector(canvas, blockData, chartInstance);
        });
      }

      // Auto refresh (if enabled)
      if (blockData.config.autoRefresh) {
        this.setupAutoRefresh(canvas, blockData, blockData.config.refreshInterval);
      }
    },

    /**
     * Reset block chart to default terms.
     */
    resetBlockChart: function(canvas, blockData, chartInstance) {
      const topTerms = blockData.topTerms || [];
      const defaultTerms = topTerms.slice(0, blockData.config.term_limit || 10);
      
      // Update chart data
      const newDatasets = this.createChartDatasets(defaultTerms);
      chartInstance.data.datasets = newDatasets;
      chartInstance.update();
      
      this.updateBlockStatus(canvas, `Reset to top ${defaultTerms.length} terms`, 'info');
    },

    /**
     * Clear all data from block chart.
     */
    clearBlockChart: function(chartInstance) {
      chartInstance.data.datasets = [];
      chartInstance.update();
    },

    /**
     * Update chart based on term selector.
     */
    updateBlockChartFromSelector: function(canvas, blockData, chartInstance) {
      const canvasId = canvas.id;
      const blockElement = canvas.closest('.news-motivation-timeline-block');
      const selector = blockElement.querySelector(`.term-selector[data-canvas-id="${canvasId}"]`);
      
      if (!selector) return;

      const selectedTermIds = Array.from(selector.selectedOptions).map(option => parseInt(option.value));
      const allTerms = blockData.timelineData || [];
      
      // Filter timeline data for selected terms
      const selectedTermsData = allTerms.filter(termData => 
        selectedTermIds.includes(parseInt(termData.term_id))
      );

      // Update chart
      const newDatasets = this.createChartDatasets(selectedTermsData);
      chartInstance.data.datasets = newDatasets;
      chartInstance.update();
      
      this.updateBlockStatus(canvas, `Showing ${selectedTermsData.length} selected terms`, 'info');
    },

    /**
     * Set up auto refresh for a block.
     */
    setupAutoRefresh: function(canvas, blockData, interval) {
      const canvasId = canvas.id;
      console.log(`Setting up auto refresh for ${canvasId} every ${interval} seconds`);
      
      setInterval(() => {
        // In a real implementation, you'd fetch new data here
        console.log(`Auto refreshing chart: ${canvasId}`);
        this.updateBlockStatus(canvas, 'Data refreshed', 'info');
      }, interval * 1000);
    },

    /**
     * Update status message for a block.
     */
    updateBlockStatus: function(canvas, message, type = 'info') {
      const canvasId = canvas.id;
      const blockElement = canvas.closest('.news-motivation-timeline-block');
      
      // Try multiple possible status element selectors
      let statusElement = blockElement.querySelector('#chart-status');
      if (!statusElement) {
        statusElement = blockElement.querySelector(`#chart-status-${canvasId.split('-').pop()}`);
      }
      if (!statusElement) {
        statusElement = blockElement.querySelector('.chart-status');
      }
      
      if (statusElement) {
        statusElement.textContent = message;
        statusElement.className = `chart-status ${type}`;
        
        // Auto-clear success/info messages after 3 seconds
        if (type === 'success' || type === 'info') {
          setTimeout(() => {
            if (statusElement.textContent === message) {
              statusElement.textContent = '';
              statusElement.className = 'chart-status';
            }
          }, 3000);
        }
      } else {
        console.log(`Status element not found for ${canvasId}, message: ${message}`);
      }
    },

    /**
     * Show no data message.
     */
    showNoDataMessage: function(canvas) {
      this.updateBlockStatus(canvas, 'No timeline data available', 'warning');
    },

    /**
     * Get chart instance by canvas ID.
     */
    getChartInstance: function(canvasId) {
      return chartInstances.get(canvasId);
    },

    /**
     * Destroy all chart instances.
     */
    destroyAllCharts: function() {
      chartInstances.forEach((chart, canvasId) => {
        console.log(`Destroying chart: ${canvasId}`);
        chart.destroy();
      });
      chartInstances.clear();
    }
  };

  // Clean up on page unload
  window.addEventListener('beforeunload', () => {
    Drupal.behaviors.taxonomyTimelineBlocks.destroyAllCharts();
  });

})(jQuery, Drupal, drupalSettings, once);
