import * as d3 from 'd3';
export default class LineChart {
    constructor(_parentElement) {
        this.parentElement = _parentElement;
        this.#initVis();
    }
    #initVis() {
        const vis = this;
        vis.MARGIN = { LEFT: 100, RIGHT: 100, TOP: 30, BOTTOM: 30 }
        vis.WIDTH = 800 - vis.MARGIN.LEFT - vis.MARGIN.RIGHT
        vis.HEIGHT = 350 - vis.MARGIN.TOP - vis.MARGIN.BOTTOM

        vis.svg = d3.select(vis.parentElement).append("svg")
            .attr("width", vis.WIDTH + vis.MARGIN.LEFT + vis.MARGIN.RIGHT)
            .attr("height", vis.HEIGHT + vis.MARGIN.TOP + vis.MARGIN.BOTTOM)

        vis.g = vis.svg.append("g")
            .attr("transform", `translate(${vis.MARGIN.LEFT}, ${vis.MARGIN.TOP})`)

        vis.parseTime = d3.timeParse("%d/%m/%Y")
        vis.formatTime = d3.timeFormat("%d/%m/%Y")
    }

    updateVis() {
        const vis = this;
        
    }
}
