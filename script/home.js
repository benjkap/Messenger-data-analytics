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
    let meSwitch = document.getElementById('meSwitch1');
    let otherSwitch = document.getElementById('otherSwitch1');

    messagesButton1.addEventListener('click', function () {
        messagesButton1.disabled = true;
        charsButton1.disabled = false;
        chart.config.options.title.text = 'Nombre de messages';
        dataSetFromSwitch();
    });

    charsButton1.addEventListener('click', function () {
        messagesButton1.disabled = false;
        charsButton1.disabled = true;
        chart.config.options.title.text = 'Nombre de caractères';
        dataSetFromSwitch();
    });

    meSwitch.addEventListener('change', dataSetFromSwitch);
    otherSwitch.addEventListener('change', dataSetFromSwitch);

    function dataSetFromSwitch() {

        let value = {
            me: meSwitch.checked,
            other: otherSwitch.checked,
            messages: (messagesButton1.disabled)?'messages':'caractères'
        };

        if (value.me && value.other) chart.config.data = generateData(value.messages, 'All');
        if (value.me && !value.other) chart.config.data = generateData(value.messages, 'Me');
        if (!value.me && value.other) chart.config.data = generateData(value.messages, 'Other');
        if (!value.me && !value.other) chart.config.data = generateData(value.messages, 'No');
        chart.update();

        if (value.me && value.other) setGlobalStats(value.messages, 'All');
        if (value.me && !value.other) setGlobalStats(value.messages, 'Me');
        if (!value.me && value.other) setGlobalStats(value.messages, 'Other');
        if (!value.me && !value.other) setGlobalStats(value.messages, 'No');

        if (value.me && value.other) setConvsStats(value.messages, 'All');
        if (value.me && !value.other) setConvsStats(value.messages, 'Me');
        if (!value.me && value.other) setConvsStats(value.messages, 'Other');
        if (!value.me && !value.other) setConvsStats(value.messages, 'No');
        sortButton.dispatchEvent(new Event("click"));
        sortButton.dispatchEvent(new Event("click"));

    }

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

    function setGlobalStats(dataStr = 'messages', dataSet = 'All') {

        let jsonData = [];
        jsonData['labels'] = JSON.parse(document.getElementById('labels').innerHTML);
        jsonData['messages'] = (dataSet !== 'No')?JSON.parse(document.getElementById('messages' + dataSet).innerHTML):[];
        jsonData['caracteres'] = (dataSet !== 'No')?JSON.parse(document.getElementById('caractères' + dataSet).innerHTML):[];

        document.getElementById('nbConv').innerText = jsonData['labels'].length;

        let nbMessages = 0;
        for (let m of jsonData['messages']) nbMessages += m;
        document.getElementById('nbMessages').innerText = nbMessages.toLocaleString();

        let nbChar = 0;
        for (let c of jsonData['caracteres']) nbChar += c;
        document.getElementById('nbChar').innerText = nbChar.toLocaleString();

    }

    function setConvsStats(dataStr = 'messages', dataSet = 'All') {

        let jsonData = [];
        jsonData['messages'] = (dataSet !== 'No')?JSON.parse(document.getElementById('messages' + dataSet).innerHTML):[];
        jsonData['caracteres'] = (dataSet !== 'No')?JSON.parse(document.getElementById('caractères' + dataSet).innerHTML):[];

        for (let conversion of document.getElementsByClassName('conversation')) {
            let key = conversion.getAttribute('data_id');
            conversion.setAttribute('data_msg', (dataSet !== 'No')?jsonData['messages'][key]:0);
            conversion.setAttribute('data_char', (dataSet !== 'No')?jsonData['caracteres'][key]:0);

            conversion.getElementsByClassName('msg')[0].innerText = (dataSet !== 'No')?jsonData['messages'][key].toLocaleString():0;
            conversion.getElementsByClassName('char')[0].innerText = (dataSet !== 'No')?jsonData['caracteres'][key].toLocaleString():0;
        }

    }

    function generateData(dataStr = 'messages', dataSet = 'All') {

        let jsonData = [];
        jsonData['labels'] = JSON.parse(document.getElementById('labels').innerHTML);
        jsonData['data'] = (dataSet !== 'No')?JSON.parse(document.getElementById(dataStr + dataSet).innerHTML):[];

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

    console.log('init');

    setGlobalStats();
    setConvsStats();

    let chart = new Chart(ctx, cfg);

    for (let convs of convsPages) tri(convs.children,function(a,b) {
        return parseInt(a.getAttribute('data_msg')) > parseInt(b.getAttribute('data_msg'));
    });

});

