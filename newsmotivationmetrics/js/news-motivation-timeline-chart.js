(function (Drupal, drupalSettings) {
  'use strict';

  let chart = null;
  let termSelector = null;

  Drupal.behaviors.newsMotivationTimelineChart = {
    attach: function (context, settings) {
      // Only process if this is the document context or contains chart canvases
      if (context !== document && !context.querySelector('canvas[id^="news-motivation-timeline-chart"]')) {
        return;
      }

      // Check for Chart.js availability
      if (typeof Chart === 'undefined') {
        console.error('❌ Chart.js library not loaded');
        const statusElement = document.querySelector('[id*="chart-status"]');
        if (statusElement) {
          statusElement.textContent = '⚠️ Chart.js library not loaded. Please check library dependencies.';
        }
        return;
      }

      // Find any canvas element with ID starting with 'news-motivation-timeline-chart'
      // Use once to prevent duplicate processing
      const canvases = context.querySelectorAll ? 
        context.querySelectorAll('canvas[id^="news-motivation-timeline-chart"]') :
        document.querySelectorAll('canvas[id^="news-motivation-timeline-chart"]');
      
      canvases.forEach((canvas) => {
        // Skip if already processed
        if (canvas.hasAttribute('data-chart-processed')) {
          return;
        }
        
        // Mark as processed
        canvas.setAttribute('data-chart-processed', 'true');
        
        const canvasId = canvas.id;

        // Find term selector based on canvas ID
        const selectorId = canvasId === 'news-motivation-timeline-chart' ? 'term-selector' : 'term-selector-' + canvasId.split('-').pop();
        termSelector = document.getElementById(selectorId) || context.querySelector('#' + selectorId);
        
        if (!termSelector) {
          // Try fallback selector
          termSelector = document.querySelector('.term-selector') || context.querySelector('.term-selector');
          if (!termSelector) {
            const statusElement = document.querySelector('[id*="chart-status"]');
            if (statusElement) {
              statusElement.textContent = '⚠️ Term selector not found. Controls may not work.';
            }
            return;
          }
        }

        // Check if we have the necessary data
        if (!settings.newsmotivationmetrics) {
          console.error('Chart data not available in drupalSettings');
          const statusElement = document.querySelector('[id*="chart-status"]');
          if (statusElement) {
            statusElement.textContent = '⚠️ Chart data not available in drupalSettings.';
          }
          return;
        }

        const chartData = settings.newsmotivationmetrics;
        
        // Validate chart data structure
        if (!chartData.timelineData || !Array.isArray(chartData.timelineData) || chartData.timelineData.length === 0) {
          console.error('No timeline data available or invalid format');
          const statusElement = document.querySelector('[id*="chart-status"]');
          if (statusElement) {
            statusElement.textContent = '⚠️ No timeline data available for chart display.';
          }
          return;
        }

        // Update debug status (find correct status element)
        const statusId = canvasId === 'news-motivation-timeline-chart' ? 'chart-status' : 'chart-status-' + canvasId.split('-').pop();
        const statusElement = document.getElementById(statusId) || document.querySelector('[id*="chart-status"]');
        if (statusElement) {
          statusElement.textContent = '✅ Chart initialized successfully';
        }

        // Initialize the chart
        initializeChart(canvas, chartData, canvasId);

        // Setup event listeners
        setupEventListeners(canvasId);
      });
    }
  };

  function initializeChart(canvas, data, canvasId) {
    if (chart) {
      chart.destroy();
    }

    try {
      const ctx = canvas.getContext('2d');
      if (!ctx) {
        throw new Error('Cannot get 2D context from canvas');
      }
      
      // Validate data structure
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

      // Prepare datasets from timeline data - Show 5 datasets by default
      const datasets = data.timelineData.slice(0, 5).map((termData, index) => {
        const colors = [
          '#FF6B6B', '#4ECDC4', '#45B7D1', '#96CEB4', '#FECA57',
          '#FF9FF3', '#54A0FF', '#5F27CD', '#00D2D3', '#FF9F43'
        ];
        
        return {
          label: termData.term_name,
          data: termData.data ? termData.data.map(point => ({
            x: point.date,
            y: point.count
          })) : [],
          borderColor: colors[index % colors.length],
          backgroundColor: colors[index % colors.length] + '20',
          fill: false,
          tension: 0.4
        };
      });

      // Get date labels from the first dataset
      const labels = data.timelineData.length > 0 && data.timelineData[0].data ? 
        data.timelineData[0].data.map(point => point.date) : [];

      chart = new Chart(ctx, {
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
              text: 'Topic Mentions Over Time (Last 90 Days)',
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
                text: 'Article Count'
              },
              beginAtZero: true,
              ticks: {
                stepSize: 1
              }
            }
          }
        }
      });

      // Store chart reference on canvas for future cleanup
      canvas.chart = chart;
      
      // Update status element with success message
      const statusId = canvasId === 'news-motivation-timeline-chart' ? 'chart-status' : 'chart-status-' + canvasId.split('-').pop();
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
    // Term selector change event
    if (termSelector) {
      termSelector.addEventListener('change', updateChart);
    }
  }

  function updateChart() {
    if (!chart || !termSelector) return;

    const selectedTermIds = Array.from(termSelector.selectedOptions).map(option => parseInt(option.value));

    // Combine timeline data from both top terms and extended terms
    let allData = drupalSettings.newsmotivationmetrics.timelineData || [];
    
    // Add extended terms data if available
    if (drupalSettings.newsmotivationmetrics.extendedTerms && 
        drupalSettings.newsmotivationmetrics.extendedTerms.timelineData) {
      const extendedData = drupalSettings.newsmotivationmetrics.extendedTerms.timelineData;
      
      // Merge extended data, avoiding duplicates
      const existingTermIds = new Set(allData.map(item => parseInt(item.term_id)));
      const newExtendedData = extendedData.filter(item => !existingTermIds.has(parseInt(item.term_id)));
      allData = [...allData, ...newExtendedData];
    }

    // Filter datasets based on selected terms
    const filteredData = allData.filter(termData => selectedTermIds.includes(parseInt(termData.term_id)));

    // Update chart datasets
    const colors = [
      '#FF6B6B', '#4ECDC4', '#45B7D1', '#96CEB4', '#FECA57',
      '#FF9FF3', '#54A0FF', '#5F27CD', '#00D2D3', '#FF9F43'
    ];

    chart.data.datasets = filteredData.map((termData, index) => ({
      label: termData.term_name,
      data: termData.data ? termData.data.map(point => ({
        x: point.date,
        y: point.count
      })) : [],
      borderColor: colors[index % colors.length],
      backgroundColor: colors[index % colors.length] + '20',
      fill: false,
      tension: 0.4
    }));

    chart.update();
    
    // Update status message
    const statusElement = document.querySelector('[id*="chart-status"]');
    if (statusElement) {
      statusElement.textContent = `📊 Chart updated with ${filteredData.length} trend lines`;
    }
  }

})(Drupal, drupalSettings);
