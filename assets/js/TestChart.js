import * as d3 from 'd3';

require('webpack-jquery-ui/slider');
require('webpack-jquery-ui/css');
import $ from "jquery";

const parseTime = d3.timeParse("%d/%m/%Y");
const formatTime = d3.timeFormat("%d/%m/%Y");

export class TestChart {
    glines
    mouseG
    tooltip
    margin = {top: 80, right: 200, bottom: 40, left: 80}
    width = 1400 - this.margin.left - this.margin.right
    height = 500 - this.margin.top - this.margin.bottom
    lineStroke = "2px"
    axisPad = 6 // axis formatting
    R = 6 //legend marker

    constructor(_parentElement, _data, _config) {
        this.res = _data;
        this.parentElement = _parentElement;
        this.category = [...new Set(this.res.map(d => d.language))];
        this.color = d3.scaleOrdinal(d3.schemeCategory10).domain(this.category)
        this.#init();
    }


    renderChart(metric) {
        const vis = this;
        vis.resNew = vis.res.filter(d => d.metric === metric)
        vis.res_nested = d3.group(vis.resNew, d => d.language)
        // APPEND MULTIPLE LINES //
        vis.lines = vis.svg.append('g')
            .attr('class', 'lines')

        vis.glines = vis.lines.selectAll("path")
            .data(vis.res_nested)
            .join("path")
            .attr('fill', 'none')
            .attr('stroke-width', vis.lineStroke)
            .attr('class', 'line')
            .attr('stroke', (d) => {
                return vis.color(d[0])
            })
            .attr("d", d => {
                return d3.line()
                    .x(d => vis.xScale(d.date))
                    .y(d => vis.yScale(d.value))
                    (d[1])
            });

        // CREATE HOVER TOOLTIP WITH VERTICAL LINE //
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

    updateChart(metric, sliderUpdate = false) {
        const vis = this;
        vis.resNew = vis.res.filter(d => d.metric === metric)
        if (sliderUpdate) {
            const sliderValues = $(vis.slider.sliderId).slider("values");
            vis.resNew = vis.resNew.filter(d => {
                return ((d.date.getTime() >= sliderValues[0]) && (d.date.getTime() <= sliderValues[1]))
            })
            vis.xScale.domain(d3.extent(vis.resNew, d => d.date))


            vis.yScale.domain([0, d3.max(vis.resNew, d => d.value)]);

            vis.xAxis = d3.axisBottom(vis.xScale).ticks(d3.timeYear.every(1)).tickSizeOuter(vis.axisPad * 2).tickSizeInner(vis.axisPad * 2)
            vis.yAxis = d3.axisLeft(vis.yScale).ticks(10, "s").tickSize(-vis.width) //horizontal ticks across svg width
            vis.xAxisCall
                .transition(d3.transition().duration(750))
                .call(vis.xAxis);

            vis.yAxisCall
                .transition(d3.transition().duration(750))
                .call(vis.yAxis);
        }

        vis.res_nested = d3.group(vis.resNew, d => d.language)

        vis.glines.select('.line') //select line path within line-group (which represents a language category), then bind new data
            .data(vis.res_nested)
            .transition().duration(750)
            .attr('d', function (d) {
                return d3.line()
                    .x(d => vis.xScale(d.date))
                    .y(d => vis.yScale(d.value))
                    (d[1])
            })

        vis.mouseG.selectAll('.mouse-per-line')
            .data(vis.res_nested)

        vis.mouseG.on('mousemove', function (event) {
            var mouse = d3.pointer(event)
            vis.updateTooltipContent(event, mouse, vis.res_nested)
        })
    }

    updateTooltipContent(event, mouse, res_nested) {
        const vis = this;
        vis.sortingObj = []
        d3.map(res_nested, d => {
            var xDate = vis.xScale.invert(mouse[0])
            var bisect = d3.bisector(function (d) {
                return d.date;
            }).left
            var idx = bisect(d[1], xDate)
            vis.sortingObj.push({
                key: d[1][idx].language,
                value: d[1][idx].value,
                metric: d[1][idx].metric,
                hash: d[1][idx].hash,
                year: d[1][idx].date.getFullYear()
            })
        })

        vis.sortingObj.sort(function (x, y) {
            return d3.descending(x.value, y.value);
        })


        vis.sortingArr = vis.sortingObj.map(d => d.key)

        vis.res_nested1 = d3.groups(res_nested, d => d.key)
            .sort((a, b) => vis.sortingArr.indexOf(a[0]) - vis.sortingArr.indexOf(b[0])) // rank vehicle category based on price of value)
        vis.res_nested1 = vis.res_nested1[0][1]
        vis.tooltip.html("COMMIT:" + vis.sortingObj[0].hash.substr(0, 6) + "; METRIC:" + vis.sortingObj[0].metric)
            .style('display', 'block')
            .style('left', `${event.pageX + 20}px`)
            .style('top', `${event.pageY - 200}px`)
            .style('font-size', 8)
            .selectAll()
            .data(vis.res_nested1).enter() // for each language, list out name and price of value
            .append('div')
            .style('color', d => {
                return vis.color(d[0])
            })
            .style('font-size', 10)
            .html(d => {
                var xDate = vis.xScale.invert(mouse[0])

                var bisect = d3.bisector(function (d) {
                    return d.date;
                }).left

                var idx = bisect(d[1], xDate)
                return d[0] + " : " + d[1][idx].value.toString()
            })
    }

    #init() {
        const vis = this;
        // initialises xScale, yScale, svg, xAxis, yAxis, legend, svgLegend, line
        vis.xScale = d3.scaleTime()
            .domain(d3.extent(vis.res, d => d.date))
            .range([0, vis.width])


        function roundToNearest10K(x) {
            return Math.round(x / 10000) * 10000
        }

        vis.yScale = d3.scaleLinear()
            .domain([0, roundToNearest10K(d3.max(vis.res, d => d.value))])
            .range([vis.height, 0]);
        vis.min = d3.min(vis.res, (d) => d.date)
        vis.max = d3.max(vis.res, (d) => d.date)
        vis.svg = d3.select(vis.parentElement).append("svg")
            .attr("width", vis.width + vis.margin.left + vis.margin.right)
            .attr("height", vis.height + vis.margin.top + vis.margin.bottom)
            .append('g')
            .attr("transform", "translate(" + vis.margin.left + "," + vis.margin.top + ")");


        // CREATE AXES //
        // render axis first before lines so that lines will overlay the horizontal ticks
        vis.xAxis = d3.axisBottom(vis.xScale).ticks(d3.timeYear.every(1)).tickSizeOuter(vis.axisPad * 2).tickSizeInner(vis.axisPad * 2)
        vis.yAxis = d3.axisLeft(vis.yScale).ticks(10, "s").tickSize(-vis.width) //horizontal ticks across svg width

        vis.xAxisCall = vis.svg.append("g")
            .attr("class", "x axis")
            .attr("transform", `translate(0, ${vis.height})`)
            .call(vis.xAxis);
        vis.xAxisCall.call(g => {
            var years = vis.xScale.ticks(d3.timeYear.every(1))
            var xshift = (vis.width / (years.length)) / 2
            g.selectAll("text").attr("transform", `translate(${xshift}, 0)`) //shift tick labels to middle of interval
                .style("text-anchor", "middle")
                .attr("y", vis.axisPad)
                .attr('fill', '#A9A9A9')

            g.selectAll("line")
                .attr('stroke', '#A9A9A9')

            g.select(".domain")
                .attr('stroke', '#A9A9A9')

        })

        vis.yAxisCall = vis.svg.append("g")
            .attr("class", "y axis")
            .call(vis.yAxis);
        vis.yAxisCall.call(g => {
            g.selectAll("text")
                .style("text-anchor", "middle")
                .attr("x", -vis.axisPad * 2)
                .attr('fill', '#A9A9A9')

            g.selectAll("line")
                .attr('stroke', '#A9A9A9')
                .attr('stroke-width', 0.7) // make horizontal tick thinner and lighter so that line paths can stand out
                .attr('opacity', 0.3)

            g.select(".domain").remove()

        })
            .append('text')
            .attr('x', 50)
            .attr("y", -10)
            .attr("fill", "#A9A9A9")
            .text('code metric')


        // CREATE LEGEND //
        vis.svgLegend = vis.svg.append('g')
            .attr('class', 'gLegend')
            .attr("transform", "translate(" + (vis.width + 20) + "," + 0 + ")")

        vis.legend = vis.svgLegend.selectAll('.legend')
            .data(vis.category)
            .enter().append('g')
            .attr("class", "legend")
            .attr("transform", function (d, i) {
                return "translate(0," + i * 20 + ")"
            })

        vis.legend.append("circle")
            .attr("class", "legend-node")
            .attr("cx", 0)
            .attr("cy", 0)
            .attr("r", vis.R)
            .style("fill", d => vis.color(d))

        vis.legend.append("text")
            .attr("class", "legend-text")
            .attr("x", vis.R * 2)
            .attr("y", vis.R / 2)
            .style("fill", "#A9A9A9")
            .style("font-size", 12)
            .text(d => d)

        // line generator
        vis.line = d3.line()
            .x(d => vis.xScale(d.date))
            .y(d => vis.yScale(d.value))

        vis.renderChart("Code") // inital chart render (set default to Bidding Exercise 1 data)

        // Update chart when radio button is selected
        d3.selectAll(("input[name='metric']")).on('change', function () {
            vis.updateChart("Code", false)
        })
        vis.#initSliders()
    }

    #initSliders() {
        const vis = this;
        vis.slider = {
            firstLabelId: vis.parentElement + "-firstDateLabel",
            secondLabelId: vis.parentElement + "-secondDateLabel",
            sliderId: vis.parentElement + "-slider"
        }
        $(vis.slider.firstLabelId).text(formatTime(vis.min))
        $(vis.slider.secondLabelId).text(formatTime(vis.max))
        $(vis.slider.sliderId).slider({
            range: true,
            max: vis.max.getTime(), //max date of all data
            min: vis.min.getTime(), //min date of all data
            step: 86400000, // one day
            values: [
                vis.min.getTime(),
                vis.max.getTime()
            ],
            slide: (event, ui) => {
                $(vis.slider.firstLabelId).text(formatTime(new Date(ui.values[0])))
                $(vis.slider.secondLabelId).text(formatTime(new Date(ui.values[1])))
                // vis.updateChart($("input[name='metric']").attr('value'), true)
                vis.updateChart("Code", true)
            }
        })
    }
}
