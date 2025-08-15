/**
 * @file
 * Recent Activity Timeline Chart functionality using Chart.js
 */

(function (Drupal, drupalSettings) {
  'use strict';

  Drupal.behaviors.recentActivityTimelineChart = {
    attach: function (context, settings) {
      console.log('=== Recent Activity Timeline Chart Script Loading ===');
      console.log('Drupal object:', typeof Drupal);
      console.log('drupalSettings object:', typeof drupalSettings);

      // Check if Chart.js is loaded
      if (typeof Chart === 'undefined') {
        console.error('Chart.js is not loaded for Recent Activity Timeline Chart');
        return;
      }

      console.log('=== Chart Script Loaded Successfully ===');
      console.log('Environment check:');
      console.log('- Drupal available:', typeof Drupal !== 'undefined');
      console.log('- jQuery available:', typeof $ !== 'undefined');
      console.log('- drupalSettings available:', typeof drupalSettings !== 'undefined');

      const charts = context.querySelectorAll('.activity-timeline-chart');
      console.log('Found', charts.length, 'activity timeline chart canvases to process');
      
      charts.forEach(function(canvas) {
        console.log('✅ Processing canvas with ID:', canvas.getAttribute('id'));
        
        if (canvas.getAttribute('data-chart-initialized')) {
          console.log('Skipping already processed canvas:', canvas.getAttribute('id'));
          return;
        }
        
        const chartId = canvas.getAttribute('id');
        const chartData = settings.newsmotivationmetrics_activity?.[chartId];
        
        if (!chartData) {
          console.warn('❌ No chart data found for', chartId);
          console.log('Available activity data keys:', Object.keys(settings.newsmotivationmetrics_activity || {}));
          return;
        }
        
        console.log('📊 Chart data loaded:', typeof chartData);
        console.log('Activity data available:', chartData.timelineData?.length || 0, 'datasets');
        
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
              display: true,
              text: 'Recent Activity Timeline (Last 90 Days)',
              font: {
                size: 16
              }
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
      
      console.log('Recent Activity Timeline Chart created successfully with', config.data.datasets.length, 'datasets');
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
        console.warn('Activity Timeline Chart container not found for canvas:', canvas.id);
        return;
      }

      // Source toggle functionality - look for select elements with activity-source-selector prefix
      const sourceSelectors = container.querySelectorAll('select[id^="activity-source-selector"]');
      if (sourceSelectors.length === 0) {
        console.warn('No source selectors found in Activity Timeline Chart for canvas:', canvas.id);
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
          console.log(`Updated visibility for selected sources:`, selectedSources);
        });
      });

      console.log(`Source filtering setup complete for ${sourceSelectors.length} selectors`);
      
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
