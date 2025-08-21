(function (Drupal, drupalSettings) {
  'use strict';

  let chart = null;
  let sourceSelector = null;

  Drupal.behaviors.biasTimelineChart = {
    attach: function (context, settings) {
      // Only process if this is the document context or contains chart canvases
      if (context !== document && !context.querySelector('canvas[id*="bias-timeline-chart"]')) {
        return;
      }

      // Check for Chart.js availability
      if (typeof Chart === 'undefined') {
        console.error('❌ Chart.js library not loaded');
        return;
      }

      // Find bias timeline chart canvases
      const canvases = context.querySelectorAll ? 
        context.querySelectorAll('canvas[id*="bias-timeline-chart"]') :
        document.querySelectorAll('canvas[id*="bias-timeline-chart"]');
      
      canvases.forEach((canvas) => {
        if (canvas.hasAttribute('data-chart-processed')) {
          return;
        }
        
        canvas.setAttribute('data-chart-processed', 'true');
        const canvasId = canvas.id;

        // Check if we have bias data
        if (!settings.newsmotivationmetrics_bias) {
          console.error('❌ No bias data found in settings');
          return;
        }

        const chartData = settings.newsmotivationmetrics_bias;

        // Find source selector
        const selectorId = 'source-selector';
        sourceSelector = document.getElementById(selectorId) || context.querySelector('#' + selectorId);
        
        if (!sourceSelector) {
          sourceSelector = document.querySelector('.source-selector');
        }

        // Validate data structure
        if (!chartData.timelineData || typeof chartData.timelineData !== 'object') {
          console.error('❌ Invalid bias timeline data structure');
          return;
        }

        // Initialize chart
        initializeChart(canvas, chartData, canvasId);
        setupEventListeners(canvasId);
      });
    }
  };

  function initializeChart(canvas, data, canvasId) {
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

      if (timelineArray.length === 0) {
        throw new Error('No bias timeline data available');
      }

      // Prepare datasets - show top 5 sources initially
      const datasets = timelineArray.slice(0, 5).map((sourceData, index) => {
        const colors = [
          '#DC2626', // Red
          '#2563EB', // Blue
          '#059669', // Green
          '#7C3AED', // Purple
          '#EA580C', // Orange
          '#DB2777', // Pink
          '#0891B2', // Cyan
          '#84CC16', // Lime
          '#F59E0B', // Amber
          '#EF4444'  // Red variant
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
              text: 'Bias Ratings Over Time (Last 90 Days)',
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
              borderWidth: 1,
              callbacks: {
                label: function(context) {
                  return context.dataset.label + ': ' + context.parsed.y.toFixed(1);
                }
              }
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
                text: 'Bias Rating (-100 to +100)'
              },
              min: -100,
              max: 100,
              ticks: {
                stepSize: 20
              }
            }
          }
        }
      });

      canvas.chart = chart;
      
    } catch (error) {
      console.error('❌ Bias chart initialization failed:', error);
    }
  }

  function setupEventListeners(canvasId) {
    if (sourceSelector) {
      sourceSelector.addEventListener('change', updateChart);
    }
  }

  function updateChart() {
    if (!chart || !sourceSelector) return;

    const selectedSourceIds = Array.from(sourceSelector.selectedOptions).map(option => parseInt(option.value));
    const data = drupalSettings.newsmotivationmetrics_bias;
    
    if (!data || !data.timelineData) return;

    // Filter data based on selected sources
    const timelineArray = Object.values(data.timelineData);
    const filteredData = timelineArray.filter(sourceData => 
      selectedSourceIds.includes(parseInt(sourceData.source_id))
    );

    // Update chart datasets
    const colors = [
      '#DC2626', '#2563EB', '#059669', '#7C3AED', '#EA580C',
      '#DB2777', '#0891B2', '#84CC16', '#F59E0B', '#EF4444'
    ];

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

})(Drupal, drupalSettings);
