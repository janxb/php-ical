function sleep(time) {
    return new Promise((resolve) => setTimeout(resolve, time));
}

$('document').ready(function () {
    window['moment-range'].extendMoment(moment);

    var app = new Vue({
        el: '#app',
        data: {
            year: moment().format('YYYY'),
            month: moment().format('MM'),
            calendars: [],
            pendingRequests: 0,
            event: null
        },
        computed: {
            isLoading: function () {
                return this.pendingRequests > 0;
            },
            daysInMonth: function () {
                let dayCount = moment(this.year + '-' + this.month, 'YYYY-MM').daysInMonth();
                return _.range([start = 1], dayCount + 1, [step = 1]);
            },
            daysInPreviousMonth: function () {
                let previousMonth = moment(this.year + '-' + (this.month - 1) + '-01', 'YYYY-MM-DD');
                let currentMonth = moment(this.year + '-' + this.month + '-01', 'YYYY-MM-DD');
                let dayCount = currentMonth.format('E') - 1;
                let startDay = previousMonth.daysInMonth() - dayCount;
                return _.range([start = startDay + 1], startDay + dayCount + 1, [step = 1]);
            },
            daysInNextMonth: function () {
                return 7 * 6 - (this.daysInPreviousMonth.length + this.daysInMonth.length);
            }
        },
        watch: {},
        methods: {
            loadEvents: function () {
                this.pendingRequests++;
                $.getJSON('api/events/' + this.year + '/' + this.month, function (calendars) {
                    calendars.forEach(function (calendar) {
                        calendar.events.forEach(function (event) {
                            event.dateStart = moment(event.dateStart);
                            event.dateEnd = moment(event.dateEnd);
                        });
                    });
                    app.calendars = calendars;
                    sleep(10).then(() => {
                        $('[data-toggle="tooltip"]').tooltip('dispose').tooltip({placement: 'top', boundary: 'window'});
                        app.initEventColors();
                        app.pendingRequests--;
                    });
                });
            },
            isEventOnDay: function (event, day) {
                return moment().range(
                    moment(event.dateStart).set({'hour': 0, 'minute': 0, 'second': 0}),
                    moment(event.dateEnd).set({'hour': 0, 'minute': 0, 'second': 0})
                ).contains(moment(this.year + '-' + this.month + '-' + day, 'YYYY-MM-DD'));
            },
            isCurrentDay: function (month, day) {
                return moment().format('YYYY-MM-D') === (this.year + '-' + month + '-' + day);
            },
            isEventStartingToday: function (event, month, day) {
                return event.dateStart.format('YYYY-MM-D') === (this.year + '-' + month + '-' + day);
            },
            isEventEndingToday: function (event, month, day) {
                return event.dateEnd.format('YYYY-MM-D') === (this.year + '-' + month + '-' + day);
            },
            navigateMonth: function (step) {
                this.month = parseInt(this.month) + parseInt(step);
                if (this.month < 1) {
                    this.month = 12;
                    this.year--;
                } else if (this.month > 12) {
                    this.month = 1;
                    this.year++;
                }
                this.month = _.padStart(this.month, 2, '0');
                this.loadEvents();
            },
            showEventDetails: function (event) {
                this.event = event;
                sleep(10).then(() => {
                    $('#eventDetails').modal('show');
                });
            },
            initEventColors() {
                $("#calendar .event").each(function () {
                    let $this = $(this);
                    if ($this.hasClass('fullDay')) {
                        $this.css('background', $this.parent().data('color'));
                        $this.css('color', 'white');
                    } else {
                        $this.css('background', 'white');
                        $this.css('color', $this.parent().data('color'));
                    }
                });
            }
        },
        filters: {
            time: function (date) {
                return date.format('LT');
            },
            monthName: function (month) {
                return moment(month, 'M').format('MMMM');
            },
            dayName: function (day) {
                return moment(day, 'd').format('dddd');
            },
            date: function (date) {
                return date.format('L')
            },
            fullDate: function (date) {
                return date.format('LLL');
            }
        },
        beforeMount() {
            this.loadEvents();
        }
    });
});