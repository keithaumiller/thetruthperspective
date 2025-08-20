/**
 * @file
 * Credibility timeline chart behavior for The Truth Perspective news source analytics.
 * 
 * This file handles Chart.js initialization for credibility score trends over time.
 * Follows the same design pattern as the combined news source timeline chart
 * but filtered to show only credibility metrics.
 */

(function ($, Drupal, drupalSettings) {
  'use strict';

  /**
   * Chart behavior for credibility timeline charts.
   */
  Drupal.behaviors.credibilityTimelineChart = {
    attach: function (context, settings) {
      console.log('🔄 Credibility Timeline Chart behavior initializing...');
      console.log('Context element:', context === document ? '#document' : context.tagName || 'Unknown', context === document ? '(' + window.location.href + ')' : '');
      console.log('Settings keys:', Object.keys(settings));

      // Find all credibility timeline chart canvases
      const canvases = $(context).find('canvas.credibility-timeline-chart').addBack('canvas.credibility-timeline-chart');
      console.log('Found', canvases.length, 'credibility chart canvases to process');

      canvases.each(function() {
        const canvas = this;
        const canvasId = canvas.id;

        // Skip if already processed
        if (canvas.hasAttribute('data-chart-processed')) {
          console.log('Skipping already processed canvas:', canvasId);
          return;
        }

        console.log('✅ Processing credibility canvas with ID:', canvasId);

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
    // Single color scheme for credibility (blue/teal tones)
    const baseColors = [
      { 
        name: 'blue', 
        credibility: '#3B82F6'   // Blue for credibility score
      },
      { 
        name: 'indigo', 
        credibility: '#6366F1'   // Indigo for credibility score  
      },
      { 
        name: 'teal', 
        credibility: '#14B8A6'   // Teal for credibility score
      }
    ];
    
    const sourceColorMap = {};
    
    // Get unique base sources and assign colors in rotation
    const uniqueSources = [...new Set(selectedSources)];
    
    uniqueSources.forEach((source, index) => {
      const colorIndex = index % 3; // Rotate through 0, 1, 2
      sourceColorMap[source] = colorIndex;
    });
    
    console.log('Color assignments (credibility chart):', sourceColorMap);
    
    return {
      sourceColorMap,
      baseColors,
    };
  }

  function initializeChart(canvas, canvasId, settings) {
    try {
      // Find associated source selector
      const selectorId = canvasId === 'credibility-timeline-chart' ? 'source-selector' : 'source-selector-' + canvasId.split('-').pop();
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

      // Get chart data from settings - use individual credibility namespace
      const chartData = settings.newsmotivationmetrics_credibility || {};
      
      if (!chartData.timelineData || !Array.isArray(chartData.timelineData) || chartData.timelineData.length === 0) {
        throw new Error('No credibility timeline data available');
      }

      console.log('📊 Credibility chart data loaded:', {
        dataPoints: chartData.timelineData ? chartData.timelineData.length : 0,
        sourceCount: chartData.topSources ? chartData.topSources.length : 0,
        timestamp: Math.floor(Date.now() / 1000),
        date: new Date().toISOString().slice(0, 19).replace('T', ' '),
        php_version: chartData.debugInfo?.php_version || 'unknown'
      });
      console.log('Timeline data available:', chartData.timelineData ? chartData.timelineData.length : 0, 'datasets');

      console.log('🎯 Initializing Chart.js...');
      console.log('Canvas element:', canvas);
      console.log('Canvas ID:', canvasId);

      createChart(canvas, chartData);
      setupEventListeners(canvasId);
      
      // Set default selection to top 3 sources (highest article count)
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
      // Ensure Chart.js is loaded
      if (typeof Chart === 'undefined') {
        throw new Error('Chart.js library not loaded');
      }

      if (!data.timelineData || !Array.isArray(data.timelineData)) {
        throw new Error('Invalid timeline data structure');
      }

      // Ensure canvas has proper dimensions
      if (canvas.clientWidth === 0 || canvas.clientHeight === 0) {
        console.warn('Canvas has zero dimensions, setting fallback size');
        canvas.style.width = '100%';
        canvas.style.height = '400px';
      }

      console.log('Canvas dimensions:', { 
        width: canvas.clientWidth, 
        height: canvas.clientHeight,
        style: canvas.style.cssText 
      });

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
    
      // Get unique source names from the data for color assignment
      const uniqueSources = [...new Set(data.timelineData.map(item => {
        // Extract base source name from dataset names like "FOXNews.com - Credibility Score"
        const sourceName = item.source_name;
        const baseName = sourceName.includes(' - ') ? sourceName.split(' - ')[0] : sourceName;
        return baseName;
      }))];
      
      console.log('Unique sources in chart:', uniqueSources);
      
      // Get color assignments for all sources (simple rotation)
      const { sourceColorMap, baseColors } = assignSourceColors(uniqueSources);
    
      // Prepare datasets from timeline data - each source has credibility metric only
      const datasets = data.timelineData.map((sourceData, index) => {
        console.log(`Dataset ${index}: ${sourceData.source_name} with ${sourceData.data ? sourceData.data.length : 0} data points`);
        
        // Get the source name and metric type
        let sourceName = sourceData.source_name;
        
        // Extract base source name from dataset names like "FOXNews.com - Credibility Score"
        const baseSourceName = sourceName.includes(' - ') ? sourceName.split(' - ')[0] : sourceName;
        
        // Get color scheme for this source using simple lookup
        const colorIndex = sourceColorMap[baseSourceName] !== undefined ? sourceColorMap[baseSourceName] : 0; // Default to first color family if not found
        const colorScheme = baseColors[colorIndex];
        
        // Get color for credibility metric
        const color = colorScheme.credibility || '#3B82F6';
        
        console.log(`Credibility color assignment for ${sourceName} -> ${baseSourceName}: ${color} (${colorScheme.name})`);
        
        return {
          label: `${baseSourceName} - Credibility Score`,
          data: sourceData.data ? sourceData.data.map(point => ({
            x: point.date,
            y: point.value
          })) : [],
          borderColor: color,
          backgroundColor: color + '20', // Add transparency for fill
          fill: false,
          tension: 0.4,
          pointRadius: 3,
          pointHoverRadius: 5
        };
      });

      console.log('📊 Creating credibility chart with', datasets.length, 'datasets');

      chart = new Chart(canvas, {
        type: 'line',
        data: { datasets },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          scales: {
            x: {
              type: 'time',
              time: {
                unit: 'day',
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
              max: 100,
              title: {
                display: true,
                text: 'Credibility Score (0=Deception, 100=Facts)'
              },
              ticks: {
                callback: function(value) {
                  return value.toFixed(1);
                }
              }
            }
          },
          plugins: {
            title: {
              display: true,
              text: 'News Source Credibility Trends (Last 90 Days)',
              font: {
                size: 16
              }
            },
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
      const statusId = canvas.id === 'credibility-timeline-chart' ? 'chart-status' : 'chart-status-' + canvas.id.split('-').pop();
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
    // Source selector change event with 3-selection limit
    if (sourceSelector) {
      sourceSelector.addEventListener('change', function(event) {
        const selectedOptions = Array.from(sourceSelector.selectedOptions);
        
        // Enforce maximum 3 selections
        if (selectedOptions.length > 3) {
          console.log('Maximum 3 sources allowed, deselecting excess selections');
          
          // Keep only the first 3 selections
          Array.from(sourceSelector.options).forEach((option, index) => {
            option.selected = false;
          });
          
          // Re-select only the first 3
          for (let i = 0; i < Math.min(3, selectedOptions.length); i++) {
            selectedOptions[i].selected = true;
          }
          
          // Show warning message
          const statusElement = document.querySelector('[id*="chart-status"]');
          if (statusElement) {
            statusElement.textContent = '⚠️ Maximum 3 sources allowed - excess selections removed';
            setTimeout(() => {
              updateChart();
            }, 1000);
            return;
          }
        }
        
        updateChart();
      });
    }

    // Reset and Clear button event listeners
    const uniqueId = canvasId.split('-').pop();
    const resetButton = document.getElementById('reset-chart-' + uniqueId) || document.querySelector('[data-canvas-id="' + canvasId + '"].chart-reset-btn');
    const clearButton = document.getElementById('clear-chart-' + uniqueId) || document.querySelector('[data-canvas-id="' + canvasId + '"].chart-clear-btn');

    if (resetButton) {
      resetButton.addEventListener('click', function() {
        console.log('Reset button clicked for canvas:', canvasId);
        resetToTopSources();
      });
    }

    if (clearButton) {
      clearButton.addEventListener('click', function() {
        console.log('Clear button clicked for canvas:', canvasId);
        clearAllSources();
      });
    }
  }

  function updateChart() {
    if (!chart || !sourceSelector) return;

    const selectedSourceIds = Array.from(sourceSelector.selectedOptions).map(option => option.value);
    console.log('📊 Updating credibility chart with selected sources:', selectedSourceIds);

    // Combine timeline data from credibility sources
    let allData = drupalSettings.newsmotivationmetrics_credibility.timelineData || [];
    
    // Add extended sources data if available
    if (drupalSettings.newsmotivationmetrics_credibility.extendedSources && 
        drupalSettings.newsmotivationmetrics_credibility.extendedSources.timelineData) {
      const extendedData = drupalSettings.newsmotivationmetrics_credibility.extendedSources.timelineData;
      console.log('📈 Found extended source data with', extendedData.length, 'credibility datasets');
      
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

    // Update chart datasets with simple color rotation
    // Get unique source names for color assignment
    const uniqueSelectedSources = [...new Set(filteredData.map(item => {
      // Extract base source name from dataset names like "FOXNews.com - Credibility Score"
      const sourceName = item.source_name;
      const baseName = sourceName.includes(' - ') ? sourceName.split(' - ')[0] : sourceName;
      return baseName;
    }))];
    
    console.log('Unique selected sources for coloring:', uniqueSelectedSources);
    
    // Get color assignments for selected sources (simple rotation)
    const { sourceColorMap, baseColors } = assignSourceColors(uniqueSelectedSources);

    chart.data.datasets = filteredData.map((sourceData, index) => {
      // Get the source name
      let sourceName = sourceData.source_name;
      
      // Extract base source name from dataset names like "FOXNews.com - Credibility Score"
      const baseSourceName = sourceName.includes(' - ') ? sourceName.split(' - ')[0] : sourceName;
      
      // Get color scheme for this source using simple lookup
      const colorIndex = sourceColorMap[baseSourceName] !== undefined ? sourceColorMap[baseSourceName] : 0; // Default to first color family if not found
      const colorScheme = baseColors[colorIndex];
      
      // Get color for credibility metric
      const color = colorScheme.credibility || '#3B82F6';
      
      console.log(`Credibility color assignment for ${sourceName} -> ${baseSourceName}: ${color} (${colorScheme.name})`);

      return {
        label: `${baseSourceName} - Credibility Score`,
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

    // Select top 3 options (top 3 sources by article count)
    const options = Array.from(sourceSelector.options);
    for (let i = 0; i < Math.min(3, options.length); i++) {
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
