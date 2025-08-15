/**
 * @file
 * Recent Activity Timeline Chart functionality using Chart.js
 */

(function (Drupal, drupalSettings, Chart) {
  'use strict';

  Drupal.behaviors.recentActivityTimelineChart = {
    attach: function (context, settings) {
      const charts = context.querySelectorAll('.recent-activity-timeline-chart');
      
      charts.forEach(function(canvas) {
        if (canvas.getAttribute('data-chart-initialized')) {
          return;
        }
        
        const chartId = canvas.getAttribute('id');
        const chartData = settings.newsmotivationmetrics_activity?.[chartId];
        
        if (!chartData) {
          console.warn('No chart data found for', chartId);
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
    const ctx = canvas.getContext('2d');
    
    const config = {
      type: 'line',
      data: {
        datasets: chartData.timelineData
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
    
    return chart;
  }

  /**
   * Setup source filtering functionality
   */
  function setupSourceFiltering(canvas, chartData) {
    const chartId = canvas.getAttribute('id');
    const sourceSelector = document.querySelector(`#activity-source-selector[data-chart-target="${chartId}"]`);
    
    if (!sourceSelector) {
      return;
    }
    
    sourceSelector.addEventListener('change', function() {
      const chart = canvas.chartInstance;
      if (!chart) return;
      
      const selectedSources = Array.from(this.selectedOptions).map(option => option.value);
      
      // Update dataset visibility based on selection
      chart.data.datasets.forEach((dataset, index) => {
        const sourceName = dataset.label.replace(/ \(Published\)| \(Processing\)/, '');
        const shouldShow = selectedSources.includes(sourceName);
        
        const meta = chart.getDatasetMeta(index);
        meta.hidden = !shouldShow;
      });
      
      chart.update('none'); // Update without animation for better performance
    });
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

})(Drupal, drupalSettings, Chart);
