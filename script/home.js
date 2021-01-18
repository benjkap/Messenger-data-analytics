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

    let messagesButton1 = document.getElementById('messagesButton1');
    let charsButton1 = document.getElementById('charsButton1');
    let sortButton = document.getElementById('sortButton');

    messagesButton1.addEventListener('click', function () {
        chart.config.data = generateData('messages');
        chart.config.options.title.text = 'Nombre de messages';
        chart.update();
        messagesButton1.disabled = true;
        charsButton1.disabled = false;
    });

    charsButton1.addEventListener('click', function () {
        chart.config.data = generateData('caractères');
        chart.config.options.title.text = 'Nombre de caractères';
        chart.update();
        messagesButton1.disabled = false;
        charsButton1.disabled = true;
    });

    let convsPages = document.getElementsByClassName('convs-pages');

    function tri(htmlCollection, f){
        function swap(node1, node2) {
            const afterNode2 = node2.nextElementSibling;
            const parent = node2.parentNode;
            node1.replaceWith(node2);
            parent.insertBefore(node1, afterNode2);
        }
        for(let i= 0 ; i< htmlCollection.length; i++){
            for(let j=i+1; j< htmlCollection.length; j++){
                if(f(htmlCollection[j], htmlCollection[i])) swap(htmlCollection[i], htmlCollection[j]);
            }
        }
    }

    for (let convs of convsPages) tri(convs.children,function(a,b) {
        return parseInt(a.getAttribute('data_msg')) > parseInt(b.getAttribute('data_msg'));
    });

    sortButton.addEventListener('click', function () {
        if (this.getAttribute('data') === 'messages'){
            this.setAttribute('data', 'chars');
            this.children.innerText.innerText = 'Par caractères';
            for (let convs of convsPages) tri(convs.children,function(a,b) {
                return parseInt(a.getAttribute('data_char')) > parseInt(b.getAttribute('data_char'));
            });
        } else if (this.getAttribute('data') === 'chars') {
            this.setAttribute('data', 'messages');
            this.children.innerText.innerText = 'Par messages';
            for (let convs of convsPages) tri(convs.children,function(a,b) {
                return parseInt(a.getAttribute('data_msg')) > parseInt(b.getAttribute('data_msg'));
            });
        }
    });

    moment.locale('fr-FR');
    const ctx = document.getElementById('myChart').getContext('2d');

    function generateData(dataStr = 'messages') {

        let jsonData = [];
        jsonData['labels'] = JSON.parse(document.getElementById('labels').innerHTML);
        jsonData['data'] = JSON.parse(document.getElementById(dataStr).innerHTML);

        let items = [];
        for (let i = 0; i < jsonData['data'].length; i++) {
            items.push({
                label: jsonData['labels'][i],
                value: jsonData['data'][i]
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
                label: 'Nombre de ' + dataStr,
                backgroundColor: (dataStr === 'messages')?window.chartColors.blue:window.chartColors.orange,
                borderColor: (dataStr === 'messages')?window.chartColors.blue:window.chartColors.orange,
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

    let chart = new Chart(ctx, cfg);

});

