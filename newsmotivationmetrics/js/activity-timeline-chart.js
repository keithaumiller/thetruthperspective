/**
 * @file
 * Recent Activity Timeline Chart functionality using Chart.js
 */

(function (Drupal, drupalSettings) {
  'use strict';

  Drupal.behaviors.recentActivityTimelineChart = {
    attach: function (context, settings) {
      // Check if Chart.js is loaded
      if (typeof Chart === 'undefined') {
        console.error('Chart.js is not loaded for Recent Activity Timeline Chart');
        return;
      }

      const charts = context.querySelectorAll('.activity-timeline-chart');
      
      charts.forEach(function(canvas) {
        if (canvas.getAttribute('data-chart-initialized')) {
          return;
        }
        
        const chartId = canvas.getAttribute('id');
        const chartData = settings.newsmotivationmetrics_activity?.[chartId];
        
        if (!chartData) {
          console.error('❌ No chart data found for', chartId);
          return;
        }
        
        // Initialize the timeline chart
        initializeActivityTimelineChart(canvas, chartData);
        
        // Set up source filtering
        setupSourceFiltering(canvas, chartData);
        
        canvas.setAttribute('data-chart-initialized', 'true');
      });
    }
  };

  /**
   * Initialize the activity timeline chart
   */
  function initializeActivityTimelineChart(canvas, chartData) {
    try {
      const ctx = canvas.getContext('2d');
      
      if (!ctx) {
        console.error('Failed to get 2D context for canvas:', canvas.id);
        return;
      }

      // Show 5 datasets by default
      const datasets = (chartData.timelineData || []).slice(0, 5);

      const config = {
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
          plugins: {
            title: {
              display: true,
              text: 'Recent Activity Timeline (Last 90 Days)',
              font: {
                size: 16
              }
            },
            legend: {
              display: true,
              position: 'top',
              onClick: function(e, legendItem, legend) {
                const chart = legend.chart;
                const index = legendItem.datasetIndex;
                const meta = chart.getDatasetMeta(index);
                
                // Toggle visibility
                meta.hidden = meta.hidden === null ? !chart.data.datasets[index].hidden : null;
                
                chart.update();
              },
              labels: {
                usePointStyle: true,
                padding: 20,
                generateLabels: function(chart) {
                  const original = Chart.defaults.plugins.legend.labels.generateLabels;
                  const labels = original.call(this, chart);
                  
                  labels.forEach(function(label) {
                    // Check if dataset is hidden
                    const meta = chart.getDatasetMeta(label.datasetIndex);
                    label.hidden = meta.hidden;
                  });
                  
                  return labels;
                }
              }
            },
            tooltip: {
              mode: 'index',
              intersect: false,
              backgroundColor: 'rgba(0,0,0,0.8)',
              titleColor: 'white',
              bodyColor: 'white',
              borderColor: 'rgba(255,255,255,0.2)',
              borderWidth: 1,
              callbacks: {
                title: function(tooltipItems) {
                  // Format date for tooltip title
                  if (tooltipItems.length > 0) {
                    const date = new Date(tooltipItems[0].parsed.x);
                    return date.toLocaleDateString('en-US', {
                      year: 'numeric',
                      month: 'long',
                      day: 'numeric'
                    });
                  }
                  return '';
                },
                label: function(context) {
                  const label = context.dataset.label || '';
                  const value = context.parsed.y;
                  return `${label}: ${value} articles`;
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
                  day: 'MMM dd',
                  week: 'MMM dd',
                  month: 'MMM'
                },
                tooltipFormat: 'MMM dd, yyyy'
              },
              title: {
                display: true,
                text: 'Publication Date',
                font: {
                  size: 12,
                  weight: 'bold'
                }
              },
              grid: {
                display: true,
                color: 'rgba(0, 0, 0, 0.1)'
              }
            },
            y: {
              beginAtZero: true,
              title: {
                display: true,
                text: 'Article Count',
                font: {
                  size: 12,
                  weight: 'bold'
                }
              },
              grid: {
                display: true,
                color: 'rgba(0, 0, 0, 0.1)'
              },
              ticks: {
                stepSize: 1,
                callback: function(value) {
                  return Number.isInteger(value) ? value : '';
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

      // Create the chart
      const chart = new Chart(ctx, config);
      
      // Store chart instance for later access
      canvas.chartInstance = chart;
      
      // Add resize observer for responsive behavior
      if (window.ResizeObserver) {
        const resizeObserver = new ResizeObserver(function() {
          chart.resize();
        });
        resizeObserver.observe(canvas.parentElement);
      }
      
      return chart;
      
    } catch (error) {
      console.error('Error creating Recent Activity Timeline Chart:', error);
      return null;
    }
  }

  /**
   * Setup source filtering functionality
   */
  function setupSourceFiltering(canvas, chartData) {
    try {
      // Find the container that holds this canvas
      const container = canvas.closest('.timeline-chart-container') || 
                       canvas.closest('.activity-timeline') ||
                       canvas.parentElement;
      
      if (!container) {
        console.error('Activity Timeline Chart container not found for canvas:', canvas.id);
        return;
      }

      // Source toggle functionality - look for select elements with activity-source-selector prefix
      const sourceSelectors = container.querySelectorAll('select[id^="activity-source-selector"]');
      if (sourceSelectors.length === 0) {
        console.error('No source selectors found in Activity Timeline Chart for canvas:', canvas.id);
        return;
      }

      sourceSelectors.forEach(selector => {
        const chartTarget = selector.getAttribute('data-chart-target');
        
        // Use the canvas directly since we already have it
        if (!canvas || !canvas.chartInstance) {
          console.error('Canvas or chart instance not found for source filtering:', canvas.id);
          return;
        }

        const chart = canvas.chartInstance;

        selector.addEventListener('change', function() {
          const selectedSources = Array.from(this.selectedOptions).map(option => option.value);
          
          // Hide/show datasets based on selected sources
          chart.data.datasets.forEach((dataset, index) => {
            const meta = chart.getDatasetMeta(index);
            const sourceName = dataset.label.replace(/ \(Published\)| \(Processing\)/, '');
            
            meta.hidden = !selectedSources.includes(sourceName);
          });

          chart.update();
        });
      });
      
    } catch (error) {
      console.error('Error setting up source filtering:', error);
    }
  }

  /**
   * Helper function to format dates consistently
   */
  function formatChartDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
      month: 'short',
      day: 'numeric'
    });
  }

})(Drupal, drupalSettings);
