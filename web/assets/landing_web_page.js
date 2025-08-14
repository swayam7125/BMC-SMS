document.addEventListener("DOMContentLoaded", function () {
    /**
     * Helper function to format numbers with commas
     * @param {number} number - The number to format
     * @returns {string} Formatted number string
     */
    function number_format(number) {
        if (isNaN(number) || number === null) return number;
        return new Intl.NumberFormat("en-US").format(number);
    }

    /**
     * Initializes the School Growth Chart on the landing page.
     * Uses Line chart to show growth over 5-year intervals.
     */
    function initSchoolGrowthChart() {
        const chartCanvas = document.getElementById("schoolGrowthChart");
        if (!chartCanvas) {
            console.log("School Growth Chart not found on this page");
            return;
        }

        // Get data from canvas data attributes
        const years = JSON.parse(chartCanvas.dataset.years || '[]');
        const openedData = JSON.parse(chartCanvas.dataset.opened || '[]');

        // Chart configuration
        const config = {
            type: 'line',
            data: {
                labels: years,
                datasets: [{
                    label: 'Total Schools',
                    data: openedData,
                    borderColor: 'rgba(78, 115, 223, 1)',
                    backgroundColor: 'rgba(78, 115, 223, 0.05)',
                    lineTension: 0.3,
                    pointRadius: 3,
                    pointBackgroundColor: 'rgba(78, 115, 223, 1)',
                    pointBorderColor: 'rgba(78, 115, 223, 1)',
                    pointHoverRadius: 5,
                    pointHoverBackgroundColor: 'rgba(78, 115, 223, 1)',
                    pointHoverBorderColor: 'rgba(78, 115, 223, 1)',
                    pointHitRadius: 10,
                    pointBorderWidth: 2
                }]
            },
            options: {
                maintainAspectRatio: false,
                layout: {
                    padding: {
                        left: 10,
                        right: 25,
                        top: 25,
                        bottom: 0
                    }
                },
                scales: {
                    xAxes: [{
                        gridLines: {
                            display: false,
                            drawBorder: false
                        },
                        ticks: {
                            maxTicksLimit: 7,
                            padding: 10,
                            color: '#858796'
                        }
                    }],
                    yAxes: [{
                        ticks: {
                            maxTicksLimit: 5,
                            padding: 10,
                            color: '#858796',
                            callback: function(value) {
                                return number_format(value);
                            }
                        },
                        gridLines: {
                            color: 'rgb(234, 236, 244)',
                            zeroLineColor: 'rgb(234, 236, 244)',
                            drawBorder: false,
                            borderDash: [2],
                            zeroLineBorderDash: [2]
                        }
                    }]
                },
                legend: {
                    display: true,
                    position: 'bottom',
                    labels: {
                        color: '#5a5c69',
                        font: {
                            family: "'Nunito', sans-serif"
                        }
                    }
                },
                tooltips: {
                    backgroundColor: 'rgb(255,255,255)',
                    bodyFontColor: '#858796',
                    titleMarginBottom: 10,
                    titleFontColor: '#6e707e',
                    titleFontSize: 14,
                    borderColor: '#dddfeb',
                    borderWidth: 1,
                    xPadding: 15,
                    yPadding: 15,
                    displayColors: false,
                    intersect: false,
                    mode: 'index',
                    caretPadding: 10,
                    callbacks: {
                        label: function(tooltipItem, chart) {
                            const datasetLabel = chart.datasets[tooltipItem.datasetIndex].label || '';
                            const value = number_format(tooltipItem.yLabel);
                            return `${datasetLabel}: ${value}`;
                        }
                    }
                }
            }
        };

        // Create the chart
        new Chart(chartCanvas.getContext('2d'), config);
    }

  /**
   * Adds click event listeners to filter tag buttons to toggle their 'active' state.
   */
  function initFilterTags() {
    const filterButtons = document.querySelectorAll(".filter-tags .btn");
    filterButtons.forEach((button) => {
      button.addEventListener("click", function () {
        // Example of toggling active state.
        // In a real app, this would trigger a filtering action.
        this.classList.toggle("active");
      });
    });
  }

  // Initialize all functions
  initSchoolGrowthChart();
  initFilterTags();
});
