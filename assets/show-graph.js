/*
 * Welcome to your app's main JavaScript file!
 *
 * We recommend including the built version of this JavaScript file
 * (and its CSS file) in your base layout (base.html.twig).
 */

// any CSS you import will output into a single css file (app.css in this case)
import './styles/app.scss';

var $ = require("jquery");

import {D3LineChart} from "./js/D3LineChart";
import * as d3 from 'd3';

const parseTime = d3.timeParse("%d/%m/%Y")
const formatTime = d3.timeFormat("%d/%m/%Y")

$(document).ready(() => {
    const asset = $('[data-asset-url]').data('asset-url');
    d3.json(asset).then((data) => {
        data.forEach(d => {
            d.date = parseTime(d.date)
            d.ltocratio = d.Lines/(d.Lines+d.Blank+d.Comment)
        })
        var loc = D3LineChart(data, {
            x: d => d.date,
            y: d => d.Lines,
            z: d => d.language,
            yLabel: "lines of code",
            width: 700,
            height: 500,
            color: "steelblue",
            marginLeft: 80,
            voronoi: false // if true, show Voronoi overlay
        })
        // document.body.append(loc);
        var comment = D3LineChart(data, {
            x: d => d.date,
            y: d => d.Comment,
            z: d => d.language,
            yLabel: "commented lines",
            width: 700,
            height: 500,
            color: "steelblue",
            marginLeft: 80,
            voronoi: false // if true, show Voronoi overlay
        })
        // document.body.append(comment);

        var blanks = D3LineChart(data, {
            x: d => d.date,
            y: d => d.Blank,
            z: d => d.language,
            yLabel: "blank lines",
            width: 700,
            height: 500,
            color: "steelblue",
            marginLeft: 80,
            voronoi: false // if true, show Voronoi overlay
        })
        // document.body.append(blanks);

        var locvscomments = D3LineChart(data, {
            x: d => d.date,
            y: d => d.ltocratio,
            z: d => d.language,
            yLabel: "Code Lines/All lines Ratio",
            yDomain: [0,1],
            width: 700,
            height: 500,
            color: "steelblue",
            marginLeft: 80,
            voronoi: false // if true, show Voronoi overlay
        })
        // document.body.append(locvscomments);

        var complexity = D3LineChart(data, {
            x: d => d.date,
            y: d => d.Complexity,
            z: d => d.language,
            yLabel: "Complexity",
            yDomain: [0,1],
            width: 700,
            height: 500,
            color: "steelblue",
            marginLeft: 80,
            voronoi: false // if true, show Voronoi overlay
        })
        // document.body.append(complexity);
        document.getElementById("loc-container").append(loc)
        document.getElementById("comments-container").append(comment)
        document.getElementById("blanks-container").append(blanks)
        document.getElementById("ratio-container").append(locvscomments)
        document.getElementById("complexity-container").append(complexity)

    })
})
