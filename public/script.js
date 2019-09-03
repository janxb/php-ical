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
			ajaxRequest: null,
			pendingRequests: 0,
			event: null,
			password: Cookies.get('password'),
			rawPassword: "",
			isAuthenticated: null
		},
		computed: {
			selectedMonth: function () {
				return moment(this.year + "-" + this.month + "-01", "YYYY-MM-DD");
			},
			currentMonth: function () {
				return moment().startOf('month');
			},
			isLoading: function () {
				return this.pendingRequests > 0;
			},
			calendarDays: function () {
				const days = [];
				const self = this;
				const today = new Date();
				this.daysInPreviousMonth.forEach(function (day) {
					const date = moment(self.year + '-' + self.month + '-01', 'YYYY-MM-DD').subtract(1, 'month').add(day - 1, 'days');
					days.push({
						date: date,
						isCurrentMonth: false,
						isCurrentDay: false,
						dayOfWeek: moment(date).add(1, 'days').weekday()
					});
				});
				this.daysInMonth.forEach(function (day) {
					const date = moment(self.year + '-' + self.month + '-' + day, 'YYYY-MM-DD');
					days.push({
						date: date,
						isCurrentMonth: true,
						isCurrentDay: date.isSame(today, "day"),
						dayOfWeek: moment(date).add(1, 'days').weekday()
					});
				});
				this.daysInNextMonth.forEach(function (day) {
					const date = moment(self.year + '-' + self.month + '-01', 'YYYY-MM-DD').add(1, 'month').add(day - 1, 'days');
					days.push({
						date: date,
						isCurrentMonth: false,
						isCurrentDay: false,
						dayOfWeek: moment(date).add(1, 'days').weekday()
					});
				});
				return days;
			},
			daysInMonth: function () {
				let dayCount = moment(this.year + '-' + this.month, 'YYYY-MM').daysInMonth();
				return _.range([start = 1], dayCount + 1, [step = 1]);
			},
			daysInPreviousMonth: function () {
				let currentMonth = moment(this.year + '-' + this.month + '-01', 'YYYY-MM-DD');
				let dayNumInCurrentMonth = currentMonth.format('E') - 1;
				let previousMonth = currentMonth.subtract(1, 'month');
				let startDay = previousMonth.daysInMonth() - dayNumInCurrentMonth;
				return _.range([start = startDay + 1], startDay + dayNumInCurrentMonth + 1, [step = 1]);
			},
			daysInNextMonth: function () {
				return _.range(
					[start = 1],
					7 * 6 - (this.daysInPreviousMonth.length + this.daysInMonth.length) + 1,
					[step = 1]);
			}
		},
		watch: {
			rawPassword: function (value) {
				this.password = CryptoJS.SHA1(value).toString();
			}
		},
		methods: {
			savePassword: function () {
				document.location.hash = 'p=' + this.password;
				$('#passwordModal').modal('hide');
			},
			loadEvents: function () {
				if (this.ajaxRequest) this.ajaxRequest.abort();
				this.pendingRequests++;
				this.ajaxRequest = $.ajax({
					dataType: "json",
					url: 'api/events/' + this.year + '/' + this.month + '?p=' + this.password,
					success: function (data) {
						data.calendars.forEach(function (calendar) {
							calendar.events.forEach(function (event) {
								event.calendarName = calendar.name;
								event.dateStart = moment(event.dateStart);
								event.dateEnd = moment(event.dateEnd);
							});
						});
						app.calendars = data.calendars;
						app.isAuthenticated = data.isAuthenticated;

						sleep(10).then(() => {
							$('[data-toggle="tooltip"]').tooltip('dispose').tooltip({
								placement: 'top',
								boundary: 'window'
							});
							app.initEventColors();
						});
					},
					error: function (response) {
						if (response.status === 403) {
							app.showPasswordDialog();
						}
					},
					complete: function () {
						sleep(10).then(() => {
							app.pendingRequests--;
						});
					}
				});
			},
			showPasswordDialog: function () {
				this.rawPassword = "";
				$('#passwordModal').modal('show');
				$('#rawPassword').focus();
			},
			logout: function () {
				Cookies.remove('password');
				this.password = "";
				this.rawPassword = "";
				this.calendars = [];
				this.loadEvents();
			},
			isEventOnDate: function (event, date) {
				return moment().range(
					moment(event.dateStart).set({'hour': 0, 'minute': 0, 'second': 0}),
					moment(event.dateEnd).set({'hour': 0, 'minute': 0, 'second': 0})
				).contains(date);
			},
			isEventStartingOnDate: function (event, date) {
				return event.dateStart.isSame(date, "day");
			},
			isEventEndingOnDate: function (event, date) {
				return event.dateEnd.isSame(date, "day");
			},
			navigateMonthCurrent: function () {
				this.navigateMonth(this.currentMonth.diff(this.selectedMonth, 'months'));
			},
			navigateMonth: function (step) {
				const newMonth = moment(this.selectedMonth).add(step, 'months');
				this.month = newMonth.format("MM");
				this.year = newMonth.format("YYYY");
				history.replaceState(null, null, document.location.pathname + '#' + 'y=' + this.year + '&m=' + this.month);
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
						$this.css('background', 'transparent');
						$this.css('color', $this.parent().data('color'));
					}
				});
			},
			parseHash: function () {
				const urlPassword = url('#p');
				if (urlPassword) {
					Cookies.set('password', urlPassword, {expires: 365});
					this.password = urlPassword;
					history.replaceState({}, document.title, ".");
				}

				if (url('#m'))
					this.month = url('#m');
				if (url('#y'))
					this.year = url('#y');

				if (!url('#m') || !url('#y'))
					sleep(10).then(() => {
						this.navigateMonth(0);
					});
				else
					this.loadEvents();

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
			this.parseHash();
			$(window).on('hashchange', function () {
				app.parseHash();
			});
		}
	});
});