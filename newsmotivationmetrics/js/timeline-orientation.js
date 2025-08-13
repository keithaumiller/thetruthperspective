/**
 * Timeline Chart Orientation Handler
 * Prevents chart rendering in portrait mode and handles re-initialization in landscape
 */

(function (Drupal, once) {
  'use strict';

  Drupal.behaviors.timelineChartOrientation = {
    attach: function (context, settings) {
      // Only run on mobile devices
      if (!this.isMobileDevice()) {
        return;
      }

      once('timeline-orientation', '.taxonomy-timeline-section', context).forEach((element) => {
        this.initTimelineOrientationHandler(element);
      });
    },

    isMobileDevice: function () {
      return window.innerWidth <= 768;
    },

    initTimelineOrientationHandler: function (timelineSection) {
      const self = this;
      const canvas = timelineSection.querySelector('canvas');
      
      if (!canvas) {
        return;
      }

      // Store original canvas data for restoration
      this.storeCanvasData(canvas);
      
      // Check initial orientation
      this.handleOrientationChange(timelineSection);
      
      // Listen for orientation changes with passive listeners
      window.addEventListener('orientationchange', function() {
        setTimeout(() => {
          self.handleOrientationChange(timelineSection);
        }, 100);
      }, { passive: true });
      
      // Also listen for resize events with passive listeners
      window.addEventListener('resize', this.debounce(() => {
        this.handleOrientationChange(timelineSection);
      }, 300), { passive: true });
    },

    storeCanvasData: function (canvas) {
      // Store canvas attributes for later restoration
      this.canvasData = {
        id: canvas.id,
        width: canvas.getAttribute('width'),
        height: canvas.getAttribute('height'),
        style: canvas.getAttribute('style'),
        ariaLabel: canvas.getAttribute('aria-label')
      };
    },

    handleOrientationChange: function (timelineSection) {
      const isPortrait = window.innerHeight > window.innerWidth;
      const canvas = timelineSection.querySelector('canvas');
      const chartContainer = timelineSection.querySelector('.chart-container');
      
      if (!canvas || !chartContainer) {
        return;
      }

      if (isPortrait) {
        this.hideChart(timelineSection, canvas, chartContainer);
      } else {
        this.showChart(timelineSection, canvas, chartContainer);
      }
    },

    hideChart: function (timelineSection, canvas, chartContainer) {
      // Destroy existing Chart.js instance if it exists
      if (window.Chart && canvas.chart) {
        canvas.chart.destroy();
        delete canvas.chart;
      }
      
      // Hide canvas completely
      canvas.style.display = 'none';
      
      // Add portrait class to timeline section for CSS targeting
      timelineSection.classList.add('portrait-mode');
      
      // Hide chart controls
      const controls = timelineSection.querySelector('.chart-controls-section');
      if (controls) {
        controls.style.display = 'none';
      }
    },

    showChart: function (timelineSection, canvas, chartContainer) {
      // Remove portrait class
      timelineSection.classList.remove('portrait-mode');
      
      // Show canvas
      canvas.style.display = 'block';
      
      // Show chart controls
      const controls = timelineSection.querySelector('.chart-controls-section');
      if (controls) {
        controls.style.display = 'block';
      }
      
      // Re-initialize the chart if it doesn't exist
      this.reinitializeChart(timelineSection, canvas);
    },

    reinitializeChart: function (timelineSection, canvas) {
      // Only reinitialize if chart doesn't already exist
      if (canvas.chart) {
        return;
      }

      // Trigger chart re-initialization by dispatching a custom event
      // This will be picked up by the existing chart behavior
      const event = new CustomEvent('chartReinitialize', {
        detail: {
          canvasId: canvas.id,
          timelineSection: timelineSection
        }
      });
      
      document.dispatchEvent(event);
      
      // Fallback: try to reinitialize using existing Drupal behaviors
      setTimeout(() => {
        if (!canvas.chart && Drupal.behaviors.taxonomyTimelineBlocks) {
          // Re-run the chart behavior on this specific element
          const context = { timelineSection };
          const settings = Drupal.settings || drupalSettings;
          Drupal.behaviors.taxonomyTimelineBlocks.attach(context, settings);
        }
      }, 200);
    },

    debounce: function (func, wait) {
      let timeout;
      return function executedFunction(...args) {
        const later = () => {
          clearTimeout(timeout);
          func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
      };
    }
  };

  // Enhanced chart behavior to handle re-initialization
  Drupal.behaviors.chartReinitialization = {
    attach: function (context, settings) {
      // Listen for chart re-initialization events with passive listeners
      document.addEventListener('chartReinitialize', (event) => {
        const { canvasId, timelineSection } = event.detail;
        
        // Use existing chart initialization logic if available
        if (settings.newsmotivationmetrics && settings.newsmotivationmetrics.blocks) {
          const chartConfig = settings.newsmotivationmetrics.blocks[canvasId];
          if (chartConfig) {
            this.initializeChart(canvasId, chartConfig);
          }
        }
      }, { passive: true });
    },

    initializeChart: function (canvasId, config) {
      const canvas = document.getElementById(canvasId);
      if (!canvas || canvas.chart) {
        return;
      }

      // Use the same initialization logic as the main chart behavior
      // This is a simplified version - the full implementation would match
      // the existing chart initialization in chart-behavior.js
      if (window.Chart && config.timelineData) {
        try {
          canvas.chart = new Chart(canvas, {
            type: 'line',
            data: config.timelineData,
            options: {
              responsive: true,
              maintainAspectRatio: false,
              // ... other chart options
            }
          });
        } catch (error) {
          console.log('Chart re-initialization failed:', error);
        }
      }
    }
  };

})(Drupal, once);
