(function ($, Drupal, drupalSettings) {
  'use strict';

  console.log('=== News Source Timeline Chart Script Loading ===');
  console.log('Drupal object:', typeof Drupal);
  console.log('drupalSettings object:', typeof drupalSettings);

  // Validate environment
  if (typeof Drupal === 'undefined' || typeof drupalSettings === 'undefined' || typeof $ === 'undefined') {
    console.error('❌ Required dependencies not available');
    return;
  }

  console.log('=== Chart Script Loaded Successfully ===');

  console.log('Environment check:');
  console.log('- Drupal available:', typeof Drupal !== 'undefined');
  console.log('- jQuery available:', typeof $ !== 'undefined');
  console.log('- drupalSettings available:', typeof drupalSettings !== 'undefined');

  Drupal.behaviors.newsSourceTimelineChart = {
    attach: function (context, settings) {
      console.log('=== Drupal Behavior Attach Called ===');
      console.log('Context type:', typeof context);
      console.log('Context element:', context === document ? '#document' : context.tagName || 'Unknown', context === document ? '(' + window.location.href + ')' : '');
      console.log('Settings keys:', Object.keys(settings));

      // Find all news source timeline chart canvases
      const canvases = $(context).find('canvas.news-source-timeline-chart').addBack('canvas.news-source-timeline-chart');
      console.log('Found', canvases.length, 'chart canvases to process');

      canvases.each(function() {
        const canvas = this;
        const canvasId = canvas.id;

        // Skip if already processed
        if (canvas.hasAttribute('data-chart-processed')) {
          console.log('Skipping already processed canvas:', canvasId);
          return;
        }

        console.log('✅ Processing canvas with ID:', canvasId);

        // Mark as processed
        canvas.setAttribute('data-chart-processed', 'true');

        // Initialize chart for this canvas
        initializeChart(canvas, canvasId, settings);
      });
    }
  };

  let chart = null;
  let sourceSelector = null;

  function initializeChart(canvas, canvasId, settings) {
    try {
      // Find associated source selector
      const selectorId = canvasId === 'news-source-timeline-chart' ? 'source-selector' : 'source-selector-' + canvasId.split('-').pop();
      sourceSelector = document.getElementById(selectorId);
      
      if (!sourceSelector) {
        console.log('❌ Source selector not found with ID:', selectorId);
        // Try fallback approach - find by class
        sourceSelector = document.querySelector('.source-selector');
        if (sourceSelector) {
          console.log('✅ Found source selector using class fallback');
        }
      } else {
        console.log('✅ Found source selector with ID:', selectorId);
      }

      // Get chart data from settings
      const chartData = settings.newsmotivationmetrics || {};
      
      if (!chartData.timelineData || !Array.isArray(chartData.timelineData) || chartData.timelineData.length === 0) {
        throw new Error('No news source timeline data available');
      }

      console.log('📊 Chart data loaded:', {
        dataPoints: chartData.timelineData ? chartData.timelineData.length : 0,
        sourceCount: chartData.topSources ? chartData.topSources.length : 0,
        timestamp: Math.floor(Date.now() / 1000),
        date: new Date().toISOString().slice(0, 19).replace('T', ' '),
        php_version: chartData.debugInfo?.php_version || 'unknown',
        extendedSourcesAvailable: chartData.extendedSources ? 'Yes' : 'No',
        extendedSourcesCount: chartData.extendedSources?.timelineData?.length || 0
      });
      console.log('Timeline data available:', chartData.timelineData ? chartData.timelineData.length : 0, 'datasets');
      
      // Log extended sources availability
      if (chartData.extendedSources) {
        console.log('📈 Extended sources available:', chartData.extendedSources.timelineData?.length || 0, 'additional datasets');
      } else {
        console.log('⚠️ No extended sources data found');
      }

      console.log('🎯 Initializing Chart.js...');
      console.log('Canvas element:', canvas);
      console.log('Canvas ID:', canvasId);
      console.log('Data structure:', {
        timelineData: Array.isArray(chartData.timelineData) ? chartData.timelineData.length : 'invalid',
        topSources: Array.isArray(chartData.topSources) ? chartData.topSources.length : 'invalid',
        debugInfo: typeof chartData.debugInfo,
        extendedSources: typeof chartData.extendedSources
      });

      createChart(canvas, chartData);
      setupEventListeners(canvasId);

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
        console.log('Destroying existing chart on canvas...');
        canvas.chart.destroy();
        canvas.chart = null;
      }
      
      // Also check Chart.js registry for any charts using this canvas
      if (window.Chart && window.Chart.getChart) {
        const existingChart = window.Chart.getChart(canvas);
        if (existingChart) {
          console.log('Destroying existing chart from Chart.js registry...');
          existingChart.destroy();
        }
      }

      console.log('Processing', data.timelineData.length, 'datasets...');
    
      // Prepare datasets from timeline data - each source has 3 metrics
      const datasets = data.timelineData.map((sourceData, index) => {
        console.log(`Dataset ${index}: ${sourceData.source_name} with ${sourceData.data ? sourceData.data.length : 0} data points`);
        
        // Different colors for different metric types
        const colors = {
          bias: ['#FF6B6B', '#FF9F9F', '#FFCCCC'],        // Red shades for bias
          credibility: ['#4ECDC4', '#7DDED8', '#B3EEEC'], // Teal shades for credibility
          sentiment: ['#45B7D1', '#78C8E0', '#ABD9EF']     // Blue shades for sentiment
        };
        
        let colorSet = colors.bias; // default
        if (sourceData.metric_type === 'credibility') {
          colorSet = colors.credibility;
        } else if (sourceData.metric_type === 'sentiment') {
          colorSet = colors.sentiment;
        }
        
        const colorIndex = Math.floor(index / 3) % colorSet.length;
        
        return {
          label: sourceData.source_name,
          data: sourceData.data ? sourceData.data.map(point => ({
            x: point.date,
            y: point.value
          })) : [],
          borderColor: colorSet[colorIndex],
          backgroundColor: colorSet[colorIndex] + '20',
          fill: false,
          tension: 0.4
        };
      });

      // Get date labels from the first dataset
      const labels = data.timelineData.length > 0 && data.timelineData[0].data ? 
        data.timelineData[0].data.map(point => point.date) : [];

      console.log('Chart labels:', labels.slice(0, 5), '... (showing first 5)');
      console.log('Chart datasets:', datasets.length, 'total datasets');

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
          scales: {
            x: {
              type: 'time',
              time: {
                parser: 'yyyy-MM-dd',
                displayFormats: {
                  day: 'MMM dd',
                  week: 'MMM dd',
                  month: 'MMM yyyy'
                }
              },
              title: {
                display: true,
                text: 'Date'
              }
            },
            y: {
              beginAtZero: true,
              title: {
                display: true,
                text: 'Score/Rating'
              },
              ticks: {
                callback: function(value) {
                  return value.toFixed(1);
                }
              }
            }
          },
          plugins: {
            legend: {
              display: true,
              position: 'top'
            },
            tooltip: {
              mode: 'index',
              intersect: false,
              callbacks: {
                title: function(tooltipItems) {
                  return tooltipItems[0].label;
                },
                label: function(context) {
                  return context.dataset.label + ': ' + context.parsed.y.toFixed(2);
                }
              }
            }
          },
          interaction: {
            mode: 'index',
            intersect: false
          }
        }
      });

      // Store chart reference on canvas for future cleanup
      canvas.chart = chart;

      console.log('✅ Chart initialized successfully');
      
      // Update status element with success message
      const statusId = canvas.id === 'news-source-timeline-chart' ? 'chart-status' : 'chart-status-' + canvas.id.split('-').pop();
      const statusElement = document.getElementById(statusId) || document.querySelector('[id*="chart-status"]');
      if (statusElement) {
        statusElement.textContent = '✅ Chart loaded with ' + datasets.length + ' trend lines';
      }
      
    } catch (error) {
      console.error('❌ Chart initialization failed:', error);
      
      // Update status element with error message
      const statusElement = document.querySelector('[id*="chart-status"]');
      if (statusElement) {
        statusElement.textContent = '⚠️ Chart initialization failed: ' + error.message;
      }
    }
  }

  function setupEventListeners(canvasId) {
    // Source selector change event
    if (sourceSelector) {
      sourceSelector.addEventListener('change', updateChart);
    }

    // Reset button
    const resetId = canvasId === 'news-source-timeline-chart' ? 'reset-chart' : 'reset-chart-' + canvasId.split('-').pop();
    const resetButton = document.getElementById(resetId) || document.querySelector('[id*="reset-chart"]');
    if (resetButton) {
      resetButton.addEventListener('click', resetToTopSources);
    }

    // Clear button
    const clearId = canvasId === 'news-source-timeline-chart' ? 'clear-chart' : 'clear-chart-' + canvasId.split('-').pop();
    const clearButton = document.getElementById(clearId) || document.querySelector('[id*="clear-chart"]');
    if (clearButton) {
      clearButton.addEventListener('click', clearAllSources);
    }
  }

  function updateChart() {
    if (!chart || !sourceSelector) return;

    const selectedSourceIds = Array.from(sourceSelector.selectedOptions).map(option => option.value);
    console.log('📊 Updating chart with selected sources:', selectedSourceIds);

    // Combine timeline data from both top sources and extended sources
    let allData = drupalSettings.newsmotivationmetrics.timelineData || [];
    
    // Add extended sources data if available
    if (drupalSettings.newsmotivationmetrics.extendedSources && 
        drupalSettings.newsmotivationmetrics.extendedSources.timelineData) {
      const extendedData = drupalSettings.newsmotivationmetrics.extendedSources.timelineData;
      console.log('📈 Found extended source data with', extendedData.length, 'datasets');
      
      // Merge extended data, avoiding duplicates
      const existingSourceKeys = new Set(allData.map(item => item.source_id + '_' + item.metric_type));
      const newExtendedData = extendedData.filter(item => !existingSourceKeys.has(item.source_id + '_' + item.metric_type));
      allData = [...allData, ...newExtendedData];
      
      console.log('📊 Total available timeline data:', allData.length, 'datasets');
    }

    // Filter datasets based on selected sources
    const filteredData = allData.filter(sourceData => selectedSourceIds.includes(sourceData.source_id));
    
    console.log('🎯 Found timeline data for', filteredData.length, 'datasets from', selectedSourceIds.length, 'selected sources');

    // Warn about sources without timeline data
    const foundSourceIds = new Set(filteredData.map(item => item.source_id));
    const missingSourceIds = selectedSourceIds.filter(id => !foundSourceIds.has(id));
    if (missingSourceIds.length > 0) {
      console.warn('⚠️ No timeline data available for source IDs:', missingSourceIds);
    }

    // Update chart datasets
    const colors = {
      bias: ['#FF6B6B', '#FF9F9F', '#FFCCCC'],        // Red shades for bias
      credibility: ['#4ECDC4', '#7DDED8', '#B3EEEC'], // Teal shades for credibility
      sentiment: ['#45B7D1', '#78C8E0', '#ABD9EF']     // Blue shades for sentiment
    };

    chart.data.datasets = filteredData.map((sourceData, index) => {
      let colorSet = colors.bias; // default
      if (sourceData.metric_type === 'credibility') {
        colorSet = colors.credibility;
      } else if (sourceData.metric_type === 'sentiment') {
        colorSet = colors.sentiment;
      }
      
      const colorIndex = Math.floor(index / 3) % colorSet.length;

      return {
        label: sourceData.source_name,
        data: sourceData.data ? sourceData.data.map(point => ({
          x: point.date,
          y: point.value
        })) : [],
        borderColor: colorSet[colorIndex],
        backgroundColor: colorSet[colorIndex] + '20',
        fill: false,
        tension: 0.4
      };
    });

    chart.update();
    
    // Update status message
    const statusElement = document.querySelector('[id*="chart-status"]');
    if (statusElement) {
      let message = `📊 Chart updated with ${filteredData.length} trend lines`;
      if (missingSourceIds.length > 0) {
        message += ` (${missingSourceIds.length} sources have no timeline data)`;
      }
      statusElement.textContent = message;
    }
  }

  function resetToTopSources() {
    if (!sourceSelector) return;

    // Clear all selections
    Array.from(sourceSelector.options).forEach(option => {
      option.selected = false;
    });

    // Select top 5 options (fewer than motivation chart due to 3 metrics per source)
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
