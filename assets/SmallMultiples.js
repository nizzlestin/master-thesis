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

import {SmallMultiples} from "./js/SmallMultiples";
import * as d3 from 'd3';

const parseTime = d3.timeParse("%d/%m/%Y");
const formatTime = d3.timeFormat("%d/%m/%Y");
let loc;
let comment;
let blanks;
let locvscomments;
let complexity;

$(document).ready(() => {
    function makeDict(d, m, v, l, h) {
        return {date: parseTime(d), metric: m, value: v, language: l, hash: h};
    }
    function makeEmtpyEntryWithLanguage(h, l, d) {
        return {date: d, hash: h, language: l, Bytes: 0, CodeBytes: 0, Lines: 0, Code: 0, Comment: 0, Blank: 0, Complexity: 0, Count: 0, WeightedComplexity: 0, Files: []}
    }

    var groupBy = function (xs, key) {
        return xs.reduce(function (rv, x) {
            (rv[x[key]] = rv[x[key]] || []).push(x);
            return rv;
        }, {});
    };
    const asset = $('[data-asset-url]').data('asset-url');
    d3.json(asset).then((data) => {
        var languages = [...new Set(data.map(d => d.language))];
        var languageLength = languages.length
        var allDates = [...new Set(data.map(d => d.date))];
        data = data.reverse()
        var data2 = groupBy(data, 'hash')
        var finalData = []
        for (const [key, value] of Object.entries(data2)) {
            if (value.length != languageLength) {
                const entryLanguages = [...new Set(value.map(d => d.language))];
                const filteredArray = languages.filter(v => !entryLanguages.includes(v));
                filteredArray.forEach(l => {
                    finalData.push(makeEmtpyEntryWithLanguage(key, l, value[0].date))
                })
            }

            value.forEach(existingV => finalData.push(existingV))
        }

        var res = finalData.map((d, i) => {
            var ratio = Math.round(d.Code*100 / (d.Comment + d.Code))/100;
            var ltoc = ratio ? ratio : 1.0;
            return [
                makeDict(d.date, 'Code', d.Code, d.language, d.hash),
                makeDict(d.date, 'ltocratio', ltoc, d.language, d.hash),
                makeDict(d.date, 'Complexity', d.Complexity, d.language, d.hash),
            ]
        })
        res = res.flat()
        loc = new SmallMultiples("#small-multiples", res, {'metric': 'Code'});
    })
})
