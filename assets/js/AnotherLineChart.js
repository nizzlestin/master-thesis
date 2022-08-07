import * as d3 from 'd3';

var $ = require('jquery');
const parseTime = d3.timeParse("%d/%m/%Y")
const formatTime = d3.timeFormat("%d/%m/%Y")

export class AnotherLineChart {
    constructor(_parentElement, _data, _config) {
        this.config = _config;
        this.data = _data;
        this.unique = [...new Set(this.data.map(d => d.language))];
        this.uniqueDict = [];
        this.unique.map((k, v) => this.uniqueDict[k] = 0)
        this.parentElement = _parentElement;
        this.variable = _config.metric;

        this.init();
    }

    wrangle() {
        const vis = this;
        var groupedByDate = d3.group(vis.data, d => formatTime(d.date));
        groupedByDate = Array.from(groupedByDate, ([key, values]) => ({key, values}));
        vis.dataFiltered = groupedByDate
            .map(day => {
                    return day.values.reduce(
                        (accumulator, current) => {
                            accumulator.date = day.key;
                            accumulator[current.language] = accumulator[current.language] + current[vis.variable];
                            return accumulator
                        }, structuredClone(vis.uniqueDict))
                }
            );
        const arr = [];


        vis.dataFiltered.forEach(d => {
            vis.unique.forEach(x => {
                arr.push({"date": d.date, "language": x, "metric": d[x]})
            })
        })
        vis.dataFiltered = arr
        vis.renderableData = d3.group(vis.dataFiltered, d => d.language)
        vis.update()
    }


    update() {
        const vis = this;
        vis.t = d3.transition().duration(750);
        vis.x = vis.x.domain(d3.extent(vis.dataFiltered, (d) => parseTime(d.date)));
        vis.y = vis.y.domain([0, d3.max(vis.dataFiltered, function (d) {
            return d.metric;
        })]);
        vis.line = d3.line()
            .x(d => vis.x(parseTime(d.date)))
            .y(d => vis.y(d.metric))
            .curve(d3.curveBasis)
        vis.renderableData.forEach(d => {
        })
        // update axes
        vis.xAxisCall.scale(vis.x);
        vis.xAxis.transition(vis.t).call(vis.xAxisCall);
        vis.yAxisCall.scale(vis.y);
        vis.yAxis.transition(vis.t).call(vis.yAxisCall);
        vis.lines = vis.g.append('g')
        vis.lines.selectAll(".line")
            .attr("class", "line")
            .data(vis.renderableData)
            .enter()
            .append("path")
            .attr("d", d => {
                return d3.line()
                    .x(d => vis.x(parseTime(d.date)))
                    .y(d => vis.y(d.metric))
                    (d[1])
            })
            .attr("fill", "none")
            // .attr('transform', `transform(${vis.MARGIN.LEFT}, ${vis.MARGIN.TOP})`)
            .attr("stroke", d => vis.color(d[0]))
            .attr("stroke-width", 2)
    }

    drawLines() {

    }

    init() {
        const vis = this;
        vis.#preparePlot();
        vis.#prepareScales();
        vis.#addLegend();

        vis.wrangle();
    }

    #preparePlot() {
        const vis = this;
        // vis.unique = [...new Set(this.data.map(d => d.language))];

        vis.MARGIN = {LEFT: 80, RIGHT: 100, TOP: 50, BOTTOM: 40};
        vis.WIDTH = 800 - vis.MARGIN.LEFT - vis.MARGIN.RIGHT;
        vis.HEIGHT = 370 - vis.MARGIN.TOP - vis.MARGIN.BOTTOM;

        vis.svg = d3.select(vis.parentElement).append("svg")
            .attr("width", vis.WIDTH + vis.MARGIN.LEFT + vis.MARGIN.RIGHT)
            .attr("height", vis.HEIGHT + vis.MARGIN.TOP + vis.MARGIN.BOTTOM);

        vis.g = vis.svg.append("g") // vis.svg.g
            .attr("transform", `translate(${vis.MARGIN.LEFT}, ${vis.MARGIN.TOP})`);
    }

    #prepareScales() {
        const vis = this;
        vis.color = d3.scaleOrdinal(d3.schemeCategory10).domain(vis.unique);
        vis.x = d3.scaleTime().range([0, vis.WIDTH]);
        vis.y = d3.scaleLinear().range([vis.HEIGHT, 0]);
        vis.yAxisCall = d3.axisLeft();
        vis.xAxisCall = d3.axisBottom()
            .ticks(4);
        vis.xAxis = vis.g.append("g") // vis.svg.g.g
            .attr("class", "x axis")
            .attr("transform", `translate(0, ${vis.HEIGHT})`);
        vis.yAxis = vis.g.append("g") // vis.svg.g.(g,g)
            .attr("class", "y axis");

    }

    #addLegend() {
        const vis = this;
        const legend = vis.g.append("g") // vis.svg.g.(g,g,g)
            .attr("transform", "translate(0, -25)");

        const legendArray = [];
        vis.unique.forEach(lang => legendArray.push({
                label: lang, color: vis.color(lang)
            })
        );

        const legendCol = legend.selectAll(".legendCol")
            .data(legendArray)
            .enter().append("g")
            .attr("class", "legendCol")
            .attr("transform", (d, i) => `translate(${i * 120}, 0)`);

        legendCol.append("rect")
            .attr("class", "legendRect")
            .attr("width", 10)
            .attr("height", 10)
            .attr("fill", d => d.color)
            .attr("fill-opacity", 1);

        legendCol.append("text")
            .attr("class", "legendText")
            .attr("x", 20)
            .attr("y", 10)
            .attr("text-anchor", "start")
            .text(d => d.label)
    }
}
