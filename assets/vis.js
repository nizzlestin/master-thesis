/*
 * Welcome to your app's main JavaScript file!
 *
 * We recommend including the built version of this JavaScript file
 * (and its CSS file) in your base layout (base.html.twig).
 */

// any CSS you import will output into a single css file (app.css in this case)
import './styles/app.scss';

var $ = require("jquery");

import {AnotherLineChart} from "./js/AnotherLineChart";
import * as d3 from 'd3';

const parseTime = d3.timeParse("%d/%m/%Y");
const formatTime = d3.timeFormat("%d/%m/%Y");
let loc;
let comment;
let blanks;
let locvscomments;
let complexity;

function updateCharts() {
    loc.wrangle();
    comment.wrangle();
    blanks.wrangle();
    locvscomments.wrangle();
    complexity.wrangle();
}

$(document).ready(() => {
    // $("#date-slider").slider({
    //     range: true,
    //     max: parseTime("31/10/2017").getTime(),
    //     min: parseTime("12/5/2013").getTime(),
    //     step: 86400000, // one day
    //     values: [
    //         parseTime("12/5/2013").getTime(),
    //         parseTime("31/10/2017").getTime()
    //     ],
    //     slide: (event, ui) => {
    //         $("#dateLabel1").text(formatTime(new Date(ui.values[0])))
    //         $("#dateLabel2").text(formatTime(new Date(ui.values[1])))
    //         updateCharts()
    //     }
    // })
    
    const asset = $('[data-asset-url]').data('asset-url');
    d3.json(asset).then((data) => {
        data.forEach(d => {
            d.date = parseTime(d.date)
            d.ltocratio = d.Lines/(d.Lines+d.Blank+d.Comment)
        })
        const config = {'metric': 'Code'};

        loc = new AnotherLineChart("#loc-container", data, config);
        comment = new AnotherLineChart("#comments-container", data, {'metric': 'Comment'});
        // blanks = new AnotherLineChart("#blanks-container", data, config);
        // locvscomments = new AnotherLineChart("#ratio-container", data, config);
        // complexity = new AnotherLineChart("#complexity-container", data, config);
    })
})
