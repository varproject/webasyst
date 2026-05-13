/*$(function () {*/
  $.wrlDates = {
      isToday: function (d) {
        const now = new Date();
        return d.getDate() === now.getDate() && d.getMonth() === now.getMonth() && d.getFullYear() === now.getFullYear();
      },
      isYesterday: function (d) {
        const yesterday = new Date();
        yesterday.setDate(yesterday.getDate() - 1);
        return d.getDate() === yesterday.getDate() && d.getMonth() === yesterday.getMonth() && d.getFullYear() === yesterday.getFullYear();
      },
      isCurrentYear: function (d) {
        const now = new Date();
        return d.getFullYear() === now.getFullYear();
      },
      humanDateTime: function (d, loc) {
        const time = ' ' + `0${d.getHours()}`.slice(-2) + ':' + `0${d.getMinutes()}`.slice(-2);

        if (this.isToday(d)) return this.l10n('Сегодня', loc) + time;
        if (this.isYesterday(d)) return this.l10n('Вчера', loc) + time;

        return d.getDate() + ' ' +
            this.l10n(['января', 'февраля', 'марта', 'апреля', 'мая', 'июня', 'июля', 'августа', 'сентября', 'октября', 'ноября', 'декабря'][d.getMonth()], loc) +
            ` ${d.getFullYear()}` + time;
      },
      humanDate: function (d, loc) {
          if (this.isToday(d)) return this.l10n('Сегодня', loc);
          if (this.isYesterday(d)) return this.l10n('Вчера', loc);

          return d.getDate() + ' ' +
              this.l10n(['января', 'февраля', 'марта', 'апреля', 'мая', 'июня', 'июля', 'августа', 'сентября', 'октября', 'ноября', 'декабря'][d.getMonth()], loc) +
              ` ${d.getFullYear()}`;
      },
      simpleDateTime: function (d, loc) {
          const time = ' ' + `0${d.getHours()}`.slice(-2) + ':' + `0${d.getMinutes()}`.slice(-2);
          return `${d.getFullYear()}` + '-' + d.getMonth()+ '-' + d.getDate() + ' ' + time;
      },
      simpleDate: function (d, loc) {
          return `${d.getFullYear()}` + '-' + d.getMonth()+ '-' + d.getDate();
      },
      fullDate: function (d, loc) {
          return d.getDate() + ' ' +
              this.l10n(['января', 'февраля', 'марта', 'апреля', 'мая', 'июня', 'июля', 'августа', 'сентября', 'октября', 'ноября', 'декабря'][d.getMonth()], loc) +
              ` ${d.getFullYear()}`;
      },
      isOlderThanDays: function (d, n) {
        const date1 = new Date(d.getFullYear(), d.getMonth(), d.getDate());
        const past_date = new Date();
        past_date.setHours(0, 0, 0, 0);
        past_date.setDate(past_date.getDate() - n);
        return past_date > date1;
      },
      l10n: function (str, strings) {
        if (strings && strings.hasOwnProperty(str)) return strings[str];
        //if (window.$_ && typeof window.$_ === 'function') return $_(str);
        return str;
      }
    }
/*
});*/
