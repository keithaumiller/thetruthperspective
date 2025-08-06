(function (Drupal, drupalSettings) {
  'use strict';

  console.log('=== Taxonomy Timeline Chart Script Loading ===');
  console.log('Drupal object:', typeof Drupal);
  console.log('drupalSettings object:', typeof drupalSettings);
  
  let chart = null;
  let allTimelineData = [];
  let topTerms = [];

  // Color palette for chart lines
  const colorPalette = [
    '#667eea', '#764ba2', '#f093fb', '#f5576c', '#4facfe', '#00f2fe',
    '#43e97b', '#38f9d7', '#ffecd2', '#fcb69f', '#a8edea', '#fed6e3',
    '#d299c2', '#fef9d7', '#dee5e5', '#eef2f3', '#89f7fe', '#66a6ff',
    '#fdbb2d', '#22c1c3'
  ];

  Drupal.behaviors.taxonomyTimelineChart = {
    attach: function (context, settings) {
      console.log('=== Drupal Behavior Attach Called ===');
      console.log('Context type:', typeof context);
      console.log('Settings keys:', Object.keys(settings || {}));
      
      // Check if we're in the right context (avoid duplicate initialization)
      const canvas = context.querySelector('#taxonomy-timeline-chart');
      if (!canvas) {
        console.log('Canvas element not found in this context');
        return;
      }
      
      console.log('✓ Canvas element found:', canvas);
      console.log('Canvas dimensions:', canvas.width + 'x' + canvas.height);
      
      // Debug Chart.js availability
      if (typeof Chart === 'undefined') {
        console.error('✗ Chart.js is NOT loaded');
        const errorMsg = '❌ Chart.js library failed to load. Check network connectivity.';
        canvas.parentElement.innerHTML = '<div style="padding: 20px; background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; border-radius: 4px; margin: 20px 0;">' + errorMsg + '</div>';
        return;
      }
      
      console.log('✓ Chart.js loaded successfully');
      console.log('Chart.js version:', Chart.version || 'version unknown');
      console.log('Chart.js defaults:', typeof Chart.defaults);
      
      // Debug drupalSettings structure
      console.log('=== Settings Debug ===');
      console.log('Full settings object keys:', Object.keys(settings));
      
      if (typeof settings.newsmotivationmetrics === 'undefined') {
        console.warn('⚠️ newsmotivationmetrics settings not found');
        console.log('Creating test chart with dummy data...');
        createTestChart(canvas);
        return;
      }
      
      const moduleSettings = settings.newsmotivationmetrics;
      console.log('✓ Module settings found');
      console.log('Module settings keys:', Object.keys(moduleSettings));
      
      const timelineData = moduleSettings.timelineData || [];
      const topTerms = moduleSettings.topTerms || [];
      
      console.log('Timeline data type:', typeof timelineData);
      console.log('Timeline data length:', timelineData.length);
      console.log('Top terms length:', topTerms.length);
      
      if (timelineData.length > 0) {
        console.log('Sample timeline data:', timelineData[0]);
        console.log('First term data points:', timelineData[0].data ? timelineData[0].data.length : 'no data array');
      }
      
      if (timelineData.length === 0) {
        console.warn('⚠️ No timeline data available - creating test chart');
        createTestChart(canvas);
        return;
      }
      
      // Initialize chart with real data
      console.log('Initializing chart with real data...');
      try {
        initializeRealChart(canvas, timelineData);
        bindControls();
        console.log('✓ Chart initialization completed successfully');
      } catch (error) {
        console.error('✗ Chart initialization failed:', error);
        createTestChart(canvas);
      }
    }
  };

  function createTestChart(canvas) {
    console.log('=== Creating Test Chart ===');
    
    const testData = {
      labels: ['Day 1', 'Day 2', 'Day 3', 'Day 4', 'Day 5', 'Day 6', 'Day 7'],
      datasets: [{
        label: 'Test Data (Backend Working, No Real Data)',
        data: [12, 19, 3, 17, 6, 3, 7],
        borderColor: '#667eea',
        backgroundColor: 'rgba(102, 126, 234, 0.1)',
        tension: 0.3,
        borderWidth: 3,
        pointBackgroundColor: '#667eea',
        pointBorderColor: '#ffffff',
        pointBorderWidth: 2,
        pointRadius: 5
      }]
    };
    
    try {
      console.log('Creating Chart instance...');
      chart = new Chart(canvas, {
        type: 'line',
        data: testData,
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            title: {
              display: true,
              text: '🧪 Test Chart - Backend Systems Operational',
              font: { size: 18, weight: 'bold' },
              color: '#28a745',
              padding: 20
            },
            legend: {
              display: true,
              position: 'top',
              labels: { padding: 20, usePointStyle: true }
            },
            tooltip: {
              backgroundColor: 'rgba(0,0,0,0.8)',
              titleColor: '#ffffff',
              bodyColor: '#ffffff',
              borderColor: '#667eea',
              borderWidth: 1
            }
          },
          scales: {
            y: {
              beginAtZero: true,
              title: {
                display: true,
                text: 'Article Count',
                color: '#495057',
                font: { size: 14, weight: 'bold' }
              },
              grid: { color: '#e9ecef' },
              ticks: { color: '#6c757d' }
            },
            x: {
              title: {
                display: true,
                text: 'Timeline',
                color: '#495057',
                font: { size: 14, weight: 'bold' }
              },
              grid: { color: '#e9ecef' },
              ticks: { color: '#6c757d' }
            }
          },
          animation: {
            duration: 2000,
            easing: 'easeInOutQuart'
          }
        }
      });
      
      console.log('✓ Test chart created successfully');
      console.log('Chart instance:', chart);
      
    } catch (error) {
      console.error('✗ Test chart creation failed:', error);
      canvas.parentElement.innerHTML = '<div style="padding: 20px; background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; border-radius: 4px; margin: 20px 0;">❌ Chart creation failed: ' + error.message + '</div>';
    }
  }

  function initializeRealChart(canvas, timelineData) {
    console.log('=== Creating Real Chart ===');
    console.log('Processing', timelineData.length, 'terms for chart...');
    
    try {
      // Limit to first 5 terms for performance and readability
      const limitedData = timelineData.slice(0, 5);
      console.log('Limited to', limitedData.length, 'terms');
      
      const chartData = prepareChartData(limitedData);
      console.log('Chart data prepared:', chartData);
      
      if (!chartData.labels || chartData.labels.length === 0) {
        console.error('No chart labels generated');
        createTestChart(canvas);
        return;
      }
      
      chart = new Chart(canvas, {
        type: 'line',
        data: chartData,
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            title: {
              display: true,
              text: '📈 Topic Trends Over Time (Last 90 Days)',
              font: { size: 18, weight: 'bold' },
              color: '#2c3e50',
              padding: 20
            },
            legend: {
              display: true,
              position: 'top',
              labels: { 
                usePointStyle: true, 
                padding: 15,
                generateLabels: function(chart) {
                  const original = Chart.defaults.plugins.legend.labels.generateLabels;
                  const labels = original.call(this, chart);
                  console.log('Legend labels generated:', labels.length);
                  return labels;
                }
              }
            },
            tooltip: {
              mode: 'index',
              intersect: false,
              backgroundColor: 'rgba(0,0,0,0.8)',
              titleColor: '#ffffff',
              bodyColor: '#ffffff',
              borderColor: '#667eea',
              borderWidth: 1,
              callbacks: {
                title: function(context) {
                  return 'Date: ' + context[0].label;
                },
                label: function(context) {
                  return context.dataset.label + ': ' + context.parsed.y + ' articles';
                }
              }
            }
          },
          scales: {
            x: {
              title: { 
                display: true, 
                text: 'Date', 
                color: '#495057',
                font: { size: 14, weight: 'bold' }
              },
              grid: { color: '#e9ecef' },
              ticks: { 
                color: '#6c757d',
                maxTicksLimit: 10
              }
            },
            y: {
              beginAtZero: true,
              title: { 
                display: true, 
                text: 'Number of Articles', 
                color: '#495057',
                font: { size: 14, weight: 'bold' }
              },
              grid: { color: '#e9ecef' },
              ticks: { 
                precision: 0,
                color: '#6c757d'
              }
            }
          },
          interaction: {
            mode: 'nearest',
            axis: 'x',
            intersect: false
          },
          animation: {
            duration: 1500,
            easing: 'easeInOutQuart'
          }
        }
      });
      
      console.log('✓ Real chart created successfully');
      console.log('Chart datasets:', chart.data.datasets.length);
      
    } catch (error) {
      console.error('✗ Real chart creation failed:', error);
      console.error('Error stack:', error.stack);
      createTestChart(canvas);
    }
  }

  function prepareChartData(timelineData) {
    console.log('=== Preparing Chart Data ===');
    
    if (!timelineData || timelineData.length === 0) {
      console.error('No timeline data to prepare');
      return { labels: [], datasets: [] };
    }

    const colors = [
      '#667eea', '#764ba2', '#f093fb', '#f5576c', '#4facfe',
      '#43e97b', '#38f9d7', '#ffecd2', '#fcb69f', '#a8edea'
    ];
    
    // Get labels from first term's data
    if (!timelineData[0].data || timelineData[0].data.length === 0) {
      console.error('First term has no data points');
      return { labels: [], datasets: [] };
    }
    
    const labels = timelineData[0].data.map(item => {
      // Format date for display (e.g., "2024-08-01" -> "Aug 1")
      const date = new Date(item.date);
      return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
    });
    
    console.log('Generated', labels.length, 'labels');
    console.log('Sample labels:', labels.slice(0, 5));

    const datasets = timelineData.map((termData, index) => {
      const data = termData.data.map(item => parseInt(item.count) || 0);
      const color = colors[index % colors.length];
      
      console.log(`Term "${termData.term_name}": ${data.length} data points, max value: ${Math.max(...data)}`);
      
      return {
        label: termData.term_name,
        data: data,
        borderColor: color,
        backgroundColor: color + '20', // Add transparency
        tension: 0.2,
        borderWidth: 3,
        pointRadius: 3,
        pointHoverRadius: 6,
        pointBackgroundColor: color,
        pointBorderColor: '#ffffff',
        pointBorderWidth: 2,
        fill: false
      };
    });

    console.log('Generated', datasets.length, 'datasets');
    console.log('Chart data structure complete');

    return { labels: labels, datasets: datasets };
  }

  function bindControls() {
    console.log('=== Binding Chart Controls ===');
    
    const termSelector = document.getElementById('term-selector');
    const resetButton = document.getElementById('reset-chart');
    const clearButton = document.getElementById('clear-chart');

    if (termSelector) {
      console.log('✓ Term selector found with', termSelector.options.length, 'options');
      termSelector.addEventListener('change', function() {
        console.log('Term selector changed, selected:', this.selectedOptions.length, 'terms');
        updateChart();
      });
    } else {
      console.warn('⚠️ Term selector not found');
    }

    if (resetButton) {
      console.log('✓ Reset button found');
      resetButton.addEventListener('click', function() {
        console.log('Reset button clicked');
        resetToTop10();
      });
    } else {
      console.warn('⚠️ Reset button not found');
    }

    if (clearButton) {
      console.log('✓ Clear button found');
      clearButton.addEventListener('click', function() {
        console.log('Clear button clicked');
        clearAllTerms();
      });
    } else {
      console.warn('⚠️ Clear button not found');
    }
  }

  function updateChart() {
    if (!chart) return;

    const termSelector = document.getElementById('term-selector');
    const selectedValues = Array.from(termSelector.selectedOptions).map(option => option.value);
    
    // Filter timeline data to selected terms
    const selectedData = allTimelineData.filter(termData => 
      selectedValues.includes(termData.term_id.toString())
    );

    const chartData = prepareChartData(selectedData);
    chart.data = chartData;
    chart.update('active');
  }

  function resetToTop10() {
    const termSelector = document.getElementById('term-selector');
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
    const termSelector = document.getElementById('term-selector');
    if (!termSelector) return;

    // Clear all selections
    Array.from(termSelector.options).forEach(option => {
      option.selected = false;
    });

    updateChart();
  }

  function updateSelectorState() {
    const termSelector = document.getElementById('term-selector');
    if (!termSelector) return;

    // Mark top 10 as selected by default
    const options = Array.from(termSelector.options);
    for (let i = 0; i < Math.min(10, options.length); i++) {
      options[i].selected = true;
    }
  }

  // Debug information on script load
  console.log('=== Chart Script Loaded Successfully ===');
  console.log('Environment check:');
  console.log('- Drupal available:', typeof Drupal !== 'undefined');
  console.log('- jQuery available:', typeof jQuery !== 'undefined');
  console.log('- drupalSettings available:', typeof drupalSettings !== 'undefined');

})(Drupal, drupalSettings);