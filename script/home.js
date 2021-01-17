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

        let jsonData = [];
        jsonData['labels'] = JSON.parse(document.getElementById('labels').innerHTML);
        jsonData['messages'] = JSON.parse(document.getElementById('messages').innerHTML);

        let items = [];
        for (let i = 0; i < jsonData['messages'].length; i++) {
            items.push({
                label: jsonData['labels'][i],
                value: jsonData['messages'][i]
            });
        }

        items.sort(function (a, b) {
            return b.value - a.value;
        });

        let labels = [];
        let data = [];
        for (let item of items) {
            labels.push(item.label);
            data.push(item.value);
        }

        return {
            labels: labels,
            datasets: [{
                label: 'Nombre de messages',
                backgroundColor: window.chartColors.blue,
                borderColor: window.chartColors.blue,
                borderWidth: 1,
                data: data
            }]

        };
    }

    Chart.scaleService.updateScaleDefaults('category', {
        ticks: {
            callback: function (tick) {
                if (tick.length > 25) return tick.substring(0, 25) + '...';
                else return tick;
            }
        }
    });

    const cfg = {
        type: 'horizontalBar',
        data: generateData(),
        options: {
            scaleShowValues: true,
            scales: {
                yAxes: [{
                    ticks: {
                        autoSkip: false,
                    }
                }],
                xAxes: [{
                    ticks: {
                        display: false
                    }
                }],
            },
            elements: {
                rectangle: {
                    borderWidth: 10,
                }
            },
            responsive: true,
            legend: {
                display: false,
            },
            title: {
                display: true,
                text: 'Nombre de messages'
            },
            tooltips: {
                callbacks: {
                    title: function (tooltipItems, data) {
                        return data.labels[tooltipItems[0].index]
                    }
                }
            }
        }
    };

    const chart = new Chart(ctx, cfg);

});

