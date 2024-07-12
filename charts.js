let table; // Global reference to the Tabulator table
let chart; // Reference to the line chart
let barChart; // Reference to the bar chart
let isScrolling;
let customColumnNames = {}; // This will store the custom names
let metadataId; // Global metadataId
let studentIdNew; // Global studentIdNew
let performanceData = [];
let globalSlope = 0;
let globalIntercept = 0;

// Define series colors
const seriesColors = [
    '#082645', '#FF8C00', '#388E3C', '#D32F2F', '#7B1FA2', '#1976D2', '#C2185B', '#0288D1', '#7C4DFF', '#C21807'
];

document.addEventListener('DOMContentLoaded', function() {
    setupInitialPageLoad();
    attachEventListeners();
    initializeCharts();


    document.getElementById('printReportBtn').addEventListener('click', showPrintDialogModal);
    window.hidePrintDialogModal = hidePrintDialogModal;
    window.printReport = printReport;

    document.querySelectorAll('.selector-item').forEach(item => {
        item.addEventListener('click', function() {
            item.classList.toggle('selected');
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
    fetchGoals(studentIdNew, metadataId); // Ensure goals are fetched
    fetchReportingPeriods(studentIdNew, metadataId); // Fetch reporting periods
}

function fetchReportingPeriods(goalId) {
    fetch(`./users/fetch_reporting_periods.php?student_id=${studentIdNew}&metadata_id=${metadataId}&goal_id=${goalId}`)
        .then(response => response.json())
        .then(data => {
            const reportingPeriodSelect = document.getElementById('reporting_period');
            reportingPeriodSelect.innerHTML = ''; // Clear previous options

            if (data.length === 0) {
                // No previous reports, start with 1
                const option = document.createElement('option');
                option.value = 1;
                option.text = '1';
                reportingPeriodSelect.appendChild(option);
            } else {
                data.forEach((period, index) => {
                    const option = document.createElement('option');
                    option.value = index + 1;
                    option.text = period.reporting_period;
                    reportingPeriodSelect.appendChild(option);
                });

                // Add the next reporting period
                const nextPeriod = document.createElement('option');
                nextPeriod.value = data.length + 1;
                nextPeriod.text = (data.length + 1).toString();
                reportingPeriodSelect.appendChild(nextPeriod);
            }
        })
        .catch(error => {
            console.error('Error fetching reporting periods:', error);
        });
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
        const data = table.getData();
        console.log('Table Data:', data);
        const categories = data.map(row => row['score_date']);
        console.log('Categories:', categories);

        const selectedColumns = getSelectedColumns().map(item => ({
            field: item.getAttribute("data-column-name"),
            name: item.textContent.trim()  // Use textContent of the item as the series name
        }));
        console.log('Selected Columns:', selectedColumns);

        const series = selectedColumns.map(column => {
            let rawData = data.map(row => row[column.field]);
            console.log(`Raw Data for ${column.name}:`, rawData);
            let interpolatedData = interpolateData(rawData); // Interpolate missing values
            console.log(`Interpolated Data for ${column.name}:`, interpolatedData);
            return {
                name: column.name,  // Using the custom name for the series
                data: interpolatedData,
                color: seriesColors[parseInt(column.field.replace('score', '')) - 1]  // Deduce color by score index
            };
        });

        const trendlineSeries = series.map(seriesData => {
            const { trendlineData, slope, intercept } = getTrendlineData(seriesData.data);
            console.log(`Trendline Data for ${seriesData.name}:`, trendlineData);
            console.log(`Trendline Slope: ${slope} Trendline Intercept: ${intercept}`);
            return {
                name: `${seriesData.name} Trendline`,
                data: trendlineData,
                type: 'line',
                dashArray: 5,
                stroke: { width: 2, curve: 'straight' },
                color: seriesData.color
            };
        });

        updateLineChart(categories, [...series, ...trendlineSeries]);
        updateBarChart(categories, series);

        // Ensure the correct statistics are displayed
        const tbody = document.getElementById('statsTable').getElementsByTagName('tbody')[0];
        tbody.innerHTML = ''; // Clear existing rows
        selectedColumns.forEach(column => {
            updateStatisticsDisplay(column.field, column.name, tbody);
        });

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
                enabled: false,
                easing: 'easeinout',
                speed: 800,
                animateGradually: {
                    enabled: true,
                    delay: 150
                },
                dynamicAnimation: {
                    enabled: false,
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
        series: [],
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
            id: 'barChartContainer',
            type: 'bar',
            height: '500',
            background: '#fff',
            toolbar: {
                show: true
            },
            animations: {
                enabled: false, // Disable animations
                easing: 'easeinout',
                speed: 800,
                animateGradually: {
                    enabled: false,
                    delay: 150
                },
                dynamicAnimation: {
                    enabled: false,
                    speed: 350
                }
            },
            stacked: true
        },
        plotOptions: {
            bar: {
                horizontal: true, // Make the bar chart horizontal
                barHeight: '95%', // Increase the bar height (can be adjusted as needed)
                dataLabels: {
                    total: {
                        enabled: true,
                        offsetX: 0,
                        style: {
                            fontSize: '13px',
                            fontWeight: 900
                        }
                    }
                }
            }
        },
        colors: seriesColors,
        dataLabels: {
            enabled: true,
            enabledOnSeries: undefined, // Show dataLabels on all series
            formatter: function(val, opts) {
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
            width: 1,
            colors: ['#fff'] // Add white stroke between bars
        },
        series: seriesData,
        xaxis: {
            categories: dates,
            title: {
                text: 'Value',
                offsetY: -10 // Move the axis title closer to the dates
            },
            labels: {
                formatter: function(val) {
                    return val; // No need to append 'K' dynamically, the values will match the data
                }
            }
        },
        yaxis: {
            title: {
                text: undefined
            }
        },
        fill: {
            opacity: 1
        },
        tooltip: {
            y: {
                formatter: function(val) {
                    return val; // No need to append 'K' dynamically, the values will match the data
                }
            }
        },
        legend: {
            position: 'top',
            horizontalAlign: 'left',
            offsetX: 40
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
    console.log('Data for Trendline Calculation:', data);

    const validDataPoints = data
        .map((val, idx) => ({ x: idx + 1, y: parseFloat(val) }))  // Ensure y-values are parsed as numbers
        .filter(point => !isNaN(point.y));

    if (validDataPoints.length === 0) {
        return {
            trendlineFunction: function (x) {
                return 0;
            },
            slope: 0,
            intercept: 0
        };
    }

    const n = validDataPoints.length;
    const sumX = validDataPoints.reduce((acc, point) => acc + point.x, 0);
    const sumY = validDataPoints.reduce((acc, point) => acc + point.y, 0);
    const sumXY = validDataPoints.reduce((acc, point) => acc + point.x * point.y, 0);
    const sumXX = validDataPoints.reduce((acc, point) => acc + point.x * point.x, 0);

    const slope = (n * sumXY - sumX * sumY) / (n * sumXX - sumX * sumX);
    const intercept = (sumY - slope * sumX) / n;

    console.log('SumX:', sumX, 'SumY:', sumY, 'SumXY:', sumXY, 'SumXX:', sumXX);
    console.log('Slope:', slope, 'Intercept:', intercept);

    const trendlineFunction = function (x) {
        return parseFloat((slope * x + intercept).toFixed(2)); // Round to 2 decimal places
    };

    return {
        trendlineFunction,
        slope,
        intercept
    };
}

function getTrendlineData(data) {
    const dataCopy = data.map(val => parseFloat(val)); // Ensure data is parsed as numbers
    const { trendlineFunction, slope, intercept } = calculateTrendline(dataCopy);
    const trendlineData = data.map((_, idx) => {
        const x = idx + 1;
        const y = trendlineFunction(x);
        return y !== null && !isNaN(y) ? y : null;
    });
    return {
        trendlineData,
        slope,
        intercept
    };
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

// Calculate statistics without altering the original data array
function calculateStatistics(data) {
    let dataCopy = [...data]; // Copy data to avoid modifying the original array
    let mean = dataCopy.reduce((acc, val) => acc + val, 0) / dataCopy.length;
    let median = calculateMedian(dataCopy);
    let stdDev = calculateStandardDeviation(dataCopy, mean);

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
    let dataCopy = [...data]; // Copy data to avoid modifying the original array
    dataCopy.sort((a, b) => a - b);
    let mid = Math.floor(dataCopy.length / 2);
    return dataCopy.length % 2 !== 0 ? dataCopy[mid] : (dataCopy[mid - 1] + dataCopy[mid]) / 2;
}

function calculateTrendlineEquation() {
    return `y = ${globalSlope.toFixed(2)}x + ${globalIntercept.toFixed(2)}`;
}

function updateStatisticsDisplay(columnField, columnName, tbody) {
    const data = table.getData().map(row => parseFloat(row[columnField])).filter(val => !isNaN(val));

    if (data.length > 0) {
        const stats = calculateStatistics(data);
        const { slope, intercept } = calculateTrendline(data);
        const row = tbody.insertRow();
        row.innerHTML = `
            <td>${columnName}</td>
            <td>${stats.mean}</td>
            <td>${stats.median}</td>
            <td>${stats.stdDev}</td>
            <td>y = ${slope.toFixed(2)}x + ${intercept.toFixed(2)}</td>
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
            if (data && Array.isArray(data)) {
                displayGoals(data.filter(goal => goal.metadata_id == metadataId));
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
                <div class="goal-text-container">
                    <div class="goal-text">${goal.goal_description}</div>
                </div>
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

// Function to check if a column is numeric and not "Notes"
function isNumericColumn(columnName) {
    const nonNumericColumns = ['score10']; // Add specific column names here
    const isNumeric = !nonNumericColumns.includes(columnName);
    //console.log(`Checking if column is numeric - Column: ${columnName}, Is Numeric: ${isNumeric}`);
    return isNumeric;
}

// Function to get selected columns excluding non-numeric ones
function getSelectedColumns() {
    return Array.from(document.querySelectorAll('.selector-item.selected'))
        .filter(column => isNumericColumn(column.getAttribute('data-column-name')));
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

    generateReportImage(selectedGoal, selectedSections, reportingPeriod, notes, selectedColumns);
}

function generateReportImage(selectedGoal, selectedSections, reportingPeriod, notes, selectedColumns) {
    const commonWidth = '1000px'; // Fixed width for consistency

    let printContents = `
        <div class="print-container" style="width: ${commonWidth}; margin: 0 auto; padding: 0; padding-bottom: 20px;">
            <div class="goal-text-container" style="width: ${commonWidth}; margin: 0 auto;">
                <div class="print-goal-text">${selectedGoal.innerHTML}</div>
            </div>`;

    if (selectedSections.includes('printTable')) {
        const tableContent = generatePrintTable(selectedColumns);
        printContents += `<div class="print-table-container" style="width: ${commonWidth}; margin: 0 auto;">${tableContent}</div>`;
    }

    if (selectedSections.includes('printLineChart')) {
        const lineChartElement = document.getElementById('chartContainer').cloneNode(true);
        lineChartElement.style.width = commonWidth;
        lineChartElement.style.height = 'auto';
        lineChartElement.style.overflow = 'visible';
        lineChartElement.style.position = 'relative';
        printContents += `<div class="print-graph" style="width: ${commonWidth}; margin: 0 auto;">${lineChartElement.outerHTML}</div>`;
    }

    if (selectedSections.includes('printBarChart')) {
        const barChartElement = document.getElementById('barChartContainer').cloneNode(true);
        barChartElement.style.width = commonWidth;
        barChartElement.style.height = 'auto';
        barChartElement.style.overflow = 'visible';
        barChartElement.style.position = 'relative';
        printContents += `<div class="print-graph" style="width: ${commonWidth}; margin: 0 auto;">${barChartElement.outerHTML}</div>`;
    }

    if (selectedSections.includes('printStatistics')) {
        const statisticsContent = document.getElementById('statistics').innerHTML;
        printContents += `<div class="statistics-area" style="width: ${commonWidth}; margin: 0 auto;">${statisticsContent}</div>`;
    }

    printContents += `
        <div style="width: ${commonWidth}; margin: 0 auto; padding-bottom: 20px;"><strong>Reporting Period:</strong> ${reportingPeriod}</div>
        <div style="width: ${commonWidth}; margin: 0 auto; padding-bottom: 20px;"><strong>Notes:</strong> ${notes}</div>
    </div>`;

    const printDiv = document.createElement('div');
    printDiv.innerHTML = printContents;

    // Ensure styles are embedded within the printDiv
    const styles = `
        <style>
            .print-container { width: ${commonWidth}; margin: 0 auto; padding: 0; padding-bottom: 20px; }
            .goal-text-container { width: ${commonWidth}; margin: 0 auto; }
            .print-goal-text { line-height: 1.5; overflow-wrap: break-word; word-wrap: break-word; white-space: normal; }
            .print-table-container { width: ${commonWidth}; margin: 0 auto; }
            .print-graph { width: ${commonWidth}; margin: 0 auto; overflow: visible; position: relative; }
            .statistics-area { width: ${commonWidth}; margin: 0 auto; }
            body { margin: 0; padding: 0; }
            img { display: block; width: 100%; height: auto; }
        </style>
    `;
    printDiv.insertAdjacentHTML('beforeend', styles);

    document.body.appendChild(printDiv);

    // Resize the charts explicitly before capturing with html2canvas
    resizeCharts(commonWidth).then(() => {
        html2canvas(printDiv, {
            width: parseInt(commonWidth),
            windowWidth: parseInt(commonWidth),
            scrollX: -window.scrollX,
            scrollY: -window.scrollY
        }).then(canvas => {
            document.body.removeChild(printDiv);
            const dataUrl = canvas.toDataURL('image/png');

            // Update the notes object to include the image data
            const payload = {
                goal_id: selectedGoal.getAttribute('data-goal-id'),
                student_id_new: window.studentIdNew,
                school_id: window.schoolId,
                metadata_id: window.metadataId,
                reporting_period: reportingPeriod,
                notes: notes,
                report_image: dataUrl.split(',')[1] // Get base64 string
            };

            console.log("Payload being sent to save_notes.php:", payload);

            // Save notes with the image data
            fetch('./users/save_notes.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(payload)
            })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(error => {
                        throw new Error(`HTTP error! status: ${response.status}, message: ${error.message}`);
                    });
                }
                return response.json();
            })
            .then(data => {
                if (data.status === 'success') {
                    const newTab = window.open();
                    newTab.document.write(`<img src="${dataUrl}" alt="Report Image" style="display: block; margin: 0 auto; width: ${commonWidth};"/>`);
                    newTab.document.close();
                    setTimeout(() => {
                        window.location.reload();
                    }, 2000);
                } else {
                    console.error('Error saving notes:', data.message);
                    alert('Failed to save notes: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while saving notes: ' + error.message);
            });
        });
    });
}

function resizeCharts(width) {
    return new Promise((resolve) => {
        const chartContainers = document.querySelectorAll('.print-graph');
        chartContainers.forEach(container => {
            container.style.width = width;
            container.style.height = 'auto';
        });
        setTimeout(resolve, 500); // Give some time for the resize to take effect
    });
}

function printReport(selectedGoal, selectedSections, reportingPeriod, notes, selectedColumns) {
    let printContents = `<div>${selectedGoal.innerHTML}</div>`;
    
    printContents += `<div class="print-container">`;

    if (selectedSections.includes('printTable')) {
        const tableContent = generatePrintTable(selectedColumns);
        printContents += `<div class="print-table-container">${tableContent}</div>`;
    }

    if (selectedSections.includes('printLineChart')) {
        const lineChartElement = document.getElementById('chartContainer').outerHTML;
        printContents += `<div class="print-graph">${lineChartElement}</div>`;
    }

    if (selectedSections.includes('printBarChart')) {
        const barChartElement = document.getElementById('barChartContainer').outerHTML;
        printContents += `<div class="print-graph">${barChartElement}</div>`;
    }

    printContents += `</div>`;

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
        html2canvas(document.body).then(canvas => {
            const imgData = canvas.toDataURL('image/png');
            const newTab = window.open();
            newTab.document.write('<img src="' + imgData + '" />');
            newTab.document.close();

            document.body.innerHTML = originalContents;
            enableChartInteractions();
        });
    }, 50);
}

// Function to generate the print table
function generatePrintTable(selectedColumns) {
    const tableData = table.getData();
    if (!tableData || tableData.length === 0) {
        return "<div>No data available to display.</div>";
    }

    const excludeColumns = ['Performance Table', 'Line Chart', 'Bar Chart', 'Statistics'];

    let tableHTML = `
        <table class="print-table">
            <thead>
                <tr>
                    <th>Date</th>`;
    
    // Add all columns including non-numeric ones for printing
    selectedColumns.forEach(column => {
        const columnName = column.textContent.trim();
        if (!excludeColumns.includes(columnName)) {
            let splitColumnName = columnName.split('/').join('<br>'); // Splitting at "/" and joining with a line break
            tableHTML += `<th>${splitColumnName}</th>`;
        }
    });

    // Ensure Notes column is included in the printed table
    tableHTML += `<th>Notes</th>`;

    tableHTML += `
                </tr>
            </thead>
            <tbody>`;
    
    tableData.forEach(row => {
        tableHTML += '<tr>';
        const dateValue = row['score_date'] !== null && row['score_date'] !== undefined ? row['score_date'] : '';
        tableHTML += `<td>${dateValue}</td>`;
        
        selectedColumns.forEach(column => {
            const columnField = column.getAttribute("data-column-name");
            const columnName = column.textContent.trim();
            if (!excludeColumns.includes(columnName)) {
                const cellData = row[columnField] !== null && row[columnField] !== undefined ? row[columnField] : '';
                tableHTML += `<td>${cellData}</td>`;
            }
        });

        // Add Notes column data to the printed table
        const notesData = row['score10'] !== null && row['score10'] !== undefined ? row['score10'] : '';
        tableHTML += `<td>${notesData}</td>`;

        tableHTML += '</tr>';
    });
    
    tableHTML += `
            </tbody>
        </table>`;
    
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

                    // Fetch existing reports for the selected goal
                    fetchExistingReports(goal.goal_id);
                    // Show the reporting period dropdown
                    document.getElementById('reportingPeriodContainer').style.display = 'block';
                });
                goalContainer.appendChild(goalItem);
            });

            document.getElementById('printDialogModal').style.display = 'block';
        })
        .catch(error => {
            console.error('Error fetching goals:', error);
        });
}

function fetchExistingReports(goalId) {
    fetch(`./users/fetch_reports.php?goal_id=${goalId}`)
        .then(response => response.json())
        .then(data => {
            const reportingPeriodDropdown = document.getElementById('reporting_period');
            reportingPeriodDropdown.innerHTML = ''; // Clear previous options

            let nextReportingPeriod = 1;

            if (data.length > 0) {
                data.forEach(report => {
                    const option = document.createElement('option');
                    option.value = report.reporting_period;
                    option.textContent = report.reporting_period;
                    reportingPeriodDropdown.appendChild(option);
                    nextReportingPeriod = Math.max(nextReportingPeriod, parseInt(report.reporting_period, 10) + 1);
                });
            }

            // Add option for the next reporting period
            const nextOption = document.createElement('option');
            nextOption.value = nextReportingPeriod;
            nextOption.textContent = nextReportingPeriod;
            reportingPeriodDropdown.appendChild(nextOption);

            // Set the dropdown to the next reporting period by default
            reportingPeriodDropdown.value = nextReportingPeriod;
            // Clear the notes field initially
            document.getElementById('notes').value = '';

            // Add event listener to populate notes based on selected period
            reportingPeriodDropdown.addEventListener('change', function() {
                const selectedPeriod = this.value;
                const report = data.find(report => report.reporting_period == selectedPeriod);
                document.getElementById('notes').value = report ? report.notes : '';
            });
        })
        .catch(error => {
            console.error('Error fetching reports:', error);
        });
}

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