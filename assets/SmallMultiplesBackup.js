import './styles/app.scss';
import $ from 'jquery';

import {SmallMultiplesBackup} from "./js/SmallMultiplesBackup";
import * as d3 from 'd3';

const parseTime = d3.timeParse("%d/%m/%Y");
let loc;

$(document).ready(() => {
    function makeDict(d, m, v, c, h) {
        return {date: parseTime(d), metric: m, value: v, category: c, hash: h};
    }
    function makeEmtpyEntryWithCategory(h, l, d, f = "") {
        return {commit_date: d, commit: h, language: l, bytes: 0, lines: 0, code: 0, comment: 0, blank: 0, complexity: 0, count: 0, file: f }
    }

    var groupBy = function (xs, key) {
        return xs.reduce(function (rv, x) {
            (rv[x[key]] = rv[x[key]] || []).push(x);
            return rv;
        }, {});
    };
    const asset = $('[data-asset-url]').data('asset-url');
    d3.json(asset).then((data) => {

        var categories = [...new Set(data.map(d => d.file))];
        var categoryLength = categories.length
        data = data.reverse()

        var data2 = groupBy(data, 'commit')
        var finalData = []
        for (const [key, value] of Object.entries(data2)) {
            if (value.length !== categoryLength) {
                const entryCategories = [...new Set(value.map(d => d.file))];
                const filteredArray = categories.filter(v => !entryCategories.includes(v));
                filteredArray.forEach(l => {

                    finalData.push(makeEmtpyEntryWithCategory(key, l, value[0].commit_date, l))
                })
            }

            value.forEach(existingV => finalData.push(existingV))
        }

        var res = finalData.map((d, i) => {
            var ratio = Math.round(d.code*100 / (d.comment + d.code))/100;
            var ltoc = ratio ? ratio : 1.0;
            let complexityPerLine = Math.round(d.complexity*100/d.code)/100;
            complexityPerLine = complexityPerLine ? complexityPerLine : 0.0

            ltoc = ltoc*100
            return [
                makeDict(d.commit_date, 'Code', d.code, d.file, d.commit),
                makeDict(d.commit_date, 'ltocratio', ltoc, d.file, d.commit),
                makeDict(d.commit_date, 'StatComplexity', complexityPerLine, d.file, d.commit),
                makeDict(d.commit_date, 'Complexity', d.complexity, d.file, d.commit),
            ]
        })
        res = res.flat()
        loc = new SmallMultiplesBackup("#small-multiples", res, {'metric': 'Code'});
        var $optionsCardCardBody = $('#options-card').find('.card-body');
        var checkboxes = []
        categories.forEach(d => {
            var div = document.createElement("div");
            div.setAttribute("class", "form-check");
            var label = document.createElement("label");
            label.setAttribute("class", "form-check-label");
            var input = document.createElement("input");
            input.setAttribute("class", "form-check-input category-filter");
            input.setAttribute("type", "checkbox");
            input.setAttribute("value", d);
            input.checked = 1;
            label.innerText = d;
            label.append(input);
            div.append(label);
            checkboxes.push(div);
        })
        $optionsCardCardBody.append(checkboxes)
        $(":checkbox").change(function() {
            loc.rerender();
        })
    })
})
