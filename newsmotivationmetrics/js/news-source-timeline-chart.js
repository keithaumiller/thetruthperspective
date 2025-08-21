(function ($, Drupal, drupalSettings) {
  'use strict';

  // Validate environment
  if (typeof Drupal === 'undefined' || typeof drupalSettings === 'undefined' || typeof $ === 'undefined') {
    console.error('❌ Required dependencies not available');
    return;
  }

  Drupal.behaviors.newsSourceTimelineChart = {
    attach: function (context, settings) {
      // Find all news source timeline chart canvases
      const canvases = $(context).find('canvas.news-source-timeline-chart').addBack('canvas.news-source-timeline-chart');

      canvases.each(function() {
        const canvas = this;
        const canvasId = canvas.id;

        // Skip if already processed
        if (canvas.hasAttribute('data-chart-processed')) {
          return;
        }

        // Mark as processed
        canvas.setAttribute('data-chart-processed', 'true');

        // Initialize chart for this canvas
        initializeChart(canvas, canvasId, settings);
      });
    }
  };

  let chart = null;
  let sourceSelector = null;

  // Function to assign colors to sources based on strict rules
  function assignSourceColors(selectedSources) {
    // 9-color scheme: 3 shades per source (Dark, Medium, Light)
    const baseColors = [
      { 
        name: 'red', 
        bias: '#7F1D1D',        // Dark Red
        credibility: '#DC2626',  // Red  
        sentiment: '#FCA5A5'     // Light Red
      },
      { 
        name: 'blue', 
        bias: '#1E3A8A',        // Dark Blue
        credibility: '#3B82F6',  // Blue
        sentiment: '#93C5FD'     // Light Blue
      },
      { 
        name: 'green', 
        bias: '#14532D',        // Dark Green
        credibility: '#16A34A',  // Green
        sentiment: '#86EFAC'     // Light Green
      }
    ];
    
    const sourceColorMap = {};
    
    // Get unique base sources and assign colors in rotation
    const uniqueSources = [...new Set(selectedSources)];
    
    uniqueSources.forEach((source, index) => {
      const colorIndex = index % 3; // Rotate through 0, 1, 2
      sourceColorMap[source] = colorIndex;
    });
    
    return {
      sourceColorMap,
      baseColors,
      originalToNormalized: {} // Not needed anymore
    };
  }

  function initializeChart(canvas, canvasId, settings) {
    try {
      // Find associated source selector
      const selectorId = canvasId === 'news-source-timeline-chart' ? 'source-selector' : 'source-selector-' + canvasId.split('-').pop();
      sourceSelector = document.getElementById(selectorId);
      
      if (!sourceSelector) {
        // Try fallback approach - find by class
        sourceSelector = document.querySelector('.source-selector');
      }

      // Get chart data from settings
      const chartData = settings.newsmotivationmetrics_sources || {};
      
      if (!chartData.timelineData || !Array.isArray(chartData.timelineData) || chartData.timelineData.length === 0) {
        throw new Error('No news source timeline data available');
      }

      createChart(canvas, chartData);
      setupEventListeners(canvasId);
      
      // Set default selection to top 5 sources (highest article count)
      if (sourceSelector) {
        resetToTopSources();
      }

    } catch (error) {
      console.error('❌ Chart initialization error:', error);
      
      // Update status element with error message
      const statusElement = document.querySelector('[id*="chart-status"]');
      if (statusElement) {
        statusElement.textContent = '⚠️ Chart initialization failed: ' + error.message;
      }
    }
  }

  function createChart(canvas, data) {
    try {
      if (!data.timelineData || !Array.isArray(data.timelineData)) {
        throw new Error('Invalid timeline data structure');
      }

      // Check if canvas already has a chart and destroy it
      if (canvas.chart) {
        canvas.chart.destroy();
        canvas.chart = null;
      }
      
      // Also check Chart.js registry for any charts using this canvas
      if (window.Chart && window.Chart.getChart) {
        const existingChart = window.Chart.getChart(canvas);
        if (existingChart) {
          existingChart.destroy();
        }
      }

      // Get unique source names from the data for color assignment
      const uniqueSources = [...new Set(data.timelineData.map(item => {
        // Extract base source name from dataset names like "FOXNews.com - Bias Rating"
        const sourceName = item.source_name;
        const baseName = sourceName.includes(' - ') ? sourceName.split(' - ')[0] : sourceName;
        return baseName;
      }))];
      
      // Get color assignments for all sources (simple rotation)
      const { sourceColorMap, baseColors } = assignSourceColors(uniqueSources);
    
      // Prepare datasets from timeline data - Show 5 sources by default
      const datasets = data.timelineData.slice(0, 15).map((sourceData, index) => { // 5 sources × 3 metrics = 15 datasets max
        // Get the source name and metric type
        let sourceName = sourceData.source_name;
        const metricType = sourceData.metric_type;
        
        // Extract base source name from dataset names like "FOXNews.com - Bias Rating"
        const baseSourceName = sourceName.includes(' - ') ? sourceName.split(' - ')[0] : sourceName;
        
        // Get color scheme for this source using simple lookup
        const colorIndex = sourceColorMap[baseSourceName] !== undefined ? sourceColorMap[baseSourceName] : 0; // Default to first color family if not found
        const colorScheme = baseColors[colorIndex];
        
        // Get color for this metric type
        const color = colorScheme[metricType] || '#6B7280';
        
        return {
          label: `${baseSourceName} - ${metricType}`,
          data: sourceData.data ? sourceData.data.map(point => ({
            x: point.date,
            y: point.value
          })) : [],
          borderColor: color,
          backgroundColor: color + '20', // Add transparency for fill
          fill: false,
          tension: 0.4
        };
      });

      // Get date labels from the first dataset
      const labels = data.timelineData.length > 0 && data.timelineData[0].data ? 
        data.timelineData[0].data.map(point => point.date) : [];

      // Create Chart.js instance
      chart = new Chart(canvas, {
        type: 'line',
        data: {
          labels: labels,
          datasets: datasets
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          interaction: {
            mode: 'index',
            intersect: false,
          },
          plugins: {
            title: {
              display: true,
              text: 'News Source Metrics Over Time (Last 90 Days)',
              font: {
                size: 16
              }
            },
            legend: {
              position: 'top',
              labels: {
                usePointStyle: true,
                padding: 20
              }
            },
            tooltip: {
              mode: 'index',
              intersect: false,
              backgroundColor: 'rgba(0,0,0,0.8)',
              titleColor: 'white',
              bodyColor: 'white',
              borderColor: 'rgba(255,255,255,0.2)',
              borderWidth: 1
            }
          },
          scales: {
            x: {
              display: true,
              title: {
                display: true,
                text: 'Date'
              },
              type: 'time',
              time: {
                parser: 'yyyy-MM-dd',
                tooltipFormat: 'MMM dd, yyyy',
                displayFormats: {
                  day: 'MMM dd',
                  week: 'MMM dd',
                  month: 'MMM yyyy'
                }
              }
            },
            y: {
              display: true,
              title: {
                display: true,
                text: 'Score'
              },
              beginAtZero: true
            }
          }
        }
      });

      // Store chart reference on canvas
      canvas.chart = chart;
      
      // Update status element
      const statusElement = document.querySelector('[id*="chart-status"]');
      if (statusElement) {
        statusElement.textContent = '✅ Chart loaded with ' + datasets.length + ' datasets';
      }
      
    } catch (error) {
      console.error('❌ Chart creation error:', error);
      
      // Update status element with error message
      const statusElement = document.querySelector('[id*="chart-status"]');
      if (statusElement) {
        statusElement.textContent = '⚠️ Chart creation failed: ' + error.message;
      }
    }
  }

  function setupEventListeners(canvasId) {
    // Source selector change event
    if (sourceSelector) {
      sourceSelector.addEventListener('change', updateChart);
    }
  }

  function updateChart() {
    if (!chart || !sourceSelector) return;

    const selectedSourceIds = Array.from(sourceSelector.selectedOptions).map(option => parseInt(option.value));

    // Combine timeline data from both top sources and extended sources
    let allData = drupalSettings.newsmotivationmetrics_sources.timelineData || [];
    
    // Add extended sources data if available
    if (drupalSettings.newsmotivationmetrics_sources.extendedSources && 
        drupalSettings.newsmotivationmetrics_sources.extendedSources.timelineData) {
      const extendedData = drupalSettings.newsmotivationmetrics_sources.extendedSources.timelineData;
      
      // Merge extended data, avoiding duplicates
      const existingSourceKeys = new Set(allData.map(item => item.source_id + '_' + item.metric_type));
      const newExtendedData = extendedData.filter(item => !existingSourceKeys.has(item.source_id + '_' + item.metric_type));
      allData = [...allData, ...newExtendedData];
    }

    // Filter datasets based on selected sources
    const filteredData = allData.filter(sourceData => selectedSourceIds.includes(sourceData.source_id));

    // Update chart datasets with simple color rotation
    // Get unique source names for color assignment
    const uniqueSelectedSources = [...new Set(filteredData.map(item => {
      // Extract base source name from dataset names like "FOXNews.com - Bias Rating"
      const sourceName = item.source_name;
      const baseName = sourceName.includes(' - ') ? sourceName.split(' - ')[0] : sourceName;
      return baseName;
    }))];
    
    // Get color assignments for selected sources (simple rotation)
    const { sourceColorMap, baseColors } = assignSourceColors(uniqueSelectedSources);

    chart.data.datasets = filteredData.map((sourceData, index) => {
      // Get the source name and metric type
      let sourceName = sourceData.source_name;
      const metricType = sourceData.metric_type;
      
      // Extract base source name from dataset names like "FOXNews.com - Bias Rating"
      const baseSourceName = sourceName.includes(' - ') ? sourceName.split(' - ')[0] : sourceName;
      
      // Get color scheme for this source using simple lookup
      const colorIndex = sourceColorMap[baseSourceName] !== undefined ? sourceColorMap[baseSourceName] : 0; // Default to first color family if not found
      const colorScheme = baseColors[colorIndex];
      
      // Get color for this metric type
      const color = colorScheme[metricType] || '#6B7280';

      return {
        label: `${baseSourceName} - ${metricType}`,
        data: sourceData.data ? sourceData.data.map(point => ({
          x: point.date,
          y: point.value
        })) : [],
        borderColor: color,
        backgroundColor: color + '20', // Add transparency for fill
        fill: false,
        tension: 0.4
      };
    });

    chart.update();
    
    // Update status message
    const statusElement = document.querySelector('[id*="chart-status"]');
    if (statusElement) {
      statusElement.textContent = `📊 Chart updated with ${filteredData.length} trend lines`;
    }
  }

  function resetToTopSources() {
    if (!sourceSelector) return;

    // Clear all selections
    Array.from(sourceSelector.options).forEach(option => {
      option.selected = false;
    });

    // Select top 5 options (top 5 sources by article count)
    const options = Array.from(sourceSelector.options);
    for (let i = 0; i < Math.min(5, options.length); i++) {
      if (options[i]) {
        options[i].selected = true;
      }
    }

    // Trigger update
    updateChart();
  }

  function clearAllSources() {
    if (!sourceSelector) return;

    // Clear all selections
    Array.from(sourceSelector.options).forEach(option => {
      option.selected = false;
    });

    // Update chart with empty data
    if (chart) {
      chart.data.datasets = [];
      chart.update();
      
      // Update status
      const statusElement = document.querySelector('[id*="chart-status"]');
      if (statusElement) {
        statusElement.textContent = '📊 Chart cleared - select sources to display data';
      }
    }
  }

})(jQuery, Drupal, drupalSettings);
