(function (Drupal, drupalSettings) {
  'use strict';

  console.log('=== Taxonomy Timeline Chart Script Loading ===');
  console.log('Drupal object:', typeof Drupal);
  console.log('drupalSettings object:', typeof drupalSettings);

  let chart = null;
  let termSelector = null;

  Drupal.behaviors.taxonomyTimelineChart = {
    attach: function (context, settings) {
      console.log('=== Drupal Behavior Attach Called ===');
      console.log('Context type:', typeof context);
      console.log('Context element:', context);
      console.log('Settings keys:', Object.keys(settings));

      // Try multiple approaches to find the canvas element
      let canvas = null;
      
      // First, try to find canvas in the current context
      if (context && context.querySelector) {
        canvas = context.querySelector('#taxonomy-timeline-chart');
        console.log('Canvas found in context:', canvas);
      }
      
      // If not found in context, search in document
      if (!canvas) {
        console.log('Canvas not found in context, searching in document...');
        canvas = document.getElementById('taxonomy-timeline-chart');
        console.log('Canvas found in document:', canvas);
      }
      
      if (!canvas) {
        console.log('❌ Canvas element #taxonomy-timeline-chart not found anywhere in DOM');
        
        // Debug: List all elements with 'chart' in their ID or class
        const chartElements = document.querySelectorAll('[id*="chart"], [class*="chart"]');
        console.log('Chart-related elements found:', chartElements.length);
        chartElements.forEach((el, index) => {
          console.log(`Chart element ${index}:`, el.tagName, ' ', el.className);
        });
        
        // Try to find chart container
        const chartContainer = document.querySelector('.chart-container');
        if (chartContainer) {
          console.log('✓ Chart container found, but missing canvas element');
          
          // Dynamically create the canvas element if container exists but canvas doesn't
          console.log('🔧 Creating canvas element dynamically...');
          const newCanvas = document.createElement('canvas');
          newCanvas.id = 'taxonomy-timeline-chart';
          newCanvas.width = 800;
          newCanvas.height = 400;
          newCanvas.style.maxWidth = '100%';
          newCanvas.style.height = 'auto';
          chartContainer.appendChild(newCanvas);
          canvas = newCanvas;
          console.log('✅ Canvas element created and added to DOM');
        } else {
          console.log('❌ Chart container not found either');
          document.getElementById('chart-status').textContent = '⚠️ Chart canvas element not found. HTML structure may be incorrect.';
          return;
        }
      }

      // Find term selector
      termSelector = document.getElementById('term-selector') || context.querySelector('#term-selector');
      if (!termSelector) {
        console.log('❌ Term selector not found');
        document.getElementById('chart-status').textContent = '⚠️ Term selector not found. Controls may not work.';
        return;
      }

      console.log('✅ Canvas found:', canvas);
      console.log('✅ Term selector found:', termSelector);

      // Check if we have the necessary data
      if (!settings.newsmotivationmetrics) {
        console.log('❌ Chart data not available in drupalSettings');
        document.getElementById('chart-status').textContent = '⚠️ Chart data not available.';
        return;
      }

      const chartData = settings.newsmotivationmetrics;
      console.log('📊 Chart data loaded:', chartData.debugInfo);

      // Update debug status
      document.getElementById('chart-status').textContent = '✅ Chart initialized successfully';
      document.getElementById('chart-data-status').textContent = `Data loaded: ${chartData.debugInfo.termCount} terms, ${chartData.debugInfo.dataPoints} data series`;

      // Initialize the chart
      initializeChart(canvas, chartData);

      // Setup event listeners
      setupEventListeners();
    }
  };

  function initializeChart(canvas, data) {
    console.log('🎯 Initializing Chart.js...');
    
    if (chart) {
      chart.destroy();
    }

    const ctx = canvas.getContext('2d');
    
    // Prepare datasets from timeline data
    const datasets = data.timelineData.map((termData, index) => {
      const colors = [
        '#FF6B6B', '#4ECDC4', '#45B7D1', '#96CEB4', '#FECA57',
        '#FF9FF3', '#54A0FF', '#5F27CD', '#00D2D3', '#FF9F43'
      ];
      
      return {
        label: termData.term_name,
        data: termData.data.map(point => ({
          x: point.date,
          y: point.count
        })),
        borderColor: colors[index % colors.length],
        backgroundColor: colors[index % colors.length] + '20',
        fill: false,
        tension: 0.4
      };
    });

    // Get date labels from the first dataset
    const labels = data.timelineData.length > 0 ? data.timelineData[0].data.map(point => point.date) : [];

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
              parser: 'YYYY-MM-DD',
              tooltipFormat: 'MMM DD, YYYY',
              displayFormats: {
                day: 'MMM DD',
                week: 'MMM DD',
                month: 'MMM YYYY'
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

    console.log('✅ Chart initialized successfully');
  }

  function setupEventListeners() {
    // Term selector change event
    if (termSelector) {
      termSelector.addEventListener('change', updateChart);
    }

    // Reset button
    const resetButton = document.getElementById('reset-chart');
    if (resetButton) {
      resetButton.addEventListener('click', resetToTop10);
    }

    // Clear button
    const clearButton = document.getElementById('clear-chart');
    if (clearButton) {
      clearButton.addEventListener('click', clearAllTerms);
    }
  }

  function updateChart() {
    if (!chart || !termSelector) return;

    const selectedTermIds = Array.from(termSelector.selectedOptions).map(option => parseInt(option.value));
    console.log('📊 Updating chart with selected terms:', selectedTermIds);

    // Filter datasets based on selected terms
    const allData = drupalSettings.newsmotivationmetrics.timelineData;
    const filteredData = allData.filter(termData => selectedTermIds.includes(parseInt(termData.term_id)));

    // Update chart datasets
    const colors = [
      '#FF6B6B', '#4ECDC4', '#45B7D1', '#96CEB4', '#FECA57',
      '#FF9FF3', '#54A0FF', '#5F27CD', '#00D2D3', '#FF9F43'
    ];

    chart.data.datasets = filteredData.map((termData, index) => ({
      label: termData.term_name,
      data: termData.data.map(point => ({
        x: point.date,
        y: point.count
      })),
      borderColor: colors[index % colors.length],
      backgroundColor: colors[index % colors.length] + '20',
      fill: false,
      tension: 0.4
    }));

    chart.update();
  }

  function resetToTop10() {
    if (!termSelector) return;

    // Clear all selections
    Array.from(termSelector.options).forEach(option => {
      option.selected = false;
    });

    // Select top 10 options
    const options = Array.from(termSelector.options);
    for (let i = 0; i < Math.min(10, options.length); i++) {
      options[i].selected = true;
    }

    updateChart();
  }

  function clearAllTerms() {
    if (!termSelector) return;

    // Clear all selections
    Array.from(termSelector.options).forEach(option => {
      option.selected = false;
    });

    updateChart();
  }

  // Debug information on script load
  console.log('=== Chart Script Loaded Successfully ===');
  console.log('Environment check:');
  console.log('- Drupal available:', typeof Drupal !== 'undefined');
  console.log('- jQuery available:', typeof jQuery !== 'undefined');
  console.log('- drupalSettings available:', typeof drupalSettings !== 'undefined');

})(Drupal, drupalSettings);