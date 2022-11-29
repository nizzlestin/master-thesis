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
import * as d3 from 'd3';


$(document).ready(() => {
    const asset = $('[data-asset-url]').data('asset-url');
    var currentCircle = null
    // set the dimensions and margins of the graph
    const margin = {top: 10, right: 30, bottom: 40, left: 40},
        width = 470 - margin.left - margin.right,
        height = 460 - margin.top - margin.bottom;

    // append the svg object to the body of the page
    const svg = d3.select("#biplot")
        .append("svg")
        .attr("width", width + margin.left + margin.right)
        .attr("height", height + margin.top + margin.bottom)
        .append("g")
        .attr("transform",
            `translate(${margin.left}, ${margin.top})`);
    const svg2 = d3.select("#small-multiples")
        .append("svg")
        .attr("width", width + margin.left + margin.right)
        .attr("height", height + margin.top + margin.bottom)
        .append("g")
        .attr("transform",
            `translate(${margin.left}, ${margin.top})`);

//Read the data
    d3.json(asset).then(function (data) {
        // Add X axis
        const x = d3.scaleLinear()
            .domain([0, 1])
            .range([0, width]);
        svg.append("g")
            .attr("transform", `translate(0, ${height})`)
            .call(d3.axisBottom(x));

        // Add Y axis
        const y = d3.scaleLinear()
            .domain([0, 1])
            .range([height, 0]);
        svg.append("g")
            .call(d3.axisLeft(y));

        // Add a tooltip div. Here I define the general feature of the tooltip: stuff that do not depend on the data point.
        // Its opacity is set to 0: we don't see it by default.
        const tooltip = d3.select("#biplot")
            .append("div")
            .style("opacity", 0)
            .attr("class", "tooltip")
            .style("background-color", "white")
            .style("border", "solid")
            .style("border-width", "1px")
            .style("border-radius", "5px")
            .style("padding", "10px")
        svg.append("text")
            .attr("class", "x label")
            .attr("text-anchor", "end")
            .attr("x", width)
            .attr("y", height - 6)
            .text("Normalized Complexity");

        svg.append("text")
            .attr("class", "y label")
            .attr("text-anchor", "end")
            .attr("y", 6)
            .attr("dy", ".75em")
            .attr("transform", "rotate(-90)")
            .text("Normalized Churn");
        // A function that change this tooltip when the user hover a point.
        // Its opacity is set to 1: we can now see it. Plus it set the text and position of tooltip depending on the datapoint (d)
        const mouseover = function (event, dx) {
            d3.json(`https://127.0.0.1:8000/metrics/single-file/${dx.fid}`).then(
                function (data) {
                    svg2.selectAll("*").remove()
                    var $h5 = $('#timeline-h5');
                    $h5.text(`${dx.full}`)
                    $h5.append(`<p class="js-p">average growth rate: ${data.growth}%</p>`)
                    data = data['data'].map(d => {
                        return {date: d3.timeParse("%Y-%m-%d")(d.commit_date), value: d.code}
                    })

                    // Add X axis --> it is a date format
                    const x = d3.scaleTime()
                        .domain(d3.extent(data, function (d) {
                            return d.date;
                        }))
                        .range([0, width]);
                    svg2.append("g")
                        .attr("transform", `translate(0, ${height})`)
                        .call(d3.axisBottom(x))
                        .selectAll("text")
                        .style("text-anchor", "end")
                        .attr("dx", "-.8em")
                        .attr("dy", ".15em")
                        .attr("transform", "rotate(-55)");

                    // Add Y axis
                    const y = d3.scaleLinear()
                        .domain([0, d3.max(data, function (d) {
                            return +d.value;
                        })])
                        .range([height, 0]);
                    svg2.append("g")
                        .call(d3.axisLeft(y));
                    svg2.append("text")
                        .attr("class", "y label")
                        .attr("text-anchor", "end")
                        .attr("y", 6)
                        .attr("dy", ".75em")
                        .attr("transform", "rotate(-90)")
                        .text("SLOC");

                    svg2.append("text")
                        .attr("class", "x label")
                        .attr("text-anchor", "end")
                        .attr("x", width)
                        .attr("y", height - 6)
                        .text("Date");
                    // Add the line
                    svg2.append("path")
                        .datum(data)
                        .attr("fill", "none")
                        .attr("stroke", "steelblue")
                        .attr("stroke-width", 1.5)
                        .attr("d", d3.line()
                            .x(function (d) {
                                return x(d.date)
                            })
                            .y(function (d) {
                                return y(d.value)
                            })
                        )
                })


            currentCircle = d3.select(this)
            currentCircle.attr("r", 10).style("fill", "red");
            tooltip
                .style("opacity", 1)
        }

        const mousemove = function (event, d) {
            tooltip
                .html(`filename: ${d.full}; churn: ${d.y}; comp: ${d.x}`)
                .style("left", (event.x) + "px")// It is important to put the +90: other wise the tooltip is exactly where the point is an it creates a weird effect
                .style("top", (event.y) + "px")
        }

        // A function that change this tooltip when the leaves a point: just need to set opacity to 0 again
        const mouseleave = function (event, d) {
            currentCircle.attr("r", 7).style("fill", "#69b3a2")
            tooltip
                .transition()
                .duration(200)
                .style("opacity", 0)
        }

        // Add dots
        svg.append('g')
            .selectAll("dot")
            .data(data) // the .filter part is just to keep a few dots on the chart, not all of them
            .enter()
            .append("circle")
            .attr("cx", function (d) {
                return x(d.x);
            })
            .attr("cy", function (d) {
                return y(d.y);
            })
            .attr("r", 7)
            .style("fill", "#69b3a2")
            .style("opacity", 0.3)
            .style("stroke", "white")
            .on("mouseover", mouseover)
            .on("mousemove", mousemove)
            .on("mouseleave", mouseleave)

    })

})
