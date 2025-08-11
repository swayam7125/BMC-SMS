// File: /assets/js/dynamic_chart.js
document.addEventListener("DOMContentLoaded", function () {
  const chartCanvas = document.getElementById("myAreaChart");
  if (!chartCanvas) {
    console.error("Chart canvas element 'myAreaChart' not found!");
    return;
  }

  // Read user and app info from the canvas data attributes
  const role = chartCanvas.dataset.role;
  const userId = chartCanvas.dataset.userId;
  const baseUrl = chartCanvas.dataset.baseUrl || "/";
  const chartTitle = document.getElementById("chart-title");
  const chartArea = document.querySelector(".chart-area");
  let myLineChart; // Variable to hold the chart instance

  if (!role || !userId) {
    chartArea.innerHTML =
      '<p class="text-center pt-5">Error: Could not identify user role.</p>';
    return;
  }

  // Helper function to format numbers with commas
  function number_format(number) {
    if (isNaN(number) || number === null) return number;
    return new Intl.NumberFormat("en-US").format(number);
  }

  // Construct the correct URL for the fetch request
  const fetchUrl = `${baseUrl}includes/chart_data/dynamic_chart_data.php?role=${role}&userId=${userId}`;

  // Fetch data from the backend
  fetch(fetchUrl)
    .then((response) => {
      if (!response.ok) {
        throw new Error(
          `Network response was not ok. Status: ${response.status}`
        );
      }
      return response.json();
    })
    .then((config) => {
      // Check if the server returned a handled error
      if (config.error) {
        chartArea.innerHTML = `<p class="text-center pt-5">${config.error}</p>`;
        if (chartTitle) chartTitle.innerText = "Overview";
        return;
      }

      if (!config.labels || !config.datasets) {
        throw new Error("Invalid data structure received from server.");
      }

      // Update chart title from backend response
      if (chartTitle) chartTitle.innerText = config.title || "Overview";

      // Destroy the previous chart instance if it exists to prevent conflicts
      if (myLineChart) {
        myLineChart.destroy();
      }

      // --- Main Chart.js Configuration ---
      const chartOptions = {
        maintainAspectRatio: false,
        layout: { padding: { left: 10, right: 25, top: 25, bottom: 0 } },
        scales: {
          xAxes: [
            {
              gridLines: { display: false, drawBorder: false },
              ticks: { maxTicksLimit: 7 },
            },
          ],
          yAxes: [
            {
              ticks: {
                maxTicksLimit: 5,
                padding: 10,
                callback: number_format, // Default y-axis label formatter
                precision: 0, // This line ensures no decimal values on the axis
              },
              gridLines: {
                color: "rgb(234, 236, 244)",
                zeroLineColor: "rgb(234, 236, 244)",
                drawBorder: false,
                borderDash: [2],
                zeroLineBorderDash: [2],
              },
            },
          ],
        },
        legend: { display: config.datasets.length > 1 }, // Show legend only for multiple datasets
        tooltips: {
          backgroundColor: "rgb(255,255,255)",
          bodyFontColor: "#858796",
          titleMarginBottom: 10,
          titleFontColor: "#6e707e",
          titleFontSize: 14,
          borderColor: "#dddfeb",
          borderWidth: 1,
          xPadding: 15,
          yPadding: 15,
          displayColors: false,
          intersect: false,
          mode: "index",
          caretPadding: 10,
          callbacks: {
            label: function (tooltipItem, chart) {
              const datasetLabel =
                chart.datasets[tooltipItem.datasetIndex].label || "";
              let value = number_format(tooltipItem.yLabel);
              // Append '%' to tooltip if specified by backend
              if (config.yAxisFormat === "percentage") {
                value += "%";
              }
              return `${datasetLabel}: ${value}`;
            },
          },
        },
      };

      // Apply special formatting flags sent from the backend
      if (config.yAxisFormat === "percentage") {
        chartOptions.scales.yAxes[0].ticks.max = 100; // Set y-axis max to 100 for percentage
        chartOptions.scales.yAxes[0].ticks.min = 0; // Set y-axis min to 0
        chartOptions.scales.yAxes[0].ticks.callback = function (value) {
          return value + "%"; // Y-axis label formatter for percentages
        };
        // For percentage charts, we don't need the precision setting.
        delete chartOptions.scales.yAxes[0].ticks.precision;
      }

      // Render the new chart
      myLineChart = new Chart(chartCanvas.getContext("2d"), {
        type: "line",
        data: {
          labels: config.labels,
          datasets: config.datasets,
        },
        options: chartOptions,
      });
    })
    .catch((error) => {
      console.error(
        "Fatal Error: Could not fetch or render chart data.",
        error
      );
      chartArea.innerHTML =
        '<p class="text-center pt-5">An error occurred while loading chart data.</p>';
      if (chartTitle) chartTitle.innerText = "Overview";
    });
});
