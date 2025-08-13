(function (Drupal, drupalSettings) {
  'use strict';

  console.log('=== News Motivation Timeline Chart Script Loading ===');
  console.log('Drupal object:', typeof Drupal);
  console.log('drupalSettings object:', typeof drupalSettings);

  let chart = null;
  let termSelector = null;

  Drupal.behaviors.newsMotivationTimelineChart = {
    attach: function (context, settings) {
      console.log('=== Drupal Behavior Attach Called ===');
      console.log('Context type:', typeof context);
      console.log('Context element:', context);
      console.log('Settings keys:', Object.keys(settings));

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
      
      console.log('Found', canvases.length, 'chart canvases to process');
      
      canvases.forEach((canvas) => {
        // Skip if already processed
        if (canvas.hasAttribute('data-chart-processed')) {
          console.log('Skipping already processed canvas:', canvas.id);
          return;
        }
        
        // Mark as processed
        canvas.setAttribute('data-chart-processed', 'true');
        
        const canvasId = canvas.id;
        console.log('✅ Processing canvas with ID:', canvasId);

        // Find term selector based on canvas ID
        const selectorId = canvasId === 'news-motivation-timeline-chart' ? 'term-selector' : 'term-selector-' + canvasId.split('-').pop();
        termSelector = document.getElementById(selectorId) || context.querySelector('#' + selectorId);
        
        if (!termSelector) {
          console.log('❌ Term selector not found with ID:', selectorId);
          // Try fallback selector
          termSelector = document.querySelector('.term-selector') || context.querySelector('.term-selector');
          if (termSelector) {
            console.log('✅ Found term selector using class fallback');
          } else {
            console.log('❌ Term selector not found at all');
            const statusElement = document.querySelector('[id*="chart-status"]');
            if (statusElement) {
              statusElement.textContent = '⚠️ Term selector not found. Controls may not work.';
            }
            return;
          }
        } else {
          console.log('✅ Term selector found:', termSelector);
        }

        // Check if we have the necessary data
        if (!settings.newsmotivationmetrics) {
          console.log('❌ Chart data not available in drupalSettings');
          console.log('Available settings keys:', Object.keys(settings));
          const statusElement = document.querySelector('[id*="chart-status"]');
          if (statusElement) {
            statusElement.textContent = '⚠️ Chart data not available in drupalSettings.';
          }
          return;
        }

        const chartData = settings.newsmotivationmetrics;
        console.log('📊 Chart data loaded:', {
          dataPoints: chartData.timelineData ? chartData.timelineData.length : 0,
          termCount: chartData.topTerms ? chartData.topTerms.length : 0,
          timestamp: Math.floor(Date.now() / 1000),
          date: new Date().toISOString().slice(0, 19).replace('T', ' '),
          php_version: chartData.debugInfo?.php_version || 'unknown',
          extendedTermsAvailable: chartData.extendedTerms ? 'Yes' : 'No',
          extendedTermsCount: chartData.extendedTerms?.timelineData?.length || 0
        });
        console.log('Timeline data available:', chartData.timelineData ? chartData.timelineData.length : 0, 'datasets');
        
        // Log extended terms availability
        if (chartData.extendedTerms) {
          console.log('📈 Extended terms available:', chartData.extendedTerms.timelineData?.length || 0, 'additional terms');
        } else {
          console.log('⚠️ No extended terms data found');
        }        // Validate chart data structure
        if (!chartData.timelineData || !Array.isArray(chartData.timelineData) || chartData.timelineData.length === 0) {
          console.log('❌ No timeline data available or invalid format');
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
    console.log('🎯 Initializing Chart.js...');
    console.log('Canvas element:', canvas);
    console.log('Canvas ID:', canvasId);
    console.log('Data structure:', data);
    
    if (chart) {
      console.log('Destroying existing chart...');
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
    
      // Prepare datasets from timeline data
      const datasets = data.timelineData.map((termData, index) => {
        console.log(`Dataset ${index}: ${termData.term_name} with ${termData.data ? termData.data.length : 0} data points`);
        
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

      console.log('Chart labels:', labels.slice(0, 5), '... (showing first 5)');
      console.log('Chart datasets:', datasets.length, 'total datasets');

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

      console.log('✅ Chart initialized successfully');
      
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

    // Reset button
    const resetId = canvasId === 'news-motivation-timeline-chart' ? 'reset-chart' : 'reset-chart-' + canvasId.split('-').pop();
    const resetButton = document.getElementById(resetId) || document.querySelector('[id*="reset-chart"]');
    if (resetButton) {
      resetButton.addEventListener('click', resetToTop10);
    }

    // Clear button
    const clearId = canvasId === 'news-motivation-timeline-chart' ? 'clear-chart' : 'clear-chart-' + canvasId.split('-').pop();
    const clearButton = document.getElementById(clearId) || document.querySelector('[id*="clear-chart"]');
    if (clearButton) {
      clearButton.addEventListener('click', clearAllTerms);
    }
  }

  function updateChart() {
    if (!chart || !termSelector) return;

    const selectedTermIds = Array.from(termSelector.selectedOptions).map(option => parseInt(option.value));
    console.log('📊 Updating chart with selected terms:', selectedTermIds);

    // Combine timeline data from both top terms and extended terms
    let allData = drupalSettings.newsmotivationmetrics.timelineData || [];
    
    // Add extended terms data if available
    if (drupalSettings.newsmotivationmetrics.extendedTerms && 
        drupalSettings.newsmotivationmetrics.extendedTerms.timelineData) {
      const extendedData = drupalSettings.newsmotivationmetrics.extendedTerms.timelineData;
      console.log('📈 Found extended timeline data with', extendedData.length, 'terms');
      
      // Merge extended data, avoiding duplicates
      const existingTermIds = new Set(allData.map(item => parseInt(item.term_id)));
      const newExtendedData = extendedData.filter(item => !existingTermIds.has(parseInt(item.term_id)));
      allData = [...allData, ...newExtendedData];
      
      console.log('📊 Total available timeline data:', allData.length, 'terms');
    }

    // Filter datasets based on selected terms
    const filteredData = allData.filter(termData => selectedTermIds.includes(parseInt(termData.term_id)));
    
    console.log('🎯 Found timeline data for', filteredData.length, 'of', selectedTermIds.length, 'selected terms');

    // Warn about terms without timeline data
    const foundTermIds = new Set(filteredData.map(item => parseInt(item.term_id)));
    const missingTermIds = selectedTermIds.filter(id => !foundTermIds.has(id));
    if (missingTermIds.length > 0) {
      console.warn('⚠️ No timeline data available for term IDs:', missingTermIds);
    }

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
      let message = `📊 Chart updated with ${filteredData.length} trend lines`;
      if (missingTermIds.length > 0) {
        message += ` (${missingTermIds.length} terms have no timeline data)`;
      }
      statusElement.textContent = message;
    }
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