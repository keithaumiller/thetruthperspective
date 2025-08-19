/**
 * @file
 * Authoritarianism Timeline Chart functionality for News Motivation Metrics.
 *
 * Provides interactive timeline visualization for news source authoritarianism scores
 * using Chart.js with consistent design patterns matching other assessment metrics.
 */

(function (Drupal, drupalSettings, $) {
  'use strict';

  /**
   * Initialize authoritarianism timeline chart behavior.
   */
  Drupal.behaviors.authoritarianismTimelineChart = {
    attach: function (context, settings) {
      var chartSettings = settings.newsmotivationmetrics_authoritarianism;
      
      if (!chartSettings || !window.Chart) {
        console.warn('Authoritarianism timeline chart: Missing chart settings or Chart.js library');
        return;
      }

      // Initialize chart if canvas element exists and hasn't been processed
      var $canvas = $('#authoritarianism-timeline-chart', context).once('authoritarianism-timeline-chart');
      if ($canvas.length) {
        console.log('Initializing authoritarianism timeline chart with settings:', chartSettings);
        initializeAuthoritarianismChart($canvas[0], chartSettings);
      }
    }
  };

  /**
   * Initialize the authoritarianism timeline chart.
   *
   * @param {HTMLCanvasElement} canvas
   *   The canvas element for the chart.
   * @param {Object} chartSettings
   *   Chart configuration and data settings.
   */
  function initializeAuthoritarianismChart(canvas, chartSettings) {
    var ctx = canvas.getContext('2d');
    
    // Extract timeline data
    var timelineData = chartSettings.timelineData || [];
    var topSources = chartSettings.topSources || [];
    var extendedSources = chartSettings.extendedSources || null;
    
    console.log('Authoritarianism timeline data received:', {
      timelineCount: timelineData.length,
      topSourcesCount: topSources.length,
      hasExtended: !!extendedSources
    });

    // Process data for Chart.js format
    var chartData = processAuthoritarianismTimelineData(timelineData, topSources);
    
    if (!chartData.labels || chartData.labels.length === 0) {
      console.warn('No authoritarianism data available for chart');
      showEmptyState(canvas);
      return;
    }

    // Chart configuration optimized for authoritarianism score visualization
    var config = {
      type: 'line',
      data: chartData,
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          title: {
            display: true,
            text: 'News Source Authoritarianism Trends Over Time',
            font: { size: 16, weight: 'bold' }
          },
          legend: {
            display: true,
            position: 'top',
            labels: {
              filter: function(legendItem, chartData) {
                // Show legend for visible datasets only
                return chartData.datasets[legendItem.datasetIndex] && 
                       !chartData.datasets[legendItem.datasetIndex].hidden;
              },
              usePointStyle: true,
              padding: 15
            }
          },
          tooltip: {
            mode: 'index',
            intersect: false,
            callbacks: {
              title: function(tooltipItems) {
                return 'Date: ' + tooltipItems[0].label;
              },
              label: function(context) {
                var value = context.parsed.y;
                var sourceName = context.dataset.label;
                return sourceName + ': ' + value.toFixed(1) + '/10';
              },
              afterBody: function(tooltipItems) {
                return ['', '📊 Authoritarianism Scale:', '0 = Democratic/liberal', '5 = Mixed approach', '10 = Authoritarian/autocratic'];
              }
            }
          }
        },
        interaction: {
          mode: 'nearest',
          axis: 'x',
          intersect: false
        },
        scales: {
          x: {
            type: 'time',
            time: {
              unit: 'day',
              displayFormats: {
                day: 'MMM dd'
              }
            },
            title: {
              display: true,
              text: 'Date',
              font: { weight: 'bold' }
            },
            grid: {
              display: true,
              color: 'rgba(0,0,0,0.1)'
            }
          },
          y: {
            beginAtZero: true,
            max: 10,
            title: {
              display: true,
              text: 'Authoritarianism Score (0-10 scale)',
              font: { weight: 'bold' }
            },
            grid: {
              display: true,
              color: 'rgba(0,0,0,0.1)'
            },
            ticks: {
              callback: function(value) {
                return value.toFixed(1);
              }
            }
          }
        },
        elements: {
          line: {
            tension: 0.2,
            borderWidth: 2.5
          },
          point: {
            radius: 3,
            hoverRadius: 6,
            borderWidth: 2
          }
        }
      }
    };

    // Create chart instance
    var chart = new Chart(ctx, config);
    
    // Store chart instance for external access
    canvas.chartInstance = chart;

    // Set up source selector functionality
    setupSourceSelector(chart, chartSettings);
    
    // Set up chart action buttons
    setupChartActions(chart, chartSettings, topSources);

    console.log('Authoritarianism timeline chart initialized successfully');
  }

  /**
   * Process timeline data for Chart.js consumption.
   *
   * @param {Array} timelineData
   *   Raw timeline data from backend.
   * @param {Array} topSources
   *   Top news sources data.
   *
   * @return {Object}
   *   Formatted data for Chart.js.
   */
  function processAuthoritarianismTimelineData(timelineData, topSources) {
    var datasets = [];
    var allDates = new Set();
    
    // Color palette for authoritarianism visualization (red spectrum for intensity)
    var colors = [
      '#FF6B6B', '#FF8E8E', '#FFB3B3', '#FFD1D1', '#FF4757',
      '#FF3838', '#FF6348', '#FF7675', '#FD79A8', '#E84393'
    ];
    
    console.log('Processing authoritarianism timeline data:', timelineData);
    
    // Process each source's authoritarianism data
    timelineData.forEach(function(sourceData, index) {
      if (!sourceData.data || sourceData.data.length === 0) {
        return;
      }
      
      // Extract dates and values
      var chartPoints = [];
      sourceData.data.forEach(function(point) {
        if (point.date && point.value !== undefined) {
          chartPoints.push({
            x: point.date,
            y: parseFloat(point.value) || 0
          });
          allDates.add(point.date);
        }
      });
      
      if (chartPoints.length === 0) {
        return;
      }
      
      // Create dataset for this source
      var colorIndex = index % colors.length;
      var dataset = {
        label: sourceData.source_name || ('Source ' + (index + 1)),
        data: chartPoints,
        borderColor: colors[colorIndex],
        backgroundColor: colors[colorIndex] + '20', // 20% opacity
        fill: false,
        tension: 0.2,
        pointBackgroundColor: colors[colorIndex],
        pointBorderColor: '#FFFFFF',
        pointBorderWidth: 2,
        pointRadius: 4,
        pointHoverRadius: 7,
        hidden: index >= 5 // Hide datasets beyond first 5 by default
      };
      
      datasets.push(dataset);
    });
    
    // Generate sorted date labels
    var labels = Array.from(allDates).sort();
    
    console.log('Processed authoritarianism chart data:', {
      labels: labels.length,
      datasets: datasets.length,
      dateRange: labels.length > 0 ? [labels[0], labels[labels.length - 1]] : []
    });
    
    return {
      labels: labels,
      datasets: datasets
    };
  }

  /**
   * Set up source selector functionality.
   *
   * @param {Chart} chart
   *   Chart.js instance.
   * @param {Object} chartSettings
   *   Chart configuration settings.
   */
  function setupSourceSelector(chart, chartSettings) {
    var $selector = $('#source-selector, [id*="source-selector"]');
    
    if ($selector.length === 0) {
      console.log('No source selector found for authoritarianism chart');
      return;
    }
    
    $selector.on('change', function() {
      var selectedSources = $(this).val() || [];
      console.log('Authoritarianism chart sources selected:', selectedSources);
      
      // Update chart visibility based on selection
      chart.data.datasets.forEach(function(dataset, index) {
        var sourceName = dataset.label.replace(' - Authoritarianism Score', '');
        var isSelected = selectedSources.length === 0 || 
                        selectedSources.includes(sourceName) ||
                        selectedSources.includes(dataset.source_id);
        
        dataset.hidden = !isSelected;
      });
      
      chart.update('none'); // Fast update without animation
    });
  }

  /**
   * Set up chart action buttons (reset, clear).
   *
   * @param {Chart} chart
   *   Chart.js instance.
   * @param {Object} chartSettings
   *   Chart configuration settings.
   * @param {Array} topSources
   *   Default top sources.
   */
  function setupChartActions(chart, chartSettings, topSources) {
    // Reset button - show top 5 sources
    $('[id*="reset-chart"]').on('click', function() {
      var canvasId = $(this).data('canvas-id');
      if (canvasId !== 'authoritarianism-timeline-chart') return;
      
      console.log('Resetting authoritarianism chart to top sources');
      
      chart.data.datasets.forEach(function(dataset, index) {
        dataset.hidden = index >= 5; // Show first 5, hide rest
      });
      
      // Reset source selector
      var $selector = $('#source-selector, [id*="source-selector"]');
      $selector.val([]).trigger('change');
      
      chart.update();
    });
    
    // Clear button - hide all sources
    $('[id*="clear-chart"]').on('click', function() {
      var canvasId = $(this).data('canvas-id');
      if (canvasId !== 'authoritarianism-timeline-chart') return;
      
      console.log('Clearing all authoritarianism chart sources');
      
      chart.data.datasets.forEach(function(dataset) {
        dataset.hidden = true;
      });
      
      // Clear source selector
      var $selector = $('#source-selector, [id*="source-selector"]');
      $selector.val([]).trigger('change');
      
      chart.update();
    });
  }

  /**
   * Show empty state when no data is available.
   *
   * @param {HTMLCanvasElement} canvas
   *   The canvas element.
   */
  function showEmptyState(canvas) {
    var ctx = canvas.getContext('2d');
    var width = canvas.width;
    var height = canvas.height;
    
    ctx.clearRect(0, 0, width, height);
    ctx.fillStyle = '#666666';
    ctx.font = '16px Arial';
    ctx.textAlign = 'center';
    ctx.fillText('No authoritarianism data available', width / 2, height / 2);
    ctx.fillText('for the selected time period', width / 2, height / 2 + 25);
  }

})(Drupal, drupalSettings, jQuery);
