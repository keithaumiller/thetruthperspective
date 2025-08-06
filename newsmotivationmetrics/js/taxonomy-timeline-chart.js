(function (Drupal, drupalSettings) {
  'use strict';

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
      if (typeof settings.newsmotivationmetrics !== 'undefined') {
        allTimelineData = settings.newsmotivationmetrics.timelineData || [];
        topTerms = settings.newsmotivationmetrics.topTerms || [];
        
        // Initialize chart on page load
        once('taxonomy-timeline-chart', '#taxonomy-timeline-chart', context).forEach(function(element) {
          initializeChart();
          bindControls();
        });
      }
    }
  };

  function initializeChart() {
    const ctx = document.getElementById('taxonomy-timeline-chart');
    if (!ctx) return;

    // Get initial data (top 10 terms)
    const initialData = allTimelineData.slice(0, 10);
    const chartData = prepareChartData(initialData);

    chart = new Chart(ctx, {
      type: 'line',
      data: chartData,
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          title: {
            display: true,
            text: 'Topic Trends Over Time (Last 90 Days)',
            font: {
              size: 16,
              weight: 'bold'
            },
            color: '#2c3e50'
          },
          legend: {
            display: true,
            position: 'top',
            labels: {
              usePointStyle: true,
              padding: 15,
              font: {
                size: 12
              }
            }
          },
          tooltip: {
            mode: 'index',
            intersect: false,
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
            type: 'time',
            time: {
              unit: 'day',
              displayFormats: {
                day: 'MMM d'
              }
            },
            title: {
              display: true,
              text: 'Date',
              color: '#495057',
              font: {
                weight: 'bold'
              }
            },
            grid: {
              color: '#e9ecef'
            }
          },
          y: {
            beginAtZero: true,
            title: {
              display: true,
              text: 'Number of Articles',
              color: '#495057',
              font: {
                weight: 'bold'
              }
            },
            grid: {
              color: '#e9ecef'
            },
            ticks: {
              precision: 0
            }
          }
        },
        interaction: {
          mode: 'nearest',
          axis: 'x',
          intersect: false
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
    });

    // Set initial selector state
    updateSelectorState();
  }

  function prepareChartData(timelineData) {
    if (!timelineData || timelineData.length === 0) {
      return {
        labels: [],
        datasets: []
      };
    }

    // Get all unique dates from the first term's data
    const labels = timelineData[0].data.map(item => item.date);

    // Create datasets for each term
    const datasets = timelineData.map((termData, index) => {
      return {
        label: termData.term_name,
        data: termData.data.map(item => item.count),
        borderColor: colorPalette[index % colorPalette.length],
        backgroundColor: colorPalette[index % colorPalette.length] + '20', // 20% opacity
        fill: false,
        tension: 0.2
      };
    });

    return {
      labels: labels,
      datasets: datasets
    };
  }

  function bindControls() {
    const termSelector = document.getElementById('term-selector');
    const resetButton = document.getElementById('reset-chart');
    const clearButton = document.getElementById('clear-chart');

    if (termSelector) {
      termSelector.addEventListener('change', function() {
        updateChart();
      });
    }

    if (resetButton) {
      resetButton.addEventListener('click', function() {
        resetToTop10();
      });
    }

    if (clearButton) {
      clearButton.addEventListener('click', function() {
        clearAllTerms();
      });
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

})(Drupal, drupalSettings);