/*
 * Welcome to your app's main JavaScript file!
 *
 * We recommend including the built version of this JavaScript file
 * (and its CSS file) in your base layout (base.html.twig).
 */

// any CSS you import will output into a single css file (app.css in this case)
import './styles/app.scss';
// require('jquery-ui/ui/widgets/droppable');
// require('jquery-ui/ui/widgets/sortable');
// require('jquery-ui/ui/widgets/selectable');

// var $ = require("jquery");
import $ from 'jquery';

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

    const asset = $('[data-asset-url]').data('asset-url');
    d3.json(asset).then((data) => {
        data.forEach(d => {
            d.date = parseTime(d.date)
            d.ltocratio = d.Lines/(d.Lines+d.Blank+d.Comment)
        })
        const config = {'metric': 'Code'};

        loc = new AnotherLineChart("#loc-container", data, config);
        comment = new AnotherLineChart("#comments-container", data, {'metric': 'Comment'});
        blanks = new AnotherLineChart("#blanks-container", data, {'metric': 'Blank'});
        locvscomments = new AnotherLineChart("#ratio-container", data, {'metric': 'ltocratio'});
        complexity = new AnotherLineChart("#complexity-container", data, {'metric': 'Complexity'});

    })


})
