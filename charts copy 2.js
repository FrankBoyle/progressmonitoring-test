let table; // Global reference to the Tabulator table
let chart; // Reference to the line chart
let barChart; // Reference to the bar chart
let isScrolling;
let customColumnNames = {}; // This will store the custom names
let metadataId; // Global metadataId
let studentIdNew; // Global studentIdNew
let performanceData = [];

// Define series colors
const seriesColors = [
    '#082645', '#FF8C00', '#388E3C', '#D32F2F', '#7B1FA2', '#1976D2', '#C2185B', '#0288D1', '#7C4DFF', '#C21807'
];

document.addEventListener('DOMContentLoaded', function() {
    setupInitialPageLoad();
    attachEventListeners();
    initializeCharts();
    fetchGoals(studentIdNew, metadataId);

    document.getElementById('printReportBtn').addEventListener('click', showPrintDialogModal);
    window.hidePrintDialogModal = hidePrintDialogModal;
    window.printReport = printReport;

    document.querySelectorAll('.selector-item').forEach(item => {
        item.addEventListener('click', function() {
            item.classList.toggle('selected');
            //console.log(`Toggled selection for ${item.getAttribute('data-section')}: ${item.classList.contains('selected')}`);
        });
    });
});

function setupInitialPageLoad() {
    const urlParams = new URLSearchParams(window.location.search);
    studentIdNew = urlParams.get('student_id');
    metadataId = urlParams.get('metadata_id');
    
    window.studentIdNew = studentIdNew;
    window.metadataId = metadataId;
    window.schoolId = schoolId;

    if (!studentIdNew || !metadataId) {
        console.error('Student ID or Metadata ID is missing in the URL parameters.');
        alert('Student ID or Metadata ID is missing. Please check the URL parameters.');
        return;
    }
    
    fetchInitialData(studentIdNew, metadataId);
}

function attachEventListeners() {
    const filterBtn = document.getElementById('filterData');
    if (filterBtn) {
        filterBtn.addEventListener('click', function() {
            const iepDate = document.getElementById('iep_date').value;
            saveIEPDate(iepDate, studentIdNew);
        });
    }

    const addDataRowBtn = document.getElementById("addDataRow");
    if (addDataRowBtn) {
        addDataRowBtn.addEventListener("click", addDataRowHandler);
    }

    const editColumnsBtn = document.getElementById('editColumnsBtn');
    if (editColumnsBtn) {
        editColumnsBtn.addEventListener('click', showEditColumnNamesModal);
        //console.log("Edit columns button listener attached.");
    } else {
        //console.log("Edit columns button not found.");
    }
}

function addDataRowHandler() {
    const newRowDateInput = document.getElementById("newRowDate");
    newRowDateInput.style.display = "block";
    newRowDateInput.focus();

    newRowDateInput.addEventListener("change", function() {
        const newDate = newRowDateInput.value;
        if (newDate === "") {
            alert("Please select a date.");
            return;
        }

        if (isDateDuplicate(newDate)) {
            alert("An entry for this date already exists. Please choose a different date.");
            return;
        }

        const newData = createNewDataObject(studentIdNew, metadataId, newDate);
        submitNewDataRow(newData, newRowDateInput);
    }, { once: true });
}

function createNewDataObject(studentIdNew, metadataId, newDate) {
    const newData = {
        student_id_new: studentIdNew,
        school_id: schoolId,
        metadata_id: metadataId,
        score_date: newDate,
        scores: {}
    };

    for (let i = 1; i <= 10; i++) {
        newData.scores[`score${i}`] = null;
    }

    return newData;
}

function submitNewDataRow(newData, newRowDateInput) {
    //console.log('Sending new data:', newData);
    fetch('./users/insert_performance.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(newData)
    })
    .then(response => response.text())
    .then(text => {
        let result = JSON.parse(text);
        if (result.success) {
            newData.performance_id = result.performance_id;
            table.addRow(newData);
            newRowDateInput.value = "";
            newRowDateInput.style.display = "none";
        } else {
            throw new Error('Failed to add new data: ' + result.error);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while adding new data.');
    });
}

window.addEventListener('scroll', function(event) {
    window.clearTimeout(isScrolling);

    // Disable chart interactions during scroll
    disableChartInteractions();

    isScrolling = setTimeout(function() {
        // Enable chart interactions after scroll
        enableChartInteractions();
    }, 100);
}, false);

// Function to initialize the table
function initializeTable(performanceData, scoreNames, studentIdNew, metadataId) {
    if (table) {
        table.destroy();
    }

    const columns = [
        // Add Actions column with delete button
        {
            title: "Actions",
            field: "actions",
            formatter: function(cell, formatterParams, onRendered) {
                return '<button class="delete-row-btn" data-performance-id="' + cell.getRow().getData().performance_id + '">Delete</button>';
            },
            width: 100,
            hozAlign: "center", // Correct option for horizontal alignment
            cellClick: function(e, cell) {
                const performanceId = cell.getRow().getData().performance_id;

                // Confirm before delete
                if (confirm('Are you sure you want to delete this row?')) {
                    // Send a request to delete the data from the server
                    fetch('./users/delete_performance.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({ performance_id: performanceId })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Remove the row from the table
                            cell.getRow().delete();
                        } else {
                            alert('Failed to delete data. Please try again.');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred while deleting the data.');
                    });
                }
            }
        },
        {
            title: "Date",
            field: "score_date",
            editor: "input",
            formatter: function(cell, formatterParams, onRendered) {
                const DateTime = luxon.DateTime;
                let date = DateTime.fromISO(cell.getValue());
                return date.isValid ? date.toFormat("MM/dd/yyyy") : "(invalid date)";
            },
            editorParams: {
                mask: "MM/DD/YYYY",
                format: "MM/DD/YYYY",
            },
            width: 120,
            frozen: false,
        }
    ];

    Object.keys(scoreNames).forEach((key, index) => {
        columns.push({
            title: scoreNames[key],
            field: `score${index + 1}`,
            editor: "input",
            width: 100
        });
    });

    table = new Tabulator("#performance-table", {
        height: "500px", // Limit table height to 500px
        data: performanceData,
        columns: columns,
        layout: "fitDataStretch",
        tooltips: true,
        movableColumns: false,
        resizableRows: false,
        editTriggerEvent: "dblclick",
        editorEmptyValue: null,
        clipboard: true,
        clipboardCopyRowRange: "range",
        clipboardPasteParser: "range",
        clipboardPasteAction: "range",
        clipboardCopyConfig: {
            rowHeaders: false,
            columnHeaders: true,
        },
        clipboardCopyStyled: false,
        selectableRange: 1,
        selectableRangeColumns: false,
        selectableRangeRows: false,
        selectableRangeClearCells: false,
        virtualDomBuffer: 300, // Increase virtual DOM buffer size
    });

    // Add cellEdited event listener inside initializeTable after declaring table
    table.on("cellEdited", function(cell) {
        const field = cell.getField();
        let value = cell.getValue();

        if (value === "") {
            value = null;
        }

        const updatedData = cell.getRow().getData();
        updatedData[field] = value;
        updatedData.student_id_new = studentIdNew;  // Ensure student_id_new is included
        updatedData.metadata_id = metadataId;  // Ensure metadata_id is included

        // Log the updated data for debugging
        //console.log("Updated data:", updatedData);

        // Update the cell data in the backend (make AJAX call)
        fetch('./users/update_performance.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(updatedData)
        }).then(response => response.json())
          .then(result => {
              if (result.success) {
                  //console.log('Data updated successfully');
              } else {
                  alert('Failed to update data: ' + result.message);
                  console.error('Error info:', result.errorInfo); // Log detailed error info
              }
          })
          .catch(error => console.error('Error:', error));
    });

    // Ensure the table is fully initialized and rendered before allowing selection
    table.on("tableBuilt", function() {
        //console.log("Table fully built and ready for interaction.");
    });
}

function isDateDuplicate(date) {
    const data = table.getData();
    return data.some(row => row['score_date'] === date);
}

function saveIEPDate(iepDate, studentIdNew) {
    //console.log(`Saving IEP Date: ${iepDate} for Student ID: ${studentIdNew}`);
    fetch('./users/save_iep_date.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            iep_date: iepDate,
            student_id: studentIdNew
        })
    })
    .then(response => response.json())
    .then(data => {
        //console.log('IEP date saved:', data);
        if (data.success) {
            fetchInitialData(studentIdNew, metadataId);
        } else {
            alert(data.message);
        }
    })
    .catch(error => console.error('Error saving IEP date:', error));
}

function fetchInitialData(studentIdNew, metadataId) {
    fetch(`./users/fetch_data.php?student_id=${studentIdNew}&metadata_id=${metadataId}`)
        .then(response => response.json())
        .then(data => {
            //console.log('Initial data fetched:', data);
            if (data && data.performanceData && data.scoreNames) {
                createColumnCheckboxes(data.scoreNames);
                customColumnNames = data.scoreNames; // Store the names
                initializeTable(data.performanceData, data.scoreNames, studentIdNew, metadataId);
                if (data.iepDate) {
                    document.getElementById('iep_date').value = data.iepDate;
                }
                if (data.studentName && data.categoryName) {
                    document.title = `${data.studentName} - ${data.categoryName}`;
                }
            } else {
                console.error('Invalid or incomplete initial data:', data);
            }
        })
        .catch(error => {
            console.error('Error fetching initial data:', error);
        });
}

function initializeCharts() {
    initializeLineChart();
    initializeBarChart();
}

function initializeLineChart() {
    const chartOptions = getLineChartOptions([], []); // Empty data initially
    window.lineChart = new ApexCharts(document.querySelector("#chartContainer"), chartOptions);
    window.lineChart.render();
}

function initializeBarChart() {
    const barChartOptions = getBarChartOptions([], []); // Empty data initially
    barChart = new ApexCharts(document.querySelector("#barChartContainer"), barChartOptions);
    barChart.render();
}

// Extract chart data based on selected columns
function extractChartData() {
    try {
        //console.log("Extracting chart data...");
        const data = table.getData();
        const categories = data.map(row => row['score_date']);

        const selectedColumns = Array.from(document.querySelectorAll(".selector-item.selected"))
            .map(item => ({
                field: item.getAttribute("data-column-name"),
                name: item.textContent.trim()  // Use textContent of the item as the series name
            }));

        const series = selectedColumns.map(column => {
            let rawData = data.map(row => row[column.field]);
            let interpolatedData = interpolateData(rawData); // Interpolate missing values
            return {
                name: column.name,  // Using the custom name for the series
                data: interpolatedData,
                color: seriesColors[parseInt(column.field.replace('score', '')) - 1]  // Deduce color by score index
            };
        });

        const trendlineSeries = series.map(seriesData => ({
            name: `${seriesData.name} Trendline`,
            data: getTrendlineData(seriesData.data),
            type: 'line',
            dashArray: 5,
            stroke: { width: 2, curve: 'straight' },
            color: seriesData.color
        }));

        updateLineChart(categories, [...series, ...trendlineSeries]);
        updateBarChart(categories, series);

        //console.log("Charts updated successfully.");
    } catch (error) {
        console.error("Error extracting chart data:", error);
    }
}

function interpolateData(data) {
    let interpolatedData = [...data];
    for (let i = 1; i < interpolatedData.length - 1; i++) {
        if (interpolatedData[i] === null) {
            let prev = i - 1;
            let next = i + 1;
            while (next < interpolatedData.length && interpolatedData[next] === null) {
                next++;
            }
            if (next < interpolatedData.length) {
                let interpolatedValue = interpolatedData[prev] + (interpolatedData[next] - interpolatedData[prev]) * (i - prev) / (next - prev);
                interpolatedData[i] = parseFloat(interpolatedValue.toFixed(2)); // Round to 2 decimal places
            }
        }
    }
    return interpolatedData;
}

// Update Line Chart
function updateLineChart(categories, seriesData) {
    if (!window.lineChart) {
        console.error('Line chart is not initialized');
        return;
    }

    window.lineChart.updateOptions({
        xaxis: { categories },
        yaxis: {
            labels: { formatter: val => val.toFixed(0) } // Ensure whole numbers
        },
        series: seriesData,
        colors: seriesData.map(s => s.color), // Apply specific colors to series
        stroke: {
            curve: 'smooth',
            width: seriesData.map(s => s.name.includes('Trendline') ? 2 : 5),
            dashArray: seriesData.map(s => s.name.includes('Trendline') ? 5 : 0)
        },
        chart: {
            animations: {
                enabled: true,
                easing: 'easeinout',
                speed: 800,
                animateGradually: {
                    enabled: true,
                    delay: 150
                },
                dynamicAnimation: {
                    enabled: true,
                    speed: 350
                }
            }
        }
    }).then(() => {
        // Force a full redraw
        window.lineChart.updateSeries(seriesData);
    });
}

// Data preparation for line and bar charts
function prepareChartData(rawData) {
    const dates = rawData.map(data => data.date);
    const seriesData = rawData.map(data => ({ name: data.name, data: data.values }));
    return { dates, seriesData };
}

// Update Bar Chart
function updateBarChart(categories, seriesData) {
    if (!barChart) {
        console.error('Bar chart is not initialized');
        return;
    }

    if (seriesData.length === 0) {
        seriesData.push({ name: "No Data", data: [] });
    }

    // Calculate the maximum stack height for each category
    const maxStackHeight = categories.map((_, i) => {
        return seriesData.reduce((acc, series) => acc + (series.data[i] || 0), 0);
    });
    const maxDataValue = Math.max(...maxStackHeight);

    barChart.updateOptions({
        xaxis: {
            categories: categories
        },
        yaxis: {
            min: 0, // Ensure the minimum value is 0
            max: maxDataValue + 10 // Add some padding to the max value
        },
        series: seriesData,
        colors: seriesData.map(s => s.color) // Ensure colors are correctly applied
    });
}

// Function to get options for Line Chart
function getLineChartOptions(dates, seriesData) {
    return {
        chart: {
            id: 'chartContainer',
            type: 'line',
            height: 500,
            background: '#fff',
            toolbar: {
                show: true
            },
            animations: {
                enabled: true,
                easing: 'easeinout',
                speed: 800,
                animateGradually: {
                    enabled: true,
                    delay: 150
                },
                dynamicAnimation: {
                    enabled: true,
                    speed: 350
                }
            },
            dropShadow: {
                enabled: true,
                top: 1,
                left: 3,
                blur: 3,
                color: '#000',
                opacity: 0.1
            },
        },
        colors: seriesColors,
        dataLabels: {
            enabled: true,
            formatter: function(val, opts) {
                var seriesName = opts.w.config.series[opts.seriesIndex].name;
                if (val === null) {
                    return '';
                }
                if (seriesName.includes('Trendline')) {
                    return '';  // No labels on trendlines
                }
                return val.toFixed(0);
            },
            style: {
                fontSize: '12px',
                fontWeight: 'bold'
            },
            background: {
                enabled: true,
                borderRadius: 2,
                borderWidth: 1,
                borderColor: '#000',
                dropShadow: {
                    enabled: false
                }
            }
        },
        stroke: {
            curve: 'smooth',
            width: seriesData.map(series => series.name.includes('Trendline') ? 2 : 5),
            dashArray: seriesData.map(series => series.name.includes('Trendline') ? 5 : 0),
            colors: seriesColors
        },
        series: seriesData,
        grid: {
            borderColor: '#a8a8a8',
            strokeDashArray: 0,
            position: 'back',
            xaxis: {
                lines: {
                    show: true
                }
            },
            yaxis: {
                lines: {
                    show: true
                }
            }
        },
        markers: {
            size: 5
        },
        xaxis: {
            categories: dates,
            title: {
                text: 'Date',
                offsetY: -20
            },
            axisTicks: {
                show: true
            },
            axisBorder: {
                show: true
            }
        },
        yaxis: {
            title: {
                text: 'Value'
            },
            labels: {
                formatter: function(val) {
                    return val.toFixed(0);
                }
            }
        },
        legend: {
            position: 'bottom',
            horizontalAlign: 'center',
            showForSingleSeries: true
        }
    };
}

// Function to get options for Bar Chart
function getBarChartOptions(dates, seriesData) {
    return {
        chart: {
            type: 'bar',
            height: '500',
            background: '#fff',
            toolbar: {
                show: true
            },
            stacked: true
        },
        plotOptions: {
            bar: {
                horizontal: false,
                columnWidth: '80%', // Increase the bar width
            },
        },
        colors: seriesColors,
        dataLabels: {
            enabled: true,
            enabledOnSeries: undefined, // Show dataLabels on all series
            formatter: function (val, opts) {
                return val; // Keep the label text the same as the data value
            },
            textAnchor: 'middle',
            distributed: false, // Do not distribute labels individually
            offsetX: 0,
            offsetY: 0,
            style: {
                fontSize: '12px',
                fontFamily: 'Helvetica, Arial, sans-serif',
                fontWeight: 'bold',
                colors: undefined // Colors will be overridden by background.foreColor
            },
            background: {
                enabled: true,
                foreColor: '#fff', // Text color
                padding: 1,
                borderRadius: 0,
                borderWidth: 0, // Thin border
                borderColor: '#000', // Black outline
                opacity: 0.9,
                dropShadow: {
                    enabled: false // Disable background shadow
                }
            },
            dropShadow: {
                enabled: false, // Disable text shadow
                top: 1,
                left: 1,
                blur: 1,
                color: '#000',
                opacity: 0.45
            }
        },
        stroke: {
            show: true,
            width: 2,
            colors: ['transparent']
        },
        series: seriesData,
        xaxis: {
            categories: dates,
            title: {
                text: 'Date',
                offsetY: -10 // Move the axis title closer to the dates
            }
        },
        yaxis: {
            title: {
                text: 'Value'
            },
            labels: {
                formatter: function (val) {
                    return val.toFixed(0);
                }
            }
        },
        fill: {
            opacity: 1
        },
        tooltip: {
            y: {
                formatter: function (val) {
                    return val;
                }
            }
        },
        legend: {
            position: 'bottom',
            horizontalAlign: 'center',
            showForSingleSeries: true // Always show the legend, even for a single series
        }
    };
}

function createColumnCheckboxes(scoreNames) {
    const columnSelector = document.getElementById('columnSelector');
    columnSelector.innerHTML = ''; // Clear any existing checkboxes
    Object.keys(scoreNames).forEach((key, index) => {
        const item = document.createElement('div');
        item.classList.add('selector-item');
        item.setAttribute("data-column-name", `score${index + 1}`);
        item.textContent = scoreNames[key];
        item.addEventListener('click', function() {
            item.classList.toggle('selected');
            extractChartData();
            refreshStatisticsDisplay();  // Update to call refresh on any click
        });
        columnSelector.appendChild(item);
    });
}

function disableChartInteractions() {
    if (window.lineChart) {
        window.lineChart.updateOptions({
            chart: {
                animations: {
                    enabled: false
                }
            }
        });
    }
    if (window.barChart) {
        window.barChart.updateOptions({
            chart: {
                animations: {
                    enabled: false
                }
            }
        });
    }

    const chartElements = document.querySelectorAll('.apexcharts-canvas');
    chartElements.forEach(chart => {
        chart.style.pointerEvents = 'none';
    });
}

function enableChartInteractions() {
    if (window.lineChart) {
        window.lineChart.updateOptions({
            chart: {
                animations: {
                    enabled: true
                }
            }
        });
    }
    if (window.barChart) {
        window.barChart.updateOptions({
            chart: {
                animations: {
                    enabled: true
                }
            }
        });
    }

    const chartElements = document.querySelectorAll('.apexcharts-canvas');
    chartElements.forEach(chart => {
        chart.style.pointerEvents = 'auto';
    });
}

function calculateTrendline(data) {
    const validDataPoints = data.map((val, idx) => ({ x: idx + 1, y: val })).filter(point => point.y !== null && !isNaN(point.y));

    if (validDataPoints.length === 0) {
        return function(x) {
            return 0;
        };
    }

    const n = validDataPoints.length;
    const sumX = validDataPoints.reduce((acc, point) => acc + point.x, 0);
    const sumY = validDataPoints.reduce((acc, point) => acc + point.y, 0);
    const sumXY = validDataPoints.reduce((acc, point) => acc + point.x * point.y, 0);
    const sumXX = validDataPoints.reduce((acc, point) => acc + point.x * point.x, 0);

    const slope = (n * sumXY - sumX * sumY) / (n * sumXX - sumX * sumX);
    const intercept = (sumY - slope * sumX) / n;

    return function (x) {
        return parseFloat((slope * x + intercept).toFixed(2)); // Round to 2 decimal places
    };
}

function getTrendlineData(data) {
    let trendlineFunction = calculateTrendline(data);
    return data.map((_, idx) => {
        const x = idx + 1;
        const y = trendlineFunction(x);
        return y !== null && !isNaN(y) ? y : null;
    });
}

function refreshStatisticsDisplay() {
    const selectedColumns = Array.from(document.querySelectorAll(".selector-item.selected"));
    const tbody = document.getElementById('statsTable').getElementsByTagName('tbody')[0];
    tbody.innerHTML = ''; // Clear existing rows

    if (selectedColumns.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5">No columns selected</td></tr>'; // Handle no selection
        return;
    }

    selectedColumns.forEach(item => {
        const columnField = item.getAttribute("data-column-name");
        const columnName = item.textContent.trim();
        updateStatisticsDisplay(columnField, columnName, tbody);
    });
}

function calculateStatistics(data) {
    let mean = data.reduce((acc, val) => acc + val, 0) / data.length;
    let median = calculateMedian(data);
    let stdDev = calculateStandardDeviation(data, mean);

    return {
        mean: mean.toFixed(2),
        median: median,
        stdDev: stdDev.toFixed(2)
    };
}

function calculateStandardDeviation(data, mean) {
    let squareDiffs = data.map(value => {
        let diff = value - mean;
        return diff * diff;
    });
    let avgSquareDiff = squareDiffs.reduce((sum, value) => sum + value, 0) / data.length;
    return Math.sqrt(avgSquareDiff);
}

function calculateMedian(data) {
    data.sort((a, b) => a - b);
    let mid = Math.floor(data.length / 2);
    return data.length % 2 !== 0 ? data[mid] : (data[mid - 1] + data[mid]) / 2;
}

function calculateTrendlineEquation(data) {
    const n = data.length;
    let sumX = 0, sumY = 0, sumXY = 0, sumXX = 0;
    data.forEach((y, x) => {
        sumX += x;
        sumY += y;
        sumXY += x * y;
        sumXX += x * x;
    });
    let slope = (n * sumXY - sumX * sumY) / (n * sumXX - sumX * sumX);
    let intercept = (sumY - slope * sumX) / n;
    return `y = ${slope.toFixed(2)}x + ${intercept.toFixed(2)}`;
}

function updateStatisticsDisplay(columnField, columnName, tbody) {
    const data = table.getData().map(row => parseFloat(row[columnField])).filter(val => !isNaN(val));

    if (data.length > 0) {
        const stats = calculateStatistics(data);
        const trendlineEquation = calculateTrendlineEquation(data);
        const row = tbody.insertRow();
        row.innerHTML = `
            <td>${columnName}</td>
            <td>${stats.mean}</td>
            <td>${stats.median}</td>
            <td>${stats.stdDev}</td>
            <td>${trendlineEquation}</td>
        `;
    } else {
        const row = tbody.insertRow();
        row.innerHTML = `<td colspan="5">No data available for ${columnName}</td>`;
    }
}

function showEditColumnNamesModal() {
    const modal = document.getElementById('editColumnNamesModal');
    const form = document.getElementById('editColumnNamesForm');
    form.innerHTML = ''; // Clear previous contents

    //console.log("Custom Column Names: ", customColumnNames); // Log custom column names

    // Use stored custom names
    let index = 1;
    for (const key in customColumnNames) {
        if (customColumnNames.hasOwnProperty(key) && key !== "score_date") { // Exclude score_date
            let label = `<label>Column ${index} (${customColumnNames[key]}): </label>`;
            let input = `<input type="text" data-column-field="${key}" value="${customColumnNames[key]}"><br>`;

            form.innerHTML += label + input;

            //console.log(`Input for ${key} created with value: ${customColumnNames[key]}`);

            index++;
        }
    }

    form.innerHTML += "<button type='submit'>Save Changes</button>"; // Add the submit button at the end
    modal.style.display = 'block'; // Show the modal

    // Log modal display status
    //console.log("Modal displayed with current column names.");

    // Log the form's innerHTML to check the final state of the form
    //console.log("Form innerHTML:", form.innerHTML);
}

function hideEditColumnNamesModal() {
    const modal = document.getElementById('editColumnNamesModal');
    if (modal) {
        modal.style.display = 'none';
        //console.log("Modal hidden.");
    } else {
        //console.log("Modal element for hiding not found.");
    }
}

function submitColumnNames(event) {
    event.preventDefault();
    const inputs = event.target.querySelectorAll('input[type="text"]');
    let updatedNames = {};

    inputs.forEach(input => {
        let field = input.dataset.columnField;
        let newValue = input.value;
        updatedNames[field] = newValue;
    });

    hideEditColumnNamesModal(); // Optionally close the modal after submit
    updateColumnNamesOnServer(updatedNames); // Send new titles to server
}

function updateColumnNamesOnServer(newColumnNames) {
    const urlParams = new URLSearchParams(window.location.search);
    const metadataId = urlParams.get('metadata_id'); // Use the current metadataId dynamically

    // Prepare the data to be sent as FormData to align with your PHP backend expectations
    const formData = new FormData();
    formData.append('metadata_id', metadataId); // Use the current metadataId dynamically
    formData.append('custom_column_names', JSON.stringify(newColumnNames)); // Send the updated names

    // Make an AJAX call to the PHP script
    fetch('./users/edit_goal_columns.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.message) {
            //console.log('Column names updated successfully:', data.message);
            alert('Column names updated successfully!');
        } else if (data.error) {
            console.error('Error updating column names:', data.error);
            alert('Failed to update column names: ' + data.error);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Network or server error occurred.');
    });
}

function fetchGoals(studentIdNew, metadataId) {
    fetch(`./users/fetch_goals.php?student_id=${studentIdNew}&metadata_id=${metadataId}`)
        .then(response => response.json())
        .then(data => {
            //console.log('Goals data fetched:', data);
            if (data && Array.isArray(data)) {
                // Filter goals by metadata_id before displaying
                displayGoals(data.filter(goal => goal.metadata_id == metadataId));
                populateGoalSelectionModal(data);
            } else {
                console.error('Invalid or incomplete goals data:', data);
            }
        })
        .catch(error => {
            console.error('Error fetching goals data:', error);
        });
}

function displayGoals(goals) {
    const goalsContainer = document.getElementById('goals-container');
    goalsContainer.innerHTML = ''; // Clear existing goals

    goals.forEach(goal => {
        if (!goal.goal_id || !goal.goal_description) {
            console.error('Invalid goal structure:', goal);
            return;
        }

        const goalItem = document.createElement('div');
        goalItem.classList.add('goal-item');
        goalItem.innerHTML = `
            <div class="goal-content" id="goal-content-${goal.goal_id}" ondblclick="editGoal(${goal.goal_id})">
                <div class="goal-text">${goal.goal_description}</div>
                <button class="archive-btn">Archive</button>
            </div>
            <div class="goal-edit" id="goal-edit-${goal.goal_id}" style="display: none;">
                <div id="editor-${goal.goal_id}" class="quill-editor"></div>
                <button class="btn btn-primary save-btn">Save</button>
                <button class="btn btn-secondary cancel-btn">Cancel</button>
            </div>
        `;

        goalsContainer.appendChild(goalItem);

        const quill = new Quill(`#editor-${goal.goal_id}`, {
            theme: 'snow',
            modules: {
                toolbar: [
                    [{ 'header': '1'}, {'header': '2'}, { 'font': [] }],
                    [{size: []}],
                    ['bold', 'italic', 'underline', 'strike'],
                    [{ 'color': [] }, { 'background': [] }],
                    [{ 'script': 'sub'}, { 'script': 'super' }],
                    ['blockquote', 'code-block'],
                    [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                    [{ 'indent': '-1'}, { 'indent': '+1' }, { 'align': [] }],
                    ['link', 'image', 'video'],
                    ['clean']  
                ]
            }
        });

        quill.root.innerHTML = goal.goal_description; // Load the goal content

        // Set up button actions
        goalItem.querySelector('.archive-btn').addEventListener('click', () => {
            archiveGoal(goal.goal_id, goalItem);
        });

        goalItem.querySelector('.save-btn').addEventListener('click', () => {
            const updatedContent = quill.root.innerHTML;
            saveGoal(goal.goal_id, updatedContent, goalItem);
        });

        goalItem.querySelector('.cancel-btn').addEventListener('click', () => {
            quill.root.innerHTML = goal.goal_description;
            quill.enable(false);
            document.getElementById(`goal-content-${goal.goal_id}`).style.display = 'block';
            document.getElementById(`goal-edit-${goal.goal_id}`).style.display = 'none';
        });

        window[`quillEditor${goal.goal_id}`] = quill; // Save the editor instance to a global variable for later use
    });
}

function initializeQuillEditor(goalId, content) {
    const quill = new Quill(`#editor-${goalId}`, {
        theme: 'snow'
    });
    quill.root.innerHTML = content;

    window[`quillEditor${goalId}`] = quill; // Save the editor instance to a global variable for later use
}

function editGoal(goalId) {
    const quill = window[`quillEditor${goalId}`];
    if (!quill) {
        console.error('Quill editor instance not found for goal ID:', goalId);
        return;
    }
    quill.enable(true);
    document.getElementById(`goal-content-${goalId}`).style.display = 'none';
    document.getElementById(`goal-edit-${goalId}`).style.display = 'block';
}

function saveGoal(goalId, updatedContent, goalItem) {
    fetch('./users/update_goal.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            goal_id: goalId,
            new_text: updatedContent
        })
    }).then(response => response.json())
      .then(data => {
          if (data.success) {
              goalItem.querySelector('.goal-text').innerHTML = updatedContent;
              const quill = window[`quillEditor${goalId}`];
              quill.enable(false);
              document.getElementById(`goal-content-${goalId}`).style.display = 'block';
              document.getElementById(`goal-edit-${goalId}`).style.display = 'none';
          } else {
              alert('Failed to save goal. Please try again.');
          }
      }).catch(error => {
          console.error('Error:', error);
          alert('An error occurred while saving the goal.');
      });
}

function cancelEdit(goalId) {
    document.getElementById(`goal-content-${goalId}`).style.display = 'block';
    document.getElementById(`goal-edit-${goalId}`).style.display = 'none';
}

function archiveGoal(goalId, goalItem) {
    if (!confirm('Are you sure you want to archive this goal?')) return;

    fetch('./users/archive_goal.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            goal_id: goalId
        })
    }).then(response => response.json())
      .then(data => {
          if (data.success) {
              goalItem.remove();
          } else {
              alert('Failed to archive goal. Please try again.');
          }
      }).catch(error => {
          console.error('Error:', error);
          alert('An error occurred while archiving the goal.');
      });
}

function getSelectedColumns() {
    return Array.from(document.querySelectorAll("#columnSelector .selector-item.selected"));
}

function saveAndPrintReport() {
    const selectedGoal = document.querySelector('.goal-item.selected');
    if (!selectedGoal) {
        alert("Please select a goal.");
        return;
    }

    const selectedColumns = getSelectedColumns();
    if (selectedColumns.length === 0) {
        alert("Please select at least one column.");
        return;
    }

    const selectedSections = Array.from(document.querySelectorAll('#sectionSelectionContainer .selector-item.selected'))
        .map(item => item.getAttribute('data-section'));

    if (selectedSections.length === 0) {
        alert("Please select at least one section to print.");
        return;
    }

    const reportingPeriod = document.getElementById('reporting_period').value.trim();
    const notes = document.getElementById('notes').value.trim();

    if (!reportingPeriod) {
        alert("Please enter the reporting period.");
        return;
    }

    const goalId = selectedGoal.getAttribute('data-goal-id');
    const studentIdNew = window.studentIdNew;
    const schoolId = window.schoolId;
    const metadataId = window.metadataId;

    const payload = {
        goal_id: goalId,
        student_id_new: studentIdNew,
        school_id: schoolId,
        metadata_id: metadataId,
        reporting_period: reportingPeriod,
        notes: notes
    };

    fetch('./users/save_notes.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(payload)
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            // Proceed to print the report
            printReport(selectedGoal, selectedSections, reportingPeriod, notes, selectedColumns);
        } else {
            console.error('Error saving notes:', data.message);
            alert('Failed to save notes: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while saving notes.');
    });
}

function printReport(selectedGoal, selectedSections, reportingPeriod, notes, selectedColumns) {
    let printContents = `<div>${selectedGoal.innerHTML}</div>`;

    if (selectedSections.includes('printTable')) {
        const tableContent = generatePrintTable(selectedColumns);
        printContents += `<div>${tableContent}</div>`;
    }

    if (selectedSections.includes('printLineChart')) {
        const lineChartElement = document.getElementById('chartContainer').outerHTML;
        printContents += `<div id="printLineChartContainer" style="width: 100%; height: auto;">${lineChartElement}</div>`;
    }

    if (selectedSections.includes('printBarChart')) {
        const barChartElement = document.getElementById('barChartContainer').outerHTML;
        printContents += `<div id="printBarChartContainer" style="width: 100%; height: auto;">${barChartElement}</div>`;
    }

    if (selectedSections.includes('printStatistics')) {
        const statisticsContent = document.getElementById('statistics').innerHTML;
        printContents += `<div>${statisticsContent}</div>`;
    }

    // Include reporting period and notes in the print content
    printContents += `<div><strong>Reporting Period:</strong> ${reportingPeriod}</div>`;
    printContents += `<div><strong>Notes:</strong> ${notes}</div>`;

    const originalContents = document.body.innerHTML;
    document.body.innerHTML = printContents;

    setTimeout(() => {
        window.print();
        document.body.innerHTML = originalContents;
        enableChartInteractions();
    }, 500);
}

function generatePrintTable(selectedColumns) {
    const tableData = table.getData();
    if (!tableData || tableData.length === 0) {
        return "<div>No data available to display.</div>";
    }

    // Create the table element
    let tableHTML = '<table style="width: auto; max-width: 50%; margin: 0; table-layout: auto; border-collapse: collapse;">';

    // Generate table header
    tableHTML += '<thead><tr>';
    // Add "Date" column header first
    tableHTML += '<th>Date</th>';
    selectedColumns.forEach(column => {
        const columnName = column.textContent.trim();
        tableHTML += `<th>${columnName}</th>`;
    });
    tableHTML += '</tr></thead>';

    // Generate table body
    tableHTML += '<tbody>';
    tableData.forEach(row => {
        tableHTML += '<tr>';
        // Add "Date" column data first
        const dateValue = row['score_date'] !== null && row['score_date'] !== undefined ? row['score_date'] : '';
        tableHTML += `<td>${dateValue}</td>`;
        selectedColumns.forEach(column => {
            const columnField = column.getAttribute("data-column-name");
            const cellData = row[columnField] !== null && row[columnField] !== undefined ? row[columnField] : '';
            tableHTML += `<td>${cellData}</td>`;
        });
        tableHTML += '</tr>';
    });
    tableHTML += '</tbody>';

    // Close the table element
    tableHTML += '</table>';

    return tableHTML;
}

// Function to show the print dialog modal
function showPrintDialogModal() {
    const selectedColumns = document.querySelectorAll(".selector-item.selected").length;
    if (selectedColumns === 0) {
        alert("Please select at least one column.");
        return;
    }

    const goalContainer = document.getElementById('goalSelectionContainer');
    goalContainer.innerHTML = ''; // Clear previous options

    fetch(`./users/fetch_goals.php?student_id=${studentIdNew}`)
        .then(response => response.json())
        .then(data => {
            if (data.length === 0) {
                alert("No goals available.");
                return;
            }

            const filteredGoals = data.filter(goal => goal.metadata_id == metadataId);

            filteredGoals.forEach(goal => {
                const goalItem = document.createElement('div');
                goalItem.classList.add('goal-item');
                goalItem.setAttribute('data-goal-id', goal.goal_id);
                goalItem.innerHTML = goal.goal_description;
                goalItem.addEventListener('click', function() {
                    document.querySelectorAll('.goal-item').forEach(item => item.classList.remove('selected'));
                    goalItem.classList.add('selected');
                });
                goalContainer.appendChild(goalItem);
            });

            document.getElementById('printDialogModal').style.display = 'block';
        })
        .catch(error => {
            console.error('Error fetching goals:', error);
        });
}

function hidePrintDialogModal() {
    document.getElementById('printDialogModal').style.display = 'none';
}

// Function to hide the print dialog modal
function hidePrintDialogModal() {
    document.getElementById('printDialogModal').style.display = 'none';
}

// Function to show the goal selection modal
function showGoalSelectionModal() {
        const modal = document.getElementById('goalSelectionModal');
        const container = document.getElementById('goalSelectionContainer');
        container.innerHTML = '';

        // Fetch goals and populate the modal
        fetch(`./users/fetch_goals.php?student_id=${studentIdNew}&metadata_id=${metadataId}`)
            .then(response => response.json())
            .then(data => {
                data.forEach(goal => {
                    const goalItem = document.createElement('div');
                    goalItem.classList.add('goal-item');
                    goalItem.textContent = goal.goal_description;
                    goalItem.addEventListener('click', function() {
                        document.querySelectorAll('.goal-item').forEach(item => item.classList.remove('selected'));
                        goalItem.classList.add('selected');
                    });
                    container.appendChild(goalItem);
                });
                modal.style.display = 'block';
            })
            .catch(error => {
                console.error('Error fetching goals:', error);
            });
}

// Function to hide the goal selection modal
function hideGoalSelectionModal() {
        document.getElementById('goalSelectionModal').style.display = 'none';
}

// Function to confirm goal selection
function confirmGoalSelection() {
        const selectedGoal = document.querySelector('.goal-item.selected');
        if (selectedGoal) {
            document.getElementById('goalSelectionModal').style.display = 'none';
        } else {
            alert("Please select a goal.");
        }
}

function populateGoalSelectionModal(goals) {
    const container = document.getElementById('goalSelectionContainer');
    container.innerHTML = '';

    goals.forEach(goal => {
        const goalItem = document.createElement('div');
        goalItem.classList.add('goal-item');
        goalItem.innerHTML = `<div id="editor-modal-${goal.goal_id}" class="quill-editor"></div>`;
        goalItem.addEventListener('click', function() {
            document.querySelectorAll('.goal-item').forEach(item => item.classList.remove('selected'));
            goalItem.classList.add('selected');
        });
        container.appendChild(goalItem);

        const quill = new Quill(`#editor-modal-${goal.goal_id}`, {
            theme: 'snow',
            readOnly: true
        });

        quill.root.innerHTML = goal.goal_description;
        window[`quillEditorModal${goal.goal_id}`] = quill;
    });
}
