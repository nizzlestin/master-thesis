import * as d3 from 'd3';

require('webpack-jquery-ui/slider');
require('webpack-jquery-ui/css');
import $ from "jquery";

const parseTime = d3.timeParse("%d/%m/%Y");
const formatTime = d3.timeFormat("%d/%m/%Y");

export class SmallMultiples {
    glines
    mouseG
    tooltip
    margin = {top: 30, right: 10, bottom: 40, left: 50};
    width = 310 - this.margin.left - this.margin.right;
    height = 310 - this.margin.top - this.margin.bottom;
    lineStroke = "2px"
    axisPad = 3 // axis formatting
    R = 3 //legend marker

    constructor(_parentElement, _data, _config) {
        this.data = _data;
        this.config = _config;
        this.parentElement = _parentElement;
        this.languageKeys = new Set(this.data.map(d => d.language));
        this.metricKeys = new Set(this.data.map(d => d.metric));
        this.color = d3.scaleOrdinal(d3.schemeCategory10).domain(this.languageKeys);
        this.#init();
    }

    update(metric) {
        const vis = this;
        vis.dataByMetric = vis.data.filter(d => d.metric === metric)
        const sumstat = d3.group(vis.dataByMetric, d => d.language);
        const x = d3.scaleLinear()
            .domain(d3.extent(vis.dataByMetric, function (d) {
                return d.date;
            }))
            .range([0, vis.width]);
        const y = d3.scaleLinear()
            .domain([0, d3.max(vis.dataByMetric, function (d) {
                return +d.value;
            })])
            .range([vis.height, 0]);
        // vis.svg.selectAll(".x.axis")
        //     .call()
        vis.svg.selectAll(".y.axis")
            .transition().duration(750)
            .call(d3.axisLeft(y).ticks(5, 's'));
        vis.svg.select('.line')
            .data(sumstat)
            .transition().duration(750)
            .attr("d", function (d) {
                return d3.line()
                    .x(function (d) {
                        return x(d.date);
                    })
                    .y(function (d) {
                        return y(+d.value);
                    })
                    (d[1])
            })
    }
    #init() {
        const vis = this;
        vis.dataByMetric = vis.data.filter(d => d.metric === vis.config.metric)
        const sumstat = d3.group(vis.dataByMetric, d => d.language);
        // Add an svg element for each group. The will be one beside each other and will go on the next row when no more room available
        vis.svg = d3.select(vis.parentElement)
            .selectAll("uniqueChart")
            .data(sumstat)
            .enter()
            .append("svg")
            .attr("width", vis.width + vis.margin.left + vis.margin.right)
            .attr("height", vis.height + vis.margin.top + vis.margin.bottom)
            .append("g")
            .attr("transform",
                `translate(${vis.margin.left},${vis.margin.top})`);

        // Add X axis --> it is a date format
        const x = d3.scaleLinear()
            .domain(d3.extent(vis.dataByMetric, function (d) {
                return d.date;
            }))
            .range([0, vis.width]);
        // svg
        //     .append("g")
        //     .attr("transform", `translate(0, ${vis.height})`)
        //     .call(d3.axisBottom(x).ticks(3));

        vis.svg
            .append("g")
            .attr("class", "x axis")
            .attr("transform", `translate(0, ${vis.height})`)
            .call(d3.axisBottom(d3.scaleTime()
                .domain(d3.extent(vis.dataByMetric, d => d.date))
                .range([0, vis.width])))
            .selectAll("text")
            .style("text-anchor", "end")
            .attr("dx", "-.8em")
            .attr("dy", ".15em")
            .attr("transform", "rotate(-55)");

        //Add Y axis
        const y = d3.scaleLinear()
            .domain([0, d3.max(vis.dataByMetric, function (d) {
                return +d.value;
            })])
            .range([vis.height, 0]);
        vis.svg.append("g")
            .attr("class", "y axis")
            .call(d3.axisLeft(y).ticks(5, 's'));

        // Draw the line
        vis.svg
            .append("path")
            .attr("fill", "none")
            .attr("class", "line")
            .attr("stroke", function (d) {
                return vis.color(d[0])
            })
            .attr("stroke-width", 1.9)
            .attr("d", function (d) {
                return d3.line()
                    .x(function (d) {
                        return x(d.date);
                    })
                    .y(function (d) {
                        return y(+d.value);
                    })
                    (d[1])
            })

        // Add titles
        vis.svg
            .append("text")
            .attr("text-anchor", "start")
            .attr("y", -5)
            .attr("x", 0)
            .text(function (d) {
                return (d[0])
            })
            .style("fill", function (d) {
                return vis.color(d[0])
            })

        d3.selectAll(("input[name='metric_radio']")).on('change', function(){
            vis.update(this.value)
        })
    }

    tooltip() {
        const vis = this;
        vis.tooltip = d3.select(vis.parentElement).append("div")
            .attr('id', 'tooltip')
            .style('position', 'absolute')
            .style("background-color", "#D3D3D3")
            .style('padding', 6)
            .style('display', 'none')

        vis.mouseG = vis.svg.append("g")
            .attr("class", "mouse-over-effects");

        vis.mouseG.append("path") // create vertical line to follow mouse
            .attr("class", "mouse-line")
            .style("stroke", "#A9A9A9")
            .style("stroke-width", vis.lineStroke)
            .style("opacity", "0");


        vis.mousePerLine = vis.mouseG.selectAll('.mouse-per-line')
            .data(vis.res_nested)
            .enter()
            .append("g")
            .attr("class", "mouse-per-line");

        vis.mousePerLine.append("circle")
            .attr("r", 4)
            .style("stroke", function (d) {
                return vis.color(d.key)
            })
            .style("fill", "none")
            .style("stroke-width", vis.lineStroke)
            .style("opacity", "0");

        vis.mouseG.append('svg:rect')
            .attr('width', vis.width)
            .attr('height', vis.height)
            .attr('fill', 'none')
            .attr('pointer-events', 'all')
            .on('mouseout', function () {
                d3.select(".mouse-line")
                    .style("opacity", "0");
                d3.selectAll(".mouse-per-line circle")
                    .style("opacity", "0");
                d3.selectAll(".mouse-per-line text")
                    .style("opacity", "0");
                d3.selectAll("#tooltip")
                    .style('display', 'none')

            })
            .on('mouseover', function () {
                d3.select(".mouse-line")
                    .style("opacity", "1");
                d3.selectAll(".mouse-per-line circle")
                    .style("opacity", "1");
                d3.selectAll("#tooltip")
                    .style('display', 'block')
            })
            .on('mousemove', function (event) {
                var mouse = d3.pointer(event)

                d3.selectAll(".mouse-per-line")
                    .attr("transform", function (d, i) {
                        const xDate = vis.xScale.invert(mouse[0]); // use 'invert' to get date corresponding to distance from mouse position relative to svg
                        const bisect = d3.bisector(function (d) {
                            return d.date;
                        }).left;
                        const idx = bisect(d[1], xDate);

                        d3.select(".mouse-line")
                            .attr("d", function () {
                                let data = "M" + vis.xScale(d[1][idx].date) + "," + (vis.height);
                                data += " " + vis.xScale(d[1][idx].date) + "," + 0;
                                return data;
                            });
                        return "translate(" + vis.xScale(d[1][idx].date) + "," + vis.yScale(d[1][idx].value) + ")";

                    });

                vis.updateTooltipContent(event, mouse, vis.res_nested)
            })
    }
}
