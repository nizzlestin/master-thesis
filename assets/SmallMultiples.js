import './styles/app.scss';
import $ from 'jquery';

import {SmallMultiples} from "./js/SmallMultiples";
import * as d3 from 'd3';

const parseTime = d3.timeParse("%d/%m/%Y");
let loc;

$(document).ready(() => {
    function makeDict(d, m, v, l, h, axis) {
        return {date: parseTime(d), metric: m, value: v, language: l, hash: h, axis: axis};
    }
    function makeEmtpyEntryWithLanguage(h, l, d) {
        return {commit_date: d, commit: h, language: l, bytes: 0, lines: 0, code: 0, comment: 0, blank: 0, complexity: 0, count: 0 }
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
        data = data.reverse()

        var data2 = groupBy(data, 'commit')
        var finalData = []
        for (const [key, value] of Object.entries(data2)) {
            if (value.length !== languageLength) {
                const entryLanguages = [...new Set(value.map(d => d.language))];
                const filteredArray = languages.filter(v => !entryLanguages.includes(v));
                filteredArray.forEach(l => {
                    finalData.push(makeEmtpyEntryWithLanguage(key, l, value[0].commit_date))
                })
            }

            value.forEach(existingV => finalData.push(existingV))
        }
        finalData = finalData.sort((a, b) => {
            return d3.ascending(a.language, b.language)
        } )

        var res = finalData.map((d, i) => {
            var ratio = Math.round(d.code*100 / (d.comment + d.code))/100;
            var ltoc = ratio ? ratio : 1.0;
            ltoc = ltoc*100
            let complexityPerLine = Math.round(d.complexity*100/d.code)/100;
            complexityPerLine = complexityPerLine ? complexityPerLine : 0.0
            return [
                makeDict(d.commit_date, 'Code', d.code, d.language, d.commit, {y : 'SLOC', x: 'Time'}),
                makeDict(d.commit_date, 'ltocratio', ltoc, d.language, d.commit, {y : 'CC', x: 'Time'}),
                makeDict(d.commit_date, 'StatComplexity', complexityPerLine, d.language, d.commit, {y : 'Complexity per Line', x: 'Time'}),
                makeDict(d.commit_date, 'Complexity', d.complexity, d.language, d.commit, {y : 'SLOC', x: 'Time'}),
            ]
        })

        res = res.flat()
        res.sort((a,b) => {
            return d3.ascending(a.language, b.language)
        })
        loc = new SmallMultiples("#small-multiples", res, {'metric': 'Code'});
        var $optionsCardCardBody = $('#options-card').find('.card-body');
        var checkboxes = []
        languages.forEach(d => {
            var div = document.createElement("div");
             div.setAttribute("class", "form-check");
            var label = document.createElement("label");
            label.setAttribute("class", "form-check-label");
            var input = document.createElement("input");
            input.setAttribute("class", "form-check-input language-filter");
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
