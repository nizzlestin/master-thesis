import * as d3 from 'd3';
require('webpack-jquery-ui/slider');
require('webpack-jquery-ui/css');
import $ from "jquery";

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
        this.#init();
    }

    wrangle() {
        const vis = this;
        var groupedByDate = d3.group(vis.data, d => formatTime(d.date));
        groupedByDate = Array.from(groupedByDate, ([key, values]) => ({key, values}));
        vis.allData = groupedByDate
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


        vis.allData.forEach(d => {
            vis.unique.forEach(x => {
                arr.push({"date": d.date, "language": x, "metric": d[x]})
            })
        })
        vis.allData = arr
        // vis.renderableData = d3.group(vis.allData, d => d.language)
    }


    update() {
        const vis = this;
        vis.t = d3.transition().duration(750);
        const sliderValues = $(vis.slider.sliderId).slider("values");
        vis.dataTimeFiltered = vis.allData.filter(d => {
            return ((parseTime(d.date).getTime() >= sliderValues[0]) && (parseTime(d.date).getTime() <= sliderValues[1]))
        })
        vis.renderableData = d3.group(vis.dataTimeFiltered, d => d.language);

        vis.x = vis.x.domain(d3.extent(vis.dataTimeFiltered, (d) => parseTime(d.date)));
        vis.y = vis.y.domain([0, d3.max(vis.dataTimeFiltered, function (d) {
            return d.metric;
        })]);

        // update axes
        vis.xAxisCall.scale(vis.x);
        vis.xAxis.transition(vis.t).call(vis.xAxisCall);
        vis.yAxisCall.scale(vis.y);
        vis.yAxis.transition(vis.t).call(vis.yAxisCall);
        if(vis.lines) {
            vis.lines.selectAll(".line").remove();
        }
        vis.lines = vis.g.append('g');

        vis.lines.selectAll(".line")
            // .attr("class", "line")
            .data(vis.renderableData)
            .enter()
            .append("g")
            .attr("class", "line")
            .append("path")
            .transition(vis.t)
            .attr("d", d => {
                return d3.line()
                    .x(d => vis.x(parseTime(d.date)))
                    .y(d => vis.y(d.metric))
                    (d[1])
            })
            .attr("fill", "none")
            .attr("stroke", d => vis.color(d[0]))
            .attr("stroke-width", 2)
    }


    #init() {
        const vis = this;
        vis.#preparePlot();
        vis.#prepareScales();
        vis.#addLegend();
        vis.wrangle();
        vis.min = d3.min(vis.allData, (d) => parseTime(d.date))
        vis.max = d3.max(vis.allData, (d) => parseTime(d.date))
        vis.#initSliders()
        vis.update();

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

    #initSliders() {
        const vis = this;
        vis.slider = {
            firstLabelId : vis.parentElement+"-firstDateLabel",
            secondLabelId : vis.parentElement+"-secondDateLabel",
            sliderId : vis.parentElement+"-slider"
        }

        $(vis.slider.firstLabelId).text(formatTime(vis.min))
        $(vis.slider.secondLabelId).text(formatTime(vis.max))
        $(vis.slider.sliderId).slider({
            range: true,
            max: vis.max.getTime(),
            min: vis.min.getTime(),
            step: 86400000, // one day
            values: [
                vis.min.getTime(),
                vis.max.getTime()
            ],
            slide: (event, ui) => {
                $(vis.slider.firstLabelId).text(formatTime(new Date(ui.values[0])))
                $(vis.slider.secondLabelId).text(formatTime(new Date(ui.values[1])))
                vis.update()
            }
        })
    }
}
