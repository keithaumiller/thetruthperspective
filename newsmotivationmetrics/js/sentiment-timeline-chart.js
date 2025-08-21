(function (Drupal, drupalSettings) {
  'use strict';

  console.log('=== Sentiment Timeline Chart       // Prepare datasets - show top 5 sources initially
      const datasets = timelineArray.slice(0, 5).map((sourceData, index) => {ript Loading ===');

  let chart = null;
  let sourceSelector = null;

  Drupal.behaviors.sentimentTimelineChart = {
    attach: function (context, settings) {
      // Only process if this is the document context or contains chart canvases
      if (context !== document && !context.querySelector('canvas[id*="sentiment-timeline-chart"]')) {
        return;
      }

      console.log('=== Sentiment Timeline Chart Behavior Attach Called ===');

      // Check for Chart.js availability
      if (typeof Chart === 'undefined') {
        console.error('❌ Chart.js library not loaded');
        return;
      }

      // Find sentiment timeline chart canvases
      const canvases = context.querySelectorAll ? 
        context.querySelectorAll('canvas[id*="sentiment-timeline-chart"]') :
        document.querySelectorAll('canvas[id*="sentiment-timeline-chart"]');
      
      console.log('Found', canvases.length, 'sentiment chart canvases');
      
      canvases.forEach((canvas) => {
        if (canvas.hasAttribute('data-chart-processed')) {
          return;
        }
        
        canvas.setAttribute('data-chart-processed', 'true');
        const canvasId = canvas.id;
        console.log('✅ Processing sentiment chart canvas:', canvasId);

        // Check if we have sentiment data
        if (!settings.newsmotivationmetrics_sentiment) {
          console.log('❌ No sentiment data found in settings');
          console.log('Available settings:', Object.keys(settings));
          return;
        }

        const chartData = settings.newsmotivationmetrics_sentiment;
        console.log('📊 Sentiment chart data structure:', {
          timelineData: chartData.timelineData ? Object.keys(chartData.timelineData).length : 0,
          topSources: chartData.topSources ? chartData.topSources.length : 0
        });

        // Find source selector
        const selectorId = 'source-selector';
        sourceSelector = document.getElementById(selectorId) || context.querySelector('#' + selectorId);
        
        if (!sourceSelector) {
          sourceSelector = document.querySelector('.source-selector');
        }

        // Validate data structure
        if (!chartData.timelineData || typeof chartData.timelineData !== 'object') {
          console.log('❌ Invalid sentiment timeline data structure');
          return;
        }

        // Initialize chart
        initializeChart(canvas, chartData, canvasId);
        setupEventListeners(canvasId);
      });
    }
  };

  function initializeChart(canvas, data, canvasId) {
    console.log('🎯 Initializing Sentiment Chart...');
    
    try {
      const ctx = canvas.getContext('2d');
      
      // Destroy existing chart
      if (chart) {
        chart.destroy();
        chart = null;
      }
      
      if (canvas.chart) {
        canvas.chart.destroy();
        canvas.chart = null;
      }

      // Convert object-based timeline data to array format for processing
      const timelineArray = Object.values(data.timelineData);
      console.log('Timeline data points:', timelineArray.length);

      if (timelineArray.length === 0) {
        throw new Error('No sentiment timeline data available');
      }

      // Prepare datasets - show top 5 sources initially
      const datasets = timelineArray.slice(0, 5).map((sourceData, index) => {
        console.log(`Processing dataset ${index}: ${sourceData.source_name}`);
        
        const colors = [
          '#8B5CF6', // Purple for sentiment
          '#A855F7', // Violet
          '#C084FC'  // Light purple
        ];
        
        return {
          label: sourceData.source_name,
          data: sourceData.data ? sourceData.data.map(point => ({
            x: point.date,
            y: point.value
          })) : [],
          borderColor: colors[index % colors.length],
          backgroundColor: colors[index % colors.length] + '20',
          fill: false,
          tension: 0.4,
          pointRadius: 3,
          pointHoverRadius: 6
        };
      });

      console.log('Creating sentiment chart with', datasets.length, 'datasets');

      chart = new Chart(ctx, {
        type: 'line',
        data: { datasets },
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
              text: 'News Source Sentiment Trends Over Time',
              font: { size: 16 }
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
              borderWidth: 1,
              callbacks: {
                afterLabel: function(context) {
                  const value = context.parsed.y;
                  if (value >= 70) return 'Very Positive';
                  if (value >= 60) return 'Positive';
                  if (value >= 40) return 'Neutral';
                  if (value >= 30) return 'Negative';
                  return 'Very Negative';
                }
              }
            }
          },
          scales: {
            x: {
              type: 'time',
              time: {
                parser: 'yyyy-MM-dd',
                tooltipFormat: 'MMM dd, yyyy',
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
              min: 0,
              max: 100,
              title: {
                display: true,
                text: 'Sentiment Rating (0=Negative, 100=Positive)'
              },
              ticks: {
                stepSize: 10
              }
            }
          }
        }
      });

      canvas.chart = chart;
      console.log('✅ Sentiment chart initialized successfully');
      
    } catch (error) {
      console.error('❌ Sentiment chart initialization failed:', error);
    }
  }

  function setupEventListeners(canvasId) {
    if (sourceSelector) {
      sourceSelector.addEventListener('change', updateChart);
    }

    // Reset and clear buttons
    const resetButton = document.querySelector('[id*="reset-chart"]');
    const clearButton = document.querySelector('[id*="clear-chart"]');

    if (resetButton) {
      resetButton.addEventListener('click', resetToTopSources);
    }

    if (clearButton) {
      clearButton.addEventListener('click', clearAllSources);
    }
  }

  function updateChart() {
    if (!chart || !sourceSelector) return;

    const selectedSourceIds = Array.from(sourceSelector.selectedOptions).map(option => option.value);
    console.log('📊 Updating sentiment chart with selected sources:', selectedSourceIds);

    // Get timeline data
    const allTimelineData = Object.values(drupalSettings.newsmotivationmetrics_sentiment.timelineData || {});
    
    // Filter by selected sources
    const filteredData = allTimelineData.filter(sourceData => 
      selectedSourceIds.includes(sourceData.source_id)
    );

    console.log('Found sentiment data for', filteredData.length, 'sources');

    // Update chart datasets
    const colors = ['#8B5CF6', '#A855F7', '#C084FC', '#DDD6FE', '#E9D5FF'];
    
    chart.data.datasets = filteredData.map((sourceData, index) => ({
      label: sourceData.source_name,
      data: sourceData.data ? sourceData.data.map(point => ({
        x: point.date,
        y: point.value
      })) : [],
      borderColor: colors[index % colors.length],
      backgroundColor: colors[index % colors.length] + '20',
      fill: false,
      tension: 0.4,
      pointRadius: 3,
      pointHoverRadius: 6
    }));

    chart.update();
  }

  function resetToTopSources() {
    if (!sourceSelector) return;

    // Clear selections
    Array.from(sourceSelector.options).forEach(option => {
      option.selected = false;
    });

    // Select top 5
    const options = Array.from(sourceSelector.options);
    for (let i = 0; i < Math.min(5, options.length); i++) {
      options[i].selected = true;
    }

    updateChart();
  }

  function clearAllSources() {
    if (!sourceSelector) return;

    Array.from(sourceSelector.options).forEach(option => {
      option.selected = false;
    });

    if (chart) {
      chart.data.datasets = [];
      chart.update();
    }
  }

  console.log('=== Sentiment Chart Script Loaded Successfully ===');

})(Drupal, drupalSettings);
