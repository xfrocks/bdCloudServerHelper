//noinspection ThisExpressionReferencesGlobalObjectJS,JSUnusedLocalSymbols
/** @param {jQuery} $ jQuery Object */
!function ($, window, document, _undefined) {

    window.formatCount = function (value) {
        if (value > 1000000) {
            return (Math.round(value / 1000000 * 10) / 10) + 'm';
        } else if (value > 1000) {
            return (Math.round(value / 1000 * 10) / 10) + 'k';
        } else {
            return value;
        }
    };
    window.formatTime = function (value, decision) {
        if (!decision) {
            decision = 2;
        }
        var pow10 = Math.pow(10, decision);

        return (Math.round(value * pow10) / pow10) + 's';
    };
    window.formatPercent = function (value, total) {
        return (Math.round(value / total * 100 * 100) / 100);
    };

    XenForo.bdCloudServerHelper_LiveStats = function ($div) {
        this.__construct($div);
    };
    XenForo.bdCloudServerHelper_LiveStats.prototype = {
        __construct: function ($div) {
            this.$div = $div;
            this.delay = $div.data('delay');
            this.disabledText = $div.data('disabledText');
            this.url = $div.data('url');

            this.segment = $div.data('segment');
            this.stats = {
                success: parseInt($div.find('.success').data('value')),
                '4xx': parseInt($div.find('.4xx').data('value')),
                error: parseInt($div.find('.error').data('value')),
                pageTime: parseInt($div.find('.pageTime').data('value'))
            };
            this.sum = {
                success: 0,
                '4xx': 0,
                error: 0,
                pageTime: 0
            };

            this.$loadavgs = $($div.data('loadavgsSelector'));
            if (this.$loadavgs.length == 0) {
                this.$loadavgs = null;
            }

            if (this.segment && this.url) {
                this.scheduleTimeout();
            }
        },

        disable: function () {
            for (var type in this.stats) {
                if (!this.stats.hasOwnProperty(type)) {
                    continue;
                }

                this.selectByType(type).text('N/A');
            }

            this.selectByType('total').text(this.disabledText);

            if (this.$loadavgs) {
                this.$loadavgs.empty();
            }
        },

        refresh: function () {
            if (!this.url) {
                return;
            }

            if (!XenForo._hasFocus
                && this.disabledText) {
                return this.disable();
            }

            XenForo.ajax(this.url, {}, $.context(this, 'refreshSuccess'), {global: false});
        },

        refreshSuccess: function (ajaxData) {
            if (XenForo.hasResponseError(ajaxData)) {
                return false;
            }

            this.updateStats(ajaxData);
            this.updateLoadavg(ajaxData);

            this.scheduleTimeout();
        },

        updateStats: function (ajaxData) {
            if (ajaxData['currentSegment'] && ajaxData['currentStats']) {
                if (ajaxData['currentSegment'] != this.segment) {
                    // new segment data, save old stats to total
                    for (var i in this.stats) {
                        if (!this.stats.hasOwnProperty(i)) {
                            continue;
                        }
                        this.sum[i] += this.stats[i];
                    }
                    this.segment = ajaxData['currentSegment'];
                }

                // update current stats
                for (var j in this.stats) {
                    if (!this.stats.hasOwnProperty(j)) {
                        continue;
                    }
                    if (ajaxData['currentStats'][j]) {
                        this.stats[j] = ajaxData['currentStats'][j];
                    } else {
                        this.stats[j] = 0;
                    }
                }
            }

            var tmp = {};
            var total = 0;
            for (var z in this.sum) {
                if (!this.sum.hasOwnProperty(z)) {
                    continue;
                }
                tmp[z] = this.sum[z];
                if (this.stats[z]) {
                    tmp[z] += this.stats[z];
                }

                if (z != 'pageTime') {
                    total += tmp[z];
                }
            }
            tmp['total'] = total;

            for (var type in tmp) {
                if (!tmp.hasOwnProperty(type)) {
                    continue;
                }

                var $value = this.selectByType(type);
                var value = tmp[type];

                switch (type) {
                    case 'pageTime':
                        if (value > 0 && total > 0) {
                            value = window.formatTime(value / total, 5);
                        }
                        break;
                    default:
                        if ($value.is('.percent')) {
                            value = window.formatPercent(value, total)
                                + '% (' + window.formatCount(value)
                                + '/' + window.formatCount(total) + ')';
                        } else {
                            value = window.formatCount(value);
                        }
                        break;
                }

                $value.text(value);
            }
        },

        updateLoadavg: function (ajaxData) {
            if (!this.$loadavgs
                || !ajaxData['hostname']
                || !ajaxData['loadavg']) {
                return;
            }

            var $loadavg = null;

            this.$loadavgs.children().each(function () {
                var $this = $(this);
                if ($this.data('hostname') == ajaxData['hostname']) {
                    $loadavg = $this;
                }
            });

            if (!$loadavg) {
                $loadavg = $('<dl class="pairsJustified"><dt class="hostname"></dt><dd class="value"></dd></dl>')
                    .data('hostname', ajaxData['hostname'])
                    .xfInsert('appendTo', this.$loadavgs);
                $loadavg.find('.hostname').text(ajaxData['hostname']);
            }

            var $loadavgValue = $loadavg.find('.value');
            var loadavgText = ajaxData['loadavg'].join(' ');
            if ($loadavgValue.text() != loadavgText) {
                $loadavgValue.fadeOut(100, function () {
                    $loadavgValue.text(loadavgText).fadeIn();
                })
            }
        },

        scheduleTimeout: function () {
            if (this.delay > 0) {
                this.timeout = window.setTimeout($.context(this, 'refresh'), this.delay);
            } else {
                this.refresh();
            }
        },

        selectByType: function (type) {
            return this.$div.find('.' + type + ' .value');
        }
    };

    XenForo.register('div.currentStats', 'XenForo.bdCloudServerHelper_LiveStats');

}(jQuery, this, document);