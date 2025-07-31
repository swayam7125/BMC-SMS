// File: /assets/js/dynamic_chart.js
document.addEventListener("DOMContentLoaded", function () {
  console.log("Dynamic chart script started.");

  const chartCanvas = document.getElementById("myAreaChart");
  if (!chartCanvas) {
    console.error("Chart canvas element with ID 'myAreaChart' was not found!");
    return;
  }

  // Read role, userId, and baseUrl from the canvas element's data attributes
  const role = chartCanvas.dataset.role;
  const userId = chartCanvas.dataset.userId;
  const baseUrl = chartCanvas.dataset.baseUrl || ""; // Default to empty string if not set
  const chartTitle = document.getElementById("chart-title");
  const chartArea = document.querySelector(".chart-area");
  let myLineChart; // Variable to hold the chart instance

  console.log(`Role: ${role}, User ID: ${userId}, Base URL: ${baseUrl}`);

  if (!role || !userId) {
    console.error(
      "Role or User ID is missing from the canvas data attributes."
    );
    chartArea.innerHTML =
      '<p style="text-align:center; padding-top: 5rem;">Error: Could not identify user role.</p>';
    return;
  }

  // Function to format numbers for tooltips and axes
  function number_format(number) {
    if (isNaN(number) || number === null) return number;
    return new Intl.NumberFormat("en-US").format(number);
  }

  // Construct the correct URL for the fetch request
  const fetchUrl = `${baseUrl}includes/chart_data/dynamic_chart_data.php?role=${role}&userId=${userId}`;
  console.log("Fetching chart data from URL:", fetchUrl);

  // --- Start Fetching Data ---
  fetch(fetchUrl)
    .then((response) => {
      console.log("Received response from server.");
      if (!response.ok) {
        throw new Error(
          `Network response was not ok. Status: ${response.status}`
        );
      }
      return response.json();
    })
    .then((config) => {
      console.log("Parsed JSON data:", config);

      if (config.error) {
        console.warn("Server returned an error:", config.error);
        chartArea.innerHTML = `<p style="text-align:center; padding-top: 5rem;">${config.error}</p>`;
        if (chartTitle) chartTitle.innerText = "Overview";
        return;
      }

      if (!config.labels || !config.datasets) {
        throw new Error("Invalid data structure received from server.");
      }

      // Update chart title
      if (chartTitle) chartTitle.innerText = config.title || "Overview";

      // Destroy previous chart instance if it exists
      if (myLineChart) {
        myLineChart.destroy();
      }

      // --- Chart.js Configuration ---
      const chartOptions = {
        maintainAspectRatio: false,
        layout: {
          padding: { left: 10, right: 25, top: 25, bottom: 0 },
        },
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
                callback: function (value) {
                  return number_format(value);
                },
              },
              gridLines: {
                color: "rgb(234, 236, 244)",
                zeroLineColor: "rgb(234, 236, 244)",
                drawBorder: false,
                borderDash: [2],
                zeroLineBorderDash: [2],
              },
              // Apply custom options from PHP if they exist
              ...((config.options &&
                config.options.scales &&
                config.options.scales.yAxes &&
                config.options.scales.yAxes[0]) ||
                {}),
            },
          ],
        },
        legend: { display: config.datasets.length > 1 },
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
              var datasetLabel =
                chart.datasets[tooltipItem.datasetIndex].label || "";
              return `${datasetLabel}: ${number_format(tooltipItem.yLabel)}`;
            },
          },
        },
      };

      console.log("Rendering chart with options:", chartOptions);
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
        '<p style="text-align:center; padding-top: 5rem;">A client-side error occurred. Please check the browser console.</p>';
      if (chartTitle) chartTitle.innerText = "Overview";
    });
});
