'use strict';

window.chartColors = {
    red: 'rgb(255, 99, 132)',
    orange: 'rgb(255, 159, 64)',
    yellow: 'rgb(255, 205, 86)',
    green: 'rgb(75, 192, 192)',
    blue: 'rgb(54, 162, 235)',
    purple: 'rgb(153, 102, 255)',
    grey: 'rgb(201, 203, 207)'
};



document.addEventListener("DOMContentLoaded", function(){

    moment.locale('fr-FR');
    const ctx = document.getElementById('myChart').getContext('2d');

    function generateData() {

        let data = [];
        let jsonData = JSON.parse(document.getElementById('messagePerDays').innerHTML);

        for(let index in jsonData) {
            if(jsonData.hasOwnProperty(index)) data.push({
                t: parseInt(index),
                y: jsonData[index].toString()
            });
        }

        return data;
    }

    const color = Chart.helpers.color;
    const cfg = {
        data: {
            datasets: [{
                label: 'Nombre de messages envoyés par jour',
                backgroundColor: window.chartColors.red,
                borderColor: window.chartColors.red,
                data: generateData(),
                type: 'bar',
                pointRadius: 0,
                fill: false,
                lineTension: 0,
                borderWidth: 2
            }]
        },
        options: {
            animation: {
                duration: 0
            },
            scales: {
                xAxes: [{
                    type: 'time',
                    distribution: 'series',
                    offset: true,
                    time: {
                        unit: 'day',
                        tooltipFormat: 'D MMM YYYY',
                        displayFormats: {
                            day: 'D MMM'
                        },
                        distribution: 'series'
                    },
                    ticks: {
                        major: {
                            enabled: true,
                            fontStyle: 'bold'
                        },
                        source: 'data',
                        autoSkip: true,
                        autoSkipPadding: 75,
                        maxRotation: 0,
                        sampleSize: 100
                    },
                }],
                yAxes: [{
                    gridLines: {
                        drawBorder: false
                    },
                    scaleLabel: {
                        display: true,
                        labelString: 'Nombre de messages'
                    }
                }]
            },
            tooltips: {
                intersect: false,
                mode: 'index',
                callbacks: {
                    label: function (tooltipItem, myData) {
                        let label = 'Nombre de messages: ';
                        label += parseInt(tooltipItem.value);
                        return label;
                    }
                },
                filter: function (tooltipItem, data) {
                    return tooltipItem.yLabel !== 0;
                },
            }
        }
    };

    const chart = new Chart(ctx, cfg);

});

