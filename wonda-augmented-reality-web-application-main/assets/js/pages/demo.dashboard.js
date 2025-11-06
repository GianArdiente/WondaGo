! function(o) {
    "use strict";

    function e() {
        this.$body = o("body"), this.charts = []
    }
    e.prototype.initCharts = function() {
        window.Apex = {
            chart: {
                parentHeightOffset: 0,
                toolbar: {
                    show: !1
                }
            },
            grid: {
                padding: {
                    left: 0,
                    right: 0
                }
            },
            colors: ["#30382f", "#0acf97", "#fa5c7c", "#ffbc00"]
        };
        var e = ["#30382f", "#0acf97", "#fa5c7c", "#ffbc00"],
            t = o("#revenue-chart").data("colors");
        t && (e = t.split(","));

        // Helper to parse payment_info for category counts
        function parsePaymentInfo(paymentInfo) {
            const categories = ["Female", "Male", "PWD", "Pregnant", "Children", "Senior"];
            const result = {};
            categories.forEach(cat => result[cat] = 0);

            if (!paymentInfo) return result;

            paymentInfo.split(',').forEach(item => {
                categories.forEach(cat => {
                    if (item.startsWith(cat + ":")) {
                        // Handles formats like Female:2:950:25:1425
                        const parts = item.split(':');
                        // parts[1] is count
                        result[cat] = parseInt(parts[1], 10) || 0;
                    }
                });
            });
            return result;
        }

        // Prepare data for high-performing-product chart
        let categoryTotals = {
            Female: 0,
            Male: 0,
            PWD: 0,
            Pregnant: 0,
            Children: 0,
            Senior: 0
        };

        if (window.transactionsData && Array.isArray(window.transactionsData)) {
            window.transactionsData.forEach(tx => {
                const counts = parsePaymentInfo(tx.payment_info);
                Object.keys(categoryTotals).forEach(cat => {
                    categoryTotals[cat] += counts[cat];
                });
            });
        }

        // Prepare chart data
        const highPerfLabels = Object.keys(categoryTotals);
        const highPerfSeries = highPerfLabels.map(cat => categoryTotals[cat]);

        // High-performing-product chart config
        var r = {
            chart: {
                height: 364,
                type: "bar"
            },
            series: [{
                name: "Count",
                data: highPerfSeries
            }],
            colors: e,
            xaxis: {
                categories: highPerfLabels
            },
            dataLabels: {
                enabled: !0
            },
            legend: {
                show: !1
            }
        };

        new ApexCharts(document.querySelector("#high-performing-product"), r).render();

        // Average-sales donut chart (unchanged)
        e = ["#30382f", "#0acf97", "#fa5c7c", "#ffbc00"];
        (t = o("#average-sales").data("colors")) && (e = t.split(","));
        r = {
            chart: {
                height: 203,
                type: "donut"
            },
            legend: {
                show: !1
            },
            stroke: {
                colors: ["transparent"]
            },
            series: [44, 55, 41, 17],
            labels: ["Direct", "Affilliate", "Sponsored", "E-mail"],
            colors: e,
            responsive: [{
                breakpoint: 480,
                options: {
                    chart: {
                        width: 200
                    },
                    legend: {
                        position: "bottom"
                    }
                }
            }]
        };
        new ApexCharts(document.querySelector("#average-sales"), r).render()
    }, e.prototype.initMaps = function() {
        0 < o("#world-map-markers").length && o("#world-map-markers").vectorMap({
            map: "world_mill_en",
            normalizeFunction: "polynomial",
            hoverOpacity: .7,
            hoverColor: !1,
            regionStyle: {
                initial: {
                    fill: "#e3eaef"
                }
            },
            markerStyle: {
                initial: {
                    r: 9,
                    fill: "#30382f",
                    "fill-opacity": .9,
                    stroke: "#fff",
                    "stroke-width": 7,
                    "stroke-opacity": .4
                },
                hover: {
                    stroke: "#fff",
                    "fill-opacity": 1,
                    "stroke-width": 1.5
                }
            },
            backgroundColor: "transparent",
            markers: [{
                latLng: [40.71, -74],
                name: "New York"
            }, {
                latLng: [37.77, -122.41],
                name: "San Francisco"
            }, {
                latLng: [-33.86, 151.2],
                name: "Sydney"
            }, {
                latLng: [1.3, 103.8],
                name: "Singapore"
            }],
            zoomOnScroll: !1
        })
    }, e.prototype.init = function() {
        o("#dash-daterange").daterangepicker({
            singleDatePicker: !0
        }), this.initCharts(), this.initMaps()
    }, o.Dashboard = new e, o.Dashboard.Constructor = e
}(window.jQuery),
function(t) {
    "use strict";
    t(document).ready(function(e) {
        t.Dashboard.init()
    })
}(window.jQuery);