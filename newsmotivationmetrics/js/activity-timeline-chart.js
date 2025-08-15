/**
 * @file
 * Recent Activity Timeline Chart functionality using Chart.js
 */

(function (Drupal, drupalSettings) {
  'use strict';

  console.log('=== Recent Activity Timeline Chart Script Loading ===');
  console.log('Drupal object:', typeof Drupal);
  console.log('drupalSettings object:', typeof drupalSettings);
  console.log('Chart object:', typeof Chart);
  console.log('=== Recent Activity Chart Script Loaded Successfully ===');

  Drupal.behaviors.recentActivityTimelineChart = {
    attach: function (context, settings) {
      console.log('=== Recent Activity Timeline Chart Behavior Attach Called ===');
      console.log('Context type:', typeof context);
      console.log('Context element:', context);
      console.log('Settings keys:', Object.keys(settings));
      
      // Check if Chart.js is loaded
      if (typeof Chart === 'undefined') {
        console.error('Chart.js is not loaded for Recent Activity Timeline Chart');
        return;
      }

      const charts = context.querySelectorAll('.recent-activity-timeline-chart');
      console.log('Found', charts.length, 'Recent Activity chart canvases to process');
      
      charts.forEach(function(canvas) {
        console.log('✅ Processing Recent Activity canvas with ID:', canvas.id);
        
        if (canvas.getAttribute('data-chart-initialized')) {
          console.log('Skipping already initialized Recent Activity canvas:', canvas.id);
          return;
        }
        
        const chartId = canvas.getAttribute('id');
        const chartData = settings.newsmotivationmetrics_activity?.[chartId];
        
        if (!chartData) {
          console.warn('No chart data found for', chartId);
          return;
        }
        
        console.log('Initializing Recent Activity Timeline Chart:', chartId, chartData);
        
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

      const config = {
        type: 'line',
        data: {
          datasets: chartData.timelineData || []
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
              display: false  // Title handled by block header
            },
            legend: {
              display: true,
              position: 'top',
              labels: {
                usePointStyle: true,
                padding: 15,
                font: {
                  size: 12
                },
                generateLabels: function(chart) {
                  const original = Chart.defaults.plugins.legend.labels.generateLabels;
                  const labels = original.call(this, chart);
                  
                  // Group published and processing for same source
                  const groupedLabels = [];
                  const processedSources = new Set();
                  
                  labels.forEach(label => {
                    const sourceName = label.text.replace(/ \(Published\)| \(Processing\)/, '');
                    
                    if (!processedSources.has(sourceName)) {
                      processedSources.add(sourceName);
                      
                      // Find both published and processing datasets for this source
                      const publishedDataset = chart.data.datasets.find(d => 
                        d.label === sourceName + ' (Published)'
                      );
                      const processingDataset = chart.data.datasets.find(d => 
                        d.label === sourceName + ' (Processing)'
                      );
                      
                      if (publishedDataset) {
                        groupedLabels.push({
                          text: sourceName,
                          fillStyle: publishedDataset.borderColor,
                          strokeStyle: publishedDataset.borderColor,
                          pointStyle: 'line',
                          datasetIndex: chart.data.datasets.indexOf(publishedDataset),
                          hidden: publishedDataset.hidden || false
                        });
                      }
                    }
                  });
                  
                  return groupedLabels;
                }
              },
              onClick: function(e, legendItem, legend) {
                const chart = legend.chart;
                const sourceName = legendItem.text;
                
                // Toggle visibility for both published and processing datasets
                chart.data.datasets.forEach((dataset, index) => {
                  if (dataset.label.startsWith(sourceName + ' (')) {
                    const meta = chart.getDatasetMeta(index);
                    meta.hidden = meta.hidden === null ? !dataset.hidden : null;
                  }
                });
                
                chart.update();
              }
            },
            tooltip: {
              mode: 'index',
              intersect: false,
              backgroundColor: 'rgba(0, 0, 0, 0.8)',
              titleColor: '#fff',
              bodyColor: '#fff',
              borderColor: '#ccc',
              borderWidth: 1,
              cornerRadius: 6,
              displayColors: true,
              callbacks: {
                title: function(tooltipItems) {
                  const date = new Date(tooltipItems[0].parsed.x);
                  return date.toLocaleDateString('en-US', {
                    weekday: 'short',
                    year: 'numeric',
                    month: 'short',
                    day: 'numeric'
                  });
                },
                label: function(context) {
                  const label = context.dataset.label || '';
                  const value = context.parsed.y;
                  const isProcessing = label.includes('(Processing)');
                  const icon = isProcessing ? '⏳' : '📰';
                  
                  return `${icon} ${label}: ${value} article${value !== 1 ? 's' : ''}`;
                },
                footer: function(tooltipItems) {
                  let total = 0;
                  tooltipItems.forEach(item => {
                    total += item.parsed.y;
                  });
                  return total > 0 ? `Total: ${total} articles` : '';
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
      
      console.log('Recent Activity Timeline Chart created successfully:', canvas.id);
      return chart;
      
    } catch (error) {
      console.error('Error creating Recent Activity Timeline Chart:', error);
      return null;
    }
  }

  /**
   * Setup source filtering functionality
   */
  function setupSourceFiltering(context) {
    try {
      const container = context.querySelector('.recent-activity-timeline-chart-container');
      if (!container) {
        console.warn('Recent Activity Timeline Chart container not found');
        return;
      }

      // Source toggle functionality
      const sourceToggles = container.querySelectorAll('.source-toggle');
      if (sourceToggles.length === 0) {
        console.warn('No source toggles found in Recent Activity Timeline Chart');
        return;
      }

      const canvas = container.querySelector('canvas');
      if (!canvas || !canvas.chartInstance) {
        console.error('Canvas or chart instance not found for source filtering');
        return;
      }

      const chart = canvas.chartInstance;

      sourceToggles.forEach(toggle => {
        toggle.addEventListener('change', function() {
          const sourceName = this.dataset.source;
          const isVisible = this.checked;
          
          if (!sourceName) {
            console.warn('Source name not found in toggle element');
            return;
          }

          // Find datasets for this source
          let datasetsUpdated = false;
          chart.data.datasets.forEach((dataset, index) => {
            if (dataset.label && dataset.label.includes(sourceName)) {
              const meta = chart.getDatasetMeta(index);
              meta.hidden = !isVisible;
              datasetsUpdated = true;
            }
          });

          if (datasetsUpdated) {
            chart.update();
            console.log(`Updated visibility for source: ${sourceName} to ${isVisible ? 'visible' : 'hidden'}`);
          } else {
            console.warn(`No datasets found for source: ${sourceName}`);
          }
        });
      });

      // Date range filtering (if implemented)
      const dateRangeInputs = container.querySelectorAll('input[type="date"]');
      dateRangeInputs.forEach(input => {
        input.addEventListener('change', function() {
          // Date range filtering would be implemented here
          console.log('Date range filter changed:', this.value);
        });
      });

      console.log(`Source filtering setup complete for ${sourceToggles.length} sources`);
      
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

  /**
   * Debug helper function
   */
  window.debugActivityChart = function(chartId) {
    const canvas = document.getElementById(chartId);
    if (!canvas || !canvas.chartInstance) {
      console.log('Chart not found or not initialized');
      return;
    }
    
    const chart = canvas.chartInstance;
    const chartData = drupalSettings.newsmotivationmetrics_activity?.[chartId];
    
    console.log('Chart Debug Info:', {
      chartId: chartId,
      datasetsCount: chart.data.datasets.length,
      visibleDatasets: chart.data.datasets.filter((_, i) => !chart.getDatasetMeta(i).hidden).length,
      chartData: chartData,
      canvas: canvas,
      chart: chart
    });
    
    return {
      chart: chart,
      data: chartData,
      canvas: canvas
    };
  };

})(Drupal, drupalSettings);
